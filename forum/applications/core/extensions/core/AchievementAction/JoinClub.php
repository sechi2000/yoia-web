<?php
/**
 * @brief		Achievement Action Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @since		04 Mar 2021
 */

namespace IPS\core\extensions\core\AchievementAction;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Achievements\Actions\AchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\Db;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member\Club;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement Action Extension
 */
class JoinClub extends AchievementActionAbstract // NOTE: Other classes exist to provided bases for common situations, like where node-based filters will be required
{
	/**
	 * Can use this rule?
	 *
	 * @return boolean
	 */
	public function canUse(): bool
	{
		return parent::canUse() and Settings::i()->clubs;
	}

	/**
	 * Get filter form elements
	 *
	 * @param	array|NULL		$filters	Current filter values (if editing)
	 * @param	Url	$url		The URL the form is being shown on
	 * @return	array
	 */
	public function filters( ?array $filters, Url $url ): array
	{
		$return = parent::filters( $filters, $url );

		$nthFilter = new Number( 'achievement_filter_club_joined_nth', ( $filters and isset( $filters['milestone'] ) and $filters['milestone'] ) ? $filters['milestone'] : 0, FALSE, [], NULL, Member::loggedIn()->language()->addToStack('achievement_filter_nth_their'), Member::loggedIn()->language()->addToStack('achievement_filter_JoinClub_nth_suffix') );
		$nthFilter->label = Member::loggedIn()->language()->addToStack('achievement_filter_JoinClub_nth');

		$return['milestone'] = $nthFilter;

		$clubs = iterator_to_array(
			Db::i()->select( 'id,name', 'core_clubs', [ '`type`!=?', Club::TYPE_PUBLIC ], 'name' )
				->setKeyField( 'id' )
				->setValueField( 'name' )
		);
		$return['clubs'] = new Select( 'achievement_filter_JoinClub_club', ( $filters and isset( $filters['clubs'] ) and $filters['clubs'] ) ? $filters['clubs'] : NULL, FALSE, [
				'options' => $clubs,
				'multiple' => true,
				'noDefault' => true
			], NULL, Member::loggedIn()->language()->addToStack('achievement_filter_JoinClub_club_prefix' ), null, 'achievement_filter_JoinClub_club' );
		
		return $return;
	}
	
	/**
	 * Format filter form values
	 *
	 * @param	array	$values	The values from the form
	 * @return	array
	 */
	public function formatFilterValues( array $values ): array
	{
		$return = parent::formatFilterValues( $values );

		if ( isset( $values['achievement_filter_club_joined_nth'] ) )
		{
			$return['milestone'] = $values['achievement_filter_club_joined_nth'];
		}

		if( isset( $values['achievement_filter_JoinClub_club'] ) )
		{
			$return['clubs'] = $values['achievement_filter_JoinClub_club'];
		}

		return $return;
	}
	
	/**
	 * Work out if the filters applies for a given action
	 *
	 * Important note for milestones: consider the context. This method is called by \IPS\Member::achievementAction(). If your code 
	 * calls that BEFORE making its change in the database (or there is read/write separation), you will need to add
	 * 1 to the value being considered for milestones
	 *
	 * @param	Member	$subject	The subject member
	 * @param	array		$filters	The value returned by formatFilterValues()
	 * @param	mixed		$extra		Any additional information about what is happening (e.g. if a post is being made: the post object)
	 * @return	bool
	 */
	public function filtersMatch( Member $subject, array $filters, mixed $extra = NULL ): bool
	{
		if ( isset( $filters['milestone'] ) )
		{
			$where = [];
			$where[] = [ "member_id=? and status IN('" . Club::STATUS_MEMBER .  "','" . Club::STATUS_MODERATOR . "','" . Club::STATUS_LEADER . "','" . Club::STATUS_EXPIRED . "','" . Club::STATUS_EXPIRED_MODERATOR . "')", $subject->member_id ];
					
			$count = Db::i()->select( 'COUNT(*)', 'core_clubs_memberships', $where )->first();
			
			if ( $count < $filters['milestone'] )
			{
				return FALSE;
			}
		}

		if( isset( $filters['clubs'] ) and $extra instanceof Club )
		{
			if( !in_array( $extra->id, $filters['clubs'] ) )
			{
				return false;
			}
		}
		
		return TRUE;
	}

	/**
	 * Get the labels for the people this action might give awards to
	 *
	 * @param	array|NULL		$filters	Current filter values
	 *
	 * @return	array
	 */
	 public function awardOptions( ?array $filters ): array
	 {
	 	return [
	 		'subject'	=> 'achievement_filter_JoinClub_joiner',
	 		'other'		=> 'achievement_filter_JoinClub_owner'
	 	];
	 }

	/**
	 * Get the "other" people we need to award =stuff to
	 *
	 * @param	mixed		$extra		Any additional information about what is happening (e.g. if a post is being made: the post object)
	 * @param	array|NULL	$filters	Current filter values
	 * @return	array
	 */
	public function awardOther( mixed $extra = NULL, ?array $filters = NULL ): array
	 {
	 	return [ $extra->owner ];
	 }

	/**
	 * Determines if the member has already completed this rule.
	 * Used for retroactive rule completion.
	 * So far, this is only used in Quests, but may be used elsewhere at a later point.
	 *
	 * @param Member $member
	 * @param array $filters
	 * @return bool
	 */
	public function isRuleCompleted( Member $member, array $filters ) : bool
	{
		if( !Settings::i()->clubs )
		{
			return false;
		}

		$where = [
			[ 'member_id=?', $member->member_id ],
			[ Db::i()->in( 'status', [ Club::STATUS_EXPIRED, Club::STATUS_EXPIRED_MODERATOR, Club::STATUS_MODERATOR, Club::STATUS_MEMBER, Club::STATUS_LEADER ] ) ]
		];

		$clubsJoined = iterator_to_array(
			Db::i()->select( 'club_id', 'core_clubs_memberships', $where )
		);

		if( !count( $clubsJoined ) )
		{
			return false;
		}

		if( !empty( $filters['clubs'] ) )
		{
			$match = array_intersect( $filters['clubs'], $clubsJoined );
			if( !count( $match ) )
			{
				return false;
			}
		}

		if( !empty( $filters['milestone'] ) )
		{
			return count( $clubsJoined ) >= $filters['milestone'];
		}

		return true;
	}
	
	/**
	 * Get identifier to prevent the member being awarded points for the same action twice
	 * Must be unique within within of this domain, must not exceed 32 chars.
	 *
	 * @param	Member	$subject	The subject member
	 * @param	mixed		$extra		Any additional information about what is happening (e.g. if a post is being made: the post object)
	 * @return	string
	 */
	public function identifier( Member $subject, mixed $extra = NULL ): string
	{
		return (string) $extra->id;
	}
	
	/**
	 * Return a description for this action to show in the log
	 *
	 * @param	string	$identifier	The identifier as returned by identifier()
	 * @param	array	$actor		If the member was the "subject", "other", or both
	 * @return	string
	 */
	public function logRow( string $identifier, array $actor ): string
	{
		$club = Club::load( $identifier );
		$clubLink = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $club->url(), TRUE, $club->name, FALSE );
			
		return Member::loggedIn()->language()->addToStack( 'AchievementAction__JoinClub_log', FALSE, [ 'htmlsprintf' => [ $clubLink ] ] );
	}
	
	/**
	 * Get "description" for rule
	 *
	 * @param	Rule	$rule	The rule
	 * @return	string|NULL
	 */
	public function ruleDescription( Rule $rule ): ?string
	{
		$conditions = [];
		if ( isset( $rule->filters['milestone'] ) )
		{
			$conditions[] = Member::loggedIn()->language()->addToStack( 'achievements_title_filter_milestone', FALSE, [
				'htmlsprintf' => [
					Theme::i()->getTemplate( 'achievements', 'core' )->ruleDescriptionBadge( 'milestone', Member::loggedIn()->language()->addToStack( 'achievements_title_filter_milestone_nth', FALSE, [ 'pluralize' => [ $rule->filters['milestone'] ] ] ) )
				],
				'sprintf'		=> [ Member::loggedIn()->language()->addToStack('AchievementAction__JoinClub_title_generic') ]
			] );
		}

		if( isset( $rule->filters['clubs'] ) )
		{
			$clubNames = iterator_to_array( Db::i()->select( 'name', 'core_clubs', Db::i()->in( 'id', $rule->filters['clubs'] ), 'name' ) );
			$conditions[] = Member::loggedIn()->language()->addToStack( 'achievements_title_filter_clubs', false, [
				'htmlsprintf' => [
					Theme::i()->getTemplate( 'achievements', 'core' )->ruleDescriptionBadge( 'clubs', Member::loggedIn()->language()->addToStack( 'achievement_filter_JoinClub_club_title_pl', false, [ 'pluralize' => [ count( $clubNames ) ] ] ), $clubNames )
				]
			] );
		}

		if( $questCondition = $this->_questFilterDescription( $rule ) )
		{
			$conditions[] = $questCondition;
		}

		return Theme::i()->getTemplate( 'achievements', 'core' )->ruleDescription(
			Member::loggedIn()->language()->addToStack( 'AchievementAction__JoinClub_title' ),
			$conditions
		);
	}

	/**
	 * Get rebuild data
	 *
	 * @return	array
	 */
	static public function rebuildData(): array
	{
		return [ [
			'table' => 'core_clubs_memberships',
			'pkey'  => 'club_id',
			'date'  => 'joined',
			'where' => [],
		] ];
	}

	/**
	 * Process the rebuild row
	 *
	 * @param array		$row	Row from database
	 * @param array		$data	Data collected when starting rebuild [table, pkey...]
	 * @return void
	 */
	public static function rebuildRow( array $row, array $data ) : void
	{
		if ( in_array( $row['status'], array( Club::STATUS_MEMBER, Club::STATUS_MODERATOR, Club::STATUS_LEADER, Club::STATUS_EXPIRED, Club::STATUS_EXPIRED_MODERATOR ) ) )
		{
			Member::load( $row['member_id'] )->achievementAction( 'core', 'JoinClub', Club::load( $row['club_id'] ) );
		}
	}
}