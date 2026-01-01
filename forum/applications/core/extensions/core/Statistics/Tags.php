<?php
/**
 * @brief		Statistics Chart Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/

 * @since		26 Jan 2023
 */

namespace IPS\core\extensions\core\Statistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Application;
use IPS\Content\Taggable;
use IPS\DateTime;
use IPS\Db;
use IPS\Helpers\Chart;
use IPS\Helpers\Chart\Database;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Select;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Node\Model;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Chart Extension
 */
class Tags extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public ?string $controller = 'core_activitystats_tags';
	
	/**
	 * Render Chart
	 *
	 * @param	Url	$url	URL the chart is being shown on.
	 * @return Chart
	 */
	public function getChart( Url $url ): Chart
	{
		$chart = new Database( $url, 'core_tags', 'tag_added', '', array(
				'isStacked'			=> FALSE,
				'backgroundColor' 	=> '#ffffff',
				'hAxis'				=> array( 'gridlines' => array( 'color' => '#f5f5f5' ) ),
				'lineWidth'			=> 1,
				'areaOpacity'		=> 0.4
			), 
			'AreaChart',
			'daily',
			array( 'start' => DateTime::create()->sub( new DateInterval( 'P1M' ) ), 'end' => 0 )
		);
		$chart->setExtension( $this );
		$chart->groupBy			= 'tag_text';
		$chart->availableTypes	= array( 'AreaChart', 'ColumnChart', 'BarChart', 'PieChart' );

		$where = $chart->where;
		$where[] = array( "tag_added>?", 0 );
		if ( $chart->start )
		{
			$where[] = array( "tag_added>?", $chart->start->getTimestamp() );
		}
		if ( $chart->end )
		{
			$where[] = array( "tag_added<?", $chart->end->getTimestamp() );
		}
		
		/* Only get visible tags */
		$where[] = array( 'tag_aai_lookup IN(?)', Db::i()->select( 'tag_perm_aai_lookup', 'core_tags_perms', [ 'tag_perm_visible=1' ] ) );

		foreach( Db::i()->select( 'tag_text', 'core_tags', $where, NULL, NULL, array( 'tag_text' ) ) as $tag )
		{
			$chart->addSeries( $tag, 'number', 'COUNT(*)', TRUE, $tag );
		}

		$customValues = $chart->savedCustomFilters;
		$chart->customFiltersForm = array(
			'form' => $this->buildFormFields( $customValues ),
			'where' => function( $values )
			{
				$clause = [];
				$binds = [];
				$this->parseFormValues( $values );
				if( !empty( $values['app_key'] ) )
				{
					$clause[] = 'tag_meta_app=?';
					$binds[] = $values['app_key'];
					if( !empty( $values['item_class'] ) and !empty( $values['node_id'] ) )
					{
						$class = $values['item_class'];
						$clause[] = 'tag_meta_area=?';
						$binds[] = $class::$module;
						$clause[] = 'tag_meta_parent_id=?';
						$binds[] = $values['node_id'];
					}
				}

				if( count( $clause ) )
				{
					return array_merge( array( implode( " and ", $clause ) ), $binds );
				}

				return null;
			},
			'groupBy' => 'tag_text',
			'series'  => function( $values )
			{
				return [];
			},
			'defaultSeries' => function()
			{
				return [];
			}
		);
		
		return $chart;
	}

	/**
	 * Process the form values so that we can easily handle them in the logic above
	 *
	 * @param array $values
	 * @return void
	 */
	protected function parseFormValues( array &$values ) : void
	{
		if( empty( $values['app_key'] ) )
		{
			return;
		}

		/* If we already parsed this, stop here */
		if( array_key_exists( 'item_class', $values ) )
		{
			return;
		}

		/* Clear these out so that we don't have a mix-up later */
		if( isset( $values['node_class'] ) )
		{
			unset( $values['node_class'] );
			unset( $values['node_id'] );
		}

		foreach( Application::load( $values['app_key'] )->extensions( 'core', 'ContentRouter' ) as $extension )
		{
			foreach( $extension->classes as $class )
			{
				if( IPS::classUsesTrait( $class, Taggable::class ) )
				{
					$values['item_class'] = $class;
					if( isset( $class::$containerNodeClass ) )
					{
						$fieldKey = str_replace( '\\', '_', $class );
						if( array_key_exists( $fieldKey, $values ) and !empty( $values[ $fieldKey ] ) )
						{
							$values['node_class'] = $class::$containerNodeClass;
							$values['node_id'] = ( $values[ $fieldKey ] instanceof Model ? $values[ $fieldKey ]->_id : null );
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * Build an array of form fields for the custom filters
	 *
	 * @param array $customValues
	 * @return Select[]
	 */
	protected function buildFormFields( array $customValues ) : array
	{
		$options = array( '' => "All" );
		$fields = array();
		$toggles = [];
		foreach( Application::appsWithExtension( 'core', 'ContentRouter' ) as $key => $application )
		{
			$appHasTags = false;
			foreach( $application->extensions( 'core', 'ContentRouter' ) as $router )
			{
				foreach ( $router->classes as $class )
				{
					if( !IPS::classUsesTrait( $class, Taggable::class ) )
					{
						continue;
					}

					$appHasTags = true;
					if( isset( $class::$containerNodeClass ) )
					{
						$nodeField = new Node( str_replace( '\\', '_', $class ), ( isset( $customValues['app_key'] ) and $customValues['app_key'] == $key and isset( $customValues['node_id'] ) ) ? $customValues['node_id'] : null, FALSE, array(
							'class' => $class::$containerNodeClass,
							'clubs' => FALSE,
							'multiple' => FALSE,
							'autoPopulate' => TRUE
						), NULL, NULL, NULL, str_replace( '\\', '_', $class ) );
						$nodeField->label = Member::loggedIn()->language()->addToStack( $class::$containerNodeClass::$nodeTitle );
						$fields[] = $nodeField;

						$toggles[ $key ][] = str_replace( '\\', '_', $class );
					}
					continue 2;
				}
			}

			if( $appHasTags )
			{
				$options[ $key ] = $application->_title;
			}
		}

		$appField = new Select( 'app_key', $customValues['app_key'] ?? null, false, [
			'options' => $options,
			'toggles' => $toggles
		]);
		$appField->label = Member::loggedIn()->language()->addToStack( Application::$nodeTitle );
		$return = [
			$appField
		];

		foreach( $fields as $field )
		{
			$return[] = $field;
		}

		return $return;
	}
}