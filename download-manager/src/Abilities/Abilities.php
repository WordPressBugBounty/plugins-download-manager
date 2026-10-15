<?php

namespace WPDM\Abilities;

use WPDM\Abilities\Admin\SettingsTab;

/**
 * Registers Download Manager operations with the WordPress Abilities API (6.9+),
 * so AI agents can run them through the MCP Adapter.
 *
 * Each group is one ability category and one settings toggle. Groups are registered
 * on every supported site but stay private to REST and MCP until an admin enables them.
 *
 * @since 3.3.73
 */
class Abilities {

	const GROUPS_OPTION = '__wpdm_abilities_groups';

	const LIMITS_OPTION = '__wpdm_abilities_limits';

	/**
	 * Default limits, editable in the settings tab.
	 */
	const LIMIT_DEFAULTS = [
		'writes_per_minute' => 30,
		'emails_per_hour'   => 20,
		'inline_upload_mb'  => 10,
		'url_download_mb'   => 200,
		'max_link_hours'    => 720,
	];

	/**
	 * Ability namespaces this module owns, for logging.
	 */
	const NAMESPACES = [ 'wpdm/', 'wpdmpp/' ];

	public static function boot() {
		add_action( 'wp_abilities_api_categories_init', [ __CLASS__, 'registerCategories' ] );
		add_action( 'wp_abilities_api_init', [ __CLASS__, 'registerAbilities' ] );
		add_action( 'wp_ability_invoked', [ __CLASS__, 'logRejected' ], 10, 3 );

		if ( is_admin() ) {
			SettingsTab::boot();
		}
	}

	/**
	 * Whether this WordPress version has the Abilities API.
	 *
	 * @return bool
	 */
	public static function supported() {
		return function_exists( 'wp_register_ability' );
	}

	/**
	 * Ability groups: category slug => label, description and the class that registers them.
	 *
	 * @return array
	 */
	public static function groups() {
		$groups = [
			'wpdm-packages'   => [
				'label'       => __( 'Packages', 'download-manager' ),
				'description' => __( 'Find, read, create, edit, copy and trash packages.', 'download-manager' ),
				'class'       => Groups\Packages::class,
			],
			'wpdm-files'      => [
				'label'       => __( 'Files', 'download-manager' ),
				'description' => __( 'Attach files to packages and detach them. Detached files stay on the server.', 'download-manager' ),
				'class'       => Groups\Files::class,
			],
			'wpdm-links'      => [
				'label'       => __( 'Download links', 'download-manager' ),
				'description' => __( 'Create expiring download links and email them. Links bypass a package\'s locks and access roles.', 'download-manager' ),
				'class'       => Groups\Links::class,
			],
			'wpdm-categories' => [
				'label'       => __( 'Categories', 'download-manager' ),
				'description' => __( 'Read the package category tree and create categories.', 'download-manager' ),
				'class'       => Groups\Categories::class,
			],
			'wpdm-stats'      => [
				'label'       => __( 'Stats', 'download-manager' ),
				'description' => __( 'Read download counts and weekly or monthly activity reports.', 'download-manager' ),
				'class'       => Groups\Stats::class,
			],
		];

		/**
		 * Filters the ability groups. Add-ons add a group here to get a category and a settings toggle.
		 *
		 * @param array $groups Category slug => [ label, description, class ].
		 */
		return apply_filters( 'wpdm_ability_groups', $groups );
	}

	/**
	 * Category slugs the admin has made public to REST and MCP.
	 *
	 * @return string[]
	 */
	public static function enabledGroups() {
		$enabled = get_option( self::GROUPS_OPTION, [] );

		return is_array( $enabled ) ? array_values( array_intersect( $enabled, array_keys( self::groups() ) ) ) : [];
	}

	public static function groupEnabled( $slug ) {
		return in_array( $slug, self::enabledGroups(), true );
	}

	/**
	 * One limit from settings, falling back to its default.
	 *
	 * @param string $key A key of LIMIT_DEFAULTS.
	 *
	 * @return int At least 1.
	 */
	public static function limit( $key ) {
		$saved = get_option( self::LIMITS_OPTION, [] );
		$value = is_array( $saved ) && isset( $saved[ $key ] ) ? (int) $saved[ $key ] : self::LIMIT_DEFAULTS[ $key ];

		return max( 1, $value );
	}

	public static function registerCategories() {
		foreach ( self::groups() as $slug => $group ) {
			wp_register_ability_category(
				$slug,
				[
					'label'       => $group['label'],
					'description' => $group['description'],
				]
			);
		}
	}

	public static function registerAbilities() {
		foreach ( self::groups() as $group ) {
			if ( ! empty( $group['class'] ) && class_exists( $group['class'] ) ) {
				( new $group['class']() )->register();
			}
		}
	}

	/**
	 * Log calls that core rejects before the ability runs (bad input or no permission).
	 * Fires only on WordPress 7.1+, which added the `wp_ability_invoked` action.
	 *
	 * @param string      $name    Ability name.
	 * @param mixed       $input   Raw input.
	 * @param \WP_Ability $ability Ability instance.
	 */
	public static function logRejected( $name, $input, $ability ) {
		if ( ! self::owns( $name ) || ! $ability instanceof \WP_Ability ) {
			return;
		}

		$normalized = $ability->normalize_input( $input );
		if ( is_wp_error( $normalized ) ) {
			AuditLog::add( $name, 'error', $input, $normalized->get_error_code() );
			return;
		}

		$valid = $ability->validate_input( $normalized );
		if ( is_wp_error( $valid ) ) {
			AuditLog::add( $name, 'error', $normalized, $valid->get_error_code() );
			return;
		}

		if ( true !== $ability->check_permissions( $normalized ) ) {
			AuditLog::add( $name, 'denied', $normalized, 'ability_invalid_permissions' );
		}
	}

	/**
	 * Whether an ability name belongs to this module.
	 *
	 * @param string $name Ability name.
	 *
	 * @return bool
	 */
	public static function owns( $name ) {
		foreach ( self::NAMESPACES as $namespace ) {
			if ( 0 === strpos( (string) $name, $namespace ) ) {
				return true;
			}
		}

		return false;
	}
}
