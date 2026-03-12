<?php
/**
 * Gravity Forms connector.
 *
 * Registers the GFFieldGaitcha field type, enqueues scripts,
 * and validates submissions via the gform_field_validation filter.
 *
 * @package GaitchaWP\Connectors
 */

namespace GaitchaWP\Connectors;

use Gaitcha\Config;
use Gaitcha\ValidationOrchestrator;
use GaitchaWP\Endpoint;
use GaitchaWP\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class GravityFormsConnector
 */
class GravityFormsConnector implements ConnectorInterface {

	/**
	 * Gravity Forms field type identifier.
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
		add_filter( 'gform_field_validation', array( $this, 'validate_field' ), 10, 4 );
		add_action( 'gform_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 2 );
	}

	/**
	 * Registers the GFFieldGaitcha field type with Gravity Forms.
	 *
	 * @return void
	 */
	private function register_field_type() {
		if ( class_exists( 'GF_Fields' ) ) {
			\GF_Fields::register( new GFFieldGaitcha() );
		}
	}

	/**
	 * Validates the gaitcha field on form submission.
	 *
	 * @param array    $result       Validation result with 'is_valid' and 'message' keys.
	 * @param mixed    $value        Submitted value.
	 * @param array    $form         Form data.
	 * @param \GF_Field $field       Field object.
	 * @return array Modified validation result.
	 */
	public function validate_field( $result, $value, $form, $field ) {
		if ( self::FIELD_TYPE !== $field->type ) {
			return $result;
		}

		// Admin bypass.
		$bypass_admin = apply_filters( 'gaitcha_bypass_admin', current_user_can( 'manage_options' ) );
		if ( $bypass_admin ) {
			return $result;
		}

		$orchestrator = new ValidationOrchestrator( $this->config );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Gaitcha uses HMAC token validation, not nonces.
		$gaitcha_result = $orchestrator->validate( wp_unslash( $_POST ) );

		if ( ! $gaitcha_result->isAccepted() ) {
			$result['is_valid'] = false;
			$result['message']  = ! empty( $field->errorMessage )
				? $field->errorMessage
				: __( 'Verification failed. Please try again.', 'gaitcha-for-wp' );
		}

		return $result;
	}

	/**
	 * Enqueues Gaitcha scripts when a form containing a gaitcha field is rendered.
	 *
	 * @param array $form Form data.
	 * @param bool  $ajax Whether the form uses AJAX.
	 * @return void
	 */
	public function enqueue_scripts( $form, $ajax ) {
		if ( ! $this->form_has_gaitcha_field( $form ) ) {
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
			'gaitcha-gravityforms',
			GAITCHA_WP_URL . 'assets/js/gaitcha-gravityforms.js',
			array( 'gaitcha' ),
			GAITCHA_WP_VERSION,
			true
		);

		wp_localize_script(
			'gaitcha-gravityforms',
			'gaitchaWPConfig',
			array(
				'endpoint'     => $this->endpoint->get_url(),
				'defaultLabel' => __( 'I\'m a real person', 'gaitcha-for-wp' ),
				'theme'        => Settings::get_theme(),
			)
		);
	}

	/**
	 * Checks whether the form contains a gaitcha field.
	 *
	 * @param array $form Form data.
	 * @return bool True if the form has a gaitcha field.
	 */
	private function form_has_gaitcha_field( $form ) {
		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return false;
		}

		foreach ( $form['fields'] as $field ) {
			if ( self::FIELD_TYPE === $field->type ) {
				return true;
			}
		}

		return false;
	}
}
