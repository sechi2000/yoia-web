<?php
/**
 * @brief		Achievements Rule Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		24 Feb 2021
 */

namespace IPS\core\Achievements\Actions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\cloud\Quest;
use IPS\core\Achievements\Rule;
use IPS\Helpers\Form\Node;
use IPS\Http\Url;
use IPS\Member;
use IPS\Platform\Bridge;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function get_called_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Base class for AchievementAction extensions
 */
abstract class AchievementActionAbstract
{
	/**
	 * Can use this rule?
	 *
	 * @return boolean
	 */
	public function canUse(): bool
	{
		$exploded = explode( '\\', get_called_class() );

		return Application::appIsEnabled( $exploded[1] );
	}

	/**
	 * Determines whether this should be displayed in the ACP list.
	 * We need to override this for some rules that should remain hidden
	 *
	 * @return bool
	 */
	public function showInAcp() : bool
	{
		return true;
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
		$return = [];

		if( Bridge::i()->featureIsEnabled( 'quests' ) )
		{
			$classKey = explode( '\\', get_called_class() )[5];
			$questField = new Node( 'achievement_filter_quest_' . $classKey, $filters['quest'] ?? null, false, [
				'class' => Quest::class,
				'subnodes' => false,
				'multiple' => false,
				'showAllNodes' => true
			], null, Member::loggedIn()->language()->addToStack( 'achievement_filter_quest_prefix' ), null, 'achievement_filter_quest_' . $classKey );
			$questField->label = Member::loggedIn()->language()->addToStack( 'achievement_filter_quest' );
			$return['quest'] = $questField;
		}

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
		if( Bridge::i()->featureIsEnabled( 'quests' ) )
		{
			$classKey = explode( '\\', get_called_class() )[5];
			if( isset( $values['achievement_filter_quest_' . $classKey ] ) and $values['achievement_filter_quest_' . $classKey ] instanceof Quest )
			{
				return [
					'quest' => $values['achievement_filter_quest_' . $classKey ]->_id
				];
			}
		}

		return [];
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
		return TRUE;
	}

	/**
	 * Get "description" for rule (usually a description of the rule's filters)
	 *
	 * @param	Rule	$rule	The rule
	 * @return	string|NULL
	 */
	protected function _questFilterDescription( Rule $rule ): ?string
	{
		if ( isset( $rule->filters['quest'] ) and Bridge::i()->featureIsEnabled( 'quests' ) )
		{
			try
			{
				$quest = Quest::load( $rule->filters['quest'] );
				return Member::loggedIn()->language()->addToStack( 'achievements_title_filter_quest', false, [
					'htmlsprintf' => [
						Theme::i()->getTemplate( 'achievements', 'core' )->ruleDescriptionBadge( 'quest', $quest->_title )
					]
				]);
			}
			catch( OutOfRangeException ){}
		}

		return NULL;
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
			'subject'	=> '',
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
		return [];
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
		/* Default to false, so that any other rules would have to be completed again. */
		return false;
	}
	
	/**
	 * Get identifier to prevent the member being awarded points for the same action twice
	 * Must be unique within within of this domain, must not exceed 32 chars.
	 *
	 * @param	Member	$subject	The subject member
	 * @param	mixed		$extra		Any additional information about what is happening (e.g. if a post is being made: the post object)
	 * @return	string
	 */
	abstract public function identifier( Member $subject, mixed $extra = NULL ): string;
	
	/**
	 * Return a description for this action to show in the log
	 *
	 * @param	string	$identifier	The identifier as returned by identifier()
	 * @param	array	$actor		If the member was the "subject", "other", or both
	 * @return	string
	 */
	abstract public function logRow( string $identifier, array $actor ): string;
		
	/**
	 * Get "description" for rule
	 *
	 * @param	Rule	$rule	The rule
	 * @return	string|null
	 */
	abstract public function ruleDescription( Rule $rule ): ?string;

	/**
	 * Process the rebuild row
	 *
	 * @param array		$row	Row from database
	 * @param array		$data	Data collected when starting rebuild [table, pkey...]
	 * @return void
	 */
	abstract public static function rebuildRow( array $row, array $data ) : void;

	/**
	 * Get rebuild data
	 *
	 * @return	array
	 */
	abstract public static function rebuildData(): array;
}