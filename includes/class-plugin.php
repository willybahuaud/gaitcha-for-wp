<?php
/**
 * Plugin orchestrator.
 *
 * Builds the Gaitcha Config, registers the REST endpoint,
 * loads connectors and the GitHub updater.
 *
 * @package GaitchaWP
 */

namespace GaitchaWP;

use Gaitcha\Config;
use GaitchaWP\Connectors\WSFormConnector;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 */
class Plugin {

	/**
	 * Gaitcha configuration instance.
	 *
	 * @var Config|null
	 */
	private $config = null;

	/**
	 * REST endpoint instance.
	 *
	 * @var Endpoint|null
	 */
	private $endpoint = null;

	/**
	 * Initializes the plugin components.
	 *
	 * @return void
	 */
	public function init() {
		$secret = get_option( 'gaitcha_secret', '' );

		// Bail silently if the secret is too short (plugin not activated properly).
		if ( strlen( $secret ) < 32 ) {
			return;
		}

		$options = apply_filters(
			'gaitcha_config',
			array(
				'secret' => $secret,
				'debug'  => defined( 'WP_DEBUG' ) && WP_DEBUG,
			)
		);

		$this->config   = new Config( $options );
		$this->endpoint = new Endpoint( $this->config );
		$this->endpoint->register();

		// WS Form connector.
		if ( class_exists( 'WS_Form' ) ) {
			$connector = new WSFormConnector( $this->config, $this->endpoint );
			$connector->register_hooks();
		}

		// GitHub auto-updater.
		$updater = new Updater();
		$updater->register_hooks();
	}
}
