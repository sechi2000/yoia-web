<?php

use IPS\Output;
use IPS\Output\Javascript;
use IPS\Request;

define('REPORT_EXCEPTIONS', TRUE);
require_once '../../../../init.php';

$content = <<<JS
;(() => {
    "use strict";
    Debug.log( "No component supplied in the request." );
})();
JS;


if ( isset( Request::i()->component ) )
{
	try
	{
		$content = Javascript::generateWebComponent( Request::i()->component );
	}
	catch ( Exception $e )
	{
		$encodedComponent = json_encode( Request::i()->component );
		$content = <<<JS
;(() => {
    "use strict";
    Debug.log( "The source file for the component " + {$encodedComponent} + " could not be found." );
})();
JS;
	}
}

$cacheHeaders	= ( \IPS\IN_DEV ) ? Output::getNoCacheHeaders() : Output::getCacheHeaders( time(), 360 );

Output::i()->sendOutput( $content, 200, 'text/javascript', $cacheHeaders );
