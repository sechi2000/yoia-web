<?php
/**
 * @brief		Currencies
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		26 Mar 2014
 */

namespace IPS\nexus\modules\admin\payments;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Lang;
use IPS\Member;
use function in_array;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Currencies
 */
class _currencies extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static $csrfProtected = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'currencies_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage()
	{
		$languages = \IPS\Lang::languages();
		
		$matrix = new \IPS\Helpers\Form\Matrix;
		$matrix->squashFields = FALSE; // This has issues with select fields
		$matrix->langPrefix = 'currency_';
		$matrix->columns = array(
			'code'		=> function( $key, $value, $data )
			{
				return new \IPS\Helpers\Form\Text( $key, $value, FALSE, array( 'minLength' => 3, 'maxLength' => 3, 'placeholder' => 'USD' ) );
			},
			'default'	=> function( $key, $value, $data ) use ( $languages )
			{
				if ( \count( $languages ) === 1 )
				{
					return new \IPS\Helpers\Form\Checkbox( $key, $value );
				}
				else
				{
					$options = array();
					foreach ( $languages as $k => $v )
					{
						$options[ $v->id ] = $v->_title;
					}
					
					return new \IPS\Helpers\Form\Select( $key, $value, FALSE, array( 'options' => $options, 'multiple' => TRUE ) );
				}
			}
		);
		
		$warnings = '';
		if ( $currencies = json_decode( \IPS\Settings::i()->nexus_currency, TRUE ) )
		{
			foreach ( $currencies as $code => $defaults )
			{
				$matrix->rows[] = array(
					'code'		=> $code,
					'default'	=> $defaults
				);
				
				if ( !in_array( $code, array( 'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BTN', 'BWP', 'BYN', 'BYR', 'BZD', 'CAD', 'CDF', 'CHF', 'CLF', 'CLP', 'CNY', 'COP', 'CRC', 'CUC', 'CUP', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ERN', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GGP', 'GHS', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'IMP', 'INR', 'IQD', 'IRR', 'ISK', 'JEP', 'JMD', 'JOD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KPW', 'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'LYD', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRO', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SDG', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SPL*', 'SRD', 'STD', 'SVC', 'SYP', 'SZL', 'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY', 'TTD', 'TVD', 'TWD', 'TZS', 'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VEF', 'VND', 'VUV', 'WST', 'XAF', 'XCD', 'XDR', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW', 'ZWD' ) ) )
				{
					$warnings .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->message( \IPS\Member::loggedIn()->language()->addToStack( 'currency_code_invalid', FALSE, array( 'sprintf' => array( $code ) ) ), 'warning' );
				}
			}
		}
		else
		{
			$matrix->rows[] = array(
				'code'		=> \IPS\Settings::i()->nexus_currency,
				'default'	=> \count( $languages ) === 1 ? TRUE : array_keys( $languages )
			);
		}

		$currencies = json_decode( \IPS\Settings::i()->nexus_currency, true );
		if( empty( $currencies ) )
		{
			$currencies = \IPS\Settings::i()->nexus_currency;
		}
		$errors = $this->_checkCurrencies( $currencies );

		if ( $values = $matrix->values() )
		{
			$save = array();

			$definition = \IPS\Db::i()->getTableDefinition( 'nexus_package_base_prices' );

			foreach ( $values as $data )
			{
				if ( $data['code'] )
				{
					$save[ $data['code'] ] = ( \count( $languages ) === 1 ) ? ( $data['default'] ? array_keys( $languages ) : array() ) : $data['default'];

					/* Add the column if it doesn't exist */
					if ( !isset( $definition['columns'][ $data['code'] ] ) )
					{
						\IPS\Db::i()->addColumn( 'nexus_package_base_prices', array(
							'name'	=> $data['code'],
							'type'	=> 'FLOAT'
						) );
					}
				}
			}

			/* Sanity check the final result */
			$errors = $this->_checkCurrencies( $save );
			if( !$errors )
			{
				\IPS\Settings::i()->changeValues( array( 'nexus_currency' => json_encode( $save ) ) );

				foreach ( $definition['columns'] AS $key => $value )
				{
					if ( $key === 'id' )
					{
						continue;
					}

					if ( !isset( $save[$key] ) )
					{
						\IPS\Db::i()->dropColumn( 'nexus_package_base_prices', $key );
					}
				}

				\IPS\Session::i()->log( 'acplogs__currencies' );

				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=payments&controller=paymentsettings&tab=currencies' ) );
			}
			else
			{
				/* Clear the elements from the matrix so that they are forced to rebuild.
				Otherwise, it hides the last row in the table */
				$matrix->elements = null;
			}
		}

		\IPS\Output::i()->output = $errors . $matrix . $warnings;
	}

	/**
	 * Sanity check on currency settings
	 *
	 * @param array|string $values
	 * @return string
	 */
	protected function _checkCurrencies( array|string $values ) : string
	{
		$errors = '';

		/* If we have a string here, then we're good to go, we only have one currency */
		if( \is_string( $values ) )
		{
			return $errors;
		}

		$languages = Lang::languages();

		foreach( array_keys( $languages ) as $langId )
		{
			$langUsed = 0;
			foreach( $values as $code => $defaults )
			{
				if( in_array( $langId, $defaults ) )
				{
					$langUsed++;
				}
			}

			/* If the language doesn't have a default, throw an error */
			if( !$langUsed )
			{
				$errors .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->message( \IPS\Member::loggedIn()->language()->addToStack( 'currency_code_lang_missing', FALSE, array( 'sprintf' => array( $languages[ $langId ]->_title ) ) ), 'error' );
			}

			/* A language can't have more than one default */
			if( $langUsed > 1 )
			{
				$errors .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->message( \IPS\Member::loggedIn()->language()->addToStack( 'currency_code_multiple_default', false, array( 'sprintf' => array( $languages[ $langId ]->_title ) ) ), 'error' );
			}
		}

		return $errors;
	}
}