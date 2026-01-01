<?php
/**
 * @brief		Statistics Charts
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		1 Jul 2015
 */

namespace IPS\core\Statistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use InvalidArgumentException;
use IPS\Application;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\DateRange;
use IPS\Helpers\Form\Select;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function array_keys;
use function defined;
use function in_array;
use function is_array;
use function is_int;
use function is_numeric;
use function strval;
use function time;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Charts
 */
abstract class Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'NULL';

	/**
	 * Whether this chart can be saved so that extra tabs exist. When this is true, the chart can only be saved to reports
	 *
	 * @var bool
	 */
	public bool $noTabs = false;

	/**
	 * Get Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * 
	 * @return \IPS\Helpers\Chart
	 */
	abstract public function getChart( Url $url ): \IPS\Helpers\Chart;

	/**
	 * Load from Extension
	 *
	 * @param string $app
	 * @param string $extension
	 * @return    static
	 */
	public static function loadFromExtension( string $app, string $extension ): static
	{
		$extensions = Application::load( $app )->extensions( 'core', 'Statistics' );
		if ( in_array( $extension, array_keys( $extensions ) ) )
		{
			return $extensions[ $extension ];
		}

		throw new OutOfRangeException;
	}

	/**
	 * Load from Controller
	 *
	 * @param string $controller
	 * @return	static
	 * @throws	OutOfRangeException
	 */
	public static function loadFromController( string $controller ): static
	{
		foreach( Application::allExtensions( 'core', 'Statistics', FALSE ) AS $extension )
		{
			if ( $extension->controller AND $extension->controller === $controller )
			{
				return $extension;
			}
		}
		
		throw new OutOfRangeException;
	}
	
	/**
	 * Construct a saved chart from data
	 *
	 * @param	array|int			$data			Chart ID or pre-loaded chart data.
	 * @param	Url		$url			URL chart is shown on
	 * @param	Member|bool	$check			Check chart is owned by a specific member, or the currently logged in member if TRUE. If FALSE, no permission checking.
	 * 
	 * @return	\IPS\Helpers\Chart
	 * @throws	OutOfRangeException
	 * @throws	InvalidArgumentException
	 */
	public static function constructMemberChartFromData( array|int $data, Url $url, Member|bool $check = TRUE ): \IPS\Helpers\Chart
	{
		try
		{
			if ( $check === FALSE )
			{
				if ( !is_array( $data ) )
				{
					$data = Db::i()->select( '*', 'core_saved_charts', array( "id=?", $data ) )->first();
				}
			}
			else if ( ( $check instanceof Member ) AND $check->member_id )
			{
				if ( !is_array( $data ) )
				{
					$data = Db::i()->select( '*', 'core_saved_charts', array( "chart_id=? AND (chart_member=? OR chart_member IS NULL)", $data, $check->member_id ) )->first();
				}
				else if ( $data['chart_member'] !== $check->member_id and $data['chart_member'] !== null and $data['chart_member'] !== 0 )
				{
					throw new UnderflowException;
				}
			}
			else if ( $check === TRUE AND Member::loggedIn()->member_id )
			{
				if ( !is_array( $data ) )
				{
					$data = Db::i()->select( '*', 'core_saved_charts', array( "chart_id=? AND (chart_member=? OR chart_member IS NULL OR chart_member=0)", $data, Member::loggedIn()->member_id ) )->first();
				}
				else if ( $data['chart_member'] !== Member::loggedIn()->member_id and $data['chart_member'] !== null and $data['chart_member'] !== 0 )
				{
					throw new UnderflowException;
				}
			}
			else
			{
				/* If we're here, we were passed a guest object which isn't going to work. Throw a different exception as this should lead directly to a bugfix since this should never happen. */
				throw new InvalidArgumentException;
			}
			
			$extension = static::loadFromController( $data['chart_controller'] );
			$chart = $extension->getChart( $url );
			$currentFilters = array();
			foreach( json_decode( $data['chart_configuration'], true ) AS $k => $v )
			{
				if ( mb_substr( $k, 0, 11 ) == 'customform_' )
				{
					$currentFilters[ mb_substr( $k, 11 ) ] = $v;
				}
				else
				{
					$currentFilters[ $k ] = $v;
				}
			}
			
			$chart->savedCustomFilters = $currentFilters;
			$chart->timescale = $data['chart_timescale'] ?? $chart->timescale;
			$chart->title = $data['chart_title'];
			$chart->showFilterTabs = FALSE;
			$chart->showIntervals = FALSE;
			$chart->showDateRange = FALSE;

			$dateRange = static::getChartDateRanges( is_numeric( Request::i()->report_id ) ? (int) Request::i()->report_id : null );
			$chart->start = $dateRange['start'];
			$chart->end = $dateRange['end'];
			
			return $chart;
		}
		catch( UnderflowException )
		{
			/* Chart doesn't exist, or isn't owned by $member */
			throw new OutOfRangeException;
		}
	}
	
	/**
	 * Get charts for Member
	 *
	 * @param	Url		$url		URL
	 * @param	bool				$idsOnly	Return only ID's belonging to the user for lazyloading.
	 * @param	Member|null	$member		Member, or NULL for currently logged in member.
	 *
	 * @return	array
	 * @throws	InvalidArgumentException
	 */
	public static function getChartsForMember( Url $url, bool $idsOnly = FALSE, ?Member $member = NULL ): array
	{
		$member ??= Member::loggedIn();

		if ( !$member->member_id )
		{
			throw new InvalidArgumentException;
		}

		if ( Bridge::i()->featureIsEnabled( "community_health_stats" ) and !empty( $url->queryString['communityhealth'] ) )
		{
			$charts = Bridge::i()->getCommunityHealthCharts();
		}
		else
		{
			$return = [];
			$reportID = is_numeric( Request::i()->report_id ) ? (int) Request::i()->report_id : 0;
			$where = [];
			if ( $reportID > 0 )
			{
				$where[] = array( "(chart_member IS NULL OR chart_member=? OR chart_member=0) AND chart_report_id=?", $member->member_id, $reportID );
			}
			else
			{
				$where[] = array( "(chart_member IS NULL OR chart_member=0) AND chart_report_id IS NULL", $member->member_id );
			}

			$charts = Db::i()->select( '*', 'core_saved_charts', $where );
		}

		foreach( $charts AS $chart )
		{
			if ( $idsOnly )
			{
				$return[] = $chart['chart_id'];
			}
			else
			{
				try
				{
					$return[$chart['chart_id']] = array(
						'chart'		=> static::constructMemberChartFromData( $chart, $url, $member ),
						'data'		=> $chart
					);
				}
				catch( OutOfRangeException $e )
				{
					continue;
				}
			}
		}
		
		return $return;
	}


	/**
	 * Get the saved blocks for the current page.
	 *
	 * @return array
	 */
	public static function getSavedBlocks() : array
	{
		static $toDisplay = null;
		if ( !is_array( $toDisplay ) )
		{
			$where = [[ "(stat_member IS NULL OR stat_member=?)", Member::loggedIn()->member_id ]];
			if ( Request::i()->report_id )
			{
				$where[] = [ "stat_report_id=?", Request::i()->report_id ];
			}
			else
			{
				$where[] = [ "stat_report_id IS NULL" ];
			}

			$allExtensions = Application::allExtensions( 'core', 'OverviewStatistics', TRUE );
			$toDisplay = [];
			foreach ( Db::i()->select( "*", "core_saved_overview_stats", $where ) as $savedStat )
			{
				$key = $savedStat['stat_app'] . "_" . $savedStat['stat_extension'];
				if ( isset( $allExtensions[ $key ] ) and in_array( $savedStat['stat_subblock'], $allExtensions[ $key ]->getBlocks() ) )
				{
					$details = $allExtensions[ $key ]->getBlockDetails( $savedStat['stat_subblock'] );
					$details['title'] = $savedStat['stat_title'];

					$toDisplay[] = [
						"blockKey"      => $key,
						"subblock"      => $savedStat['stat_subblock'],
						"saved_stat_id" => $savedStat['id'],
						"details"       => $details,
						'page'          => $allExtensions[ $key ]->page,
						'extension'     => $allExtensions[$key],
					];
				}
			}
		}

		return $toDisplay;
	}

	/**
	 * Get the date range selector that is at the top of the saved reports acp page
	 * @return string
	 */
	public static function getBlockDateRangeForm( ?int $reportId=null ) : string
	{
		$options = array(
			'7'		=> 'last_week',
			'30'	=> 'last_month',
			'90'	=> 'last_three_months',
			'180'	=> 'last_six_months',
			'365'	=> 'last_year',
			'0'		=> 'alltime',
			'-1'	=> 'custom'
		);

		$defaultRange = '7';
		$dateRange = null;
		if ( ( is_numeric( Request::i()->predate ) and isset( $options[Request::i()->predate] ) ) or ( is_numeric( @Request::i()->date['start'] ) and is_numeric( @Request::i()->date['end'] ) ) )
		{
			if ( is_numeric( @Request::i()->date['start'] ) and is_numeric( @Request::i()->date['end'] ) )
			{
				$dateRange = [
					'start' => Request::i()->date['start'],
					'end' => Request::i()->date['end']
				];

				$defaultRange = '-1';
			}
			else
			{
				$defaultRange = Request::i()->predate;
			}
		}
		else if ( is_int( $reportId ) )
		{
			try
			{
				$currentReport = Db::i()->select( '*', 'core_reports', ['id=?', $reportId] )->first();
				$defaultRange = strval($currentReport['report_range_timescale'] ?? '7');
				if ( isset( $currentReport['report_range_start'] ) or isset( $currentReport['report_range_end'] ) )
				{
					$dateRange = [
						'start'=> $currentReport['report_range_start'],
						'end' => $currentReport['report_range_end'],
					];
				}
			}
			catch ( \UnderflowException ) {}
		}

		$form = new Form( 'posts', 'update' );
		$form->add( new Select( 'predate', $defaultRange, FALSE, array( 'options' => $options, 'toggles' => array( '-1' => array( 'dateFilterInputs' ) ) ) ) );
		$form->add( new DateRange( 'date', $dateRange, FALSE, array(), NULL, NULL, NULL, 'dateFilterInputs' ) );
		$form->class .= " ipsBox ipsPull i-padding_2";

		if ( is_int( $reportId ) and $values = $form->values() )
		{
			$update = [
				'report_range_timescale' => (int) $values['predate'],
				'report_range_start' => @$values['date']['start']?->getTimestamp() ?: null,
				'report_range_end' => @$values['date']['end']?->getTimestamp() ?: null
			];
			Db::i()->update( 'core_reports', $update, ['id=?', $reportId] );
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( ['message' => 'ok'], 200 );
			}
			Output::i()->redirect( (string) Url::internal( 'app=core&module=overview&controller=mycharts' ) );
		}

		return $form->customTemplate( array( Theme::i()->getTemplate( 'stats' ), 'filtersOverviewForm' ) );
	}

	/**
	 * @return array
	 */
	public static function getAppsToFilterBy()
	{
		static $apps = null;
		if ( !is_array( $apps ) )
		{
			$blocks = static::getSavedBlocks();
			$apps = [];
			foreach ( $blocks as $block )
			{
				if ( isset( $block['details']['app'] ) and !isset( $apps[ $block['details']['app'] ] ) )
				{
					$apps[ $block['details']['app'] ] = Application::load( $block['details']['app'] );
				}
			}
		}

		return $apps;
	}


	/**
	 * @param int|null $reportId The ID of the report, or leave null to use the default saved charts function
	 * @return array{start: DateTime|null, end: DateTime|null}
	 */
	public static function getChartDateRanges( ?int $reportId=null )
	{
		// It is possible this is included right in the request
		if ( isset( Request::i()->predate ) and is_numeric( Request::i()->predate ) and intval( Request::i()->predate ) >= 0 )
		{
			$days = (int) Request::i()->predate;
			if ( $days <= 0 )
			{
				return [
					'start' => null,
					'end' => null,
				];
			}
			return [
				'end' => DateTime::ts( time() )->setTime( 23, 59, 59 ),
				'start' => DateTime::ts( time() )->setTime( 0, 0, 0 )->sub( new DateInterval( "P{$days}D" ) )
			];
		}
		else if ( isset( Request::i()->date['start'] ) and isset( Request::i()->date['end'] ) ) // the way the JS works, the start and end are sent even if it just relies on the predate.
		{
			$start = is_numeric( Request::i()->date['start'] ) ? DateTime::ts( (int) Request::i()->date['start'] ) : null;
			$end = is_numeric( Request::i()->date['end'] ) ? DateTime::ts( (int) Request::i()->date['end'] ) : null;

			return [
				'start' => $start,
				'end' => $end,
			];
		}

		if ( is_null( $reportId ) )
		{
			if ( isset( Settings::i()->core_saved_charts_range_timescale ) and is_numeric( Settings::i()->core_saved_charts_range_timescale ) and intval( Settings::i()->core_saved_charts_range_timescale ) >= 0 )
			{
				$days = (int) Settings::i()->core_saved_charts_range_timescale;
				if ( $days <= 0 )
				{
					return [
						'start' => null,
						'end' => null,
					];
				}
				return [
					'end' => DateTime::ts( time() )->setTime( 23, 59, 59 ),
					'start' => DateTime::ts( time() )->setTime( 0, 0, 0 )->sub( new DateInterval( "P{$days}D" ) )
				];
			}

			$start = is_numeric( Settings::i()->core_saved_charts_range_start ) ? DateTime::ts( (int) Settings::i()->core_saved_charts_range_start ) : null;
			$end = is_numeric( Settings::i()->core_saved_charts_range_end ) ? DateTime::ts( (int) Settings::i()->core_saved_charts_range_end ) : null;
		}
		else
		{
			try
			{
				$row = Db::i()->select( "*", "core_reports", [ "id=?", $reportId ?: 0 ] )->first();
				$start = is_int( $row['report_range_start'] ) ? DateTime::ts( $row['report_range_start'] ) : null;
				$end = is_int( $row['report_range_end'] ) ? DateTime::ts( $row['end'] ) : null;
			}
			catch ( UnderflowException $e )
			{
				$start = null;
				$end = null;
			}
		}

		return [
			"start" => $start,
			"end" => $end
		];
	}
}