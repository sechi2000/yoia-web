<?php
namespace IPS\Theme;
class class_downloads_front_widgets extends \IPS\Theme\Template
{	function downloadsCommentFeed( $comments, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $comments )  ):
$return .= <<<IPSCONTENT

	<header class='ipsWidget__header'>
		<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$carouselID = 'widget-topic-feed_' . mt_rand();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>
	<div class='ipsWidget__content'>
		<i-data>
			<ul class='ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
ipsData--carousel
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--widget-downloads-commentFeed' 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

foreach ( $comments as $comment ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->ui('css'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->ui('dataAttributes'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						
IPSCONTENT;

if ( in_array($layout, array("wallpaper", "featured", "grid")) ):
$return .= <<<IPSCONTENT

							<div class="ipsData__image" aria-hidden="true">
								
IPSCONTENT;

if ( $image = $comment->item()->primaryImage() ):
$return .= <<<IPSCONTENT

									<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<i></i>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<div class='ipsData__icon'>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $comment->author(), 'fluid' );
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div class="ipsData__content">
							<div class='ipsData__main'>
								<div class='ipsData__title'>
									<h4><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($comment->item()->mapped('title')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_file', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
								</div>
								<div class='ipsData__desc ipsRichText' data-controller='core.front.core.lightboxedImages'>{$comment->truncated()}</div>
								<p class='ipsData__meta'>
IPSCONTENT;

$htmlsprintf = array($comment->author()->link( NULL, NULL, $comment->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 &middot; <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->url()->setQueryString( array( 'do' => 'findComment', 'comment' => $comment->id ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>{$comment->dateLine()}</a></p>
							</div>
						</div>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function downloadsReviewFeed( $reviews, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsWidget__header'>
	<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
	
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$carouselID = 'widget-downloads-reviews_' . mt_rand();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</header>
<div class='ipsWidget__content'>
	
IPSCONTENT;

if ( !empty( $reviews )  ):
$return .= <<<IPSCONTENT

		<i-data>
			<ul class='ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
ipsData--carousel
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--widget-downloads-reviews' 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

foreach ( $reviews as $review ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->ui('css'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->ui('dataAttributes'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						
IPSCONTENT;

if ( \in_array( $layout, array("grid", "wallpaper", "featured")) ):
$return .= <<<IPSCONTENT

							<div class="ipsData__image" aria-hidden="true">
								
IPSCONTENT;

if ( $image = $review->item()->primaryImage() ):
$return .= <<<IPSCONTENT

									<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<i></i>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<div class='ipsData__icon'>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $review->author(), 'fluid' );
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div class='ipsData__content'>
							<div class='ipsData__main'>
								<h4 class='ipsData__title'>
									<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($review->item()->mapped('title')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_file', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $review->item()->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
									
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'small', $review->rating, \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT

								</h4>
								<p class='ipsData__meta'>
									
IPSCONTENT;

$htmlsprintf = array($review->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
, <time datetime='
IPSCONTENT;

$val = ( $review->mapped('date' ) instanceof \IPS\DateTime ) ? $review->mapped('date' ) : \IPS\DateTime::ts( $review->mapped('date' ) );$return .= (string) $val;
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = ( $review->mapped('date') instanceof \IPS\DateTime ) ? $review->mapped('date') : \IPS\DateTime::ts( $review->mapped('date') );$return .= (string) $val;
$return .= <<<IPSCONTENT
</time>
								</p>
								<p class='ipsData__desc'>{$review->truncated()}</p>
							</div>
						</div>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_recent_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function downloadStats( $stats, $latestFile, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_downloadStats', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
<div class='ipsWidget__content'>
	<ul class='ipsList ipsList--stats ipsList--stats ipsList--stacked ipsList--border ipsList--fill'>
		<li>
			<span class='ipsList__value'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $stats['totalFiles'] );
$return .= <<<IPSCONTENT
</span>
			<span class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_files_front', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
		</li>
		
IPSCONTENT;

if ( $stats['totalComments'] ):
$return .= <<<IPSCONTENT

			<li>
				<span class='ipsList__value'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $stats['totalComments'] );
$return .= <<<IPSCONTENT
</span>
				<span class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $stats['totalReviews'] ):
$return .= <<<IPSCONTENT

			<li>
				<span class='ipsList__value'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $stats['totalReviews'] );
$return .= <<<IPSCONTENT
</span>
				<span class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $latestFile ):
$return .= <<<IPSCONTENT

			<li>
				<div class='cNewestMember'>
					<div id='elDownloadStatsLatest' class=''>
						<p class='i-color_soft i-link-color_inherit'>
IPSCONTENT;

$htmlsprintf = array($latestFile->author()->link()); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'latest_file', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</p>
						<p class="i-font-weight_500"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $latestFile->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($latestFile->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_file', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $latestFile->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></p>
						<ul class="ipsList ipsList--inline i-justify-content_center i-gap_3">
							
IPSCONTENT;

if ( $latestFile->downloads ):
$return .= <<<IPSCONTENT

								<li><i class="fa-solid fa-download"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $latestFile->downloads );
$return .= <<<IPSCONTENT
</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $latestFile->container()->bitoptions['comments'] AND $latestFile->comments ):
$return .= <<<IPSCONTENT

								<li><i class='fa-solid fa-comment'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $latestFile->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</ul>
					</div>
				</div>
			</li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function fileFeed( $files, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsWidget__header'>
	<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
	
IPSCONTENT;

if ( \count( $files ) and $isCarousel ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$carouselID = 'widget--downloads-file-feed_' . mt_rand();
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</header>
<div class='ipsWidget__content'>
	
IPSCONTENT;

if ( \count( $files ) ):
$return .= <<<IPSCONTENT

		<i-data>
			<ul class="ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
ipsData--carousel
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--downloads-widget-file-feed" 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

foreach ( $files as $file ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "downloads" )->fileRow( $file, $layout );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_new_files', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function fileRow( $file, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<li class="ipsData__item 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->ui('css'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->ui('dataAttributes'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url('getPrefComment'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/widgets/fileRow", "image:before", [ $file,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
<div class="ipsData__image" aria-hidden="true" data-ips-hook="image">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/widgets/fileRow", "image:inside-start", [ $file,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $screenshot = $file->primary_screenshot_thumb ):
$return .= <<<IPSCONTENT

			<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $screenshot->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<i></i>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/widgets/fileRow", "image:inside-end", [ $file,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/widgets/fileRow", "image:after", [ $file,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

	<div class="ipsData__content">
		<div class="ipsData__main">
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/widgets/fileRow", "title:before", [ $file,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
<h4 class="ipsData__title" data-ips-hook="title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/widgets/fileRow", "title:inside-start", [ $file,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

				<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$sprintf = array($file->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_file', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/widgets/fileRow", "title:inside-end", [ $file,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
</h4>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/widgets/fileRow", "title:after", [ $file,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$price = NULL;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on ):
$return .= <<<IPSCONTENT

				<p class="cWidgetPrice">
					
IPSCONTENT;

if ( $file->isPaid() ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $price = $file->price() ):
$return .= <<<IPSCONTENT

							{$price}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_free_feed', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $layout === "featured" ):
$return .= <<<IPSCONTENT
<div class="ipsData__desc">{$file->truncated(TRUE)}</div>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \in_array( $layout, array("minimal")) ):
$return .= <<<IPSCONTENT

				<div class="ipsData__meta">
IPSCONTENT;

$htmlsprintf = array($file->author()->link( NULL, NULL, $file->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 · 
IPSCONTENT;

$val = ( $file->mapped('date') instanceof \IPS\DateTime ) ? $file->mapped('date') : \IPS\DateTime::ts( $file->mapped('date') );$return .= $val->html(FALSE, useTitle: true);
$return .= <<<IPSCONTENT
</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $file->container()->bitoptions['reviews'] ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'small', $file->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		
IPSCONTENT;

if ( !\in_array( $layout, array("minimal")) ):
$return .= <<<IPSCONTENT

			<div class="ipsData__extra">
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/widgets/fileRow", "stats:before", [ $file,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
<ul class="ipsData__stats" data-ips-hook="stats">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/widgets/fileRow", "stats:inside-start", [ $file,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $file->isPaid() and !$file->nexus and \in_array( 'purchases', explode( ',', \IPS\Settings::i()->idm_nexus_display ) )  ):
$return .= <<<IPSCONTENT

						<li data-stattype="purchases" data-v="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
							<span class="ipsData__stats-icon" data-stat-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'idm_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></span>
							<span class="ipsData__stats-label">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->purchaseCount(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'idm_purchases', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$file->isPaid() or \in_array( 'downloads', explode( ',', \IPS\Settings::i()->idm_nexus_display ) ) ):
$return .= <<<IPSCONTENT

						<li data-stattype="downloads" data-v="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
">
							<span class="ipsData__stats-icon" data-stat-value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></span>
							<span class="ipsData__stats-label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $file->downloads );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'downloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $file->container()->bitoptions['comments'] ):
$return .= <<<IPSCONTENT

						<li data-stattype="comments" data-v="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
							<span class="ipsData__stats-icon" data-stat-value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></span>
							<span class="ipsData__stats-label">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $file->comments, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/widgets/fileRow", "stats:inside-end", [ $file,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "downloads/front/widgets/fileRow", "stats:after", [ $file,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

				<div class="ipsData__last">
					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $file->author(), 'fluid' );
$return .= <<<IPSCONTENT

					<div class="ipsData__last-text">
						<div class="ipsData__last-primary">
IPSCONTENT;

$htmlsprintf = array($file->author()->link( NULL, NULL, $file->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
</div>
						<div class="ipsData__last-secondary">
IPSCONTENT;

$val = ( $file->mapped('date') instanceof \IPS\DateTime ) ? $file->mapped('date') : \IPS\DateTime::ts( $file->mapped('date') );$return .= $val->html(FALSE, useTitle: true);
$return .= <<<IPSCONTENT
</div>
					</div>
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</li>
IPSCONTENT;

		return $return;
}

	function topDownloads( $week, $month, $year, $all, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_topDownloads', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>

IPSCONTENT;

$tabID = mt_rand();
$return .= <<<IPSCONTENT

<i-tabs class='ipsTabs ipsTabs--small ipsTabs--stretch' id='ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsTabBar data-ipsTabBar-contentArea='#ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content'>
	<div role="tablist">
		<button type="button" id='ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_Week' class='ipsTabs__tab' role="tab" aria-controls='ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_Week_panel' aria-selected='true'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'top_week', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		<button type="button" id='ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_Month' class='ipsTabs__tab' role="tab" aria-controls='ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_Month_panel' aria-selected='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'top_month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		<button type="button" id='ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_Year' class='ipsTabs__tab' role="tab" aria-controls='ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_Year_panel' aria-selected='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'top_year', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		<button type="button" id='ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_All' class='ipsTabs__tab' role="tab" aria-controls='ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_All_panel' aria-selected='false'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'top_alltime', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
	</div>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

</i-tabs>
<section id='ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content' class='ipsTabs__panels'>
	<div id="ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_Week_panel" class='ipsTabs__panel' role="tabpanel" aria-labelledby="ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_Week">
		
IPSCONTENT;

if ( \count( $week ) ):
$return .= <<<IPSCONTENT

			<i-data>
				<ol class='ipsData ipsData--table ipsData--top-downloads-week'>
					
IPSCONTENT;

foreach ( $week as $idx => $data ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "downloads" )->fileRow( $data, $layout );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ol>
			</i-data>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_downloaded_files__week', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div id="ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_Month_panel" class='ipsTabs__panel' role="tabpanel" aria-labelledby="ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_Month" hidden>
		
IPSCONTENT;

if ( \count( $month ) ):
$return .= <<<IPSCONTENT

			<i-data>
				<ol class='ipsData ipsData--table ipsData--top-downloads-month'>
					
IPSCONTENT;

foreach ( $month as $idx => $data ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "downloads" )->fileRow( $data, $layout );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ol>
			</i-data>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_downloaded_files__month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div id="ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_Year_panel" class='ipsTabs__panel' role="tabpanel" aria-labelledby="ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_Year" hidden>
		
IPSCONTENT;

if ( \count( $year ) ):
$return .= <<<IPSCONTENT

			<i-data>
				<ol class='ipsData ipsData--table ipsData--top-downloads-year'>
					
IPSCONTENT;

foreach ( $year as $idx => $data ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "downloads" )->fileRow( $data, $layout );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ol>
			</i-data>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_downloaded_files__year', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
	<div id="ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_All_panel" class='ipsTabs__panel' role="tabpanel" aria-labelledby="ipsTabs_topDownloads
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_All" hidden>
		
IPSCONTENT;

if ( \count( $all ) ):
$return .= <<<IPSCONTENT

			<i-data>
				<ol class='ipsData ipsData--table ipsData--top-downloads-all'>
					
IPSCONTENT;

foreach ( $all as $idx => $data ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "downloads" )->fileRow( $data, $layout );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ol>
			</i-data>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<p class='ipsEmptyMessage'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_downloaded_files', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</section>
IPSCONTENT;

		return $return;
}

	function topSubmitterRow( $idx, $data, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsPhotoPanel">
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $data['member'], 'fluid' );
$return .= <<<IPSCONTENT

	<div class='ipsPhotoPanel__text'>
		<p class='ipsPhotoPanel__primary'>
			<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$data['member']->member_id}&do=content&type=downloads_file", "front", "profile_content", $data['member']->members_seo_name, 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $data['member']->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
		</p>
		<div class='ipsPhotoPanel__secondary i-flex i-gap_2'>
			<div data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'top_avg_rating', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'small', $data['rating'], \IPS\Settings::i()->reviews_rating_out_of );
$return .= <<<IPSCONTENT
</div>
			
IPSCONTENT;

$pluralize = array( $data['files'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_file_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function topSubmitters( $week, $month, $year, $all, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<header class='ipsWidget__header'>
	<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_topSubmitters', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	
IPSCONTENT;

$carouselID = 'widget-top-file-submitters_' . mt_rand();
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

</header>
<div class='ipsWidget__content'>
	<div class="ipsCarousel i-basis_300 ipsCarousel--widget-top-file-submitters" id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0">
		<div class="i-padding_3 i-flex_10">
			<h4 class='ipsTitle ipsTitle--h4 i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'week', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
			
IPSCONTENT;

if ( \count( $week ) ):
$return .= <<<IPSCONTENT

				<div class="i-grid i-gap_2">
					
IPSCONTENT;

foreach ( $week as $idx => $data ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "downloads" )->topSubmitterRow( $idx, $data );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'top_submitters_empty__week', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class="i-padding_3 i-flex_10">
			<h4 class='ipsTitle ipsTitle--h4 i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>

			
IPSCONTENT;

if ( \count( $month ) ):
$return .= <<<IPSCONTENT

				<div class="i-grid i-gap_2">
					
IPSCONTENT;

foreach ( $month as $idx => $data ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "downloads" )->topSubmitterRow( $idx, $data );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'top_submitters_empty__month', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class="i-padding_3 i-flex_10">
			<h4 class='ipsTitle ipsTitle--h4 i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'year', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
			
IPSCONTENT;

if ( \count( $year ) ):
$return .= <<<IPSCONTENT

				<div class="i-grid i-gap_2">
					
IPSCONTENT;

foreach ( $year as $idx => $data ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "downloads" )->topSubmitterRow( $idx, $data );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'top_submitters_empty__year', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class="i-padding_3 i-flex_10">
			<h4 class='ipsTitle ipsTitle--h4 i-margin-bottom_3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'alltime', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h4>
			
IPSCONTENT;

if ( \count( $all ) ):
$return .= <<<IPSCONTENT

				<div class="i-grid i-gap_2">
					
IPSCONTENT;

foreach ( $all as $idx => $data ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "downloads" )->topSubmitterRow( $idx, $data );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</div>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<p class='i-color_soft'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'top_submitters_empty__all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}}