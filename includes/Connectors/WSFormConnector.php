<?php
/**
 * WS Form connector.
 *
 * Registers a "Gaitcha" field type in WS Form's Spam Protection group,
 * enqueues scripts, and validates submissions.
 *
 * @package GaitchaWP\Connectors
 */

namespace GaitchaWP\Connectors;

use Gaitcha\Config;
use Gaitcha\ValidationOrchestrator;
use GaitchaWP\Endpoint;

defined( 'ABSPATH' ) || exit;

/**
 * Class WSFormConnector
 */
class WSFormConnector implements ConnectorInterface {

	/**
	 * WS Form field type identifier.
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
		add_filter( 'wsf_config_field_types', array( $this, 'register_field_type' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'wsf_submit_validate', array( $this, 'validate_submission' ), 10, 3 );
	}

	/**
	 * Registers the Gaitcha field type in WS Form.
	 *
	 * Adds Gaitcha to the "Spam Protection" group alongside reCAPTCHA, hCaptcha, and Turnstile.
	 *
	 * @param array $field_types Existing field types grouped by category.
	 * @return array Modified field types.
	 */
	public function register_field_type( $field_types ) {
		// Find the "Spam Protection" group key (the one containing recaptcha).
		$spam_group_key = $this->find_spam_group_key( $field_types );

		$gaitcha_field = array(
			'label'                => 'Gaitcha',
			'label_default'        => 'Gaitcha',
			'mask_field'                => '#pre_label<div id="#id" class="wsf-gaitcha-container" data-gaitcha-container="#id"#attributes></div>#post_label',
			'mask_field_label'          => '<label id="#label_id" for="#id"#attributes>#label</label>',
			'mask_field_label_attributes' => array( 'class' ),
			'mask_field_attributes' => array( 'class' ),
			'submit_save'          => false,
			'submit_edit'          => false,
			'calc_in'              => false,
			'calc_out'             => false,
			'text_in'              => false,
			'text_out'             => false,
			'value_out'            => false,
			'mappable'             => false,
			'has_required'         => false,
			'progress'             => false,
			'keyword'              => __( 'captcha spam gaitcha behavioral', 'gaitcha-for-wp' ),
			'multiple'             => false,
			'icon'                 => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
			'fieldsets'            => array(
				'basic' => array(
					'label'    => __( 'Basic', 'gaitcha-for-wp' ),
					'meta_keys' => array( 'label_render', 'help' ),
				),
				'advanced' => array(
					'label'     => __( 'Advanced', 'gaitcha-for-wp' ),
					'fieldsets' => array(
						array(
							'label'    => __( 'Style', 'gaitcha-for-wp' ),
							'meta_keys' => array( 'class_single_vertical_align' ),
						),
						array(
							'label'    => __( 'Classes', 'gaitcha-for-wp' ),
							'meta_keys' => array( 'class_field_wrapper' ),
						),
						array(
							'label'    => __( 'Breakpoints', 'gaitcha-for-wp' ),
							'meta_keys' => array( 'breakpoint_sizes' ),
							'class'    => array( 'wsf-fieldset-panel' ),
						),
					),
				),
			),
		);

		if ( null !== $spam_group_key ) {
			$field_types[ $spam_group_key ]['types'][ self::FIELD_TYPE ] = $gaitcha_field;
		} else {
			// Fallback: create a dedicated group.
			$field_types['gaitcha'] = array(
				'label' => __( 'Spam Protection', 'gaitcha-for-wp' ),
				'types' => array(
					self::FIELD_TYPE => $gaitcha_field,
				),
			);
		}

		return $field_types;
	}

	/**
	 * Enqueues Gaitcha core and WS Form adapter scripts.
	 *
	 * Loaded on all front-end pages when WS Form is active.
	 * The JS adapter is a no-op if no [data-gaitcha-container] is found.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( is_admin() ) {
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
			'gaitcha-wsform',
			GAITCHA_WP_URL . 'assets/js/gaitcha-wsform.js',
			array( 'gaitcha' ),
			GAITCHA_WP_VERSION,
			true
		);

		wp_localize_script(
			'gaitcha-wsform',
			'gaitchaWPConfig',
			array(
				'endpoint'     => $this->endpoint->get_url(),
				'defaultLabel' => __( 'Yes, I\'m a real person', 'gaitcha-for-wp' ),
			)
		);
	}

	/**
	 * Validates a WS Form submission against Gaitcha.
	 *
	 * Only runs if the form contains a Gaitcha field.
	 *
	 * @param array  $error_validation_actions Existing validation errors.
	 * @param string $post_mode                Submit mode ('save', 'submit', etc.).
	 * @param object $submit                   WS Form submit object.
	 * @return array Modified validation errors.
	 */
	public function validate_submission( $error_validation_actions, $post_mode, $submit ) {
		if ( 'save' === $post_mode ) {
			return $error_validation_actions;
		}

		if ( ! $this->form_has_gaitcha_field( $submit ) ) {
			return $error_validation_actions;
		}

		// Admin bypass.
		$bypass_admin = apply_filters( 'gaitcha_bypass_admin', current_user_can( 'manage_options' ) );
		if ( $bypass_admin ) {
			return $error_validation_actions;
		}

		$orchestrator = new ValidationOrchestrator( $this->config );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Gaitcha uses HMAC token validation, not nonces.
		$result       = $orchestrator->validate( wp_unslash( $_POST ) );

		if ( ! $result->isAccepted() ) {
			$error_validation_actions[] = __( 'Verification failed. Please try again.', 'gaitcha-for-wp' );
		}

		return $error_validation_actions;
	}

	/**
	 * Checks whether the submitted form contains a Gaitcha field.
	 *
	 * @param object $submit WS Form submit object.
	 * @return bool True if the form has a gaitcha field type.
	 */
	private function form_has_gaitcha_field( $submit ) {
		if ( ! isset( $submit->form_object ) ) {
			return false;
		}

		$fields = \WS_Form_Common::get_fields_from_form( $submit->form_object );

		foreach ( $fields as $field ) {
			if ( isset( $field->type ) && self::FIELD_TYPE === $field->type ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Finds the "Spam Protection" group key in the field types array.
	 *
	 * @param array $field_types Field types grouped by category.
	 * @return string|null Group key or null if not found.
	 */
	private function find_spam_group_key( $field_types ) {
		foreach ( $field_types as $key => $group ) {
			if ( ! isset( $group['types'] ) ) {
				continue;
			}

			if ( isset( $group['types']['recaptcha'] ) || isset( $group['types']['hcaptcha'] ) || isset( $group['types']['turnstile'] ) ) {
				return $key;
			}
		}

		return null;
	}
}
