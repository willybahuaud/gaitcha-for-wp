/**
 * Gaitcha adapter for WPForms.
 *
 * Detects gaitcha containers rendered by WPForms and initializes
 * the Gaitcha captcha inside each one. Uses native WPForms checkbox
 * classes for consistent styling.
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
	 * @param {HTMLElement} container The gaitcha container rendered by WPForms.
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
		var containers = document.querySelectorAll('.wpforms-gaitcha-container[data-gaitcha-container]');

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

	// Re-scan after WPForms AJAX form submissions (jQuery event, loaded by WPForms).
	if (typeof jQuery !== 'undefined') {
		jQuery(document).on('wpformsAjaxSubmitSuccessConfirmation', scanContainers);

		/**
		 * Resets Gaitcha after a WPForms server-side validation error.
		 *
		 * WPForms shows errors inline without re-rendering the form.
		 * The captcha stays checked with stale data — intercept AJAX
		 * responses to detect failed submissions and reset.
		 *
		 * @param {Object} event    jQuery AJAX complete event.
		 * @param {Object} xhr      The XMLHttpRequest object.
		 * @param {Object} settings jQuery AJAX settings.
		 * @return {void}
		 */
		jQuery(document).ajaxComplete(function handleWPFormsAjaxComplete(event, xhr, settings) {
			// WPForms sends FormData (not a string) — check via .get().
			var isWPFormsSubmit = false;
			if (settings && settings.data instanceof FormData) {
				isWPFormsSubmit = settings.data.get('action') === 'wpforms_submit';
			} else if (settings && typeof settings.data === 'string') {
				isWPFormsSubmit = settings.data.indexOf('action=wpforms_submit') !== -1;
			}

			if (!isWPFormsSubmit) {
				return;
			}

			var response;
			try {
				response = JSON.parse(xhr.responseText);
			} catch (e) {
				return;
			}

			// Only reset on failed submissions (validation errors).
			if (response && !response.success) {
				var containers = document.querySelectorAll('.wpforms-gaitcha-container[data-gaitcha-container]');
				for (var i = 0; i < containers.length; i++) {
					var form = containers[i].closest('form');
					if (form) {
						Gaitcha.reset(form);
					}
				}
			}
		});
	}
})();
