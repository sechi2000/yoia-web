/*
 * IPS embed handling
 * https://www.invisioncommunity.com
 */
( function () {
	"use strict";

	let _origin;
	let _div;
	let _embedId = '';
	let _failSafe = null;

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

		// But if we're in the 'top frame', don't bother showing loading
		try {
			if( window.self === window.top ){
				_div.style.opacity = 1;
				_div.parentNode.className = '';
			}
		} catch (err) {}

		// Start an emergency timeout, which we'll use to force-show the embed if the parent page
		// isn't talking to us for some reason. This will force the embed to eventually show, albeit at the wrong size,
		// instead of just being forever loading.
		var counter = 0;
		_failSafe = setInterval( function () {
			if( counter >= 6 ){ // approx 6 seconds
				ips.utils.log("Triggered failsafe timer");
				_div.parentNode.className = '';
				ips.utils.fadeIn( _div );
				clearInterval( _failSafe );
			}
			counter++;
		}, 1000 );
	}

	/**
	 * Starts our main loop, which posts messages to the parent frame as needed
	 *
	 * @returns {void}
	 */
	function createResizeObserver() {
		ips.utils.log("Creating resize observer for external embed...");
		let debounceLastCall=0, debounceTimeout, debounceInterval=200, sentMessage=false;
		const debouncePostMessage = (height) => {
			clearTimeout(debounceTimeout);
			const delta = Date.now() - debounceLastCall - debounceInterval;
			debounceLastCall = Date.now();
			debounceTimeout = setTimeout(
				() => {
					if (!sentMessage) {
						sentMessage = true;
						ips.utils.fadeIn(_div);
					}
					_div.parentNode.className = '';
					_postMessage('height', {height})
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
				ips.utils.log("Determined height as " + height + ' for external embed ID ' + _embedId );

				// getting the height can force a reflow, so just double check it stil hasn't been called
				if (debounceLastCall === 0) {
					debounceLastCall = Date.now(); // do this outside the last call
					debouncePostMessage(height);
				}
			}
		}, 20);
	}

	/**
	 * Posts a message to the iframe
	 *
	 * @returns {void}
	 */
	var _postMessage = function (method, obj) {
		// Send to parent window
		window.top.postMessage(JSON.stringify( {...obj, method, embedId: _embedId}), _origin);
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

			createResizeObserver();
			clearInterval( _failSafe ); // Stop our emergency timer
		},

		stop() {
			_ro?.disconnect();
			clearInterval( _failSafe ); // Stop our emergency timer
			eventHandler.off( window, 'message', windowMessage );
		}
	};

	/*******************************************************************************************/
	/* Boring stuff below */

	// Main message handler
	ips.eventHandler.on( window, 'message', windowMessage );

	function windowMessage (e) {
		if( e.origin !== _origin ){
			ips.utils.log( e.origin + ' does not equal ' + _origin );
			return;
		}
		if (typeof e.data !== "string") {
			return;
		}

		let pmData;
		try {
			pmData = JSON.parse(e.data);
			if (typeof pmData !== 'object') {
				return;
			}
		} catch (e) {
			return; // we don't log here because the message could have come from somewhere else
		}

		try {
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