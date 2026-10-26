<?php

namespace WPDM\Abilities\Groups;

use WPDM\Abilities\Abilities;
use WPDM\Abilities\AbilityGroup;
use WPDM\Package\PackageService;

/**
 * Site abilities: the download settings an agent needs before it creates packages,
 * offered as one read-only MCP resource.
 *
 * @since 3.3.74
 */
class Site extends AbilityGroup {

	const CATEGORY = 'wpdm-site';

	const SETTINGS_URI = 'wpdm://settings';

	public function register() {
		$this->registerResource(
			'wpdm/site-settings',
			[
				'label'               => __( 'Site settings', 'download-manager' ),
				'description'         => __( 'Download settings as JSON: file types allowed for downloads, upload and link limits, the roles that can be given access, package statuses, the site timezone and which ability groups are enabled. Read it before attaching files or setting access.', 'download-manager' ),
				'uri'                 => self::SETTINGS_URI,
				'permission_callback' => [ PackageService::class, 'userCanAuthor' ],
				'execute_callback'    => [ $this, 'siteSettings' ],
			]
		);
	}

	public function siteSettings() {
		$roles = [
			[
				'slug' => 'guest',
				'name' => __( 'All visitors', 'download-manager' ),
			],
		];
		foreach ( wp_roles()->get_names() as $slug => $name ) {
			$roles[] = [
				'slug' => $slug,
				'name' => translate_user_role( $name ),
			];
		}

		return $this->jsonContents(
			self::SETTINGS_URI,
			[
				'plugin_version'     => WPDM_VERSION,
				'site_url'           => home_url( '/' ),
				'timezone'           => wp_timezone_string(),
				'allowed_file_types' => array_values( array_filter( array_map( 'strtolower', (array) WPDM()->fileSystem->getAllowedFileTypes( true ) ) ) ),
				'limits'             => [
					'inline_upload_mb'     => Abilities::limit( 'inline_upload_mb' ),
					'url_download_mb'      => Abilities::limit( 'url_download_mb' ),
					'server_upload_max_mb' => (int) floor( wp_max_upload_size() / MB_IN_BYTES ),
					'max_link_hours'       => Abilities::limit( 'max_link_hours' ),
					'writes_per_minute'    => Abilities::limit( 'writes_per_minute' ),
					'emails_per_hour'      => Abilities::limit( 'emails_per_hour' ),
				],
				'roles'              => $roles,
				'statuses'           => [ 'draft', 'pending', 'publish', 'private' ],
				'link_types'         => [ 'direct', 'page' ],
				'enabled_groups'     => Abilities::enabledGroups(),
			]
		);
	}
}
