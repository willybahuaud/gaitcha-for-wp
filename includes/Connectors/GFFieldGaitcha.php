<?php
/**
 * Gravity Forms field type for Gaitcha.
 *
 * Extends GF_Field to register a captcha checkbox field
 * with proper editor config and frontend rendering.
 *
 * @package GaitchaWP\Connectors
 */

namespace GaitchaWP\Connectors;

use GaitchaWP\WidgetPreview;

defined( 'ABSPATH' ) || exit;

/**
 * Class GFFieldGaitcha
 */
class GFFieldGaitcha extends \GF_Field {

	/**
	 * Field type identifier.
	 *
	 * @var string
	 */
	public $type = 'gaitcha';

	/**
	 * Field label shown in the GF editor.
	 *
	 * Always "Gaitcha" — the captcha label displayed inside
	 * the widget is managed by JS and translated via l10n.
	 *
	 * @var string
	 */
	public $label = 'Gaitcha';

	/**
	 * Returns the field title for the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return 'Gaitcha';
	}

	/**
	 * Returns the field icon for the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--check-box';
	}

	/**
	 * Returns the field description for the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_html__( 'Adds a Gaitcha behavioral captcha checkbox to your form.', 'gaitcha-for-wp' );
	}

	/**
	 * Returns the editor button config to place Gaitcha in the "Advanced Fields" group.
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'advanced_fields',
			'text'  => $this->get_form_editor_field_title(),
			'icon'  => $this->get_form_editor_field_icon(),
		);
	}

	/**
	 * Returns the field settings to display in the editor sidebar.
	 *
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return array(
			'description_setting',
			'css_class_setting',
			'conditional_logic_field_setting',
			'error_message_setting',
		);
	}

	/**
	 * Renders the field HTML on the frontend.
	 *
	 * Outputs a container matching native GF checkbox markup.
	 * The gaitcha JS adapter injects the checkbox inside the gchoice div.
	 *
	 * @param array      $form  Form data.
	 * @param string     $value Current value.
	 * @param array|null $entry Entry data.
	 * @return string HTML output.
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id  = absint( $form['id'] );
		$field_id = (int) $this->id;

		if ( $this->is_form_editor() ) {
			return sprintf(
				'<div class="ginput_container ginput_container_checkbox"><div class="gfield_checkbox">%s</div></div>',
				WidgetPreview::render()
			);
		}

		return sprintf(
			'<div class="ginput_container ginput_container_checkbox">'
			. '<div class="gfield_checkbox" id="input_%d_%d">'
			. '<div class="gchoice" id="gaitcha_%d_%d" data-gaitcha-container="gaitcha_%d_%d"></div>'
			. '</div></div>',
			$form_id,
			$field_id,
			$form_id,
			$field_id,
			$form_id,
			$field_id
		);
	}

	/**
	 * Prevents Gaitcha from saving a value to the entry.
	 *
	 * @param string     $value      Submitted value.
	 * @param array      $form       Form data.
	 * @param string     $input_name Input name.
	 * @param int        $lead_id    Entry ID.
	 * @param array      $lead       Entry data.
	 * @return string Empty string.
	 */
	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {
		return '';
	}
}
