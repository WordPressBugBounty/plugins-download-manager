<?php


namespace WPDM\Package;


class RestAPI
{

    function __construct()
    {
        add_action('rest_api_init', array($this, 'introduceEndpoints'));

        // WordPress's own /wp/v2/wpdmpro route follows "Hide all package info from users without access"
        add_filter('rest_wpdmpro_query', array($this, 'hideRestrictedPackages'), 10, 2);
        add_filter('rest_request_before_callbacks', array($this, 'refuseRestrictedPackage'), 10, 3);
    }

    /**
     * Whether packages the visitor can't access are hidden from them.
     * Users who can edit posts see every package, as in the admin.
     *
     * @return bool
     */
    private function hidesRestricted()
    {
        return (int)get_option('_wpdm_hide_all', 0) === 1 && !current_user_can('edit_posts');
    }

    /**
     * Leave packages the visitor can't access out of /wp/v2/wpdmpro lists,
     * matched on the package's access roles as the site's own package lists are.
     *
     * @param array $args
     * @param \WP_REST_Request $request
     * @return array
     */
    function hideRestrictedPackages($args, $request)
    {
        if (!$this->hidesRestricted()) return $args;

        $access = ['relation' => 'OR'];
        foreach (array_unique(array_merge(['guest'], (array)wp_get_current_user()->roles)) as $role) {
            $access[] = ['key' => '__wpdm_access', 'value' => '"' . $role . '"', 'compare' => 'LIKE'];
        }
        $args['meta_query'] = empty($args['meta_query']) ? [$access] : ['relation' => 'AND', $args['meta_query'], $access];

        return $args;
    }

    /**
     * Refuse a single package the visitor can't access, as its page does.
     * Runs before the posts controller, which can't take an error back from its own filters.
     *
     * @param \WP_REST_Response|\WP_HTTP_Response|\WP_Error|mixed $response
     * @param array $handler
     * @param \WP_REST_Request $request
     * @return mixed
     */
    function refuseRestrictedPackage($response, $handler, $request)
    {
        if (is_wp_error($response) || !in_array($request->get_method(), ['GET', 'HEAD'], true)) return $response;
        if (!preg_match('#^/wp/v2/wpdmpro/(\d+)$#', $request->get_route(), $match) || !$this->hidesRestricted()) return $response;

        $id = (int)$match[1];
        if (get_post_type($id) !== 'wpdmpro' || WPDM()->package->userCanAccess($id)) return $response;

        return new \WP_Error('rest_post_invalid_id', __('Invalid post ID.'), ['status' => 404]);
    }

    function introduceEndpoints()
    {

        register_rest_route('wpdm', '/validate-captcha', array(
            'methods' => 'POST',
            'callback' => [new PackageLocks(), 'validateCaptcha'],
            'permission_callback' => '__return_true'
        ));

        register_rest_route('wpdm', '/validate-password', array(
            'methods' => 'POST',
            'callback' => [new PackageLocks(), 'validatePassword'],
            'permission_callback' => '__return_true'
        ));

        register_rest_route('wpdm', '/search', array(
            'methods' => 'GET',
            'callback' => [new PackageController(), 'search'],
            'permission_callback' => '__return_true'
        ));

        // NOTE: The POST /wpdm/view-count route was removed. It exposed a
        // state-changing write (__wpdm_view_count post meta, on any post ID)
        // to unauthenticated callers via permission_callback => '__return_true'.
        // Nothing in the plugin ever called it - view counts are recorded
        // through the 'wpdm_view_count' admin-ajax action (see addViewCount()),
        // which verifies a nonce and constrains the ID to the wpdmpro type.

    }
}
