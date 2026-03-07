<?php
/**
 * Contact Form 7 connector.
 *
 * Registers a [gaitcha] form tag, enqueues scripts,
 * and validates submissions via the wpcf7_spam filter.
 *
 * @package GaitchaWP\Connectors
 */

namespace GaitchaWP\Connectors;

use Gaitcha\Config;
use Gaitcha\ValidationOrchestrator;
use GaitchaWP\Endpoint;

defined( 'ABSPATH' ) || exit;

/**
 * Class CF7Connector
 */
class CF7Connector implements ConnectorInterface {

	/**
	 * CF7 form tag name.
	 *
	 * @var string
	 */
	const TAG_NAME = 'gaitcha';

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
		add_action( 'wpcf7_init', array( $this, 'register_form_tag' ) );
		add_action( 'wpcf7_admin_init', array( $this, 'register_tag_generator' ), 60, 0 );
		add_action( 'wpcf7_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'wpcf7_spam', array( $this, 'verify_submission' ), 9, 2 );
	}

	/**
	 * Registers the [gaitcha] form tag.
	 *
	 * @return void
	 */
	public function register_form_tag() {
		wpcf7_add_form_tag(
			self::TAG_NAME,
			array( $this, 'render_form_tag' ),
			array(
				'display-block' => true,
				'singular'      => true,
				'not-for-mail'  => true,
			)
		);
	}

	/**
	 * Renders the [gaitcha] form tag HTML.
	 *
	 * Usage: [gaitcha] or [gaitcha "Custom label"]
	 *
	 * @param \WPCF7_FormTag $tag Form tag object.
	 * @return string HTML output.
	 */
	public function render_form_tag( $tag ) {
		$label    = ! empty( $tag->values ) ? $tag->values[0] : '';
		$field_id = 'wpcf7-gaitcha-' . wp_unique_id();

		return sprintf(
			'<div %s></div>',
			wpcf7_format_atts( array(
				'class'                  => 'wpcf7-gaitcha',
				'id'                     => $field_id,
				'data-gaitcha-container' => $field_id,
				'data-gaitcha-label'     => $label,
			) )
		);
	}

	/**
	 * Enqueues Gaitcha scripts when CF7 is loaded.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'gaitcha',
			GAITCHA_WP_URL . 'assets/js/gaitcha.min.js',
			array(),
			GAITCHA_WP_VERSION,
			true
		);

		wp_enqueue_script(
			'gaitcha-cf7',
			GAITCHA_WP_URL . 'assets/js/gaitcha-cf7.js',
			array( 'gaitcha' ),
			GAITCHA_WP_VERSION,
			true
		);

		wp_localize_script(
			'gaitcha-cf7',
			'gaitchaWPConfig',
			array(
				'endpoint'     => $this->endpoint->get_url(),
				'defaultLabel' => __( 'I am not a robot', 'gaitcha-for-wp' ),
			)
		);
	}

	/**
	 * Registers the tag generator button in the CF7 admin editor toolbar.
	 *
	 * @return void
	 */
	public function register_tag_generator() {
		$tag_generator = \WPCF7_TagGenerator::get_instance();

		$tag_generator->add(
			self::TAG_NAME,
			__( 'gaitcha', 'gaitcha-for-wp' ),
			array( $this, 'render_tag_generator_panel' ),
			array( 'version' => '2' )
		);
	}

	/**
	 * Renders the tag generator panel content.
	 *
	 * @param \WPCF7_ContactForm $contact_form CF7 form object.
	 * @param array              $options       Tag generator options.
	 * @return void
	 */
	public function render_tag_generator_panel( $contact_form, $options ) {
		$tgg = new \WPCF7_TagGeneratorGenerator( $options['content'] );

		$formatter = new \WPCF7_HTMLFormatter();

		$formatter->append_start_tag( 'header', array(
			'class' => 'description-box',
		) );

		$formatter->append_start_tag( 'h3' );
		$formatter->append_preformatted(
			esc_html__( 'Gaitcha form-tag generator', 'gaitcha-for-wp' )
		);
		$formatter->end_tag( 'h3' );

		$formatter->append_start_tag( 'p' );
		$formatter->append_preformatted(
			esc_html__( 'Generates a form-tag for the Gaitcha captcha checkbox.', 'gaitcha-for-wp' )
		);
		$formatter->end_tag( 'header' );

		$formatter->append_start_tag( 'div', array(
			'class' => 'control-box',
		) );

		$formatter->call_user_func( static function () use ( $tgg ) {
			$tgg->print( 'field_type', array(
				'select_options' => array(
					'gaitcha' => __( 'Gaitcha', 'gaitcha-for-wp' ),
				),
			) );

			$tgg->print( 'default_value', array(
				'title' => __( 'Label', 'contact-form-7' ),
			) );
		} );

		$formatter->end_tag( 'div' );

		$formatter->append_start_tag( 'footer', array(
			'class' => 'insert-box',
		) );

		$formatter->call_user_func( static function () use ( $tgg ) {
			$tgg->print( 'insert_box_content' );
		} );

		$formatter->print();
	}

	/**
	 * Verifies the submission against Gaitcha.
	 *
	 * Only runs if the form contains a [gaitcha] tag.
	 *
	 * @param bool              $spam       Current spam status.
	 * @param \WPCF7_Submission $submission CF7 submission object.
	 * @return bool True if spam, false otherwise.
	 */
	public function verify_submission( $spam, $submission ) {
		if ( $spam ) {
			return $spam;
		}

		$form = $submission->get_contact_form();

		if ( ! $this->form_has_gaitcha_tag( $form ) ) {
			return $spam;
		}

		// Admin bypass.
		$bypass_admin = apply_filters( 'gaitcha_bypass_admin', current_user_can( 'manage_options' ) );
		if ( $bypass_admin ) {
			return $spam;
		}

		$orchestrator = new ValidationOrchestrator( $this->config );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Gaitcha uses HMAC token validation, not nonces.
		$result       = $orchestrator->validate( wp_unslash( $_POST ) );

		if ( ! $result->isAccepted() ) {
			$submission->add_spam_log( array(
				'agent'  => 'gaitcha',
				'reason' => $result->getReason(),
			) );

			return true;
		}

		return $spam;
	}

	/**
	 * Checks whether a CF7 form contains a [gaitcha] tag.
	 *
	 * @param \WPCF7_ContactForm $form CF7 form object.
	 * @return bool True if a gaitcha tag is present.
	 */
	private function form_has_gaitcha_tag( $form ) {
		$tags = $form->scan_form_tags( array( 'type' => self::TAG_NAME ) );

		return ! empty( $tags );
	}
}
