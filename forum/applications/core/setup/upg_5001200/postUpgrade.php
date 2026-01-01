<?php

/* Look for any clubs custom fields that do not have permissions configured.
Inform the Admin that these need to be checked. */
$fields = iterator_to_array(
	\IPS\Db::i()->select( 'f_id', 'core_clubs_fields', [ 'f_id not in (?)', \IPS\Db::i()->select( 'perm_type_id', 'core_permission_index', [ 'app=? and perm_type=?', 'core', 'clubfields' ] ) ] )
);

if( \count( $fields ) )
{
	$message = "The following Club Custom Fields have no permissions configured and should be reviewed:";
	foreach( $fields as $field )
	{
		$message .= "<br>" . \IPS\Member::loggedIn()->language()->get( 'core_clubfield_' . $field );
	}

	$message = \IPS\Theme::i()->getTemplate( 'global' )->block( NULL, $message );
}