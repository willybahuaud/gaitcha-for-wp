<?php
/**
 * Elementor Pro Forms connector.
 *
 * Registers a "Gaitcha" field type in Elementor Pro's form widget,
 * renders the captcha container, enqueues scripts conditionally,
 * and validates submissions via the Gaitcha pipeline.
 *
 * Follows the same handler pattern as Elementor Pro's built-in
 * Honeypot_Handler and Recaptcha_Handler (not Field_Base).
 *
 * @package GaitchaWP\Connectors
 */

namespace GaitchaWP\Connectors;

use Gaitcha\Config;
use Gaitcha\ValidationOrchestrator;
use GaitchaWP\Endpoint;
use GaitchaWP\Settings;
use GaitchaWP\WidgetPreview;

defined( 'ABSPATH' ) || exit;

/**
 * Class ElementorProConnector
 */
class ElementorProConnector implements ConnectorInterface {

	/**
	 * Field type identifier used in Elementor Pro.
	 *
	 * @var string
	 */
	const FIELD_TYPE = 'gaitcha';

	/**
	 * Gaitcha configuration.
	 *
	 * @var Config
	 */
	private $config;

	/**
	 * REST endpoint instance.
	 *
	 * @var Endpoint
	 */
	private $endpoint;

	/**
	 * @param Config   $config   Gaitcha configuration.
	 * @param Endpoint $endpoint REST endpoint.
	 */
	public function __construct( Config $config, Endpoint $endpoint ) {
		$this->config   = $config;
		$this->endpoint = $endpoint;
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'elementor_pro/forms/field_types', array( $this, 'add_field_type' ) );
		add_filter( 'elementor_pro/forms/render/item', array( $this, 'filter_field_item' ), 10, 3 );
		add_action( 'elementor_pro/forms/render_field/' . self::FIELD_TYPE, array( $this, 'render_field' ), 10, 3 );
		add_action( 'elementor_pro/forms/validation', array( $this, 'validate' ), 10, 2 );
		add_action( 'elementor/element/form/section_form_fields/before_section_end', array( $this, 'update_controls' ) );

		// Enqueue scripts on the frontend via Elementor's hook.
		// Cannot rely on render_field() because Elementor caches
		// widget HTML — the action doesn't fire on cached page loads.
		add_action( 'elementor/frontend/after_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Enqueue editor script for the static preview in the form builder.
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );
	}

	/**
	 * Adds the Gaitcha field type to the form widget dropdown.
	 *
	 * @param array $field_types Registered field types.
	 * @return array Modified field types.
	 */
	public function add_field_type( $field_types ) {
		$field_types[ self::FIELD_TYPE ] = esc_html__( 'Gaitcha', 'gaitcha-for-wp' );

		return $field_types;
	}

	/**
	 * Hides the label for Gaitcha fields.
	 *
	 * Gaitcha renders its own label via JS,
	 * so we prevent Elementor from showing one.
	 *
	 * @param array  $item       Field config.
	 * @param int    $item_index Field index.
	 * @param object $widget     Form widget instance.
	 * @return array Modified field config.
	 */
	public function filter_field_item( $item, $item_index, $widget ) {
		if ( self::FIELD_TYPE === $item['field_type'] ) {
			$item['field_label'] = false;
		}

		return $item;
	}

	/**
	 * Renders the Gaitcha container on the frontend.
	 *
	 * Only outputs the container div — scripts are enqueued
	 * separately via the 'elementor/frontend/after_enqueue_scripts' hook.
	 *
	 * @param array  $item       Field config.
	 * @param int    $item_index Field index.
	 * @param object $widget     Form widget instance.
	 * @return void
	 */
	public function render_field( $item, $item_index, $widget ) {
		$container_id = 'gaitcha-elementor-' . esc_attr( $item['custom_id'] );

		// In the editor, Elementor uses PHP render for the initial view
		// (content_template JS only takes over on interaction).
		$inner = \Elementor\Plugin::$instance->editor->is_edit_mode()
			? WidgetPreview::render()
			: '';

		printf(
			'<div class="elementor-field" id="form-field-%s">'
			. '<div class="gaitcha-elementor-container" data-gaitcha-container="%s">%s</div>'
			. '</div>',
			esc_attr( $item['custom_id'] ),
			esc_attr( $container_id ),
			$inner
		);
	}

	/**
	 * Validates Gaitcha fields during form submission.
	 *
	 * Uses global validation hook (not field-specific) because
	 * Gaitcha validates against $_POST, not individual field values.
	 *
	 * @param object $record       Form record instance.
	 * @param object $ajax_handler Ajax handler instance.
	 * @return void
	 */
	public function validate( $record, $ajax_handler ) {
		$fields = $record->get_field( array( 'type' => self::FIELD_TYPE ) );

		if ( empty( $fields ) ) {
			return;
		}

		$field = current( $fields );

		$bypass_admin = apply_filters( 'gaitcha_bypass_admin', current_user_can( 'manage_options' ) );
		if ( $bypass_admin ) {
			$record->remove_field( $field['id'] );
			return;
		}

		$orchestrator = new ValidationOrchestrator( $this->config );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Gaitcha uses HMAC token validation, not nonces.
		$result = $orchestrator->validate( wp_unslash( $_POST ) );

		if ( ! $result->isAccepted() ) {
			$ajax_handler->add_error(
				$field['id'],
				esc_html__( 'Verification failed. Please try again.', 'gaitcha-for-wp' )
			);
		}

		// Don't include the gaitcha field in emails/actions.
		$record->remove_field( $field['id'] );
	}

	/**
	 * Hides irrelevant controls for Gaitcha fields in the editor.
	 *
	 * Removes "Required" and "Column Width" options since they
	 * don't apply to a captcha field.
	 *
	 * @param object $widget Form widget instance.
	 * @return void
	 */
	public function update_controls( $widget ) {
		if ( ! method_exists( $widget, 'get_unique_name' ) ) {
			return;
		}

		$elementor = \Elementor\Plugin::$instance;
		if ( ! $elementor || ! isset( $elementor->controls_manager ) ) {
			return;
		}

		$control_data = $elementor->controls_manager->get_control_from_stack(
			$widget->get_unique_name(),
			'form_fields'
		);

		if ( is_wp_error( $control_data ) ) {
			return;
		}

		foreach ( $control_data['fields'] as $index => $field ) {
			if ( in_array( $field['name'], array( 'required', 'width' ), true ) ) {
				$control_data['fields'][ $index ]['conditions']['terms'][] = array(
					'name'     => 'field_type',
					'operator' => '!in',
					'value'    => array( self::FIELD_TYPE ),
				);
			}
		}

		$widget->update_control( 'form_fields', $control_data );
	}

	/**
	 * Enqueues the editor script for the Gaitcha field preview.
	 *
	 * Provides a static widget preview in Elementor's form builder
	 * via the content_template JS hook system.
	 *
	 * @return void
	 */
	public function enqueue_editor_scripts() {
		$preview_html = wp_json_encode( WidgetPreview::render() );

		// Inline script attached to elementor-pro: runs after elementor-pro
		// loads but before DOMContentLoaded. The elementor global doesn't
		// exist yet at this point — it's created later during init.
		// We register a listener for elementor:init which fires after the
		// global is created but before widgets render.
		$inline_js = <<<JS
(function () {
	'use strict';
	var previewHtml = {$preview_html};
	function renderGaitchaPreview() {
		return '<div class="elementor-field">' + previewHtml + '</div>';
	}
	jQuery(window).on('elementor:init', function registerGaitchaPreview() {
		elementor.hooks.addFilter(
			'elementor_pro/forms/content_template/field/gaitcha',
			renderGaitchaPreview
		);
	});
})();
JS;

		wp_add_inline_script( 'elementor-pro', $inline_js );
	}

	/**
	 * Enqueues Gaitcha scripts on the frontend.
	 *
	 * Hooked to 'elementor/frontend/after_enqueue_scripts' because
	 * Elementor caches widget HTML — render_field() doesn't fire
	 * on cached page loads, so wp_enqueue_script() inside it
	 * would never execute.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Don't enqueue in Elementor preview mode.
		$elementor = \Elementor\Plugin::$instance;
		if ( $elementor && $elementor->preview->is_preview_mode() ) {
			return;
		}

		wp_enqueue_script(
			'gaitcha',
			GAITCHA_WP_URL . 'assets/js/gaitcha.min.js',
			array(),
			GAITCHA_WP_VERSION,
			true
		);

		wp_enqueue_script(
			'gaitcha-elementor',
			GAITCHA_WP_URL . 'assets/js/gaitcha-elementor.js',
			array( 'gaitcha', 'jquery' ),
			GAITCHA_WP_VERSION,
			true
		);

		wp_localize_script(
			'gaitcha-elementor',
			'gaitchaWPConfig',
			array(
				'endpoint'     => $this->endpoint->get_url(),
				'defaultLabel' => __( 'I\'m a real person', 'gaitcha-for-wp' ),
				'theme'        => Settings::get_theme(),
			)
		);

	}
}
