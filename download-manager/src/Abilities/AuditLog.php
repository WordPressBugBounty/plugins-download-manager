<?php

namespace WPDM\Abilities;

use WPDM\__\__;

/**
 * Records every WPDM ability call in {prefix}ahm_ability_log.
 *
 * @since 3.3.73
 */
class AuditLog {

	const DB_VERSION     = 1;
	const VERSION_OPTION = '__wpdm_ability_log_db_version';
	const RETENTION_DAYS = 90;
	const SUMMARY_LIMIT  = 2048;

	/**
	 * Input keys whose values never reach the log.
	 */
	const REDACT = [ 'password', 'content_base64' ];

	public static function table() {
		global $wpdb;

		return "{$wpdb->prefix}ahm_ability_log";
	}

	public static function maybeInstall() {
		if ( (int) get_option( self::VERSION_OPTION ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  created_at int(11) NOT NULL,
  user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  ability varchar(100) NOT NULL,
  object_id bigint(20) unsigned NOT NULL DEFAULT 0,
  status varchar(10) NOT NULL,
  error_code varchar(100) NOT NULL DEFAULT '',
  source varchar(10) NOT NULL DEFAULT '',
  ip varchar(45) NOT NULL DEFAULT '',
  input_summary text NOT NULL,
  PRIMARY KEY  (id),
  KEY created_at (created_at),
  KEY user_ability (user_id,ability)
) ENGINE=InnoDB {$charset};"
		);
		update_option( self::VERSION_OPTION, self::DB_VERSION, false );
	}

	/**
	 * Add one row.
	 *
	 * @param string $ability    Ability name.
	 * @param string $status     ok, error or denied.
	 * @param mixed  $input      The ability input; summarized and redacted.
	 * @param string $error_code WP_Error code when the call failed.
	 */
	public static function add( $ability, $status, $input = null, $error_code = '' ) {
		global $wpdb;
		self::maybeInstall();
		self::maybePurge();

		$wpdb->insert(
			self::table(),
			[
				'created_at'    => time(),
				'user_id'       => get_current_user_id(),
				'ability'       => substr( (string) $ability, 0, 100 ),
				'object_id'     => self::objectId( $input ),
				'status'        => $status,
				'error_code'    => substr( (string) $error_code, 0, 100 ),
				'source'        => self::source(),
				'ip'            => substr( __::get_client_ip(), 0, 45 ),
				'input_summary' => self::summarize( $input ),
			],
			[ '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * JSON of the input with secrets removed, cut to SUMMARY_LIMIT bytes.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return string
	 */
	public static function summarize( $input ) {
		if ( null === $input ) {
			return '';
		}
		if ( is_array( $input ) ) {
			array_walk_recursive(
				$input,
				function ( &$value, $key ) {
					if ( 'content_base64' === $key && is_string( $value ) ) {
						$value = '[' . strlen( $value ) . ' base64 chars]';
					} elseif ( in_array( $key, self::REDACT, true ) ) {
						$value = '[redacted]';
					}
				}
			);
		}
		$json = wp_json_encode( $input );

		return strlen( $json ) > self::SUMMARY_LIMIT ? substr( $json, 0, self::SUMMARY_LIMIT - 3 ) . '...' : $json;
	}

	/**
	 * Newest rows first.
	 *
	 * @param array $args limit, offset, user_id, ability, status.
	 *
	 * @return array
	 */
	public static function recent( array $args = [] ) {
		global $wpdb;
		self::maybeInstall();

		$where  = [ '1=1' ];
		$params = [];
		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$params[] = (int) $args['user_id'];
		}
		if ( ! empty( $args['ability'] ) ) {
			$where[]  = 'ability = %s';
			$params[] = $args['ability'];
		}
		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}
		$params[] = isset( $args['limit'] ) ? max( 1, min( 500, (int) $args['limit'] ) ) : 50;
		$params[] = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;

		$table = self::table();

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d OFFSET %d', $params ), ARRAY_A );
	}

	/**
	 * Signed-in users with rows in the log, for the filter.
	 *
	 * @return array[] ID and display_name.
	 */
	public static function users() {
		global $wpdb;
		self::maybeInstall();

		$users = [];
		foreach ( $wpdb->get_col( 'SELECT DISTINCT user_id FROM ' . self::table() . ' WHERE user_id > 0 ORDER BY user_id LIMIT 200' ) as $id ) {
			$user    = get_userdata( (int) $id );
			$users[] = [
				'ID'           => (int) $id,
				/* translators: %d: user ID */
				'display_name' => $user ? $user->display_name : sprintf( __( 'Deleted user #%d', 'download-manager' ), (int) $id ),
			];
		}

		return $users;
	}

	public static function clear() {
		global $wpdb;
		self::maybeInstall();
		$wpdb->query( 'DELETE FROM ' . self::table() );
	}

	/**
	 * Delete rows older than RETENTION_DAYS.
	 */
	public static function purge() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE created_at < %d', time() - self::RETENTION_DAYS * DAY_IN_SECONDS ) );
	}

	/**
	 * Purge at most once a day, on write, so no cron job is needed.
	 */
	private static function maybePurge() {
		if ( ! get_transient( 'wpdm_ability_log_purged' ) ) {
			self::purge();
			set_transient( 'wpdm_ability_log_purged', 1, DAY_IN_SECONDS );
		}
	}

	private static function objectId( $input ) {
		if ( ! is_array( $input ) ) {
			return 0;
		}
		foreach ( [ 'package_id', 'id', 'category_id' ] as $key ) {
			if ( isset( $input[ $key ] ) && is_numeric( $input[ $key ] ) ) {
				return (int) $input[ $key ];
			}
		}

		return 0;
	}

	/**
	 * Where the call came from: mcp, rest, cli or php.
	 *
	 * @return string
	 */
	public static function source() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return ! empty( $GLOBALS['argv'] ) && in_array( 'mcp-adapter', (array) $GLOBALS['argv'], true ) ? 'mcp' : 'cli';
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';

			return preg_match( '#^/(mcp|wpdm)/#', $route ) && false !== strpos( $route, 'mcp' ) ? 'mcp' : 'rest';
		}

		return 'php';
	}
}
