<?php
/**
 * Fluent Forms connector.
 *
 * Registers a Gaitcha element in Fluent Forms' builder,
 * renders the captcha container, enqueues scripts,
 * and validates submissions.
 *
 * @package GaitchaWP\Connectors
 */

namespace GaitchaWP\Connectors;

use Gaitcha\Config;
use Gaitcha\ValidationOrchestrator;
use GaitchaWP\Endpoint;

defined( 'ABSPATH' ) || exit;

/**
 * Class FluentFormsConnector
 */
class FluentFormsConnector implements ConnectorInterface {

	/**
	 * Fluent Forms element identifier.
	 *
	 * @var string
	 */
	const ELEMENT = 'gaitcha';

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
		add_filter( 'fluentform/editor_components', array( $this, 'register_component' ) );
		add_action( 'fluentform/render_item_' . self::ELEMENT, array( $this, 'render_field' ), 10, 2 );
		add_filter( 'fluentform/validate_input_item_' . self::ELEMENT, array( $this, 'validate_field' ), 10, 5 );
		add_filter( 'fluentform/rendering_form', array( $this, 'maybe_enqueue_scripts' ) );
	}

	/**
	 * Registers the Gaitcha component in the form editor.
	 *
	 * @param array $components Editor component groups.
	 * @return array Modified components.
	 */
	public function register_component( $components ) {
		$components['advanced'][] = array(
			'index'          => 20,
			'element'        => self::ELEMENT,
			'attributes'     => array(
				'type' => 'checkbox',
				'name' => self::ELEMENT,
			),
			'settings'       => array(
				'label'            => __( 'Gaitcha', 'gaitcha-for-wp' ),
				'tnc_html'         => __( 'Yes, I\'m a real person', 'gaitcha-for-wp' ),
				'has_checkbox'     => true,
				'help_message'     => '',
				'container_class'  => '',
				'validation_rules' => array(),
			),
			'editor_options' => array(
				'title'      => 'Gaitcha',
				'icon_class' => 'ff-edit-checkbox-1',
				'template'   => 'termsCheckbox',
			),
		);

		return $components;
	}

	/**
	 * Renders the gaitcha field on the frontend.
	 *
	 * @param array  $data Field data.
	 * @param object $form Form object.
	 * @return void
	 */
	public function render_field( $data, $form ) {
		$form_id      = absint( $form->id );
		$label        = ! empty( $data['settings']['tnc_html'] ) ? $data['settings']['tnc_html'] : '';
		$container_id = 'ff-gaitcha-' . $form_id;

		$container_class = 'ff-el-group';
		if ( ! empty( $data['settings']['container_class'] ) ) {
			$container_class .= ' ' . $data['settings']['container_class'];
		}

		printf(
			'<div class="%s"><div class="ff-el-input--content"><div class="ff-el-form-check" id="%s" data-gaitcha-container="%s" data-gaitcha-label="%s"></div></div></div>',
			esc_attr( $container_class ),
			esc_attr( $container_id ),
			esc_attr( $container_id ),
			esc_attr( $label )
		);
	}

	/**
	 * Validates the gaitcha field on form submission.
	 *
	 * @param string $error    Existing error.
	 * @param array  $field    Field config.
	 * @param array  $formData Submitted form data.
	 * @param array  $fields   All field configs.
	 * @param object $form     Form object.
	 * @return string|array Error message or empty string.
	 */
	public function validate_field( $error, $field, $formData, $fields, $form ) {
		// Admin bypass.
		$bypass_admin = apply_filters( 'gaitcha_bypass_admin', current_user_can( 'manage_options' ) );
		if ( $bypass_admin ) {
			return $error;
		}

		$orchestrator = new ValidationOrchestrator( $this->config );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Gaitcha uses HMAC token validation, not nonces.
		$result       = $orchestrator->validate( wp_unslash( $_POST ) );

		if ( ! $result->isAccepted() ) {
			return __( 'Verification failed. Please try again.', 'gaitcha-for-wp' );
		}

		return $error;
	}

	/**
	 * Enqueues scripts when a form is rendered.
	 *
	 * Fluent Forms uses applyFilters (not do_action) for this hook,
	 * so the form object must be returned to avoid breaking the chain.
	 *
	 * @param object $form Form object.
	 * @return object The unmodified form object.
	 */
	public function maybe_enqueue_scripts( $form ) {
		wp_enqueue_script(
			'gaitcha',
			GAITCHA_WP_URL . 'assets/js/gaitcha.min.js',
			array(),
			GAITCHA_WP_VERSION,
			true
		);

		wp_enqueue_script(
			'gaitcha-fluentforms',
			GAITCHA_WP_URL . 'assets/js/gaitcha-fluentforms.js',
			array( 'gaitcha' ),
			GAITCHA_WP_VERSION,
			true
		);

		wp_localize_script(
			'gaitcha-fluentforms',
			'gaitchaWPConfig',
			array(
				'endpoint'     => $this->endpoint->get_url(),
				'defaultLabel' => __( 'Yes, I\'m a real person', 'gaitcha-for-wp' ),
			)
		);

		return $form;
	}
}
