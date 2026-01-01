<?php

namespace IPS\convert\Tools\Vanilla\QuillListeners;

use IPS\Db;
use nadar\quill\InlineListener;
use nadar\quill\Line;

class EmbedExternal extends InlineListener
{
	private $contentType;
	private $contentId;
	private $commentId;

	public function __construct( ?string $contentType=null, ?int $contentId=null, ?int $commentId=null )
	{
		$this->contentType = $contentType;
		$this->contentId = $contentId;
		$this->commentId = $commentId;
	}

    /**
     * {@inheritDoc}
     */
    public function process(Line $line)
    {
        $embed = $line->insertJsonKey('embed-external');

		if( $embed )
		{
			$return = match( $embed['data']['embedType'] )
			{
				'quote' => $this->processQuote( $embed ),
				'image', 'file', 'imgur', 'giphy' => $this->processMedia( $embed ),
				'youtube', 'twitter', 'instagram', 'twitch', 'vimeo' => $this->processOembed( $embed ),
				'link' => $this->processLink( $embed ),
				default => $this->processMedia( $embed )
			};

			if ( $return !== NULL ) {
				$this->updateInput( $line, $return );
			}
		}
    }

	public function processLink( array $data ):? string
	{
		return ' ' . $data['data']['url'] . " \n";
	}

	public function processMedia( array $data ):? string
	{
		if( isset( $data['data']['mediaID'] ) )
		{
		//	dump($this->contentType, $this->commentId);
			if( $this->contentType AND $this->commentId )
			{
				Db::i()->insert( 'convert_vanilla_temp', [ 'media_id' => $data['data']['mediaID'], 'link_type' => $this->contentType, 'content_id' => $this->contentId, 'post_id' => $this->commentId ] );
			}
			return "[ATTACH={$data['data']['mediaID']}] <br>\n";
		}
		elseif( !empty( $data['data']['url'] ) )
		{
			return "[img]{$data['data']['url']}[/img] <br>\n";
		}
		return null;
	}

	public function processOembed( array $data ):? string
	{
		return $data['data']['url'] . " \n";
	}

	public function processQuote( array $data ):? string
	{
		return '[quote name="' . $data['data']['insertUser']['name'] . '" timestamp="' . strtotime( $data['data']['dateInserted'] ) . '"]' . $data['data']['body'] . "[/quote]";
	}
}