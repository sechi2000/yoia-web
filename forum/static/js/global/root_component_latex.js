;(function(){"use strict";class iLatex extends HTMLElement{_latexHTML=null;get latexHTML(){return this._latexHTML;}
_originalText="";get originalText(){return this._originalText;}
constructor(){super();this._originalText=this.textContent;this.addEventListener('fillLatex',this.fill);this.addEventListener('unfillLatex',this.unfill);this.fill().then(()=>{});}
async katexLoaded(){await ips.ui._codehighlighting.whenLoaded();}
async fill(text){if(typeof text==='string'&&text!==this._originalText){this._originalText=text;this._latexHTML=null;}
if(!this._latexHTML){await this.katexLoaded();this._latexHTML=await ips.ui.codehighlighting.renderKatexRaw(this._originalText);}
this.innerHTML=this._latexHTML;}
unfill(){this.textContent=this._originalText;}}
ips.ui.registerWebComponent("latex",iLatex);Debug.log(`Submitted the web component constructor, iLatex, for ${"latex"}`);})();;