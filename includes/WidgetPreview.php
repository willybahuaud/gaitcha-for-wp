<?php
/**
 * Static widget preview for form editor contexts.
 *
 * Renders a non-interactive HTML replica of the Gaitcha frontend
 * widget, using inline styles so it works in any admin editor
 * without requiring external CSS.
 *
 * @package GaitchaWP
 */

namespace GaitchaWP;

defined( 'ABSPATH' ) || exit;

/**
 * Class WidgetPreview
 */
class WidgetPreview {

	/**
	 * Shield SVG markup (stroke-based, currentColor).
	 *
	 * @var string
	 */
	const SHIELD_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:20px;height:20px;opacity:0.35;color:#8a9099;display:block;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';

	/**
	 * Returns static HTML for the Gaitcha widget preview.
	 *
	 * Renders a non-interactive replica of the frontend widget
	 * (idle state) for use in form builder editors.
	 *
	 * @param string $label      Label text for the widget.
	 * @param string $label_attr Extra HTML attributes for the label span (e.g. class for live preview).
	 * @return string HTML output.
	 */
	public static function render( $label = '', $label_attr = '' ) {
		if ( empty( $label ) ) {
			$label = __( 'I\'m a real person', 'gaitcha-for-wp' );
		}

		$label_attr_str = $label_attr ? ' ' . $label_attr : '';

		return '<div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,\'Helvetica Neue\',Arial,sans-serif;background:#fff;border:1.5px solid #e2e6ea;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.07),0 4px 12px rgba(0,0,0,0.05);padding:14px 16px 12px;max-width:260px;display:flex;align-items:center;gap:11px;box-sizing:border-box;line-height:1;">'
			// Checkbox visual.
			. '<div style="width:22px;height:22px;min-width:22px;border-radius:5px;border:1.5px solid #e2e6ea;background:#fff;box-sizing:border-box;"></div>'
			// Content section.
			. '<div style="display:flex;align-items:center;gap:8px;flex:1;min-width:0;overflow:hidden;">'
			// Label.
			. '<span' . $label_attr_str . ' style="font-size:14px;font-weight:500;color:#1a1d23;line-height:1.3;flex:1;min-width:0;text-align:left;">'
			. esc_html( $label )
			. '</span>'
			// Badge.
			. '<div style="display:flex;flex-direction:column;align-items:center;gap:3px;flex-shrink:0;">'
			. self::SHIELD_SVG
			. '<span style="font-family:ui-monospace,\'SF Mono\',Menlo,Consolas,\'Liberation Mono\',monospace;font-size:9px;letter-spacing:0.04em;color:#8a9099;line-height:1;opacity:0.7;text-transform:lowercase;">gaitcha</span>'
			. '</div>'
			. '</div>'
			. '</div>';
	}
}
