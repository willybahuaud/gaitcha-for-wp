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
	 * Initializes containers added after page load (forms injected in a popin, for instance).
	 *
	 * @param {MutationRecord[]} mutations Observed DOM changes.
	 * @return {void}
	 */
	function handleDomMutations(mutations) {
		var hasNewElements = mutations.some(function hasAddedElement(mutation) {
			return mutation.addedNodes.length > 0;
		});

		if (hasNewElements) {
			scanContainers();
		}
	}

	new MutationObserver(handleDomMutations).observe(document.body, { childList: true, subtree: true });

	/**
	 * Resets Gaitcha once CF7 has answered a submission.
	 *
	 * CF7 never replaces the form DOM. After a failure (wpcf7invalid,
	 * wpcf7spam, wpcf7mailfailed) the checkbox stays checked with stale
	 * behavioral data — the user can't retry without a reset. After a
	 * success (wpcf7mailsent) it stays checked with a consumed token:
	 * a second message from the same page would be flagged as spam.
	 *
	 * @param {Event} event CF7 custom event on the form (6.x) or wrapper div (5.x).
	 * @return {void}
	 */
	function handleSubmissionResult(event) {
		var target = event.target;
		var form = target.tagName === 'FORM'
			? target
			: target.querySelector('form');

		if (form) {
			Gaitcha.reset(form);
		}
	}

	// Reset after each answer — form stays in DOM, needs fresh captcha.
	document.addEventListener('wpcf7invalid', handleSubmissionResult);
	document.addEventListener('wpcf7spam', handleSubmissionResult);
	document.addEventListener('wpcf7mailfailed', handleSubmissionResult);
	document.addEventListener('wpcf7mailsent', handleSubmissionResult);
})();
