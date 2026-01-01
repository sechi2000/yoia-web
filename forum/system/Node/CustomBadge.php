<?php

/**
 * @brief        CustomBadge
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        3/6/2025
 */

namespace IPS\Node;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\CustomBadge as CustomBadgeClass;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Icon;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use IPS\Output;
use IPS\Theme;
use UnderflowException;
use function get_called_class;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

trait CustomBadge
{
	/**
	 * @var array
	 */
	protected static array $customBadgeStore = array();

	/**
	 * Get the custom badge linked to this class
	 *
	 * @param string|null $identifier
	 * @return CustomBadgeClass|null
	 */
	public function getRecordBadge( ?string $identifier=null ) : CustomBadgeClass|null
	{
		$class = get_called_class();
		$idColumn = static::$databaseColumnId;
		$key = $this->$idColumn . '.' . ( $identifier ?? '' );

		if( !isset( static::$customBadgeStore[ $class ][ $key ] ) )
		{
			try
			{
				$where = [
					[ 'class=?', $class ],
					[ 'active_record_id=?', $this->$idColumn ]
				];
				if( !empty( $identifier ) )
				{
					$where[] = [ 'extra=?', $identifier ];
				}
				else
				{
					$where[] = [ 'extra is null' ];
				}

				$row = Db::i()->select( '*', 'core_custom_badges', $where )->first();
				static::$customBadgeStore[ $class ][ $key ] = CustomBadgeClass::constructFromData( $row );
			}
			catch( UnderflowException )
			{
				static::$customBadgeStore[ $class ][ $key ] = false;
			}
		}

		return static::$customBadgeStore[ $class ][ $key ] ?: null;
	}

	/**
	 * Add elements to the form which are responsible for creating custom badges, and loads the JS and CSS that forms the interactive and grid-style UI
	 *
	 * @param Form $form
	 * @param CustomBadgeClass|null $badge
	 * @param string|null $badgeType
	 * @return void
	 */
	public function addBadgeFieldsToForm( Form $form, ?CustomBadgeClass $badge=null, ?string $badgeType=null ) : void
	{
		$form->class .= " ipsBadgePicker--form";
		$badgeType = $badgeType ?? 'custombadge';

		$shapeToggles = ["{$badgeType}_background", "{$badgeType}_border"];
		$dynamicShapeToggles = [ "{$badgeType}_sides", "{$badgeType}_rotation" ];
		$form->add( new YesNo(
			"{$badgeType}_use_custom",
			(bool) $badge,
			true,
			[
				'togglesOff' => static::$customBadgeToggles ?? [],
				'togglesOn' => [
					"{$form->id}_header_{$badgeType}_preview",
					"{$badgeType}_preview",
					"{$badgeType}_border",
					"{$badgeType}_foreground",
					"{$badgeType}_background",
					"{$badgeType}_shape",
					"{$badgeType}_icon",
					"{$badgeType}_sides",
					"{$badgeType}_rotation",
					"{$badgeType}_number_overlay",
					"{$badgeType}_icon_size"
				]
			]
		));

		$form->addHeader( $badgeType . '_preview' );
		Member::loggedIn()->language()->words[ $badgeType . '_preview' ] = Member::loggedIn()->language()->get( 'custombadge_preview' );

		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_badgepreview.js', 'core', 'admin' ) );
		$form->addHtml( Theme::i()->getTemplate( 'achievements', 'core', 'admin' )->badgePreview( $badge ? $badge->raw : '', $badgeType ) );

		$formFields = [
			'shape' => new Radio( "{$badgeType}_shape", $badge ? $badge->shape : 'circle', null, ['options' => [
				'square' => 'custombadge_square',
				'circle' => 'custombadge_circle',
				'ngon' => 'custombadge_n_gon',
				'star' => 'custombadge_star',
				'flower' => 'custombadge_flower',
				"no_shape" => "custombadge_no_shape",
			], 'toggles' => [
				'ngon' => [...$dynamicShapeToggles, ...$shapeToggles],
				'star' => [...$dynamicShapeToggles, ...$shapeToggles],
				'flower' => [...$dynamicShapeToggles, ...$shapeToggles],
				"square" => $shapeToggles,
				"circle" => $shapeToggles
			], 'rowClasses' => ['ipsFieldRow--badge-creator ipsFieldRow--badge-creator-shape']], null, null, null, "{$badgeType}_shape"),
			'icon' => new Icon( "{$badgeType}_icon", ( $badge and $badge->icon ) ? [ $badge->icon ] : null, false, ['useSvgIcon' => true, 'rowClasses' => ['ipsFieldRow--badge-creator ipsFieldRow--badge-creator-icon']], null, null, null, "{$badgeType}_icon" )
		];

		/* The overlay is optional */
		if ( !isset( static::$customBadgeNumberOverlay ) or static::$customBadgeNumberOverlay )
		{
			$formFields['number_overlay'] = new Number( "{$badgeType}_number_overlay", ( $badge and $badge->number_overlay ) ? $badge->number_overlay : 0, false, [ 'step' => 1, 'min' => 1, 'max' => 999, 'unlimited' => 0, 'unlimitedLang' => 'none', 'rowClasses' => ['ipsFieldRow--badge-creator'] ], null, null, null, "{$badgeType}_number_overlay" );
		}

		$formFields['background'] = new Color( "{$badgeType}_background", $badge ? $badge->background : '#eeb95f', null, ['rowClasses' => ['ipsFieldRow--badge-creator ipsFieldRow--badge-creator-background']], null, null, null, "{$badgeType}_background" );
		$formFields['border'] = new Color( "{$badgeType}_border", $badge ? $badge->border : '#f7d36f', null, ['rowClasses' => ['ipsFieldRow--badge-creator ipsFieldRow--badge-creator-border']], null, null, null, "{$badgeType}_border" );
		$formFields['foreground'] = new Color( "{$badgeType}_foreground", $badge ? $badge->foreground : '#ffffff', null, ['rgba' => true, 'swatches' => true, 'rowClasses' => ['ipsFieldRow--badge-creator ipsFieldRow--badge-creator-foreground']], null, null, null, "{$badgeType}_foreground" );
		$formFields['icon_size'] = new Form\Number( "{$badgeType}_icon_size", $badge ? $badge->icon_size : 3, null, ["min" => 1, "max" => 5, "step" => 1, "range" => true, "rowClasses" => ['ipsFieldRow--badge-creator', 'ipsFieldRow--badge-creator-icon-size']], id: "{$badgeType}_icon_size" );
		$formFields['sides'] = new Number( "{$badgeType}_sides", $badge ? $badge->sides : 5, null, ['step' => 1, 'min' => 4, 'max' => 12, 'range' => true, 'rowClasses' => ['ipsFieldRow--badge-creator']], null, null, null, "{$badgeType}_sides" );
		$formFields['rotation'] = new Number( "{$badgeType}_rotation", $badge?->rotation, null, ['step' => 1, 'min' => 0, 'max' => 90, 'range' => true, 'rowClasses' => ['ipsFieldRow--badge-creator']], null, null, null, "{$badgeType}_rotation" );

		foreach( $formFields as $k => $v )
		{
			$v->label = Member::loggedIn()->language()->addToStack( 'custombadge_' . $k );
			$form->add( $v );
		}
	}

	/**
	 * @param array $values
	 * @param string|null $badgeType
	 * @return void
	 */
	public function saveRecordBadge( array &$values, ?string $badgeType=null ) : void
	{
		$badge = $this->getRecordBadge( $badgeType );
		$prefix = $badgeType ?? 'custombadge';

		/* Are we using a badge? */
		if( $values[ $prefix . '_use_custom'] )
		{
			if( $badge === null )
			{
				$badge = new CustomBadgeClass;
				$badge->class = get_called_class();

				$idColumn = static::$databaseColumnId;
				$badge->active_record_id = $this->$idColumn;
			}

			$badge->shape = $values[ $prefix . '_shape'];
			$badge->foreground = $values[ $prefix . '_foreground'];
			$badge->background = $values[ $prefix . '_background'];
			$badge->border = $values[ $prefix . '_border'];
			$badge->icon = $values[ $prefix . '_icon'][0] ?? null;
			$badge->rotation = $values[ $prefix . '_rotation'];
			$badge->sides = $values[ $prefix . '_sides'];
			$badge->number_overlay = $values[ $prefix . '_number_overlay'] ?? 0;
			$badge->icon_size = $values[ $prefix . '_icon_size'] ?? 3;
			$badge->extra = $badgeType ?? null;
			$badge->save();
		}
		elseif( $badge !== null )
		{
			/* If we are not using a custom badge, and we already have one, delete it */
			$badge->delete();
		}

		/* Clear the form fields */
		foreach ( ['shape', 'foreground', 'background', 'border', 'icon', 'rotation', 'sides', 'number_overlay', 'use_custom', 'icon_size'] as $key )
		{
			unset( $values[ "{$prefix}_{$key}" ] );
		}
	}
}