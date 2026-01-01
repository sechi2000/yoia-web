<?php
/**
 * @brief		Community Enhancements
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		23 Oct 2025
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Dispatcher;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Stack;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Member as MemberClass;
use IPS\Output;
use IPS\Theme;
use IPS\Settings;
use LogicException;
use function defined;
use function explode;
use function implode;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement
 */
class Iframely extends CommunityEnhancementsAbstract
{
	/**
	 * @brief	Enhancement is enabled?
	 */
	public bool $enabled	= false;

	/**
	 * @brief	IPS-provided enhancement?
	 */
	public bool $ips	= false;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public bool $hasOptions	= true;

	/**
	 * @brief	Icon data
	 */
	public string $icon	= "iframely.png";
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		foreach ( [ 'iframely_enabled', 'iframely_api_key', 'iframely_url_whitelist' ] as $key )
		{
			$this->_settings[$key] = Settings::i()->$key;
		}
		$this->enabled = (bool) Settings::i()->iframely_enabled;
	}

	/**
	 * The settings that this extension is currently using
	 * @var array
	 */
	protected array $_settings = [];
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		$form = new Form;

		$message = Member::loggedIn()->language()->addToStack( 'enhancements__core_Iframely_desc_full' );
		if ( Dispatcher::i()->checkAcpPermission( "posting_manage_simplified_mode", return: true ) )
		{
			$message = "<p>{$message}</p><p>" . Member::loggedIn()->language()->addToStack( "enhancements__core_Iframely_embed_settings_link" ) . "</p>";
		}
		Member::loggedIn()->language()->parseOutputForDisplay( $message );
		Member::loggedIn()->language()->words['enhancements__core_Iframely_message'] = $message;
		$form->addMessage( 'enhancements__core_Iframely_message' );

		$form->add( new YesNo( 'iframely_enabled', Settings::i()->iframely_enabled, false, ['togglesOn' => ['iframely_api_key', 'iframely_url_whitelist', 'iframely_builtin_services']] ) );
		$form->add( new Text( 'iframely_api_key', Settings::i()->iframely_api_key, false, id: 'iframely_api_key' ) );

		$services = [];
		$enabled = [];
		foreach ( ['iframely_facebook_enabled', 'iframely_instagram_enabled'] as $serviceSetting )
		{
			if ( Settings::i()->$serviceSetting )
			{
				$enabled[] = $serviceSetting;
			}
			$services[$serviceSetting] = $serviceSetting;
		}
		$form->add( new Form\CheckboxSet( "iframely_builtin_services", $enabled, false, [
			'options' => $services
		], id: 'iframely_builtin_services' ) );
		$form->add( new Stack( 'iframely_url_whitelist', explode( ',', Settings::i()->iframely_url_whitelist ?: "" ), false, id: 'iframely_url_whitelist' ) );

		if ( $values = $form->values() )
		{
			try
			{
				$values['iframely_url_whitelist'] = implode( ",", $values['iframely_url_whitelist'] );
				$this->_settings = array_replace( $this->_settings, $values );
				$this->testSettings();
				if ( array_key_exists( 'iframely_builtin_services', $values ) )
				{
					foreach( $services as $setting )
					{
						$values[$setting] = in_array( $setting, $values['iframely_builtin_services'] ) ? 1 : 0;
					}
				}
				unset( $values['iframely_builtin_services'] );
				Settings::i()->changeValues( $values );

				Output::i()->inlineMessage	= MemberClass::loggedIn()->language()->addToStack('saved');
			}
			catch ( LogicException $e )
			{
				$form->error = $e->getMessage();
			}
		}
		
		Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'help',
				'icon'		=> 'question-circle',
				'link'		=> Url::ips( 'docs/iframely' ),
				'target'	=> '_blank'
			),
		);
		
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_Iframely', $form );
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
		if ( $enabled )
		{
			$this->testSettings();
		}
		
		Settings::i()->changeValues( array( 'iframely_enabled' => $enabled ) );
	}
	
	/**
	 * Test Settings
	 *
	 * @return	void
	 * @throws	LogicException
	 */
	protected function testSettings() : void
	{
		/* If this is not enabled, no need to check the api key */
		if ( !@$this->_settings['iframely_enabled'] )
		{
			return;
		}

		if ( empty( $this->_settings['iframely_api_key'] ) )
		{
			throw new LogicException( Member::loggedIn()->language()->get("iframely_err_no_api_key" ) );
		}

		/* We only need to test the key if it's different from the saved value */
		if ( $this->_settings['iframely_api_key'] !== Settings::i()->iframely_api_key )
		{
			try
			{
				$response = Url::external( "https://iframe.ly/api/iframely" )
					->setQueryString([
						'key' => $this->_settings['iframely_api_key'],
						'url' => 'https://iframely.com',
					                 ])
					->request()
					->get();

				if ( $response->httpResponseCode == 403 )
				{
					throw new Exception();
				}
			}
			catch ( Exception )
			{
				throw new LogicException( Member::loggedIn()->language()->get( "iframely_err_invalid_api_key" ) );
			}
		}
	}
}