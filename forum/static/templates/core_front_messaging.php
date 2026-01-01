<?php
namespace IPS\Theme;
class class_core_front_messaging extends \IPS\Theme\Template
{	function conversation( $conversation, $folders, $alert=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsMessenger__conversationHeader'>
	<div class='i-flex i-flex-wrap_wrap-reverse i-gap_3'>
		<h1 class='i-flex_91 i-basis_440 ipsTitle ipsTitle--h2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
		<ul class='i-flex_11 ipsButtons'>
			<li><button type="button" data-action='filterBarSwitch' data-switchTo='filterBar' class='ipsButton ipsButton--secondary ipsButton-backToInbox'><i class='fa-solid fa-caret-left'></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_list', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button></li>
			<li><button type="button" id="elConvoActions" popovertarget="elConvoActions_menu" class='ipsButton ipsButton--inherit i-margin-start_auto'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-caret-down'></i></button></li>
		</ul>
	</div>
	<i-dropdown popover id="elConvoActions_menu">
		<div class="iDropdown">
			<ul class="iDropdown__items">
				
IPSCONTENT;

if ( \count( $folders ) > 1 ):
$return .= <<<IPSCONTENT

					<li class='iDropdown__title'>
						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'move_message_to', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</li>
					
IPSCONTENT;

foreach ( $folders as $id => $name ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( isset( $conversation->map['map_folder_id'] ) AND (string) $id !== $conversation->map['map_folder_id'] ):
$return .= <<<IPSCONTENT

							<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->url('move')->csrf()->setQueryString( 'to', $id ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><i class="fa-regular fa-folder"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					<li><hr></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $conversation->map['map_ignore_notification'] ) ):
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

if ( $conversation->map['map_ignore_notification'] ):
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->url('notifications')->csrf()->setQueryString( 'status', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action="stopNotifications">
								<i class='fa-solid fa-bell'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_notifications_on', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</a>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->url('notifications')->csrf()->setQueryString( 'status', 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-action="stopNotifications">
								<i class='fa-regular fa-bell-slash'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_notifications_off', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</a>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<li>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->url('leaveConversation')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="deleteConversation">
						<i class="fa-regular fa-trash-can"></i> 
IPSCONTENT;

if ( $conversation->canDelete() AND isset( \IPS\Widget\Request::i()->_report ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_leave_moderator', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_leave', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</a>
				</li>
				
IPSCONTENT;

if ( $conversation->canDelete() AND isset( \IPS\Widget\Request::i()->_report ) ):
$return .= <<<IPSCONTENT

					<li>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->url('moderate')->setQueryString( array( 'action' => 'delete', '_report' => \IPS\Request::i()->_report ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm>
							<i class='fa-solid fa-trash'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_leave', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</a>
					</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</div>
	</i-dropdown>

	<div class='cMessage_members' id='elConvoMembers_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		<div class='i-color_soft i-font-weight_500 i-margin-top_1'><i class='fa-solid fa-user i-margin-end_icon'></i>
IPSCONTENT;

$pluralize = array( $conversation->to_count ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'members_in_convo', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>
		<ol class='ipsList ipsList--inline i-gap_4 i-margin-top_3'>
			
IPSCONTENT;

$members = 0;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $conversation->maps() as $map ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "messaging", \IPS\Request::i()->app )->participant( $map, $conversation );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$members++;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->group['g_max_mass_pm'] == -1 OR $members < \IPS\Member::loggedIn()->group['g_max_mass_pm']  ):
$return .= <<<IPSCONTENT

			<li data-role='addUserItem' class='i-align-self_center'>
				<button type="button" id="elInviteMember
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" popovertarget="elInviteMember
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" data-action='inviteUsers' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invite_a_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip class='ipsButton ipsButton--small ipsButton--inherit'><i class="fa-solid fa-user-plus"></i></button>
				<i-dropdown popover id="elInviteMember
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
					<div class="iDropdown">
						<form accept-charset='utf-8' action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->url('addParticipant'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-role='addUser' data-conversation="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsForm">
							<div class="ipsFieldRow">
								<div class="ipsFieldRow__label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_invite_placeholder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
								<input type='text' autofocus class='ipsInput ipsInput--text ipsInput--wide' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_invite_placeholder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' name="member_names" data-ipsAutocomplete data-ipsAutocomplete-unique data-ipsAutocomplete-dataSource="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=system&controller=ajax&do=findMember", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-ipsAutocomplete-commaTrigger='false' data-ipsAutocomplete-queryParam='input' data-ipsAutocomplete-resultItemTemplate="core.autocomplete.memberItem">
							</div>
							<div class="ipsSubmitRow">
								<button class='ipsButton ipsButton--primary ipsButton--wide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'invite', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
							</div>
						</form>
					</div>
				</i-dropdown>
			</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ol>
	</div>
</div>
<div data-controller='core.front.core.commentFeed, core.front.core.ignoredComments' 
IPSCONTENT;

if ( \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
data-autoPoll
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-baseURL='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $conversation->isLastPage() ):
$return .= <<<IPSCONTENT
data-lastPage
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-feedID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

if ( $alert  ):
$return .= <<<IPSCONTENT

		<blockquote class="ipsQuote i-margin-bottom_3">
			<div class="ipsQuote_contents ipsClearfix">
				{$alert->content}
			</div>
		</blockquote>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="ipsButtonBar 
IPSCONTENT;

if ( $conversation->commentPageCount() <= 1 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-role="tablePagination">
		<div class='ipsButtonBar__pagination'>
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app, 'global' )->pagination( $conversation->url(), $conversation->commentPageCount(), \IPS\Request::i()->page ? \intval( \IPS\Request::i()->page ) : 1, \IPS\core\Messenger\Conversation::getCommentsPerPage(), TRUE );
$return .= <<<IPSCONTENT

		</div>
	</div>
	<div data-role='commentFeed'>
		
IPSCONTENT;

foreach ( $conversation->comments() as $comment ):
$return .= <<<IPSCONTENT

			{$comment->html()}
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	</div>
	<div 
IPSCONTENT;

if ( $conversation->commentPageCount() <= 1 ):
$return .= <<<IPSCONTENT
class='ipsHide'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-role="tablePagination">
		<div class='ipsButtonBar'>
			<div class='ipsButtonBar__pagination'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", \IPS\Request::i()->app, 'global' )->pagination( $conversation->url(), $conversation->commentPageCount(), \IPS\Request::i()->page ? \intval( \IPS\Request::i()->page ) : 1, \IPS\core\Messenger\Conversation::getCommentsPerPage(), TRUE );
$return .= <<<IPSCONTENT

			</div>
		</div>
	</div>
	<div data-role='replyArea' class='i-padding_2'>
		{$conversation->commentForm()}
	</div>
    
IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() and \IPS\Settings::i()->core_datalayer_enabled ):
$return .= <<<IPSCONTENT

    <script>
		if ( IpsDataLayerConfig && !window.IpsDataLayerConfig && IpsDataLayerConfig._events.content_view.enabled ) {
			$('body').trigger( 'ipsDataLayer', {
				_key: 'content_view',
				_properties: 
IPSCONTENT;

$return .= json_encode($conversation->getDataLayerProperties());
$return .= <<<IPSCONTENT

			});
		}
    </script>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function folderForm( $action, $formHtml ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-controller="core.front.messages.folderDialog" data-action='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	{$formHtml}
</div>
IPSCONTENT;

		return $return;
}

	function messageList( $baseUrl, $langPrefix, $headers, $mainColumn, $rootButtons, $rows, $sortBy, $sortDirection, $filters, $currentFilter, $pages, $currentPage, $noSort, $quickSearch, $advancedSearch, $classes, $widths ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div id='elMessageSidebar' data-controller='core.front.messages.list, core.genericTable' data-baseurl="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "{$baseUrl}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
	<div class='ipsButtonBar'>
		<!--<span class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'menu_messages', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>-->
		<ul class='ipsButtonRow'>
			<li class=''>
				<button type="button" id="elCheck" popovertarget="elCheck_menu" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_rows_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsAutoCheck data-ipsAutoCheck-context="#elMessageList">
					<span class="cAutoCheckIcon"><i class="fa-regular fa-square"></i></span> <i class="fa-solid fa-caret-down"></i>
					<span class='ipsNotification' data-role='autoCheckCount'>0</span>
				</button>
				<i-dropdown popover id="elCheck_menu">
					<div class="iDropdown">
						<ul class="iDropdown__items">
							<li class="iDropdown__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_rows', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
							<li><button type="button" data-ipsMenuValue="all">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
							<li><button type="button" data-ipsMenuValue="none">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
						</ul>
					</div>
				</i-dropdown>
			</li>
			<li>
				<button type="button" id="elSortByMenu" popovertarget="elSortByMenu_menu">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></button>
				<i-dropdown popover id="elSortByMenu_menu" data-i-dropdown-selectable="radio">
					<div class="iDropdown">
						<ul class="iDropdown__items">
							
IPSCONTENT;

foreach ( $headers as $k => $header ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \in_array( $k, array( 'mt_last_post_time', 'mt_start_time', 'mt_replies' ) ) ):
$return .= <<<IPSCONTENT

									<li><button type="button" 
IPSCONTENT;

if ( $k == $sortBy ):
$return .= <<<IPSCONTENT
aria-selected="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsMenuValue='recent'><i class="iDropdown__input"></i>
IPSCONTENT;

$val = "{$langPrefix}{$header}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				</i-dropdown>
			</li>
			<li>
				<button type="button" id="elFilterMenu" popovertarget="elFilterMenu_menu">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></button>
				<i-dropdown popover id="elFilterMenu_menu" data-i-dropdown-selectable="radio">
					<div class="iDropdown">
						<ul class="iDropdown__items">
							<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "{$baseUrl}&sortby={$sortBy}&sortdirection={$sortDirection}&page=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( !array_key_exists( $currentFilter, $filters ) ):
$return .= <<<IPSCONTENT
aria-selected="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsMenuValue='all'><i class="iDropdown__input"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_filter_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

foreach ( $filters as $k => $q ):
$return .= <<<IPSCONTENT

								<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "{$baseUrl}&filter={$k}&sortby={$sortBy}&sortdirection={$sortDirection}&page=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $k === $currentFilter ):
$return .= <<<IPSCONTENT
aria-selected="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsMenuValue='others'><i class="iDropdown__input"></i>
IPSCONTENT;

$val = "{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
					
						</ul>
					</div>
				</i-dropdown>
			</li>
		</ul>
	</div>
	<div id='elMessageList' class='i-background_2'>
		<i-data>
			<ol class='ipsData ipsData--table ipsData--message-list' data-role='messageList'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "messaging", "core" )->messageListRows( $rows, $mainColumn, $rootButtons, $headers, $langPrefix );
$return .= <<<IPSCONTENT

			</ol>
		</i-data>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function messageListRow( $row, $overview, $folders ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsData__item ipsData__item--messenger 
IPSCONTENT;

if ( $row['mt_id'] == \IPS\Widget\Request::i()->id ):
$return .= <<<IPSCONTENT
ipsData__item--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $row['map_has_unread'] ):
$return .= <<<IPSCONTENT
data-ips-unread
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
data-ips-read
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-messageid='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['map_topic_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-keyNavBlock data-keyAction='return'>
	
IPSCONTENT;

if ( $overview ):
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&id={$row['mt_id']}", null, "messenger_convo", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['mt_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&id={$row['mt_id']}&latest=1", null, "messenger_convo", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['mt_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='ipsData__icon'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $row['last_message']->author(), 'fluid' );
$return .= <<<IPSCONTENT
</div>
	<div class="ipsData__content">
		<div class='ipsData__main'>
			<div class='ipsData__title'>
				
IPSCONTENT;

if ( $row['map_has_unread'] ):
$return .= <<<IPSCONTENT
<span class="ipsIndicator"></span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<h4>
					
IPSCONTENT;

if ( $overview ):
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&id={$row['mt_id']}", null, "messenger_convo", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-role="messageURL">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['mt_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&id={$row['mt_id']}&latest=1", null, "messenger_convo", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-role="messageURL">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['mt_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</h4>
			</div>
			
IPSCONTENT;

if ( $row['last_message'] ):
$return .= <<<IPSCONTENT
<div class='ipsData__desc ipsTruncate_2'>{$row['last_message']->truncated( TRUE )}</div>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsData__extra'>
				<div class="ipsData__last">
					<span data-ipsTooltip title="
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $row['mt_starter_id'] )->name, \IPS\DateTime::ts( $row['mt_start_time'] )->relative()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_started_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $row['mt_start_time'] !== $row['mt_last_post_time'] AND $row['last_message'] ):
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;

$sprintf = array($row['last_message']->author()->name, \IPS\DateTime::ts( $row['mt_last_post_time'] )->relative()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_last_reply', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$val = ( $row['mt_last_post_time'] instanceof \IPS\DateTime ) ? $row['mt_last_post_time'] : \IPS\DateTime::ts( $row['mt_last_post_time'] );$return .= $val->html(TRUE, TRUE, useTitle: true);
$return .= <<<IPSCONTENT
 &middot; 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['participants'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				</div>
				<ul class='ipsData__stats'>
					<li data-statType="comments">
						<span class='ipsData__stats-icon' data-stat-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['mt_replies'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title='
IPSCONTENT;

$pluralize = array( $row['mt_replies'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_message_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'></span>
						<span class="ipsData__stats-label" data-role="replyCount">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $row['mt_replies'] );
$return .= <<<IPSCONTENT
</span>
					</li>
				</ul>
			</div>
		</div>
	</div>
	
IPSCONTENT;

if ( $overview ):
$return .= <<<IPSCONTENT

		<div class="ipsData__mod">
			<input type='checkbox' data-role='moderation' name="moderate[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['map_topic_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" data-actions='delete 
IPSCONTENT;

if ( \is_array($folders) and \count($folders) > 1 ):
$return .= <<<IPSCONTENT
move
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-state class="ipsInput ipsInput--toggle">
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</li>
IPSCONTENT;

		return $return;
}

	function messageListRows( $conversations, $pagination=NULL, $overview=FALSE, $folders=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( empty( $conversations ) ):
$return .= <<<IPSCONTENT

	<li class='ipsData__item'>
		<div class='i-text-align_center i-flex_11 i-color_soft i-font-weight_500'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results_messages', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	</li>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $conversations as $row ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "messaging", \IPS\Request::i()->app )->messageListRow( $row, $overview, $folders );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function nomessage(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsEmpty">
	<i class="fa-solid fa-inbox i-opacity_2"></i>
	<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_message_selected', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
</div>
IPSCONTENT;

		return $return;
}

	function participant( $map, $conversation ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='
IPSCONTENT;

if ( !$map['map_user_active'] or $map['map_user_banned'] or \IPS\Member::load( $map['map_user_id'] )->members_disable_pm ):
$return .= <<<IPSCONTENT
cMessage_leftConvo
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-participant="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $map['map_user_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<div class='ipsPhotoPanel'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( \IPS\Member::load( $map['map_user_id'] ), 'fluid' );
$return .= <<<IPSCONTENT

		<div class='ipsPhotoPanel__text'>
			<div class='ipsPhotoPanel__primary'>
				
IPSCONTENT;

if ( $map['map_user_id'] == \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::load( $map['map_user_id'] )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( !\IPS\Member::load( $map['map_user_id'] )->member_id ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_deleted_member', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<button type="button" id="elMessage
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $map['map_user_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" popovertarget="elMessage
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $map['map_user_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" class="cMessage_name" data-role='userActions' data-username='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::load( $map['map_user_id'] )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Member::load( $map['map_user_id'] )->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></button>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsPhotoPanel__secondary i-font-size_-1'>
				<span data-role='userReadInfo'>
					
IPSCONTENT;

if ( $map['map_user_banned'] ):
$return .= <<<IPSCONTENT

						<span class="i-color_warning"><i class="fa-solid fa-ban"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_map_removed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

elseif ( !$map['map_user_active'] ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $map['map_left_time'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_map_left', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $map['map_left_time'] instanceof \IPS\DateTime ) ? $map['map_left_time'] : \IPS\DateTime::ts( $map['map_left_time'] );$return .= $val->html(useTitle: true);
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_map_left_notime', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

elseif ( \IPS\Member::load( $map['map_user_id'] )->members_disable_pm == 2 ):
$return .= <<<IPSCONTENT

						<span title='
IPSCONTENT;

$sprintf = array(\IPS\Member::load( $map['map_user_id'] )->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_map_disabled_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_map_disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $map['map_read_time'] ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_map_read', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $map['map_read_time'] instanceof \IPS\DateTime ) ? $map['map_read_time'] : \IPS\DateTime::ts( $map['map_read_time'] );$return .= $val->html(useTitle: true);
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_map_not_read', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</span>
			</div>
		</div>
	</div>
	
IPSCONTENT;

if ( $map['map_user_id'] != \IPS\Member::loggedIn()->member_id and \IPS\Member::load( $map['map_user_id'] )->member_id ):
$return .= <<<IPSCONTENT

		<i-dropdown popover id="elMessage
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_user
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $map['map_user_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu">
			<div class="iDropdown">
				<ul class="iDropdown__items">
					
IPSCONTENT;

if ( $conversation->starter_id == \IPS\Member::loggedIn()->member_id and ( $map['map_user_active'] or $map['map_user_banned'] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $map['map_user_banned'] ):
$return .= <<<IPSCONTENT

							<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->url('addParticipant')->csrf()->setQueryString( 'member', $map['map_user_id'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsMenuValue='unblock'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_map_unremove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->url('blockParticipant')->csrf()->setQueryString( 'member', $map['map_user_id'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsMenuValue='block'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_map_remove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<li><hr></li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose&to={$map['map_user_id']}", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'compose_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsMenuValue='msg'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_map_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				</ul>
			</div>
		</i-dropdown>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</li>
IPSCONTENT;

		return $return;
}

	function submitForm( $title, $form ) {
		$return = '';
		$return .= <<<IPSCONTENT


<header class="ipsPageHeader ipsPageHeader--messenger-submit">
	<h1 class="ipsPageHeader__title">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>
</header>
<div class='ipsBox ipsBox--messengerSubmit'>{$form}</div>
IPSCONTENT;

		return $return;
}

	function template( $folder, $folders, $counts, $conversations, $pagination, $conversation, $baseUrl, $baseUrlTemplate, $sortBy, $filter, $alert=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $alert = \IPS\core\Alerts\Alert::getAlertCurrentlyFilteringMessages() ):
$return .= <<<IPSCONTENT

	<div class="ipsMessage ipsMessage--info">
	    <div class='i-flex i-align-items_center i-justify-content_space-between'>
	        <div>
IPSCONTENT;

$sprintf = array($alert->title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'inbox_filtered_by_alert', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>
	        <div><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=removeAlertFilter" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "messaging", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" class="ipsButton">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'inbox_filtered_by_alert_undo', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></div>
	    </div>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div data-controller='core.front.messages.main' class='ipsMessenger ipsBox ipsBox--messenger ipsPull'>
	<div class='ipsMessenger__header' id='elMessageHeader'>

		<div class='i-flex_91 i-flex i-gap_2 i-align-items_center'>
			<div id="elMessengerTitle">
				<h1 class='ipsTitle ipsTitle--h3'>
					<button type="button" id="elMessageFolders" popovertarget="elMessageFolders_menu"><span data-role='currentFolder'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $folders[ $folder ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-angle-down i-margin-start_icon i-font-size_2 i-color_soft'></i></button>
				</h1>
				<i-dropdown popover id="elMessageFolders_menu">
					<div class="iDropdown">
						<ul class="iDropdown__items">
							
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_disable_pm == 0 ):
$return .= <<<IPSCONTENT

								<li class='ipsResponsive_hideDesktop'>
									<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=disableMessenger" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "messaging", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disable_messenger_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-microphone-lines-slash"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disable_messenger', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
								</li>
								<li class='ipsResponsive_hideDesktop'><hr></li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $folders as $id => $name ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $id === 'myconvo' ):
$return .= <<<IPSCONTENT

									<li data-role="folderInMenu"><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger", null, "messaging", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><span data-role='folderName'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><span class='ipsMenu_itemCount'>
IPSCONTENT;

if ( isset( $counts[ $id ] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $counts[ $id ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></a></li>
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<li data-role="folderInMenu"><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&folder={$id}", null, "messaging", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><span data-role='folderName'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span><span class='ipsMenu_itemCount'>
IPSCONTENT;

if ( isset( $counts[ $id ] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $counts[ $id ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span></a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							<li><hr></li>
							<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=addFolder" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-action="addFolder" id='elAddFolder'><i class="fa-solid fa-folder-plus i-opacity_5"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_add_folder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
						</ul>
					</div>
				</i-dropdown>
			</div>
			<span data-role="loadingFolderAction" class='i-color_soft' style='display: none'><i class='ipsLoadingIcon i-marign-inline-end_icon'></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'loading', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</div>

		<ul class='ipsButtons i-flex_11'>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_disable_pm == 0 ):
$return .= <<<IPSCONTENT

				<li class='ipsResponsive_showDesktop i-color_soft'>
					<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=disableMessenger" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "messaging", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-confirm data-confirmSubMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disable_messenger_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class='ipsButton ipsButton--text'><i class="fa-solid fa-microphone-lines-slash"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'disable_messenger', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\core\Messenger\Conversation::showComposeButton( \IPS\Member::loggedIn() ) ):
$return .= <<<IPSCONTENT

			    <li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-url='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=compose", null, "messenger_compose", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'compose_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'><i class="fa-solid fa-pen-to-square"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'compose_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>

		<i-dropdown popover id="elFolderSettings_menu">
			<div class="iDropdown">
				<ul class="iDropdown__items">
					<li class='iDropdown__title'>
IPSCONTENT;

$sprintf = array($folders[ $folder ]); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_action_with', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</li>
					<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=readFolder&folder={$folder}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsMenuValue='markRead'><i class="fa-solid fa-circle-check"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_action_read', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=renameFolder&folder={$folder}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $folder == 'myconvo' ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsMenuValue='rename' id='elFolderRename'><i class="fa-solid fa-pen-to-square"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_action_rename', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					<li><hr></li>
					<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=emptyFolder&folder={$folder}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' data-ipsMenuValue='empty'><i class="fa-solid fa-trash-can"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_action_empty', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
					<li><a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=deleteFolder&folder={$folder}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $folder == 'myconvo' ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsMenuValue='delete'><i class="fa-solid fa-folder-minus"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_action_delete_folder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				</ul>
			</div>
		</i-dropdown>
	</div>

	<div class='ipsMessenger__columns' data-ipsFilterBar data-ipsFilterBar-on='phone,tablet' data-ipsFilterBar-viewDefault='
IPSCONTENT;

if ( \IPS\Widget\Request::i()->id && !isset( \IPS\Widget\Request::i()->_list) ):
$return .= <<<IPSCONTENT
filterContent
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
filterBar
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsFilterBar-viewing='
IPSCONTENT;

if ( \IPS\Widget\Request::i()->id && !isset( \IPS\Widget\Request::i()->_list) ):
$return .= <<<IPSCONTENT
filterContent
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
filterBar
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
		<div class='ipsMessenger__inbox' data-role='filterBar'>
			
			<div id='elMessageSidebar' class='' data-controller='core.front.messages.list' data-folderID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $folder, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
				data-ipsInfScroll
				data-ipsInfScroll-scrollScope='#elMessageList'
				data-ipsInfScroll-container='#elMessageList [data-role="messageList"]'
				data-ipsInfScroll-url='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( array( 'sortBy' => $sortBy, 'filter' => $filter ) )->stripQueryString( 'id' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
				data-ipsInfScroll-pageParam='listPage'
			>

				<div class='ipsButtonBar ipsButtonBar--top' data-role="messageListFilters">
					<ul class='ipsDataFilters'>
						<li>
							<button type="button" id="elSortByMenu" popovertarget="elSortByMenu_menu" class='ipsDataFilters__button'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-caret-down'></i></button>
							<i-dropdown popover id="elSortByMenu_menu" data-i-dropdown-selectable="radio">
								<div class="iDropdown">
									<ul class="iDropdown__items">
										
IPSCONTENT;

foreach ( array( 'mt_last_post_time', 'mt_start_time', 'mt_replies' ) as $k ):
$return .= <<<IPSCONTENT

											<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( array( 'sortBy' => $k, 'filter' => $filter ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $k == \IPS\Widget\Request::i()->sortBy or ( !\IPS\Widget\Request::i()->sortBy and $k === 'mt_last_post_time') ):
$return .= <<<IPSCONTENT
aria-selected="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><i class="iDropdown__input"></i>
IPSCONTENT;

$val = "{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</ul>
								</div>
							</i-dropdown>
						</li>
						<li>
							<button type="button" id="elFilterMenu" popovertarget="elFilterMenu_menu" class='ipsDataFilters__button'><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='fa-solid fa-caret-down'></i></button>
							<i-dropdown popover id="elFilterMenu_menu" data-i-dropdown-selectable="radio">
								<div class="iDropdown">
									<ul class="iDropdown__items">
										<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( array( 'sortBy' => $sortBy ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( !\IPS\Widget\Request::i()->filter ):
$return .= <<<IPSCONTENT
aria-selected="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsMenuValue='all'><i class="iDropdown__input"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_filter_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										<li><hr></li>
										
IPSCONTENT;

foreach ( array( 'mine', 'not_mine', 'read', 'not_read' ) as $k ):
$return .= <<<IPSCONTENT

											<li><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $baseUrl->setQueryString( array( 'sortBy' => $sortBy, 'filter' => $k ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $k === \IPS\Widget\Request::i()->filter or ( !\IPS\Widget\Request::i()->filter and $k === 'all' ) ):
$return .= <<<IPSCONTENT
aria-selected="true"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><i class="iDropdown__input"></i>
IPSCONTENT;

$val = "messenger_filter_{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</ul>
								</div>
							</i-dropdown>
						</li>
						<li><button type="button" id="elFolderSettings" popovertarget="elFolderSettings_menu" class='ipsDataFilters__button'><i class='fa-solid fa-gear'></i><i class="fa-solid fa-caret-down"></i></button></li>
						<li>
							<button type="button" id="elCheck" popovertarget="elCheck_menu" class="ipsDataFilters__button" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_rows_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsAutoCheck data-ipsAutoCheck-context="#elMessageList">
								<span class="cAutoCheckIcon"><i class="fa-regular fa-square"></i></span><i class="fa-solid fa-caret-down"></i>
								<span class='ipsNotification' data-role='autoCheckCount'>0</span>
							</button>
							<i-dropdown popover id="elCheck_menu">
								<div class="iDropdown">
									<ul class="iDropdown__items">
										<li class="iDropdown__title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_rows', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
										<li><button type="button" data-ipsMenuValue="all">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
										<li><button type="button" data-ipsMenuValue="none">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
									</ul>
								</div>
							</i-dropdown>
						</li>
					</ul>
				</div>

				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->members_disable_pm ):
$return .= <<<IPSCONTENT

					<div class="ipsMessage ipsMessage--warning">
						<div class="i-flex i-align-items_center i-gap_2">
							<div class='i-flex_11'>
								<div class='i-font-weight_500'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'inbox_disabled', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
								<div class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'inbox_disabled_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
							</div>
							<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=enableMessenger" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "messaging", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class="i-flex_00 ipsButton ipsButton--primary ipsButton--small">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'inbox_enable', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
						</div>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


				<div class='ipsMessenger__search' id='elMessageSearch'>
					<form accept-charset='utf-8' method='post' action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger", null, "messaging", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" data-role='messageSearch' id='elMessageSearchForm'>
						<i class="fa-solid fa-magnifying-glass"></i>
						<input type='text' data-role='messageSearchText' name='q' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' value="
IPSCONTENT;

$return .= isset( \IPS\Widget\Request::i()->q ) ? htmlspecialchars( \IPS\Widget\Request::i()->q, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ): NULL;
$return .= <<<IPSCONTENT
">
						<button type="button" class='ipsMessenger__search-action' data-action='messageSearchCancel' hidden><i class='fa-solid fa-xmark'></i></button>
						<button type="button" id="elSearchTypes" popovertarget="elSearchTypes_menu" class='ipsMessenger__search-action'><i class="fa-solid fa-sliders"></i></button>
						<i-dropdown popover id="elSearchTypes_menu" data-i-dropdown-persist>
							<div class="iDropdown">
								<ul class="iDropdown__items">
									<li class='iDropdown__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_search_menu_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
									<li><label data-ipsMenuValue='post'><input type="checkbox" name="search[post]" checked value="1" id="search_post"> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_search_in_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label></li>
									<li><label data-ipsMenuValue='topic'><input type="checkbox" name="search[topic]" checked value="1" id="search_topic"> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_search_in_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label></li>
									<li><label data-ipsMenuValue='recipient'><input type="checkbox" name="search[recipient]" 
IPSCONTENT;

if ( ! empty(\IPS\Widget\Request::i()->search['recipient']) ):
$return .= <<<IPSCONTENT
checked="checked"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 recipientvalue="1" id="search_recipient"> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_recipient_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label></li>
									<li><label data-ipsMenuValue='sender'><input type="checkbox" name="search[sender]" 
IPSCONTENT;

if ( ! empty(\IPS\Widget\Request::i()->search['sender']) ):
$return .= <<<IPSCONTENT
checked="checked"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 value="1" id="search_sender"> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_sender_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label></li>
								</ul>
							</div>
						</i-dropdown>
					</form>
				</div>
				
				
IPSCONTENT;

if ( \IPS\Widget\Request::i()->q ):
$return .= <<<IPSCONTENT

					<p class='ipsMessage ipsMessage--info'>
IPSCONTENT;

$sprintf = array(\IPS\Request::i()->q); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_filtering', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
				<div id='elMessageList' class='ipsMessenger__inboxList'>
					<form action="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "messaging", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" method="post" data-role='moderationTools' data-ipsPageAction>
						<i-data>
							<ol class="ipsData ipsData--table ipsData--compact ipsData--messenger-inbox" data-role='messageList' data-ipsKeyNav data-ipsKeyNav-observe='return'>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "messaging", "core" )->messageListRows( $conversations, NULL, TRUE, $folders );
$return .= <<<IPSCONTENT

							</ol>
						</i-data>
						<div class="ipsData__modBar ipsJS_hide" data-role="pageActionOptions">
							<select class="ipsInput ipsInput--select i-basis_300" name="modaction" data-role="moderationAction">
								<option value='delete' data-icon='trash'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_leave', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
								<option value='move' data-icon='arrow-right'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_move', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
							</select>
							<button type="submit" class="ipsButton ipsButton--primary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
						</div>
					</form>
				</div>

				<div class='ipsResponsive_showPhone ipsButtonBar ipsButtonBar--bottom' data-role='messageListPagination'>
					{$pagination}
				</div>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "members", "core", 'global' )->messengerQuota( \IPS\Member::loggedIn(), array_sum( $counts ) );
$return .= <<<IPSCONTENT

			</div>
		</div>
		<div class='ipsMessenger__conversation' data-role='filterContent'>
			<div id='elMessageViewer' class='' data-controller='core.front.messages.view' 
IPSCONTENT;

if ( $conversation !== NULL ):
$return .= <<<IPSCONTENT
data-current-id="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $conversation->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

if ( $conversation === NULL ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "messaging", \IPS\Request::i()->app )->nomessage(  );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "messaging", \IPS\Request::i()->app )->conversation( $conversation, $folders, $alert );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
	</div>

	<div id='elFolderRename_content' style='display: none' data-controller="core.front.messages.folderDialog" data-type='rename'>
		<form action='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=renameFolder", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' method='post' class="ipsForm ipsForm--vertical">
		    <input type='hidden' name='csrfKey' value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			<div class='ipsFieldRow'>
				<input type='text' class='ipsInput' data-role="folderName">
			</div>
			<div class='ipsSubmitRow'>
				<button type='submit' class='ipsButton ipsButton--primary' data-action='saveFolderName'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</div>
		</form>
	</div>

	<div id='elAddFolder_content' style='display: none' data-controller="core.front.messages.folderDialog" data-type='add'>
		<form action='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&do=addFolder", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' method='post' class="ipsForm ipsForm--vertical">
		    <input type='hidden' name='csrfKey' value='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Session::i()->csrfKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			<div class='ipsFieldRow'>
				<input type='text' class='ipsInput' data-role="folderName" placeholder="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_add_folder_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
			</div>
			<div class='ipsSubmitRow'>
				<button type='submit' class='ipsButton ipsButton--primary' data-action='saveFolderName'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'messenger_add_folder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
			</div>
		</form>
	</div>
</div>

IPSCONTENT;

		return $return;
}}