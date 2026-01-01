<?php
/**
 * @brief		5.0.11 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		14 Aug 2025
 */

namespace IPS\core\setup\upg_5001101;

use IPS\Content\Search\Elastic\Index;
use IPS\Db;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use IPS\Member\Club;
use IPS\Settings;
use function defined;
use function json_encode;
use function rtrim;
use const IPS\LONG_REQUEST_TIMEOUT;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.11 Beta 1 Upgrade Code
 */
class Upgrade
{
	/**
	 * Clean up approval queue, due to a bug all content was stored there, while only hideable is supported.
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		$toRemove = ['IPS\core\Messenger\Conversation', 'IPS\core\Messenger\Message'];

		Db::i()->delete( 'core_approval_queue', Db::i()->in( 'approval_content_class', $toRemove ) );

		return TRUE;
	}

	/**
	 * Try and update the elasticsearch index without requiring a full reindex
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step2() : bool|array
	{
		if ( Settings::i()->search_method == 'elastic' )
		{
			$esUrl = Url::external( rtrim( Settings::i()->search_elastic_server, '/' ) . '/' . Settings::i()->search_elastic_index );

			try
			{
				/* Add the mapping for index_id */
				$r = Index::request( $esUrl->setPath( $esUrl->data[ Url::COMPONENT_PATH ] . '/_mapping' ), LONG_REQUEST_TIMEOUT )
					->setHeaders( array( 'Content-Type' => 'application/json' ) )
					->put( json_encode(
						[
							'properties' => [
								'index_id' => ['type' => 'keyword']
							]
						] ) );

				/* If the status code is not 20x then log it for review. We could force a rebuild at this point but perhaps this code runs twice, or some other failure as the mapping is there, so best human review it. */
				if ( $r->httpResponseCode >= 300 )
				{
					throw new \Exception( 'Failed to add index_id to mapping, consider manually rebuilding the search index' );
				}

				/* Now populate it */
				$r = Index::request( $esUrl->setPath( $esUrl->data[ Url::COMPONENT_PATH ] . '/_update_by_query' ), LONG_REQUEST_TIMEOUT )
					->setHeaders( array( 'Content-Type' => 'application/json' ) )
					->post( json_encode(
						[
							'script' => [
								'source' => 'ctx._source.index_id = ctx._id',
								'lang'   => 'painless'
							],
							'query'  => [
								'match_all' => (object) []
							]
						] ) );

				/* If the status code is not 20x then log it for review. We could force a rebuild at this point but perhaps this code runs twice, or some other failure as the mapping is there, so best human review it. */
				if ( $r->httpResponseCode >= 300 )
				{
					throw new \Exception( 'Failed to populate index_id, consider manually rebuilding the search index' );
				}
			}
			catch( \Exception $e )
			{
				/* Now log it */
				Log::log( $e->getMessage(), 'elastic' );
			}
		}

		return TRUE;
	}

	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}