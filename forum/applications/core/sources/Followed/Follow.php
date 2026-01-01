<?php
/**
 * @brief		Follow Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Nov 2017
 * @todo		Adjust follow code over time to use this class instead
 */

namespace IPS\core\Followed;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Api\Webhook;
use IPS\Application;
use IPS\Content\Tag;
use IPS\DateTime;
use IPS\Db;
use IPS\Events\Event;
use IPS\IPS;
use IPS\Member;
use IPS\Member\Club;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecord;
use OutOfRangeException;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Follow Model
 */
class Follow extends ActiveRecord
{
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_follow';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'follow_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();

    /**
     * Set Default Values (overriding $defaultValues)
     *
     * @return	void
     */
    protected function setDefaultValues() : void
    {
        $this->notify_meta = '';
        $this->visible = 1;
    }

	/**
	 * Save
	 *
	 * @return    void
	 */
	public function save(): void
	{
		$sendWebhook = false;
		
		if( $this->_new )
		{
			$this->added		= time();
			$sendWebhook = true;
		}

		$this->id	= md5( $this->app . ';' . $this->area . ';' . $this->rel_id . ';' . $this->member_id );

		parent::save();

		if ( $sendWebhook )
		{
			Webhook::fire( 'content_followed', $this);
		}

		$this->clearFollowCache();
	}

	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array|null
	 * @apiresponse	int		followKey	Unique key that represents the follow
	 * @apiresponse	string	followApp	The application of the content that was followed
	 * @apiresponse	string	followArea	The area of the content that was followed
	 * @apiresponse	int		followId	The ID of the content that was followed
	 * @apiresponse	bool	followAnon	Flag to indicate if the member is following anonymously
	 * @apiresponse	bool	followNotify	Flag to indicate if notifications should be sent
	 * @apiresponse	string|null	followType	Notification preference for this follow, or null if notifications are not being sent
	 * @apiresponse	datetime|null	followSent	Date and time the last notification was sent, or NULL if none has been sent
	 * @apiresponse	string	followName	Textual representation of the content that was followed (title, name, etc.)
	 * @apiresponse	string	followUrl	URL to the content that was followed
	 * @apiresponse	\IPS\Member	followMember	Member following the item
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array|null
	{
		if( $this->area == 'member' AND $this->app == 'core' )
		{
			$followed	= Member::load( $this->rel_id );
			$name		= $followed->name;
			$url		= $followed->url();
		}
		else if( $this->area == 'club' AND $this->app == 'core')
		{
			$followed	= Club::load( $this->rel_id );
			$name		= $followed->name;
			$url		= $followed->url();
		}
		else
		{
			foreach( Application::load( $this->app )->extensions( 'core', 'ContentRouter' ) as $key => $router )
			{
				foreach( $router->classes as $class )
				{
					$followArea		= mb_strtolower( mb_substr( $class, mb_strrpos( $class, '\\' ) + 1 ) );

					if( $followArea == $this->area AND $class::$application == $this->app )
					{
						try
						{
							$followed	= $class::load( $this->rel_id );
							$name		= $followed->mapped('title');
							$url		= $followed->url();
						}
						catch( OutOfRangeException $e )
						{
							/* If the item doesn't exist we may as well clean up core_follow */
							parent::delete();

							return NULL;
						}
					}
					else
					{
						$containers		= array();

						if( isset( $class::$containerNodeClass ) )
						{
							$containers[ $class::$containerNodeClass ]	= $class::$containerNodeClass;
						}

						if( isset( $class::$containerFollowClasses ) )
						{
							foreach( $class::$containerFollowClasses as $followClass )
							{
								$containers[ $followClass ]	= $followClass;
							}
						}

						foreach( $containers as $container )
						{
							$containerArea	= mb_strtolower( mb_substr( $container, mb_strrpos( $container, '\\' ) + 1 ) );

							if( $containerArea == $this->area AND $class::$application == $this->app )
							{
								try
								{
									$followed	= $container::load( $this->rel_id );
									$name		= $followed->_title;
									$url		= $followed->url();
								}
								catch( OutOfRangeException $e )
								{
									/* If the item doesn't exist we may as well clean up core_follow */
									parent::delete();

									return null;
								}
							}
						}
					}
				}
			}
		}

		return array(
			'followKey'		=> $this->id,
			'followApp'		=> $this->app,
			'followArea'	=> $this->area,
			'followId'		=> $this->rel_id,
			'followAnon'	=> (bool) $this->is_anon,
			'followNotify'	=> (bool) $this->notify_do,
			'followType'	=> $this->notify_do ? $this->notify_freq : NULL,
			'followSent'	=> $this->notify_sent ? DateTime::ts( $this->notify_sent )->rfc3339() : NULL,
			'followName'	=> $name ?? '',
			'followUrl'		=> ( isset( $url ) ? (string) $url : '' ),
			'followMember' => Member::load($this->member_id)->apiOutput($authorizedMember)
		);
	}


	/**
	 * @var array[<string><string>] Special follow classes
	 */
	public static array $specialFollowClasses = [
		'core' => [
			'member' => Member::class,
			'club' => Club::class,
            'tag' => Tag::class
		],
	];

    /**
     * Return the app and area parameters
     *
     * @param object $object
     * @return array
     */
    public static function getFollowParameters( object $object ) : array
    {
        $objectClass = get_class( $object );
        foreach( static::$specialFollowClasses as $app => $areas )
        {
            foreach( $areas as $area => $class )
            {
                if( $class == $objectClass )
                {
                    return [
                        'app' => $app,
                        'area' => $area
                    ];
                }
            }
        }

        $itemClass = ( $object instanceof Model ) ? $object::$contentItemClass : get_class( $object );
        return [
            'app' => $itemClass::$application,
            'area' => mb_strtolower( mb_substr( $objectClass, mb_strrpos( $objectClass, '\\' ) + 1 ) )
        ];
    }

    /**
     * Find a record based on an object and a member
     *
     * @param object $object
     * @param Member|null $member
     * @return static|null
     * @throws OutOfRangeException
     */
    public static function loadByObject( object $object, ?Member $member=null ) : static|null
    {
        $member = $member ?: Member::loggedIn();

        /* Guests can never follow anything */
        if ( !$member->member_id )
        {
            throw new OutOfRangeException;
        }

        $params = static::getFollowParameters( $object );
        $idColumn = $object::$databaseColumnId;
        try
        {
            $row = Db::i()->select( '*', 'core_follow', [ 'follow_app=? and follow_area=? and follow_rel_id=? and follow_member_id=?', $params['app'], $params['area'], $object->$idColumn, $member->member_id ] )->first();
            return static::constructFromData( $row );
        }
        catch( UnderflowException )
        {
            throw new OutOfRangeException;
        }
    }

    /**
     * @return string
     */
    public function get__class() : string
    {
        if( isset( static::$specialFollowClasses[ $this->app ][ $this->area ] ) )
        {
            return static::$specialFollowClasses[ $this->app ][ $this->area ];
        }

        return 'IPS\\' . $this->app . '\\' . IPS::mb_ucfirst( $this->area );
    }

    /**
     * @return ActiveRecord|null
     */
    public function get__item() : ActiveRecord|null
    {
        $class = $this->_class;
        try
        {
            return $class::load( $this->rel_id );
        }
        catch( OutOfRangeException )
        {
            return null;
        }
    }

    /**
     * Set as many properties as we can get from the object
     *
     * @param object $object
     * @return void
     */
    public function set_object( object $object ) : void
    {
        $params = static::getFollowParameters( $object );
        $this->app = $params['app'];
        $this->area = $params['area'];

        $idColumn = $object::$databaseColumnId;
        $this->rel_id = $object->$idColumn;
    }

	/**
	 * Get the class to follow
	 *
	 * @param	string	$app	Application key
	 * @param	string	$area	Area
	 * @return	string
	 * @throws	InvalidArgumentException
	 */
	public static function getClassToFollow(string $app, string $area): string
	{
		if( isset( static::$specialFollowClasses[$app][$area] ) )
		{
			$classToFollow	= static::$specialFollowClasses[$app][$area];
		}
		else
		{
			$classToFollow	= 'IPS\\' . $app . '\\' . IPS::mb_ucfirst( $area );
			if( !class_exists( $classToFollow) or !array_key_exists( $app, Application::applications() ) )
			{
				throw new InvalidArgumentException;
			}
		}
		return $classToFollow;
	}

    /**
     * Delete corresponding record from the follow cache
     *
     * @return void
     */
    protected function clearFollowCache() : void
    {
        Db::i()->delete( 'core_follow_count_cache', array( 'id=? AND class=?', $this->rel_id, $this->_class ) );
    }

	public function delete(): void
	{
        Webhook::fire( 'content_unfollowed', $this );

        Event::fire( 'onUnfollow', Member::load( $this->member_id ), [ $this->_item ] );

		parent::delete();

        /* Delete from the cache */
        $this->clearFollowCache();
	}
}