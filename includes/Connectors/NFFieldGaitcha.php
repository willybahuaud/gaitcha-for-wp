<?php
/**
 * Ninja Forms field type for Gaitcha.
 *
 * Extends NF_Abstracts_Field to register a captcha checkbox field
 * with label setting and server-side validation.
 *
 * @package GaitchaWP\Connectors
 */

namespace GaitchaWP\Connectors;

use Gaitcha\Config;
use Gaitcha\ValidationOrchestrator;

defined( 'ABSPATH' ) || exit;

/**
 * Class NFFieldGaitcha
 */
class NFFieldGaitcha extends \NF_Abstracts_Field {

	/**
	 * Field name identifier.
	 *
	 * @var string
	 */
	protected $_name = 'gaitcha';

	/**
	 * Field type.
	 *
	 * @var string
	 */
	protected $_type = 'gaitcha';

	/**
	 * Display name in the form builder.
	 *
	 * @var string
	 */
	protected $_nicename = 'Gaitcha';

	/**
	 * Builder section.
	 *
	 * @var string
	 */
	protected $_section = 'misc';

	/**
	 * Font Awesome icon.
	 *
	 * @var string
	 */
	protected $_icon = 'check-square-o';

	/**
	 * Underscore.js template name.
	 *
	 * @var string
	 */
	protected $_templates = 'gaitcha';

	/**
	 * Common settings to expose in the builder.
	 *
	 * Only label and description — no default, placeholder, required, etc.
	 *
	 * @var array
	 */
	protected $_settings_all_fields = array( 'label', 'classes', 'key' );

	/**
	 * Gaitcha configuration.
	 *
	 * @var Config
	 */
	private $gaitcha_config;

	/**
	 * Cached validation result per request.
	 *
	 * Ninja Forms calls validate() multiple times per request.
	 * We cache the result to avoid consuming the anti-replay token
	 * on the first call and getting rejected on the second.
	 *
	 * Keyed by a request-unique identifier to avoid stale cache
	 * in persistent PHP runtimes (Swoole, FrankenPHP worker mode).
	 *
	 * @var array{ id: string, result: array|string }|null
	 */
	private static $cached_result = null;

	/**
	 * @param Config $config Gaitcha configuration.
	 */
	public function __construct( Config $config ) {
		$this->gaitcha_config = $config;

		parent::__construct();

		$this->_nicename = __( 'Gaitcha', 'gaitcha-for-wp' );

		$this->_settings['label']['value'] = __( 'Yes, I\'m a real person', 'gaitcha-for-wp' );
	}

	/**
	 * Validates the gaitcha field on form submission.
	 *
	 * @param array $field Field settings.
	 * @param array $data  Form submission data.
	 * @return array|string Error(s) or empty array if valid.
	 */
	public function validate( $field, $data ) {
		// NF calls validate() multiple times per request — return cached result.
		// Use REQUEST_TIME_FLOAT as request-scoped key to avoid stale cache
		// in persistent PHP runtimes (Swoole, FrankenPHP worker mode).
		$request_id = (string) ( $_SERVER['REQUEST_TIME_FLOAT'] ?? '' );

		if ( null !== self::$cached_result && self::$cached_result['id'] === $request_id ) {
			return self::$cached_result['result'];
		}

		// Admin bypass.
		$bypass_admin = apply_filters( 'gaitcha_bypass_admin', current_user_can( 'manage_options' ) );
		if ( $bypass_admin ) {
			self::$cached_result = array( 'id' => $request_id, 'result' => array() );
			return self::$cached_result['result'];
		}

		$orchestrator = new ValidationOrchestrator( $this->gaitcha_config );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Gaitcha uses HMAC token validation, not nonces.
		$result       = $orchestrator->validate( wp_unslash( $_POST ) );

		if ( ! $result->isAccepted() ) {
			$validation_result   = __( 'Verification failed. Please try again.', 'gaitcha-for-wp' );
			self::$cached_result = array( 'id' => $request_id, 'result' => $validation_result );
			return $validation_result;
		}

		self::$cached_result = array( 'id' => $request_id, 'result' => array() );
		return self::$cached_result['result'];
	}
}
