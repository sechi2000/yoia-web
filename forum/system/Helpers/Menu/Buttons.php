<?php

/**
 * @brief        Buttons
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        3/28/2025
 */

namespace IPS\Helpers\Menu;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Menu;
use IPS\Http\Url;
use IPS\Theme;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Buttons extends Menu
{
	/**
	 * @param string $css
	 */
	public function __construct( string $css = 'ipsButtons' )
	{
		parent::__construct( '', null, $css );
	}

	/**
	 * Adds a seperator element to the stack
	 *
	 * @return $this
	 */
	public function addSeparator(): self
	{
		/* Separators are not supported in buttons list */
		return $this;
	}

	/**
	 * Adds a title field to the stack
	 *
	 * @param string $title
	 * @return $this
	 */
	public function addTitleField( string $title ): self
	{
		/* Title fields are not supported in buttons list */
		return $this;
	}

	/**
	 * Static method to build a button.
	 * Primarily used for 3rd party extensions.
	 *
	 * @param Url|string|null $url
	 * @param string $languageString
	 * @param string|null $css
	 * @param array $dataAttributes
	 * @param bool $opensDialog
	 * @param string|null $icon
	 * @param bool|string $tooltip
	 * @return string|Link
	 */
	public static function button( Url|string|null $url, string $languageString, ?string $css = null, array $dataAttributes = [], bool $opensDialog = FALSE, ?string $icon = NULL, bool|string $tooltip=false ) : string|Link
	{
		/* Handle the tooltip */
		if( $tooltip !== false )
		{
			$dataAttributes['title'] = is_string( $tooltip ) ? $tooltip : $languageString;
			$dataAttributes['data-ipsTooltip'] = '';
		}

		/* If the URL is null, then we're just showing a <span> instead of an actual link */
		if( $url === null )
		{
			return Theme::i()->getTemplate( 'menu', 'core', 'front' )->buttonsItem( $css ?? 'ipsButton ipsButton--text', $languageString, $icon ?? '', $dataAttributes );
		}
		else
		{
			return new Link( $url, $languageString, $css ?? 'ipsButton ipsButton--inherit', $dataAttributes, $opensDialog, $icon );
		}
	}

	/**
	 * Build a button
	 *
	 * @param Url|string|null $url
	 * @param string $languageString
	 * @param string|null $css
	 * @param array $dataAttributes
	 * @param bool $opensDialog
	 * @param string|null $icon
	 * @param bool|string $tooltip
	 * @return $this
	 */
	public function addButton( Url|string|null $url, string $languageString, ?string $css = null, array $dataAttributes = [], bool $opensDialog = FALSE, ?string $icon = NULL, bool|string $tooltip=false ) : self
	{
		$this->elements[] = static::button( $url, $languageString, $css, $dataAttributes, $opensDialog, $icon, $tooltip );
		return $this;
	}

	/**
	 * Returns a row wrapper which can contain custom HTML code
	 * @param string $content
	 * @return string
	 */
	public function rowWrapper( string $content ): string
	{
		return '';
	}

	/**
	 * Returns the link
	 *
	 * @return string
	 */
	public function linkHtml(): string
	{
		return $this->contentHtml();
	}

	/**
	 * Returns the parsed menu content
	 *
	 * @return string
	 */
	public function contentHtml(): string
	{
		if( $this->hasContent() )
		{
			return Theme::i()->getTemplate( 'menu', 'core', 'front' )->buttonsContent( $this );
		}

		return '';
	}

	/**
	 * Returns the parsed menu
	 * @return string
	 */
	public function __toString(): string
	{
		if( !$this->hasContent() )
		{
			return '';
		}

		return Theme::i()->getTemplate( 'menu', 'core', 'front' )->buttonsList( $this );
	}
}