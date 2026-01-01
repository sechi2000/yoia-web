<?php

/**
 * @brief        Mailchimp
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        8/13/2025
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Http\Request\Exception as RequestException;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use IPS\Settings;
use function str_replace;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

class Mailchimp
{
    /**
     * @var string
     */
    protected string $apiKey = '';

    /**
     * @var string
     */
    protected string $prefix = '';

    /**
     * @var string
     */
    protected static string $apiEndpoint = 'https://<prefix>.api.mailchimp.com/3.0';

    public function __construct()
    {
        /* Are we testing a new API key? */
        $this->apiKey = Settings::i()->mailchimp_api_key;
        $this->prefix = Settings::i()->mailchimp_server_prefix;
    }

    /**
     * @var Mailchimp|null
     */
    protected static ?Mailchimp $instance = null;

    /**
     * @return static
     */
    public static function i() : static
    {
        if( static::$instance === null )
        {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Test the connection
     *
     * @param string|null $apiKey
     * @param string|null $prefix
     * @return bool
     */
    public function test( ?string $apiKey=null, ?string $prefix=null ) : bool
    {
        /* Are we overriding the settings? */
        if( $apiKey !== null )
        {
            $this->apiKey = $apiKey;
        }
        if( $prefix !== null )
        {
            $this->prefix = $prefix;
        }

        if( $result = $this->makeRequest( 'ping', [], 'GET' ) )
        {
            if( isset( $result['health_status'] ) )
            {
                return true;
            }

            throw new DomainException( $result['title'] );
        }

        throw new DomainException;
    }

    /**
     * Return all available lists
     *
     * @return array
     */
    public function getLists() : array
    {
        $lists = [];
        if( $result = $this->makeRequest( "lists", [], 'GET' ) )
        {
            if( !empty( $result['lists'] ) )
            {
                foreach( $result['lists'] as $list )
                {
                    $lists[ $list['id'] ] = $list['name'];
                }
            }
        }

        return $lists;
    }

    /**
     * Subscribe a member to a list
     *
     * @param Member|null $member
     * @param bool $subscribe
     * @return bool
     */
    public function subscribeMember( ?Member $member=null, bool $subscribe=true ) : bool
    {
        if( !$this->getListId() )
        {
            return false;
        }

        $member = $member ?? Member::loggedIn();

        /* Did we unsubscribe emails? */
        if( !$member->allow_admin_mails and $subscribe )
        {
            return false;
        }

        if( $result = $this->makeRequest( "lists/" . $this->getListId(), [
            'members' => [
                [
                    'email_address' => $member->email,
                    'status' => ( $subscribe ? 'subscribed' : 'unsubscribed' )
                ]
            ],
            'update_existing' => true
        ] ) )
        {
            /* Did we have any errors? */
            if( !empty( $result['errors'] ) )
            {
                /* Make sure it's for this email address */
                foreach( $result['errors'] as $error )
                {
                    if( $error['email_address'] == $member->email )
                    {
                        $member->logHistory( 'core', 'mailchimp_subscribed', [
                            'error_code' => $error['error_code'],
                            'error_message' => $error['error']
                        ]);
                        return false;
                    }
                }
            }

            /* Log a successful subscription */
            $member->logHistory( 'core', 'mailchimp_subscribed', [ 'list' => $this->getListName(), 'action' => ( $subscribe ? 'subscribed' : 'unsubscribed' ) ] );
            return true;
        }

        return false;
    }

    /**
     * Update a member's email address
     *
     * @param Member|null $member
     * @param string $oldEmail
     * @return bool
     */
    public function updateMemberEmail( ?Member $member , string $oldEmail ) : bool
    {
        if( !$this->getListId() )
        {
            return false;
        }

        /* Did we unsubscribe emails? */
        if( !$member->allow_admin_mails )
        {
            return false;
        }

        if( $result = $this->makeRequest( "lists/" . $this->getListId() . "/members/" . $this->subscriberHash( $oldEmail ), [
            'email_address' => $member->email,
            'status_if_new' => 'subscribed'
        ], 'PUT' ) )
        {
            /* Did we have any errors? */
            if( !empty( $result['errors'] ) )
            {
                /* Make sure it's for either the old or new email address */
                foreach( $result['errors'] as $error )
                {
                    if( $error['email_address'] == $member->email or $error['email_address'] == $oldEmail )
                    {
                        $member->logHistory( 'core', 'mailchimp_subscribed', [
                            'error_code' => $error['error_code'],
                            'error_message' => $error['error']
                        ]);
                        return false;
                    }
                }
            }

            /* Log a successful subscription */
            $member->logHistory( 'core', 'mailchimp_subscribed', [ 'list' => $this->getListName(), 'action' => 'email_update' ] );
            return true;
        }

        return false;
    }

    /**
     * Return the ID of the list to which we are subscribing
     *
     * @return string
     */
    protected function getListId() : string
    {
        if( $value = json_decode( Settings::i()->mailchimp_lists, true ) )
        {
            return $value['id'];
        }

        return '';
    }

    /**
     * Return the name of the list to which we are subscribing
     *
     * @return string
     */
    protected function getListName() : string
    {
        if( $value = json_decode( Settings::i()->mailchimp_lists, true ) )
        {
            return $value['name'];
        }

        return '';
    }

    /**
     * Generate a hash that mailchimp will recognize
     *
     * @param string $email
     * @return string
     */
    protected function subscriberHash( string $email ) : string
    {
        return md5( strtolower( $email ) );
    }

    /**
     * Make an API request to Mailchimp
     *
     * @param string $endpoint
     * @param array $params
     * @param string $method
     * @return array|null
     */
    protected function makeRequest( string $endpoint, array $params=array(), string $method='POST' ) : ?array
    {
        /* Make sure we have an API key */
        if( empty( $this->apiKey ) or empty( $this->prefix ) )
        {
            return null;
        }

        $method = strtolower( $method );
        $url = Url::external( str_replace( "<prefix>", $this->prefix, static::$apiEndpoint ) . '/' . ltrim( $endpoint, '/' ) );
        if( $method == 'get' and !empty( $params ) )
        {
            $url = $url->setQueryString( $params );
            $params = null;
        }

        try
        {
            $response = $url->request()
                ->login( 'apiKey', $this->apiKey )
                ->setHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])->$method( !empty( $params ) ? json_encode( $params ) : null );

            return $response->decodeJson();
        }
        catch( RequestException $e )
        {
            Log::log( $e->getMessage(), 'mailchimp' );
            return null;
        }
    }
}