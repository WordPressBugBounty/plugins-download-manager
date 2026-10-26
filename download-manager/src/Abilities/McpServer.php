<?php

namespace WPDM\Abilities;

/**
 * A dedicated MCP server for Download Manager, registered with the MCP Adapter plugin.
 *
 * The adapter's default server only offers three generic meta tools (discover, describe,
 * execute), so an agent never sees Download Manager's tools, schemas or safety hints
 * directly. This server lists every enabled ability as a first-class MCP tool at
 * /wp-json/wpdm/mcp. The default server keeps working alongside it.
 *
 * @since 3.3.74
 */
class McpServer {

	const ID = 'wpdm';

	const ROUTE_NAMESPACE = 'wpdm';

	const ROUTE = 'mcp';

	public static function boot() {
		add_action( 'mcp_adapter_init', [ __CLASS__, 'register' ] );
	}

	/**
	 * Whether the MCP Adapter plugin is active.
	 *
	 * @return bool
	 */
	public static function available() {
		return class_exists( '\WP\MCP\Core\McpAdapter' );
	}

	/**
	 * The server's endpoint.
	 *
	 * @return string
	 */
	public static function url() {
		return rest_url( self::ROUTE_NAMESPACE . '/' . self::ROUTE );
	}

	/**
	 * Enabled abilities of this module, grouped by their MCP component type.
	 *
	 * Exposure follows the group toggles: `AbilityGroup::meta()` sets `mcp.public` from
	 * the toggle at registration, and abilities of disabled groups stay off the server.
	 *
	 * @return array{tool: string[], resource: string[], prompt: string[]} Ability names.
	 */
	public static function components() {
		$components = [
			'tool'     => [],
			'resource' => [],
			'prompt'   => [],
		];

		if ( ! Abilities::supported() ) {
			return $components;
		}

		foreach ( wp_get_abilities() as $ability ) {
			$name = $ability->get_name();
			if ( ! Abilities::owns( $name ) ) {
				continue;
			}

			$meta = $ability->get_meta();
			$mcp  = isset( $meta['mcp'] ) && is_array( $meta['mcp'] ) ? $meta['mcp'] : [];
			$open = isset( $mcp['public'] ) ? (bool) $mcp['public'] : ! empty( $meta['public'] );
			if ( ! $open ) {
				continue;
			}

			$type = isset( $mcp['type'], $components[ $mcp['type'] ] ) ? $mcp['type'] : 'tool';

			$components[ $type ][] = $name;
		}

		/**
		 * Filters the abilities the Download Manager MCP server offers.
		 *
		 * @param array $components Ability names keyed by MCP type: tool, resource, prompt.
		 */
		return apply_filters( 'wpdm_mcp_server_components', $components );
	}

	/**
	 * Create the server. Runs on `mcp_adapter_init`.
	 *
	 * @param \WP\MCP\Core\McpAdapter $adapter The adapter.
	 */
	public static function register( $adapter ) {
		if ( ! $adapter instanceof \WP\MCP\Core\McpAdapter ) {
			return;
		}

		$components = self::components();

		$result = $adapter->create_server(
			self::ID,
			self::ROUTE_NAMESPACE,
			self::ROUTE,
			'Download Manager',
			__( 'Manage download packages, files, links, categories and stats on this WordPress site.', 'download-manager' ),
			WPDM_VERSION,
			[ \WP\MCP\Transport\HttpTransport::class ],
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			null,
			$components['tool'],
			$components['resource'],
			$components['prompt']
		);

		if ( is_wp_error( $result ) ) {
			error_log( 'Download Manager MCP server was not created: ' . $result->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
