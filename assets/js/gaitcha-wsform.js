/**
 * Gaitcha adapter for WS Form.
 *
 * Initializes Gaitcha on every WS Form, including dynamically
 * rendered forms (AJAX, popups) via the wsf-rendered event.
 *
 * @package GaitchaWP
 */
(function () {
	'use strict';

	/** @type {{ endpoint: string, label: string }} */
	var config = window.gaitchaWPConfig || {};

	/**
	 * Initializes Gaitcha on a single form element.
	 *
	 * @param {HTMLFormElement} form - The WS Form element.
	 * @return {void}
	 */
	function initOnForm(form) {
		if (!config.endpoint) {
			return;
		}

		// Gaitcha.init() is double-init safe (checks data-gaitcha-initialized).
		Gaitcha.init(form, config.endpoint, { label: config.label });
	}

	/**
	 * Scans the DOM for WS Form instances and initializes Gaitcha on each.
	 *
	 * @return {void}
	 */
	function scanForms() {
		var forms = document.querySelectorAll('form.wsf-form');

		forms.forEach(function forEachForm(form) {
			initOnForm(form);
		});
	}

	/**
	 * Handles the wsf-rendered jQuery event for dynamically loaded forms.
	 *
	 * @param {Object} event - jQuery event object.
	 * @param {Object} formObject - WS Form rendered object.
	 * @return {void}
	 */
	function handleWsfRendered(event, formObject) {
		if (formObject && formObject.form_canvas_obj) {
			var form = formObject.form_canvas_obj.closest('form');
			if (form) {
				initOnForm(form);
				return;
			}
		}

		// Fallback: re-scan all forms.
		scanForms();
	}

	// Init on DOMContentLoaded.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', scanForms);
	} else {
		scanForms();
	}

	// Listen for dynamically rendered WS Forms via jQuery event.
	if (typeof jQuery !== 'undefined') {
		jQuery(document).on('wsf-rendered', handleWsfRendered);
	}
})();
