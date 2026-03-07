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

	/** @type {{ endpoint: string, defaultLabel: string }} */
	var config = window.gaitchaWPConfig || {};

	/**
	 * Reads the WS Form field label associated with a gaitcha container.
	 *
	 * WS Form renders a <label> before the field wrapper. The container's
	 * id matches the label's "for" attribute.
	 *
	 * @param {HTMLElement} container The gaitcha container element.
	 * @return {string} The label text, or the default label.
	 */
	function readFieldLabel(container) {
		var fieldId = container.id;
		if (!fieldId) {
			return config.defaultLabel || '';
		}

		var label = container.closest('form')
			? container.closest('form').querySelector('label[for="' + fieldId + '"]')
			: null;

		if (label && label.textContent.trim()) {
			return label.textContent.trim();
		}

		return config.defaultLabel || '';
	}

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

		var form = container.closest('form');
		if (!form) {
			return;
		}

		// Gaitcha.init() is double-init safe (checks data-gaitcha-initialized).
		var label = readFieldLabel(container);
		Gaitcha.init(form, config.endpoint, {
			label: label,
			container: container
		});
	}

	/**
	 * Scans the DOM for gaitcha containers and initializes each one.
	 *
	 * @return {void}
	 */
	function scanContainers() {
		var containers = document.querySelectorAll('[data-gaitcha-container]');

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
				var containers = formEl.querySelectorAll('[data-gaitcha-container]');
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
	}
})();
