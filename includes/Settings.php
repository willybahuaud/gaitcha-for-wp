<?php
/**
 * Plugin settings page.
 *
 * Registers a settings page under "Settings" in the WordPress admin.
 * Handles appearance (theme) and native form protection options.
 *
 * @package GaitchaWP
 */

namespace GaitchaWP;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 */
class Settings {

	/**
	 * Option name for all Gaitcha settings.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'gaitcha_settings';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'gaitcha-settings';

	/**
	 * Default settings values.
	 *
	 * @var array
	 */
	const DEFAULTS = array(
		'theme'              => 'light',
		'protect_login'      => false,
		'protect_register'   => false,
		'protect_lostpassword' => false,
		'protect_comments'   => false,
	);

	/**
	 * Registers admin hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . GAITCHA_WP_BASENAME, array( $this, 'add_settings_link' ) );
	}

	/**
	 * Adds a "Settings" link in the plugins list.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=' . self::PAGE_SLUG ),
			esc_html__( 'Settings', 'gaitcha-for-wp' )
		);
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Adds the options page under Settings.
	 *
	 * @return void
	 */
	public function add_options_page() {
		add_options_page(
			__( 'Gaitcha Settings', 'gaitcha-for-wp' ),
			__( 'Gaitcha', 'gaitcha-for-wp' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Registers settings, sections and fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::PAGE_SLUG,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::DEFAULTS,
			)
		);

		// Appearance section.
		add_settings_section(
			'gaitcha_appearance',
			__( 'Appearance', 'gaitcha-for-wp' ),
			'__return_null',
			self::PAGE_SLUG
		);

		add_settings_field(
			'gaitcha_theme',
			__( 'Widget theme', 'gaitcha-for-wp' ),
			array( $this, 'render_theme_field' ),
			self::PAGE_SLUG,
			'gaitcha_appearance'
		);

		// Native forms section.
		add_settings_section(
			'gaitcha_native_forms',
			__( 'Built-in WordPress forms', 'gaitcha-for-wp' ),
			array( $this, 'render_native_forms_description' ),
			self::PAGE_SLUG
		);

		$native_fields = array(
			'protect_login'        => __( 'Login form', 'gaitcha-for-wp' ),
			'protect_register'     => __( 'Registration form', 'gaitcha-for-wp' ),
			'protect_lostpassword' => __( 'Lost password form', 'gaitcha-for-wp' ),
			'protect_comments'     => __( 'Comment form', 'gaitcha-for-wp' ),
		);

		foreach ( $native_fields as $key => $label ) {
			add_settings_field(
				'gaitcha_' . $key,
				$label,
				array( $this, 'render_checkbox_field' ),
				self::PAGE_SLUG,
				'gaitcha_native_forms',
				array(
					'key'       => $key,
					'label_for' => 'gaitcha_' . $key,
				)
			);
		}
	}

	/**
	 * Sanitizes settings before saving.
	 *
	 * @param array $input Raw input from the form.
	 * @return array Sanitized settings.
	 */
	public function sanitize( $input ) {
		$clean = array();

		$valid_themes   = array( 'light', 'dark', 'auto' );
		$clean['theme'] = in_array( $input['theme'] ?? '', $valid_themes, true )
			? $input['theme']
			: 'light';

		$checkboxes = array( 'protect_login', 'protect_register', 'protect_lostpassword', 'protect_comments' );
		foreach ( $checkboxes as $key ) {
			$clean[ $key ] = ! empty( $input[ $key ] );
		}

		return $clean;
	}

	/**
	 * Renders the theme select field.
	 *
	 * @return void
	 */
	public function render_theme_field() {
		$settings = self::get_settings();
		$options  = array(
			'light' => __( 'Light', 'gaitcha-for-wp' ),
			'dark'  => __( 'Dark', 'gaitcha-for-wp' ),
			'auto'  => __( 'Follow system preference', 'gaitcha-for-wp' ),
		);

		echo '<select name="' . esc_attr( self::OPTION_NAME ) . '[theme]" id="gaitcha_theme">';
		foreach ( $options as $value => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $settings['theme'], $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	/**
	 * Renders the native forms section description.
	 *
	 * @return void
	 */
	public function render_native_forms_description() {
		echo '<p>' . esc_html__( 'Enable Gaitcha protection on WordPress built-in forms.', 'gaitcha-for-wp' ) . '</p>';
	}

	/**
	 * Renders a checkbox field.
	 *
	 * @param array $args Field arguments (key, label_for).
	 * @return void
	 */
	public function render_checkbox_field( $args ) {
		$settings = self::get_settings();
		$key      = $args['key'];
		$checked  = ! empty( $settings[ $key ] );

		printf(
			'<input type="checkbox" id="%s" name="%s[%s]" value="1"%s />',
			esc_attr( 'gaitcha_' . $key ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $key ),
			checked( $checked, true, false )
		);
	}

	/**
	 * Renders the settings page HTML.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		echo '<form action="options.php" method="post">';
		settings_fields( self::PAGE_SLUG );
		do_settings_sections( self::PAGE_SLUG );
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Returns the current settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		return wp_parse_args(
			get_option( self::OPTION_NAME, array() ),
			self::DEFAULTS
		);
	}

	/**
	 * Returns the configured widget theme.
	 *
	 * @return string 'light', 'dark', or 'auto'.
	 */
	public static function get_theme() {
		$settings = self::get_settings();

		return $settings['theme'];
	}

	/**
	 * Checks whether a specific native form protection is enabled.
	 *
	 * @param string $key Protection key (protect_login, protect_register, etc.).
	 * @return bool
	 */
	public static function is_protected( $key ) {
		$settings = self::get_settings();

		return ! empty( $settings[ $key ] );
	}
}
