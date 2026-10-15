<?php

namespace WPDM\Abilities;

/**
 * Base class for a group of abilities that share one category and one settings toggle.
 *
 * @since 3.3.73
 */
abstract class AbilityGroup {

	/**
	 * Ability category slug. Also the key of the group's settings toggle.
	 */
	const CATEGORY = '';

	/**
	 * Register this group's abilities. Runs on `wp_abilities_api_init`.
	 */
	abstract public function register();

	/**
	 * Register one ability with the group's category, exposure flags and audit wrapper.
	 *
	 * @param string $name Ability name, e.g. `wpdm/list-packages`.
	 * @param array  $args wp_register_ability() args, plus `annotations`
	 *                     (readonly, destructive, idempotent) in place of `meta`.
	 *
	 * @return \WP_Ability|null
	 */
	protected function registerAbility( $name, array $args ) {
		$annotations = isset( $args['annotations'] ) ? $args['annotations'] : [];
		unset( $args['annotations'] );

		$args['category']         = static::CATEGORY;
		$args['execute_callback'] = $this->wrap( $name, $args['execute_callback'], ! empty( $annotations['readonly'] ) );
		$args['meta']             = $this->meta( $annotations );

		return wp_register_ability( $name, $args );
	}

	/**
	 * Annotations plus exposure flags from the group's toggle.
	 *
	 * `show_in_rest` and `mcp.public` are set explicitly: core 7.1's `meta.public`
	 * shortcut doesn't exist on 6.9 or 7.0.
	 *
	 * @param array $annotations readonly, destructive, idempotent.
	 *
	 * @return array
	 */
	protected function meta( array $annotations ) {
		$public = Abilities::groupEnabled( static::CATEGORY );

		return [
			'annotations'  => $annotations + [
				'readonly'    => false,
				'destructive' => false,
				'idempotent'  => false,
			],
			'show_in_rest' => $public,
			'mcp'          => [
				'public' => $public,
				'type'   => 'tool',
			],
		];
	}

	/**
	 * Run an execute callback and record the outcome in the audit log.
	 * Calls that change data count against the per-user write limit first.
	 *
	 * @param string   $name     Ability name.
	 * @param callable $callback Receives the validated input array.
	 * @param bool     $readonly Whether the ability only reads.
	 *
	 * @return \Closure
	 */
	protected function wrap( $name, callable $callback, $readonly = true ) {
		return function ( $input = null ) use ( $name, $callback, $readonly ) {
			$input  = is_array( $input ) ? $input : [];
			$result = $readonly ? true : RateLimiter::hit( 'writes', Abilities::limit( 'writes_per_minute' ), MINUTE_IN_SECONDS );
			if ( true === $result ) {
				$result = call_user_func( $callback, $input );
			}
			if ( is_wp_error( $result ) ) {
				AuditLog::add( $name, 'error', $input, $result->get_error_code() );
			} else {
				AuditLog::add( $name, 'ok', $input );
			}

			return $result;
		};
	}

	/**
	 * A WP_Error with an HTTP status.
	 *
	 * @param string $code    Error code, `wpdm_` prefixed.
	 * @param string $message Translated message.
	 * @param int    $status  HTTP status.
	 *
	 * @return \WP_Error
	 */
	protected function error( $code, $message, $status ) {
		return new \WP_Error( $code, $message, [ 'status' => $status ] );
	}

	/**
	 * The standard list envelope.
	 *
	 * @param array $items    Items on this page.
	 * @param int   $total    Items across all pages.
	 * @param int   $page     Page number.
	 * @param int   $per_page Page size.
	 *
	 * @return array
	 */
	protected function page( array $items, $total, $page, $per_page ) {
		return [
			'items'       => $items,
			'total'       => (int) $total,
			'page'        => (int) $page,
			'per_page'    => (int) $per_page,
			'total_pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 0,
		];
	}

	/**
	 * Output schema for the list envelope.
	 *
	 * @param array $item_schema Schema of one item.
	 *
	 * @return array
	 */
	protected function pageSchema( array $item_schema ) {
		return [
			'type'       => 'object',
			'properties' => [
				'items'       => [
					'type'  => 'array',
					'items' => $item_schema,
				],
				'total'       => [ 'type' => 'integer' ],
				'page'        => [ 'type' => 'integer' ],
				'per_page'    => [ 'type' => 'integer' ],
				'total_pages' => [ 'type' => 'integer' ],
			],
			'required'   => [ 'items', 'total', 'page', 'per_page', 'total_pages' ],
		];
	}

	/**
	 * Input properties for page and per_page.
	 *
	 * @return array
	 */
	protected function pagingProperties() {
		return [
			'page'     => [
				'type'        => 'integer',
				'minimum'     => 1,
				'default'     => 1,
				'description' => 'Page number, starting at 1.',
			],
			'per_page' => [
				'type'        => 'integer',
				'minimum'     => 1,
				'maximum'     => 100,
				'default'     => 20,
				'description' => 'Items per page, up to 100.',
			],
		];
	}

	/**
	 * ISO 8601 date in the site's timezone.
	 *
	 * @param int $timestamp Unix timestamp.
	 *
	 * @return string
	 */
	protected function isoDate( $timestamp ) {
		return wp_date( DATE_ATOM, (int) $timestamp );
	}

	/**
	 * HTML reduced to plain text and clipped, so author-written content reaches agents as data.
	 *
	 * @param string $html  HTML.
	 * @param int    $limit Maximum characters.
	 *
	 * @return array [ text, truncated ]
	 */
	protected function plainText( $html, $limit ) {
		$text = trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES, 'UTF-8' ) ) );
		if ( mb_strlen( $text ) > $limit ) {
			return [ mb_substr( $text, 0, $limit ), true ];
		}

		return [ $text, false ];
	}
}
