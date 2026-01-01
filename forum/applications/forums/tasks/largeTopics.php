<?php
/**
 * @brief		largeTopics Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		09 Oct 2025
 */

namespace IPS\forums\tasks;

use IPS\DateTime;
use IPS\Db;
use IPS\Events\Event;
use IPS\forums\Topic;
use IPS\forums\Topic\Post;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task;
use IPS\Task\Exception as TaskException;
use function defined;
use const IPS\LARGE_TOPIC_LOCK;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * largeTopics Task
 */
class largeTopics extends Task
{
	/**
	 * @brief	timestamp of the earliest topic we want to fetch
	 * @var		int
	 */
	const EARLIEST_TOPICS_TO_FETCH = 1762174055;

	/**
	 * @brief	Batch Size
	 * @var		int
	 */
	const NO_OF_TOPICS_TO_FETCH = 10;

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
		$largeTopics = iterator_to_array(
			new ActiveRecordIterator(
				Db::i()->select( '*', 'forums_topics', [ 'posts + topic_queuedposts >= ? and state=? and last_post > ?', LARGE_TOPIC_LOCK, 'open', static::EARLIEST_TOPICS_TO_FETCH ] , limit: static::NO_OF_TOPICS_TO_FETCH ),
				Topic::class
			)
		);

		$log = '';
		foreach( $largeTopics as $topic )
		{
			$this->autoClose( $topic );
			$log .= "Splitting topic {$topic->tid} with {$topic->posts} posts;";
		}

		return $log ?: null;
	}
	
	protected function autoClose( Topic $topic ) : void
	{
		/* Lock the original topic so that it doesn't get any bigger,
		but we're going to skip the modAction method because it uses permissions */
		$topic->state = 'closed';
		$topic->topic_open_time = 0;
		$topic->save();

		/* Fire an event here */
		Event::fire( 'onStatusChange', $topic, array( 'lock' ) );

		/* Is this already a child topic? */
		if( $parentTopic = $topic->parent() )
		{
			$sequence = $topic->sequence();
		}
		else
		{
			$sequence = 1;

			/* Add a record to the topic relationships table */
			Db::i()->insert( 'forums_topics_children', [ 'topic_id' => $topic->tid, 'parent_topic' => $topic->tid, 'sequence' => $sequence ] );
			$parentTopic = $topic;
		}

		$sequence++;

		/* Get the last post, that will be the first post in the new topic */
		/* @var Post $lastPost */
		$lastPost = $topic->comments( 1, 0, 'date', 'desc', includeHiddenComments: true );

		/* Create a new topic, using the last post author and timestamp */
		$newTopic = Topic::createItem( $lastPost->author(), $lastPost->ip_address, DateTime::ts( $lastPost->post_date ), $topic->container(), $topic->hidden() );
		$title = $lastPost->author()->language()->addToStack( 'large_topic_new_title', false, [ 'sprintf' => [ $parentTopic->title, $sequence ] ] );
		$lastPost->author()->language()->parseOutputForDisplay( $title );
		$newTopic->title = $title;
		$newTopic->save();

		/* Immediately add a row to the topic relationships table */
		Db::i()->insert( 'forums_topics_children', [ 'topic_id' => $newTopic->tid, 'parent_topic' => $parentTopic->tid, 'sequence' => $sequence ] );

		/* Handle tags */
		$newTopic->setTags( $parentTopic->tags() );

		/* Copy the last post to the new topic */
		$newFirstPost = Post::create( $newTopic, $lastPost->content(), true, null, null, $lastPost->author(), DateTime::ts( $lastPost->post_date ), $lastPost->ip_address );

		/* Was the last post anonymous? */
		if( $lastPost->author()->member_id and $lastPost->isAnonymous() )
		{
			$newTopic->setAnonymous( true, $lastPost->author() );
			$newFirstPost->setAnonymous( true, $lastPost->author() );
		}

		$newTopic->topic_firstpost = $newFirstPost->pid;
		$newTopic->save();

		/* Recount on the forums */
		$newTopic->container()->resetCommentCounts();

		/* Fire an event so that we know the topic was split */
		Event::fire( 'onItemSplit', $newTopic, array( $topic ) );
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