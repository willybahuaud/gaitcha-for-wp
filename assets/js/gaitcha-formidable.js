/**
 * Gaitcha adapter for Formidable Forms.
 *
 * Detects gaitcha containers rendered by Formidable and initializes
 * the Gaitcha captcha inside each one. Supports AJAX form resets
 * via the frmPageChanged and frmFormComplete jQuery events.
 *
 * @package GaitchaWP
 */
(function () {
	'use strict';

	/** @type {{ endpoint: string, defaultLabel: string, theme: string }} */
	var config = window.gaitchaWPConfig || {};

	/**
	 * Initializes Gaitcha on a single container element.
	 *
	 * @param {HTMLElement} container The gaitcha container rendered by Formidable.
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
			theme: config.theme || 'light',
			style: config.style || 'default'
		});
	}

	/**
	 * Scans the DOM for gaitcha containers and initializes each one.
	 *
	 * @return {void}
	 */
	function scanContainers() {
		var containers = document.querySelectorAll('.frm-gaitcha-container[data-gaitcha-container]');

		containers.forEach(function forEachContainer(container) {
			initOnContainer(container);
		});
	}

	/**
	 * Resets Gaitcha after a Formidable validation error.
	 *
	 * Formidable fires frmFormErrors (jQuery event) when server-side
	 * validation fails — the form stays in DOM with errors shown inline.
	 *
	 * @param {Object}             event jQuery event object.
	 * @param {HTMLElement|jQuery} form  The form: a DOM element in Formidable 7, a jQuery object is accepted too.
	 * @return {void}
	 */
	function handleFormidableErrors(event, form) {
		var element = form && form.jquery ? form[0] : form;

		if (element && element.tagName === 'FORM') {
			Gaitcha.reset(element);
		}
	}

	/**
	 * Binds Formidable's events, which are jQuery events (native
	 * listeners never receive them).
	 *
	 * Called once the document is parsed: this script is enqueued on
	 * every page without a jQuery dependency, so it may run before
	 * jQuery is defined.
	 *
	 * @return {void}
	 */
	function bindFormidableEvents() {
		if (typeof jQuery === 'undefined') {
			return;
		}

		// Reset after validation errors.
		jQuery(document).on('frmFormErrors', handleFormidableErrors);

		// Re-scan after Formidable replaced the form by AJAX (multi-page forms, form shown again after success).
		jQuery(document).on('frmPageChanged frmFormComplete', scanContainers);
	}

	/**
	 * Initializes the containers and binds the Formidable events.
	 *
	 * @return {void}
	 */
	function start() {
		scanContainers();
		bindFormidableEvents();
	}

	// Init on DOMContentLoaded.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
