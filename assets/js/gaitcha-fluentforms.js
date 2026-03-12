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

	/** @type {{ endpoint: string, defaultLabel: string, theme: string }} */
	var config = window.gaitchaWPConfig || {};

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

	/**
	 * Resets Gaitcha after a Fluent Forms validation error.
	 *
	 * Fluent Forms shows errors inline without re-rendering the form.
	 * It may use jQuery AJAX or fetch — we handle both approaches.
	 *
	 * @param {HTMLFormElement} form The form to reset.
	 * @return {void}
	 */
	function resetFormGaitcha(form) {
		Gaitcha.reset(form);
	}

	// Approach 1: intercept jQuery AJAX responses (Fluent Forms Pro).
	if (typeof jQuery !== 'undefined') {
		jQuery(document).ajaxComplete(function handleFluentAjaxComplete(event, xhr, settings) {
			if (!settings || !settings.data || typeof settings.data !== 'string') {
				return;
			}

			if (settings.data.indexOf('action=fluentform_submit') === -1) {
				return;
			}

			var response;
			try {
				response = JSON.parse(xhr.responseText);
			} catch (e) {
				return;
			}

			if (response && response.errors) {
				var containers = document.querySelectorAll('.ff-el-form-check[data-gaitcha-container]');
				for (var i = 0; i < containers.length; i++) {
					var form = containers[i].closest('form');
					if (form) {
						resetFormGaitcha(form);
					}
				}
			}
		});
	}

	// Approach 2: MutationObserver for non-jQuery submissions (fetch-based FF).
	// Watches for .ff-el-is-error class appearing on field wrappers after submit.
	// More reliable than a fixed timeout — reacts as soon as FF renders errors.

	/**
	 * Observes a Fluent Forms form for validation error classes.
	 *
	 * Watches for .ff-el-is-error appearing on any descendant.
	 * Disconnects after first match or after 10s timeout (safety net).
	 *
	 * @param {HTMLFormElement} form The Fluent Forms form element.
	 * @return {void}
	 */
	function observeFormErrors(form) {
		var observer = new MutationObserver(function handleMutations(mutations) {
			for (var i = 0; i < mutations.length; i++) {
				if (mutations[i].target.classList && mutations[i].target.classList.contains('ff-el-is-error')) {
					resetFormGaitcha(form);
					observer.disconnect();
					return;
				}
			}
		});

		observer.observe(form, {
			attributes: true,
			attributeFilter: ['class'],
			subtree: true
		});

		// Safety net: disconnect after 10s to prevent leaks.
		setTimeout(function disconnectObserver() {
			observer.disconnect();
		}, 10000);
	}

	document.addEventListener('submit', function handleFluentSubmit(event) {
		var form = event.target;
		if (!form || !form.classList.contains('frm-fluent-form')) {
			return;
		}

		observeFormErrors(form);
	}, true);
})();
