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
		// Calls that core rejects before the ability runs. These filters exist on WordPress 7.1+.
		add_filter( 'wp_ability_normalize_input', [ __CLASS__, 'logNormalizeError' ], 10, 2 );
		add_filter( 'wp_ability_validate_input', [ __CLASS__, 'logInvalidInput' ], 10, 3 );
		add_filter( 'wp_ability_permission_result', [ __CLASS__, 'logDenied' ], 10, 3 );
		McpServer::boot();

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
				'description' => __( 'Find, read, create, edit, copy, trash and restore packages, and list tags.', 'download-manager' ),
				'class'       => Groups\Packages::class,
			],
			'wpdm-files'      => [
				'label'       => __( 'Files', 'download-manager' ),
				'description' => __( 'Attach files to packages, edit their details and detach them. Detached files stay on the server.', 'download-manager' ),
				'class'       => Groups\Files::class,
			],
			'wpdm-links'      => [
				'label'       => __( 'Download links', 'download-manager' ),
				'description' => __( 'Create expiring download links, email them and regenerate master keys. Links bypass a package\'s locks and access roles.', 'download-manager' ),
				'class'       => Groups\Links::class,
			],
			'wpdm-categories' => [
				'label'       => __( 'Categories', 'download-manager' ),
				'description' => __( 'Read the package category tree; create, edit and delete categories.', 'download-manager' ),
				'class'       => Groups\Categories::class,
			],
			'wpdm-stats'      => [
				'label'       => __( 'Stats', 'download-manager' ),
				'description' => __( 'Read download counts, download history and weekly or monthly activity reports.', 'download-manager' ),
				'class'       => Groups\Stats::class,
			],
			'wpdm-site'       => [
				'label'       => __( 'Site settings', 'download-manager' ),
				'description' => __( 'Read the download settings an agent needs to get packages right: allowed file types, size limits, roles and statuses.', 'download-manager' ),
				'class'       => Groups\Site::class,
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
	 * Log input that failed normalization. Filter callback; returns the input unchanged.
	 *
	 * @param mixed  $input Normalized input, or a WP_Error.
	 * @param string $name  Ability name.
	 *
	 * @return mixed
	 */
	public static function logNormalizeError( $input, $name ) {
		if ( is_wp_error( $input ) && self::owns( $name ) ) {
			AuditLog::add( $name, 'error', null, $input->get_error_code() );
		}

		return $input;
	}

	/**
	 * Log input that failed validation. Filter callback; returns the result unchanged.
	 *
	 * @param true|\WP_Error $valid Validation result.
	 * @param mixed          $input The input.
	 * @param string         $name  Ability name.
	 *
	 * @return true|\WP_Error
	 */
	public static function logInvalidInput( $valid, $input, $name ) {
		if ( true !== $valid && self::owns( $name ) ) {
			AuditLog::add( $name, 'error', $input, is_wp_error( $valid ) ? $valid->get_error_code() : 'ability_invalid_input' );
		}

		return $valid;
	}

	/**
	 * Log a refused permission check. Filter callback; returns the result unchanged.
	 *
	 * @param bool|\WP_Error $permission Permission result.
	 * @param string         $name       Ability name.
	 * @param mixed          $input      The input.
	 *
	 * @return bool|\WP_Error
	 */
	public static function logDenied( $permission, $name, $input ) {
		if ( true !== $permission && self::owns( $name ) ) {
			AuditLog::add( $name, 'denied', $input, 'ability_invalid_permissions' );
		}

		return $permission;
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
