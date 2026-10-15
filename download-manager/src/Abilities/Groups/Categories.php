<?php

namespace WPDM\Abilities\Groups;

use WPDM\Abilities\AbilityGroup;
use WPDM\Package\PackageService;

/**
 * Category abilities: read the category tree.
 *
 * @since 3.3.73
 */
class Categories extends AbilityGroup {

	const CATEGORY = 'wpdm-categories';

	public function register() {
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
				'permission_callback' => function () {
					$taxonomy = get_taxonomy( 'wpdmcategory' );

					return $taxonomy && current_user_can( $taxonomy->cap->manage_terms );
				},
				'execute_callback'    => [ $this, 'createCategory' ],
				'annotations'         => [
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				],
			]
		);
	}

	public function createCategory( array $input ) {
		$parent = isset( $input['parent'] ) ? (int) $input['parent'] : 0;
		if ( $parent && ! term_exists( $parent, 'wpdmcategory' ) ) {
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
			$term->add_data( [ 'status' => 'term_exists' === $term->get_error_code() ? 409 : 400 ] );

			return $term;
		}
		if ( $roles ) {
			update_term_meta( $term['term_id'], '__wpdm_access', $roles );
		}

		$total    = 0;
		$created  = get_term( $term['term_id'], 'wpdmcategory' );
		$children = [ $parent => [ $created ] ];
		$nodes    = $this->branch( $children, $parent, $total );

		return $nodes[0];
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
