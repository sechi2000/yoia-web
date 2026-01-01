<?php
/**
 * @brief		mycharts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		09 Dec 2022
 */

namespace IPS\core\modules\admin\overview;

use DateInterval;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\core\Statistics\Chart;
use IPS\DateTime;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Chart\Dynamic;
use IPS\Helpers\Form;
use IPS\Helpers\Form\DateRange;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use Throwable;
use function array_fill;
use function array_key_exists;
use function array_merge;
use function array_values;
use function count;
use function defined;
use function explode;
use function fclose;
use function fopen;
use function fputcsv;
use function in_array;
use function is_array;
use function is_callable;
use function is_iterable;
use function is_numeric;
use function is_string;
use function iterator_to_array;
use function ksort;
use function max;
use function str_replace;
use function tempnam;
use function time;
use function trim;
use const IPS\TEMP_DIRECTORY;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * mycharts
 */
class mycharts extends Controller
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
	 * @var "charts"|"blocks"
	 */
	protected string $activeTab = "charts";

	/**
	 * The report id for this request
	 * @var int|null
	 */
	protected int|null $reportId = null;

	protected bool $communityhealth = false;

	/**
	 * @var string[]
	 */
	protected array $tabs = [
		"charts" => "statsreports_saved_charts",
		"blocks" => "statsreports_saved_blocks",
	];


	/**
	 * Get the report for this request
	 *
	 * @return array|null
	 */
	protected function _getReport()
	{
		static $report = false;
		if ( $this->reportId and $report === false )
		{
			try
			{
				$report = Db::i()->select( "*", "core_reports", [['id=?', $this->reportId]] )->first();
			}
			catch ( \UnderflowException $e )
			{
				$report = null;
			}
		}
		else if ( $report === false )
		{
			$report = null;
		}
		return $report;
	}

	protected ?array $chartRow= null;

	/**
	 * Community health shortcut from the menu system
	 */
	protected function communityhealth() : void
	{
		if ( !Bridge::i()->featureIsEnabled( 'community_health_stats' ) )
		{
			Output::i()->redirect( Url::internal( "app=core&module=overview&controller=mycharts" ) );
		}

		Request::i()->communityhealth = '1';
		$this->communityhealth = true;
		$this->manage();
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function execute() : void
	{
		if ( !Request::i()->communityhealth and Request::i()->chartId and Bridge::i()->featureIsEnabled( 'community_health_stats' ) )
		{
			$allCharts = Bridge::i()->getCommunityHealthCharts();

			foreach( $allCharts as $data )
			{
				if ( $data['chart_id'] == Request::i()->chartId )
				{
					$this->chartRow = $data;
					break;
				}
			}

			if ( $this->chartRow )
			{
				Request::i()->communityhealth = '1';
			}
		}

		if ( Request::i()->communityhealth )
		{
			if ( !Bridge::i()->featureIsEnabled( "community_health_stats" ) )
			{
				Output::i()->redirect( Url::internal( "app=core&module=overview&controller=mycharts" ) );
			}

			$this->communityhealth = true;
			$this->activeTab = "charts";
			$this->reportId = null;
		}
		else
		{
			$this->activeTab = isset( $this->tabs[ Request::i()->tab ] ) ? Request::i()->tab : "charts";
			$this->reportId = is_numeric( Request::i()->report_id ) ? (int)Request::i()->report_id : 0;
			$this->reportId = $this->reportId > 0 ? $this->reportId : null;

			if ( $this->reportId and !$this->_getReport() )
			{
				Output::i()->redirect( Url::internal( (string)Request::i()->url() )->setQueryString( [ "report_id" => "0" ] ) );
			}
		}

		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$report = $this->_getReport();
		Output::i()->title = $report ? $report['report_title'] : Member::loggedIn()->loggedIn()->language()->addToStack( $this->communityhealth ? "communityhealth" : "menu__core_overview_mycharts" );

		// Have to generate the form regardless of ajax so the values can be saved
		$form = Chart::getBlockDateRangeForm( $report ? $report['id'] : null );
		if ( !Request::i()->isAjax() )
		{
			Output::i()->output = $form;
		}

		if ( $this->activeTab === "charts" )
		{
			$charts = [];
			foreach( Chart::getChartsForMember( Url::internal( "app=core&module=overview&controller=mycharts" . ( $this->communityhealth ? '&communityhealth=1' : '' ) ), TRUE ) AS $id )
			{
				$charts[] = $id;
			}

			if ( !\count( $charts ) )
			{
				$content = Theme::i()->getTemplate( 'stats')->mychartsEmpty();
			}
			else
			{
				$content = Theme::i()->getTemplate( 'stats' )->mycharts( $charts );
			}
		}
		else
		{
			$content = Theme::i()->getTemplate( "reports", "core", "admin" )->savedStatBlocks();
		}

		if ( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $content );
		}

		$this->_addSidebarButtons();
		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_stats.js', 'core' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/statistics.css', 'core', 'admin' ) );
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'system/reports.css', 'core', 'admin' ) );
		$url = Url::internal( "app=core&module=overview&controller=mycharts" );
		if ( $this->communityhealth )
		{
			$url = $url->setQueryString( [ 'do' => 'communityhealth' ] );
		}
		else if ( $this->reportId )
		{
			$url = $url->setQueryString( [ "report_id" => $this->reportId ] );
		}

		if ( $this->communityhealth )
		{
			Output::i()->output .= Theme::i()->getTemplate( "global", "core", "admin" )->tabs( ['charts' => 'statsreports_saved_charts'], 'charts', $content, $url );
			return;
		}
		Output::i()->output .= Theme::i()->getTemplate( "global", "core", "admin" )->tabs( $this->tabs, $this->activeTab, $content, $url );
	}
	
	/**
	 * Get chart
	 *
	 * @return	void
	 */
	public function getChart() : void
	{
		if ( !isset( Request::i()->chartId ) )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->output = '';
			}
			else
			{
				Output::i()->redirect( Url::internal( "app=core&module=overview&controller=mycharts" ) );
			}
		}
		
		try
		{
			/* We first get the date range form to load the chart if it exists */
			$report = $this->_getReport();
			Chart::getBlockDateRangeForm( $report ? $report['id'] : null );
			$chart = Chart::constructMemberChartFromData( $this->chartRow ?: Request::i()->chartId, Url::internal( "app=core&module=overview&controller=mycharts&do=getChart&chartId=" . Request::i()->chartId ) );

			$chart->options['height'] = '300px'; // This chart is displayed in a grid with other charts, so we want them all to have the same height
			Output::i()->output = (string) $chart;
		}
		catch( Throwable )
		{
			if ( Request::i()->isAjax() )
			{
				Output::i()->output = '';
			}
			else
			{
				Output::i()->redirect( Url::internal( "app=core&module=overview&controller=mycharts" ) );
			}
		}
	}

	/**
	 * Add the export and report selector sidebar buttons
	 *
	 * @return void
	 */
	protected function _addSidebarButtons()
	{
		if ( $this->_getReport() or Bridge::i()->featureIsEnabled( 'analytics_full' ) or Db::i()->select( "count(*) as cnt", 'core_reports' )->first() )
		{
			Output::i()->sidebar['actions']['select_report'] = array(
				'primary' => false,
				'icon'    => 'area-chart',
				'title'   => 'statsreports_select_report',
				'id'	  => 'elStatsSelectReport',
				'menu'=> $this->getReportSelector(),
                'link' => '#'
			);
		}

		Output::i()->sidebar['actions']['download'] = array(
			'primary'	=> true,
			'icon'		=> 'download',
			'title'		=> 'statsreports_download',
			'link'		=> Url::internal( 'app=core&module=overview&controller=mycharts&do=download' )->setQueryString( [ "report_id" => $this->reportId ?: 0, "tab" => $this->activeTab ]),
			'data'		=> array( 'ipstooltip' => '', 'ipstooltip-label' => Member::loggedIn()->language()->addToStack( 'statsreports_download_desc'), 'controller' => 'core.admin.stats.downloadButton' )
		);
	}

	/**
	 * Get the dropdown to pick a report
	 *
	 * @return array
	 */
	protected function getReportSelector() : array
	{
        $return = [
            'main' => [
                'icon' => 'earth-americas',
                'title' => 'statsreports_report_maindash',
                'link' => Url::internal( "app=core&moduel=overview&controller=mycharts&tab=" . $this->activeTab )
            ]
        ];

        if( Bridge::i()->featureIsEnabled( 'community_health_stats' ) )
        {
            $return[ 'health' ] = [
                'icon' => 'heart-pulse',
                'title' => 'communityhealth',
                'link' => Url::internal( "app=core&module=overview&controller=mycharts&communityhealth=1" )
            ];
        }

        foreach( iterator_to_array( Db::i()->select( '*', 'core_reports', order: "report_title ASC" ) ) as $row )
        {
            $return[ 'report_' . $row['id'] ] = [
                'icon' => 'bookmark',
                'link' => Url::internal( "app=core&module=overview&controller=mycharts&report_id={$row['id']}&tab={$this->activeTab}" ),
                'title' => $row['report_title']
            ];
        }

        return $return;
	}

	/**
	 * @return void
	 */
	protected function saveBlock()
	{
		$block = Request::i()->block;
		$app = Request::i()->blockapp;
		$subblock = Request::i()->subblock;
		if ( !$block || !$app || !$subblock )
		{
			Output::i()->json( "No block, sub-block or app specified in the request", 400 );
		}

		$savedBlock = null;
		if ( $savedId = Request::i()->saved_block_id )
		{
			try
			{
				$savedBlock = Db::i()->select( '*', 'core_saved_overview_stats', [ 'id=?', $savedId] )->first();
			}
			catch ( \UnderflowException )
			{
				Output::i()->json( "Cannot find block with id {$savedId}", 404 );
			}
		}

		$defaultTitle = null;
		if ( isset( $savedBlock['stat_title'] ) )
		{
			$defaultTitle = $savedBlock['stat_title'];
		}
		else
		{
			try
			{
				$application = Application::load( $app );
				$extension = null;
				foreach ( $application->extensions( 'core', 'OverviewStatistics', true ) as $key => $_extension )
				{
					if ( $key === $block )
					{
						$extension = $_extension;
						break;
					}
				}

				if ( !$extension )
				{
					throw new OutOfRangeException( "The OverviewStatistics plugin {$block} does not exist for the app {$app}" );
				}

				if ( !in_array( $subblock, $extension->getBlocks() ) )
				{
					throw new OutOfRangeException( "Block {$subblock} not defined for this overview stats plugin" );
				}

				if ( ( $details = $extension->getBlockDetails( $subblock ) ) and isset( $details['title'] ) )
				{
					try
					{
						$defaultTitle = Member::loggedIn()->language()->addToStack( $details['title'] );
						Member::loggedIn()->language()->parseOutputForDisplay( $defaultTitle );
					}
					catch ( Exception ){}
				}
			}
			catch ( Exception )
			{
				Output::i()->json( "The block or subblock could not be found", 404 );
			}
		}

		$form = new Form();
		$form->class = 'ipsForm--vertical';
		$form->add(new Text( "statsreports_saved_block_title", $defaultTitle, true ));
		if ( !is_array( $savedBlock ) )
		{
			$reports = iterator_to_array( Db::i()->select( "*", "core_reports" ) );
			$reportOptions = [];
			foreach( $reports as $row )
			{
				$reportOptions["report_" . $row['id']] = $row['report_title'];
			}
			$form->add( new YesNo( "statsreports_show_all_members", true, false ) );
			$form->add( new YesNo( "statsreports_include_in_report", false, false, [ "togglesOn" => [ "statsreports_report_select", empty($reportOptions) ? "statsreports_report_new_title" : "" ] ] ) );
			// if there are saved reports, allow user to select one of them
			if ( !empty( $reportOptions ) )
			{
				// todo an autocomplete field is probably a better UX when there could be dozens of reports saved
				$form->add( new Select( "statsreports_report", @$reports[0] ? 'report_'.$reports[0]['id'] : false, false, [
					"unlimited"        => false,
					"unlimitedLang"    => "statsreports_report_new",
					"unlimitedToggles" => [
						'statsreports_report_new_title'
					],
					"options"          => $reportOptions,
				], id: 'statsreports_report_select' ) );
			}

			$form->add( new Text( "statsreports_report_new_title", null, false, customValidationCode: (function( $value ) use ( $reportOptions ) {
				if ( ( empty( $reportOptions ) OR ( isset( Request::i()->statsreports_report_unlimited ) ) ) and !empty( (int) Request::i()->statsreports_include_in_report_checkbox ) and ( !$value OR strlen( $value ) < 5 ) )
				{
					throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack( "statsreports_no_title_error" ) );
				}
				else if ( !empty( $reportOptions ) and in_array( $value, array_values( $reportOptions ) ) )
				{
					throw new InvalidArgumentException( Member::loggedIn()->language()->addToStack( 'statsreports_title_in_use_error' ) );
				}
			}), id: "statsreports_report_new_title" ) );
		}


		if ( $values = $form->values() )
		{
			// first, is there a report this block is being saved to?
			$reportID = null;
			if ( @$values['statsreports_include_in_report'] )
			{
				if ( isset( $values['statsreports_report'] ) and $values['statsreports_report'] )
				{
					$reportID = (int) explode( '_', $values['statsreports_report'], 2 )[1];
				}
				else if ( ( empty( $reportOptions ) and !empty( $values['statsreports_report_new_title'] ) ) or @$values['statsreports_report'] === false )
				{
					$title = $values['statsreports_report_new_title'];
					$reportID = Db::i()->insert( "core_reports", [ "report_title" => $title ] );
				}
			}

			$insert = [
				"stat_app" => $app,
				"stat_extension" => $block,
				"stat_subblock" => $subblock,
				"stat_title" => $values['statsreports_saved_block_title'],
				"stat_report_id" => $reportID,
			];

			if ( is_array( $savedBlock ) )
			{
				$insert['id'] = $savedBlock['id'];
			}
			else
			{
				$insert['stat_member'] = $values['statsreports_show_all_members'] ? null: Member::loggedIn()->member_id;
			}

			Db::i()->insert( "core_saved_overview_stats", $insert, true );

			if ( Request::i()->isAjax() or Request::i()->frommenu )
			{
				Output::i()->json(         [
					                        "message" => "ok",
					                        "title" => $insert['stat_title']
				                        ], 201 );
			}

			Output::i()->redirect( Url::internal( "app=core&module=overview&controller=mycharts&do=saveBlock" )->setQueryString(
				[ 'block' => Request::i()->block, 'subblock' => Request::i()->subblock, 'blockapp' => Request::i()->blockapp ]
			) );
		}

		Output::i()->output = (string) $form;
	}

	/**
	 * Delete an overview stats block
	 *
	 * @return void
	 * @throws \IPS\Api\Exception
	 */
	protected function deleteBlock()
	{
		Session::i()->csrfCheck();
		if ( !isset( Request::i()->saved_block_id ) or !is_numeric( Request::i()->saved_block_id ) )
		{
			Output::i()->error( "Cannot delete because there is no saved_block_id in the request", 400 );
		}

		$id = (int) Request::i()->saved_block_id;
		Db::i()->delete( "core_saved_overview_stats", [ "id=?", $id] );
		Output::i()->redirect( Url::internal( "app=core&module=overview&controller=mycharts&tab=blocks" ) );
	}

	/**
	 * Download a CSV of this report
	 * @return void
	 */
	protected function download()
	{
		$csvContents = $this->activeTab === 'charts' ? $this->_compileCharts() : $this->_compileBlocks();
		$lang = Member::loggedIn()->language();
		$name = $lang->get( 'menu__core_overview_mycharts' ) . ' - ' . $lang->get( $this->activeTab === 'charts' ? 'statsreports_chart_fname' : 'statsreports_block_fname' );

		// todo use the report's title when that's implemented


		/* Compile the data */
		$file = tempnam( TEMP_DIRECTORY, 'IPS' );
		$fh = fopen( $file, 'w' );
		fputcsv( $fh, $csvContents['headers'] );
		foreach( $csvContents['data'] as $row )
		{
			fputcsv( $fh, $row );
		}

		fclose( $fh );
		$csv = \file_get_contents( $file );
		Member::loggedIn()->language()->parseOutputForDisplay( $csv );
//	    Output::i()->sendOutput( $csv, 200, 'text/plain' ); // for debugging
		Output::i()->sendOutput( $csv, 200, 'text/csv', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', "{$name}.csv" ) ), FALSE, FALSE, FALSE );
	}

	/**
	 * Get all the charts in this report merged into one array
	 *
	 * @return array{headers: string[], data: string[][]}
	 */
	protected function _compileCharts() : array
	{
		$dateRange = $this->_getDateRange();
		$data = [
			"headers" => [ Member::loggedIn()->language()->addToStack( 'date')],
			"data" => []
		];

		Member::loggedIn()->language()->parseOutputForDisplay( $data['headers'][0] );

		$rowsByDate = [];
		$timescale = null;
		$totalColumnCount = 0;
		// the type needs to be set before the charts are initialized
		if ( isset( Request::i()->types ) and is_iterable( Request::i()->types ) )
		{
			foreach( Request::i()->types as $identifier => $type )
			{
				Request::i()->types[ $identifier][ $type] = "AreaChart";
			}
		}

		$url = Url::internal( "app=core&module=overview&controller=mycharts" );
		Dynamic::$mergedChartType = 'AreaChart';
		foreach( Chart::getChartsForMember( $url ) AS $chartData )
		{
			$chart = $chartData['chart'];
			if ( !( $chart instanceof Dynamic ) )
			{
				continue;
			}

			$timescale = $timescale ?: $chart->timescale;

			// Cannot add pie charts
			if ( $chart->type === 'PieChart' or $chart->type === 'GeoChart' )
			{
				continue; // todo this should probably be indicated to the user if the chart cannot be included in the CSV
			}

			$chart->timescale = $timescale;
			$chart->start = $dateRange['start'];
			$chart->end = $dateRange['end'];
			$chart->compileForOutput();
			$rawHeaders = $chart->headers;
			$columnCount = 0;
			foreach ( $rawHeaders as $header )
			{
				$formattedHeader = $chart->title . ' - ' . str_replace( "\n", ' ', $header['label'] );
				Member::loggedIn()->language()->parseOutputForDisplay( $formattedHeader );

				// if the first column is a date, it's the indexable date type
				if ( $columnCount === 0 and in_array( $header['type'], [ 'date', 'datetime', 'timeofday' ] ) )
				{
					continue;
				}

				$columnCount++;
				$data['headers'][] = $formattedHeader;
			}

			if ( $columnCount )
			{
				$insertedDates = [];
				$rawRows = $chart->rows;
				foreach( $rawRows AS $row )
				{
					$i = 0;
					$save = array();
					$date = null;
					foreach( $rawHeaders AS $header )
					{
						switch( $header['type'] )
						{
							/* Booleans convert to string 'true' or 'false' */
							case 'bool':
								$save[] = ( $row[$i] ) ? 'true' : 'false';
								break;

							/* Make dates human readable */
							case 'date':
							case 'datetime':
							case 'timeofday':
								// In theory, this should be the first column in the chart
								if ( $i === 0 )
								{
									$date = "ts_" . ( new DateTime( $row[$i] ) )->getTimestamp(); // using ts_ before the date to prevent the arrays from auto-filling numeric indices
								}
								else
								{
									$save[] = ( new DateTime( $row[ $i ] ) )->fullYearLocaleDate();
								}
								break;

							/* Anything else can just save as-is */
							default:
								if ( \is_array( $row[$i] ) )
								{
									/* GeoCharts use arrays so we need to use the value rather */
									$save[] = $row[$i]['value'];
								}
								else
								{
									$save[] = $row[$i];
								}
								break;
						}
						$i++;
					}

					if ( $date )
					{
						$insertedDates[$date] = $date;
						if ( isset( $rowsByDate[$date] ) )
						{
							$rowsByDate[$date] = array_merge( $rowsByDate[$date], $save );
						}
						else
						{
							$rowsByDate[$date] = array_merge( array_fill( 0, $totalColumnCount, "NULL" ), $save );
						}
					}
				}

				// after iterating through the rows, we need to make sure we add empty values for dates in the output
				foreach( $rowsByDate as $rowDate => $rowValues )
				{
					if ( array_key_exists( $rowDate, $insertedDates ) )
					{
						continue;
					}

					$rowsByDate[$rowDate] = array_merge( $rowValues, array_fill( 0, $columnCount, "NULL" ) );
				}
			}

			$totalColumnCount += $columnCount;
		}

		ksort( $rowsByDate );
		foreach( $rowsByDate as $date => $rowValues )
		{
			// the dates are ts_<unix timestamp as an int>
			$dateString = ( DateTime::ts( (int) \mb_substr( $date, 3 ) ) )->fullYearLocaleDate();
			$data['data'][] = array_merge( array( $dateString ), $rowValues );
		}

		return $data;
	}

	/**
	 * Get the date range based on the request for a report download
	 *
	 * @return array{start: DateTime, end: DateTime}
	 */
	protected function _getDateRange() : array
	{
		if ( isset( Request::i()->range ) and is_numeric( Request::i()->range ) )
		{
			$days = (int) Request::i()->range;
			return [
				"start" => DateTime::create()->setTime( 0, 0, 0 )->sub( new DateInterval( "P{$days}D" ) ),
				"end" => DateTime::create()->setTime( 23, 59, 59 ),
			];
		}

		if ( is_array( Request::i()->range ) and is_numeric( @Request::i()->range['start'] ) and is_numeric( @Request::i()->range['end'] ) )
		{
			return [
				"start" => DateTime::ts( (int) Request::i()->range['start'] )->setTime( 23, 59, 59 ),
				"end" => DateTime::ts( (int) Request::i()->range['end'] )->setTime( 0, 0, 0 )->sub( new DateInterval( "P7D" ) ),
			];
		}

		return [
			"start" => DateTime::create()->setTime( 0, 0, 0 )->sub( new DateInterval( "P7D" ) ), // The default time interval is 7 days
			"end" => DateTime::create()->setTime( 23, 59, 59 ),
		];
	}

	/**
	 * Get all the blocks in this report merged into one array
	 *
	 * @return array{headers: string[], data: string[][]}
	 */
	protected function _compileBlocks() : array
	{
		$returnData = [
			"headers" => [],
			"data" => []
		];

		$rowCount = 0;
		$columns = [];
		$dateRange = $this->_getDateRange();

		foreach( Chart::getSavedBlocks() as $block )
		{
			$title = Member::loggedIn()->language()->addToStack( $block["details"]["title"] );
			$numbersFound = [];

			if ( is_callable( array( $block['extension'], 'getBlockNumbers' ) ) )
			{
				// Allow extensions to define their own numbers
				$numbersFound = $block['extension']->getBlockNumbers( $dateRange, $block['subblock'] );
				if ( !is_array( $numbersFound ) )
				{
					$numbersFound = [[ Member::loggedIn()->language()->get( "statsreports_legacy_block" ) ]];
				}
			}
			else
			{
				// Here we use the same logic from the JS to find numbers
				$content = $block['extension']->getBlock( $dateRange, $block['subblock'] );
				Member::loggedIn()->language()->parseOutputForDisplay( $content );
				$doc = new DOMDocument();
				$doc->loadHTML( "<!DOCTYPE html>\n<html><head><meta charset=\"utf-8\"><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head><body>" . $content . "</body></html>" );
				$path = new DOMXPath( $doc );
				$previousPeriodLangString = Member::loggedIn()->language()->addToStack( "previous_period" );
				Member::loggedIn()->language()->parseOutputForDisplay( $previousPeriodLangString );
				$numbersFound = [[]];
				foreach ( ( $path->query( "//*[@data-number]|//*[@title][contains(@class, 'cStat__change')]" ) ?? [] ) as $node )
				{
					if ( $node instanceof DOMElement )
					{
						if ( $node->hasAttribute( "data-number" ) )
						{
							$numbersFound[0][] = trim( $node->getAttribute( "data-number" ) ) ?: 0;
						}
						else if ( $node->hasAttribute( "title" ) and ( $titleAttr = $node->getAttribute( "title" ) and str_starts_with( $titleAttr, $previousPeriodLangString ) ) )
						{
							$numbersFound[1][] = trim( str_replace( $previousPeriodLangString, '', $titleAttr ) ) ?: 0;
						}
					}
				}

				if ( count( $numbersFound ) === 2 )
				{
					$numbersFound = [
						"" => $numbersFound[0],
						"statsreports_previous_count" => $numbersFound[1]
					];
				}
			}

			$numbersFound = is_array( $numbersFound ) ? $numbersFound : [ $numbersFound ];
			foreach ( $numbersFound as $idx => $numbersFoundCol )
			{
				// we need to make sure this is a column and not just a scalar or associative array
				$numbersFoundCol = is_array( $numbersFoundCol ) ? $numbersFoundCol : [ $numbersFoundCol ];
				$numbersFoundCol = array_values( $numbersFoundCol );

				// When the column isn't named, we index by the block name and column number
				if ( !is_string( $idx ) )
				{
					$idx = $title . (count($numbersFound) > 1 ? " - (" . $idx + 1 . ")" : "");
				}
				else
				{
					$idx = $title . ($idx ? ' - ' . Member::loggedIn()->language()->addToStack( $idx ) : "");
				}
				Member::loggedIn()->language()->parseOutputForDisplay( $idx );
				$returnData['headers'][] = $idx;

				$rowCount = max( $rowCount, count( $numbersFoundCol ) );
				if ( $rowCount > count( $numbersFoundCol ) )
				{
					$numbersFoundCol = array_merge( $numbersFoundCol, array_fill( 0, $rowCount - count( $numbersFoundCol ), "NULL" ) );
				}
				$columns[] = $numbersFoundCol;
			}
		}

		// now we need to transpose the columns into rows
		for( $column = 0; $column < count( $columns ); $column++ )
		{
			for ( $row = 0; $row < $rowCount; $row++ )
			{
				$returnData['data'][$row] = $returnData['data'][$row] ?? [];
				$returnData['data'][$row][$column] = $columns[$column][$row] ?? "NULL";
			}
		}


		return $returnData;
	}
}