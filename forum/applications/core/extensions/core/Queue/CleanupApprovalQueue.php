<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		11 Sep 2025
 */

namespace IPS\core\extensions\core\Queue;

use IPS\Application;
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
class CleanupApprovalQueue extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
        $data['count'] = (int) Db::i()->select( 'count(approval_id)', 'core_approval_queue' )->first();
        $data['processed'] = 0;
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
        $rows = iterator_to_array(
            Db::i()->select( '*', 'core_approval_queue', [ 'approval_id > ?', $data['lastId'] ], 'approval_id', [ 0, $limit ] )
        );

        if( empty( $rows ) )
        {
            throw new QueueOutOfRangeException;
        }

        $enabledApplications = array_keys( Application::enabledApplications() );

        foreach( $rows as $row )
        {
            $class = $row['approval_content_class'];

            /* Make sure this class exists. First we'll check the application,
            in the case of non-upgraded apps */
            $bits = explode( "\\", $class );
            if( $bits[1] != 'Member' and !in_array( $bits[1], $enabledApplications ) )
            {
                continue;
            }

            try
            {
                if( !class_exists( $class ) )
                {
                    throw new OutOfRangeException;
                }

                $class::load( $row['approval_content_id'] );
            }
            catch( OutOfRangeException )
            {
                Db::i()->delete( 'core_approval_queue', [ 'approval_id=?', $row['approval_id'] ] );
            }

            $data['processed']++;
            $data['lastId'] = $row['approval_id'];
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
            'text' => Member::loggedIn()->language()->addToStack( 'queue_cleanup_approval_queue' ),
            'complete' => ( $data['processed'] ? round( 100 / $data['count'] * $data['processed'], 2 ) : 0 )
        ];
	}
}