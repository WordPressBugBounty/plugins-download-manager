<?php

namespace WPDM\Abilities\Groups;

use WPDM\Abilities\AbilityGroup;
use WPDM\Package\PackageService;

/**
 * Category abilities: read the category tree, as a tool and as a resource, and manage categories.
 *
 * @since 3.3.73
 */
class Categories extends AbilityGroup {

	const CATEGORY = 'wpdm-categories';

	const TREE_URI = 'wpdm://categories';

	public function register() {
		$this->registerResource(
			'wpdm/category-tree',
			[
				'label'               => __( 'Category tree', 'download-manager' ),
				'description'         => __( 'The whole package category tree as JSON: every category\'s ID, slug, package count, allowed roles and children. Read it before filing a package or filtering by category.', 'download-manager' ),
				'uri'                 => self::TREE_URI,
				'permission_callback' => [ PackageService::class, 'userCanAuthor' ],
				'execute_callback'    => [ $this, 'categoryTree' ],
			]
		);

		$this->registerAbility(
			'wpdm/list-categories',
			[
				'label'               => __( 'List categories', 'download-manager' ),
				'description'         => __( 'Returns the package category tree with each category\'s ID, slug, package count and the roles allowed to download from it. Use a category ID or slug to filter wpdm/list-packages.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'default'              => [],
					'additionalProperties' => false,
					'properties'           => [
						'parent' => [
							'type'        => 'integer',
							'minimum'     => 0,
							'default'     => 0,
							'description' => 'Start from this category ID; 0 returns the whole tree.',
						],
					],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'items' => [
							'type'  => 'array',
							'items' => $this->nodeSchema(),
						],
						'total' => [
							'type'        => 'integer',
							'description' => 'Categories in the returned tree, at every depth.',
						],
					],
					'required'   => [ 'items', 'total' ],
				],
				'permission_callback' => [ PackageService::class, 'userCanAuthor' ],
				'execute_callback'    => [ $this, 'listCategories' ],
				'annotations'         => [
					'readonly'   => true,
					'idempotent' => true,
				],
			]
		);

		$this->registerAbility(
			'wpdm/create-category',
			[
				'label'               => __( 'Create category', 'download-manager' ),
				'description'         => __( 'Creates a package category, optionally under a parent and limited to some roles. Check wpdm/list-categories first to avoid duplicates.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'name' ],
					'properties'           => [
						'name'        => [
							'type'      => 'string',
							'minLength' => 1,
							'maxLength' => 200,
						],
						'slug'        => [
							'type'      => 'string',
							'maxLength' => 200,
						],
						'parent'      => [
							'type'        => 'integer',
							'minimum'     => 0,
							'description' => 'Parent category ID; 0 or absent for a top-level category.',
						],
						'description' => [
							'type'      => 'string',
							'maxLength' => 2000,
						],
						'access'      => [
							'type'        => 'array',
							'items'       => [ 'type' => 'string' ],
							'description' => 'Role slugs allowed to download from this category. "guest" means everyone; empty means no category-level restriction.',
						],
					],
				],
				'output_schema'       => $this->nodeSchema(),
				'permission_callback' => $this->can( 'manage_terms' ),
				'execute_callback'    => [ $this, 'createCategory' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				],
			]
		);

		$this->registerAbility(
			'wpdm/update-category',
			[
				'label'               => __( 'Update category', 'download-manager' ),
				'description'         => __( 'Changes only the fields given of a category: name, slug, description, parent or allowed roles. Pass parent 0 to make it top-level and access [] to remove the role restriction.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'category_id' ],
					'properties'           => [
						'category_id' => $this->categoryIdProperty(),
						'name'        => [
							'type'      => 'string',
							'minLength' => 1,
							'maxLength' => 200,
						],
						'slug'        => [
							'type'      => 'string',
							'maxLength' => 200,
						],
						'parent'      => [
							'type'        => 'integer',
							'minimum'     => 0,
							'description' => 'New parent category ID; 0 makes it top-level.',
						],
						'description' => [
							'type'      => 'string',
							'maxLength' => 2000,
						],
						'access'      => [
							'type'        => 'array',
							'items'       => [ 'type' => 'string' ],
							'description' => 'Role slugs allowed to download from this category. "guest" means everyone; empty removes the category-level restriction.',
						],
					],
				],
				'output_schema'       => $this->nodeSchema(),
				'permission_callback' => $this->can( 'edit_terms' ),
				'execute_callback'    => [ $this, 'updateCategory' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => true,
				],
			]
		);

		$this->registerAbility(
			'wpdm/delete-category',
			[
				'label'               => __( 'Delete category', 'download-manager' ),
				'description'         => __( 'Deletes a category. Its packages stay as they are, just without this category, and its sub-categories move up to its parent. This cannot be undone, so confirm with the user before calling it.', 'download-manager' ),
				'input_schema'        => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'category_id' ],
					'properties'           => [ 'category_id' => $this->categoryIdProperty() ],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'id'                => [ 'type' => 'integer' ],
						'deleted'           => [ 'type' => 'boolean' ],
						'packages_affected' => [
							'type'        => 'integer',
							'description' => 'Packages that were in the category.',
						],
						'children_moved'    => [
							'type'        => 'integer',
							'description' => 'Sub-categories moved up to the parent.',
						],
					],
					'required'   => [ 'id', 'deleted', 'packages_affected', 'children_moved' ],
				],
				'permission_callback' => $this->can( 'delete_terms' ),
				'execute_callback'    => [ $this, 'deleteCategory' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => true,
				],
			]
		);
	}

	public function createCategory( array $input ) {
		$parent = isset( $input['parent'] ) ? (int) $input['parent'] : 0;
		if ( $parent && ! $this->exists( $parent ) ) {
			return $this->error( 'wpdm_category_not_found', __( 'No category has that parent ID.', 'download-manager' ), 404 );
		}

		$roles = [];
		if ( isset( $input['access'] ) ) {
			$roles = PackageService::validRoles( (array) $input['access'] );
			if ( is_wp_error( $roles ) ) {
				return $roles;
			}
		}

		$term = wp_insert_term(
			sanitize_text_field( $input['name'] ),
			'wpdmcategory',
			[
				'slug'        => isset( $input['slug'] ) ? sanitize_title( $input['slug'] ) : '',
				'parent'      => $parent,
				'description' => isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : '',
			]
		);
		if ( is_wp_error( $term ) ) {
			return $this->termError( $term );
		}
		if ( $roles ) {
			update_term_meta( $term['term_id'], '__wpdm_access', $roles );
		}

		return $this->node( (int) $term['term_id'] );
	}

	public function updateCategory( array $input ) {
		$id = (int) $input['category_id'];
		if ( ! $this->exists( $id ) ) {
			return $this->notFound();
		}

		$args = [];
		if ( isset( $input['name'] ) ) {
			$args['name'] = sanitize_text_field( $input['name'] );
		}
		if ( isset( $input['slug'] ) ) {
			$args['slug'] = sanitize_title( $input['slug'] );
		}
		if ( isset( $input['description'] ) ) {
			$args['description'] = sanitize_textarea_field( $input['description'] );
		}
		if ( isset( $input['parent'] ) ) {
			$parent = (int) $input['parent'];
			if ( $parent && ! $this->exists( $parent ) ) {
				return $this->error( 'wpdm_category_not_found', __( 'No category has that parent ID.', 'download-manager' ), 404 );
			}
			if ( $parent === $id || in_array( $parent, array_map( 'intval', (array) get_term_children( $id, 'wpdmcategory' ) ), true ) ) {
				return $this->error( 'wpdm_invalid_parent', __( 'A category cannot be moved under itself or one of its own sub-categories.', 'download-manager' ), 400 );
			}
			$args['parent'] = $parent;
		}

		$roles = null;
		if ( isset( $input['access'] ) ) {
			$roles = PackageService::validRoles( (array) $input['access'] );
			if ( is_wp_error( $roles ) ) {
				return $roles;
			}
		}

		if ( $args ) {
			$result = wp_update_term( $id, 'wpdmcategory', $args );
			if ( is_wp_error( $result ) ) {
				return $this->termError( $result );
			}
		}
		if ( null !== $roles ) {
			if ( $roles ) {
				update_term_meta( $id, '__wpdm_access', $roles );
			} else {
				delete_term_meta( $id, '__wpdm_access' );
			}
		}

		return $this->node( $id );
	}

	public function deleteCategory( array $input ) {
		$id   = (int) $input['category_id'];
		$term = get_term( $id, 'wpdmcategory' );
		if ( ! $term || is_wp_error( $term ) ) {
			return $this->notFound();
		}

		$packages = get_objects_in_term( $id, 'wpdmcategory' );
		$children = get_terms(
			[
				'taxonomy'   => 'wpdmcategory',
				'parent'     => $id,
				'hide_empty' => false,
				'fields'     => 'ids',
			]
		);

		$result = wp_delete_term( $id, 'wpdmcategory' );
		if ( is_wp_error( $result ) ) {
			return $this->termError( $result );
		}
		if ( ! $result ) {
			return $this->error( 'wpdm_delete_failed', __( 'The category could not be deleted.', 'download-manager' ), 500 );
		}

		return [
			'id'                => $id,
			'deleted'           => true,
			'packages_affected' => is_array( $packages ) ? count( $packages ) : 0,
			'children_moved'    => is_array( $children ) ? count( $children ) : 0,
		];
	}

	/**
	 * One category as get-package and the tree report it, without its children.
	 *
	 * @param int $id Term ID.
	 *
	 * @return array
	 */
	private function node( $id ) {
		$term     = get_term( $id, 'wpdmcategory' );
		$parent   = (int) $term->parent;
		$total    = 0;
		$children = [ $parent => [ $term ] ];
		$nodes    = $this->branch( $children, $parent, $total );

		return $nodes[0];
	}

	private function exists( $id ) {
		$term = get_term( (int) $id, 'wpdmcategory' );

		return $term && ! is_wp_error( $term );
	}

	/**
	 * A permission callback for one capability of the category taxonomy.
	 *
	 * @param string $cap manage_terms, edit_terms or delete_terms.
	 *
	 * @return \Closure
	 */
	private function can( $cap ) {
		return function () use ( $cap ) {
			$taxonomy = get_taxonomy( 'wpdmcategory' );

			return $taxonomy && current_user_can( $taxonomy->cap->$cap );
		};
	}

	private function categoryIdProperty() {
		return [
			'type'        => 'integer',
			'minimum'     => 1,
			'description' => 'Category ID, from wpdm/list-categories.',
		];
	}

	private function notFound() {
		return $this->error( 'wpdm_category_not_found', __( 'No category has that ID.', 'download-manager' ), 404 );
	}

	/**
	 * A term error with an HTTP status: 409 for a taken name or slug, 400 otherwise.
	 *
	 * @param \WP_Error $error From wp_insert_term(), wp_update_term() or wp_delete_term().
	 *
	 * @return \WP_Error
	 */
	private function termError( \WP_Error $error ) {
		$error->add_data( [ 'status' => in_array( $error->get_error_code(), [ 'term_exists', 'duplicate_term_slug' ], true ) ? 409 : 400 ] );

		return $error;
	}


	/**
	 * The whole tree as a JSON document at wpdm://categories.
	 */
	public function categoryTree() {
		$tree = $this->listCategories( [] );
		if ( is_wp_error( $tree ) ) {
			return $tree;
		}

		return $this->jsonContents( self::TREE_URI, $tree );
	}

	public function listCategories( array $input ) {
		$parent = isset( $input['parent'] ) ? (int) $input['parent'] : 0;
		$terms  = get_terms(
			[
				'taxonomy'   => 'wpdmcategory',
				'hide_empty' => false,
			]
		);
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		$children = [];
		foreach ( $terms as $term ) {
			$children[ (int) $term->parent ][] = $term;
		}

		$total = 0;
		$items = $this->branch( $children, $parent, $total );

		return [
			'items' => $items,
			'total' => $total,
		];
	}

	private function branch( array $children, $parent, &$total ) {
		$nodes = [];
		foreach ( isset( $children[ $parent ] ) ? $children[ $parent ] : [] as $term ) {
			$total++;
			$nodes[] = [
				'id'       => (int) $term->term_id,
				'name'     => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ),
				'slug'     => $term->slug,
				'parent'   => (int) $term->parent,
				'count'    => (int) $term->count,
				'access'   => array_values( array_map( 'strval', (array) WPDM()->categories->getAllowedRoles( $term->term_id ) ) ),
				'children' => $this->branch( $children, (int) $term->term_id, $total ),
			];
		}

		return $nodes;
	}

	private function nodeSchema() {
		return [
			'type'       => 'object',
			'properties' => [
				'id'       => [ 'type' => 'integer' ],
				'name'     => [ 'type' => 'string' ],
				'slug'     => [ 'type' => 'string' ],
				'parent'   => [ 'type' => 'integer' ],
				'count'    => [ 'type' => 'integer' ],
				'access'   => [
					'type'        => 'array',
					'items'       => [ 'type' => 'string' ],
					'description' => 'Roles allowed to download. Empty means no category-level restriction.',
				],
				'children' => [
					'type'        => 'array',
					'items'       => [ 'type' => 'object' ],
					'description' => 'Child categories, same shape.',
				],
			],
			'required'   => [ 'id', 'name', 'slug' ],
		];
	}
}
