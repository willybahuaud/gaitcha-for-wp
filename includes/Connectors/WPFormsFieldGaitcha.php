<?php
/**
 * WPForms field type for Gaitcha.
 *
 * Extends WPForms_Field to register a captcha checkbox field
 * with proper builder config and frontend rendering.
 *
 * @package GaitchaWP\Connectors
 */

namespace GaitchaWP\Connectors;

use GaitchaWP\WidgetPreview;

defined( 'ABSPATH' ) || exit;

/**
 * Class WPFormsFieldGaitcha
 */
class WPFormsFieldGaitcha extends \WPForms_Field {

	/**
	 * Initializes field properties.
	 *
	 * @return void
	 */
	public function init() {
		$this->name     = 'Gaitcha';
		$this->keywords = __( 'captcha spam gaitcha', 'gaitcha-for-wp' );
		$this->type     = 'gaitcha';
		$this->icon     = 'fa-check-square-o';
		$this->order    = 200;
		$this->group    = 'standard';

		$this->default_settings = array(
			'label'      => 'Gaitcha',
			'label_hide' => '1',
		);
	}

	/**
	 * Renders field options in the builder sidebar.
	 *
	 * @param array $field Field data.
	 * @return void
	 */
	public function field_options( $field ) {
		$this->field_option( 'basic-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'description', $field );
		$this->field_option( 'basic-options', $field, array( 'markup' => 'close' ) );

		$this->field_option( 'advanced-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'css', $field );
		$this->field_option( 'advanced-options', $field, array( 'markup' => 'close' ) );
	}

	/**
	 * Renders the field preview in the builder.
	 *
	 * @param array $field Field data.
	 * @return void
	 */
	public function field_preview( $field ) {
		echo '<div class="primary-input" style="padding:8px 0;">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WidgetPreview::render() handles escaping internally.
		echo WidgetPreview::render();
		echo '</div>';

		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Renders the field on the frontend.
	 *
	 * Outputs a container matching native WPForms checkbox markup.
	 * The gaitcha JS adapter injects the checkbox inside.
	 *
	 * @param array $field      Field data.
	 * @param array $deprecated Deprecated.
	 * @param array $form_data  Form data.
	 * @return void
	 */
	public function field_display( $field, $deprecated, $form_data ) {
		$form_id  = absint( $form_data['id'] );
		$field_id = absint( $field['id'] );

		$container_id = 'wpforms-gaitcha-' . $form_id . '-' . $field_id;

		printf(
			'<div class="wpforms-gaitcha-container" id="%s" data-gaitcha-container="%s"></div>',
			esc_attr( $container_id ),
			esc_attr( $container_id )
		);
	}
}
