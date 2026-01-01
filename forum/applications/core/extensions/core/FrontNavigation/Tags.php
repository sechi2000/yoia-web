<?php
/**
 * @brief		Front Navigation Extension: Tags
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		06 Jun 2024
 */

namespace IPS\core\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Tag;
use IPS\core\FrontNavigation\FrontNavigationAbstract;
use IPS\Db;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\Member;
use IPS\Request;
use IPS\Settings;
use OutOfRangeException;
use function array_merge;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Tags
 */
class Tags extends FrontNavigationAbstract
{
	/**
	 * @var string Default icon
	 */
	public string $defaultIcon = '\f02c';

	/**
	 * The tag
	 */
	protected ?Tag $tag = null;

	/**
	 * Constructor
	 *
	 * @param	array	$configuration	The configuration
	 * @param	int		$id				The ID number
	 * @param	string|null	$permissions	The permissions (* or comma-delimited list of groups)
	 * @param	string	$menuTypes		The menu types (either * or json string)
	 * @param	array|null	$icon			Array of icon data or null
	 * @return	void
	 */
	public function __construct( array $configuration, int $id, string|null $permissions, string $menuTypes, array|null $icon )
	{
		parent::__construct( $configuration, $id, $permissions, $menuTypes, $icon );

		if ( count( $configuration ) and ! empty( $configuration['id'] ) )
		{
			try
			{
				$this->tag = Tag::load( $configuration['id'] );
			}
			catch( OutOfRangeException )
			{

			}
		}
	}

	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle(): string
	{
		return Member::loggedIn()->language()->addToStack('frontnavigation_core_tag');
	}

	/**
	 * Allow multiple instances?
	 *
	 * @return	bool
	 */
	public static function allowMultiple() : bool
	{
		return true;
	}

	/**
	 * Can the currently logged in user access the content this item links to?
	 *
	 * @return	bool
	 */
	public function canAccessContent(): bool
	{
		if( $this->tag === null )
		{
			return false;
		}

		if ( ! $this->tag->canView() )
		{
			return false;
		}

		return true;
	}

	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function title(): string
	{
		return (string) $this->tag?->text;
	}

	/**
	 * Get Link
	 *
	 * @return    string|Url|null
	 */
	public function link(): Url|string|null
	{
		return $this->tag?->url();
	}

	/**
	 * Is Active?
	 *
	 * @return	bool
	 */
	public function active(): bool
	{
		return stristr( (string) Request::i()->url(), (string) $this->tag->url() );
	}

	/**
	 * Get configuration fields
	 *
	 * @param	array	$existingConfiguration	The existing configuration, if editing an existing item
	 * @param	int|null		$id						The ID number of the existing item, if editing
	 * @return	array
	 */
	public static function configuration( array $existingConfiguration, ?int $id = NULL ): array
	{
		$existingValue = null;
		/* We have an ID, but the auto complete needs text */
		if ( ! empty( $existingConfiguration['id'] ) )
		{
			try
			{
				$tag = Tag::load( $existingConfiguration['id'] );
				$existingValue = $tag->text;
			}
			catch ( \OutOfRangeException )
			{
				/* Can't find it */
			}
		}

		$options = array(
			'autocomplete' => array(
				'unique' => true,
				'source' => Tag::getStore(),
				'freeChoice' => false,
				'minimized' => true,
				'maxItems' => 1,
				'alphabetical' => true,
				'disallowedCharacters' => array( '#' ), // @todo Pending \IPS\Http\Url rework, hashes cannot be used in URLs
			) );

		return [
			new Text( 'tag', $existingValue, false, $options )
		];
	}

	/**
	 * Parse configuration fields
	 *
	 * @param	array	$configuration	The values received from the form
	 * @param	int		$id				The ID number of the existing item, if editing
	 * @return	array
	 */
	public static function parseConfiguration( array $configuration, int $id ): array
	{
		/* We get the name from autocomplete, but we need the ID, so look that up */
		try
		{
			$tag = Tag::load( $configuration['tag'], 'tag_text' );

			return [
				'id' => $tag->id
			];
		}
		catch( \OutOfRangeException $e )
		{
			/* No idea */
			return [
				'id' => null
			];
		}
	}
}