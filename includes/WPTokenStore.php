<?php
/**
 * WordPress option-based token store for anti-replay protection.
 *
 * Stores used token hashes in a single wp_option (autoload=no).
 * Self-cleaning: expired entries are purged on every checkAndAdd().
 *
 * For high-traffic sites, consider a Redis or custom DB implementation
 * via the gaitcha_config filter.
 *
 * @package GaitchaWP
 */

namespace GaitchaWP;

use Gaitcha\TokenStoreInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class WPTokenStore
 */
class WPTokenStore implements TokenStoreInterface {

	/**
	 * Option name in wp_options.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'gaitcha_used_tokens';

	/**
	 * Token TTL in seconds (used for self-cleaning).
	 *
	 * @var int
	 */
	private $ttl;

	/**
	 * @param int $ttl Token TTL in seconds.
	 */
	public function __construct( int $ttl = 120 ) {
		$this->ttl = $ttl;
	}

	/**
	 * Checks if a token hash has already been used.
	 *
	 * @param string $hash Token hash.
	 * @return bool True if the token was already used.
	 */
	public function has( string $hash ): bool {
		$entries = $this->load();
		return isset( $entries[ $hash ] );
	}

	/**
	 * Records a token hash as used.
	 *
	 * @param string $hash      Token hash.
	 * @param int    $timestamp Submission timestamp.
	 * @return void
	 */
	public function add( string $hash, int $timestamp ): void {
		$entries          = $this->load();
		$entries[ $hash ] = $timestamp;
		$this->save( $entries );
	}

	/**
	 * Atomically checks and records a token hash.
	 *
	 * Also purges expired entries to keep the option small.
	 * Not truly atomic (no DB-level lock), but the race window
	 * is negligible for captcha use cases.
	 *
	 * @param string $hash      Token hash.
	 * @param int    $timestamp Submission timestamp.
	 * @return bool True if the hash already existed (replay detected).
	 */
	public function checkAndAdd( string $hash, int $timestamp ): bool {
		$entries = $this->load();

		// Self-clean: purge expired entries.
		$cutoff = time() - $this->ttl;
		foreach ( $entries as $key => $ts ) {
			if ( $ts <= $cutoff ) {
				unset( $entries[ $key ] );
			}
		}

		if ( isset( $entries[ $hash ] ) ) {
			return true;
		}

		$entries[ $hash ] = $timestamp;
		$this->save( $entries );

		return false;
	}

	/**
	 * Purges entries older than the TTL.
	 *
	 * @param int      $ttl         TTL in seconds.
	 * @param int|null $current_time Current timestamp (default: time()).
	 * @return void
	 */
	public function purge( int $ttl, ?int $current_time = null ): void {
		$cutoff  = ( $current_time ?? time() ) - $ttl;
		$entries = $this->load();

		foreach ( $entries as $key => $timestamp ) {
			if ( $timestamp <= $cutoff ) {
				unset( $entries[ $key ] );
			}
		}

		$this->save( $entries );
	}

	/**
	 * Loads stored token hashes from wp_options.
	 *
	 * @return array<string, int> Hash => timestamp entries.
	 */
	private function load(): array {
		$entries = get_option( self::OPTION_NAME, array() );
		return is_array( $entries ) ? $entries : array();
	}

	/**
	 * Saves token hashes to wp_options.
	 *
	 * @param array<string, int> $entries Hash => timestamp entries.
	 * @return void
	 */
	private function save( array $entries ): void {
		if ( empty( $entries ) ) {
			delete_option( self::OPTION_NAME );
			return;
		}

		// Use update_option with autoload=no to avoid loading on every page.
		if ( false === get_option( self::OPTION_NAME ) ) {
			add_option( self::OPTION_NAME, $entries, '', false );
		} else {
			update_option( self::OPTION_NAME, $entries, false );
		}
	}
}
