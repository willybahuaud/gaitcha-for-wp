<?php
/**
 * Ninja Forms field type for Gaitcha.
 *
 * Extends NF_Abstracts_Field to register a captcha checkbox field
 * with server-side validation.
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
	 * Settings to exclude from the field.
	 *
	 * @var array
	 */
	protected $_settings_exclude = array( 'default', 'placeholder', 'input_limit_set' );

	/**
	 * Gaitcha configuration.
	 *
	 * @var Config
	 */
	private $gaitcha_config;

	/**
	 * @param Config $config Gaitcha configuration.
	 */
	public function __construct( Config $config ) {
		$this->gaitcha_config = $config;

		parent::__construct();

		$this->_nicename = __( 'Gaitcha', 'gaitcha-for-wp' );
	}

	/**
	 * Validates the gaitcha field on form submission.
	 *
	 * @param array $field Field settings.
	 * @param array $data  Form submission data.
	 * @return array|string Error(s) or empty array if valid.
	 */
	public function validate( $field, $data ) {
		// Admin bypass.
		$bypass_admin = apply_filters( 'gaitcha_bypass_admin', current_user_can( 'manage_options' ) );
		if ( $bypass_admin ) {
			return array();
		}

		$orchestrator = new ValidationOrchestrator( $this->gaitcha_config );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Gaitcha uses HMAC token validation, not nonces.
		$result       = $orchestrator->validate( wp_unslash( $_POST ) );

		if ( ! $result->isAccepted() ) {
			return __( 'Verification failed. Please try again.', 'gaitcha-for-wp' );
		}

		return array();
	}
}
