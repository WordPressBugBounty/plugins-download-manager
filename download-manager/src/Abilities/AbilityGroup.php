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
	 *                     (readonly, destructive, idempotent) in place of `meta`, and
	 *                     optionally `mcp` (type, uri, mimeType) for resources and prompts.
	 *
	 * @return \WP_Ability|null
	 */
	protected function registerAbility( $name, array $args ) {
		$annotations = isset( $args['annotations'] ) ? $args['annotations'] : [];
		$mcp         = isset( $args['mcp'] ) ? $args['mcp'] : [];
		unset( $args['annotations'], $args['mcp'] );

		$args['category']         = static::CATEGORY;
		$args['execute_callback'] = $this->wrap( $name, $args['execute_callback'], ! empty( $annotations['readonly'] ) );
		$args['meta']             = $this->meta( $annotations, $mcp );

		return wp_register_ability( $name, $args );
	}

	/**
	 * Register a read-only ability that MCP clients see as a resource: a document at a
	 * fixed URI, read without arguments.
	 *
	 * The execute callback returns MCP resource contents: a list of items with `uri`,
	 * `mimeType` and `text`. Use {@see AbilityGroup::jsonContents()} to build one.
	 *
	 * @param string $name Ability name, e.g. `wpdm/category-tree`.
	 * @param array  $args label, description, permission_callback, execute_callback,
	 *                     `uri` (e.g. `wpdm://categories`) and optionally `mime_type`.
	 *
	 * @return \WP_Ability|null
	 */
	protected function registerResource( $name, array $args ) {
		$uri       = $args['uri'];
		$mime_type = isset( $args['mime_type'] ) ? $args['mime_type'] : 'application/json';
		unset( $args['uri'], $args['mime_type'] );

		return $this->registerAbility(
			$name,
			$args + [
				'output_schema' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'uri'      => [ 'type' => 'string' ],
							'mimeType' => [ 'type' => 'string' ],
							'text'     => [ 'type' => 'string' ],
						],
						'required'   => [ 'uri', 'text' ],
					],
				],
				'annotations'   => [
					'readonly'   => true,
					'idempotent' => true,
				],
				'mcp'           => [
					'type'     => 'resource',
					'uri'      => $uri,
					'mimeType' => $mime_type,
				],
			]
		);
	}

	/**
	 * Register an ability that MCP clients see as a prompt: a reusable instruction
	 * template with named string arguments.
	 *
	 * The input schema's properties become the prompt's arguments, so keep them strings.
	 * The execute callback returns `[ 'messages' => [ [ 'role' => 'user', 'content' => ... ] ] ]`;
	 * use {@see AbilityGroup::promptMessages()} to build it.
	 *
	 * @param string $name Ability name, e.g. `wpdm/publish-release`.
	 * @param array  $args label, description, input_schema, permission_callback, execute_callback.
	 *
	 * @return \WP_Ability|null
	 */
	protected function registerPrompt( $name, array $args ) {
		return $this->registerAbility(
			$name,
			$args + [
				'output_schema' => [
					'type'       => 'object',
					'properties' => [
						'messages' => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'role'    => [ 'type' => 'string' ],
									'content' => [ 'type' => 'object' ],
								],
								'required'   => [ 'role', 'content' ],
							],
						],
					],
					'required'   => [ 'messages' ],
				],
				'annotations'   => [
					'readonly'   => true,
					'idempotent' => true,
				],
				'mcp'           => [ 'type' => 'prompt' ],
			]
		);
	}

	/**
	 * Resource contents holding one JSON document.
	 *
	 * @param string $uri  The resource URI.
	 * @param mixed  $data Anything wp_json_encode() accepts.
	 *
	 * @return array
	 */
	protected function jsonContents( $uri, $data ) {
		return [
			[
				'uri'      => $uri,
				'mimeType' => 'application/json',
				'text'     => wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
			],
		];
	}

	/**
	 * A prompt result with one user message.
	 *
	 * @param string $text The instructions.
	 *
	 * @return array
	 */
	protected function promptMessages( $text ) {
		return [
			'messages' => [
				[
					'role'    => 'user',
					'content' => [
						'type' => 'text',
						'text' => $text,
					],
				],
			],
		];
	}

	/**
	 * Annotations plus exposure flags from the group's toggle.
	 *
	 * `show_in_rest` and `mcp.public` are set explicitly: core 7.1's `meta.public`
	 * shortcut doesn't exist on 6.9 or 7.0.
	 *
	 * @param array $annotations readonly, destructive, idempotent.
	 * @param array $mcp         MCP extras: type (tool, resource, prompt), uri, mimeType.
	 *
	 * @return array
	 */
	protected function meta( array $annotations, array $mcp = [] ) {
		$public = Abilities::groupEnabled( static::CATEGORY );

		return [
			'annotations'  => $annotations + [
				'readonly'    => false,
				'destructive' => false,
				'idempotent'  => false,
			],
			'show_in_rest' => $public,
			'mcp'          => $mcp + [
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
