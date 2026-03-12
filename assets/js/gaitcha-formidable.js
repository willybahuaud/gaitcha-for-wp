/**
 * Gaitcha adapter for Formidable Forms.
 *
 * Detects gaitcha containers rendered by Formidable and initializes
 * the Gaitcha captcha inside each one. Supports AJAX form resets
 * via the frmPageChanged and frmFormComplete events.
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
			theme: config.theme || 'light'
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

	// Init on DOMContentLoaded.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', scanContainers);
	} else {
		scanContainers();
	}

	// Re-scan after Formidable AJAX events (multi-page forms, form reset).
	document.addEventListener('frmPageChanged', scanContainers);
	document.addEventListener('frmFormComplete', scanContainers);

	// Reset after validation errors.
	// Formidable fires frmFormErrors (jQuery event) when server-side
	// validation fails — the form stays in DOM with errors shown inline.
	if (typeof jQuery !== 'undefined') {
		/**
		 * Resets Gaitcha after a Formidable validation error.
		 *
		 * @param {Object}      event  jQuery event object.
		 * @param {jQuery}      $form  jQuery object of the form.
		 * @param {Object}      errors Error object from Formidable.
		 * @return {void}
		 */
		jQuery(document).on('frmFormErrors', function handleFormidableErrors(event, $form, errors) {
			if ($form && $form.length) {
				Gaitcha.reset($form[0]);
			}
		});
	}
})();
