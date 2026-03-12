/**
 * Gaitcha adapter for Elementor Pro Forms.
 *
 * Detects gaitcha containers rendered by the Elementor Pro connector
 * and initializes the Gaitcha captcha inside each one. Handles form
 * reset and error events triggered by Elementor's AJAX submission.
 *
 * @package GaitchaWP
 */
(function () {
	'use strict';

	/** @type {{ endpoint: string, defaultLabel: string, theme: string }} */
	var config = window.gaitchaWPConfig || {};

	// Elementor's .elementor-field-group is display:flex — the .elementor-field
	// wrapper needs explicit width for container-type:inline-size to resolve
	// through the widget chain.
	var fixStyle = document.createElement('style');
	fixStyle.textContent = '.elementor-field:has(.gaitcha-elementor-container) { width: 100%; }';
	document.head.appendChild(fixStyle);

	/**
	 * Initializes Gaitcha on a single container element.
	 *
	 * @param {HTMLElement} container The gaitcha container.
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

		// Gaitcha.init() is double-init safe (checks data-gaitcha-initialized).
		Gaitcha.init(form, config.endpoint, {
			label: config.defaultLabel || '',
			container: container,
			theme: config.theme || 'light'
		});
	}

	/**
	 * Scans the DOM for gaitcha containers and initializes each one.
	 *
	 * @return {void}
	 */
	function scanContainers() {
		var containers = document.querySelectorAll('.gaitcha-elementor-container[data-gaitcha-container]');

		containers.forEach(function forEachContainer(container) {
			initOnContainer(container);
		});
	}

	// Init on DOMContentLoaded.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', scanContainers);
	} else {
		scanContainers();
	}

	// Elementor Pro triggers jQuery events on the form element:
	// - 'reset' after a successful submission (form clears)
	// - 'error' after a validation failure
	// - 'submit_success' after a successful submission (with response data)
	if (typeof jQuery !== 'undefined') {
		/**
		 * Resets Gaitcha when the form resets or encounters an error.
		 *
		 * Elementor Pro fires 'reset' after success and 'error' after
		 * validation failure. In both cases Gaitcha must be reset so
		 * the user can re-check on the next attempt.
		 *
		 * @param {Object} event jQuery event object.
		 * @return {void}
		 */
		jQuery(document).on('reset error', '.elementor-form', function handleFormResetOrError(event) {
			Gaitcha.reset(this);
		});
	}
})();
