<?php
/**
 * Ninja Forms connector.
 *
 * Registers a Gaitcha field type in Ninja Forms,
 * outputs the Backbone template, enqueues scripts,
 * and validates submissions.
 *
 * @package GaitchaWP\Connectors
 */

namespace GaitchaWP\Connectors;

use Gaitcha\Config;
use Gaitcha\ValidationOrchestrator;
use GaitchaWP\Endpoint;
use GaitchaWP\Settings;
use GaitchaWP\WidgetPreview;

defined( 'ABSPATH' ) || exit;

/**
 * Class NinjaFormsConnector
 */
class NinjaFormsConnector implements ConnectorInterface {

	/**
	 * Ninja Forms field type identifier.
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
		add_filter( 'ninja_forms_register_fields', array( $this, 'register_field' ) );
		add_action( 'ninja_forms_output_templates', array( $this, 'output_template' ) );
		add_action( 'nf_display_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Registers the Gaitcha field type with Ninja Forms.
	 *
	 * @param array $fields Registered fields.
	 * @return array Modified fields.
	 */
	public function register_field( $fields ) {
		$fields[ self::FIELD_TYPE ] = new NFFieldGaitcha( $this->config );

		return $fields;
	}

	/**
	 * Outputs the Backbone.js template for the gaitcha field.
	 *
	 * Ninja Forms renders fields client-side via Underscore templates.
	 *
	 * @return void
	 */
	public function output_template() {
		?>
		<script id="tmpl-nf-field-gaitcha" type="text/template">
			<ul>
				<li id="nf-gaitcha-{{ data.id }}"
					class="nf-gaitcha-container"
					data-gaitcha-container="nf-gaitcha-{{ data.id }}">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WidgetPreview handles escaping.
					echo WidgetPreview::render();
					?>
				</li>
			</ul>
		</script>
		<?php
	}

	/**
	 * Enqueues Gaitcha scripts on Ninja Forms pages.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Hide the native NF label for gaitcha fields (the widget has its own).
		wp_register_style( 'gaitcha-ninjaforms', false, array(), GAITCHA_WP_VERSION );
		wp_enqueue_style( 'gaitcha-ninjaforms' );
		wp_add_inline_style(
			'gaitcha-ninjaforms',
			'.gaitcha-container .nf-field-label { display: none; }'
			. ' .gaitcha-container .nf-field-element ul { list-style: none; margin: 0; padding: 0; }'
		);

		wp_enqueue_script(
			'gaitcha',
			GAITCHA_WP_URL . 'assets/js/gaitcha.min.js',
			array(),
			GAITCHA_WP_VERSION,
			true
		);

		wp_enqueue_script(
			'gaitcha-ninjaforms',
			GAITCHA_WP_URL . 'assets/js/gaitcha-ninjaforms.js',
			array( 'jquery', 'gaitcha', 'nf-front-end' ),
			GAITCHA_WP_VERSION,
			true
		);

		wp_localize_script(
			'gaitcha-ninjaforms',
			'gaitchaWPConfig',
			array(
				'endpoint'     => $this->endpoint->get_url(),
				'defaultLabel' => __( 'I\'m a real person', 'gaitcha-for-wp' ),
				'theme'        => Settings::get_theme(),
			)
		);
	}
}
