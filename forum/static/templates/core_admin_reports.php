<?php
namespace IPS\Theme;
class class_core_admin_reports extends \IPS\Theme\Template
{	function overviewStatisticBlock( $blockKey, $subBlock, $details, $savedBlockId, $additionalClasses='', $hidden=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div
	class='ipsBox cStatTile 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $additionalClasses, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	data-role='statsBlock'
	data-refresh='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details["refresh"], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	data-block='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blockKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	data-subblock='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subBlock, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	data-app='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details["app"], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	data-controller='core.admin.stats.overviewBlock'
    
IPSCONTENT;

if ( $savedBlockId ):
$return .= <<<IPSCONTENT
data-savedstatid="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $savedBlockId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( isset( $details["form"] ) AND $details['form'] === TRUE ):
$return .= <<<IPSCONTENT
data-nodeFilter=''
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $hidden ):
$return .= <<<IPSCONTENT
hidden
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	style="view-transition-name: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $blockKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $subBlock, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
>
	<div class='cStatTile__header'>
		<h2 class='cStatTile__title' data-role="stat_title">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $details['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
		<div class="cStatTile__icons">
			
IPSCONTENT;

if ( isset( $details['form'] ) AND $details['form'] === TRUE ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

 $filterFormUrl = (string) \IPS\Http\Url::internal( "app=core&module=activitystats&controller=overview&do=loadBlockForm&blockKey={$blockKey}&subBlockKey={$subBlock}" ); 
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "reports", "core", 'admin' )->statBlockButton( 'overview_stats_form', 'filterForm', '', 'filter', $filterFormUrl, headerLang: 'statsreports_update_filters' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


            
IPSCONTENT;

$components = explode( "_", $blockKey, 2 );$app=array_shift($components);$block=array_shift($components);
$return .= <<<IPSCONTENT

            
IPSCONTENT;

 $saveEditForm = (string) \IPS\Http\Url::internal( "app=core&module=overview&controller=mycharts&do=saveBlock&block=" . $block . "&subblock={$subBlock}&blockapp={$app}" . ($savedBlockId ? '&saved_block_id=' . $savedBlockId : '') ); 
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $savedBlockId ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "reports", "core", 'admin' )->statBlockButton( 'statsreports_edit_block', 'saveReport', '', 'pencil', $saveEditForm, 'statsreports_edit_block' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "reports", "core", 'admin' )->statBlockButton( 'mychart_remove', 'removeBlock', 'ipsButton--negative', 'trash' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "reports", "core", 'admin' )->statBlockButton( dropdownSource:$saveEditForm, headerLang: 'statsreports_add_block_to_report' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
	
IPSCONTENT;

if ( $details['description'] ):
$return .= <<<IPSCONTENT

		<div class="cStatTile__desc i-color_soft">
IPSCONTENT;

$val = "{$details['description']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class='cStatTile__body ipsLoading ipsLoading--small'>
        <div class='cStatTile__bodyInner' data-role='statBlockContent'></div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function reportSelector( $reports, $activeTab='charts', $currentReport=null ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!-- v5 todo: This template is potentially no longer used -->
<ul class="ipsMenu ipsMenu_normal ipsHide" id="elStatsSelectReport_menu" data-role="reports">
    <li class="ipsMenu_item">
        <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=mycharts&tab=$activeTab", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-earth-americas"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'statsreports_report_maindash', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
    </li>
    
IPSCONTENT;

if ( \IPS\Platform\Bridge::i()->featureIsEnabled('community_health_stats') ):
$return .= <<<IPSCONTENT

    <li class="ipsMenu_item">
        <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=mycharts&communityhealth=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-heart-pulse"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'communityhealth', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
    </li>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    <li class="ipsMenu_sep">
        <hr class='ipsHr'>
    </li>
    
IPSCONTENT;

foreach ( $reports as $report ):
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$id = $report['id'];
$return .= <<<IPSCONTENT

        <li class="ipsMenu_item" 
IPSCONTENT;

if ( $currentReport === $report['id'] ):
$return .= <<<IPSCONTENT
data-selected
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
            <a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=mycharts&report_id=$id&tab=$activeTab", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-bookmark"></i>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $report['report_title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
        </li>
    
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function savedStatBlocks(  ) {
		$return = '';
		$return .= <<<IPSCONTENT




IPSCONTENT;

if ( ($blocks = \IPS\core\Statistics\Chart::getSavedBlocks()) AND !empty($blocks) ):
$return .= <<<IPSCONTENT

<div data-role="savedOverviewStats" data-controller='core.admin.stats.overview' data-url='?app=core&module=stats&controller=overview&do=loadBlock'>
    <div class='ipsBox i-padding_3 i-margin-bottom_2 i-flex i-flex-wrap_wrap i-align-items_center i-gap_2'>
        
IPSCONTENT;

if ( ($apps = \IPS\core\Statistics\Chart::getAppsToFilterBy()) and count( $apps ) > 1 ):
$return .= <<<IPSCONTENT

            <ul class='ipsList ipsList--inline i-flex_11 i-margin-block_1'>
                <li>
                    <strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'stats_show_c', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
                </li>

                
IPSCONTENT;

foreach ( \IPS\Application::applications() as $key => $app ):
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

if ( isset( $apps[ $key ] ) ):
$return .= <<<IPSCONTENT

                        <li>
                            <label for='toggle_app_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->directory, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
                                <input
                                        type='checkbox'
                                        id='toggle_app_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->directory, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
                                        name='toggle_app_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->directory, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
                                        data-action='toggleApp'
                                        data-toggledApp='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->directory, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
                                        checked
                                >
                                
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $app->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

                            </label>
                        </li>
                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

                
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

            </ul>
        
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

            <div class='i-flex_11'></div>
        
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

    </div>
    <hr class='ipsHr' />
    <div class='cStatsGrid'>
        <div class='ipsGrid i-basis_340'>
            
IPSCONTENT;

foreach ( $blocks as $block ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "reports", "core", 'admin' )->overviewStatisticBlock( $block['blockKey'], $block['subblock'], $block['details'], $block['saved_stat_id'] );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

        </div>
    </div>
</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

    <div data-role="savedOverviewStats" class="ipsBox">
        <p class="ipsEmpty">
            
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'statsreports_no_blocks', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

        </p>
    </div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT



IPSCONTENT;

		return $return;
}

	function statBlockButton( $lang='save_chart', $role='saveReport', $buttonStyle='ipsButton--inherit', $icon='floppy-disk', $dropdownSource=null, $headerLang=null ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

$id = mt_rand();
$return .= <<<IPSCONTENT


<button
	data-ipstooltip
	title='
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
	class='ipsButton 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $buttonStyle, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
	data-role='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $role, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
    type="button"
    id="stat_block_button_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
    popovertarget="stat_block_button_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_dropdown"
>
	
IPSCONTENT;

if ( $icon ):
$return .= <<<IPSCONTENT
<i class='fa-solid fa-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $icon, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'></i><span class="ipsInvisible">
IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$lang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</button>

IPSCONTENT;

if ( $dropdownSource ):
$return .= <<<IPSCONTENT

    <i-dropdown
            popover
            id="stat_block_button_
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_dropdown"
            class="cStatTile__save-report-menu"
            data-i-dropdown-source="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $dropdownSource, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
            data-dropdown-role="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $role, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
    >
        <div class="iDropdown">
            
IPSCONTENT;

if ( $headerLang ):
$return .= <<<IPSCONTENT

                <div class="iDropdown__header">
                    <h4>
IPSCONTENT;

$val = "{$headerLang}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
                </div>
            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

            <div class="ipsLoading iDropdown__content"></div>
        </div>
    </i-dropdown>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}