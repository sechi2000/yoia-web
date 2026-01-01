<?php

/**
 * @brief        ClubType
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        01/09/2024
 */

namespace IPS\Events\ListenerType;

use IPS\Events\ListenerType;
use IPS\Member\Club;

/**
 * @method onCreate( Club $club ) : void
 * @method onEdit( ?Club $club ) : void
 * @method onDelete( Club $club ) : void
 */
class ClubListenerType extends ListenerType
{

	/**
	 * Defines the classes that are supported by each Listener Type
	 * When a new Listener Type is created, we must specify which
	 * classes are valid (e.g. \IPS\Content, \IPS\Member).
	 *
	 * @var array
	 */
	protected static array $supportedBaseClasses = array(
	Club::class
	);
}
