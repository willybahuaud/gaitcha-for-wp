<?php
/**
 * Native WordPress forms connector.
 *
 * Adds Gaitcha protection to WordPress built-in forms:
 * login, registration, lost password, and comments.
 * Each form is individually toggleable via the settings page.
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
 * Class NativeFormsConnector
 */
class NativeFormsConnector implements ConnectorInterface {

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
	 * Registers WordPress hooks based on active protections.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( Settings::is_protected( 'protect_login' ) ) {
			add_action( 'login_form', array( $this, 'render_container' ) );
			add_filter( 'authenticate', array( $this, 'validate_login' ), 50, 3 );
			add_action( 'login_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		if ( Settings::is_protected( 'protect_register' ) ) {
			add_action( 'register_form', array( $this, 'render_container' ) );
			add_filter( 'registration_errors', array( $this, 'validate_registration' ), 10, 3 );
			add_action( 'login_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		if ( Settings::is_protected( 'protect_lostpassword' ) ) {
			add_action( 'lostpassword_form', array( $this, 'render_container' ) );
			add_action( 'lostpassword_post', array( $this, 'validate_lostpassword' ) );
			add_action( 'login_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		if ( Settings::is_protected( 'protect_comments' ) ) {
			add_filter( 'comment_form_submit_field', array( $this, 'inject_before_submit' ), 10, 2 );
			add_filter( 'preprocess_comment', array( $this, 'validate_comment' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_frontend' ) );
		}
	}

	/**
	 * Renders the Gaitcha container div.
	 *
	 * Used for login, register, and lost password forms.
	 *
	 * @return void
	 */
	public function render_container() {
		echo '<div class="gaitcha-native-container" data-gaitcha-container="gaitcha-native" style="margin-bottom:16px;"></div>';
	}

	/**
	 * Injects the Gaitcha container before the comment submit button.
	 *
	 * @param string $submit_field The submit button HTML.
	 * @param array  $args         Comment form arguments.
	 * @return string Modified HTML with Gaitcha container prepended.
	 */
	public function inject_before_submit( $submit_field, $args ) {
		$container = '<div class="gaitcha-native-container" data-gaitcha-container="gaitcha-comment" style="margin-bottom:16px;"></div>';

		return $container . $submit_field;
	}

	/**
	 * Validates Gaitcha on the login form.
	 *
	 * Hooked to 'authenticate' at priority 50 (after WP checks credentials).
	 * Only blocks if credentials were valid but captcha failed,
	 * to avoid leaking whether an account exists.
	 *
	 * @param \WP_User|\WP_Error|null $user     Authentication result.
	 * @param string                  $username  Submitted username.
	 * @param string                  $password  Submitted password.
	 * @return \WP_User|\WP_Error Authentication result.
	 */
	public function validate_login( $user, $username, $password ) {
		// Only validate on actual form submission (not cookie-based re-auth).
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Login form has no nonce.
		if ( empty( $_POST['log'] ) ) {
			return $user;
		}

		// Don't interfere if authentication already failed.
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( $this->should_bypass() ) {
			return $user;
		}

		if ( ! $this->is_gaitcha_valid() ) {
			return new \WP_Error(
				'gaitcha_failed',
				'<strong>' . esc_html__( 'Error:', 'gaitcha-for-wp' ) . '</strong> '
				. esc_html__( 'Verification failed. Please try again.', 'gaitcha-for-wp' )
			);
		}

		return $user;
	}

	/**
	 * Validates Gaitcha on the registration form.
	 *
	 * @param \WP_Error $errors               Registration errors.
	 * @param string    $sanitized_user_login  Sanitized username.
	 * @param string    $user_email            User email.
	 * @return \WP_Error Modified errors.
	 */
	public function validate_registration( $errors, $sanitized_user_login, $user_email ) {
		if ( $this->should_bypass() ) {
			return $errors;
		}

		if ( ! $this->is_gaitcha_valid() ) {
			$errors->add(
				'gaitcha_failed',
				'<strong>' . esc_html__( 'Error:', 'gaitcha-for-wp' ) . '</strong> '
				. esc_html__( 'Verification failed. Please try again.', 'gaitcha-for-wp' )
			);
		}

		return $errors;
	}

	/**
	 * Validates Gaitcha on the lost password form.
	 *
	 * @param \WP_Error $errors Lost password errors.
	 * @return void
	 */
	public function validate_lostpassword( $errors ) {
		if ( $this->should_bypass() ) {
			return;
		}

		if ( ! $this->is_gaitcha_valid() ) {
			$errors->add(
				'gaitcha_failed',
				'<strong>' . esc_html__( 'Error:', 'gaitcha-for-wp' ) . '</strong> '
				. esc_html__( 'Verification failed. Please try again.', 'gaitcha-for-wp' )
			);
		}
	}

	/**
	 * Validates Gaitcha on the comment form.
	 *
	 * @param array $commentdata Comment data.
	 * @return array Unmodified comment data if valid.
	 */
	public function validate_comment( $commentdata ) {
		if ( $this->should_bypass() ) {
			return $commentdata;
		}

		if ( ! $this->is_gaitcha_valid() ) {
			wp_die(
				esc_html__( 'Verification failed. Please try again.', 'gaitcha-for-wp' ),
				esc_html__( 'Comment Submission Error', 'gaitcha-for-wp' ),
				array( 'back_link' => true )
			);
		}

		return $commentdata;
	}

	/**
	 * Enqueues scripts on the wp-login.php page.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$this->do_enqueue();
	}

	/**
	 * Enqueues scripts on the frontend for comment forms.
	 *
	 * Only enqueues if the current page is singular (has comments).
	 *
	 * @return void
	 */
	public function enqueue_scripts_frontend() {
		if ( ! is_singular() ) {
			return;
		}

		$this->do_enqueue();
	}

	/**
	 * Performs the actual script enqueue.
	 *
	 * @return void
	 */
	private function do_enqueue() {
		wp_enqueue_script(
			'gaitcha',
			GAITCHA_WP_URL . 'assets/js/gaitcha.min.js',
			array(),
			GAITCHA_WP_VERSION,
			true
		);

		wp_enqueue_script(
			'gaitcha-native',
			GAITCHA_WP_URL . 'assets/js/gaitcha-native.js',
			array( 'gaitcha' ),
			GAITCHA_WP_VERSION,
			true
		);

		wp_localize_script(
			'gaitcha-native',
			'gaitchaWPConfig',
			array(
				'endpoint'     => $this->endpoint->get_url(),
				'defaultLabel' => __( 'I\'m a real person', 'gaitcha-for-wp' ),
				'theme'        => Settings::get_theme(),
			)
		);
	}

	/**
	 * Checks if admin bypass is active.
	 *
	 * @return bool
	 */
	private function should_bypass() {
		return apply_filters( 'gaitcha_bypass_admin', current_user_can( 'manage_options' ) );
	}

	/**
	 * Runs the Gaitcha validation pipeline.
	 *
	 * @return bool True if accepted, false otherwise.
	 */
	private function is_gaitcha_valid() {
		$orchestrator = new ValidationOrchestrator( $this->config );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Gaitcha uses HMAC token validation, not nonces.
		$result = $orchestrator->validate( wp_unslash( $_POST ) );

		return $result->isAccepted();
	}
}
