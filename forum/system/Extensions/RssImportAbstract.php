<?php

/**
 * @brief        RssImportAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/21/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\core\Rss\Import;
use IPS\Helpers\Form;
use IPS\Node\Model;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class RssImportAbstract
{
	/**
	 * @brief	RSSImport Classes
	 */
	public array $classes = array();

	/**
	 * @brief	FileStorage extension to use
	 */
	public string $fileStorage = '';

	/**
	 * Show in the Admin CP?
	 *
	 * @return boolean
	 */
	public function showInAdminCp(): bool
	{
		return true;
	}

	/**
	 * Return available options for a Form\Select
	 *
	 * @return array
	 */
	public function availableOptions(): array
	{
		return array();
	}

	/**
	 * Node selector options
	 *
	 * @param 	Import|null	$rss	Existing RSS object if editing|null if not
	 * @return array
	 */
	abstract public function nodeSelectorOptions( ?Import $rss ): array;

	/**
	 * Display Form
	 *
	 * @param Form $form The form
	 * @param Import|null $rss
	 * @return    void
	 */
	abstract public function form( Form $form, ?Import $rss=null ) : void;

	/**
	 * Save
	 *
	 * @param array $values Values from form
	 * @param Import $rss
	 * @return    array
	 */
	abstract public function saveForm( array &$values, Import $rss ) : array;

	/**
	 * @param Import 	$rss 		RSS object
	 * @param array 				$article 	RSS feed article importing
	 * @param Model 		$container  Container object
	 * @param	string				$content	Post content with read more link if set
	 * @return Content|null
	 */
	abstract public function create( Import $rss, array $article, Model $container, string $content ): ?Content;
}