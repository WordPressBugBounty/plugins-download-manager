<?php

namespace WPDM\Abilities;

/**
 * Per-user call limits for abilities, counted in fixed time windows.
 *
 * Counters are incremented atomically (an object cache increment, or a single
 * UPDATE on the options table) so parallel calls can't slip past the limit.
 *
 * @since 3.3.73
 */
class RateLimiter {

	const CACHE_GROUP = 'wpdm_rate_limit';

	/**
	 * Count $cost units against the current user's limit for a bucket.
	 *
	 * @param string $bucket Bucket name, e.g. `writes` or `emails`.
	 * @param int    $limit  Units allowed per window.
	 * @param int    $window Window length in seconds.
	 * @param int    $cost   Units this call uses.
	 *
	 * @return true|\WP_Error `wpdm_rate_limited` (429) when the call would go over the limit.
	 */
	public static function hit( $bucket, $limit, $window, $cost = 1 ) {
		$prefix = sprintf( 'wpdm_rl_%s_%d_', $bucket, get_current_user_id() );
		$key    = $prefix . (int) floor( time() / $window );
		$used   = self::increment( $prefix, $key, (int) $cost, (int) $window );

		if ( $used > $limit ) {
			// A refused call doesn't use up the window.
			self::increment( $prefix, $key, -(int) $cost, (int) $window );

			return new \WP_Error(
				'wpdm_rate_limited',
				sprintf(
					/* translators: 1: limit, 2: seconds in the window */
					__( 'Rate limit reached: %1$d per %2$d seconds. Try again shortly.', 'download-manager' ),
					$limit,
					$window
				),
				[ 'status' => 429 ]
			);
		}

		return true;
	}

	/**
	 * Add $by to a counter and return the new value.
	 *
	 * @param string $prefix Key prefix shared by this user's windows for the bucket.
	 * @param string $key    Counter key for the current window.
	 * @param int    $by     Units to add; negative to give units back.
	 * @param int    $window Window length in seconds, for the cache expiry.
	 *
	 * @return int
	 */
	private static function increment( $prefix, $key, $by, $window ) {
		if ( wp_using_ext_object_cache() ) {
			wp_cache_add( $key, 0, self::CACHE_GROUP, $window );
			$value = $by < 0 ? wp_cache_decr( $key, -$by, self::CACHE_GROUP ) : wp_cache_incr( $key, $by, self::CACHE_GROUP );
			if ( false !== $value ) {
				return (int) $value;
			}
		}

		return self::incrementOption( $prefix, $key, $by );
	}

	/**
	 * Atomic counter in the options table, bypassing the options cache. A counter for a
	 * new window deletes the user's older windows for the bucket.
	 *
	 * @param string $prefix Key prefix.
	 * @param string $key    Counter key.
	 * @param int    $by     Units to add.
	 *
	 * @return int
	 */
	private static function incrementOption( $prefix, $key, $by ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %d, 'off') ON DUPLICATE KEY UPDATE option_value = GREATEST(0, CAST(option_value AS SIGNED) + %d)",
				$key,
				max( 0, $by ),
				$by
			)
		);
		if ( 1 === $wpdb->rows_affected ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name <> %s", $wpdb->esc_like( $prefix ) . '%', $key ) );
		}

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key ) );
	}
}
