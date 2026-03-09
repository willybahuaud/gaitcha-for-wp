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
use GaitchaWP\Connectors\CF7Connector;
use GaitchaWP\Connectors\FormidableConnector;
use GaitchaWP\Connectors\GravityFormsConnector;
use GaitchaWP\Connectors\WPFormsConnector;
use GaitchaWP\Connectors\FluentFormsConnector;
use GaitchaWP\Connectors\NinjaFormsConnector;

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
				'secret'       => $secret,
				'debug'        => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'anti_replay'  => true,
			)
		);

		// Provide the default WPTokenStore when anti-replay is enabled
		// and no custom store was supplied via the filter.
		if ( ! empty( $options['anti_replay'] ) && empty( $options['token_store'] ) ) {
			$ttl                    = (int) ( $options['ttl'] ?? 120 );
			$options['token_store'] = new WPTokenStore( $ttl );
		}

		$this->config   = new Config( $options );
		$this->endpoint = new Endpoint( $this->config );
		$this->endpoint->register();

		// WS Form connector.
		if ( class_exists( 'WS_Form' ) ) {
			$connector = new WSFormConnector( $this->config, $this->endpoint );
			$connector->register_hooks();
		}

		// Contact Form 7 connector.
		if ( class_exists( 'WPCF7' ) ) {
			$connector = new CF7Connector( $this->config, $this->endpoint );
			$connector->register_hooks();
		}

		// Formidable Forms connector.
		if ( class_exists( 'FrmAppHelper' ) ) {
			$connector = new FormidableConnector( $this->config, $this->endpoint );
			$connector->register_hooks();
		}

		// Gravity Forms connector.
		if ( class_exists( 'GFForms' ) ) {
			$connector = new GravityFormsConnector( $this->config, $this->endpoint );
			$connector->register_hooks();
		}

		// WPForms connector.
		if ( function_exists( 'wpforms' ) ) {
			$connector = new WPFormsConnector( $this->config, $this->endpoint );
			$connector->register_hooks();
		}

		// Fluent Forms connector.
		if ( defined( 'FLUENTFORM' ) ) {
			$connector = new FluentFormsConnector( $this->config, $this->endpoint );
			$connector->register_hooks();
		}

		// Ninja Forms connector.
		if ( class_exists( 'Ninja_Forms' ) ) {
			$connector = new NinjaFormsConnector( $this->config, $this->endpoint );
			$connector->register_hooks();
		}

		// GitHub auto-updater.
		$updater = new Updater();
		$updater->register_hooks();
	}

}
