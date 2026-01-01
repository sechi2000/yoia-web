/*
 * IPS embed handling
 * https://www.invisioncommunity.com
 */
( function () {
	"use strict";

	let _origin;
	let _div;
	let _embedId = '';
	let _initialHeight = 0;
	let _embedMaxWidth = null;
	let _currentHeight = null;

	/**
	 * @type {ResizeObserver}
	 */
	let _ro;

	/**
	 * Init method, called when the document is ready
	 *
	 * @returns {void}
	 */
	function init() {
		// Check for postMessage and JSON support
		if( !window.postMessage || !window.JSON.parse ){
			return;
		}

		// Work out our URL
		_div = ips.utils.get('ipsEmbed');
		const url = new URL(window.location.href);

		// Set our origin
		_origin = url.origin;
		ips.utils.log( "Origin in loader is " + _origin );

		// Hide the content
		_div.style.opacity = '0.00001';

		_initialHeight = ips.utils.getObjHeight( _div );

		// Check for any truncated text
		var truncated = document.querySelectorAll('[data-truncate]');

		if( truncated ){
			for( var n = 0; n < truncated.length; n++ ){
				var size = parseInt( truncated[ n ].getAttribute('data-truncate') || 5 );
				clamp( truncated[ n ], size );
			}
		}

		ips.eventHandler.on( document.body, 'click', clickLink );

		// Set all links to open in a new tab/window
		var links = document.querySelectorAll('a');
		for( var i = 0; i < links.length; i++ ){
			links[i].setAttribute('target', '_blank');
		}

		createResizeObserver();
	}

	var _showEmbed = function () {
		document.body.classList.remove('unloaded');
		ips.utils.fadeIn( _div );
	};

	/**
	 * Starts our main loop, which posts messages to the parent frame as needed
	 *
	 * @returns {void}
	 */
	function createResizeObserver() {
		ips.utils.log("Creating resize observer for internal embed...");
		let debounceLastCall=0, debounceTimeout, debounceInterval=200, sentMessage=false;
		const debouncePostMessage = (height) => {
			clearTimeout(debounceTimeout);
			const delta = Date.now() - debounceLastCall - debounceInterval;
			debounceLastCall = Date.now();
			debounceTimeout = setTimeout(
				() => {
					_currentHeight = height;
					ips.utils.log("Determined height as " + height + ' for internal embed ID ' + _embedId );
					if (!sentMessage) {
						sentMessage = true;
						_showEmbed();
					}

					if( _embedMaxWidth !== null ){
						_postMessage('dims', {
							height: height,
							width: _embedMaxWidth
						});
					} else {
						_postMessage('height', {
							height: height
						});
					}
				},
				Math.max(0, -delta)
			);
		}

		_ro = new ResizeObserver((entries) => {
			for (const entry of entries) {
				if (entry.target === _div) {
					if (typeof entry.borderBoxSize?.[0]?.blockSize === 'number') {
						debouncePostMessage(entry.borderBoxSize[0].blockSize + 10);
					}
					break;
				}
			}
		});

		_ro.observe(
			_div,
			{box: 'border-box'}
		);

		// resize observers usually call the callback right when .observe() is called, but just in case we manually send the height if there hasn't been one
		setTimeout(() => {
			// still hasn't been called?
			if (debounceLastCall === 0) {
				const height = ips.utils.getObjHeight(_div);

				// getting the height can force a reflow, so just double check it stil hasn't been called
				if (debounceLastCall === 0) {
					debounceLastCall = Date.now(); // do this outside the last call
					debouncePostMessage(height + 10);
				}
			}
		}, 20);
	}

	/**
	 * Posts a message to the iframe
	 *
	 * @param {string}	method		Method - will be encoded in the resulting message
	 * @param {Object}	[obj={}]	Obj - Any other keys and properties to encode in the message object
	 *
	 * @returns {void}
	 */
	var _postMessage = function (method, obj) {
		// Send to parent window
		window.top.postMessage( JSON.stringify({...obj, method, embedId: _embedId}), _origin);
	};

	/**
	 * Handles link clicks, to check for dialogs. If one is found, the options are passed
	 * up to the parent to display.
	 *
	 * @returns {void}
	 */
	var clickLink = function (e) {
		var link = e.target.closest('a');

		if( link !== null ){
			if( link.hasAttributes() ){
				var output = {};
				var attrs = link.attributes;

				for( var i = attrs.length - 1; i > 0; i-- ){
					if( attrs[i].name !== 'class' && attrs[i].name !== 'title' ){
						output[ attrs[i].name ] = attrs[i].value;
					}
				}

				if( output['data-ipsdialog'] !== undefined ){
					e.preventDefault();

					_postMessage('dialog', {
						url: link.href,
						options: output
					});
				}
			}
		}
	};

	/**
	 * Events sent to the iframe
	 */
	var messageEvents = {
		/**
		 * The parent is ready for messages
		 *
		 * @param 	{object} 	data 	Data from the iframe
		 * @returns {void}
		 */
		ready(data) {
			_embedId = data.embedId;
			_postMessage('ok');


			// make sure this is sent back to the regular IPS js. The resize observer is created on init(), which is called on domcontentready, not on "ready" which indicates the host page is ready to accept messages
			if (_currentHeight !== null) {
				if( _embedMaxWidth !== null ){
					_postMessage('dims', {
						height: _currentHeight,
						width: _embedMaxWidth
					});
				} else {
					_postMessage('height', {
						height: _currentHeight
					});
				}
			}
		},

		stop() {
			_ro?.disconnect();
			ips.eventHandler.off( window, 'message', windowMessage );
		},

		responsiveState(data) {
			if( data.currentIs === 'phone' ){
				document.body.className += ' ipsRichEmbed_phone';
			} else {
				document.body.className = document.body.className.replace( ' ipsRichEmbed_phone', '' );
			}
		}
	};

	/*******************************************************************************************/
	/* Boring stuff below */
	// Main message handler
	ips.eventHandler.on( window, 'message', windowMessage );

	function windowMessage (e) {
		if( e.origin !== _origin ){
			ips.utils.log( e.origin + 'does not equal' + _origin );
			return;
		}

		try {
			var pmData = JSON.parse( e.data );
			var method = pmData.method;
		} catch (err) {
			ips.utils.error("iframe: invalid data.");
			ips.utils.error(err.message)
			return;
		}

		if( method && typeof messageEvents[ method ] != 'undefined' ){
			ips.utils.log("Called " + method );
			messageEvents[ method ].call( this, pmData );
		} else {
			ips.utils.log("Method " + method + " doesn't exist");
		}
	}

	ips.utils.contentLoaded( window, function () {
		init();
	});
})();
