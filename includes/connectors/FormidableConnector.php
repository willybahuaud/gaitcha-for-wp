<?php
/**
 * Formidable Forms connector.
 *
 * Registers a "Gaitcha" field type in Formidable's field palette,
 * maps it to the FrmFieldGaitcha class, enqueues scripts,
 * and validates submissions.
 *
 * @package GaitchaWP\Connectors
 */

namespace GaitchaWP\Connectors;

use Gaitcha\Config;
use GaitchaWP\Endpoint;

defined( 'ABSPATH' ) || exit;

/**
 * Class FormidableConnector
 */
class FormidableConnector implements ConnectorInterface {

	/**
	 * Formidable field type identifier.
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
		add_filter( 'frm_available_fields', array( $this, 'register_field_type' ) );
		add_filter( 'frm_get_field_type_class', array( $this, 'register_field_class' ), 10, 2 );
		add_filter( 'frm_validate_' . self::FIELD_TYPE . '_field_entry', array( $this, 'validate_field' ), 10, 4 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Adds Gaitcha to the field palette in the form editor.
	 *
	 * @param array $fields Available field types.
	 * @return array Modified field types.
	 */
	public function register_field_type( $fields ) {
		$fields[ self::FIELD_TYPE ] = array(
			'name' => 'Gaitcha',
			'icon' => 'frmfont frm_shield_check2_icon',
		);

		return $fields;
	}

	/**
	 * Maps the gaitcha field type to the FrmFieldGaitcha class.
	 *
	 * @param string $class      Current field class name.
	 * @param string $field_type Field type identifier.
	 * @return string Field class name.
	 */
	public function register_field_class( $class, $field_type ) {
		if ( self::FIELD_TYPE === $field_type ) {
			return FrmFieldGaitcha::class;
		}

		return $class;
	}

	/**
	 * Validates the gaitcha field on form submission.
	 *
	 * Hooked to frm_validate_gaitcha_field_entry, so only fires for gaitcha fields.
	 *
	 * @param array  $errors       Existing validation errors.
	 * @param object $posted_field Field object.
	 * @param mixed  $value        Submitted value.
	 * @param array  $args         Additional arguments.
	 * @return array Modified validation errors.
	 */
	public function validate_field( $errors, $posted_field, $value, $args ) {
		// Admin bypass.
		$bypass_admin = apply_filters( 'gaitcha_bypass_admin', current_user_can( 'manage_options' ) );
		if ( $bypass_admin ) {
			return $errors;
		}

		$orchestrator = new \Gaitcha\ValidationOrchestrator( $this->config );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Gaitcha uses HMAC token validation, not nonces.
		$result       = $orchestrator->validate( wp_unslash( $_POST ) );

		if ( ! $result->isAccepted() ) {
			$errors[ 'field' . $posted_field->id ] = __( 'Verification failed. Please try again.', 'gaitcha-for-wp' );
		}

		return $errors;
	}

	/**
	 * Enqueues Gaitcha scripts on the frontend.
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
			'gaitcha-formidable',
			GAITCHA_WP_URL . 'assets/js/gaitcha-formidable.js',
			array( 'gaitcha' ),
			GAITCHA_WP_VERSION,
			true
		);

		wp_localize_script(
			'gaitcha-formidable',
			'gaitchaWPConfig',
			array(
				'endpoint'     => $this->endpoint->get_url(),
				'defaultLabel' => __( 'Yes, I\'m a real person', 'gaitcha-for-wp' ),
			)
		);

		// Align gaitcha checkbox+label with native Formidable checkboxes.
		wp_add_inline_style(
			'formidable',
			'.frm_checkbox .gaitcha-checkbox, .frm_checkbox .gaitcha-label { display: inline; vertical-align: middle; } .frm_checkbox .gaitcha-label { margin-left: 4px; }'
		);
	}
}
