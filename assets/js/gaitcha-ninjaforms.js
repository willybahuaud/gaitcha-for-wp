/**
 * Gaitcha adapter for Ninja Forms.
 *
 * Ninja Forms renders fields client-side via Backbone.js and submits
 * via AJAX (not form.submit()). This adapter:
 * 1. Watches for gaitcha containers rendered by NF's Backbone views
 * 2. Initializes the captcha widget in each container
 * 3. Hooks into NF's jQuery AJAX to append gaitcha hidden fields
 *    (_ct token + _gc_xxx_log) to the POST payload
 *
 * The gaitcha core serializes the behavioral log at check time
 * (not on form submit), so the hidden inputs have their values
 * ready before NF collects the AJAX data.
 *
 * @package GaitchaWP
 */
(function () {
	'use strict';

	/** @type {{ endpoint: string, defaultLabel: string, theme: string }} */
	var config = window.gaitchaWPConfig || {};

	/** @type {Array<HTMLElement>} Tracked gaitcha containers for AJAX injection. */
	var trackedContainers = [];

	/**
	 * Initializes Gaitcha on a single container element.
	 *
	 * @param {HTMLElement} container The gaitcha container rendered by NF.
	 * @return {void}
	 */
	function initOnContainer(container) {
		if (!config.endpoint) {
			return;
		}

		var form = container.closest('form');
		if (!form) {
			return;
		}

		// Remove static preview placeholder injected by the Underscore template.
		container.innerHTML = '';

		Gaitcha.init(form, config.endpoint, {
			label: config.defaultLabel || '',
			container: container,
			theme: config.theme || 'light'
		});

		trackedContainers.push(container);
	}

	/**
	 * Scans the DOM for gaitcha containers and initializes each one.
	 *
	 * @return {void}
	 */
	function scanContainers() {
		var containers = document.querySelectorAll('.nf-gaitcha-container[data-gaitcha-container]');

		for (var i = 0; i < containers.length; i++) {
			initOnContainer(containers[i]);
		}
	}

	// Ninja Forms renders via Backbone after DOM load.
	// Use MutationObserver to detect when gaitcha containers appear.
	var observer = new MutationObserver(function handleMutations(mutations) {
		for (var i = 0; i < mutations.length; i++) {
			for (var j = 0; j < mutations[i].addedNodes.length; j++) {
				var node = mutations[i].addedNodes[j];
				if (node.nodeType !== 1) {
					continue;
				}
				if (node.matches && node.matches('.nf-gaitcha-container[data-gaitcha-container]')) {
					initOnContainer(node);
				}
				if (node.querySelectorAll) {
					var nested = node.querySelectorAll('.nf-gaitcha-container[data-gaitcha-container]');
					for (var k = 0; k < nested.length; k++) {
						initOnContainer(nested[k]);
					}
				}
			}
		}
	});

	observer.observe(document.body || document.documentElement, {
		childList: true,
		subtree: true
	});

	// Also scan on DOMContentLoaded as a fallback.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', scanContainers);
	} else {
		scanContainers();
	}

	// NF submits via jQuery AJAX to admin-ajax.php (action=nf_ajax_submit).
	// The hidden gaitcha fields are in the DOM but not in NF's Backbone
	// data model, so they're not included in the AJAX payload by default.
	// We intercept the request to append them.
	jQuery.ajaxPrefilter(function appendGaitchaFields(options) {
		if (!options.data || typeof options.data !== 'string') {
			return;
		}

		if (options.data.indexOf('nf_ajax_submit') === -1) {
			return;
		}

		for (var i = 0; i < trackedContainers.length; i++) {
			var inputs = trackedContainers[i].querySelectorAll('input');

			for (var j = 0; j < inputs.length; j++) {
				var input = inputs[j];
				if (!input.name) {
					continue;
				}

				var value = input.type === 'checkbox'
					? (input.checked ? 'on' : '')
					: input.value;

				if (value) {
					options.data += '&' + encodeURIComponent(input.name) + '=' + encodeURIComponent(value);
				}
			}
		}
	});

	/**
	 * Resets Gaitcha after a Ninja Forms validation error.
	 *
	 * NF shows errors via Backbone views without re-rendering the form.
	 * Detect failed submissions from the AJAX response and reset.
	 *
	 * @param {Object} event    jQuery AJAX complete event.
	 * @param {Object} xhr      The XMLHttpRequest object.
	 * @param {Object} settings jQuery AJAX settings.
	 * @return {void}
	 */
	jQuery(document).ajaxComplete(function handleNFAjaxComplete(event, xhr, settings) {
		if (!settings || !settings.data || typeof settings.data !== 'string') {
			return;
		}

		if (settings.data.indexOf('nf_ajax_submit') === -1) {
			return;
		}

		var response;
		try {
			response = JSON.parse(xhr.responseText);
		} catch (e) {
			return;
		}

		// NF puts errors at response.errors (not response.data.errors).
		var hasErrors = response
			&& response.errors
			&& typeof response.errors === 'object'
			&& Object.keys(response.errors).length > 0;

		if (hasErrors) {
			for (var i = 0; i < trackedContainers.length; i++) {
				var form = trackedContainers[i].closest('form');
				if (form) {
					Gaitcha.reset(form);
				}
			}
		}
	});
})();
