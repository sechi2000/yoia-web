<?php
/**
 * @brief		MemberHistory: Mailchimp
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		13 Aug 2025
 */

namespace IPS\core\extensions\core\MemberHistory;

use IPS\Extensions\MemberHistoryAbstract;
use IPS\Member;
use IPS\Theme;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member History: Mailchimp
 */
class Mailchimp extends MemberHistoryAbstract
{
	/**
	 * Return the valid member history log types
	 *
	 * @return array
	 */
	public function getTypes(): array
	{
		return array(
            'mailchimp_subscribed'
        );
	}

	/**
	 * Parse LogType column
	 *
	 * @param	string		$value		column value
	 * @param	array		$row		entire log row
	 * @return	string
	 */
	public function parseLogType( string $value, array $row ): string
	{
		return Theme::i()->getTemplate( 'members', 'core', 'admin' )->logType( 'email_change' );
	}

	/**
	 * Parse LogData column
	 *
	 * @param	string		$value		column value
	 * @param	array		$row		entire log row
	 * @return	string
	 */
	public function parseLogData( string $value, array $row ): string
	{
        $jsonValue = json_decode( $value, TRUE );

        if( isset( $jsonValue['error_code'] ) )
        {
            return Member::loggedIn()->language()->addToStack( 'history__mailchimp_error', true, [ 'sprintf' => [ $jsonValue['error_code'], $jsonValue['error_message'] ] ] );
        }

        return Member::loggedIn()->language()->addToStack( 'history__mailchimp_' . $jsonValue['action'], true, [ 'sprintf' => [ $jsonValue['list'] ] ] );
	}
}