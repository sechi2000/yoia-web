<?php
namespace IPS\Theme;
class class_convert_admin_forms extends \IPS\Theme\Template
{	function reactionmapper( $name, $value, $cReactions, $ipsReactions, $descriptions ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsSpanGrid ipsAttachment_fileList cReactionMapper">
	
IPSCONTENT;

foreach ( $cReactions as $id => $cReaction ):
$return .= <<<IPSCONTENT

	<div class='ipsSpanGrid__2 ipsBox ipsAttach ipsImageAttach i-padding_2' data-controller="convert.admin.forms.reactionmapper" data-reactionid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
        
IPSCONTENT;

if ( $cReaction['icon'] ):
$return .= <<<IPSCONTENT

        <div class='i-text-align_center i-padding-top_2 cReactionImage'>
            <img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $cReaction['icon'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt='' class='ipsImage'>
        </div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        <div class="i-text-align_center i-padding_2">
            <input type="hidden" value="0" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" />
            <p class='i-text-align_center'>{$cReaction['title']}</p>
            <div>
                <button type="button" id="elReactionMapper
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" popovertarget="elReactionMapper
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" class='ipsButton ipsButton--secondary ipsButton--small ipsButton--wide'><span class="elMenuSelect_replace">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'convert_reaction_choose', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-caret-down'></i></button>
            </div>
            <i-dropdown popover id="elReactionMapper
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
                <div class="iDropdown">
                    <ul class="iDropdown__items" role='menu'>
                        
IPSCONTENT;

foreach ( $ipsReactions as $reactionId => $reaction ):
$return .= <<<IPSCONTENT

                            <li><button type="button" data-action="selectReaction" role='menuitem' data-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reactionId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ((isset($value[$id]) AND $value[$id] ==$reactionId) ):
$return .= <<<IPSCONTENT
data-default="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
IPSCONTENT;

if (( !empty( $reaction )) ):
$return .= <<<IPSCONTENT
<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $reaction, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" class="cReactionSmall"/> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $descriptions[ $reactionId ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</button></li>
                        
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                    </ul>
                </div>
            </i-dropdown>
        </div>
	</div>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function settingsToConvert( $settingsToConvert ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsMessage ipsMessage--info'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'converting_settings_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
<ul class='i-padding_3'>
	
IPSCONTENT;

foreach ( $settingsToConvert AS $setting ):
$return .= <<<IPSCONTENT

		<li><em>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</em> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'to', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <em>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting['our_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</em>: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $setting['value'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}}