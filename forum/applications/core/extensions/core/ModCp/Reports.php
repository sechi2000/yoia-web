<?php
/**
 * @brief		Moderator Control Panel Extension: Reports
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		24 Oct 2013
 */

namespace IPS\core\extensions\core\ModCp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Reports\Report;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Select;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use function count;
use function intval;
use function iterator_to_array;
use function rtrim;
use function str_replace;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Report Center
 */
class _Reports extends \IPS\Content\Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static $contentModel = 'IPS\core\Reports\Report';
	
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return	string
	 */
	public function getTab()
	{
		/* Check Permissions */
		if ( ! Member::loggedIn()->modPermission('can_view_reports') )
		{
			return null;
		}
		
		return 'reports';
	}
		
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{		
		if ( !Member::loggedIn()->modPermission( 'can_view_reports' ) )
		{
			Output::i()->error( 'no_module_permission', '2C139/1', 403, '' );
		}
		
		Output::i()->sidebar['enabled'] = FALSE;
		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/modcp.css' ) );
		if ( \IPS\Theme::i()->settings['responsive'] )
		{
			Output::i()->cssFiles = array_merge( Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/modcp_responsive.css' ) );
		}
		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'front_modcp.js', 'core' ) );
		
		parent::execute();
	}
	
	/**
	 * Overview
	 *
	 * @return	void
	 */
	public function manage()
	{
		/* Make sure we're only loading reports where we have permission to view the content */
		$where = [ \IPS\core\Reports\Report::where() ];

		/* Make sure we're loading the correct statuses */
		if( Request::i()->isAjax() and isset( Request::i()->overview ) )
		{
			$where[] = array( "status IN( 1,2 )" );
		}

		/* Create table */
		$table = new \IPS\Helpers\Table\Content( '\IPS\core\Reports\Report', \IPS\Http\Url::internal( 'app=core&module=modcp&controller=modcp&tab=reports', NULL, 'modcp_reports' ), $where );
		$table->sortBy = $table->sortBy ?: 'first_report_date';
		$table->sortDirection = 'desc';
		
		/* Title is a special case in the Report center class that isn't available in the core_rc_index table so attempting to sort on it throws an error and does nothing */
		unset( $table->sortOptions['title'] );
		
		$table->filters = [
			'filter_report_status_1' => array( 'status=1' )
		];

		foreach ( \IPS\Application::allExtensions( 'core', 'ContentRouter') as $app => $classes )
		{
			$appKey = explode('_', $app)[0];
			$apps[ $appKey ] = $classes;
		}

		foreach( $apps as $appKey => $data )
		{
			$classes = $data->classes;
			foreach( $classes as $class )
			{
				if ( is_subclass_of( $class, "IPS\Content\Item" ) )
				{
					$classes[] = $class::$commentClass;

					if ( isset( $class::$reviewClass ) )
					{
						$classes[] = $class::$reviewClass;
					}
				}
			}

			$table->filters['filter_report_status_1_' . $appKey ] = [ 'status=1 and ' . \IPS\Db::i()->in( 'class', $classes ) ];
			Member::loggedIn()->language()->words[ 'filter_report_status_1_' . $appKey ] = Member::loggedIn()->language()->addToStack( 'filter_report_status_1_app', NULL, [ 'sprintf' => [ \IPS\Application::applications()[ $appKey ]->_title ] ] );
		}

		$table->filters['filter_report_status_2'] = [ 'status=2' ];
		$table->filters['filter_report_status_3'] = [ 'status=3' ];
		$table->filters['filter_report_status_4'] = [ 'status=4' ];
		$table->title = Member::loggedIn()->language()->addToStack( 'report_center_header' );

		if ( Request::i()->isAjax() and isset( Request::i()->overview ) )
		{
			$table->tableTemplate = array( \IPS\Theme::i()->getTemplate( 'modcp', 'core' ), 'reportListOverview' );
			Output::i()->json( array( 'data' => (string) $table ) );
		}
		else
		{
			Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( 'modcp_reports' ) );
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'modcp_reports' );
			return  \IPS\Theme::i()->getTemplate( 'modcp' )->reportList( (string) $table );
		}
	}

	/**
	 * View a report
	 *
	 * @return	void
	 */
	public function view()
	{
		/* Load Report */
		try
		{
			$report = \IPS\core\Reports\Report::loadAndCheckPerms( Request::i()->id );
			$report->markRead();
		}
		catch ( \OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C139/3', 404, '' );
		}
		
		/* Check permission. We do it this way rather than loadAndCheckPerms() since we need to know if the user *had* permission if the content has been deleted */
		$allowedPermIds = iterator_to_array( \IPS\Db::i()->select( 'perm_id', 'core_permission_index', \IPS\Db::i()->findInSet( 'perm_view', Member::loggedIn()->permissionArray() ) . " OR perm_view='*'" ) );
		$workingClass = $report->class;
		if ( isset( $workingClass::$itemClass ) )
		{
			$workingClass = $workingClass::$itemClass;
		}
		if ( isset( $workingClass::$containerNodeClass ) )
		{
			$workingClass = $workingClass::$containerNodeClass;
		}
		if ( isset( $workingClass::$permissionMap ) and isset( $workingClass::$permissionMap['read'] ) and $workingClass::$permissionMap['read'] !== 'view' )
		{
			$allowedPermIds = array_intersect( $allowedPermIds, iterator_to_array( \IPS\Db::i()->select( 'perm_id', 'core_permission_index', \IPS\Db::i()->findInSet( 'perm_' . $workingClass::$permissionMap['read'], Member::loggedIn()->permissionArray() ) . " OR perm_" . $workingClass::$permissionMap['read'] . "='*'" ) ) );
		}

		if ( \in_array( 'IPS\Content\Permissions', class_implements( $workingClass ) ) and !\in_array( $report->perm_id, $allowedPermIds ) )
		{
			Output::i()->error( 'node_error_no_perm', '2C139/5', 403, '' );
		}
		
		/* Setting status? */
		if( isset( Request::i()->setStatus ) and \in_array( Request::i()->setStatus, range( 1, 4 ) ) )
		{
			\IPS\Session::i()->csrfCheck();
			$report->changeStatus( (int) Request::i()->setStatus );

			Output::i()->redirect( $report->url()->setQueryString( 'changed', '1' ) );
		}

		/* Deleting? */
		if( isset( Request::i()->_action ) and Request::i()->_action == 'delete' and $report->canDelete() )
		{
			\IPS\Session::i()->csrfCheck();
			
			$report->delete();
			
			Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=modcp&controller=modcp&tab=reports', NULL, 'modcp_reports' ) );
		}

		/* Load */
		$comment = NULL;
		$item = NULL;
		$ref = NULL;
		$delLog = NULL;
		try
		{
			$reportClass = $report->class;
			$thing = $reportClass::load( $report->content_id );
			if ( $thing instanceof \IPS\Content\Comment )
			{
				$comment = $thing;
				$item = $comment->item();
				
				$class = $report->class;
				$itemClass = $class::$itemClass;
				$ref = $thing->warningRef();
			}
			else
			{
				$item = $thing;
				$itemClass = $report->class;
				$ref = $thing->warningRef();
			}
			
			$hidden = $thing->hidden();
			$contentToCheck = $thing;
			if ( ( $thing instanceof \IPS\Content\Comment ) AND $hidden !== -2 )
			{
				$hidden = $thing->item()->hidden();
				$contentToCheck = $thing->item();
			}
			
			if ( $hidden === -2 )
			{
				try
				{
					$delLog = \IPS\core\DeletionLog::loadFromContent( $contentToCheck );
				}
				catch( \OutOfRangeException $e ) {}
			}
		}
		catch ( \OutOfRangeException $e ) { }
		
		/* Next/Previous Links */
		$permSubQuery = \IPS\Db::i()->select( 'perm_id', 'core_permission_index', \IPS\Db::i()->findInSet( 'perm_view', array_merge( array( Member::loggedIn()->member_group_id ) , array_filter( explode( ',', Member::loggedIn()->mgroup_others ) ) ) ) . " or perm_view='*'" );

		$prevReport	= NULL;
		$prevItem	= NULL;
		$nextReport	= NULL;
		$nextItem	= NULL;
		
		/* Prev */
		try
		{
			$prevReport = \IPS\Db::i()->select( 'id, class, content_id', 'core_rc_index', array( '( perm_id IN (?) OR perm_id IS NULL ) AND first_report_date>?', $permSubQuery, $report->first_report_date ), 'first_report_date ASC', 1 )->first();
			
			try
			{
				$reportClass = $prevReport['class'];
				$prevItem = $reportClass::load( $prevReport['content_id'] );
				
				if ( $prevItem instanceof \IPS\Content\Comment )
				{
					$prevItem = $prevItem->item();
				}
			}
			catch (\OutOfRangeException $e) {}
		}
		catch ( \UnderflowException $e ) {}
		
		/* Next */
		try
		{
			$nextReport = \IPS\Db::i()->select( 'id, class, content_id', 'core_rc_index', array( '( perm_id IN (?) OR perm_id IS NULL ) AND first_report_date<?', $permSubQuery, $report->first_report_date ), 'first_report_date DESC', 1 )->first();

			try
			{
				$reportClass = $nextReport['class'];
				$nextItem = $reportClass::load( $nextReport['content_id'] );

				if ( $nextItem instanceof \IPS\Content\Comment )
				{
					$nextItem = $nextItem->item();
				}
			}
			catch (\OutOfRangeException $e) {}
		}
		catch ( \UnderflowException $e ) {}

		/* Display */
		if ( Request::i()->isAjax() and !isset( Request::i()->_contentReply ) and !isset( Request::i()->getUploader ) AND !isset( Request::i()->page ) AND !isset( Request::i()->_previewField ) )
		{
			Output::i()->output = \IPS\Theme::i()->getTemplate( 'modcp' )->reportPanel( $report, $comment, $ref );
		}
		else
		{
			if ( isset( Request::i()->changed ) )
			{
				/* Show a flash message */
				Output::i()->inlineMessage = Member::loggedIn()->language()->addToStack( 'report_status_changed');
			}

			$sprintf = $item ? htmlspecialchars( $item->mapped('title'), ENT_DISALLOWED | ENT_QUOTES, 'UTF-8', FALSE ) : Member::loggedIn()->language()->addToStack('content_deleted');
			Output::i()->title = Member::loggedIn()->language()->addToStack( 'modcp_reports_view', FALSE, array( 'sprintf' => array( $sprintf ) ) );
			Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=core&module=modcp&controller=modcp&tab=reports', 'front', 'modcp_reports' ), Member::loggedIn()->language()->addToStack( 'modcp_reports' ) );
			Output::i()->breadcrumb[] = array( NULL, $item ? $item->mapped('title') : Member::loggedIn()->language()->addToStack( 'content_deleted' ) );
			return \IPS\Theme::i()->getTemplate( 'modcp' )->report( $report, $comment, $item, $ref, $prevReport, $prevItem, $nextReport, $nextItem, $delLog );
		}
	}

	/**
	 * Show the report center modal
	 *
	 * @return void
	 */
	public function reportCenterConfirmModal()
	{
		$id = intval( Request::i()->id );

		try
		{
			$report = Report::load( $id );
		}
		catch( \OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S319/1', 404 );
		}

		$form = new Form( 'form', 'modal_author_notification_form_submit' );
		$form->class .= 'ipsForm_vertical';
		$form->addButton( 'cancel', 'link', $report->url() );

		/* Get the optional notifications */
		$notifications = [ 0 => 'modal_author_notification_none' ];
		foreach( Report::getAuthorNotifications() as $notification )
		{
			$notifications[ $notification['id'] ] = $notification['title'];
		}
		$form->add( new Select( 'modal_author_notification', null, null, [ 'options' => $notifications ] ) );

		try
		{
			$class = $report->class;
			$thing = $class::load( $report->content_id );
		}
		catch( \OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2S319/2', 404 );
		}

		/* Fix the language string */
		Member::loggedIn()->language()->words['modal_author_notification'] = Member::loggedIn()->language()->addToStack('modal_author__notification', null, [ 'sprintf' => [ $thing->author()->name ] ] );
		if ( $values = $form->values() )
		{
			\IPS\Session::i()->csrfCheck();
			$report->changeStatus( (int) Request::i()->status, $values['modal_author_notification'] );

			Output::i()->redirect( $report->url()->setQueryString( 'changed', '1' ) );
		}

		Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->genericBlock( (string) $form, null, 'ipsPadding' ) );
	}

	/**
	 * Change Report Reason (report_type)
	 *
	 * @return	void
	 */
	public function changeType()
	{

		/* Load the report and the main object */
		try
		{
			$report = \IPS\Db::i()->select( '*', 'core_rc_reports', [ 'id=?', Request::i()->id ] )->first();
			$item = \IPS\core\Reports\Report::loadAndCheckPerms( $report['rid'] );
		}
		catch ( \Exception $e )
		{
			Output::i()->error( 'node_error', '2C139/6', 404, '' );
		}

		/* Build form */
		$form = new Form;
		$options = array( \IPS\core\Reports\Report::TYPE_MESSAGE => Member::loggedIn()->language()->addToStack('report_message_comment') );
		foreach( \IPS\core\Reports\Types::roots() as $type )
		{
			$options[ $type->id ] = $type->_title;
		}

		if ( count( $options ) > 1 )
		{
			$form->add( new Select( 'report_type', $report['report_type'], null, [ 'options' => $options ] ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'report_type_change_log', true, null ) );
		}
		$form->class .= 'ipsForm_vertical';

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Update the new reason */
			\IPS\Db::i()->update( 'core_rc_reports', array( 'report_type' => $values['report_type'] ), [ 'id=?', $report['id'] ] );

			if ( ! empty( $values['report_type_change_log'] ) )
			{
				$old = Member::loggedIn()->language()->get('none');
				$new = Member::loggedIn()->language()->get('none');

				if ( $values['report_type'] )
				{
					$new = \IPS\core\Reports\Types::load( $values['report_type'] )->_title;
				}

				if ( $report['report_type'] )
				{
					try
					{
						$old = \IPS\core\Reports\Types::load( $report['report_type'] )->_title;
					}
					catch ( \OutOfRangeException $e )
					{
						Output::i()->error( 'node_error', '2C139/7', 403, '' );
					}
				}

				if ( $report['report_type'] != $values['report_type'] )
				{
					$url = $item->url()->setQueryString( 'tab', 'reports' )->setFragment( 'report' . $report['id'] );
					$content = Member::loggedIn()->language()->addToStack('report_type_changed', FALSE, array( 'sprintf' => array( $new, $old, $url, $report['id'] ) ) );
					Member::loggedIn()->language()->parseOutputForDisplay( $content );
					$content = str_replace( rtrim( \IPS\Settings::i()->base_url, '/' ), '<___base_url___>', $content );

					$comment = \IPS\core\Reports\Comment::create( $item, $content, TRUE, NULL, NULL, Member::loggedIn(), new \IPS\DateTime );
					$comment->save();
				}
			}

			Output::i()->redirect( $item->url() );
		}

		/* Display form */
		Output::i()->output = $form->customTemplate( array( \IPS\Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
	}

	/**
	 * Redirect to the original content for a report
	 *
	 * @return	void
	 */
	public function find()
	{		
		try
		{
			$report = \IPS\core\Reports\Report::loadAndCheckPerms( Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C139/4', 404, '' );
		}
		
		$reportClass = $report->class;
		$comment = $reportClass::load( $report->content_id );
		$url = Request::i()->parent ? $comment->item()->url() : $comment->url();
		$url = $url->setQueryString( '_report', $report->id );
		
		Output::i()->redirect( $url );
	}
	
	/**
	 * Return a comment URL
	 *
	 * @return \IPS\Http\Url
	 */
	 public function findComment()
	 {
		try
		{
			$report = \IPS\core\Reports\Report::loadAndCheckPerms( Request::i()->id );
			$comment = \IPS\core\Reports\Comment::load( Request::i()->comment );
		}
		catch ( \OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C139/4', 404, '' );
		}
		
		$url = $report->url()->setQueryString( 'activeTab', 'comments' );
		
		$idColumn = \IPS\core\Reports\Report::$databaseColumnId;
		$commentIdColumn = \IPS\core\Reports\Comment::$databaseColumnId;
		$position = \IPS\Db::i()->select( 'COUNT(*)', 'core_rc_comments', array( "rid=? AND id<=?", $report->$idColumn, $comment->$commentIdColumn ) )->first();
		
		$page = ceil( $position / $report::getCommentsPerPage() );
		if ( $page != 1 )
		{
			$url = $url->setPage( 'page', $page );
		}
		
		
		Output::i()->redirect( $url->setFragment( 'comment-' . $comment->$commentIdColumn ) );
	 }
}