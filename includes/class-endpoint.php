<?php
/**
 * REST API endpoint for Gaitcha token initialization.
 *
 * Registers POST gaitcha/v1/init and delegates to the core handleInit().
 *
 * @package GaitchaWP
 */

namespace GaitchaWP;

use Gaitcha\AbstractEndpoint;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class Endpoint
 */
class Endpoint extends AbstractEndpoint {

	/**
	 * Registers the REST route.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	/**
	 * Callback for rest_api_init — declares the route.
	 *
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			'gaitcha/v1',
			'/init',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_request' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handles the REST request and returns token data.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response
	 */
	public function handle_request( WP_REST_Request $request ) {
		return new WP_REST_Response( $this->handleInit(), 200 );
	}

	/**
	 * Returns the full URL of the init endpoint.
	 *
	 * @return string
	 */
	public function get_url() {
		return rest_url( 'gaitcha/v1/init' );
	}

	/**
	 * Not used — WP REST API handles JSON responses natively.
	 *
	 * @param array $data Response data.
	 * @return void
	 */
	protected function sendJsonResponse( array $data ): void {
		// Intentionally empty: WP_REST_Response handles serialization.
	}
}
