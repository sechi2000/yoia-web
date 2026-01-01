<?php
/**
 * @brief		Overview statistics extension: Clubs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		16 Jan 2020
 */

namespace IPS\core\extensions\core\OverviewStatistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\OverviewStatisticsAbstract;
use IPS\Member;
use IPS\Settings;
use IPS\Theme;
use function count;
use function defined;
use function is_array;
use function round;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Overview statistics extension: Clubs
 */
class Clubs extends OverviewStatisticsAbstract
{
	/**
	 * @brief	Which statistics page (activity or user)
	 */
	public string $page	= 'activity';

	/**
	 * Return the sub-block keys
	 *
	 * @note This is designed to allow one class to support multiple blocks, for instance using the ContentRouter to generate blocks.
	 * @return array
	 */
	public function getBlocks(): array
	{
		if( Settings::i()->clubs )
		{
			return array( 'clubs', 'joins' );
		}

		return array();
	}

	/**
	 * Return block details (title and description)
	 *
	 * @param	string|NULL	$subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	array
	 */
	public function getBlockDetails( string $subBlock = NULL ): array
	{
		/* Description can be null and will not be shown if so */
		if( $subBlock == 'joins' )
		{
			return array( 'app' => 'core', 'title' => 'stats_overview_clubjoins', 'description' => 'stats_overview_clubjoins_desc', 'refresh' => 60 );
		}
		else
		{
			return array( 'app' => 'core', 'title' => 'stats_overview_clubs', 'description' => null, 'refresh' => 60 );
		}
	}

	/** 
	 * Return the block HTML to show
	 *
	 * @param	array|string|null    $dateRange	String for a fixed time period in days, NULL for all time, or an array with 'start' and 'end' \IPS\DateTime objects to restrict to
	 * @param	string|NULL	$subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	string
	 */
	public function getBlock( array|string $dateRange = NULL, string $subBlock = NULL ): string
	{
		if( $subBlock == 'joins' )
		{
			return $this->_showJoins( $dateRange );
		}
		else
		{
			return $this->_showClubs( $dateRange );
		}
	}

	/** 
	 * Return the block HTML to show
	 *
	 * @param	array|string|null    $dateRange	String for a fixed time period in days, NULL for all time, or an array with 'start' and 'end' \IPS\DateTime objects to restrict to
	 * @return	string
	 */
	public function _showJoins( array|string|null $dateRange = NULL ) : string
	{
		$data = $this->getBlockNumbers( $dateRange, 'joins' );
		return Theme::i()->getTemplate( 'stats' )->overviewComparisonCount( $data['statsreports_current_count'], $data['statsreports_previous_count'] );
	}

	/** 
	 * Return the block HTML to show
	 *
	 * @param	array|string|null    $dateRange	String for a fixed time period in days, NULL for all time, or an array with 'start' and 'end' \IPS\DateTime objects to restrict to
	 * @return	string
	 */
	public function _showClubs( array|string|null $dateRange = NULL ) : string
	{
		/* Init Chart */
		$pieBarData = array();
		$data = $this->getBlockNumbers( $dateRange, 'clubs' );

		$total = $data['statsreports_current_count'];
		$chart = NULL;

		// Add percentages
		foreach ( $data as $key => $value )
		{
			if ( in_array( $key, ['statsreports_current_count', 'statsreports_previous_count'] ) )
			{
				continue;
			}

			$pieBarData[] = [
				'name' => Member::loggedIn()->language()->addToStack( $key ),
				'value' => $value,
				'percentage' => round( ( $value / $total ) * 100, 2 ),
			];
		}

		if ( count( $pieBarData ) )
		{
			$chart = Theme::i()->getTemplate( 'global', 'core', 'global'  )->applePieChart( $pieBarData );
		}

		return Theme::i()->getTemplate( 'stats' )->overviewComparisonCount( $total, $data['statsreports_previous_count'], $chart );
	}



	/**
	 * Get the block numbers
	 *
	 * @param array|string|null $dateRange String for a fixed time period in days, NULL for all time, or an array with 'start' and 'end' \IPS\DateTime objects to restrict to
	 * @param string|NULL $subBlock The subblock we are loading as returned by getBlocks()
	 *
	 * @return array{statsreports_current_count: number|null, statsreports_previous_count: number|null}|number[]
	 */
	public function getBlockNumbers( array|string $dateRange = NULL, string $subBlock=NULL ) : array
	{
		if( $subBlock == 'joins' )
		{
			$where			= NULL;
			$previousCount	= NULL;

			if( $dateRange !== NULL )
			{
				if( is_array( $dateRange ) )
				{
					$where = array(
						array( 'joined > ?', $dateRange['start']->getTimestamp() ),
						array( 'joined < ?', $dateRange['end']->getTimestamp() ),
					);
				}
				else
				{
					$currentDate	= new DateTime;
					$interval = static::getInterval( $dateRange );
					$initialTimestamp = $currentDate->sub( $interval )->getTimestamp();
					$where = array( array( 'joined > ?', $initialTimestamp ) );

					$previousCount = Db::i()->select( 'COUNT(*)', 'core_clubs_memberships', array( array( 'joined BETWEEN ? AND ?', $currentDate->sub( $interval )->getTimestamp(), $initialTimestamp ) ) )->first();
				}
			}

			$count = Db::i()->select( 'COUNT(*)', 'core_clubs_memberships', $where )->first();
			return [
				'statsreports_current_count' => $count,
				'statsreports_previous_count' => $previousCount
			];
		}


		/* Init Chart */
		$pieBarData = array();

		/* Add Rows */
		$where			= NULL;
		$previousCount	= NULL;

		if( $dateRange !== NULL )
		{
			if( is_array( $dateRange ) )
			{
				$where = array(
					array( 'created > ?', $dateRange['start']->getTimestamp() ),
					array( 'created < ?', $dateRange['end']->getTimestamp() ),
				);
			}
			else
			{
				$currentDate	= new DateTime;
				$interval = static::getInterval( $dateRange );
				$initialTimestamp = $currentDate->sub( $interval )->getTimestamp();
				$where = array( array( 'created > ?', $initialTimestamp ) );

				$previousCount = Db::i()->select( 'COUNT(*)', 'core_clubs', array( array( 'created BETWEEN ? AND ?', $currentDate->sub( $interval )->getTimestamp(), $initialTimestamp ) ) )->first();
			}
		}

		$total = 0;

		foreach( Db::i()->select( 'COUNT(*) as total, `type`', 'core_clubs', $where, NULL, NULL, 'type' ) as $result )
		{
			$pieBarData[] = array(
				'name' =>  'club_type_' . $result['type'],
				'value' => $result['total'],
			);

			$total += $result['total'];
		}

		$return = [
			'statsreports_current_count' => $total,
			'statsreports_previous_count' => $previousCount
		];

		// Add percentages
		foreach( $pieBarData as $segment )
		{
			$return[$segment['name']] = $segment['value'];
		}

		return $return;
	}
}