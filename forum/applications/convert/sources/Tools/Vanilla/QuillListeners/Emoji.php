<?php

namespace IPS\convert\Tools\Vanilla\QuillListeners;

use nadar\quill\InlineListener;
use nadar\quill\Line;

class Emoji extends InlineListener
{
    /**
     * {@inheritDoc}
     */
    public function process(Line $line)
    {
        $emoji = $line->insertJsonKey('emoji');
        if ( !empty( $emoji['emojiChar'] ) ) {
            $this->updateInput( $line, $emoji['emojiChar'] );
        }
    }
}