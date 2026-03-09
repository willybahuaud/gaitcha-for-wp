<?php
/**
 * Interface for form builder connectors.
 *
 * @package GaitchaWP\Connectors
 */

namespace GaitchaWP\Connectors;

defined( 'ABSPATH' ) || exit;

/**
 * Interface ConnectorInterface
 */
interface ConnectorInterface {

	/**
	 * Registers WordPress hooks for this connector.
	 *
	 * @return void
	 */
	public function register_hooks(): void;
}
