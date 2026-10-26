<?php

namespace WPDM\Package;

use WPDM\__\__;
use WPDM\__\Crypt;
use WPDM\__\FileSystem;

/**
 * Package write and permission logic shared by the wp-admin package form and the abilities.
 *
 * @since 3.3.73
 */
class PackageService {

	/**
	 * Meta keys a full-form save never deletes.
	 */
	const PROTECTED_META = [ '__wpdm_favs', '__wpdm_masterkey' ];

	/**
	 * Sanitize and store package meta.
	 *
	 * @param int   $id      Package ID.
	 * @param array $fields  Field => value, keyed without the `__wpdm_` prefix, as the package form submits them.
	 * @param bool  $replace True deletes every `__wpdm_` meta key missing from $fields (the wp-admin form sends them all).
	 */
	public static function saveMeta( $id, array $fields, $replace = false ) {
		if ( $replace ) {
			foreach ( get_post_custom( $id ) as $key => $value ) {
				$field = str_replace( '__wpdm_', '', $key );
				if ( ! isset( $fields[ $field ] ) && $field !== $key && ! in_array( $key, self::PROTECTED_META ) ) {
					delete_post_meta( $id, $key );
				}
			}
		}

		unset( $fields['zipped_file'], $fields['masterkey'] );

		foreach ( $fields as $meta_key => $meta_value ) {
			$key_name = '__wpdm_' . $meta_key;
			if ( $meta_key == 'package_size' && (double) $meta_value == 0 ) {
				$meta_value = '';
			}
			if ( $meta_key == 'password' ) {
				// Passwords are stored as entered.
			} else if ( $meta_key == 'files' ) {
				foreach ( $meta_value as &$value ) {
					$value = wpdm_escs( $value );
					if ( ! __::is_url( $value ) ) {
						if ( WPDM()->fileSystem->isBlocked( $value ) ) {
							$value = '';
						}
						$abspath = WPDM()->fileSystem->locateFile( $value );
						if ( ! WPDM()->fileSystem->allowedPath( $abspath ) ) {
							$value = '';
						}
					}
				}
				unset( $value );
				$meta_value = array_unique( $meta_value );
			} else if ( $meta_key == 'terms_conditions' ) {
				$meta_value = __::sanitize_var( $meta_value, 'kses' );
			} else if ( $meta_key == 'changelog' ) {
				if ( is_array( $meta_value ) ) {
					foreach ( $meta_value as &$entry ) {
						if ( is_array( $entry ) ) {
							$entry['id']        = sanitize_text_field( $entry['id'] );
							$entry['version']   = sanitize_text_field( $entry['version'] );
							$entry['date']      = sanitize_text_field( $entry['date'] );
							$entry['changes']   = wp_kses_post( $entry['changes'] );
							$entry['timestamp'] = absint( $entry['timestamp'] );
						}
					}
					unset( $entry );
				}
			} else {
				$meta_value = is_array( $meta_value ) ? wpdm_sanitize_array( $meta_value, 'txt' ) : esc_html( $meta_value );
			}
			update_post_meta( $id, $key_name, $meta_value );
		}
	}

	/**
	 * Give the package a master download key if it has none, or a new one when $reset is true.
	 *
	 * @param int  $id    Package ID.
	 * @param bool $reset Replace an existing key.
	 */
	public static function ensureMasterKey( $id, $reset = false ) {
		if ( $reset || get_post_meta( $id, '__wpdm_masterkey', true ) == '' ) {
			update_post_meta( $id, '__wpdm_masterkey', Crypt::encrypt( [ 'id' => $id, 'time' => time() ] ) );
		}
	}

	/**
	 * Create a package owned by the current user.
	 *
	 * @param array $data title (required), description, excerpt, status, categories, tags,
	 *                    version, link_label, access, password, expire_date, quota.
	 *
	 * @return int|\WP_Error New package ID.
	 */
	public static function create( array $data ) {
		if ( ! isset( $data['title'] ) || '' === trim( (string) $data['title'] ) ) {
			return new \WP_Error( 'wpdm_missing_title', __( 'A package needs a title.', 'download-manager' ), [ 'status' => 400 ] );
		}
		$data += [
			'status' => 'draft',
			'access' => [ 'guest' ],
		];

		$prepared = self::prepare( $data );
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}
		list( $post, $meta, $terms ) = $prepared;

		$post['post_type']   = 'wpdmpro';
		$post['post_author'] = get_current_user_id();
		$id                  = wp_insert_post( wp_slash( $post ), true );
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		self::writeTerms( $id, $terms );
		self::saveMeta( $id, wp_slash( $meta ) );
		self::ensureMasterKey( $id );
		// Start the counters at zero so sorting and filtering by them sees the new package.
		add_post_meta( $id, '__wpdm_download_count', 0, true );
		add_post_meta( $id, '__wpdm_view_count', 0, true );
		do_action( 'wpdm_admin_update_package', $id, $meta );

		return $id;
	}

	/**
	 * Change only the given fields of a package.
	 *
	 * @param int   $id   Package ID.
	 * @param array $data Same keys as create(), all optional.
	 *
	 * @return int|\WP_Error Package ID.
	 */
	public static function update( $id, array $data ) {
		$prepared = self::prepare( $data );
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}
		list( $post, $meta, $terms ) = $prepared;

		if ( $post ) {
			$post['ID'] = $id;
			$result     = wp_update_post( wp_slash( $post ), true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
		self::writeTerms( $id, $terms );
		if ( $meta ) {
			self::saveMeta( $id, wp_slash( $meta ) );
		}
		do_action( 'wpdm_admin_update_package', $id, $meta );

		return $id;
	}

	/**
	 * Copy a package as a draft owned by the current user, with its own master key and zeroed counts.
	 *
	 * @param int    $id    Package to copy.
	 * @param string $title Title for the copy; defaults to the original's.
	 *
	 * @return int|\WP_Error New package ID.
	 */
	public static function duplicate( $id, $title = '' ) {
		$copy = Package::copy( $id, get_current_user_id(), [ '__wpdm_download_count' => 0, '__wpdm_view_count' => 0 ] );
		if ( ! $copy || is_wp_error( $copy ) ) {
			return is_wp_error( $copy ) ? $copy : new \WP_Error( 'wpdm_copy_failed', __( 'The package could not be copied.', 'download-manager' ), [ 'status' => 500 ] );
		}

		wp_update_post(
			wp_slash(
				[
					'ID'          => $copy,
					'post_status' => 'draft',
					'post_title'  => '' !== $title ? sanitize_text_field( $title ) : get_post_field( 'post_title', $id, 'raw' ),
				]
			)
		);
		delete_post_meta( $copy, '__wpdm_zipped_file' );
		self::ensureMasterKey( $copy, true );

		return $copy;
	}

	/**
	 * Move a package to the trash. Files stay on disk.
	 *
	 * @param int $id Package ID.
	 *
	 * @return true|\WP_Error
	 */
	public static function trash( $id ) {
		if ( 'trash' === get_post_status( $id ) ) {
			return true;
		}

		return wp_trash_post( $id ) ? true : new \WP_Error( 'wpdm_trash_failed', __( 'The package could not be moved to the trash.', 'download-manager' ), [ 'status' => 500 ] );
	}

	/**
	 * Store a local file in the upload dir and attach it to a package.
	 *
	 * @param int    $id   Package ID.
	 * @param string $path Absolute path of the file.
	 * @param string $name File name to store it under.
	 * @param array  $info title, version, password.
	 * @param string $mode `move` (temp files), `copy` (leave the source in place) or
	 *                     `reference` (attach $path where it is; must already be in an allowed dir).
	 *
	 * @return array|\WP_Error The attached file: id, name, title, size_bytes, source.
	 */
	public static function addFile( $id, $path, $name, array $info = [], $mode = 'move' ) {
		$name = sanitize_file_name( wp_basename( $name ) );
		if ( '' === $name || WPDM()->fileSystem->isBlocked( $name, $path ) ) {
			return new \WP_Error( 'wpdm_file_type_not_allowed', __( 'That file type is not allowed for downloads on this site.', 'download-manager' ), [ 'status' => 415 ] );
		}
		if ( 'reference' !== $mode && ! FileSystem::validateUploadMimeType( $path, $name ) ) {
			return new \WP_Error( 'wpdm_file_type_not_allowed', __( 'The file extension does not match its contents.', 'download-manager' ), [ 'status' => 415 ] );
		}

		if ( 'reference' === $mode ) {
			$dest = $path;
		} else {
			$dest = self::destination( $name );
			if ( 'move' === $mode ) {
				$done = @rename( $path, $dest );
				if ( ! $done && @copy( $path, $dest ) ) {
					wp_delete_file( $path );
					$done = true;
				}
			} else {
				$done = @copy( $path, $dest );
			}
			if ( ! $done || ! file_exists( $dest ) ) {
				return new \WP_Error( 'wpdm_file_write_failed', __( 'The file could not be saved in the upload directory.', 'download-manager' ), [ 'status' => 500 ] );
			}
			@chmod( $dest, 0644 );
			do_action( 'wpdm_after_upload_file', $dest );
		}

		$stored   = 0 === strpos( $dest, UPLOAD_DIR ) ? substr( $dest, strlen( UPLOAD_DIR ) ) : $dest;
		$files    = self::metaArray( $id, '__wpdm_files' );
		$fileinfo = self::metaArray( $id, '__wpdm_fileinfo' );
		if ( in_array( $stored, $files, true ) ) {
			return new \WP_Error( 'wpdm_file_already_attached', __( 'That file is already attached to the package.', 'download-manager' ), [ 'status' => 409 ] );
		}

		$file_id = (string) round( microtime( true ) * 1000 );
		while ( isset( $files[ $file_id ] ) ) {
			$file_id = (string) ( (int) $file_id + 1 );
		}
		$files[ $file_id ]    = $stored;
		$fileinfo[ $file_id ] = [
			'title'    => isset( $info['title'] ) && '' !== $info['title'] ? sanitize_text_field( $info['title'] ) : $name,
			'version'  => isset( $info['version'] ) ? sanitize_text_field( $info['version'] ) : '',
			'password' => isset( $info['password'] ) ? sanitize_text_field( $info['password'] ) : '',
		];

		self::saveMeta( $id, wp_slash( [ 'files' => $files, 'fileinfo' => $fileinfo ] ) );
		$saved = self::metaArray( $id, '__wpdm_files' );
		if ( empty( $saved[ $file_id ] ) ) {
			return new \WP_Error( 'wpdm_file_rejected', __( 'The file failed the download location check.', 'download-manager' ), [ 'status' => 400 ] );
		}
		do_action( 'wpdm_admin_update_package', $id, [ 'files' => $saved ] );

		return [
			'id'         => $file_id,
			'name'       => wp_basename( $stored ),
			'title'      => $fileinfo[ $file_id ]['title'],
			'size_bytes' => (int) @filesize( $dest ),
			'source'     => 'local',
		];
	}

	/**
	 * Remove a file from a package. The file stays on disk.
	 *
	 * @param int    $id      Package ID.
	 * @param string $file_id File ID from the package's file list.
	 *
	 * @return true|\WP_Error
	 */
	public static function removeFile( $id, $file_id ) {
		$files = self::metaArray( $id, '__wpdm_files' );
		if ( ! isset( $files[ $file_id ] ) ) {
			return new \WP_Error( 'wpdm_file_not_found', __( 'No file with that ID is attached to the package.', 'download-manager' ), [ 'status' => 404 ] );
		}
		$fileinfo = self::metaArray( $id, '__wpdm_fileinfo' );
		unset( $files[ $file_id ], $fileinfo[ $file_id ] );

		self::saveMeta( $id, wp_slash( [ 'files' => $files, 'fileinfo' => $fileinfo ] ) );
		do_action( 'wpdm_admin_update_package', $id, [ 'files' => self::metaArray( $id, '__wpdm_files' ) ] );

		return true;
	}

	/**
	 * Bring a package back from the trash with the status it had before.
	 *
	 * @since 3.3.74
	 *
	 * @param int $id Package ID.
	 *
	 * @return true|\WP_Error
	 */
	public static function restore( $id ) {
		if ( 'trash' !== get_post_status( $id ) ) {
			return true;
		}
		add_filter( 'wp_untrash_post_status', 'wp_untrash_post_set_previous_status', 10, 3 );
		$restored = wp_untrash_post( $id );
		remove_filter( 'wp_untrash_post_status', 'wp_untrash_post_set_previous_status', 10 );

		return $restored ? true : new \WP_Error( 'wpdm_restore_failed', __( 'The package could not be restored from the trash.', 'download-manager' ), [ 'status' => 500 ] );
	}

	/**
	 * Change the title, version or password of one attached file. The file itself is untouched.
	 *
	 * @since 3.3.74
	 *
	 * @param int    $id      Package ID.
	 * @param string $file_id File ID from the package's file list.
	 * @param array  $info    title, version, password; only the keys given change.
	 *
	 * @return array|\WP_Error The file: id, name, title, version.
	 */
	public static function updateFileInfo( $id, $file_id, array $info ) {
		$files = self::metaArray( $id, '__wpdm_files' );
		if ( ! isset( $files[ $file_id ] ) ) {
			return new \WP_Error( 'wpdm_file_not_found', __( 'No file with that ID is attached to the package.', 'download-manager' ), [ 'status' => 404 ] );
		}
		$path     = (string) $files[ $file_id ];
		$name     = wp_basename( __::is_url( $path ) ? (string) wp_parse_url( $path, PHP_URL_PATH ) : $path );
		$fileinfo = self::metaArray( $id, '__wpdm_fileinfo' );
		$current  = isset( $fileinfo[ $file_id ] ) && is_array( $fileinfo[ $file_id ] ) ? $fileinfo[ $file_id ] : [];
		foreach ( [ 'title', 'version', 'password' ] as $key ) {
			if ( isset( $info[ $key ] ) ) {
				$current[ $key ] = sanitize_text_field( $info[ $key ] );
			}
		}
		$current += [
			'version'  => '',
			'password' => '',
		];
		if ( ! isset( $current['title'] ) || '' === $current['title'] ) {
			$current['title'] = $name;
		}
		$fileinfo[ $file_id ] = $current;

		self::saveMeta( $id, wp_slash( [ 'fileinfo' => $fileinfo ] ) );
		do_action( 'wpdm_admin_update_package', $id, [ 'fileinfo' => $fileinfo ] );

		return [
			'id'      => (string) $file_id,
			'name'    => $name,
			'title'   => $current['title'],
			'version' => $current['version'],
		];
	}

	/**
	 * Whether the current user may create packages.
	 *
	 * @return bool
	 */
	public static function userCanAuthor() {
		$post_type = get_post_type_object( 'wpdmpro' );

		return $post_type && current_user_can( $post_type->cap->edit_posts );
	}

	/**
	 * Whether the current user may change one package.
	 *
	 * An ID that isn't a package falls back to userCanAuthor(), so callers can report it as not found.
	 *
	 * @param int $id Package ID.
	 *
	 * @return bool
	 */
	public static function userCanEdit( $id ) {
		$id = (int) $id;
		if ( $id <= 0 || get_post_type( $id ) !== 'wpdmpro' ) {
			return self::userCanAuthor();
		}

		return current_user_can( 'edit_post', $id );
	}

	/**
	 * Whether the current user may trash one package.
	 *
	 * @param int $id Package ID.
	 *
	 * @return bool
	 */
	public static function userCanDelete( $id ) {
		$id = (int) $id;
		if ( $id <= 0 || get_post_type( $id ) !== 'wpdmpro' ) {
			return self::userCanAuthor();
		}

		return current_user_can( 'delete_post', $id );
	}

	/**
	 * Whether the current user may upload files.
	 *
	 * @return bool
	 */
	public static function userCanUpload() {
		return current_user_can( 'upload_files' );
	}

	/**
	 * Split create/update input into post fields, meta fields and terms, validating as it goes.
	 *
	 * @param array $data Input.
	 *
	 * @return array|\WP_Error [ post fields, meta fields, terms by taxonomy ]
	 */
	private static function prepare( array $data ) {
		$post  = [];
		$meta  = [];
		$terms = [];

		if ( isset( $data['title'] ) ) {
			$post['post_title'] = sanitize_text_field( $data['title'] );
		}
		if ( isset( $data['description'] ) ) {
			$post['post_content'] = wp_kses_post( $data['description'] );
		}
		if ( isset( $data['excerpt'] ) ) {
			$post['post_excerpt'] = sanitize_textarea_field( $data['excerpt'] );
		}
		if ( isset( $data['status'] ) ) {
			$post['post_status'] = self::allowedStatus( $data['status'] );
		}
		if ( isset( $data['categories'] ) ) {
			$ids = self::categoryIds( (array) $data['categories'] );
			if ( is_wp_error( $ids ) ) {
				return $ids;
			}
			$terms['wpdmcategory'] = $ids;
		}
		if ( isset( $data['tags'] ) ) {
			$terms[ WPDM_TAG ] = array_values( array_filter( array_map( 'sanitize_text_field', (array) $data['tags'] ) ) );
		}
		foreach ( [ 'version', 'link_label' ] as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$meta[ $key ] = sanitize_text_field( $data[ $key ] );
			}
		}
		if ( isset( $data['access'] ) ) {
			$roles = self::validRoles( (array) $data['access'] );
			if ( is_wp_error( $roles ) ) {
				return $roles;
			}
			$meta['access'] = $roles;
		}
		if ( isset( $data['password'] ) ) {
			$meta['password']      = sanitize_text_field( $data['password'] );
			$meta['password_lock'] = '' === $meta['password'] ? 0 : 1;
		}
		if ( isset( $data['expire_date'] ) ) {
			if ( '' === $data['expire_date'] ) {
				$meta['expire_date'] = '';
			} else {
				$time = strtotime( $data['expire_date'] );
				if ( ! $time ) {
					return new \WP_Error( 'wpdm_invalid_date', __( 'expire_date must look like 2026-12-31 or 2026-12-31 23:59.', 'download-manager' ), [ 'status' => 400 ] );
				}
				$meta['expire_date'] = gmdate( 'Y-m-d H:i', $time );
			}
		}
		if ( isset( $data['quota'] ) ) {
			$meta['quota'] = max( 0, (int) $data['quota'] );
		}

		return [ $post, $meta, $terms ];
	}

	/**
	 * The status the current user may set. Publishing needs `publish_posts`; others go to review.
	 *
	 * @param string $requested Requested status.
	 *
	 * @return string
	 */
	private static function allowedStatus( $requested ) {
		if ( in_array( $requested, [ 'draft', 'pending' ], true ) ) {
			return $requested;
		}

		$post_type = get_post_type_object( 'wpdmpro' );

		return current_user_can( $post_type->cap->publish_posts ) ? $requested : 'pending';
	}

	/**
	 * Category term IDs from IDs or slugs.
	 *
	 * @param array $categories IDs or slugs.
	 *
	 * @return int[]|\WP_Error
	 */
	private static function categoryIds( array $categories ) {
		$ids = [];
		foreach ( $categories as $category ) {
			$term = is_numeric( $category ) ? get_term( (int) $category, 'wpdmcategory' ) : get_term_by( 'slug', (string) $category, 'wpdmcategory' );
			if ( ! $term || is_wp_error( $term ) ) {
				/* translators: %s: category ID or slug */
				return new \WP_Error( 'wpdm_category_not_found', sprintf( __( 'No category matches "%s".', 'download-manager' ), $category ), [ 'status' => 404 ] );
			}
			$ids[] = (int) $term->term_id;
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Role slugs, checked against the site's roles plus `guest` (all visitors).
	 *
	 * @param array $roles Role slugs.
	 *
	 * @return string[]|\WP_Error
	 */
	public static function validRoles( array $roles ) {
		$known   = array_merge( [ 'guest' ], array_keys( wp_roles()->roles ) );
		$roles   = array_values( array_unique( array_map( 'sanitize_key', $roles ) ) );
		$unknown = array_diff( $roles, $known );
		if ( $unknown ) {
			/* translators: %s: role slugs */
			return new \WP_Error( 'wpdm_invalid_role', sprintf( __( 'Unknown role: %s. Use "guest" for all visitors.', 'download-manager' ), implode( ', ', $unknown ) ), [ 'status' => 400 ] );
		}

		return $roles;
	}

	private static function writeTerms( $id, array $terms ) {
		foreach ( $terms as $taxonomy => $values ) {
			wp_set_post_terms( $id, $values, $taxonomy );
		}
	}

	/**
	 * Where a new upload goes. A taken name gets a -1, -2 … suffix, so two uploads in the
	 * same second can't replace each other.
	 *
	 * @param string $name Sanitized file name.
	 *
	 * @return string Absolute path.
	 */
	private static function destination( $name ) {
		if ( ! file_exists( UPLOAD_DIR ) ) {
			\WPDM\WordPressDownloadManager::createDir();
		}
		wp_mkdir_p( UPLOAD_DIR );

		return UPLOAD_DIR . wp_unique_filename( UPLOAD_DIR, $name );
	}

	private static function metaArray( $id, $key ) {
		$value = maybe_unserialize( get_post_meta( $id, $key, true ) );

		return is_array( $value ) ? $value : [];
	}
}
