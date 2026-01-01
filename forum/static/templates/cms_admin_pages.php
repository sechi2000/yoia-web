<?php
namespace IPS\Theme;
class class_cms_admin_pages extends \IPS\Theme\Template
{	function pageRowHtml( $page, $groupNames ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $page->default == \IPS\cms\Pages\Page::PAGE_DEFAULT ):
$return .= <<<IPSCONTENT

<span class='ipsBadge ipsBadge--positive'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'default_page', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

elseif ( $groupNames ):
$return .= <<<IPSCONTENT

<span class='ipsBadge ipsBadge--intermediary'>
IPSCONTENT;

$sprintf = array($groupNames); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'group_defaults', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function previewTemplateLink(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<span data-role="viewTemplate" class='ipsButton ipsButton--inherit ipsButton--tiny'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'cms_block_view_template', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

		return $return;
}

	function revisionDate( $time, $manualSave=0 ) {
		$return = '';
		$return .= <<<IPSCONTENT

<ul class='ipsList ipsList--inline i-gap_2'>
 <li>

IPSCONTENT;

if ( $manualSave ):
$return .= <<<IPSCONTENT

    <span class="ipsBadge ipsBadge--icon ipsBadge--small ipsBadge--positive" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revision_page_type_manual', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa fa-save'></i></span>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

   <span class="ipsBadge ipsBadge--icon ipsBadge--small ipsBadge--neutral" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'revision_page_type_auto', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class='fa fa-history'></i></span>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</li>
 <li>
    <span class="
IPSCONTENT;

if ( $manualSave ):
$return .= <<<IPSCONTENT
i-color_positive
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-color_soft
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $time, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
 </li>
 </ul>

IPSCONTENT;

		return $return;
}}