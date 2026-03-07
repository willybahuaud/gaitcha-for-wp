<?php
/**
 * Auto-update via GitHub Releases.
 *
 * Hooks into the WordPress plugin update system to check for
 * new releases on the GitHub repository.
 *
 * @package GaitchaWP
 */

namespace GaitchaWP;

defined( 'ABSPATH' ) || exit;

/**
 * Class Updater
 */
class Updater {

	/**
	 * GitHub repository owner.
	 *
	 * @var string
	 */
	const GITHUB_OWNER = 'willybahuaud';

	/**
	 * GitHub repository name.
	 *
	 * @var string
	 */
	const GITHUB_REPO = 'gaitcha-for-wp';

	/**
	 * GitHub API base URL.
	 *
	 * @var string
	 */
	const GITHUB_API_URL = 'https://api.github.com';

	/**
	 * Cache duration in seconds (12 hours).
	 *
	 * @var int
	 */
	const CACHE_DURATION = 43200;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Plugin basename (folder/file.php).
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Latest GitHub release data (in-memory cache).
	 *
	 * @var object|false|null
	 */
	private $github_release = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->plugin_slug     = 'gaitcha-for-wp';
		$this->plugin_basename = GAITCHA_WP_BASENAME;
	}

	/**
	 * Registers WordPress update hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_directory_name' ), 10, 4 );
	}

	/**
	 * Checks GitHub for a newer release and injects it into the update transient.
	 *
	 * @param object $transient Plugin update transient.
	 * @return object Modified transient.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_github_release();

		if ( ! $release ) {
			return $transient;
		}

		$latest_version  = $this->parse_version( $release->tag_name );
		$current_version = GAITCHA_WP_VERSION;

		if ( version_compare( $latest_version, $current_version, '>' ) ) {
			$download_url = $this->get_download_url( $release );

			if ( $download_url ) {
				$transient->response[ $this->plugin_basename ] = (object) array(
					'slug'        => $this->plugin_slug,
					'plugin'      => $this->plugin_basename,
					'new_version' => $latest_version,
					'url'         => $release->html_url,
					'package'     => $download_url,
					'icons'       => array(),
					'banners'     => array(),
					'tested'      => '',
					'requires'    => '6.0',
				);
			}
		}

		return $transient;
	}

	/**
	 * Provides plugin details for the "View details" popup.
	 *
	 * @param false|object|array $result Default result.
	 * @param string             $action API action type.
	 * @param object             $args   Request arguments.
	 * @return false|object Plugin info or false.
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || $this->plugin_slug !== $args->slug ) {
			return $result;
		}

		$release = $this->get_github_release();

		if ( ! $release ) {
			return $result;
		}

		$latest_version = $this->parse_version( $release->tag_name );

		return (object) array(
			'name'          => 'Gaitcha for WordPress',
			'slug'          => $this->plugin_slug,
			'version'       => $latest_version,
			'author'        => '<a href="https://github.com/' . self::GITHUB_OWNER . '">Willy Bahuaud</a>',
			'homepage'      => 'https://github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO,
			'requires'      => '6.0',
			'tested'        => '',
			'downloaded'    => 0,
			'last_updated'  => $release->published_at,
			'sections'      => array(
				'description' => $this->get_plugin_description(),
				'changelog'   => $this->format_changelog( $release->body ),
			),
			'download_link' => $this->get_download_url( $release ),
		);
	}

	/**
	 * Fixes the extracted directory name after GitHub ZIP download.
	 *
	 * GitHub names folders "repo-tag" instead of "repo".
	 *
	 * @param string       $source        Extracted directory path.
	 * @param string       $remote_source Remote source path.
	 * @param \WP_Upgrader $upgrader      Upgrader instance.
	 * @param array        $hook_extra    Extra hook data.
	 * @return string|\WP_Error Corrected path or error.
	 */
	public function fix_directory_name( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;

		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $source;
		}

		$source_normalized = untrailingslashit( $source );
		$expected_dir      = untrailingslashit( trailingslashit( $remote_source ) . $this->plugin_slug );

		if ( $source_normalized === $expected_dir ) {
			return $source;
		}

		if ( basename( $source_normalized ) === $this->plugin_slug ) {
			return $source;
		}

		if ( $wp_filesystem->move( $source, trailingslashit( $expected_dir ) ) ) {
			return trailingslashit( $expected_dir );
		}

		return new \WP_Error(
			'rename_failed',
			__( 'Unable to rename the plugin directory.', 'gaitcha-for-wp' )
		);
	}

	/**
	 * Fetches the latest release data from GitHub API.
	 *
	 * Uses dual cache: in-memory for the current request + transient for 12h.
	 *
	 * @return object|false Release data or false on error.
	 */
	private function get_github_release() {
		if ( null !== $this->github_release ) {
			return $this->github_release;
		}

		$cache_key = 'gaitcha_wp_github_release';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			$this->github_release = $cached;
			return $cached;
		}

		$api_url = sprintf(
			'%s/repos/%s/%s/releases/latest',
			self::GITHUB_API_URL,
			self::GITHUB_OWNER,
			self::GITHUB_REPO
		);

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github.v3+json',
					'User-Agent' => 'GaitchaWP/' . GAITCHA_WP_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->github_release = false;
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$this->github_release = false;
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( empty( $data ) || ! isset( $data->tag_name ) ) {
			$this->github_release = false;
			return false;
		}

		set_transient( $cache_key, $data, self::CACHE_DURATION );
		$this->github_release = $data;

		return $data;
	}

	/**
	 * Extracts version number from a GitHub tag name.
	 *
	 * Strips the "v" prefix (v1.0.0 -> 1.0.0).
	 *
	 * @param string $tag_name GitHub tag name.
	 * @return string Version number.
	 */
	private function parse_version( $tag_name ) {
		return ltrim( $tag_name, 'vV' );
	}

	/**
	 * Gets the ZIP download URL from a release.
	 *
	 * Prefers an uploaded ZIP asset, falls back to the auto-generated zipball.
	 *
	 * @param object $release GitHub release data.
	 * @return string|false Download URL or false.
	 */
	private function get_download_url( $release ) {
		if ( ! empty( $release->assets ) && is_array( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( isset( $asset->content_type ) && 'application/zip' === $asset->content_type ) {
					return $asset->browser_download_url;
				}
				if ( isset( $asset->name ) && '.zip' === substr( $asset->name, -4 ) ) {
					return $asset->browser_download_url;
				}
			}
		}

		if ( ! empty( $release->zipball_url ) ) {
			return $release->zipball_url;
		}

		return false;
	}

	/**
	 * Returns the plugin description for the details popup.
	 *
	 * @return string HTML description.
	 */
	private function get_plugin_description() {
		return '<p>' . esc_html__(
			'Self-hosted behavioral captcha for WordPress — no external dependency.',
			'gaitcha-for-wp'
		) . '</p>';
	}

	/**
	 * Converts release body markdown to basic HTML for the changelog tab.
	 *
	 * @param string $body Release body (Markdown).
	 * @return string Formatted HTML.
	 */
	private function format_changelog( $body ) {
		if ( empty( $body ) ) {
			return '<p>' . esc_html__( 'No release notes available.', 'gaitcha-for-wp' ) . '</p>';
		}

		$html = esc_html( $body );
		$html = nl2br( $html );

		$html = preg_replace( '/^- (.+)$/m', '<li>$1</li>', $html );
		$html = preg_replace( '/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html );

		return $html;
	}
}
