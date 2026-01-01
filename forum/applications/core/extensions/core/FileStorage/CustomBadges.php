<?php
/**
 * @brief		File Storage Extension: CustomBadges
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		10 Mar 2025
 */

namespace IPS\core\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\FileStorageAbstract;
use IPS\File as SystemFile;
use OutOfRangeException;
use UnderflowException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: CustomBadges
 */
class CustomBadges extends FileStorageAbstract
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count(): int
	{
		return (int) Db::i()->select( "count(*)", 'core_custom_badges' )->first();
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	UnderflowException					When file record doesn't exist. Indicating there are no more files to move
	 * @return	void
	 */
	public function move( int $offset, int $storageConfiguration, int $oldConfiguration=NULL ) : void
	{
		$row = Db::i()->select( '*', 'core_custom_badges', null, 'id', [ $offset, 1 ] )->first();
		try
		{
			$file = SystemFile::get( $oldConfiguration ?: 'core_CustomBadges', $row['file'] )->move( $storageConfiguration );
			if( (string) $file != $row['file'] )
			{
				Db::i()->update( 'core_custom_badges', [ 'file' => (string) $file ], [ 'id=?', $row['id'] ] );
			}
		}
		catch( \Exception ){}
	}

	/**
	 * Check if a file is valid
	 *
	 * @param	SystemFile|string	$file		The file path to check
	 * @return	bool
	 */
	public function isValidFile( SystemFile|string $file ): bool
	{
		return (bool) Db::i()->select( 'count(*)', 'core_custom_badges', [ 'file=?', (string) $file ] )->first();
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete() : void
	{
		foreach( Db::i()->select( '*', 'core_custom_badges' ) as $row )
		{
			try
			{
				SystemFile::get( 'core_CustomBadges', $row['file'] )->delete();
			}
			catch( OutOfRangeException ){}
		}
	}
}