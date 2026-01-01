<?php
/**
 * @brief		Account Settings Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @subpackage	Converter
 * @since		14 Mar 2025
 */

namespace IPS\convert\extensions\core\LoginHandler;

use IPS\convert\Login as ConverterLogin;
use IPS\Login;
use IPS\Login\Exception;
use IPS\Login\Handler;
use IPS\Login\Handler\ButtonHandler;
use IPS\Login\Handler\UsernamePasswordHandler;
use IPS\Member as MemberClass;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * AccountSettings Extension
 */
class Converter extends ConverterLogin
{
	public static bool $allowMultiple = false;
}