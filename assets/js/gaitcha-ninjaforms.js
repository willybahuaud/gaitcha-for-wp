/**
 * Gaitcha adapter for Ninja Forms.
 *
 * Ninja Forms renders fields client-side via Backbone.js.
 * This adapter waits for NF to render, then scans for gaitcha
 * containers and initializes the captcha.
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
	 * @param {HTMLElement} container The gaitcha container rendered by NF.
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
				checkbox: 'nf-element',
				label: ''
			}
		});
	}

	/**
	 * Scans the DOM for gaitcha containers and initializes each one.
	 *
	 * @return {void}
	 */
	function scanContainers() {
		var containers = document.querySelectorAll('.nf-gaitcha-container[data-gaitcha-container]');

		containers.forEach(function forEachContainer(container) {
			initOnContainer(container);
		});
	}

	// Ninja Forms renders via Backbone after DOM load.
	// Use MutationObserver to detect when gaitcha containers appear.
	var observer = new MutationObserver(function handleMutations(mutations) {
		for (var i = 0; i < mutations.length; i++) {
			for (var j = 0; j < mutations[i].addedNodes.length; j++) {
				var node = mutations[i].addedNodes[j];
				if (node.nodeType !== 1) {
					continue;
				}
				if (node.matches && node.matches('.nf-gaitcha-container[data-gaitcha-container]')) {
					initOnContainer(node);
				}
				var nested = node.querySelectorAll && node.querySelectorAll('.nf-gaitcha-container[data-gaitcha-container]');
				if (nested) {
					nested.forEach(function forEachNested(el) {
						initOnContainer(el);
					});
				}
			}
		}
	});

	observer.observe(document.body || document.documentElement, {
		childList: true,
		subtree: true
	});

	// Also scan on DOMContentLoaded as a fallback.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', scanContainers);
	} else {
		scanContainers();
	}
})();
