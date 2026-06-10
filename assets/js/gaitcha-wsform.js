/**
 * Gaitcha adapter for WS Form.
 *
 * Detects Gaitcha field containers rendered by WS Form and initializes
 * the Gaitcha captcha inside each one. Supports dynamically rendered
 * forms (AJAX, popups) via the wsf-rendered event.
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
	 * @param {HTMLElement} container The gaitcha container rendered by WS Form.
	 * @return {void}
	 */
	function initOnContainer(container) {
		if (!config.endpoint) {
			return;
		}

		// Guard par container : le re-scan (wsf-rendered) repasserait par
		// innerHTML = '' et effacerait le placeholder injecte par le core.
		// Gaitcha.init() est double-init safe pour le form, pas pour ca.
		if (container.hasAttribute('data-gaitcha-bound')) {
			return;
		}

		var form = container.closest('form');
		if (!form) {
			return;
		}

		container.setAttribute('data-gaitcha-bound', '1');

		// Remove static preview placeholder injected by mask_field.
		container.innerHTML = '';

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
		var containers = document.querySelectorAll('.wsf-gaitcha-container[data-gaitcha-container]');

		containers.forEach(function forEachContainer(container) {
			initOnContainer(container);
		});
	}

	/**
	 * Handles the wsf-rendered jQuery event for dynamically loaded forms.
	 *
	 * @param {Object} event      jQuery event object.
	 * @param {Object} formObject WS Form rendered object.
	 * @return {void}
	 */
	function handleWsfRendered(event, formObject) {
		if (formObject && formObject.form_canvas_obj) {
			var formEl = formObject.form_canvas_obj.closest('form');
			if (formEl) {
				var containers = formEl.querySelectorAll('.wsf-gaitcha-container[data-gaitcha-container]');
				containers.forEach(function forEachContainer(container) {
					initOnContainer(container);
				});
				return;
			}
		}

		// Fallback: re-scan all containers.
		scanContainers();
	}

	// Init on DOMContentLoaded.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', scanContainers);
	} else {
		scanContainers();
	}

	// Listen for dynamically rendered WS Forms via jQuery event.
	if (typeof jQuery !== 'undefined') {
		jQuery(document).on('wsf-rendered', handleWsfRendered);

		/**
		 * Resets Gaitcha after a WS Form validation error.
		 *
		 * WS Form may show inline errors without triggering wsf-rendered.
		 * The wsf-error event fires on validation failure.
		 *
		 * @param {Object} event      jQuery event object.
		 * @param {Object} formObject WS Form object.
		 * @return {void}
		 */
		jQuery(document).on('wsf-error', function handleWsfError(event, formObject) {
			var formEl = null;

			if (formObject && formObject.form_canvas_obj) {
				formEl = formObject.form_canvas_obj.closest('form');
			}

			if (!formEl) {
				// Fallback: reset all known gaitcha forms.
				var containers = document.querySelectorAll('.wsf-gaitcha-container[data-gaitcha-container]');
				containers.forEach(function forEachContainer(container) {
					var form = container.closest('form');
					if (form) {
						Gaitcha.reset(form);
					}
				});
				return;
			}

			Gaitcha.reset(formEl);
		});
	}
})();
