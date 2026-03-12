<?php
/**
 * Formidable Forms field type for Gaitcha.
 *
 * Extends FrmFieldType to register a proper field with
 * correct settings, rendering and validation.
 *
 * @package GaitchaWP\Connectors
 */

namespace GaitchaWP\Connectors;

use GaitchaWP\WidgetPreview;

defined( 'ABSPATH' ) || exit;

/**
 * Class FrmFieldGaitcha
 */
class FrmFieldGaitcha extends \FrmFieldType {

	/**
	 * Field type identifier.
	 *
	 * @var string
	 */
	protected $type = 'gaitcha';

	/**
	 * No native input element — gaitcha injects its own via JS.
	 *
	 * @var bool
	 */
	protected $has_input = false;

	/**
	 * Returns field-specific settings visibility.
	 *
	 * Hides irrelevant settings (required, default value, etc.).
	 *
	 * @return array
	 */
	protected function field_settings_for_type() {
		return array(
			'required' => false,
			'default'  => false,
			'invalid'  => true,
		);
	}

	/**
	 * Returns default field options.
	 *
	 * Sets label to 'none' to avoid Formidable rendering its own label
	 * (gaitcha injects its own checkbox + label via JS).
	 *
	 * @return array
	 */
	protected function extra_field_opts() {
		return array(
			'label' => 'none',
		);
	}

	/**
	 * Renders the field in the admin form builder.
	 *
	 * @return string
	 */
	protected function include_form_builder_file() {
		return '';
	}

	/**
	 * Renders the field preview in the admin form builder.
	 *
	 * @param string $name Field name.
	 * @return void
	 */
	public function show_on_form_builder( $name = '' ) {
		echo '<div class="frm_html_field_placeholder">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WidgetPreview::render() handles escaping internally.
		echo WidgetPreview::render();
		echo '</div>';
	}

	/**
	 * Renders the gaitcha container on the frontend.
	 *
	 * @param array $args           Field arguments.
	 * @param array $shortcode_atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function front_field_input( $args, $shortcode_atts ) {
		$field_id = 'frm-gaitcha-' . absint( $this->get_field_column( 'id' ) );

		return sprintf(
			'<div class="frm_opt_container"><div class="frm-gaitcha-container" id="%s" data-gaitcha-container="%s"></div></div>',
			esc_attr( $field_id ),
			esc_attr( $field_id )
		);
	}

	/**
	 * Enqueues gaitcha scripts when the field is rendered.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	protected function load_field_scripts( $args ) {
		// Scripts are enqueued globally by FormidableConnector::enqueue_scripts().
	}
}
