<?php
/**
 * @brief		Achievement Action Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @since		03 Mar 2021
 */

namespace IPS\core\extensions\core\AchievementAction;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Achievements\Actions\AchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\Db;
use IPS\Helpers\Form\Member as FormMember;
use IPS\Helpers\Form\Number;
use IPS\Http\Url;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Theme;
use OutOfRangeException;
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
class FollowMember extends AchievementActionAbstract
{	
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

		$nthFilter = new Number( 'achievement_filter_FollowMember_nth', ( $filters and isset( $filters['milestone'] ) and $filters['milestone'] ) ? $filters['milestone'] : 0, FALSE, [], NULL, Member::loggedIn()->language()->addToStack('achievement_filter_nth_their'), Member::loggedIn()->language()->addToStack('achievement_filter_FollowMember_nth_suffix') );
		$nthFilter->label = Member::loggedIn()->language()->addToStack('achievement_filter_FollowMember_nth');

		$return['milestone'] = $nthFilter;

		if( isset( $filters['members'] ) )
		{
			$selected = iterator_to_array(
				new ActiveRecordIterator(
					Db::i()->select( '*', 'core_members', [ Db::i()->in( 'member_id', $filters['members'] ) ] ),
					'IPS\Member'
				)
			);
		}
		else
		{
			$selected = null;
		}

		$return['members'] = new FormMember( 'achievement_filter_FollowMember_member', $selected, false, [ 'multiple' => null ], null, Member::loggedIn()->language()->addToStack( 'achievement_filter_FollowMember_member_prefix' ), null, 'achievement_filter_FollowMember_member' );

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

		if ( isset( $values['achievement_filter_FollowMember_nth'] ) )
		{
			$return['milestone'] = $values['achievement_filter_FollowMember_nth'];
		}

		if( isset( $values['achievement_filter_FollowMember_member'] ) )
		{
			$return['members'] = array_keys( $values['achievement_filter_FollowMember_member'] );
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
			$where[] = [ 'follow_app=? and follow_area=? and follow_rel_id=?', 'core', 'member', $subject->member_id ];

			$count = Db::i()->select( 'COUNT(*)', 'core_follow', $where )->first();

			if ( $count < $filters['milestone'] )
			{
				return FALSE;
			}
		}

		if( isset( $filters['members'] ) )
		{
			if( !in_array( $subject->member_id, $filters['members'] ) )
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
			'subject'	=> 'achievement_filter_FollowMember_receiver',
			'other'		=> 'achievement_filter_FollowMember_giver'
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
		return [ $extra['giver'] ];
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
		/* Get all followed members first, because we need to take milestones into account */
		$members = iterator_to_array(
			Db::i()->select( 'follow_rel_id', 'core_follow', [ 'follow_app=? and follow_area=? and follow_member_id=?', 'core', 'member', $member->member_id ] )
		);

		if( !count( $members ) )
		{
			return false;
		}

		if( !empty( $filters['members'] ) )
		{
			$match = array_intersect( $filters['members'], $members );
			if( !count( $match ) )
			{
				return false;
			}
		}

		if( !empty( $filters['milestone'] ) )
		{
			return count( $members ) >= $filters['milestone'];
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
		return $subject->member_id . ':' . $extra['giver']->member_id;
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
		$exploded = explode( ':', $identifier );

		$reactionName = Member::loggedIn()->language()->addToStack('unknown');
		$receivedLink = Member::loggedIn()->language()->addToStack('modcp_deleted');
		$giverLink = Member::loggedIn()->language()->addToStack('modcp_deleted');
		try
		{
			$giver = Member::load( $exploded[0] );
			$received = Member::load( $exploded[1] );
			$receivedLink = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $received->url(), TRUE, $received->name, FALSE );
			$giverLink = Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $giver->url(), TRUE, $giver->name, FALSE );
		}
		catch ( OutOfRangeException $e ) {  }

		if ( in_array( 'subject', $actor ) )
		{
			return Member::loggedIn()->language()->addToStack( 'AchievementAction__FollowMember_log_subject', FALSE, [ 'htmlsprintf' => [ $receivedLink ] ] );
		}
		else
		{

			return Member::loggedIn()->language()->addToStack( 'AchievementAction__FollowMember_log_other', FALSE, [ 'htmlsprintf' => [ $giverLink ] ] );
		}
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
				'sprintf' => Member::loggedIn()->language()->addToStack('AchievementAction__FollowMember_title_generic')
			] );
		}

		if( isset( $rule->filters['members'] ) )
		{
			$memberNames = iterator_to_array( Db::i()->select( 'name', 'core_members', Db::i()->in( 'member_id', $rule->filters['members'] ), 'name' ) );
			$conditions[] = Member::loggedIn()->language()->addToStack( 'achievements_title_filter_members', false, [
				'htmlsprintf' => [
					Theme::i()->getTemplate( 'achievements', 'core' )->ruleDescriptionBadge( 'clubs', Member::loggedIn()->language()->addToStack( 'achievement_filter_FollowMember_member_title_pl', false, [ 'pluralize' => [ count( $memberNames ) ] ] ), $memberNames )
				]
			] );
		}

		if( $questCondition = $this->_questFilterDescription( $rule ) )
		{
			$conditions[] = $questCondition;
		}

		return Theme::i()->getTemplate( 'achievements', 'core' )->ruleDescription(
			Member::loggedIn()->language()->addToStack( 'AchievementAction__FollowMember_title' ),
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
			'table' => 'core_follow',
			'pkey'  => 'follow_id',
			'date'  => 'follow_added',
			'where' => [ ['follow_app=? and follow_area=?', 'core', 'member' ] ],
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
		$receiver = Member::load( $row['follow_rel_id'] );
		$receiver->achievementAction( 'core', 'FollowMember', [
			'giver' => Member::load( $row['follow_member_id'] )
		] );
	}
}
