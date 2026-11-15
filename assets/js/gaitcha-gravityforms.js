/**
 * Gaitcha adapter for Gravity Forms.
 *
 * Detects gaitcha containers rendered by Gravity Forms and initializes
 * the Gaitcha captcha inside each one. Uses native GF checkbox classes
 * for consistent styling.
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
	 * @param {HTMLElement} container The gaitcha container rendered by GF.
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

		syncFieldState(container);
	}

	/**
	 * Mirrors the Gravity Forms field state on the widget for assistive technologies.
	 *
	 * GF adds aria-required, aria-invalid and aria-describedby to the inputs it
	 * renders itself; the Gaitcha checkbox is rendered by JS, so it gets them here.
	 * The field cannot be submitted without the check, hence aria-required.
	 *
	 * @param {HTMLElement} container The gaitcha container rendered by GF.
	 * @return {void}
	 */
	function syncFieldState(container) {
		var widget = container.querySelector('.gaitcha-widget');
		var field = container.closest('.gfield');
		if (!widget || !field) {
			return;
		}

		widget.setAttribute('aria-required', 'true');

		var describedBy = [];
		var description = field.querySelector('.gfield_description:not(.gfield_validation_message)');
		if (description && description.id) {
			describedBy.push(description.id);
		}

		var message = field.querySelector('.gfield_validation_message');
		var isInvalid = field.classList.contains('gfield_error') && !!message;
		if (isInvalid && message.id) {
			describedBy.push(message.id);
		}

		if (isInvalid) {
			widget.setAttribute('aria-invalid', 'true');
		} else {
			widget.removeAttribute('aria-invalid');
		}

		if (describedBy.length) {
			widget.setAttribute('aria-describedby', describedBy.join(' '));
		} else {
			widget.removeAttribute('aria-describedby');
		}
	}

	/**
	 * Scans the DOM for gaitcha containers and initializes each one.
	 *
	 * @return {void}
	 */
	function scanContainers() {
		var containers = document.querySelectorAll('.gchoice[data-gaitcha-container]');

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

	// Re-scan and reset after GF AJAX form render (jQuery event, loaded by GF).
	// gform_post_render fires after validation errors AND successful submissions.
	// Two scenarios:
	// - GF replaces the entire form HTML: old instance is orphaned, scanContainers re-inits.
	// - GF keeps the form element (injects errors inline): reset() clears the widget.
	if (typeof jQuery !== 'undefined') {
		/**
		 * Resets and re-initializes Gaitcha after a Gravity Forms AJAX render.
		 *
		 * @param {Object} event    jQuery event object.
		 * @param {number} formId   The Gravity Forms form ID.
		 * @return {void}
		 */
		jQuery(document).on('gform_post_render', function handleGFormPostRender(event, formId) {
			var form = document.getElementById('gform_' + formId);
			if (form) {
				Gaitcha.reset(form);
			}
			scanContainers();
		});
	}
})();
