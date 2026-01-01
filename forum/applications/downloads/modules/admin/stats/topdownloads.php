<?php
/**
 * @brief		Top Downloads
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		19 Apr 2021
 */

namespace IPS\downloads\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\downloads\Field;
use IPS\downloads\File;
use IPS\Helpers\Table\Db as Table;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Session;
use IPS\Theme;
use function count;
use function defined;
use function file_get_contents;
use const IPS\Helpers\Table\SEARCH_BOOL;
use const IPS\Helpers\Table\SEARCH_CONTAINS_TEXT;
use const IPS\Helpers\Table\SEARCH_DATE_RANGE;
use const IPS\Helpers\Table\SEARCH_MEMBER;
use const IPS\Helpers\Table\SEARCH_NUMERIC;
use const IPS\Helpers\Table\SEARCH_SELECT;
use const IPS\TEMP_DIRECTORY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * topdownloads
 */
class topdownloads extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * @brief	Allow MySQL RW separation for efficiency
	 */
	public static bool $allowRWSeparation = TRUE;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'topdownloads_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$table = new Table( 'downloads_files', Url::internal( 'app=downloads&module=stats&controller=topdownloads' ) );
		$table->langPrefix = 'downloads_';
		$customFields = Field::roots();

		/* Joins */
		$joinSelect = array();
		foreach( $customFields as $field )
		{
			$joinSelect[] = 'downloads_ccontent.field_' . $field->id;
		}

		/* Columns */
		if( count( $customFields ) )
		{
			$table->joins = array(
				array( 'select' => implode(',', $joinSelect), 'from' => 'downloads_ccontent', 'where' => "downloads_ccontent.file_id=downloads_files.file_id" )
			);
		}

		$table->include = array( 'file_name', 'file_downloads' );
		$table->advancedSearch = array(
			'file_downloads' => SEARCH_NUMERIC
		);

		/* Add our custom fields */
		foreach ( $customFields as $field )
		{
			$customField = 'field_' . $field->id;
			switch ( $field->type )
			{
				case 'Checkbox':
				case 'YesNo':
					$table->advancedSearch[ $customField ] = SEARCH_BOOL;
					break;

				case 'Upload':
				case 'Color':
				case 'Password':
					/* These fields make no sense to be searchable */
					break;

				case 'CheckboxSet':
				case 'Select':
				case 'Radio':
					$options = array();
					foreach ( json_decode( $field->content ) as $option )
					{
						$options[ $option ] = $option;
					}
					$table->advancedSearch[ $customField ] = array( SEARCH_SELECT, array( 'options' => array_merge(["" => "Any"], $options ), 'multiple' => $field->multiple ) );
					break;

				case 'Date':
					$table->advancedSearch[ $customField ] = SEARCH_DATE_RANGE;
					break;

				case 'Member':
					$table->advancedSearch[ $customField ] = array( SEARCH_MEMBER, array( 'multiple' => $field->multiple ) );
					break;

				case 'Number':
				case 'Rating':
					$table->advancedSearch[ $customField ] = SEARCH_NUMERIC;
					break;

				default:
					$table->advancedSearch[ $customField ] = SEARCH_CONTAINS_TEXT;
					break;
			}
		}

		$table->mainColumn = 'file_name';
		$table->quickSearch = 'file_name';

		$table->sortBy = $table->sortBy ?: 'file_downloads';
		$table->sortDirection = $table->sortDirection ?: 'desc';

		/* Parsers */
		$table->parsers = array(
			'file_name' => function( $val, $row ) {
				$file = File::constructFromData( $row );
				return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $file->url(), TRUE, $file->name );
			}
		);

		Output::i()->sidebar['actions']['download'] = [
			'icon' => 'download',
			'title' => 'download',
			'link' => Url::internal( "app=downloads&module=stats&controller=topdownloads&do=download" )->csrf()
		];

		/* Display */
		Output::i()->output = (string) $table;
		Output::i()->title  = Member::loggedIn()->language()->addToStack('menu__downloads_stats_topdownloads');
	}

	/**
	 * @return void
	 */
	protected function download() : void
	{
		Session::i()->csrfCheck();

		$headers = [ 'file_name' => 'File', 'file_downloads' => 'Downloads' ];

		$customFields = Field::roots();
		$customFieldNames = array();
		foreach ( $customFields as $field )
		{
			$headers['field_' . $field->id] = Member::loggedIn()->language()->get( 'downloads_field_' . $field->id );
		}

		$tmpFile = tempnam( TEMP_DIRECTORY, 'IPS' );
		$fh = fopen( $tmpFile, 'w' );

		fputcsv($fh, $headers);

		$files = Db::i()->select('*', 'downloads_files', null, 'file_downloads desc' )->join('downloads_ccontent', 'downloads_ccontent.file_id=downloads_files.file_id');
		foreach ( $files as $file )
		{
			$row = [];
			foreach ( $headers as $key => $header )
			{
				if( isset( $file[ $key ] ) )
				{
					$row[] = $file[ $key ];
				}
				else
				{
					$row[] = '';
				}

			}

			fputcsv($fh, $row);
		}

		fclose( $fh );
		$csv = file_get_contents( $tmpFile );
		$name = 'topdownloads_' . date('Y-m-d');

		Member::loggedIn()->language()->parseOutputForDisplay( $name );
		Member::loggedIn()->language()->parseOutputForDisplay( $csv );
		Output::i()->sendOutput( $csv, 200, 'text/csv', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', "{$name}.csv" ) ), FALSE, FALSE, FALSE );
	}
}