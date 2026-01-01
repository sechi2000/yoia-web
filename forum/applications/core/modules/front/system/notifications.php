<?php
/**
 * @brief		Notification Settings Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 Aug 2013
 */
 
namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Api\Webhook;
use IPS\Application;
use IPS\Content;
use IPS\Content\Item;
use IPS\core\DataLayer;
use IPS\core\Followed\Follow;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Events\Event;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Checkbox;
use IPS\Helpers\Form\Radio;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Device;
use IPS\Node\Model;
use IPS\Notification;
use IPS\Notification\Table;
use IPS\Output;
use IPS\Platform\Bridge;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function in_array;
use function md5;
use const IPS\REBUILD_QUICK;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notification Settings Controller
 */
class notifications extends Controller
{
	/**
	 * Execute
	 */
	protected function _checkLoggedIn() : void
	{
		if ( !Member::loggedIn()->member_id )
		{
			Output::i()->error( 'no_module_permission_guest', '2C154/2', 403, '' );
		}
		
		Output::i()->jsFiles	= array_merge( Output::i()->jsFiles, Output::i()->js('front_system.js', 'core' ) );
		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'styles/notification_settings.css' ) );
	}
	
	/**
	 * View Notifications
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$this->_checkLoggedIn();

		/* Init table */
		$urlObject	= Url::internal( 'app=core&module=system&controller=notifications', 'front', 'notifications' );
		$table = new Table( $urlObject );
		$table->setMember( Member::loggedIn() );
		
		$notifications = $table->getRows();
	
		Db::i()->update( 'core_notifications', array( 'read_time' => time() ), array( '`member`=?', Member::loggedIn()->member_id ) );
		Member::loggedIn()->recountNotifications();
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( array( 'data' => Theme::i()->getTemplate( 'system' )->notificationsAjax( $notifications ) ) );
		}
		else
		{
			Output::i()->metaTags['robots'] = 'noindex';
			Output::i()->title = Member::loggedIn()->language()->addToStack('notifications');
			Output::i()->breadcrumb[] = array( NULL, Output::i()->title );
			Output::i()->output = (string) $table;
		}
	}
	
	/**
	 * Subscribe a user's device to push notifications
	 *
	 * @return	void
	 */
	protected function subscribeToPush() : void
	{
		$this->_checkLoggedIn();
		Session::i()->csrfCheck();
		
		if ( isset( Request::i()->subscription ) AND $subscription = json_decode( Request::i()->subscription, TRUE ) )
		{
			$device = Device::loadOrCreate( Member::loggedIn(), FALSE );
			/* If a subscription already exists, then just return */
			try
			{
				Db::i()->select( '*', 'core_notifications_pwa_keys', array( "`member`=? AND p256dh=? AND auth=?", Member::loggedIn()->member_id, $subscription['keys']['p256dh'], $subscription['keys']['auth'] ) )->first();
				
				/* Make sure encoding and device is up to date, though since it's needed for encryption transfer. */
				Db::i()->update( 'core_notifications_pwa_keys', array(
					'encoding'		=> Request::i()->encoding,
					'device'		=> $device->device_key
				), array( "`member`=? AND p256dh=? AND auth=?", Member::loggedIn()->member_id, $subscription['keys']['p256dh'], $subscription['keys']['auth'] ) );
			}
			catch( UnderflowException $e )
			{
				Db::i()->insert( 'core_notifications_pwa_keys', array(
					'member'		=> Member::loggedIn()->member_id,
					'endpoint'		=> $subscription['endpoint'],
					'p256dh'		=> $subscription['keys']['p256dh'],
					'auth'			=> $subscription['keys']['auth'],
					'encoding'		=> Request::i()->encoding,
					'device'		=> $device->device_key
				) );

				/* The expectation is to receive some push notifications, so let's add them to follower_content, new_content and new_comment and they can be removed manually if the user doesn't want them */
				$notificationTypes = array( 'follower_content', 'new_content', 'new_comment' );

				foreach ( $notificationTypes as $notificationKey )
				{
					try
					{
						/* See if we have a row in preferences already */
						$currentRow = Db::i()->select( '*', 'core_notification_preferences', array( 'member_id=? AND notification_key=?', Member::loggedIn()->member_id, $notificationKey ) )->first();

						/* Does this row have a preference of push? */
						$prefs = explode( ',', $currentRow['preference'] );

						if ( !in_array( 'push', $prefs ) )
						{
							$prefs[] = 'push';
						}
						Db::i()->update( 'core_notification_preferences', [
							'preference' => implode( ',', $prefs ),
						], array( 'member_id=? AND notification_key=?', Member::loggedIn()->member_id, $notificationKey ) );
					}
					catch( UnderflowException $e )
					{
						Db::i()->insert( 'core_notification_preferences', [
							'member_id' => Member::loggedIn()->member_id,
							'notification_key' => $notificationKey,
							'preference' => implode( ',', [ 'inline', 'push' ] )
						], true );
					}
				}
			}
			
			Output::i()->json( 'OK' );
		}
		else
		{
			Output::i()->error( 'invalid_push_subscription', '2C154/H', 403, '' );
		}
	}

	/**
	 * Verify a subscription with the provided key exists for the logged-in user
	 *
	 * @return	void
	 */
	protected function verifySubscription() : void
	{
		$this->_checkLoggedIn();
		Session::i()->csrfCheck();
		
		if ( isset( Request::i()->key ) )
		{
			/* See if a subscription already exists */
			try
			{
				Db::i()->select( '*', 'core_notifications_pwa_keys', array( "`member`=? AND p256dh=?", Member::loggedIn()->member_id, Request::i()->key ) )->first();
				Output::i()->json( 'OK' );
			}
			catch( UnderflowException $e )
			{
				// Just let it return the error below
			}
		}

		Output::i()->error( 'invalid_push_subscription', '2C154/I', 403, '' );
	}
	
	/**
	 * Options: Dispatcher
	 *
	 * @return	void
	 */
	protected function options() : void
	{
		/* Check we're logged in */
		$this->_checkLoggedIn();
		
		/* Init breadcrumb */
		$extensions = Application::allExtensions( 'core', 'Notifications' );
		Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=system&controller=notifications', 'front', 'notifications' ), Member::loggedIn()->language()->addToStack('notifications') );
		Output::i()->breadcrumb[] = array( Url::internal( 'app=core&module=system&controller=notifications&do=options', 'front', 'notifications_options' ), Member::loggedIn()->language()->addToStack('options') );

		/* Are we viewing a particular type? */
		if ( isset( Request::i()->type ) and array_key_exists( Request::i()->type, $extensions ) )
		{
			Output::i()->title = Member::loggedIn()->language()->addToStack( "notifications__" . Request::i()->type );
			Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack( "notifications__" . Request::i()->type ) );
			$this->_optionsType( Request::i()->type, $extensions[ Request::i()->type ] );
		}
				
		/* Nope, viewing the index */
		else
		{			
			$this->_optionsIndex( $extensions );
		}
	}
	
	/**
	 * Options: Index
	 *
	 * @param	array	$extensions	The extensions
	 * @return	void
	 */
	protected function _optionsIndex( array $extensions ) : void
	{		
		Output::i()->title = Member::loggedIn()->language()->addToStack('notification_options');
		Output::i()->output = Theme::i()->getTemplate('system')->notificationSettingsIndex( Notification::membersOptionCategories( Member::loggedIn(), $extensions ) );
		Output::i()->globalControllers[] = 'core.front.system.notificationSettings';
	}
	
	/**
	 * Options: Index
	 *
	 * @param	string	$extensionKey	The extension key
	 * @param	object	$extension		The extension
	 * @return	void
	 */
	protected function _optionsType( string $extensionKey, object $extension ) : void
	{
		$form = Notification::membersTypeForm( Member::loggedIn(), $extension );
		if ( $form === TRUE )
		{
			if ( Request::i()->isAjax() )
			{
				$categories = Notification::membersOptionCategories( Member::loggedIn(), array( $extensionKey => $extension ) );
				Output::i()->sendOutput( Theme::i()->getTemplate('system')->notificationSettingsIndexRowDetails( $extensionKey, $categories[ $extensionKey ] ), 200, 'text/html', Output::i()->httpHeaders );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=core&module=system&controller=notifications&do=options', 'front', 'notifications_options' ), 'saved' );
			}
		}
		elseif ( $form )
		{
			$form->class = 'ipsForm--vertical ipsForm--notification-options';
		}
		
		if ( Request::i()->isAjax() and ! isset( Request::i()->fromFollowButton ) )
		{
			$form->actionButtons = array();
			Output::i()->sendOutput( Theme::i()->getTemplate('system')->notificationSettingsType( Member::loggedIn()->language()->addToStack("notifications__{$extensionKey}"), $form, TRUE ), 200, 'text/html', Output::i()->httpHeaders );
		}
		else
		{
			/* If it's not ajax, or come via the follow button, then we want to show the form with the save button */
			Output::i()->output = Theme::i()->getTemplate('system')->notificationSettingsType( Member::loggedIn()->language()->addToStack("notifications__{$extensionKey}"), $form, FALSE );
		}
	}
	
	/**
	 * Stop receiving all notifications to a particular method
	 *
	 * @return void
	 */
	protected function disable() : void
	{
		$this->_checkLoggedIn();
		Session::i()->csrfCheck();
		
		foreach ( Application::allExtensions( 'core', 'Notifications' ) as $extension )
		{
			$options = Notification::availableOptions( Member::loggedIn(), $extension );
			foreach ( $options as $option )
			{
				if ( $option['type'] === 'standard' )
				{
					$value = array();
					foreach ( $option['options'] as $k => $optionDetails )
					{
						if ( ( $optionDetails['editable'] and $k !== Request::i()->type and $optionDetails['value'] ) or ( !$optionDetails['editable'] and $optionDetails['value'] ) )
						{
							$value[] = $k;
						}
					}
					
					foreach ( $option['notificationTypes'] as $notificationKey )
					{
						Db::i()->insert( 'core_notification_preferences', array(
							'member_id'			=> Member::loggedIn()->member_id,
							'notification_key'	=> $notificationKey,
							'preference'		=> implode( ',', $value )
						), TRUE );
					}
				}
			}

			$extension::disableExtra( Member::loggedIn(), Request::i()->type );
		}

		if ( Request::i()->type === 'push' )
		{
			Member::loggedIn()->clearPwaAuths();
		}

		/* Digests */
		Db::i()->update( 'core_follow', array( 'follow_notify_freq' => 'none'), array( 'follow_member_id=? AND follow_notify_freq IN(?,?)', Member::loggedIn()->member_id, "daily", "weekly" ) );

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'ok' );
		}
		else
		{
			Output::i()->redirect( Url::internal( 'app=core&module=system&controller=notifications&do=options', 'front', 'notifications_options' ) );
		}
	}
	
	/**
	 * Follow Something
	 *
	 * @return	void
	 */
	protected function follow() : void
	{
		$this->_checkLoggedIn();

		try
		{
			$application = Application::load( Request::i()->follow_app );
		}
		catch( OutOfRangeException $e )
		{
			Output::i()->error( 'error_no_app', '3C154/F', 404, '' );
		}

		/* Get class */
        $class = Follow::getClassToFollow( Request::i()->follow_app, Request::i()->follow_area );
		
		if( Request::i()->follow_app == 'core' and Request::i()->follow_area == 'member' )
		{
			/* You can't follow yourself */
			if( Request::i()->follow_id == Member::loggedIn()->member_id )
			{
				Output::i()->error( 'cant_follow_self', '3C154/7', 403, '' );
			}
			
			/* Following disabled */
			$member = Member::load( Request::i()->follow_id );

			if( !$member->member_id )
			{
				Output::i()->error( 'cant_follow_member', '3C154/9', 403, '' );
			}

			if( $member->members_bitoptions['pp_setting_moderate_followers'] and !Member::loggedIn()->following( 'core', 'member', $member->member_id ) )
			{
				Output::i()->error( 'cant_follow_member', '3C154/8', 403, '' );
			}
		}
		
		if ( !$class )
		{
			Output::i()->error( 'node_error', '2C154/3', 404, '' );
		}
		
		/* Get thing */
		$thing = NULL;
		try
		{
			if ( in_array( 'IPS\Node\Model', class_parents( $class ) ) )
			{
				/* @var Model $class */
				$thing = $class::loadAndCheckPerms( (int) Request::i()->follow_id );
				Output::i()->title = Member::loggedIn()->language()->addToStack( 'follow_thing', FALSE, array( 'sprintf' => array( $thing->_title ) ) );

				/* Set navigation */
				try
				{
					foreach ( $thing->parents() as $parent )
					{
						Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
					}
					Output::i()->breadcrumb[] = array( NULL, $thing->_title );
				}
				catch ( Exception $e ) { }
			}
			elseif ( $class == 'IPS\Member\Club' )
			{
                /* @var Club $class */
				$thing = $class::loadAndCheckPerms( (int) Request::i()->follow_id );
				Output::i()->title = Member::loggedIn()->language()->addToStack( 'follow_thing', FALSE, array( 'sprintf' => array( $thing->_title ) ) );
				Output::i()->breadcrumb = array(
					array( Url::internal( 'app=core&module=clubs&controller=directory', 'front', 'clubs_list' ), Member::loggedIn()->language()->addToStack('module__core_clubs') ),
					array( $thing->url(), $thing->name )
				);
			}
			elseif ( $class != "IPS\Member" )
			{	
				if( !IPS::classUsesTrait( $class, 'IPS\Content\Followable' ) )
				{
					throw new OutOfRangeException;
				}

				/* @var Item $class */
				$thing = $class::loadAndCheckPerms( (int) Request::i()->follow_id );
				Output::i()->title = Member::loggedIn()->language()->addToStack( 'follow_thing', FALSE, array( 'sprintf' => array( $thing->mapped('title') ) ) );

				/* Set navigation */
				$container = NULL;
				try
				{
					$container = $thing->container();
					foreach ( $container->parents() as $parent )
					{
						Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
					}
					Output::i()->breadcrumb[] = array( $container->url(), $container->_title );
				}
				catch ( Exception $e ) { }
				
				/* Set meta tags */
				Output::i()->breadcrumb[] = array( NULL, $thing->mapped('title') );
			}
			else 
			{
				$thing = $class::load( (int) Request::i()->follow_id );
				
				Output::i()->title = Member::loggedIn()->language()->addToStack('follow_thing', FALSE, array( 'sprintf' => array( $thing->name ) ) );

				/* Set navigation */
				Output::i()->breadcrumb[] = array( NULL, $thing->name );
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C154/4', 404, '' );
		}
		
		/* Do we follow it? */
		try
		{
			$current = Db::i()->select( '*', 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_rel_id=? AND follow_member_id=?', Request::i()->follow_app, Request::i()->follow_area, Request::i()->follow_id, Member::loggedIn()->member_id ) )->first();
		}
		catch ( UnderflowException $e )
		{
			$current = FALSE;
		}
				
		/* How do we receive notifications? */
		if ( $class == 'IPS\Member' )
		{
			$type = 'follower_content';
		}
		elseif ( $class == 'IPS\Member\Club' or in_array( 'IPS\Content\Item', class_parents( $class ) ) )
		{
			$type = 'new_comment';
		}
		else
		{
			$type = 'new_content';
		}
		$notificationConfiguration = Member::loggedIn()->notificationsConfiguration();
		$notificationConfiguration = $notificationConfiguration[$type] ?? array();
		$lang = 'follow_type_immediate';
		if ( in_array( 'email', $notificationConfiguration ) and in_array( 'inline', $notificationConfiguration ) )
		{
			$lang = 'follow_type_immediate_inline_email';
		}
		elseif ( in_array( 'email', $notificationConfiguration ) )
		{
			$lang = 'follow_type_immediate_email';
		}
		
		if ( $class == "IPS\Member" )
		{
			Member::loggedIn()->language()->words[ $lang ] = Member::loggedIn()->language()->addToStack( $lang . '_member', FALSE, array( 'sprintf' => array( $thing->name ) ) );
		}
		
		if ( empty( $notificationConfiguration ) )
		{
			Member::loggedIn()->language()->words[ $lang . '_desc' ] = Member::loggedIn()->language()->addToStack( 'follow_type_immediate_none', FALSE ) . ' <a href="' .  Url::internal( 'app=core&module=system&controller=notifications&do=options&type=core_Content', 'front', 'notifications_options' ) . '">' . Member::loggedIn()->language()->addToStack( 'notification_options', FALSE ) . '</a>';
		}
		else
		{
			Member::loggedIn()->language()->words[ $lang . '_desc' ] = '<a href="' .  Url::internal( 'app=core&module=system&controller=notifications&do=options&type=core_Content', 'front', 'notifications_options' ) . '">' . Member::loggedIn()->language()->addToStack( 'follow_type_immediate_change', FALSE ) . '</a>';
		}
			
		/* Build form */
		$form = new Form( 'follow', ( $current ) ? 'update_follow' : 'follow', NULL, array(
			'data-followApp' 	=> Request::i()->follow_app,
			'data-followArea' 	=> Request::i()->follow_area,
			'data-followID' 	=> Request::i()->follow_id
		) );

		$form->class = 'ipsForm--vertical ipsForm--notifications-follow';
		
		$options = array();

		if( $class != "IPS\Content\Tag" )
		{
			$options['immediate'] = $lang;
		}
		
		if ( $class != "IPS\Member" )
		{
			if ( $class != "IPS\Member\Club" )
			{
				$options['daily']	= Member::loggedIn()->language()->addToStack('follow_type_daily');
				$options['weekly']	= Member::loggedIn()->language()->addToStack('follow_type_weekly');
			}
			$options['none']	= Member::loggedIn()->language()->addToStack('follow_type_no_notification');
		}
		
		if ( count( $options ) > 1 )
		{
			$form->add( new Radio( 'follow_type', $current ? $current['follow_notify_freq'] : NULL, TRUE, array(
				'options'	=> $options,
				'disabled'	=> empty( $notificationConfiguration ) ? array( 'immediate' ) : array()
			) ) );
		}
		else
		{
			foreach ( $options as $k => $v )
			{
				$form->hiddenValues[ 'follow_type' ] = $k;
				if ( empty( $notificationConfiguration ) )
				{
					$type = $type == 'follower_content' ? 'core_Content' : $type;
					$form->addMessage( Member::loggedIn()->language()->addToStack( 'follow_type_no_config' ) . ' <a href="' .  Url::internal( 'app=core&module=system&controller=notifications&do=options&type=' . $type, 'front', 'notifications_options' ) . '">' . Member::loggedIn()->language()->addToStack( 'notification_options', FALSE ) . '</a>', ' ', FALSE );
				}
				else
				{
					$form->addMessage( Member::loggedIn()->language()->addToStack( $v ) . '<br>' . Member::loggedIn()->language()->addToStack( $lang  . '_desc' ), ' ', FALSE );
				}
			}
		}
		$form->add( new Checkbox( 'follow_public', $current ? !$current['follow_is_anon'] : TRUE, FALSE, array(
			'label' => ( $class != "IPS\Member" ) ? Member::loggedIn()->language()->addToStack( 'follow_public' ) : Member::loggedIn()->language()->addToStack('follow_public_member', FALSE, array( 'sprintf' => array( $thing->name ) ) )
		) ) );
		if ( $current )
		{
			$unfollowUrl = Url::internal( "app=core&module=system&controller=notifications&do=unfollow&id={$current['follow_id']}&follow_app={$current['follow_app']}&follow_area={$current['follow_area']}" )->csrf();
			if ( method_exists( $thing, 'url' ) AND $thing->url() )
			{
				$unfollowUrl = $unfollowUrl->addRef( (string) $thing->url() );
			}
			$form->addButton( 'unfollow', 'link', $unfollowUrl, 'ipsButton ipsButton--text ipsButton--icon', array('data-action' => 'unfollow') );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
            $class = Follow::getClassToFollow( Request::i()->follow_app, Request::i()->follow_area );
            try
            {
				$useDataLayer = Member::loggedIn()->member_id and DataLayer::enabled( 'analytics_full' );
				$alreadyFollowing = $useDataLayer ? Db::i()->select( 'count(*)', 'core_follow', [
					'follow_app=? AND follow_area=? AND follow_rel_id=? AND follow_member_id=?', Request::i()->follow_app, Request::i()->follow_area, Request::i()->follow_id, Member::loggedIn()->member_id
				] )->first() : false;
                $thing = $class::load( Request::i()->follow_id );
                $thing->follow( $values['follow_type'], $values['follow_public'] );

				if ( $useDataLayer and !$alreadyFollowing )
				{
					DataLayer::i()->addEvent( 'follow', [
						'follow_app' => Request::i()->follow_app,
						'follow_area' => Request::i()->follow_area,
						'follow_id' => Request::i()->follow_id
					]);
				}
            }
            catch( OutOfRangeException ){}
			
			/* Boink */
			if ( Request::i()->isAjax() )
			{
				Output::i()->json( 'ok' );
			}
			else
			{
				Output::i()->redirect( $thing->url() );
			}
		}

		/* Display */
		$output = $form->customTemplate( array( Theme::i()->getTemplate( 'system', 'core' ), 'followForm' ) );

		if( Request::i()->isAjax() )
		{
			Output::i()->sendOutput( $output );
		}
		else
		{
			Output::i()->output = $output;
		}		
	}
	
	/**
	 * Unfollow
	 *
	 * @return	void
	 */
	protected function unfollow() : void
	{
		$this->_checkLoggedIn();

		Session::i()->csrfCheck();
		
		try
		{
            $follow = Follow::load( Request::i()->id );
            if( $follow->member_id != Member::loggedIn()->member_id )
            {
                throw new OutOfRangeException;
            }
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'cant_find_unfollow', '2C154/D', 404, '' );
		}

        /* We call the unfollow on the object, not on the follow record.
        This is so that we can handle object-specific actions (e.g. unfollow a club unfollow club nodes)
        It's a bit circular, but we'll live. */
        if( $item = $follow->_item )
        {
            $item->unfollow( Member::loggedIn(), $follow->id );

	        if ( DataLayer::enabled( 'analytics_full' ) )
	        {
		        DataLayer::i()->addEvent( 'unfollow', [
			        'follow_app' => $follow->app,
			        'follow_area' => $follow->area,
			        'follow_id' => $follow->rel_id,
		        ]);
	        }
        }

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'ok' );
		}
		else
		{
			Output::i()->redirect( Request::i()->referrer() ?: Url::internal( '' ) );
		}
	}
	
	/**
	 * Show Followers
	 *
	 * @return	void
	 */
	protected function followers() : void
	{
		$perPage	= 50;
		$thisPage	= isset( Request::i()->followerPage ) ? Request::i()->followerPage : 1;
		$thisPage	= ( $thisPage > 0 ) ? $thisPage : 1;

		if( !Member::loggedIn()->group['g_view_followers'] )
		{
			Output::i()->error( 'module_no_permission', '2C154/16', 403 );
		}

		if( !Request::i()->follow_app OR !Request::i()->follow_area )
		{
			Output::i()->error( 'node_error', '2C154/E', 404, '' );
		}
				
		/* Get class */
		if( Request::i()->follow_app == 'core' and Request::i()->follow_area == 'member' )
		{
			$class = 'IPS\Member';
		}
		else if( Request::i()->follow_app == 'core' and Request::i()->follow_area == 'club' )
		{
			$class = 'IPS\Member\Club';
		}
		elseif( Request::i()->follow_app == 'core' and Request::i()->follow_area == 'tag' )
		{
			$class = 'IPS\Content\Tag';
		}
		else
		{
			$class = 'IPS\\' . Request::i()->follow_app . '\\' . IPS::mb_ucfirst( Request::i()->follow_area );
		}
		
		if ( !class_exists( $class ) or !array_key_exists( Request::i()->follow_app, Application::applications() ) )
		{
			Output::i()->error( 'node_error', '2C154/5', 404, '' );
		}
		
		/* Get thing */
		$thing = NULL;
		$anonymous = 0;
		try
		{
			if ( $class == "IPS\Member\Club" or $class == 'IPS\Content\Tag' )
			{
				$thing = $class::loadAndCheckPerms( (int) Request::i()->follow_id );
				$followers = $thing->followers( Content::FOLLOW_PUBLIC, array( 'none', 'immediate', 'daily', 'weekly' ), NULL, array( ( $thisPage - 1 ) * $perPage, $perPage ), 'name' );
				$followersCount = $thing->followersCount();
				$anonymous = $thing->followersCount( Content::FOLLOW_ANONYMOUS );
				$title = $thing->_title;
			}
			elseif ( in_array( 'IPS\Node\Model', class_parents( $class ) ) )
			{
				$classname = $class::$contentItemClass;
				$containerClass = $class;
				$thing = $containerClass::loadAndCheckPerms( (int) Request::i()->follow_id );
				$followers = $classname::containerFollowers( $thing, Content::FOLLOW_PUBLIC, array( 'none', 'immediate', 'daily', 'weekly' ), NULL, array( ( $thisPage - 1 ) * $perPage, $perPage ), 'name' );
				$followersCount = $classname::containerFollowerCount( $thing );
				$anonymous = $classname::containerFollowerCount( $thing, Content::FOLLOW_ANONYMOUS );
				$title = $thing->_title;
			}
			else if ( $class != "IPS\Member" )
			{
				/* @var Item $thing */
				$thing = $class::loadAndCheckPerms( (int) Request::i()->follow_id );
				$followers = $thing->followers( Content::FOLLOW_PUBLIC, array( 'none', 'immediate', 'daily', 'weekly' ), NULL, array( ( $thisPage - 1 ) * $perPage, $perPage ), 'name' );
				$followersCount = $thing->followersCount();
				$anonymous = $thing->followersCount( Content::FOLLOW_ANONYMOUS );
				$title = $thing->mapped('title');
			}
			else
			{
				$thing = $class::load( (int) Request::i()->follow_id );
				$followers = $thing->followers( Content::FOLLOW_PUBLIC, array( 'none', 'immediate', 'daily', 'weekly' ), NULL, array( ( $thisPage - 1 ) * $perPage, $perPage ), 'name' );
				$followersCount = $thing->followersCount();
				$anonymous = $thing->followersCount( Content::FOLLOW_ANONYMOUS );
				$title = $thing->name;
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C154/6', 404, '' );
		}

		/* Display */
		if ( Request::i()->isAjax() and isset( Request::i()->_infScroll ) )
		{
			Output::i()->sendOutput(  Theme::i()->getTemplate( 'system' )->followersRows( $followers ) );
		}
		else
		{
			$url = Url::internal( "app=core&module=system&controller=notifications&do=followers&follow_app=". Request::i()->follow_app ."&follow_area=". Request::i()->follow_area ."&follow_id=" . Request::i()->follow_id . "&_infScroll=1" );
			$removeAllUrl = Url::internal( "app=core&module=system&controller=notifications&do=removeFollowers&follow_app=". Request::i()->follow_app ."&follow_area=". Request::i()->follow_area ."&follow_id=" . Request::i()->follow_id )->csrf();
			if ( method_exists( $thing, 'url' ) AND $thing->url() )
			{
				$removeAllUrl = $removeAllUrl->addRef( (string) $thing->url() );
			}

			$pagination = Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $url, ceil( $followersCount / $perPage ), $thisPage, $perPage, FALSE, 'followerPage' );
			
			/* Instruct bots not to index this page */
			Output::i()->metaTags['robots']	= 'noindex';

			Output::i()->title = Member::loggedIn()->language()->addToStack('item_followers', FALSE, array( 'sprintf' => array( $title ) ) );
			Output::i()->breadcrumb[] = array( $thing->url(), $title );
			Output::i()->breadcrumb[] = array( NULL, Member::loggedIn()->language()->addToStack('who_follows_this') );
			Output::i()->output = Theme::i()->getTemplate( 'system' )->followers( $url, $pagination, $followers, $anonymous, $removeAllUrl );
		}
	}
	
	/**
	 * Unfollow from email
	 * If we're logged in, we can send them right to the normal follow form.
	 * Otherwise, they get a special guest page using the gkey as an authentication key.
	 *
	 * @return void
	 */
	protected function unfollowFromEmail() : void
	{		
		/* Logged in? */
		if ( Member::loggedIn()->member_id and ! isset( Request::i()->listunsubscribe ) )
		{
			/* Go to the normal page */
			Output::i()->redirect( Url::internal( "app=core&module=system&controller=notifications&do=follow&follow_app=". Request::i()->follow_app ."&follow_area=". Request::i()->follow_area ."&follow_id=" . Request::i()->follow_id ) );
		}
		
		if ( ! empty( Request::i()->gkey ) )
		{
			[ $followKey, $memberKey ] = explode( '-', Request::i()->gkey );
			/* Do we follow it? */
			try
			{
				$current = Db::i()->select( '*', 'core_follow', array( 'MD5( CONCAT_WS( \';\', follow_app, follow_area, follow_rel_id, follow_member_id, follow_added ) )=?', $followKey ) )->first();

				/* Already no subs? */
				if ( $current['follow_notify_freq'] === 'none' )
				{
					Output::i()->error( 'follow_guest_not_notified', '2C154/C', 404, '' );
				}
				
				$member = Member::load( $current['follow_member_id'] );
				
				if ( md5( $member->email . ';' . $member->ip_address . ';' . $member->joined->getTimestamp() ) != $memberKey )
				{
					throw new Exception;
				}
				
				if( !array_key_exists( Request::i()->follow_app, Application::applications() ) )
				{
					throw new Exception;
				}

                $class = Follow::getClassToFollow( Request::i()->follow_app, Request::i()->follow_area );
				if ( !class_exists( $class ) )
				{
					throw new Exception;
				}
				
				/* Get thing */
				$thing = NULL;
				
				if ( in_array( 'IPS\Node\Model', class_parents( $class ) ) )
				{
					$classname = $class::$contentItemClass;

					/* @var Model $containerClass */
					$containerClass = $class;
					$thing = $containerClass::load( (int) Request::i()->follow_id );
					$title = $thing->_title;
				}
				else if ( $class == "IPS\Member\Club" )
				{
					$thing = $class::load( (int) Request::i()->follow_id );
					$title = $thing->_title;
				}
				else if ( $class != "IPS\Member" )
				{
					$thing = $class::load( (int) Request::i()->follow_id );
					$title = $thing->mapped('title');
				}
				else
				{
					$thing = $class::load( (int) Request::i()->follow_id );
					$title = $thing->name;
				}
				
				/* Grab a count */
				$count = Db::i()->select( 'COUNT(*)', 'core_follow', array( 'follow_member_id=? and follow_notify_freq != ?', $member->member_id, 'none' ) )->first();

				if ( isset( Request::i()->listunsubscribe ) and Request::i()->requestMethod() == 'POST' )
				{
					/* Unsubscribe them now as it's come from clicking the list-unsubscribe header in the email which
					   contains List-Unsubscribe-Post:List-Unsubscribe=One-Click which sends via POST to prevent links being crawled and activated */
                    $thing->follow( 'none', !$current['follow_is_anon'], $member );

					/* Done */
					Output::i()->output = Member::loggedIn()->language()->addToStack('unsubscribed');
					return;
				}

				$form = new Form( 'unfollowFromEmail', 'update_follow' );
				$form->class = 'ipsForm--vertical ipsForm--notifications-unfollow-email';
				
				if ( $count == 1 )
				{
					$form->add( new Checkbox( 'guest_unfollow_single', 'single', FALSE, array( 'disabled' => true ) ) );
					Member::loggedIn()->language()->words['guest_unfollow_single'] = Member::loggedIn()->language()->addToStack('follow_guest_unfollow_thing', FALSE, array( 'sprintf' => array( $title ) ) );
				}
				else
				{
					$form->add( new Radio( 'guest_unfollow_choice', 'single', FALSE, array(
						'options'      => array(
							'single'   => Member::loggedIn()->language()->addToStack('follow_guest_unfollow_thing', FALSE, array( 'sprintf' => array( $title ) ) ),
							'all'	   => Member::loggedIn()->language()->addToStack('follow_guest_unfollow_all', FALSE, array( 'pluralize' => array( $count ) ) ),
						),
						'descriptions' => array(
							'single' => Member::loggedIn()->language()->addToStack('follow_guest_unfollow_thing_desc'),
							'all'	 => Member::loggedIn()->language()->addToStack('follow_guest_unfollow_all_desc', FALSE, array( 'sprintf' => array( base64_encode( Url::internal( "app=core&module=system&controller=followed" ) ) ) ) )
						)
					) ) );
				}
				
				if ( $values = $form->values() )
				{
					if ( $values['guest_unfollow_choice'] == 'single' or isset( $values['guest_unfollow_single'] ) )
					{
                        $thing->follow( 'none', !$current['follow_is_anon'], $member );
					}
					else
					{
						Db::i()->update( 'core_follow', array( 'follow_notify_freq' => 'none' ), array( 'follow_member_id=?', $member->member_id ) );
					}
				}
				
				Output::i()->sidebar['enabled'] = FALSE;
				Output::i()->bodyClasses[] = 'ipsLayout_minimal';
				Output::i()->output = Theme::i()->getTemplate( 'system' )->unfollowFromEmail( $title, $member, $form, ! isset( Request::i()->guest_unfollow_choice ) ? FALSE : Request::i()->guest_unfollow_choice );
				Output::i()->title = Member::loggedIn()->language()->addToStack('follow_guest_unfollow_thing', FALSE, array( 'sprintf' => array( $title ) ) );
			}
			catch ( Exception $e )
			{
				Output::i()->error( 'follow_guest_key_not_found', '2C154/B', 404, '' );
			}
		}
	}

	/**
	 * Follow button
	 *
	 * @return	void
	 */
	protected function button() : void
	{
		/* Get class */
		if( Request::i()->follow_app == 'core' and Request::i()->follow_area == 'member' )
		{
			$class = 'IPS\\Member';
		}
		elseif( Request::i()->follow_app == 'core' and Request::i()->follow_area == 'club' )
		{
			$class = 'IPS\\Member\Club';
		}
		elseif( Request::i()->follow_app == 'core' and Request::i()->follow_area == 'tag' )
		{
			$class = 'IPS\Content\Tag';
		}
		else
		{
			$class = 'IPS\\' . Request::i()->follow_app . '\\' . IPS::mb_ucfirst( Request::i()->follow_area );
		}
		
		if ( !class_exists( $class ) or !array_key_exists( Request::i()->follow_app, Application::applications() ) )
		{
			Output::i()->error( 'node_error', '2C154/5', 404, '' );
		}
		
		/* Get thing */
		$thing = NULL;
		try
		{
			if ( in_array( 'IPS\Node\Model', class_parents( $class ) ) and $class != 'IPS\Content\Tag' )
			{
				$classname = $class::$contentItemClass;
				$containerClass = $class;
				$thing = $containerClass::loadAndCheckPerms( (int) Request::i()->follow_id );
				$count = $classname::containerFollowerCount( $thing );
			}
			else if ( $class != "IPS\Member" )
			{
				$thing = $class::loadAndCheckPerms( (int) Request::i()->follow_id );
				$count = $thing->followersCount();
			}
			else
			{
				if( !IPS::classUsesTrait( $class, 'IPS\Content\Followable' ) AND $class != "IPS\Member" )
				{
					Output::i()->error( 'node_error', '2C154/J', 404, '' );
				}
					
				$thing = $class::load( (int) Request::i()->follow_id );
				$count = $thing->followersCount();
			}
		}
		catch ( OutOfRangeException $e )
		{
			Output::i()->error( 'node_error', '2C154/6', 404, '' );
		}

		if ( Request::i()->follow_area == 'member' && ( !isset( Request::i()->button_type ) || Request::i()->button_type === 'search' ) )
		{
			$size = isset( Request::i()->button_size ) ? Request::i()->button_size : 'normal';
			if ( isset( Request::i()->button_type ) && Request::i()->button_type === 'search' )
			{
				Output::i()->sendOutput( Theme::i()->getTemplate( 'profile' )->memberSearchFollowButton( Request::i()->follow_app, Request::i()->follow_area, Request::i()->follow_id, $count, $size ) );
			}
			else
			{
				Output::i()->sendOutput( Theme::i()->getTemplate( 'profile' )->memberFollowButton( Request::i()->follow_app, Request::i()->follow_area, Request::i()->follow_id, $count, $size ) );
			}			
		}
		else
		{
			if ( Request::i()->button_type == 'manage' )
			{
				Output::i()->sendOutput( Theme::i()->getTemplate( 'system' )->manageFollowButton( Request::i()->follow_app, Request::i()->follow_area, Request::i()->follow_id ) );
			}
			else
			{
				Output::i()->sendOutput( Theme::i()->getTemplate( 'global' )->followButton( Request::i()->follow_app, Request::i()->follow_area, Request::i()->follow_id, $count ) );
			}			
		}
	}

	/**
	 * Remove all followers
	 *
	 * @return	void
	 */
	protected function removeFollowers() : void
	{
		Session::i()->csrfCheck();

		if ( !Member::loggedIn()->modPermission('can_remove_followers') )
		{
			Output::i()->error( 'cant_remove_followers', '2C154/A', 403, 'cant_remove_followers_admin' );
		}

		Db::i()->delete( 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_rel_id=?', Request::i()->follow_app, Request::i()->follow_area, Request::i()->follow_id ) );
        Db::i()->delete( 'core_follow_count_cache', array( 'id=? AND class=?', Request::i()->follow_id, 'IPS\\' . Request::i()->follow_app . '\\' . IPS::mb_ucfirst( Request::i()->follow_area ) ) );
        
        if( Request::i()->follow_area == 'club' )
		{
			try
			{
				$club = Club::load( Request::i()->follow_id );
				foreach ( $club->nodes() as $node )
				{
					$itemClass = $node['node_class']::$contentItemClass;
					$followApp = $itemClass::$application;
					$followArea = mb_strtolower( mb_substr( $node['node_class'], mb_strrpos( $node['node_class'], '\\' ) + 1 ) );
					
					Db::i()->delete( 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_rel_id=?', $followApp, $followArea, $node['node_id'] ) );
				}
			}
			catch ( OutOfRangeException $e ) { }
		}

		Session::i()->modLog( 'modlog__item_follow_removed', array( Request::i()->follow_app => FALSE, Request::i()->follow_area=> FALSE, Request::i()->follow_id => FALSE ) );
		
		Output::i()->redirect( Request::i()->referrer() ?: Url::internal( '' ), 'followers_removed' );
	}

	/**
	 * Retrieve notification data and return it to the service worker
	 *
	 * @return void
	 */
	protected function fetchNotification() : void
	{
		/* Got the ID? */
		if( !Request::i()->id )
		{
			Output::i()->json( array( 'error' => 'missing_id' ), 404 );
		}

		/* Got the notification? */
		try
		{
			$notification = Db::i()->select( '*', 'core_notifications_pwa_queue', array( 'id=?', Request::i()->id ) )->first();
		}
		catch( UnderflowException $e )
		{
			Output::i()->json( array( 'error' => 'not_found' ), 404 );
		}

		$data = json_decode( $notification['notification_data'], true );

		if ( ! is_array( $data ) )
		{
			Output::i()->json( array( 'error' => 'invalid_data' ), 400 );
		}

		$data['unreadCount'] = Member::loggedIn()->notification_cnt;

		if( !isset( $data['member'] ) OR $data['member'] != Member::loggedIn()->member_id )
		{
			Output::i()->json( array( 'error' => 'no_permission' ), 403 );
		}

		/* Send the data */
		Output::i()->json( $data );
	}
}