<?php

namespace WPDM\Abilities;

/**
 * Per-user call limits for abilities, counted in fixed time windows.
 *
 * @since 3.3.73
 */
class RateLimiter {

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
		$slot = (int) floor( time() / $window );
		$key  = sprintf( 'wpdm_rl_%s_%d_%d', $bucket, get_current_user_id(), $slot );
		$used = (int) get_transient( $key );

		if ( $used + $cost > $limit ) {
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

		set_transient( $key, $used + $cost, $window );

		return true;
	}
}
