<?php
namespace IPS\Theme;
class class_core_front_online extends \IPS\Theme\Template
{	function onlineUsersList( $table, $totalCount ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app )->pageHeader( \IPS\Member::loggedIn()->language()->addToStack('online_users') );
$return .= <<<IPSCONTENT

<div class='ipsBox ipsBox--onlineUsersList'>
	<h2 class='ipsBox__header'>
IPSCONTENT;

$pluralize = array( $totalCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_user_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h2>
	<div class='ipsBox__content'>
		{$table}
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function onlineUsersRow( $row ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="i-text-align_center i-padding_3 i-padding-bottom_4 i-grid i-gap_2 cOnlineUser 
IPSCONTENT;

if ( $row['login_type'] == \IPS\Session\Front::LOGIN_TYPE_ANONYMOUS ):
$return .= <<<IPSCONTENT
i-opacity_4
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
    <div class="i-flex i-justify-content_center">
		<div class="i-basis_140">{$row['photo']}</div>
	</div>
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/online/onlineUsersRow", "name:before", [ $row ] );
$return .= <<<IPSCONTENT
<h3 class="i-font-weight_600 i-font-size_3" data-ips-hook="name">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/online/onlineUsersRow", "name:inside-start", [ $row ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $row['login_type'] == \IPS\Session\Front::LOGIN_TYPE_ANONYMOUS ):
$return .= <<<IPSCONTENT

			<span class="ipsBadge ipsBadge--icon ipsBadge--soft" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'signed_in_anoymously', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip><i class="fa-solid fa-eye"></i></span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		{$row['member_name']}
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/online/onlineUsersRow", "name:inside-end", [ $row ] );
$return .= <<<IPSCONTENT
</h3>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/online/onlineUsersRow", "name:after", [ $row ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/online/onlineUsersRow", "location:before", [ $row ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="location">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/online/onlineUsersRow", "location:inside-start", [ $row ] );
$return .= <<<IPSCONTENT

		<p class="i-font-weight_600 i-color_soft i-link-color_inherit">{$row['location_lang']}</p>
		<ul class="ipsList ipsList--sep i-justify-content_center i-color_soft i-margin-top_1">
			<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['running_time'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission( 'can_use_ip_tools' ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->modPermission('can_use_ip_tools') and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'modcp' ) ) ):
$return .= <<<IPSCONTENT

					<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=ip_tools&ip={$row['ip_address']}", null, "modcp_ip_tools", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['ip_address'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['ip_address'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/online/onlineUsersRow", "location:inside-end", [ $row ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/online/onlineUsersRow", "location:after", [ $row ] );
$return .= <<<IPSCONTENT

</li>
IPSCONTENT;

		return $return;
}

	function onlineUsersRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty($rows)  ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

	    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "online", "core" )->onlineUsersRow( $row );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<li class='ipsGrid__stretch i-padding_3 i-text-align_center i-font-size_2 i-font-weight_500 i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'online_users_no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function onlineUsersTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-baseurl='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-resort='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller='core.global.core.table
IPSCONTENT;

if ( $table->canModerate() ):
$return .= <<<IPSCONTENT
,core.front.core.moderation
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
	<div class="ipsButtonBar ipsButtonBar--top">
		
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

			<div class='ipsButtonBar__pagination' data-role="tablePagination">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !empty( $table->filters ) ):
$return .= <<<IPSCONTENT

			<div class='ipsButtonBar__end'>
				<ul class="ipsDataFilters">
					<li>
						<button type="button" id="elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" popovertarget="elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" class='ipsDataFilters__button' data-role='tableFilterMenu'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="fa-solid fa-caret-down"></i></button>
						<i-dropdown popover id="elFilterByMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->uniqueId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" data-i-dropdown-selectable="radio">
							<div class="iDropdown">
								<ul class="iDropdown__items">
									<li>
										<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection, 'filter' => '', 'group' => \IPS\Request::i()->group ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action="tableFilter" data-ipsMenuValue='' 
IPSCONTENT;

if ( !$table->filter ):
$return .= <<<IPSCONTENT
aria-selected="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><i class="iDropdown__input"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
									
IPSCONTENT;

foreach ( $table->filters as $k => $q ):
$return .= <<<IPSCONTENT

										<li>
											<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $k, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection, 'group' => \IPS\Request::i()->group ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action="tableFilter" data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $k === $table->filter ):
$return .= <<<IPSCONTENT
aria-selected="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
><i class="iDropdown__input"></i>
IPSCONTENT;

$val = "{$table->langPrefix}{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
										</li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
							</div>
						</i-dropdown>
					</li>
				</ul>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

	<ol class='ipsGrid ipsGrid--lines ipsGrid--online-users i-basis_260'>
		
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

	</ol>

	
IPSCONTENT;

if ( $table->pages > 1 ):
$return .= <<<IPSCONTENT

		<div class='ipsButtonBar ipsButtonBar--bottom'>
			<div class="ipsButtonBar__pagination" data-role="tablePagination">
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}}