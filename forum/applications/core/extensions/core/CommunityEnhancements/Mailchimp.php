<?php
/**
 * @brief		Community Enhancements
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		12 Aug 2025
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\core\Mailchimp as MailchimpClass;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Wizard;
use IPS\Http\Url;
use IPS\Member as MemberClass;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use LogicException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement
 */
class Mailchimp extends CommunityEnhancementsAbstract
{
	/**
	 * @brief	Enhancement is enabled?
	 */
	public bool $enabled	= FALSE;

	/**
	 * @brief	IPS-provided enhancement?
	 */
	public bool $ips	= FALSE;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public bool $hasOptions	= TRUE;

	/**
	 * @brief	Icon data
	 */
	public string $icon	= "mailchimp.png";
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
        $this->enabled = ( Settings::i()->mailchimp_enabled and Settings::i()->mailchimp_api_key and Settings::i()->mailchimp_server_prefix );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		$steps = [];
        if( empty( Settings::i()->mailchimp_api_key ) or empty( Settings::i()->mailchimp_server_prefix ) or isset( Request::i()->change ) )
        {
            $steps[ 'mailchimp__apiKey' ] = function()
            {
                $form = new Form( 'mailchimpApiForm', 'continue' );
                $form->add( new Text( 'mailchimp_api_key', Settings::i()->mailchimp_api_key, true ) );
                $form->add( new Text( 'mailchimp_server_prefix', Settings::i()->mailchimp_server_prefix, true ) );
                if( $values = $form->values() )
                {
                    try
                    {
						if ( preg_match('#https?://(us\d+)\.admin\.mailchimp\.com#i', $values['mailchimp_server_prefix'], $matches ) )
						{
							/* A common mistake is to enter the full URL, so we extract the prefix */
							$values['mailchimp_server_prefix'] = strtolower( $matches[1] );
						}

                        MailchimpClass::i()->test( $values['mailchimp_api_key'], $values['mailchimp_server_prefix'] );
                        $form->saveAsSettings( $values );
                        return $values;
                    }
                    catch( DomainException $e )
                    {
                        $form->error = $e->getMessage();
                    }
                }

                return (string) $form;
            };
        }
        else
        {
            Output::i()->sidebar['actions']['api'] = [
                'title' => 'mailchimp_change_key',
                'link' => Url::internal( "app=core&module=applications&controller=enhancements&do=edit&id=core_Mailchimp&change=1" ),
                'icon' => 'cog'
            ];
        }

        $steps['mailchimp__list'] = function()
        {
            $list = Settings::i()->mailchimp_lists ? json_decode( Settings::i()->mailchimp_lists, true ) : [];
            $form = new Form( 'mailchimpList', 'save' );

            $lists = MailchimpClass::i()->getLists();
            $form->add( new Select( 'mailchimp_lists', $list['id'] ?? null, true, [
                'options' => $lists
            ] ) );
            if( $values = $form->values() )
            {
                $form->saveAsSettings( [
                    'mailchimp_lists' => json_encode( [ 'id' => $values['mailchimp_lists'], 'name' => $lists[ $values['mailchimp_lists'] ] ] ),
                    'mailchimp_enabled' => true
                ]);

                Output::i()->redirect( Url::internal( "app=core&module=applications&controller=enhancements" ), MemberClass::loggedIn()->language()->addToStack( 'saved' ) );
            }

            return (string) $form;
        };
		
		Output::i()->output = new Wizard( $steps, Url::internal( "app=core&module=applications&controller=enhancements&do=edit&id=core_Mailchimp" ), count( $steps ) > 1 );
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 * @throws	LogicException
	 */
	public function toggle( bool $enabled ) : void
	{
        /* If we're disabling, just disable */
        if( !$enabled )
        {
            Settings::i()->changeValues( array( 'mailchimp_enabled' => 0 ) );
        }
        else
        {
            if( Settings::i()->mailchimp_api_key and Settings::i()->mailchimp_server_prefix )
            {
                Settings::i()->changeValues( array( 'mailchimp_enabled' => 1 ) );

                /* Test the connection, this will throw a Domain exception if it fails, which will take us back to the settings page */
                MailchimpClass::i()->test();
            }
            else
            {
                /* Otherwise we need to let them enter an API key before we can enable.  Throwing an exception causes you to be redirected to the settings page. */
                throw new DomainException;
            }
        }
	}
}