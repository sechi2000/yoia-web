<?php
namespace IPS\Theme;
class class_core_front_streams extends \IPS\Theme\Template
{	function extraItem( $time, $image, $html, $view = 'expanded' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class='ipsStreamItem ipsStreamItem_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $view, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsStreamItem_actionBlock' data-role="activityItem" data-timestamp='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $time->getTimestamp(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	<div class='ipsStreamItem__iconCell'>
		
IPSCONTENT;

if ( isset( $image ) ):
$return .= <<<IPSCONTENT

			{$image}
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<span></span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div class='ipsStreamItem__mainCell'>
		<div class='ipsStreamItem__header'>
			{$html}
		</div>
		<ul class='ipsStreamItem__stats'>
			<li><i class="fa-regular fa-clock"></i> 
IPSCONTENT;

$val = ( $time instanceof \IPS\DateTime ) ? $time : \IPS\DateTime::ts( $time );$return .= $val->html(useTitle: true);
$return .= <<<IPSCONTENT
</li>
		</ul>
	</div>
</li>

IPSCONTENT;

		return $return;
}

	function filterCreateForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.front.streams.form' data-formType='createStream'>
	<form accept-charset='utf-8' class="ipsForm ipsForm--vertical ipsForm--filter-create-form" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" 
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT
enctype="multipart/form-data"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

foreach ( $attributes as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
 data-ipsForm>
		<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
		
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

			<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT

			<input type="hidden" name="MAX_FILE_SIZE" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uploadField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			<input type="hidden" name="plupload" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsBox ipsBox--searchFilterCreate ipsPull i-margin-bottom_3' id='elStreamFilterForm'>
			<h2 class='ipsBox__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'create_new_stream', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
			<div class='ipsBox__content'>
				
IPSCONTENT;

if ( isset( $elements['']['stream_title'] ) ):
$return .= <<<IPSCONTENT

					<div class="i-margin-bottom_3 i-background_2 i-padding_3">
						<input type='text' name='stream_title' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['stream_title']->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsInput ipsInput--text ipsInput--primary ipsInput--wide' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' autofocus>
						
IPSCONTENT;

if ( $elements['']['stream_title']->error ):
$return .= <<<IPSCONTENT

							<br>
							<span class="i-color_warning">
IPSCONTENT;

$val = "{$elements['']['stream_title']->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class="i-padding_3">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", \IPS\Request::i()->app )->filterForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class, $attributes, $sidebar, $form );
$return .= <<<IPSCONTENT

				</div>
			</div>
			<div class='ipsSubmitRow'>
				<ul class="ipsButtons">
					<li><button type='submit' class='ipsButton ipsButton--primary' data-action='createStream'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_button_save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
				</ul>
			</div>
		</div>
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function filterForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsSpanGrid i-gap_4'>
	<div class='ipsSpanGrid__4'>
		<h3 class='ipsSideMenu__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_include_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<ul class='ipsSideMenu__list ipsSideMenu--pseudoRadios cStreamForm_list' data-ipsSideMenu data-ipsSideMenu-type='radio' data-ipsSideMenu-responsive='false'>
			
IPSCONTENT;

foreach ( $elements['']['stream_include_comments']->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

				<li>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", "core" )->filterFormRadio( $k, $v, 'stream_include_comments', $elements['']['stream_include_comments']->value, $elements['']['stream_include_comments']->required, $elements['']['stream_include_comments']->options['options'], $elements['']['stream_include_comments']->options['disabled'] );
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", \IPS\Request::i()->app )->filterFormShowMe( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class, $attributes, $sidebar, $form );
$return .= <<<IPSCONTENT

	</div>
	<div class='ipsSpanGrid__8'>
		
IPSCONTENT;

if ( isset( $elements['']['stream_tags'] ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", "core" )->filterFormTags( $elements['']['stream_tags'] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsSpanGrid i-gap_4'>
			<div class='ipsSpanGrid__6'>
				<h3 class='ipsSideMenu__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_sorting', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				<ul class='ipsSideMenu__list ipsSideMenu--pseudoRadios cStreamForm_list' data-ipsSideMenu data-ipsSideMenu-type='radio' data-ipsSideMenu-responsive='false' data-filterType='sort'>
					
IPSCONTENT;

foreach ( $elements['']['stream_sort']->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

						<li>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", "core" )->filterFormRadio( $k, $v, 'stream_sort', $elements['']['stream_sort']->value, $elements['']['stream_sort']->required, $elements['']['stream_sort']->options['options'], $elements['']['stream_sort']->options['disabled'] );
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
				<h3 class='ipsSideMenu__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_read', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				<ul class='ipsSideMenu__list ipsSideMenu--pseudoRadios cStreamForm_list' data-ipsSideMenu data-ipsSideMenu-type='radio' data-ipsSideMenu-responsive='false' data-filterType='read'>
					
IPSCONTENT;

foreach ( $elements['']['stream_read']->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

						<li>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", "core" )->filterFormRadio( $k, $v, 'stream_read', $elements['']['stream_read']->value, $elements['']['stream_read']->required, $elements['']['stream_read']->options['options'], $elements['']['stream_read']->options['disabled'] );
$return .= <<<IPSCONTENT

						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
                <h3 class='ipsSideMenu__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_solved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                <ul class='ipsSideMenu__list ipsSideMenu--pseudoRadios cStreamForm_list' data-ipsSideMenu data-ipsSideMenu-type='radio' data-ipsSideMenu-responsive='false' data-filterType='solved'>
                    
IPSCONTENT;

foreach ( $elements['']['stream_solved']->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

                    <li>
                        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", "core" )->filterFormRadio( $k, $v, 'stream_solved', $elements['']['stream_solved']->value, $elements['']['stream_solved']->required, $elements['']['stream_solved']->options['options'], $elements['']['stream_solved']->options['disabled'] );
$return .= <<<IPSCONTENT

                    </li>
                    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                </ul>
				<h3 class='ipsSideMenu__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_ownership', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				
IPSCONTENT;

if ( isset( $elements['']['stream_ownership'] ) ):
$return .= <<<IPSCONTENT

				<ul class='ipsSideMenu__list ipsSideMenu--pseudoRadios cStreamForm_list' role="radiogroup" data-ipsSideMenu data-ipsSideMenu-type='radio' data-ipsSideMenu-responsive='false' data-filterType='ownership'>
					
IPSCONTENT;

foreach ( $elements['']['stream_ownership']->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", "core" )->filterFormRadio( $k, $v, 'stream_ownership', $elements['']['stream_ownership']->value, $elements['']['stream_ownership']->required, $elements['']['stream_ownership']->options['options'], $elements['']['stream_ownership']->options['disabled'] );
$return .= <<<IPSCONTENT

					</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", \IPS\Request::i()->app )->filterFormOwnership( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class, $attributes, $sidebar, $form );
$return .= <<<IPSCONTENT

				</ul>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<h3 class='ipsSideMenu__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_default_view', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				
IPSCONTENT;

if ( isset( $elements['']['stream_default_view'] ) ):
$return .= <<<IPSCONTENT

				<ul class='ipsSideMenu__list ipsSideMenu--pseudoRadios cStreamForm_list' role="radiogroup" data-ipsSideMenu data-ipsSideMenu-type='radio' data-ipsSideMenu-responsive='false' data-filterType='defaultview'>
					
IPSCONTENT;

foreach ( $elements['']['stream_default_view']->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

					<li>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", "core" )->filterFormRadio( $k, $v, 'stream_default_view', $elements['']['stream_default_view']->value, $elements['']['stream_default_view']->required, $elements['']['stream_default_view']->options['options'], $elements['']['stream_default_view']->options['disabled'] );
$return .= <<<IPSCONTENT

					</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ul>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class='ipsSpanGrid__6'>
				
IPSCONTENT;

if ( isset( $elements['']['stream_follow'] ) ):
$return .= <<<IPSCONTENT

					<h3 class='ipsSideMenu__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_follow', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					<ul class='ipsSideMenu__list ipsSideMenu--pseudoRadios cStreamForm_list' data-ipsSideMenu data-ipsSideMenu-type='radio' data-ipsSideMenu-responsive='false' data-filterType='follow'>
						
IPSCONTENT;

foreach ( $elements['']['stream_follow']->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

							<li>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", "core" )->filterFormRadio( $k, $v, 'stream_follow', $elements['']['stream_follow']->value, $elements['']['stream_follow']->required, $elements['']['stream_follow']->options['options'], $elements['']['stream_follow']->options['disabled'], ( $k == 'followed' ) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $k == 'followed' ):
$return .= <<<IPSCONTENT

									<button type="button" id="elMenu_followOptions" popovertarget="elMenu_followOptions_menu" class='cStreamForm_menu' data-ipsTooltip>
										<i class='fa-solid fa-gear i-font-size_2'></i>
										<i class='fa-solid fa-caret-down'></i>
									</button>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", \IPS\Request::i()->app )->filterFormFollowStatus( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class, $attributes, $sidebar, $form );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<h3 class='ipsSideMenu__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_date_type', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				
IPSCONTENT;

if ( isset( $elements['']['stream_date_type'] ) ):
$return .= <<<IPSCONTENT

					<ul class='ipsSideMenu__list ipsSideMenu--pseudoRadios cStreamForm_list' data-ipsSideMenu data-ipsSideMenu-type='radio' data-ipsSideMenu-responsive='false' data-filterType='date'>
						
IPSCONTENT;

foreach ( $elements['']['stream_date_type']->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

							<li>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", "core" )->filterFormRadio( $k, $v, 'stream_date_type', $elements['']['stream_date_type']->value, $elements['']['stream_date_type']->required, $elements['']['stream_date_type']->options['options'], $elements['']['stream_date_type']->options['disabled'] );
$return .= <<<IPSCONTENT

							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", \IPS\Request::i()->app )->filterFormTimePeriod( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class, $attributes, $sidebar, $form );
$return .= <<<IPSCONTENT

					</ul>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function filterFormClubs( $field ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $field ):
$return .= <<<IPSCONTENT

	<ul class='cFilterFormClubs'>{$field}</ul>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function filterFormContentType( $elements, $key, $type, $checked=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \in_array( $type, array_keys( $elements['']['stream_classes']->options['toggles'] ) ) ):
$return .= <<<IPSCONTENT

	<i-dropdown popover id="elMenu_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_menu" data-role="streamContainer" data-contentKey="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-className="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<div class="iDropdown i-padding_3"></div>
	</i-dropdown>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function filterFormContentTypeContent( $field, $type, $key ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-contentType='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $key, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

if ( $field ):
$return .= <<<IPSCONTENT

		<ul class='cFilterFormContentType'>{$field}</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function filterFormFollowStatus( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL, $showTitle=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $elements['']['stream_follow'] ) ):
$return .= <<<IPSCONTENT

	<i-dropdown popover id="elMenu_followOptions_menu">
		<div class="iDropdown">
			<ul class='ipsSideMenu__list ipsSideMenu--pseudoChecks cStreamForm_list' data-ipsSideMenu data-ipsSideMenu-type='check' data-ipsSideMenu-responsive='false' data-filterType='followed'>
				
IPSCONTENT;

foreach ( $elements['']['stream_followed_types']->options['options'] as $type => $lang ):
$return .= <<<IPSCONTENT

					<li>
						<a href='#' class='ipsSideMenu_item 
IPSCONTENT;

if ( $elements['']['stream_followed_types']->value !== 0 && \in_array( $type, $elements['']['stream_followed_types']->value ) !== FALSE ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
							<input type='checkbox' class="ipsSideMenu__toggle" name='stream_followed_types[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]' value='1' 
IPSCONTENT;

if ( $elements['']['stream_followed_types']->value !== 0 && \in_array( $type, $elements['']['stream_followed_types']->value ) !== FALSE ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> 
							
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

						</a>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</div>
	</i-dropdown>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

		return $return;
}

	function filterFormOwnership( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL, $showTitle=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $elements['']['stream_ownership'] ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $elements['']['stream_custom_members'] ) ):
$return .= <<<IPSCONTENT

		<li class='i-padding_2 i-margin-top_2 cStreamForm_authors 
IPSCONTENT;

if ( $elements['']['stream_ownership']->value !== 'custom' ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="ownershipMemberForm">
			<h4 class='i-padding-bottom_2 i-font-weight_500 i-color_hard'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_custom_members', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
			{$elements['']['stream_custom_members']->html()}
		</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

		return $return;
}

	function filterFormRadio( $k, $v, $name, $value, $required, $options, $disabled=FALSE, $hasOptions=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


<a href='#' class='ipsSideMenu_item 
IPSCONTENT;

if ( $hasOptions ):
$return .= <<<IPSCONTENT
cStream_withOptions
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( (string) $value == (string) $k or ( isset( $userSuppliedInput ) and !\in_array( $value, array_keys( $options ) ) and $k == $userSuppliedInput ) ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	<input type="radio" class="ipsSideMenu__toggle" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $value == (string) $k or ( isset( $userSuppliedInput ) and !\in_array( $value, array_keys( $options ) ) and $k == $userSuppliedInput ) ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $disabled === TRUE or ( \is_array( $disabled ) and \in_array( $k, $disabled ) ) ):
$return .= <<<IPSCONTENT
disabled
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	<label for='elRadio_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='elField_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_label'>
IPSCONTENT;

$val = "{$v}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</label>
</a>
IPSCONTENT;

		return $return;
}

	function filterFormShowMe( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL, $showTitle=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $showTitle ):
$return .= <<<IPSCONTENT
<h3 class='ipsSideMenu__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_classes_type', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

<ul class='ipsSideMenu__list ipsSideMenu--pseudoChecks cStreamForm_list' data-ipsSideMenu data-ipsSideMenu-type='check' data-ipsSideMenu-responsive='false' data-filterType='type'>
	<li>
		<a href='#' class='ipsSideMenu_item 
IPSCONTENT;

if ( $elements['']['stream_classes_type']->value == 0 ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='__all'>
			<input type="hidden" name="stream_classes[__EMPTY]" value="__EMPTY">
			<span class="ipsSideMenu__toggle"></span>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_all_content', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		</a>
		
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'clubs' ) ) and isset( $elements['']['stream_club_select'] ) ):
$return .= <<<IPSCONTENT
			
			<a href='#' class='cStreamForm_menu' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_filter_clubs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-role='streamClubs'>
				<i class='fa-solid fa-gear i-font-size_2'></i>
				<i class='fa-solid fa-caret-down'></i>
			</a>
			<div class='cStreamForm_nodes ipsHide' id="elStreamClubs">
				<span class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'loading', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</div>
			<input type="hidden" name="stream_club_select" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['stream_club_select']->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			<input type="hidden" name="stream_club_filter" value="
IPSCONTENT;

if ( \is_array( $elements['']['stream_club_filter']->value ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode(',',$elements['']['stream_club_filter']->value), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['stream_club_filter']->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<input type='radio' class='ipsHide' name='stream_classes_type' value='0' 
IPSCONTENT;

if ( $elements['']['stream_classes_type']->value == 0 ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
		<input type='radio' class='ipsHide' name='stream_classes_type' value='1' 
IPSCONTENT;

if ( $elements['']['stream_classes_type']->value == 1 ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	</li>
	
IPSCONTENT;

if ( isset( $elements['']['stream_classes'] ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $elements['']['stream_classes']->options['options'] as $type => $lang ):
$return .= <<<IPSCONTENT

			<li>
				<a class='ipsSideMenu_item 
IPSCONTENT;

if ( isset( $elements['']['stream_containers_' . str_replace('_pl', '', $lang ) ] ) || isset( $elements['']['stream_classes_' . str_replace('_pl', '', $lang ) ] ) ):
$return .= <<<IPSCONTENT
cStream_withOptions
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $elements['']['stream_classes_type']->value !== 0 && \in_array( $type, $elements['']['stream_classes']->value ) !== FALSE ):
$return .= <<<IPSCONTENT
ipsSideMenu_itemActive
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( '_pl', '', $lang ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
					<input type='checkbox' class="ipsSideMenu__toggle" name='stream_classes[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]' value='1' 
IPSCONTENT;

if ( $elements['']['stream_classes_type']->value !== 0 && \in_array( $type, $elements['']['stream_classes']->value ) !== FALSE ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
> 
					<span>
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</a>
				
IPSCONTENT;

if ( \in_array( $type, array_keys( $elements['']['stream_classes']->options['toggles'] ) ) ):
$return .= <<<IPSCONTENT

					<a href='#' class='cStreamForm_menu' data-ipsTooltip title='
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $lang )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_filter_options', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
' data-role='streamContainer' data-class='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-contentKey='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( str_replace( '_pl', '', $lang ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						<i class='fa-solid fa-gear i-font-size_2'></i>
						<i class='fa-solid fa-caret-down'></i>
					</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class='cStreamForm_nodes ipsHide'>
					<span class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'loading', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
				</div>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( ! isset( \IPS\Widget\Request::i()->do ) or \IPS\Widget\Request::i()->do != 'create'  ):
$return .= <<<IPSCONTENT

	<li class="i-margin-top_2">
		<button title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_apply_tip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" type='button' class='ipsButton ipsButton--soft ipsButton--wide' popovertarget="elStreamContentTypes_menu">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_apply', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</ul>

IPSCONTENT;

foreach ( $elements['']['stream_classes']->options['options'] as $type => $lang ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", "core" )->filterFormContentType( $elements, str_replace('_pl', '', $lang ), $type, ( $elements['']['stream_classes_type']->value !== 0 && $elements['']['stream_classes']->value == $type ), $elements );
$return .= <<<IPSCONTENT


IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

<i-dropdown popover id="elMenu_clubFilter_menu" data-role="streamContainer" data-contentKey="clubs" data-className="clubs">
	<div class="iDropdown">
		<div class="i-padding_3">

		</div>
	</div>
</i-dropdown>
IPSCONTENT;

		return $return;
}

	function filterFormTags( $tags ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-margin-bottom_4">
	<input type="hidden" name="stream_tags_type" value="custom">
	<h3 class='ipsSideMenu__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_tags', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	<div class='ipsFieldRow--fullWidth'>
		{$tags->html()}
		<p class='i-font-size_-2 i-color_soft i-padding-top_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tags_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function filterFormTimePeriod( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL, $showTitle=TRUE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $elements['']['stream_date_relative_days'] ) ):
$return .= <<<IPSCONTENT

	<li class='i-padding_2 cStreamForm_dates 
IPSCONTENT;

if ( $elements['']['stream_date_type']->value !== 'relative' ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="dateRelativeForm">
		<h4 class='ipsMinorTitle i-margin-bottom_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_date_relative_days_prefix', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
		{$elements['']['stream_date_relative_days']->html()}
		
IPSCONTENT;

if ( $elements['']['stream_date_relative_days']->error ):
$return .= <<<IPSCONTENT

			<div class="i-color_warning i-font-size_-2 i-margin-top_1">
IPSCONTENT;

$val = "{$elements['']['stream_date_relative_days']->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( isset( $elements['']['stream_date_range'] ) ):
$return .= <<<IPSCONTENT

	<li class='i-padding_2 cStreamForm_dates 
IPSCONTENT;

if ( $elements['']['stream_date_type']->value !== 'custom' ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="dateForm">
		<div class="ipsSpanGrid">
			<div class='ipsSpanGrid__6'>
				<h4 class='ipsMinorTitle i-margin-bottom_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'start', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
				{$elements['']['stream_date_range']->start->html()}	
			</div>
			<div class='ipsSpanGrid__6'>
				<h4 class='ipsMinorTitle i-margin-bottom_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'end', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
				{$elements['']['stream_date_range']->end->html()}	
			</div>
		</div>
		
IPSCONTENT;

if ( $elements['']['stream_date_range']->error ):
$return .= <<<IPSCONTENT

			<span class="i-color_warning i-font-size_-2">
IPSCONTENT;

$val = "{$elements['']['stream_date_range']->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</li>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function filterInlineForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.front.streams.form'>
	<form accept-charset='utf-8' class="ipsForm ipsForm--vertical ipsForm--filter-inline-form" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" 
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT
enctype="multipart/form-data"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

foreach ( $attributes as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
 data-ipsForm id='elFilterForm'>
		<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
		
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

			<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT

			<input type="hidden" name="MAX_FILE_SIZE" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uploadField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			<input type="hidden" name="plupload" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='' id='elStreamFilterForm' data-ips-hidden-animation="slide" hidden>
			<ul class='cStreamFilter ipsJS_show' data-role="filterBar">
				<li data-filter='stream_include_comments'>
					<button type="button" id="elStreamShowMe" popovertarget="elStreamShowMe_menu">
						<h3 class='cStreamFilter__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_include_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<p class='cStreamFilter__blurb' data-role='filterOverview'></p>
					</button>
				</li>
				<li data-filter='stream_classes'>
					<button type="button" id="elStreamContentTypes" popovertarget="elStreamContentTypes_menu">
						<h3 class='cStreamFilter__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_classes_type', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<p class='cStreamFilter__blurb' data-role='filterOverview'></p>
					</button>
				</li>
				
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $elements['']['stream_read'] ) ):
$return .= <<<IPSCONTENT

					<li data-filter='stream_read'>
						<button type="button" id="elStreamReadStatus" popovertarget="elStreamReadStatus_menu">
							<h3 class='cStreamFilter__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_read', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							<p class='cStreamFilter__blurb' data-role='filterOverview'></p>
						</button>
					</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

if ( isset( $elements['']['stream_solved'] ) ):
$return .= <<<IPSCONTENT

                    <li data-filter='stream_solved'>
                        <button type="button" id="elStreamSolvedStatus" popovertarget="elStreamSolvedStatus_menu">
                            <h3 class='cStreamFilter__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_solved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
                            <p class='cStreamFilter__blurb' data-role='filterOverview'></p>
						</button>
                    </li>
                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $elements['']['stream_ownership'] ) ):
$return .= <<<IPSCONTENT

					<li data-filter='stream_ownership'>
						<button type="button" id="elStreamOwnership" popovertarget="elStreamOwnership_menu">
							<h3 class='cStreamFilter__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_ownership', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							<p class='cStreamFilter__blurb' data-role='filterOverview'></p>
						</button>
					</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $elements['']['stream_follow'] ) ):
$return .= <<<IPSCONTENT

					<li data-filter='stream_follow'>
						<button type="button" id="elStreamFollowStatus" popovertarget="elStreamFollowStatus_menu">
							<h3 class='cStreamFilter__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_follow', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							<p class='cStreamFilter__blurb' data-role='filterOverview'></p>
						</button>
					</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $elements['']['stream_date_type'] ) ):
$return .= <<<IPSCONTENT

				<li data-filter='stream_date_type'>
					<button type="button" id="elStreamTimePeriod" popovertarget="elStreamTimePeriod_menu">
						<h3 class='cStreamFilter__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_date_type', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<p class='cStreamFilter__blurb' data-role='filterOverview'></p>
					</button>
				</li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<li data-filter='stream_sort'>
					<button type="button" id="elStreamSortEdit" popovertarget="elStreamSortEdit_menu">
						<h3 class='cStreamFilter__title'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_sorting', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
						<p class='cStreamFilter__blurb' data-role='filterOverview'></p>
					</button>
				</li>
			</ul>
			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id  ):
$return .= <<<IPSCONTENT

				<div data-role="saveButtonContainer" class="ipsHide">
					<ul class='i-flex i-justify-content_end i-gap_1 i-flex-wrap_wrap i-align-items_center i-background_2 i-padding_1 '>
						<li><strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_save_changes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong></li>
						<li><button type="button" class='ipsButton ipsButton--negative' data-action='dismissSave'><i class="fa-solid fa-trash-can"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_save_dismiss', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button></li>
						<li>
							<button type="button" id="elSaveNewStream" popovertarget="elSaveNewStream_menu" class='ipsButton ipsButton--positive' data-action='saveNewStream'><i class="fa-solid fa-file-circle-plus"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_button_save_as_new', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<i class="ipsMenuCaret"></i></button>
						</li>
						
IPSCONTENT;

if ( isset( $hiddenValues['__stream_owner'] ) and $hiddenValues['__stream_owner'] === \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

							<li>
								<button type='button' class='ipsButton ipsButton--primary' data-action='saveStream' id='elSaveStream'><i class="fa-solid fa-floppy-disk"></i>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_button_save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
							</li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</ul>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


			<i-dropdown popover id="elStreamSortEdit_menu">
				<div class="iDropdown">
					<ul class="iDropdown__items">
						<li>
							<label>
								<input type="radio" name="stream_sort" value="newest" 
IPSCONTENT;

if ( (string) $elements['']['stream_sort']->value == 'newest' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_stream_sort_newest">
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_sort_newest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</label>
						</li>
						<li>
							<label>
								<input type="radio" name="stream_sort" value="oldest" 
IPSCONTENT;

if ( (string) $elements['']['stream_sort']->value == 'oldest' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_stream_sort_oldest">
								
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_sort_oldest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</label>
						</li>
					</ul>
				</div>
			</i-dropdown>

			<!-- Show me menu -->
			<i-dropdown popover id="elStreamShowMe_menu" data-role="streamMenuFilter">
				<div class="iDropdown">
					<ul class="iDropdown__items">
						
IPSCONTENT;

foreach ( $elements['']['stream_include_comments']->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

							<li>
								<label>
									<input type="radio" name="stream_include_comments" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $elements['']['stream_include_comments']->value == $k ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="stream_ownership_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
									
IPSCONTENT;

$val = "{$v}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</label>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
					
IPSCONTENT;

if ( isset( $elements['']['stream_tags'] ) ):
$return .= <<<IPSCONTENT

						<!-- Tags menu -->
						<div class='i-padding_2'>
							<input type="hidden" name="stream_tags_type" value="custom">
							<h3 class='i-padding-bottom_2 i-font-weight_500 i-color_hard'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_tagged_with', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							<div class='ipsFieldRow--fullWidth'>
								{$elements['']['stream_tags']->html()}
								<p class='i-font-size_-2 i-color_soft i-margin-top_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tags_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
							</div>
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</i-dropdown>

			<!-- Content types menu -->
			<i-dropdown popover id="elStreamContentTypes_menu" data-role="streamMenuFilter">
				<div class="iDropdown">
					<div class="i-padding_2">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", \IPS\Request::i()->app )->filterFormShowMe( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class, $attributes, $sidebar, $form, FALSE );
$return .= <<<IPSCONTENT

					</div>
				</div>
			</i-dropdown>

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

				<!-- Read Status menu -->
				<i-dropdown popover id="elStreamReadStatus_menu" data-role="streamMenuFilter">
					<div class="iDropdown">
						<ul class="iDropdown__items">
							
IPSCONTENT;

foreach ( $elements['']['stream_read']->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

								<li>
									<label>
										<input type="radio" name="stream_read" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $elements['']['stream_read']->value == $k ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_stream_read_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
										<div>
											
IPSCONTENT;

$val = "{$v}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( $k == 'unread' ):
$return .= <<<IPSCONTENT

												<span class="iDropdown__minor">
													
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_read_unread_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

												</span>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</div>
									</label>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				</i-dropdown>
				<!-- Ownership menu -->
				<i-dropdown popover id="elStreamOwnership_menu" data-role="streamMenuFilter" data-i-dropdown-persist>
					<div class="iDropdown">
						<ul class="iDropdown__items">
							
IPSCONTENT;

foreach ( $elements['']['stream_ownership']->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

								<li>
									<label>
										<input type="radio" name="stream_ownership" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $elements['']['stream_ownership']->value == $k ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="stream_ownership_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
										
IPSCONTENT;

$val = "{$v}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									</label>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
						<ul>
							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", \IPS\Request::i()->app )->filterFormOwnership( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class, $attributes, $sidebar, $form, FALSE );
$return .= <<<IPSCONTENT

						</ul>
					</div>
				</i-dropdown>
				<!-- Follow status menu -->
				<i-dropdown popover id="elStreamFollowStatus_menu" data-role="streamMenuFilter" data-i-dropdown-persist>
					<div class="iDropdown">
						<input type='hidden' name='stream_follow' value='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['stream_follow']->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						<ul class="iDropdown__items">
							
IPSCONTENT;

foreach ( $elements['']['stream_followed_types']->options['options'] as $type => $lang ):
$return .= <<<IPSCONTENT

								<li>
									<label>
										<input type='checkbox' name='stream_followed_types[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $type, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]' value='1' 
IPSCONTENT;

if ( $elements['']['stream_followed_types']->value !== 0 && \in_array( $type, $elements['']['stream_followed_types']->value ) !== FALSE && (string) $elements['']['stream_follow']->value !== 'all' ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
										
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

									</label>
								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				</i-dropdown>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            <!-- Solved Status menu -->
			<i-dropdown popover id="elStreamSolvedStatus_menu" data-role="streamMenuFilter">
				<div class="iDropdown">
					<ul class="iDropdown__items">
						
IPSCONTENT;

foreach ( $elements['']['stream_solved']->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

						<li>
							<label>
								<input type="radio" name="stream_solved" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $elements['']['stream_solved']->value == $k ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="elRadio_stream_solved_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
								
IPSCONTENT;

$val = "{$v}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

							</label>
						</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
				</div>
			</i-dropdown>
			<!-- Time Period menu -->
			<i-dropdown popover id="elStreamTimePeriod_menu" data-role="streamMenuFilter" data-i-dropdown-persist>
				<div class="iDropdown">
					<ul class="iDropdown__items">
						
IPSCONTENT;

foreach ( $elements['']['stream_date_type']->options['options'] as $k => $v ):
$return .= <<<IPSCONTENT

							<li>
								<label>
									<input type="radio" name="stream_date_type" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( (string) $elements['']['stream_date_type']->value == $k ):
$return .= <<<IPSCONTENT
checked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 id="stream_date_type_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
									
IPSCONTENT;

$val = "{$v}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

								</label>
							</li>
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					</ul>
					<ul>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", \IPS\Request::i()->app )->filterFormTimePeriod( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class, $attributes, $sidebar, $form, FALSE );
$return .= <<<IPSCONTENT

					</ul>
				</div>
			</i-dropdown>
		</div>
		<i-dropdown popover id="elSaveNewStream_menu">
			<div class="iDropdown">
				<ul class="ipsForm ipsForm--vertical">
					<li class='ipsFieldRow'>
						<input type='text' name='stream_title' value='
IPSCONTENT;

if ( isset( $elements['']['stream_title'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['stream_title']->value, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' placeholder='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsInput ipsInput--text ipsInput--wide' 
IPSCONTENT;

if ( !empty($elements['']['stream_title']->options['maxLength']) ):
$return .= <<<IPSCONTENT
maxLength="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $elements['']['stream_title']->options['maxLength'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
					</li>
					<li class='ipsSubmitRow'>
						<button type='submit' data-action='newStream' class='ipsButton ipsButton--primary ipsButton--wide'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_new_stream', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
					</li>
				</ul>
			</div>
		</i-dropdown>
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function stream( $stream, $results, $autoUpdate, $showTimeline=FALSE, $sort='date', $view='expanded' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-role='streamResults' data-controller='core.front.streams.results' data-streamReadType="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->read, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( !$autoUpdate ):
$return .= <<<IPSCONTENT
data-view='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $view, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $autoUpdate && \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
data-autoPoll
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-streamUrl="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->id ) ):
$return .= <<<IPSCONTENT
data-streamID='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Widget\Request::i()->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
	<div class='i-padding_1 i-flex i-flex-wrap_wrap i-align-items_center i-gap_1 i-background_2 i-border-bottom_3'>

		<div class="i-flex_11"><button type="button" aria-controls="elStreamFilterForm" aria-expanded="false" data-ipscontrols class="ipsButton ipsButton--primary"><i class="fa-solid fa-filter"></i><span class="ipsAria__expanded-true">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_toggle_filters_shown', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><span class="ipsAria__expanded-false">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_toggle_filters_hidden', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class='ipsMenuCaret'></i></button></div>

		<div class='i-flex i-align-items_center i-flex-wrap_wrap i-gap_3'>
			<p id='elStreamUpdateMsg' class='i-color_soft i-font-weight_500 
IPSCONTENT;

if ( !( $autoUpdate && \IPS\Settings::i()->auto_polling_enabled ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsJS_show ipsResponsive_hidePhone' data-role='updateMessage'><i class='fa-solid fa-arrows-rotate i-margin-end_icon'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_auto_updates', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			<ul class="ipsButtonGroup">
				<li>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->baseUrl->setQueryString( 'view', 'condensed')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action='switchView' data-view='condensed' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_condensed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton 
IPSCONTENT;

if ( $view == 'condensed' ):
$return .= <<<IPSCONTENT
ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" rel="nofollow"><i class="fa-solid fa-list"></i><span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_results_as_condensed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				</li>
				<li>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->baseUrl->setQueryString( 'view', 'expanded')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action='switchView' data-view='expanded' data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_expanded', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton 
IPSCONTENT;

if ( $view == 'expanded' ):
$return .= <<<IPSCONTENT
ipsButton--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" rel="nofollow"><i class="fa-solid fa-bars"></i> <span class="ipsInvisible">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_results_as_expanded', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				</li>
			</ul>
		</div>
	</div>
	<ol class='ipsStream 
IPSCONTENT;

if ( $showTimeline !== FALSE && \count( $results ) ):
$return .= <<<IPSCONTENT
ipsStream_withTimeline
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role='streamContent'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "streams", "core" )->streamItems( $results, $showTimeline, $sort, $view );
$return .= <<<IPSCONTENT

	</ol>
	<div class='i-padding_2 i-text-align_center ipsJS_show 
IPSCONTENT;

if ( !\count( $results ) ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="loadMoreContainer">
		<a href='#' class='ipsButton ipsButton--inherit' data-action='loadMore'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'load_more_activity', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function streamItems( $results, $showTimeSeparators=FALSE, $sort='date', $view='expanded' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$currentSeparator = NULL;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $results ) ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $results as $result ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $result !== NULL ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $showTimeSeparators ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $currentSeparator != 'earlier' ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$separator = $result->streamSeparator( $sort == 'date' );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $currentSeparator != $separator ):
$return .= <<<IPSCONTENT

						<li class="ipsPwaStickyFix ipsPwaStickyFix--ipsStreamTime"></li>
						<li class='ipsStream__time ipsTitle ipsTitle--h4' data-timeType='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $separator, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$separator}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
						
IPSCONTENT;

$currentSeparator = $separator;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			{$result->html( $view, $sort != 'date', TRUE )}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( ! ( \IPS\Widget\Request::i()->isAjax() and isset( \IPS\Widget\Request::i()->before ) ) ):
$return .= <<<IPSCONTENT

	<li class='i-text-align_center i-padding_2' data-role="streamNoResultsMessage">
		<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	</li>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function streamWrapper( $stream, $html, $form, $rssLink=NULL, $canCopy=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<section class="ipsBox ipsBox--activity-stream ipsPull" data-controller="core.front.streams.main, core.front.core.ignoredComments" data-streamid="
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->id ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Widget\Request::i()->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
all
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
	<header class="ipsPageHeader">
		<div class="ipsPageHeader__row">
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/streams/streamWrapper", "header:before", [ $stream,$html,$form,$rssLink,$canCopy ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="header" class="ipsPageHeader__primary">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/streams/streamWrapper", "header:inside-start", [ $stream,$html,$form,$rssLink,$canCopy ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/streams/streamWrapper", "title:before", [ $stream,$html,$form,$rssLink,$canCopy ] );
$return .= <<<IPSCONTENT
<h1 data-ips-hook="title" class="ipsPageHeader__title i-flex_11 i-align-self_center">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/streams/streamWrapper", "title:inside-start", [ $stream,$html,$form,$rssLink,$canCopy ] );
$return .= <<<IPSCONTENT
<span data-role="streamTitle">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/streams/streamWrapper", "title:inside-end", [ $stream,$html,$form,$rssLink,$canCopy ] );
$return .= <<<IPSCONTENT
</h1>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/streams/streamWrapper", "title:after", [ $stream,$html,$form,$rssLink,$canCopy ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $form ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/streams/streamWrapper", "blurb:before", [ $stream,$html,$form,$rssLink,$canCopy ] );
$return .= <<<IPSCONTENT
<p data-ips-hook="blurb" class="ipsPageHeader__desc" data-role="streamOverview">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/streams/streamWrapper", "blurb:inside-start", [ $stream,$html,$form,$rssLink,$canCopy ] );
$return .= <<<IPSCONTENT

						<span data-role="streamBlurb">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->blurb(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/streams/streamWrapper", "blurb:inside-end", [ $stream,$html,$form,$rssLink,$canCopy ] );
$return .= <<<IPSCONTENT
</p>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/streams/streamWrapper", "blurb:after", [ $stream,$html,$form,$rssLink,$canCopy ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/streams/streamWrapper", "header:inside-end", [ $stream,$html,$form,$rssLink,$canCopy ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "core/front/streams/streamWrapper", "header:after", [ $stream,$html,$form,$rssLink,$canCopy ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

				<div class="ipsButtons">
					<button popovertarget="elStreamOptions_menu" class="ipsButton ipsButton--inherit" id="elStreamOptions"><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span><i class="ipsMenuCaret"></i></button>
					<i-dropdown id="elStreamOptions_menu" popover>
						<div class="iDropdown">
							<ul class="iDropdown__items">
								<li 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->defaultStream === $stream->_id ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-role="toggleDefaultTrue">
									<a data-action="toggleStreamDefault" data-change="1" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->url()->csrf()->setQueryString('default', 1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-regular fa-file-lines"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_this_isnt_default', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</li>
								<li 
IPSCONTENT;

if ( \IPS\Member::loggedIn()->defaultStream !== $stream->_id ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-role="toggleDefaultFalse">
									<a data-action="toggleStreamDefault" data-change="0" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->url()->csrf()->setQueryString('default', 0), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-regular fa-file-lines"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_this_is_default', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
								</li>
								<li><hr></li>
								
IPSCONTENT;

if ( $stream->member AND $stream->member === \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

									<li>
										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->url()->setQueryString( 'do', 'edit' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipsdialog data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_edit_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-action="editStream"><i class="fa-solid fa-pen-to-square"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_edit_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
									<li>
										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->url()->setQueryString( 'do', 'delete' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="removeStream"><i class="fa-regular fa-trash-can"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_remove', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
									<li><hr></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $stream->canSubscribe() ):
$return .= <<<IPSCONTENT

									<li>
										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->url()->setQueryString('do','subscribe'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-action="subscribe" data-ipsdialog data-ipsdialog-flashmessage="subscribed" data-ipsdialog-size="narrow" data-ipsdialog-title="
IPSCONTENT;

$sprintf = array($stream->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_subscribe_s', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
											<i class="fa-regular fa-envelope"></i>
											<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_subscribe', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
<span class="iDropdown__minor">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_subscribe_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></span>											
										</a>
									</li>
								
IPSCONTENT;

elseif ( $stream->canUnsubscribe() ):
$return .= <<<IPSCONTENT

									<li>
										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->url()->setQueryString('do','unsubscribe')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm data-confirmmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'confirm_stream_unsubscribe', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-confirmsubmessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_unsubscribe_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-regular fa-envelope"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_unsubscribe', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $rssLink ):
$return .= <<<IPSCONTENT

									<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rssLink, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-rss"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_rss', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</ul>
						</div>
					</i-dropdown>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( $canCopy ):
$return .= <<<IPSCONTENT

			<div class="ipsPageHeader__row ipsPageHeader__row--footer">
				
IPSCONTENT;

$owner = \IPS\Member::load( $stream->member );
$return .= <<<IPSCONTENT

					<div class="ipsPhotoPanel ipsPageHeader__primary">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $owner, 'mini' );
$return .= <<<IPSCONTENT

						<div class="ipsPhotoPanel__text">
							<div class="ipsPhotoPanel__primary">
IPSCONTENT;

$sprintf = array($owner->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_copy_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>
							<div class="ipsPhotoPanel__secondary">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_copy_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
						</div>
					</div>
					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->url()->setQueryString('do', 'copy')->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--secondary"><i class="fa-solid fa-plus"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_copy_feed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
				</div>
			
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>

	
IPSCONTENT;

if ( \IPS\Content\Search\Query::isRebuildRunning() ):
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--info">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_rebuild_is_running', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<div class="">
		
IPSCONTENT;

if ( $form ):
$return .= <<<IPSCONTENT

			{$form}
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div data-role="streamBody">
			{$html}
		</div>
	</div>

</section>

IPSCONTENT;

if ( $rssLink || ( $stream->member && $stream->member == \IPS\Member::loggedIn()->member_id ) ):
$return .= <<<IPSCONTENT

	<ul class="ipsButtons i-color_contrast i-margin-top_3">
		
IPSCONTENT;

if ( $rssLink ):
$return .= <<<IPSCONTENT
	
			<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $rssLink, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--inherit"><i class="fa-solid fa-rss"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rss', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $stream->member && $stream->member == \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

			<li>
				<button type="button" id="elStreamShare" popovertarget="elStreamShare_menu" class="ipsButton ipsButton--inherit" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_share_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip><i class="fa-solid fa-share-nodes"></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_share', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></button>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>
	
IPSCONTENT;

if ( $stream->member && $stream->member == \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

		<i-dropdown popover id="elStreamShare_menu">
			<div class="iDropdown">
				<div class="i-padding_2">
					<h3 class="ipsTitle ipsTitle--h4">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'share_stream_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
					<hr class="ipsHr">
					<p class="i-color_soft">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'share_stream_info', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
					<input type="text" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $stream->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsInput ipsInput--text ipsInput--wide i-margin-top_2">
				</div>
			</div>
		</i-dropdown>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function unsubscribeStream( $title, $member, $form, $choice = FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='ipsBox i-padding_3'>
    <p class='i-text-align_center'>
        <i class='ipsLargeIcon fa-solid fa-envelope'></i>
    </p>

    <h1 class='i-font-size_6 i-text-align_center'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h1>

    <div class='i-font-size_2 i-text-align_center'>
        
IPSCONTENT;

if ( $choice == 'single' ):
$return .= <<<IPSCONTENT

        <div class="ipsMessage ipsMessage--info">
IPSCONTENT;

$sprintf = array($title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_guest_unfollowed_thing', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</div>
        
IPSCONTENT;

elseif ( $choice == 'all' ):
$return .= <<<IPSCONTENT

        <div class="ipsMessage ipsMessage--info">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stream_guest_unfollowed_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </div>
    <div class='i-padding_3'>
        {$form}
    </div>
</div>
<p class='i-text-align_center'>
    <a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "/", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--text ipsButton--small'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'go_community_home', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
</p>



IPSCONTENT;

		return $return;
}}