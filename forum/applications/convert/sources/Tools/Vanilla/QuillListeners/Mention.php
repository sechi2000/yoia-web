<?php

namespace IPS\convert\Tools\Vanilla\QuillListeners;

use IPS\convert\App;
use nadar\quill\InlineListener;
use nadar\quill\Line;

class Mention extends InlineListener
{
	private $app;

	public function __construct( ?App $app=null )
	{
		$this->app = $app;
	}

    /**
     * {@inheritDoc}
     */
    public function process(Line $line)
    {
        $mention = $line->insertJsonKey('mention');
        if ( $this->app AND !empty( $mention['userID'] ) ) {
			
			$ipsId = $this->app->getLink( $mention['userID'], 'core_members', true );
			$return = "[mention={$ipsId}]{$mention['name']}[/mention]";
            $this->updateInput( $line, $return );
        }
        elseif ( $mention )
        {
            $this->updateInput( $line, '@' . $mention['name'] );
        }
    }
}