<?php

namespace WPDM\Abilities\Groups;

use WPDM\Abilities\Abilities;
use WPDM\Abilities\AbilityGroup;
use WPDM\Package\PackageService;

/**
 * File abilities: attach files to packages, edit their details and detach them.
 *
 * @since 3.3.73
 */
class Files extends AbilityGroup {

	const CATEGORY = 'wpdm-files';

	const SOURCES = [ 'url', 'media_id', 'content_base64', 'server_path' ];

	public function register() {
		$package_id = [
			'type'        => 'integer',
			'minimum'     => 1,
			'description' => 'Package ID, from wpdm/list-packages.',
		];

		$this->registerAbility(
			'wpdm/attach-file',
			[
				'label'               => __( 'Attach file', 'download-manager' ),
				'description'         => __( 'Adds one file to a package. Give exactly one source: a public URL to fetch, a media library ID, small file contents as base64 (with filename), or, for administrators, a path already on the server inside the download folder. The file must be a type the site allows for downloads.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'package_id', 'source' ],
					'properties'           => [
						'package_id' => $package_id,
						'source'     => [
							'type'                 => 'object',
							'additionalProperties' => false,
							'description'          => 'Exactly one of url, media_id, content_base64 (with filename) or server_path.',
							'properties'           => [
								'url'            => [
									'type'        => 'string',
									'format'      => 'uri',
									'description' => 'Public http(s) URL. Local and private network addresses are refused.',
								],
								'media_id'       => [
									'type'    => 'integer',
									'minimum' => 1,
								],
								'content_base64' => [
									'type'        => 'string',
									'description' => 'File contents, base64-encoded. Size is capped in the AI Abilities settings (10 MB by default).',
								],
								'filename'       => [
									'type'        => 'string',
									'maxLength'   => 200,
									'description' => 'File name to store. Required with content_base64; optional with url.',
								],
								'server_path'    => [
									'type'        => 'string',
									'description' => 'Administrators only. Absolute path inside the download folder.',
								],
							],
						],
						'title'      => [
							'type'        => 'string',
							'maxLength'   => 200,
							'description' => 'File title shown to downloaders. Defaults to the file name.',
						],
						'version'    => [
							'type'      => 'string',
							'maxLength' => 50,
						],
						'password'   => [
							'type'        => 'string',
							'maxLength'   => 100,
							'description' => 'Optional password for this file only. Never returned.',
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'package_id' => [ 'type' => 'integer' ],
						'file'       => [
							'type'       => 'object',
							'properties' => [
								'id'         => [ 'type' => 'string' ],
								'name'       => [ 'type' => 'string' ],
								'title'      => [ 'type' => 'string' ],
								'size_bytes' => [ 'type' => 'integer' ],
								'source'     => [ 'type' => 'string' ],
							],
							'required'   => [ 'id', 'name' ],
						],
					],
					'required'   => [ 'package_id', 'file' ],
				],
				'permission_callback' => function ( $input ) {
					return PackageService::userCanUpload() && PackageService::userCanEdit( isset( $input['package_id'] ) ? (int) $input['package_id'] : 0 );
				},
				'execute_callback'    => [ $this, 'attachFile' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				],
			]
		);

		$this->registerAbility(
			'wpdm/update-file',
			[
				'label'               => __( 'Update file details', 'download-manager' ),
				'description'         => __( 'Changes the title, version or password of one attached file. Only the fields given change; set password to an empty string to remove it. The file itself is not touched: to replace it, detach it and attach the new one. File IDs come from wpdm/get-package.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'package_id', 'file_id' ],
					'properties'           => [
						'package_id' => $package_id,
						'file_id'    => [
							'type'      => 'string',
							'minLength' => 1,
							'maxLength' => 50,
						],
						'title'      => [
							'type'        => 'string',
							'maxLength'   => 200,
							'description' => 'File title shown to downloaders. An empty string resets it to the file name.',
						],
						'version'    => [
							'type'      => 'string',
							'maxLength' => 50,
						],
						'password'   => [
							'type'        => 'string',
							'maxLength'   => 100,
							'description' => 'Password for this file only. Empty string removes it. Never returned.',
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'package_id' => [ 'type' => 'integer' ],
						'file'       => [
							'type'       => 'object',
							'properties' => [
								'id'      => [ 'type' => 'string' ],
								'name'    => [ 'type' => 'string' ],
								'title'   => [ 'type' => 'string' ],
								'version' => [ 'type' => 'string' ],
							],
							'required'   => [ 'id', 'name', 'title', 'version' ],
						],
					],
					'required'   => [ 'package_id', 'file' ],
				],
				'permission_callback' => function ( $input ) {
					return PackageService::userCanEdit( isset( $input['package_id'] ) ? (int) $input['package_id'] : 0 );
				},
				'execute_callback'    => [ $this, 'updateFile' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => true,
				],
			]
		);

		$this->registerAbility(
			'wpdm/detach-file',
			[
				'label'               => __( 'Detach file', 'download-manager' ),
				'description'         => __( 'Removes one file from a package. The file itself stays on the server. File IDs come from wpdm/get-package.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'package_id', 'file_id' ],
					'properties'           => [
						'package_id' => $package_id,
						'file_id'    => [
							'type'      => 'string',
							'minLength' => 1,
							'maxLength' => 50,
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'package_id' => [ 'type' => 'integer' ],
						'file_id'    => [ 'type' => 'string' ],
						'detached'   => [ 'type' => 'boolean' ],
					],
					'required'   => [ 'package_id', 'file_id', 'detached' ],
				],
				'permission_callback' => function ( $input ) {
					return PackageService::userCanEdit( isset( $input['package_id'] ) ? (int) $input['package_id'] : 0 );
				},
				'execute_callback'    => [ $this, 'detachFile' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => true,
				],
			]
		);
	}

	public function attachFile( array $input ) {
		$id = (int) $input['package_id'];
		if ( 'wpdmpro' !== get_post_type( $id ) ) {
			return $this->error( 'wpdm_package_not_found', __( 'No package has that ID.', 'download-manager' ), 404 );
		}

		$source = $input['source'];
		$given  = array_keys(
			array_filter(
				array_intersect_key( $source, array_flip( self::SOURCES ) ),
				function ( $value ) {
					return '' !== $value && null !== $value;
				}
			)
		);
		if ( count( $given ) !== 1 ) {
			return $this->error( 'wpdm_invalid_source', __( 'Give exactly one of url, media_id, content_base64 or server_path.', 'download-manager' ), 400 );
		}

		$filename = isset( $source['filename'] ) ? (string) $source['filename'] : '';
		switch ( $given[0] ) {
			case 'url':
				$fetched = $this->fetchUrl( $source['url'], $filename );
				$mode    = 'move';
				break;
			case 'media_id':
				$fetched = $this->mediaFile( (int) $source['media_id'] );
				$mode    = 'copy';
				break;
			case 'content_base64':
				$fetched = $this->decodeInline( $source['content_base64'], $filename );
				$mode    = 'move';
				break;
			default:
				$fetched = $this->serverFile( $source['server_path'] );
				$mode    = 'reference';
		}
		if ( is_wp_error( $fetched ) ) {
			return $fetched;
		}
		list( $path, $name ) = $fetched;

		$file = PackageService::addFile(
			$id,
			$path,
			$name,
			array_intersect_key( $input, array_flip( [ 'title', 'version', 'password' ] ) ),
			$mode
		);
		if ( 'move' === $mode && file_exists( $path ) ) {
			wp_delete_file( $path );
		}

		return is_wp_error( $file ) ? $file : [
			'package_id' => $id,
			'file'       => $file,
		];
	}

	public function updateFile( array $input ) {
		$id = (int) $input['package_id'];
		if ( 'wpdmpro' !== get_post_type( $id ) ) {
			return $this->error( 'wpdm_package_not_found', __( 'No package has that ID.', 'download-manager' ), 404 );
		}
		$file = PackageService::updateFileInfo( $id, (string) $input['file_id'], array_intersect_key( $input, array_flip( [ 'title', 'version', 'password' ] ) ) );

		return is_wp_error( $file ) ? $file : [
			'package_id' => $id,
			'file'       => $file,
		];
	}

	public function detachFile( array $input ) {
		$id = (int) $input['package_id'];
		if ( 'wpdmpro' !== get_post_type( $id ) ) {
			return $this->error( 'wpdm_package_not_found', __( 'No package has that ID.', 'download-manager' ), 404 );
		}
		$result = PackageService::removeFile( $id, (string) $input['file_id'] );

		return is_wp_error( $result ) ? $result : [
			'package_id' => $id,
			'file_id'    => (string) $input['file_id'],
			'detached'   => true,
		];
	}

	/**
	 * Download a public URL to a temp file, refusing local and private addresses.
	 *
	 * @param string $url      URL.
	 * @param string $filename Name to store; defaults to the URL's file name.
	 *
	 * @return array|\WP_Error [ temp path, file name ]
	 */
	private function fetchUrl( $url, $filename ) {
		$url = esc_url_raw( $url, [ 'http', 'https' ] );
		if ( ! $url || ! wp_http_validate_url( $url ) ) {
			return $this->error( 'wpdm_invalid_url', __( 'Only public http(s) URLs can be fetched.', 'download-manager' ), 400 );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$name = '' !== $filename ? $filename : wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		$max  = Abilities::limit( 'url_download_mb' ) * MB_IN_BYTES;
		$tmp  = wp_tempnam( $name );

		$response = wp_safe_remote_get(
			$url,
			[
				'timeout'             => 300,
				'stream'              => true,
				'filename'            => $tmp,
				'limit_response_size' => $max + 1,
			]
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			wp_delete_file( $tmp );

			return $this->error(
				'wpdm_sideload_failed',
				is_wp_error( $response ) ? $response->get_error_message() : sprintf(
					/* translators: %d: HTTP status code */
					__( 'The URL answered with HTTP %d.', 'download-manager' ),
					(int) wp_remote_retrieve_response_code( $response )
				),
				502
			);
		}

		clearstatcache( true, $tmp );
		if ( filesize( $tmp ) > $max ) {
			wp_delete_file( $tmp );

			return $this->tooLarge( Abilities::limit( 'url_download_mb' ) );
		}

		return [ $tmp, $name ];
	}

	private function mediaFile( $media_id ) {
		if ( 'attachment' !== get_post_type( $media_id ) ) {
			return $this->error( 'wpdm_file_not_found', __( 'No media library item has that ID.', 'download-manager' ), 404 );
		}
		if ( ! current_user_can( 'edit_post', $media_id ) ) {
			return $this->error( 'wpdm_media_forbidden', __( 'You can only attach media library items you can edit.', 'download-manager' ), 403 );
		}
		$path = get_attached_file( $media_id );
		if ( ! $path || ! is_file( $path ) ) {
			return $this->error( 'wpdm_file_not_found', __( 'That media item has no file on the server.', 'download-manager' ), 404 );
		}

		return [ $path, wp_basename( $path ) ];
	}

	private function decodeInline( $base64, $filename ) {
		if ( '' === $filename ) {
			return $this->error( 'wpdm_invalid_source', __( 'content_base64 needs a filename.', 'download-manager' ), 400 );
		}
		$max = Abilities::limit( 'inline_upload_mb' ) * MB_IN_BYTES;
		if ( strlen( $base64 ) > ceil( $max / 3 ) * 4 + 4 ) {
			return $this->tooLarge( Abilities::limit( 'inline_upload_mb' ) );
		}
		$bytes = base64_decode( $base64, true );
		if ( false === $bytes ) {
			return $this->error( 'wpdm_invalid_source', __( 'content_base64 is not valid base64.', 'download-manager' ), 400 );
		}
		if ( strlen( $bytes ) > $max ) {
			return $this->tooLarge( Abilities::limit( 'inline_upload_mb' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$tmp = wp_tempnam( $filename );
		file_put_contents( $tmp, $bytes );

		return [ $tmp, $filename ];
	}

	private function serverFile( $server_path ) {
		if ( ! current_user_can( WPDM_ADMIN_CAP ) ) {
			return $this->error( 'wpdm_server_path_forbidden', __( 'Only administrators can attach files by server path.', 'download-manager' ), 403 );
		}
		$path = realpath( $server_path );
		if ( ! $path || ! is_file( $path ) || ! WPDM()->fileSystem->allowedPath( $path ) ) {
			return $this->error( 'wpdm_file_not_found', __( 'No file at that path inside the download folder.', 'download-manager' ), 404 );
		}

		return [ $path, wp_basename( $path ) ];
	}

	private function tooLarge( $mb ) {
		/* translators: %d: size limit in MB */
		return $this->error( 'wpdm_file_too_large', sprintf( __( 'The file is larger than this site allows (%d MB).', 'download-manager' ), $mb ), 413 );
	}
}
