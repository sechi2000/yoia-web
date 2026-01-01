<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		22 Sep 2025
 */

namespace IPS\forums\extensions\core\Queue;

use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\Task\Queue\OutOfRangeException as QueueOutOfRangeException;
use OutOfRangeException;
use function defined;
use const IPS\REBUILD_NORMAL;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class RebuildHelpfulCount extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count'] = (int) Db::i()->select( 'count(*)', 'forums_topics' )->first();
		$data['lastId'] = 0;
		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	array						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	QueueOutOfRangeException	        Indicates offset doesn't exist and thus task is complete
	 */
	public function run( array &$data, int $offset ): int
	{
		$limit = REBUILD_NORMAL;
		$currentId = $data['lastId'] ?? 0;

		$rows = Db::i()->select( 'tid, count(id) as helpful', 'forums_topics', [ 'tid>?', $currentId ], 'tid', $limit, 'tid' )
			->join( 'core_solved_index', [ 'core_solved_index.app=? and core_solved_index.type=? and forums_topics.tid=core_solved_index.item_id', 'forums', 'helpful'] );

		foreach( $rows as $row )
		{
			if( $row['helpful'] )
			{
				Db::i()->update( 'forums_topics', [ 'helpful_count' => $row['helpful'] ], [ 'tid=?', $row['tid'] ] );
			}

			$data['lastId'] = $row['tid'];
			$offset++;
		}

		if( $currentId === $data['lastId'] )
		{
			throw new QueueOutOfRangeException;
		}

		return $offset;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	array					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( array $data, int $offset ): array
	{
		return array(
			'text' =>  Member::loggedIn()->language()->addToStack('queue_rebuilding_helpful_counts'),
			'complete' => $offset ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100
		);
	}
}