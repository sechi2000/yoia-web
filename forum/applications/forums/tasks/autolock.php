<?php
/**
 * @brief		autolock Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		23 Jul 2025
 */

namespace IPS\forums\tasks;

use IPS\Db;
use IPS\forums\Topic;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings as SettingsClass;
use IPS\Task;
use IPS\Task\Exception as TaskException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * autolock Task
 */
class autolock extends Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	TaskException
	 */
	public function execute() : mixed
	{
		$settings = SettingsClass::i()->autolock_topic_settings ? json_decode( SettingsClass::i()->autolock_topic_settings, true ) : [];

		/* Disable this task if it's mistakenly enabled */
		if( empty( $settings['enabled'] ) )
		{
			Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), array( '`key`=?', 'autolock' ) );
			return null;
		}

		$where = [
			[ 'state=?', 'open' ],
			[ 'last_post < ?', ( time() - ( $settings['days'] * 86400 ) ) ],
			[ Db::i()->in( 'topic_archive_status', [ Topic::ARCHIVE_DONE, Topic::ARCHIVE_WORKING, Topic::ARCHIVE_RESTORE ], true ) ]
		];

		if( !$settings['pinned'] )
		{
			$where[] = [ 'pinned=?', 0 ];
		}

		if( !$settings['featured'] )
		{
			$where[] = [ 'featured=?', 0 ];
		}

		if( !empty( $settings['forums'] ) )
		{
			$where[] = [ Db::i()->in( 'forum_id', $settings['forums'], true ) ];
		}

		if( !empty( $settings['members'] ) )
		{
			$where[] = [ Db::i()->in( 'starter_id', $settings['members'], true ) ];
		}

		if( !empty( $settings['tags'] ) )
		{
			$where[] = [ 'tid not in (?)', Db::i()->select( 'tag_meta_id', 'core_tags', [
				[ 'tag_meta_app=?', 'forums' ],
				[ 'tag_meta_area=?', 'forums' ],
				[ Db::i()->in( 'tag_text', $settings['tags'] ) ]
			] ) ];
		}

		$topicIds = [];
		foreach( new ActiveRecordIterator(
					 Db::i()->select( '*', 'forums_topics', $where, 'tid DESC', [ 0, 200 ] ),
					 Topic::class
				 ) as $topic )
		{
			try
			{
				/* @var Topic $topic */
				$topic->modAction( 'lock' );
				$topicIds[] = $topic->tid;
			}
			catch( \Exception )
			{
				/* Do nothing */
			}
		}

		return count( $topicIds ) ? implode( ",", $topicIds ) : null;
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup() : void
	{
		
	}
}