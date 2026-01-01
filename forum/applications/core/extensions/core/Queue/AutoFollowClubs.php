<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		19 Sep 2025
 */

namespace IPS\core\extensions\core\Queue;

use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\Member\Club;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task\Queue\OutOfRangeException as QueueOutOfRangeException;
use OutOfRangeException;
use function defined;
use const IPS\REBUILD_SLOW;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class AutoFollowClubs extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count'] = (int) Db::i()->select( 'count(*)', 'core_clubs_memberships', [
			[ 'club_id=?', $data['club' ] ],
			[ Db::i()->in( 'status', [ Club::STATUS_MODERATOR, Club::STATUS_LEADER, Club::STATUS_MODERATOR ] ) ]
		] )->first();
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
		$limit = REBUILD_SLOW;
		$members = iterator_to_array(
			new ActiveRecordIterator(
				Db::i()->select( '*', 'core_clubs_memberships', [
					[ 'club_id=?', $data['club' ] ],
					[ Db::i()->in( 'status', [ Club::STATUS_MEMBER, Club::STATUS_LEADER, Club::STATUS_MODERATOR ] ) ]
				], 'core_members.member_id', [ $offset, $limit ] )
					->join( 'core_members', 'core_clubs_memberships.member_id=core_members.member_id' ),
				Member::class
			)
		);

		/* If we have a node specified, we are only following the node */
		if( isset( $data['node'] ) and isset( $data['nodeId'] ) )
		{
			try
			{
				/* @var Model $nodeClass */
				$nodeClass = $data['node'];
				$node = $nodeClass::load( $data['nodeId'] );

				foreach( $members as $member )
				{
					$node->follow( 'immediate', true, $member );
				}
			}
			catch( OutOfRangeException )
			{
				throw new QueueOutOfRangeException;
			}
		}
		else
		{
			/* Otherwise we are following the club itself */
			try
			{
				$club = Club::load( $data['club'] );
				foreach( $members as $member )
				{
					$club->follow( 'immediate', true, $member );
				}
			}
			catch( OutOfRangeException )
			{
				throw new QueueOutOfRangeException;
			}
		}

		$offset += $limit;
		if( $offset >= $data['count'] )
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
		return [
			'text' => Member::loggedIn()->language()->addToStack( 'queue_club_auto_follow', false, [ 'sprintf' => Club::load( $data['club'] )->name ] ),
			'complete' => ( $offset ? round( 100 / $data['count'] * $offset, 2 ) : 0 )
		];
	}
}