<?php
/**
 * @brief		Community Enhancements
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		23 Jan 2025
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Email\Outgoing\Postmark as PostmarkHandler;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member as Member;
use IPS\Output;
use IPS\Theme;
use IPS\Session;
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
class Postmark extends CommunityEnhancementsAbstract
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
	public string $icon	= "postmark.png";
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = !empty( Settings::i()->postmark_server_api_key );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		$form = new Form;
		$form->addHeader('postmark_settings');
		$form->add( new Password( 'postmark_server_api_key', Settings::i()->postmark_server_api_key, TRUE, suffix: Member::loggedIn()->language()->addToStack( 'postmark_server_api_key_suffix' ) ) );
		$form->add( new YesNo( 'postmark_track_opens', Settings::i()->postmark_track_opens, FALSE ) );

		if( !empty( Settings::i()->postmark_server_api_key ) )
		{
			$postmark = new PostmarkHandler( Settings::i()->postmark_server_api_key );
			$currentSetting = json_decode( Settings::i()->postmark_streams, TRUE );
			$streams = $descriptions = [];
			foreach( $postmark->api('message-streams')['MessageStreams'] as $stream )
			{
				if( $stream['MessageStreamType'] == 'Inbound' ) continue;
				$streams[ $stream['ID'] ] = $stream['Name'];
				$descriptions[ $stream['ID'] ] = '<br>'.$stream['Description'];
			}

			$form->addHeader('postmark_message_streams');
			$form->addMessage( 'postmark_message_description');
			$form->addMessage( 'postmark_stream_choose');
			$form->add( new Form\Radio( 'postmark_transactional_stream', $currentSetting['transactional'], true, [ 'options' => $streams, 'descriptions' => $descriptions, 'parse' => 'normal' ] ) );
			$form->add( new Form\Radio( 'postmark_bulk_stream', $currentSetting['bulk'], true, [ 'options' => $streams, 'descriptions' => $descriptions, 'parse' => 'normal' ] ) );
		}

		if ( $values = $form->values() )
		{
			try
			{
				$this->testSettings( $values );
			}
			catch ( \Exception $e )
			{
				Output::i()->error( $e->getMessage(), '2C438/1', 500 );
			}

			if( isset( $values['postmark_bulk_stream'] ) )
			{
				$values['postmark_streams'] = json_encode( [ 'bulk' => $values['postmark_bulk_stream'], 'transactional' => $values['postmark_transactional_stream'] ] );
				unset( $values['postmark_bulk_stream'], $values['postmark_transactional_stream'] );
			}

			$form->saveAsSettings( $values );
			Session::i()->log( 'acplog__enhancements_edited', array( 'enhancements__core_Postmark' => TRUE ) );
			Output::i()->inlineMessage = Member::loggedIn()->language()->addToStack( 'saved' );
		}

		Output::i()->sidebar['actions'] = array(
			'help' => array(
				'title' => 'learn_more',
				'icon' => 'question-circle',
				'link' => Url::ips( 'docs/postmark_api_key' ),
				'target' => '_blank'
			),
		);

		Output::i()->output = $form;
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 * @throws	DomainException
	 */
	public function toggle( bool $enabled ) : void
	{
		/* If we're disabling, just disable */
		if ( !$enabled )
		{
			Settings::i()->changeValues( array( 'postmark_server_api_key' => '' ) );
		}
		else
		{
			/* We need an API key. */
			throw new DomainException;
		}
	}
	
	/**
	 * Test Settings
	 *
	 * @return	void
	 * @throws	DomainException
	 */
	protected function testSettings( array $values ) : void
	{
		/* Test Postmark settings */
		$postmark = new PostmarkHandler( $values['postmark_server_api_key'] );
		$server = $postmark->api('server');

		if( $server === null OR !empty( $server['ErrorCode'] ) )
		{
			throw new DomainException( Member::loggedIn()->language()->addToStack( 'postmark_api_error', false, [ 'sprintf' => [ (string) $server['ErrorCode'], $server['Message'] ] ] ) );
		}
	}
}