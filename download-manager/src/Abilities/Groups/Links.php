<?php

namespace WPDM\Abilities\Groups;

use WPDM\Abilities\Abilities;
use WPDM\Abilities\AbilityGroup;
use WPDM\Abilities\RateLimiter;
use WPDM\Package\PackageController;
use WPDM\Package\PackageService;

/**
 * Link abilities: expiring download links, shared directly or by email.
 *
 * @since 3.3.73
 */
class Links extends AbilityGroup {

	const CATEGORY = 'wpdm-links';

	const DEFAULT_HOURS = 24;

	const MAX_RECIPIENTS = 10;

	public function register() {
		$common = [
			'package_id'       => [
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Package ID, from wpdm/list-packages.',
			],
			'expires_in_hours' => [
				'type'        => 'integer',
				'minimum'     => 1,
				'maximum'     => 8760,
				'default'     => self::DEFAULT_HOURS,
				'description' => 'Hours until the link stops working. The site sets a maximum (30 days by default).',
			],
		];

		$this->registerAbility(
			'wpdm/create-download-link',
			[
				'label'               => __( 'Create expiring download link', 'download-manager' ),
				'description'         => __( 'Creates a link to one package that stops working after a set number of downloads or hours. The link skips the package\'s locks and access roles, so share it only with the intended person. Use type "page" for a download page, or "direct" to start the download at once.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'package_id' ],
					'properties'           => $common + [
						'type'        => [
							'type'    => 'string',
							'enum'    => [ 'direct', 'page' ],
							'default' => 'direct',
						],
						'usage_limit' => [
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 1000,
							'default'     => 10,
							'description' => 'How many downloads the link allows.',
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'url'         => [ 'type' => 'string' ],
						'type'        => [ 'type' => 'string' ],
						'package_id'  => [ 'type' => 'integer' ],
						'usage_limit' => [ 'type' => 'integer' ],
						'expires_at'  => [ 'type' => 'string' ],
					],
					'required'   => [ 'url', 'type', 'package_id', 'usage_limit', 'expires_at' ],
				],
				'permission_callback' => [ $this, 'canEdit' ],
				'execute_callback'    => [ $this, 'createDownloadLink' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				],
			]
		);

		$this->registerAbility(
			'wpdm/email-download-link',
			[
				'label'               => __( 'Email download link', 'download-manager' ),
				'description'         => __( 'Emails each recipient their own expiring link to a package, using the site\'s email template. Up to 10 recipients per call. The links skip the package\'s locks and access roles. Confirm the recipients with the user before calling this.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'package_id', 'recipients' ],
					'properties'           => $common + [
						'recipients'  => [
							'type'     => 'array',
							'minItems' => 1,
							'maxItems' => self::MAX_RECIPIENTS,
							'items'    => [
								'type'                 => 'object',
								'additionalProperties' => false,
								'required'             => [ 'email' ],
								'properties'           => [
									'email' => [
										'type'   => 'string',
										'format' => 'email',
									],
									'name'  => [
										'type'      => 'string',
										'maxLength' => 100,
									],
								],
							],
						],
						'usage_limit' => [
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 1000,
							'default'     => 3,
							'description' => 'Downloads each recipient\'s link allows.',
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'package_id' => [ 'type' => 'integer' ],
						'sent'       => [ 'type' => 'integer' ],
						'recipients' => [
							'type'  => 'array',
							'items' => [ 'type' => 'string' ],
						],
						'expires_at' => [ 'type' => 'string' ],
					],
					'required'   => [ 'package_id', 'sent', 'recipients', 'expires_at' ],
				],
				'permission_callback' => [ $this, 'canEdit' ],
				'execute_callback'    => [ $this, 'emailDownloadLink' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				],
			]
		);
	}

	public function canEdit( $input ) {
		return PackageService::userCanEdit( isset( $input['package_id'] ) ? (int) $input['package_id'] : 0 );
	}

	public function createDownloadLink( array $input ) {
		$id      = (int) $input['package_id'];
		$seconds = $this->lifetime( $input );
		if ( is_wp_error( $seconds ) ) {
			return $seconds;
		}
		if ( 'wpdmpro' !== get_post_type( $id ) ) {
			return $this->notFound();
		}

		$type  = isset( $input['type'] ) ? $input['type'] : 'direct';
		$limit = isset( $input['usage_limit'] ) ? (int) $input['usage_limit'] : 10;
		$url   = 'page' === $type
			? PackageController::expirableDownloadPage( $id, $limit, $seconds, false )
			: WPDM()->package->expirableDownloadLink( $id, $limit, $seconds, false );
		if ( ! $url ) {
			return $this->notFound();
		}

		return [
			'url'         => $url,
			'type'        => $type,
			'package_id'  => $id,
			'usage_limit' => $limit,
			'expires_at'  => $this->isoDate( time() + $seconds ),
		];
	}

	public function emailDownloadLink( array $input ) {
		$id      = (int) $input['package_id'];
		$seconds = $this->lifetime( $input );
		if ( is_wp_error( $seconds ) ) {
			return $seconds;
		}
		if ( 'wpdmpro' !== get_post_type( $id ) ) {
			return $this->notFound();
		}

		$emails = [];
		$names  = [];
		foreach ( $input['recipients'] as $recipient ) {
			$email = sanitize_email( $recipient['email'] );
			if ( ! is_email( $email ) ) {
				/* translators: %s: email address */
				return $this->error( 'wpdm_invalid_email', sprintf( __( '"%s" is not a valid email address.', 'download-manager' ), $recipient['email'] ), 400 );
			}
			$emails[] = $email;
			$names[]  = isset( $recipient['name'] ) ? sanitize_text_field( str_replace( ',', ' ', $recipient['name'] ) ) : '';
		}

		$allowed = RateLimiter::hit( 'emails', Abilities::limit( 'emails_per_hour' ), HOUR_IN_SECONDS, count( $emails ) );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		PackageController::emailDownloadLink( $id, $emails, $names, isset( $input['usage_limit'] ) ? (int) $input['usage_limit'] : 3, $seconds );

		return [
			'package_id' => $id,
			'sent'       => count( $emails ),
			'recipients' => $emails,
			'expires_at' => $this->isoDate( time() + $seconds ),
		];
	}

	/**
	 * Link lifetime in seconds, capped by the site's maximum.
	 *
	 * @param array $input Ability input.
	 *
	 * @return int|\WP_Error
	 */
	private function lifetime( array $input ) {
		$hours = isset( $input['expires_in_hours'] ) ? (int) $input['expires_in_hours'] : self::DEFAULT_HOURS;
		$max   = Abilities::limit( 'max_link_hours' );
		if ( $hours > $max ) {
			/* translators: %d: hours */
			return $this->error( 'wpdm_link_too_long', sprintf( __( 'Links on this site can last at most %d hours.', 'download-manager' ), $max ), 400 );
		}

		return $hours * HOUR_IN_SECONDS;
	}

	private function notFound() {
		return $this->error( 'wpdm_package_not_found', __( 'No package has that ID.', 'download-manager' ), 404 );
	}
}
