<?php

namespace WPDM\Abilities\Groups;

use WPDM\__\__;
use WPDM\Abilities\AbilityGroup;
use WPDM\Package\PackageService;

/**
 * Package abilities: find, read, write, trash and restore packages, and list their tags.
 *
 * @since 3.3.73
 */
class Packages extends AbilityGroup {

	const CATEGORY = 'wpdm-packages';

	/**
	 * Lock meta keys (without `__wpdm_` and `_lock`) reported by get-package.
	 */
	const LOCKS = [ 'password', 'email', 'captcha', 'terms', 'linkedin', 'tweet', 'twitterfollow', 'facebooklike', 'gplusone' ];

	/**
	 * Statuses an agent can list. `any` means every status except trash.
	 */
	const STATUSES = [ 'publish', 'draft', 'pending', 'private', 'future' ];

	public function register() {
		$this->registerAbility(
			'wpdm/list-packages',
			[
				'label'               => __( 'List packages', 'download-manager' ),
				'description'         => __( 'Searches and filters Download Manager packages, newest first, one page at a time. Use it to find a package ID before reading or changing a package. Authors only see their own packages.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'default'              => [],
					'additionalProperties' => false,
					'properties'           => [
						'search'   => [
							'type'        => 'string',
							'maxLength'   => 200,
							'description' => 'Words to match in the title or description.',
						],
						'category' => [
							'type'        => 'string',
							'maxLength'   => 200,
							'description' => 'Category ID or slug, from wpdm/list-categories.',
						],
						'status'   => [
							'type'        => 'string',
							'enum'        => array_merge( [ 'any' ], self::STATUSES, [ 'trash' ] ),
							'default'     => 'any',
							'description' => 'Package status. "any" means every status except trash.',
						],
						'author'   => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Only packages by this user ID.',
						],
						'orderby'  => [
							'type'    => 'string',
							'enum'    => [ 'date', 'modified', 'title', 'downloads' ],
							'default' => 'date',
						],
						'order'    => [
							'type'    => 'string',
							'enum'    => [ 'asc', 'desc' ],
							'default' => 'desc',
						],
					] + $this->pagingProperties(),
				],
				'output_schema'       => $this->pageSchema( $this->summarySchema() ),
				'permission_callback' => [ PackageService::class, 'userCanAuthor' ],
				'execute_callback'    => [ $this, 'listPackages' ],
				'annotations'         => [
					'readonly'   => true,
					'idempotent' => true,
				],
			]
		);

		$this->registerAbility(
			'wpdm/get-package',
			[
				'label'               => __( 'Get package', 'download-manager' ),
				'description'         => __( 'Reads one package: its description, files, categories, access roles, lock types and counts. Passwords and server paths are never included.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'package_id' ],
					'properties'           => [
						'package_id' => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Package ID, from wpdm/list-packages.',
						],
					],
				],
				'output_schema'       => $this->detailSchema(),
				'permission_callback' => function ( $input ) {
					return PackageService::userCanEdit( isset( $input['package_id'] ) ? (int) $input['package_id'] : 0 );
				},
				'execute_callback'    => [ $this, 'getPackage' ],
				'annotations'         => [
					'readonly'   => true,
					'idempotent' => true,
				],
			]
		);

		$this->registerAbility(
			'wpdm/create-package',
			[
				'label'               => __( 'Create package', 'download-manager' ),
				'description'         => __( 'Creates a package owned by the signed-in user and returns it. It is saved as a draft unless another status is given. Attach files afterwards with wpdm/attach-file. Everyone can download a new package unless access says otherwise.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'title' ],
					'properties'           => $this->writeProperties(),
				],
				'output_schema'       => $this->detailSchema(),
				'permission_callback' => [ PackageService::class, 'userCanAuthor' ],
				'execute_callback'    => [ $this, 'createPackage' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				],
			]
		);

		$this->registerAbility(
			'wpdm/update-package',
			[
				'label'               => __( 'Update package', 'download-manager' ),
				'description'         => __( 'Changes only the fields given and returns the updated package. Categories, tags and access are replaced as a whole. To remove the download password, set password to an empty string.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'package_id' ],
					'properties'           => [ 'package_id' => $this->packageIdProperty() ] + $this->writeProperties(),
				],
				'output_schema'       => $this->detailSchema(),
				'permission_callback' => function ( $input ) {
					return PackageService::userCanEdit( isset( $input['package_id'] ) ? (int) $input['package_id'] : 0 );
				},
				'execute_callback'    => [ $this, 'updatePackage' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => true,
				],
			]
		);

		$this->registerAbility(
			'wpdm/duplicate-package',
			[
				'label'               => __( 'Duplicate package', 'download-manager' ),
				'description'         => __( 'Copies a package, with its files, settings, categories and tags, as a new draft. The copy starts with zero downloads and gets its own master download key.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'package_id' ],
					'properties'           => [
						'package_id' => $this->packageIdProperty(),
						'title'      => [
							'type'        => 'string',
							'maxLength'   => 200,
							'description' => 'Title for the copy. Defaults to the original title.',
						],
					],
				],
				'output_schema'       => $this->detailSchema(),
				'permission_callback' => function ( $input ) {
					return current_user_can( WPDM_ADMIN_CAP ) && PackageService::userCanEdit( isset( $input['package_id'] ) ? (int) $input['package_id'] : 0 );
				},
				'execute_callback'    => [ $this, 'duplicatePackage' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				],
			]
		);

		$this->registerPrompt(
			'wpdm/publish-release',
			[
				'label'               => __( 'Publish a release', 'download-manager' ),
				'description'         => __( 'Step-by-step instructions to publish a new download: create the package, attach the file, file it under a category, publish it and report the download page.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'title' ],
					'properties'           => [
						'title'    => [
							'type'        => 'string',
							'description' => 'Package title.',
						],
						'version'  => [
							'type'        => 'string',
							'description' => 'Version label, e.g. 2.1.0.',
						],
						'file_url' => [
							'type'        => 'string',
							'description' => 'Public URL of the file to attach. Leave empty to attach it another way.',
						],
						'category' => [
							'type'        => 'string',
							'description' => 'Category name or slug. Created if it does not exist.',
						],
						'access'   => [
							'type'        => 'string',
							'description' => 'Who may download: "everyone" or a comma-separated list of role slugs.',
						],
						'notes'    => [
							'type'        => 'string',
							'description' => 'Release notes or a short description.',
						],
					],
				],
				'permission_callback' => [ PackageService::class, 'userCanAuthor' ],
				'execute_callback'    => [ $this, 'publishReleasePrompt' ],
			]
		);

		$this->registerAbility(
			'wpdm/trash-package',
			[
				'label'               => __( 'Trash package', 'download-manager' ),
				'description'         => __( 'Moves a package to the trash, where it can be restored from wp-admin. It is never deleted permanently, and its files stay on the server. Confirm with the user before calling this.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'package_id' ],
					'properties'           => [ 'package_id' => $this->packageIdProperty() ],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'id'     => [ 'type' => 'integer' ],
						'status' => [ 'type' => 'string' ],
					],
					'required'   => [ 'id', 'status' ],
				],
				'permission_callback' => function ( $input ) {
					return PackageService::userCanDelete( isset( $input['package_id'] ) ? (int) $input['package_id'] : 0 );
				},
				'execute_callback'    => [ $this, 'trashPackage' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => true,
				],
			]
		);

		$this->registerAbility(
			'wpdm/restore-package',
			[
				'label'               => __( 'Restore package', 'download-manager' ),
				'description'         => __( 'Brings a trashed package back with the status it had before it was trashed. Find trashed packages with wpdm/list-packages, status "trash". A package that is not in the trash is returned unchanged.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'package_id' ],
					'properties'           => [ 'package_id' => $this->packageIdProperty() ],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'id'     => [ 'type' => 'integer' ],
						'status' => [ 'type' => 'string' ],
					],
					'required'   => [ 'id', 'status' ],
				],
				'permission_callback' => function ( $input ) {
					return PackageService::userCanDelete( isset( $input['package_id'] ) ? (int) $input['package_id'] : 0 );
				},
				'execute_callback'    => [ $this, 'restorePackage' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => true,
				],
			]
		);

		$this->registerAbility(
			'wpdm/list-tags',
			[
				'label'               => __( 'List tags', 'download-manager' ),
				'description'         => __( 'Lists package tags with how many packages use each, most used first. Check it before tagging a package so existing tags are reused instead of near-duplicates being created.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'default'              => [],
					'additionalProperties' => false,
					'properties'           => [
						'search' => [
							'type'        => 'string',
							'maxLength'   => 200,
							'description' => 'Only tags whose name contains this text.',
						],
					] + $this->pagingProperties(),
				],
				'output_schema'       => $this->pageSchema(
					[
						'type'       => 'object',
						'properties' => [
							'id'    => [ 'type' => 'integer' ],
							'name'  => [ 'type' => 'string' ],
							'slug'  => [ 'type' => 'string' ],
							'count' => [
								'type'        => 'integer',
								'description' => 'Published packages with this tag.',
							],
						],
						'required'   => [ 'id', 'name', 'slug', 'count' ],
					]
				),
				'permission_callback' => [ PackageService::class, 'userCanAuthor' ],
				'execute_callback'    => [ $this, 'listTags' ],
				'annotations'         => [
					'readonly'   => true,
					'idempotent' => true,
				],
			]
		);
	}

	public function publishReleasePrompt( array $input ) {
		$field = function ( $key ) use ( $input ) {
			return isset( $input[ $key ] ) ? trim( (string) $input[ $key ] ) : '';
		};
		$lines = [
			sprintf( 'Publish a new download titled "%s" on this WordPress site using the Download Manager tools. Work through the steps in order and stop and ask if a step fails.', $field( 'title' ) ),
			'',
			'1. Call wpdm/list-packages with the title as the search term. If a package with the same title already exists, ask whether to update it instead of creating a duplicate.',
			'2. Call wpdm/create-package with status "draft"' . ( $field( 'version' ) ? ', version "' . $field( 'version' ) . '"' : '' ) . ( $field( 'notes' ) ? ' and this description: ' . $field( 'notes' ) : '' ) . '.',
		];
		if ( $field( 'category' ) ) {
			$lines[] = sprintf( '3. Read the category tree (wpdm://categories or wpdm/list-categories). Find "%s" by name or slug; create it with wpdm/create-category if it is missing. Then set it on the package with wpdm/update-package.', $field( 'category' ) );
		} else {
			$lines[] = '3. If the site has categories, pick the one that fits best from the category tree and set it with wpdm/update-package; otherwise leave the package uncategorized.';
		}
		$lines[] = $field( 'file_url' )
			? sprintf( '4. Attach the file with wpdm/attach-file using the URL %s.', $field( 'file_url' ) )
			: '4. Attach the release file with wpdm/attach-file. Ask for the file if none was given.';
		$lines[] = $field( 'access' )
			? sprintf( '5. Set access with wpdm/update-package: %s. Use ["guest"] for everyone.', $field( 'access' ) )
			: '5. Leave access as it is unless told otherwise.';
		$lines[] = '6. Call wpdm/get-package and check the title, version, file list and access are right.';
		$lines[] = '7. Publish it with wpdm/update-package, status "publish".';
		$lines[] = '8. Reply with the package ID, the download page URL and the file names attached.';

		return $this->promptMessages( implode( "\n", $lines ) );
	}

	public function createPackage( array $input ) {
		$id = PackageService::create( $input );

		return is_wp_error( $id ) ? $id : $this->detail( $id );
	}

	public function updatePackage( array $input ) {
		$id = (int) $input['package_id'];
		if ( 'wpdmpro' !== get_post_type( $id ) ) {
			return $this->notFound();
		}
		unset( $input['package_id'] );
		$result = PackageService::update( $id, $input );

		return is_wp_error( $result ) ? $result : $this->detail( $id );
	}

	public function duplicatePackage( array $input ) {
		$id = (int) $input['package_id'];
		if ( 'wpdmpro' !== get_post_type( $id ) ) {
			return $this->notFound();
		}
		$copy = PackageService::duplicate( $id, isset( $input['title'] ) ? $input['title'] : '' );

		return is_wp_error( $copy ) ? $copy : $this->detail( $copy );
	}

	public function trashPackage( array $input ) {
		$id = (int) $input['package_id'];
		if ( 'wpdmpro' !== get_post_type( $id ) ) {
			return $this->notFound();
		}
		$result = PackageService::trash( $id );

		return is_wp_error( $result ) ? $result : [
			'id'     => $id,
			'status' => get_post_status( $id ),
		];
	}

	public function restorePackage( array $input ) {
		$id = (int) $input['package_id'];
		if ( 'wpdmpro' !== get_post_type( $id ) ) {
			return $this->notFound();
		}
		$result = PackageService::restore( $id );

		return is_wp_error( $result ) ? $result : [
			'id'     => $id,
			'status' => get_post_status( $id ),
		];
	}

	public function listTags( array $input ) {
		$page     = isset( $input['page'] ) ? (int) $input['page'] : 1;
		$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 20;
		$args     = [
			'taxonomy'   => WPDM_TAG,
			'hide_empty' => false,
		];
		if ( ! empty( $input['search'] ) ) {
			$args['search'] = $input['search'];
		}

		$total = wp_count_terms( $args );
		if ( is_wp_error( $total ) ) {
			return $total;
		}
		$terms = get_terms(
			$args + [
				'orderby' => 'count',
				'order'   => 'DESC',
				'number'  => $per_page,
				'offset'  => ( $page - 1 ) * $per_page,
			]
		);
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		$items = [];
		foreach ( $terms as $term ) {
			$items[] = [
				'id'    => (int) $term->term_id,
				'name'  => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
				'slug'  => $term->slug,
				'count' => (int) $term->count,
			];
		}

		return $this->page( $items, (int) $total, $page, $per_page );
	}

	private function notFound() {
		return $this->error( 'wpdm_package_not_found', __( 'No package has that ID.', 'download-manager' ), 404 );
	}

	private function packageIdProperty() {
		return [
			'type'        => 'integer',
			'minimum'     => 1,
			'description' => 'Package ID, from wpdm/list-packages.',
		];
	}

	/**
	 * Input properties shared by create-package and update-package.
	 *
	 * @return array
	 */
	private function writeProperties() {
		return [
			'title'       => [
				'type'      => 'string',
				'minLength' => 1,
				'maxLength' => 200,
			],
			'description' => [
				'type'        => 'string',
				'description' => 'Package description. Basic HTML is allowed; scripts are removed.',
			],
			'excerpt'     => [
				'type'      => 'string',
				'maxLength' => 1000,
			],
			'status'      => [
				'type'        => 'string',
				'enum'        => [ 'draft', 'pending', 'publish', 'private' ],
				'description' => 'New packages default to draft. Without permission to publish, "publish" becomes "pending" (sent for review).',
			],
			'categories'  => [
				'type'        => 'array',
				'items'       => [ 'type' => [ 'integer', 'string' ] ],
				'description' => 'Category IDs or slugs, from wpdm/list-categories. Replaces the current categories.',
			],
			'tags'        => [
				'type'        => 'array',
				'items'       => [ 'type' => 'string' ],
				'description' => 'Tag names; new tags are created. Replaces the current tags.',
			],
			'version'     => [
				'type'      => 'string',
				'maxLength' => 50,
			],
			'link_label'  => [
				'type'        => 'string',
				'maxLength'   => 100,
				'description' => 'Text on the download button.',
			],
			'access'      => [
				'type'        => 'array',
				'items'       => [ 'type' => 'string' ],
				'description' => 'Role slugs allowed to download. "guest" means everyone. New packages default to ["guest"].',
			],
			'password'    => [
				'type'        => 'string',
				'maxLength'   => 100,
				'description' => 'Download password. Use [one][two] for several passwords, or an empty string to remove it. Never returned.',
			],
			'expire_date' => [
				'type'        => 'string',
				'pattern'     => '^(\d{4}-\d{2}-\d{2}( \d{2}:\d{2})?)?$',
				'description' => 'When downloads stop: YYYY-MM-DD or YYYY-MM-DD HH:MM. Empty string removes it.',
			],
			'quota'       => [
				'type'        => 'integer',
				'minimum'     => 0,
				'description' => 'Total download limit; 0 means unlimited.',
			],
		];
	}

	public function listPackages( array $input ) {
		$page     = isset( $input['page'] ) ? (int) $input['page'] : 1;
		$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 20;
		$status   = isset( $input['status'] ) ? $input['status'] : 'any';
		$order    = isset( $input['order'] ) && 'asc' === $input['order'] ? 'ASC' : 'DESC';
		$orderby  = isset( $input['orderby'] ) ? $input['orderby'] : 'date';

		$args = [
			'post_type'           => 'wpdmpro',
			'post_status'         => 'any' === $status ? self::STATUSES : $status,
			'posts_per_page'      => $per_page,
			'paged'               => $page,
			'order'               => $order,
			'orderby'             => 'downloads' === $orderby ? 'meta_value_num' : $orderby,
			'ignore_sticky_posts' => true,
		];
		if ( 'downloads' === $orderby ) {
			// An OR of EXISTS and NOT EXISTS keeps packages that never recorded a download; they sort last.
			$args['meta_query'] = [
				'relation' => 'OR',
				[
					'key'     => '__wpdm_download_count',
					'compare' => 'EXISTS',
					'type'    => 'NUMERIC',
				],
				[
					'key'     => '__wpdm_download_count',
					'compare' => 'NOT EXISTS',
				],
			];
		}
		if ( ! empty( $input['search'] ) ) {
			$args['s'] = $input['search'];
		}

		$post_type = get_post_type_object( 'wpdmpro' );
		if ( ! current_user_can( $post_type->cap->edit_others_posts ) ) {
			$args['author'] = get_current_user_id();
		} elseif ( ! empty( $input['author'] ) ) {
			$args['author'] = (int) $input['author'];
		}

		if ( isset( $input['category'] ) && '' !== $input['category'] ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'wpdmcategory',
					'field'    => ctype_digit( (string) $input['category'] ) ? 'term_id' : 'slug',
					'terms'    => $input['category'],
				],
			];
		}

		$query = new \WP_Query( $args );

		return $this->page( array_map( [ $this, 'summary' ], $query->posts ), $query->found_posts, $page, $per_page );
	}

	public function getPackage( array $input ) {
		return $this->detail( (int) $input['package_id'] );
	}

	/**
	 * Everything get-package returns.
	 *
	 * @param int $id Package ID.
	 *
	 * @return array|\WP_Error
	 */
	public function detail( $id ) {
		clean_post_cache( $id );
		$post = get_post( $id );
		if ( ! $post || 'wpdmpro' !== $post->post_type ) {
			return $this->notFound();
		}

		list( $description, $truncated ) = $this->plainText( $post->post_content, 2000 );
		list( $excerpt )                 = $this->plainText( $post->post_excerpt, 500 );

		return $this->summary( $post ) + [
			'description'           => $description,
			'description_truncated' => $truncated,
			'excerpt'               => $excerpt,
			'tags'                  => $this->terms( $id, WPDM_TAG ),
			'link_label'            => (string) get_post_meta( $id, '__wpdm_link_label', true ),
			'package_size'          => (string) get_post_meta( $id, '__wpdm_package_size', true ),
			'view_count'            => (int) get_post_meta( $id, '__wpdm_view_count', true ),
			'quota'                 => (int) get_post_meta( $id, '__wpdm_quota', true ),
			'publish_date'          => (string) get_post_meta( $id, '__wpdm_publish_date', true ),
			'expire_date'           => (string) get_post_meta( $id, '__wpdm_expire_date', true ),
			'files'                 => $this->files( $id ),
			'locks'                 => $this->locks( $id ),
			'password_protected'    => (bool) WPDM()->package->isPasswordProtected( $id ),
			'created'               => $this->isoDate( get_post_timestamp( $post ) ),
		];
	}

	/**
	 * The fields shared by list items and the full package.
	 *
	 * @param \WP_Post $post Package.
	 *
	 * @return array
	 */
	public function summary( $post ) {
		$id     = $post->ID;
		$author = get_userdata( $post->post_author );

		return [
			'id'             => $id,
			'title'          => html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ),
			'status'         => $post->post_status,
			'version'        => (string) get_post_meta( $id, '__wpdm_version', true ),
			'categories'     => $this->terms( $id, 'wpdmcategory' ),
			'file_count'     => count( WPDM()->package->getFiles( $id ) ),
			'download_count' => (int) get_post_meta( $id, '__wpdm_download_count', true ),
			'access'         => array_values( array_map( 'strval', WPDM()->package->allowedRoles( $id ) ) ),
			'locked'         => WPDM()->package->isLocked( $id ),
			'author'         => [
				'id'   => (int) $post->post_author,
				'name' => $author ? $author->display_name : '',
			],
			'modified'       => $this->isoDate( get_post_timestamp( $post, 'modified' ) ),
			'permalink'      => (string) get_permalink( $post ),
			'edit_url'       => (string) get_edit_post_link( $id, 'raw' ),
		];
	}

	private function terms( $id, $taxonomy ) {
		$terms = get_the_terms( $id, $taxonomy );
		if ( ! is_array( $terms ) ) {
			return [];
		}

		return array_values(
			array_map(
				function ( $term ) {
					return [
						'id'   => (int) $term->term_id,
						'name' => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
						'slug' => $term->slug,
					];
				},
				$terms
			)
		);
	}

	/**
	 * Attached files without server paths: local files show their name under the upload dir,
	 * absolute paths only their base name, and URLs only the file name.
	 *
	 * @param int $id Package ID.
	 *
	 * @return array
	 */
	private function files( $id ) {
		$info  = maybe_unserialize( get_post_meta( $id, '__wpdm_fileinfo', true ) );
		$files = [];
		foreach ( WPDM()->package->getFiles( $id ) as $file_id => $path ) {
			$is_url = __::is_url( $path );
			if ( $is_url ) {
				$name = basename( (string) wp_parse_url( $path, PHP_URL_PATH ) );
			} elseif ( '/' === substr( $path, 0, 1 ) || preg_match( '#^[a-zA-Z]:[\\\\/]#', $path ) ) {
				$name = basename( $path );
			} else {
				$name = $path;
			}
			$files[] = [
				'id'     => (string) $file_id,
				'name'   => $name,
				'title'  => is_array( $info ) && isset( $info[ $file_id ]['title'] ) ? (string) $info[ $file_id ]['title'] : '',
				'source' => $is_url ? 'url' : 'local',
			];
		}

		return $files;
	}

	private function locks( $id ) {
		$locks = [];
		foreach ( self::LOCKS as $lock ) {
			if ( (int) get_post_meta( $id, "__wpdm_{$lock}_lock", true ) !== 1 ) {
				continue;
			}
			if ( 'password' === $lock && '' === trim( (string) get_post_meta( $id, '__wpdm_password', true ) ) ) {
				continue;
			}
			$locks[] = $lock;
		}

		return $locks;
	}

	private function termSchema() {
		return [
			'type'       => 'object',
			'properties' => [
				'id'   => [ 'type' => 'integer' ],
				'name' => [ 'type' => 'string' ],
				'slug' => [ 'type' => 'string' ],
			],
		];
	}

	private function summarySchema() {
		return [
			'type'       => 'object',
			'properties' => [
				'id'             => [ 'type' => 'integer' ],
				'title'          => [ 'type' => 'string' ],
				'status'         => [ 'type' => 'string' ],
				'version'        => [ 'type' => 'string' ],
				'categories'     => [
					'type'  => 'array',
					'items' => $this->termSchema(),
				],
				'file_count'     => [ 'type' => 'integer' ],
				'download_count' => [ 'type' => 'integer' ],
				'access'         => [
					'type'        => 'array',
					'items'       => [ 'type' => 'string' ],
					'description' => 'Roles allowed to download. "guest" means everyone.',
				],
				'locked'         => [ 'type' => 'boolean' ],
				'author'         => [
					'type'       => 'object',
					'properties' => [
						'id'   => [ 'type' => 'integer' ],
						'name' => [ 'type' => 'string' ],
					],
				],
				'modified'       => [ 'type' => 'string' ],
				'permalink'      => [ 'type' => 'string' ],
				'edit_url'       => [ 'type' => 'string' ],
			],
			'required'   => [ 'id', 'title', 'status' ],
		];
	}

	private function detailSchema() {
		$schema                     = $this->summarySchema();
		$schema['properties']      += [
			'description'           => [ 'type' => 'string' ],
			'description_truncated' => [ 'type' => 'boolean' ],
			'excerpt'               => [ 'type' => 'string' ],
			'tags'                  => [
				'type'  => 'array',
				'items' => $this->termSchema(),
			],
			'link_label'            => [ 'type' => 'string' ],
			'package_size'          => [ 'type' => 'string' ],
			'view_count'            => [ 'type' => 'integer' ],
			'quota'                 => [
				'type'        => 'integer',
				'description' => 'Total download limit; 0 means unlimited.',
			],
			'publish_date'          => [ 'type' => 'string' ],
			'expire_date'           => [ 'type' => 'string' ],
			'files'                 => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'id'     => [ 'type' => 'string' ],
						'name'   => [ 'type' => 'string' ],
						'title'  => [ 'type' => 'string' ],
						'source' => [
							'type' => 'string',
							'enum' => [ 'local', 'url' ],
						],
					],
				],
			],
			'locks'                 => [
				'type'  => 'array',
				'items' => [ 'type' => 'string' ],
			],
			'password_protected'    => [ 'type' => 'boolean' ],
			'created'               => [ 'type' => 'string' ],
		];

		return $schema;
	}
}
