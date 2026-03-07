/**
 * Gaitcha adapter for Fluent Forms.
 *
 * Detects gaitcha containers rendered by Fluent Forms and initializes
 * the Gaitcha captcha inside each one. Uses native Fluent Forms checkbox
 * classes for consistent styling.
 *
 * @package GaitchaWP
 */
(function () {
	'use strict';

	/** @type {{ endpoint: string, defaultLabel: string }} */
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
	 * @param {HTMLElement} container The gaitcha container rendered by Fluent Forms.
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
			label: readFieldLabel(container),
			container: container,
			classes: {
				field: '',
				checkbox: 'ff-el-form-check-input ff-el-form-check-checkbox',
				label: 'ff-el-form-check-label'
			}
		});
	}

	/**
	 * Scans the DOM for gaitcha containers and initializes each one.
	 *
	 * @return {void}
	 */
	function scanContainers() {
		var containers = document.querySelectorAll('.ff-el-form-check[data-gaitcha-container]');

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
})();
