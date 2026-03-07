<?php
/**
 * WPForms connector.
 *
 * Registers a Gaitcha field type in WPForms, enqueues scripts,
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
 * Class WPFormsConnector
 */
class WPFormsConnector implements ConnectorInterface {

	/**
	 * WPForms field type identifier.
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
		$this->register_field_type();
		add_action( 'wpforms_frontend_js', array( $this, 'enqueue_scripts' ) );
		add_action( 'wpforms_process_validate_' . self::FIELD_TYPE, array( $this, 'validate_field' ), 10, 3 );
	}

	/**
	 * Registers the WPForms field class.
	 *
	 * @return void
	 */
	private function register_field_type() {
		if ( class_exists( 'WPForms_Field' ) ) {
			new WPFormsFieldGaitcha();
		}
	}

	/**
	 * Enqueues Gaitcha scripts when a form with gaitcha field is present.
	 *
	 * @param array $forms Forms on the page.
	 * @return void
	 */
	public function enqueue_scripts( $forms ) {
		if ( ! $this->forms_have_gaitcha_field( (array) $forms ) ) {
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
			'gaitcha-wpforms',
			GAITCHA_WP_URL . 'assets/js/gaitcha-wpforms.js',
			array( 'gaitcha' ),
			GAITCHA_WP_VERSION,
			true
		);

		wp_localize_script(
			'gaitcha-wpforms',
			'gaitchaWPConfig',
			array(
				'endpoint'     => $this->endpoint->get_url(),
				'defaultLabel' => __( 'I am not a robot', 'gaitcha-for-wp' ),
			)
		);
	}

	/**
	 * Validates the gaitcha field on form submission.
	 *
	 * @param int   $field_id     Field ID.
	 * @param mixed $field_submit Submitted value.
	 * @param array $form_data    Form data.
	 * @return void
	 */
	public function validate_field( $field_id, $field_submit, $form_data ) {
		// Admin bypass.
		$bypass_admin = apply_filters( 'gaitcha_bypass_admin', current_user_can( 'manage_options' ) );
		if ( $bypass_admin ) {
			return;
		}

		$orchestrator = new ValidationOrchestrator( $this->config );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Gaitcha uses HMAC token validation, not nonces.
		$result       = $orchestrator->validate( wp_unslash( $_POST ) );

		if ( ! $result->isAccepted() ) {
			wpforms()->obj( 'process' )->errors[ $form_data['id'] ][ $field_id ] = __( 'Verification failed. Please try again.', 'gaitcha-for-wp' );
		}
	}

	/**
	 * Checks if any form on the page has a gaitcha field.
	 *
	 * @param array $forms Forms on the page.
	 * @return bool
	 */
	private function forms_have_gaitcha_field( $forms ) {
		foreach ( $forms as $form ) {
			if ( empty( $form['fields'] ) ) {
				continue;
			}
			foreach ( $form['fields'] as $field ) {
				if ( isset( $field['type'] ) && self::FIELD_TYPE === $field['type'] ) {
					return true;
				}
			}
		}

		return false;
	}
}
