;(function(){"use strict";class iPwaLoading extends HTMLElement{constructor(){super();}
connectedCallback(){document.addEventListener("click",this);window.addEventListener('pageshow',this);}
handleEvent(e){return this[`${e.type}Event`]&&this[`${e.type}Event`](e);}
pageshowEvent(e){if(e.persisted){this.hidden=true;}}
clickEvent(e){const link=e.target.closest("a[href]");if(!link)return;setTimeout(()=>{if(e.defaultPrevented)return;if(link.matches('[href^="#"], [href^="javascript:"], [target="_blank"]')||e.ctrlKey||e.metaKey||e.shiftKey||e.altKey)return;this.hidden=false;});}}
ips.ui.registerWebComponent("pwaLoading",iPwaLoading);Debug.log(`Submitted the web component constructor, iPwaLoading, for ${"pwaLoading"}`);})();;