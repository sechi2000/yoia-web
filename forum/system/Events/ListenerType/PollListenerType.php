<?php

/**
 * @brief        PollListenerType
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        4/3/2025
 */

namespace IPS\Events\ListenerType;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Events\ListenerType;
use IPS\Poll as PollClass;
use IPS\Poll\Vote as VoteClass;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @method onCreateOrEdit( PollClass $poll ) : void
 * @method onDelete( PollClass $poll ) : void
 * @method onVote( PollClass $poll, VoteClass $vote ) : void
 * @method onVoteRecount( PollClass $poll ) : void
 * @method onStateChange( PollClass $poll, string $state ) : void
 */
class PollListenerType extends ListenerType
{
	/**
	 * Defines the classes that are supported by each Listener Type
	 * When a new Listener Type is created, we must specify which
	 * classes are valid (e.g. \IPS\Content, \IPS\Member).
	 *
	 * @var array
	 */
	protected static array $supportedBaseClasses = array(
		PollClass::class
	);
}