/**
 * Gaitcha adapter for Contact Form 7.
 *
 * Detects [gaitcha] containers rendered by CF7 and initializes
 * the Gaitcha captcha inside each one. Supports AJAX form resets
 * via the wpcf7mailsent and wpcf7invalid events.
 *
 * @package GaitchaWP
 */
(function () {
	'use strict';

	/** @type {{ endpoint: string, defaultLabel: string, theme: string }} */
	var config = window.gaitchaWPConfig || {};

	/**
	 * Reads the label from the container's data attribute.
	 *
	 * @param {HTMLElement} container The gaitcha container element.
	 * @return {string} The label text, or the default label.
	 */
	function readFieldLabel(container) {
		var label = container.getAttribute('data-gaitcha-label');

		if (label && label.trim()) {
			return label.trim();
		}

		return config.defaultLabel || '';
	}

	/**
	 * Initializes Gaitcha on a single container element.
	 *
	 * @param {HTMLElement} container The gaitcha container rendered by CF7.
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
			label: readFieldLabel(container),
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
		var containers = document.querySelectorAll('.wpcf7-gaitcha[data-gaitcha-container]');

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

	// Re-scan after CF7 AJAX submission (CF7 replaces form HTML, new containers appear).
	document.addEventListener('wpcf7submit', scanContainers);

	/**
	 * Resets Gaitcha after a CF7 validation error.
	 *
	 * CF7 does not replace the form DOM on validation failures
	 * (wpcf7invalid, wpcf7spam, wpcf7mailfailed). The checkbox
	 * stays checked with stale behavioral data and a potentially
	 * consumed token — the user can't retry without a reset.
	 *
	 * @param {Event} event CF7 custom event on the form (6.x) or wrapper div (5.x).
	 * @return {void}
	 */
	function handleValidationError(event) {
		var target = event.target;
		var form = target.tagName === 'FORM'
			? target
			: target.querySelector('form');

		if (form) {
			Gaitcha.reset(form);
		}
	}

	// Reset after validation errors — form stays in DOM, needs fresh captcha.
	document.addEventListener('wpcf7invalid', handleValidationError);
	document.addEventListener('wpcf7spam', handleValidationError);
	document.addEventListener('wpcf7mailfailed', handleValidationError);
})();
