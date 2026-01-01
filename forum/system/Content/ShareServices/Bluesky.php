<?php

/**
 * @brief        Bluesky share link
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @since        19 November 2024
 */

namespace IPS\Content\ShareServices;

use IPS\Content\ShareServices;
use IPS\Theme;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Bluesky share link
 */
class Bluesky extends ShareServices
{
	/**
	 * Return the HTML code to show the share link
	 *
	 * @return    string
	 */
	public function __toString(): string
	{
		return Theme::i()->getTemplate( 'sharelinks', 'core' )->bluesky( urlencode( $this->url ), $this->title ? urlencode( $this->title ) : NULL );
	}
}
