;(function(){"use strict";class iCard extends HTMLElement{constructor(){super();this.supportsPopover=CSS.supports("selector(:popover-open)");this.supportsAnimationTimeline=CSS.supports("(animation-timeline: scroll())");this.addEventListener("beforetoggle",this);this.addEventListener("toggle",this);}
handleEvent(e){return this[`${e.type}Event`]&&this[`${e.type}Event`](e);}
beforetoggleEvent(e){if(e.newState==="open"){if(this.hasAttribute("data-i-card-append")){const appendMenuTo=this.getAttribute("data-i-card-append")||"body";document.querySelector(appendMenuTo).append(this);}
if(!this.swipeElement){this.createSwipeElement();}}}
toggleEvent(e){if(e.newState==="open"){this.scrollTop=this.scrollHeight;this.swipeObserver.observe(this.swipeElement);if(!this.supportsAnimationTimeline){this.addEventListener("scroll",this);this.addEventListener("transitionend",this);}}else{this.swipeObserver.disconnect();if(!this.supportsAnimationTimeline){this.removeEventListener("scroll",this);this.removeEventListener("transitionend",this);}}}
transitionendEvent(e){if(e.target===this&&!this.supportsAnimationTimeline){this.style.setProperty('--_card-backdrop-height',this.scrollHeight-this.clientHeight);}}
scrollEvent(e){requestAnimationFrame(()=>{this.style.setProperty('--_card-backdrop-scroll',this.scrollTop);});}
createSwipeElement(){let addBackdropElements=``;if(!this.querySelector(".iCardSwipe")){addBackdropElements+=`<div class='iCardSwipe'></div>`;}
if(!this.querySelector(".iCardDismiss")){addBackdropElements+=`<button class="iCardDismiss" type="button" popovertarget="${this.id}" popovertargetaction="hide" aria-label="Close" tabindex="-1"></button>`;}
this.insertAdjacentHTML("afterbegin",addBackdropElements);this.swipeElement=this.querySelector(".iCardSwipe");}
swipeObserver=new IntersectionObserver(entries=>{if(entries.some(entry=>entry.isIntersecting)){this.hidePopover();}},{root:this,threshold:.1})}
ips.ui.registerWebComponent("card",iCard);Debug.log(`Submitted the web component constructor, iCard, for ${"card"}`);})();;