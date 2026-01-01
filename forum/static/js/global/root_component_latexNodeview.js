;(function(){"use strict";class iLatexNodeview extends HTMLElement{_preview=null;get preview(){return this._preview;}
_source=null;get source(){return this._source;}
_childObserver=null;_sourceObserver=null;constructor(){super();this._childObserver=new MutationObserver(_.throttle(entries=>this._identifyComponentsInChildren(),100));this._sourceObserver=new MutationObserver(_.debounce(entries=>this._updateView(),500));this._childObserver.observe(this,{childList:true,subtree:true});this._identifyComponentsInChildren();this.addEventListener('click',this);}
handleEvent(e){if(!(e instanceof Event)){throw new TypeError(`A non-event object passed to handleEvent.`);}
switch(e.type){case'click':if(e.target.closest('i-latex')===this.preview&&this.source&&this.preview){this._focusSource()}
break;}}
_focusSource(){if(!this.source){return;}
this.source.classList.remove('ipsInvisible');this.preview.classList.add('iLatex--no-decoration');this.preview.querySelector(":scope > .latex")?.classList.add('ipsInvisible');const selection=document.getSelection();selection.removeAllRanges();const range=new Range();range.setStart(this.source.childNodes[0]||this.source,1);range.setEnd(this.source.childNodes[0]||this.source,0);selection.addRange(range);}
_identifyComponentsInChildren(){const preview=this.querySelector('i-latex');if(preview!==this._preview){this._preview=preview;}
const source=this.querySelector('[data-latex-source]');if(source!==this._source){this._source=source;this._sourceObserver.disconnect();if(source){this._sourceObserver.observe(this._source,{subtree:true,characterData:true});this._updateView();}}}
_updateView(){if(!this._source||!this._preview){Debug.warn(`Cannot update view of i-latex-nodeview element because either its source or its preview is undefined`);return;}
customElements.whenDefined('i-latex').then(()=>this._preview.fill(this._source.textContent));}}
ips.ui.registerWebComponent("latexNodeview",iLatexNodeview);Debug.log(`Submitted the web component constructor, iLatexNodeview, for ${"latexNodeview"}`);})();;