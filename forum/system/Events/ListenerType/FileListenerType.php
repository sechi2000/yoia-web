<?php

/**
 * @brief        FileListenerType
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
use IPS\File as SystemFile;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @method onDownload( SystemFile $file, object $content, array $extra ) : void
 */
class FileListenerType extends ListenerType
{
	/**
	 * Defines the classes that are supported by each Listener Type
	 * When a new Listener Type is created, we must specify which
	 * classes are valid (e.g. \IPS\Content, \IPS\Member).
	 *
	 * @var array
	 */
	protected static array $supportedBaseClasses = array(
		SystemFile::class
	);
}