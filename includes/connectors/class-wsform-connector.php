<?php
/**
 * WS Form connector.
 *
 * Enqueues Gaitcha scripts on pages with WS Form and validates
 * submissions via the wsf_submit_validate filter.
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
	 * Registers WordPress hooks for script enqueue and submit validation.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'wsf_submit_validate', array( $this, 'validate_submission' ), 10, 3 );
	}

	/**
	 * Enqueues Gaitcha core and WS Form adapter scripts.
	 *
	 * Only loads when WS Form public script is registered.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! wp_script_is( 'wsf-public', 'registered' ) ) {
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
				'endpoint' => $this->endpoint->get_url(),
				'label'    => __( 'I am not a robot', 'gaitcha-for-wp' ),
			)
		);
	}

	/**
	 * Validates a WS Form submission against Gaitcha.
	 *
	 * @param array  $error_validation_actions Existing validation errors.
	 * @param object $submit                   WS Form submit object.
	 * @param string $post_mode                Submit mode ('save', 'submit', etc.).
	 * @return array Modified validation errors.
	 */
	public function validate_submission( $error_validation_actions, $submit, $post_mode ) {
		// Skip draft saves.
		if ( 'save' === $post_mode ) {
			return $error_validation_actions;
		}

		$form_id = isset( $submit->form_id ) ? (int) $submit->form_id : 0;

		// Per-form opt-out.
		if ( ! apply_filters( 'gaitcha_enabled_for_form', true, $form_id ) ) {
			return $error_validation_actions;
		}

		// Admin bypass.
		$bypass_admin = apply_filters( 'gaitcha_bypass_admin', current_user_can( 'manage_options' ) );
		if ( $bypass_admin ) {
			return $error_validation_actions;
		}

		$orchestrator = new ValidationOrchestrator( $this->config );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Gaitcha uses HMAC token validation, not nonces.
		$result       = $orchestrator->validate( $_POST );

		if ( ! $result->isAccepted() ) {
			$error_validation_actions[] = __( 'Verification failed. Please try again.', 'gaitcha-for-wp' );
		}

		return $error_validation_actions;
	}
}
