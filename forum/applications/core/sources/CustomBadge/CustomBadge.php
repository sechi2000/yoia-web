<?php
/**
 * @brief		Custom Badges - Helper class to create styled "badge" svg icons
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		October 2023
 */

namespace IPS\core;

use InvalidArgumentException;
use IPS\File;
use IPS\Http\Url;
use IPS\Patterns\ActiveRecord;
use IPS\Settings;
use IPS\Xml\DOMDocument;
use OutOfRangeException;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class CustomBadge extends ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Database table
	 * @note	This MUST be over-ridden
	 */
	public static ?string $databaseTable	= 'core_custom_badges';

	/**
	 * @brief	[ActiveRecord] Multiton Store
	 * @note	This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
	 */
	protected static array $multitons	= array();

	/**
	 * Set Default Values (overriding $defaultValues)
	 *
	 * @return	void
	 */
	protected function setDefaultValues() : void
	{
		$this->shape = 'circle';
		$this->sides = 5;
		$this->rotation = 0;
		$this->number_overlay = 0;
	}

	/**
	 * @return array
	 */
	public function get_icon() : array
	{
		return isset( $this->_data['icon'] ) ? json_decode( $this->_data['icon'], true ) : [];
	}

	/**
	 * @param mixed $val
	 * @return void
	 */
	public function set_icon( mixed $val ) : void
	{
		$this->_data['icon'] = is_array( $val ) ? json_encode( $val ) : null;
	}

	/**
	 * Get the saved SVG file
	 *
	 * @return File|null
	 */
	public function file() : ?File
	{
		if( $this->file )
		{
			try
			{
				return File::get( 'core_CustomBadges', $this->file );
			}
			catch( OutOfRangeException ){}
		}
		return null;
	}

	/**
	 * @var "square"|"circle"|"ngon"|"star"|"flower"
	 */


	/**
	 * Get a Data URL with the badge svg embedded in it
	 *
	 * @return Url
	 */
	public function url() : Url
	{
		return Url::createFromString( rtrim( Settings::i()->base_url, '/' ) . '/applications/core/interface/icons/icons.php?icon=' . ( $this->id ) . '&v=' . md5( json_encode( $this->raw ) ) );
	}

	/**
	 * Generate the SVG for a badge
	 *
	 * @param bool	$preview
	 * @return void
	 */
	public function generateSVG( bool $preview=false ) : void
	{
		$method = 'generateSVG_' . $this->shape;
		$this->raw = $this->$method();

		/* If we're just doing a preview, don't save anything */
		if( $preview )
		{
			return;
		}

		/* If we already have a file, delete it */
		if( $file = $this->file() )
		{
			$file->delete();
		}

        File::$safeFileExtensions[] = 'svg';
		$this->file = (string) File::create( 'core_CustomBadges', 'custombadge-' . $this->id . '.svg', $this->raw );
	}

	/**
	 * Generate an icon without the background
	 *
	 * @return string
	 */
	protected function generateSVG_no_shape() : string
	{
		$foreground = $this->foreground ?: 'currentColor';
		$icon = $this->compileIconElement();
		$numberOverlay = $this->getBadgeNumberOverlay( $this->number_overlay );

		return <<<SVG
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" height="48">
	<g color="{$foreground}" data-fgcolor-placeholder="color">
		{$icon}
		{$numberOverlay}
	</g>
</svg>
SVG;
	}

	/**
	 * Generate a square icon
	 *
	 * @return string
	 */
	protected function generateSVG_square() : string
	{
		$this->rotation = 45;
		$this->sides = 4;
		return $this->generateSVG_ngon();
	}

	/**
	 * Generate an n-gon
	 *
	 * @return string
	 */
	protected function generateSVG_ngon() : string
	{
		$points = static::getRadialPoints( $this->sides, ( $this->sides === 4 and $this->rotation % 90 === 45) ? sqrt( 47.4*47.4*2 ) : 47.4 );
		$icon = $this->compileIconElement();
		$numberOverlay = $this->getBadgeNumberOverlay( $this->number_overlay );
		$foreground = $this->foreground ?: 'currentColor';

		$path = "";
		foreach ( $points as $point )
		{
			$path .= $path ? ' L' : 'M';
			$path .= " {$point[0]} {$point[1]}";
		}
		return <<<SVG
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" height="48">
	<g color="{$foreground}" data-fgcolor-placeholder="color">
		<path d="{$path} Z" stroke="{$this->border}" stroke-linejoin="round" stroke-width="5" fill="{$this->background}" transform="rotate({$this->rotation})" transform-origin="center" data-bgcolor-placeholder="fill" data-bordercolor-placeholder="stroke" />
		{$icon}
		{$numberOverlay}
	</g>
</svg>
SVG;
	}

	/**
	 * Generate a circle badge icon
	 *
	 * @return string
	 */
	protected function generateSVG_circle() : string
	{
		$icon = $this->compileIconElement();
		$numberOverlay = $this->getBadgeNumberOverlay( $this->number_overlay );
		$foreground = $this->foreground ?: 'currentColor';

		return <<<SVG
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" height="48">
	<g color="{$foreground}" data-fgcolor-placeholder="color">
		<circle cx="50" cy="50" r="47.4" stroke="{$this->border}" stroke-width="5" fill="{$this->background}" data-bgcolor-placeholder="fill" data-bordercolor-placeholder="stroke" />
		{$icon}
		{$numberOverlay}
	</g>
</svg>
SVG;
	}


	/**
	 * Generate a star svg badge
	 *
	 * @return string
	 */
	protected function generateSVG_star() : string
	{
		$icon = $this->compileIconElement();
		$numberOverlay = $this->getBadgeNumberOverlay( $this->number_overlay );
		$foreground = $this->foreground ?: 'currentColor';
		$innerPoints = static::getRadialPoints( $this->sides, (0.75 + (0.095 * ( $this->sides - 4 ) / 8)) * ( 45 * ( cos( pi() / $this->sides ) ) ), 0.5 );
		$outerPoints = static::getRadialPoints( $this->sides, 47.4 );
		$path = "";
		for ( $i = 0; $i < $this->sides; $i++ )
		{
			$path .= !$path ? 'M ' : ' L ';
			$path .= "{$outerPoints[$i][0]} {$outerPoints[$i][1]} L {$innerPoints[$i][0]} {$innerPoints[$i][1]}";
		}
		return <<<SVG
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" height="48">
	<g color="{$foreground}" data-fgcolor-placeholder="color">
		<path d="{$path} Z" stroke="{$this->border}" stroke-width="5" stroke-linejoin="round" fill="{$this->background}" transform="rotate({$this->rotation})" transform-origin="center" data-bgcolor-placeholder="fill" data-bordercolor-placeholder="stroke" />
		{$icon}
		{$numberOverlay}
	</g>
</svg>
SVG;
	}

	/**
	 * Generate a flower svg badge
	 *
	 * @return string
	 */
	protected function generateSVG_flower() : string
	{
		$icon = $this->compileIconElement();
		$numberOverlay = $this->getBadgeNumberOverlay( $this->number_overlay );
		$foreground = $this->foreground ?: 'currentColor';

		$r = 45 * ((sin(pi() / $this->sides)) / (sin(pi() / $this->sides) + 1));
		$innerRadius = 45 - $r;
		$innerPoints = static::getRadialPoints( $this->sides, $innerRadius );
		$innerPoints[] = [$innerPoints[0][0], $innerPoints[0][1]];


		$ro = 51 * ((sin(pi() / $this->sides)) / (sin(pi() / $this->sides) + 1));
		$outerRadius = 51 - $ro;
		$outerPoints = static::getRadialPoints( $this->sides, $outerRadius );
		$outerPoints[] = [$outerPoints[0][0], $outerPoints[0][1]];

		$path = "";
		for ( $i = 0; $i < $this->sides + 1; $i++ )
		{
			if ( !$path )
			{
				$path .= "M {$innerPoints[$i][0]} {$innerPoints[$i][1]}";
			}
			else
			{
				$path .= " A {$r} {$r} 0 1 0 {$innerPoints[$i][0]} {$innerPoints[$i][1]}";
			}
		}

		$outerPath = "";
		for ( $i = 0; $i < $this->sides + 1; $i++ )
		{
			if ( !$outerPath )
			{
				$outerPath .= "M {$outerPoints[$i][0]} {$outerPoints[$i][1]}";
			}
			else
			{
				$outerPath .= " A {$ro} {$ro} 0 1 0 {$outerPoints[$i][0]} {$outerPoints[$i][1]}";
			}
		}

		return <<<SVG
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" height="48">
	<g color="{$foreground}" data-fgcolor-placeholder="color">
		<path d="{$outerPath} Z" stroke="{$this->border}" stroke-width="1" stroke-join="miter" fill="{$this->border}" transform="rotate({$this->rotation})" transform-origin="center" data-bordercolor-placeholder="fill,stroke" />
		<path d="{$path} Z" fill="{$this->background}" transform="rotate({$this->rotation})" transform-origin="center" data-bgcolor-placeholder="fill" />
		{$icon}
		{$numberOverlay}
	</g>
</svg>
SVG;
	}

	/**
	 * Get the element which can be embedded in the badge SVG
	 *
	 * @return string
	 */
	public function compileIconElement() : string
	{
		$iconData = $this->icon;
		$foreground = $this->foreground;

		if ( $iconData === null or ( !isset( $iconData['type'] ) and !isset( $iconData['raw'] ) ) )
		{
			return "";
		}

		$sizeOffset = (($this->icon_size - 1) * 3);
		if ( $this->shape === 'no_shape' )
		{
			$sizeOffset += 13;
		}


		if ( $iconData['type'] === 'fa' )
		{
			$doc = new DOMDocument();
			$doc->loadXML( $iconData['raw'] );
			$group = $doc->createElement( 'g' );
			$group->setAttribute( 'color', $foreground );
			$group->setAttribute( 'data-fgcolor-placeholder', "color" );
			foreach ( $doc->getElementsByTagName( 'svg' ) as $root )
			{
				foreach ( $root->childNodes as $node )
				{
					if ( !$group->isSameNode( $node ) )
					{
						$group->appendChild( $node );
					}
				}
				$root->appendChild( $group );
				break;
			}
			$escapedIcon = rawurlencode( $doc->saveXML( $root ?? null ) );
			$size = 42 + round( $sizeOffset * 2, 2 );
			$offset = (100 - $size) / 2;
			return <<<SVG
<image href="data:image/svg+xml,{$escapedIcon}" width="{$size}" height="{$size}" x="{$offset}" y="{$offset}" data-embedded-fg-color="" />
SVG;
		}
		else
		{
			// complicated, but emojis don't center well. This was tested on MacOS using Apple Emoji font and accounts for whether the badge uses a background shape or not. Might be worth adding a note that emojis might not look correct on all devices (as the font is device-specific)
			$size = 50 + round( $sizeOffset * 2, 2 );
			$offset = 50 + round( $size / 10, 2 );// - round( $size / 2, 2 );//round( ($this->shape === "no_shape" ? 24 : 18) + (($this->iconSize - 1) * ($this->shape === 'no_shape' ? 12.5 : 8.75) / 4), 2 );

			return <<<SVG
<text text-anchor="middle" dominant-baseline="middle" font-size="{$size}" x="50" y="{$offset}" style="line-height: {$size}px;" class="svg__text_icon">{$iconData['raw']}</text>
SVG;
		}
	}

	/**
	 * Get the number overlay. This will appear in the bottom right of the svg icon
	 *
	 * @param int $number
	 *
	 * @return string
	 */
	protected function getBadgeNumberOverlay( int $number ) : string
	{
        /* Do nothing if the number is empty */
        if( $number == 0 )
        {
            return '';
        }

		/* Check if the class using this badge uses the overlay */
		$class = $this->class;
		if( $class )
		{
			if( isset( $class::$customBadgeNumberOverlay ) and !$class::$customBadgeNumberOverlay )
			{
				return "";
			}
		}

		return <<<SVG
<style>
	svg text.svg__text_overlay:not(#x) {
		vertical-align: middle;
		line-height: 18px;
		fill: #ffffff;
	}
</style>
<circle cx="75" cy="75" r="20" fill="#334155" stroke="#fff" stroke-width="3"  data-number-overlay="" />
<text class="svg__text_overlay" x="75" y="81.5" fill="#fff" text-anchor="middle" font-size="18" font-weight="bolder" font-family="sans-serif"  data-number-overlay="" >{$number}</text>
SVG;
	}


	/**
	 * Get points evenly distributed over a given radius from the center. This is for ngons and stars; e.g. a polygon is 5 points of equal radius to the center, with even angular spacing
	 *
	 * @param 	int 	$n 			The number of coordinates to return
	 * @param 	float 	$radius		The radius. This method assumes the origin is 50, 50 (on a 100x100 grid)
	 * @param 	float 	$offset		The amount the points are angularly "offset". 0 means the first point is straight up, 1 means the first point is placed where the second point would be placed had the offset been 0. For example, a star's concave vertices should be offset from the convex points by 0.5
	 *
	 * @return array{float, float} Returns a set of SVG coordinate vectors (this is like cartesian except y increases as you move down)
	 */
	protected static function getRadialPoints( int $n, float $radius, float $offset=0 ) : array
	{
		if ( $n < 1 )
		{
			throw new InvalidArgumentException( 'too_few_points' );
		}

		$points = [];
		$th = ( 2 * pi() ) / $n;
		$angle = pi() / 2; // this is straight 'up'
		$angle += $offset * $th;
		for ( $i = 0; $i < $n; $i++ )
		{
			$x = 50 + ($radius * cos( $angle ));
			$y = 50 - ($radius * sin( $angle ));
			$points[] = [$x, $y];

			$angle += $th;
		}

		return $points;
	}

	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	public function save(): void
	{
		$this->generateSVG();
		parent::save();
	}

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete() : void
	{
		parent::delete();

		if( $file = $this->file() )
		{
			$file->delete();
		}
	}
}