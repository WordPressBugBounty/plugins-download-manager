<?php

namespace WPDM\Abilities\Admin;

use WPDM\Abilities\Abilities;
use WPDM\Abilities\AuditLog;

/**
 * "AI Abilities" tab in Download Manager settings: group toggles, agent setup and the audit log.
 *
 * @since 3.3.73
 */
class SettingsTab {

	const TAB = 'ai-abilities';

	/**
	 * Highest value each limit accepts.
	 */
	const LIMIT_RANGES = [
		'writes_per_minute' => 1000,
		'emails_per_hour'   => 1000,
		'inline_upload_mb'  => 100,
		'url_download_mb'   => 2048,
		'max_link_hours'    => 8760,
	];

	/**
	 * Labels for the limit fields.
	 *
	 * @return array
	 */
	public static function limitLabels() {
		return [
			'writes_per_minute' => __( 'Write calls per user per minute', 'download-manager' ),
			'emails_per_hour'   => __( 'Emailed links per user per hour', 'download-manager' ),
			'inline_upload_mb'  => __( 'Largest inline upload (MB)', 'download-manager' ),
			'url_download_mb'   => __( 'Largest file fetched from a URL (MB)', 'download-manager' ),
			'max_link_hours'    => __( 'Longest download link lifetime (hours)', 'download-manager' ),
		];
	}

	public static function boot() {
		add_filter( 'add_wpdm_settings_tab', [ __CLASS__, 'addTab' ] );
		add_action( 'wp_ajax_wpdm_ability_log', [ __CLASS__, 'ajaxLog' ] );
		add_action( 'wp_ajax_wpdm_ability_log_clear', [ __CLASS__, 'ajaxClear' ] );
	}

	public static function addTab( $tabs ) {
		$tabs[ self::TAB ] = wpdm_create_settings_tab( self::TAB, __( 'AI Abilities', 'download-manager' ), [ __CLASS__, 'render' ], 'fa fa-robot' );

		return $tabs;
	}

	/**
	 * Tab callback: saves on submit, otherwise prints the tab.
	 */
	public static function render() {
		if ( ! current_user_can( WPDM_ADMIN_CAP ) ) {
			return;
		}

		if ( wpdm_query_var( 'task' ) === 'wdm_save_settings' && wpdm_query_var( 'section' ) === self::TAB ) {
			if ( ! isset( $_POST['__wpdms_nonce'] ) || ! wp_verify_nonce( $_POST['__wpdms_nonce'], WPDMSET_NONCE_KEY ) ) {
				die( esc_html__( 'Security token is expired! Refresh the page and try again.', 'download-manager' ) );
			}
			$groups = isset( $_POST['__wpdm_abilities_groups'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['__wpdm_abilities_groups'] ) ) : [];
			update_option( Abilities::GROUPS_OPTION, array_values( array_intersect( $groups, array_keys( Abilities::groups() ) ) ), false );

			$posted = isset( $_POST['__wpdm_abilities_limits'] ) ? (array) wp_unslash( $_POST['__wpdm_abilities_limits'] ) : [];
			$limits = [];
			foreach ( self::LIMIT_RANGES as $key => $max ) {
				$limits[ $key ] = isset( $posted[ $key ] ) ? min( $max, max( 1, (int) $posted[ $key ] ) ) : Abilities::LIMIT_DEFAULTS[ $key ];
			}
			update_option( Abilities::LIMITS_OPTION, $limits, false );

			die( esc_html__( 'Settings Saved Successfully!', 'download-manager' ) );
		}

		include __DIR__ . '/views/settings.php';
	}

	/**
	 * Abilities registered in one category, for the group list.
	 *
	 * @param string $category Category slug.
	 *
	 * @return \WP_Ability[]
	 */
	public static function abilitiesIn( $category ) {
		if ( ! Abilities::supported() ) {
			return [];
		}

		return array_filter(
			wp_get_abilities(),
			function ( $ability ) use ( $category ) {
				return $ability->get_category() === $category;
			}
		);
	}

	/**
	 * Badge for an ability: resource, prompt, read-only, write or destructive.
	 *
	 * @param \WP_Ability $ability Ability.
	 *
	 * @return array [ css modifier, label ]
	 */
	public static function badge( $ability ) {
		$mcp = (array) $ability->get_meta_item( 'mcp' );
		if ( isset( $mcp['type'] ) && 'resource' === $mcp['type'] ) {
			return [ 'resource', __( 'Resource', 'download-manager' ) ];
		}
		if ( isset( $mcp['type'] ) && 'prompt' === $mcp['type'] ) {
			return [ 'prompt', __( 'Prompt', 'download-manager' ) ];
		}

		$annotations = (array) $ability->get_meta_item( 'annotations' );
		if ( ! empty( $annotations['readonly'] ) ) {
			return [ 'read', __( 'Read-only', 'download-manager' ) ];
		}
		if ( ! empty( $annotations['destructive'] ) ) {
			return [ 'destructive', __( 'Destructive', 'download-manager' ) ];
		}

		return [ 'write', __( 'Write', 'download-manager' ) ];
	}

	/**
	 * Version of the active MCP Adapter, or '' when it isn't active.
	 *
	 * @return string
	 */
	public static function mcpAdapterVersion() {
		if ( class_exists( 'WP\MCP\Core\McpAdapter' ) && defined( 'WP\MCP\Core\McpAdapter::VERSION' ) ) {
			return (string) \WP\MCP\Core\McpAdapter::VERSION;
		}

		return defined( 'WP_MCP_VERSION' ) ? (string) WP_MCP_VERSION : '';
	}

	/**
	 * Rows per page of the audit log.
	 */
	const PAGE = 50;

	/**
	 * The first page of audit log rows as table HTML.
	 *
	 * @param array $filters status, ability, user_id.
	 *
	 * @return string
	 */
	public static function logRows( array $filters = [] ) {
		return self::logPage( $filters )[0];
	}

	/**
	 * One page of audit log rows as table HTML, and whether another page follows.
	 *
	 * @param array $filters status, ability, user_id.
	 * @param int   $offset  Rows to skip.
	 *
	 * @return array [ html, more ]
	 */
	public static function logPage( array $filters = [], $offset = 0 ) {
		$offset = max( 0, (int) $offset );
		$rows   = AuditLog::recent(
			$filters + [
				'limit'  => self::PAGE + 1,
				'offset' => $offset,
			]
		);
		$more   = count( $rows ) > self::PAGE;
		$rows   = array_slice( $rows, 0, self::PAGE );
		if ( ! $rows ) {
			return [ $offset ? '' : '<tr><td colspan="6" class="text-muted">' . esc_html__( 'No ability calls yet.', 'download-manager' ) . '</td></tr>', false ];
		}

		$html = '';
		foreach ( $rows as $row ) {
			$user  = $row['user_id'] ? get_userdata( (int) $row['user_id'] ) : null;
			$html .= '<tr>'
				. '<td>' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $row['created_at'] ) ) . '</td>'
				. '<td>' . esc_html( $user ? $user->display_name : __( 'Guest', 'download-manager' ) ) . '</td>'
				. '<td><code>' . esc_html( $row['ability'] ) . '</code></td>'
				. '<td>' . ( (int) $row['object_id'] ? (int) $row['object_id'] : '&mdash;' ) . '</td>'
				. '<td><span class="wpdm-abl-status wpdm-abl-status--' . esc_attr( $row['status'] ) . '">' . esc_html( $row['status'] ) . '</span>'
				. ( $row['error_code'] ? ' <small class="text-muted">' . esc_html( $row['error_code'] ) . '</small>' : '' ) . '</td>'
				. '<td>' . esc_html( $row['source'] ) . '</td>'
				. '</tr>';
		}

		return [ $html, $more ];
	}

	public static function ajaxLog() {
		check_ajax_referer( 'wpdm_ability_log', '_nonce' );
		if ( ! current_user_can( WPDM_ADMIN_CAP ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'download-manager' ) ], 403 );
		}
		list( $html, $more ) = self::logPage(
			[
				'status'  => sanitize_key( wpdm_query_var( 'status' ) ),
				'ability' => sanitize_text_field( wpdm_query_var( 'ability' ) ),
				'user_id' => (int) wpdm_query_var( 'user_id' ),
			],
			(int) wpdm_query_var( 'offset' )
		);
		wp_send_json_success(
			[
				'html' => $html,
				'more' => $more,
			]
		);
	}

	public static function ajaxClear() {
		check_ajax_referer( 'wpdm_ability_log', '_nonce' );
		if ( ! current_user_can( WPDM_ADMIN_CAP ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'download-manager' ) ], 403 );
		}
		AuditLog::clear();
		wp_send_json_success( [ 'html' => self::logRows() ] );
	}
}
