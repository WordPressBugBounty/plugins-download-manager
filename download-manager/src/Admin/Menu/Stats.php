<?php

/**
 * User: shahnuralam
 * Date: 11/9/15
 * Time: 7:44 PM
 */

namespace WPDM\Admin\Menu;


use WPDM\__\__;
use WPDM\__\Crypt;
use WPDM\__\FileSystem;

class Stats
{
    function __construct()
    {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_init', array($this, 'export'));
        add_action('wp_ajax_wpdm_stats_get_packages', array($this, 'ajax_callback_get_packages'));
        add_action('wp_ajax_wpdm_stats_get_users', array($this, 'ajax_callback_get_users'));
    }

    function menu()
    {
        add_submenu_page('edit.php?post_type=wpdmpro', __("Stats &lsaquo; Download Manager", "download-manager"), __("History", "download-manager"), WPDM_MENU_ACCESS_CAP, 'wpdm-stats', array($this, 'UI'));
    }

    function UI()
    {
        include wpdm_admin_tpl_path("stats.php");
    }

    public function ajax_callback_get_packages()
    {
		__::isAuthentic('__spnonce', WPDM_PUB_NONCE, WPDM_MENU_ACCESS_CAP);
        global $wpdb;
        $posts_table = "{$wpdb->prefix}posts";
        $packages = [];
        // 'txt' rather than the default sanitiser: prepare() below does the
        // escaping, and the default would HTML-encode quotes, so a title like
        // "O'Brien" could never be matched.
        $term = wpdm_query_var('term', 'txt');

        if ($term) {
            $like = '%' . $wpdb->esc_like($term) . '%';
            $result_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT ID, post_title FROM {$posts_table} WHERE `post_type` = 'wpdmpro' AND `post_title` LIKE %s",
                $like
            ));
            foreach ($result_rows as $row) {
                $packages[] = [
                    'id'   => $row->ID,
                    // Select2 escapes result text itself; decode stored entities so titles display correctly
                    'text' => wp_specialchars_decode($row->post_title, ENT_QUOTES)
                ];
            }
        }
        //results key is necessary for jquery select2
        wp_send_json(["results" => $packages]);
    }

    public function ajax_callback_get_users()
    {
	    __::isAuthentic('__spnonce', WPDM_PUB_NONCE, WPDM_MENU_ACCESS_CAP);
        global $wpdb;
        $users_table = "{$wpdb->base_prefix}users";
        // See ajax_callback_get_packages() for why this is 'txt' and not the default.
        $term = wpdm_query_var('term', 'txt');
        $users = [];

        if ($term) {
            $like = '%' . $wpdb->esc_like($term) . '%';
            $result_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT ID, user_login, display_name, user_email FROM {$users_table}
                 WHERE `display_name` LIKE %s OR `user_nicename` LIKE %s OR `user_login` LIKE %s OR `user_email` LIKE %s",
                $like,
                $like,
                $like,
                $like
            ));
            foreach ($result_rows as $row) {
                // Select2 escapes result text itself; decode stored entities so names display correctly
                $text    = wp_specialchars_decode($row->display_name, ENT_QUOTES) . " ( $row->user_login ) ";
                $users[] = [
                    'id'   => $row->ID,
                    'text' => $text
                ];
            }
        }
        //results key is necessary for jquery select2
        wp_send_json(["results" => $users]);
    }



    function export()
    {
        if (wpdm_query_var('page') == 'wpdm-stats' && wpdm_query_var('task') == 'export') {
            if(!current_user_can(WPDM_ADMIN_CAP) || !wp_verify_nonce(wpdm_query_var('__xnonce'), NONCE_KEY)) die('Invalid nonce!');
            global $wpdb;
            $sql = wpdm_query_var("hash") !== '' ? Crypt::decrypt(wpdm_query_var('hash')) : "";
            if(!$sql) $sql = "SELECT [##fields##] FROM {$wpdb->prefix}ahm_download_stats";
            //$data = $wpdb->get_results("select s.*, p.post_title as file from {$wpdb->prefix}ahm_download_stats s, {$wpdb->prefix}posts p where p.ID = s.pid order by id DESC");
            $total = $wpdb->get_var(str_replace("[##fields##]", "count(*) as total", $sql));
            WPDM()->fileSystem->downloadHeaders("download-stats.csv");
            ob_start();
            echo "Package ID,Package Name,User ID,User Name,User Email,Order ID,Date,Timestamp\r\n";
            ob_flush();
            $pages = $total / 20;
            if ($pages > (int) $pages) $pages++;

            for ($i = 0; $i <= $pages; $i++) {
                $start = $i * 20;
                $data = $wpdb->get_results(str_replace("[##fields##]", "*", $sql)." limit $start, 20");
                ob_start();
                foreach ($data as $d) {
                    $package_name = get_the_title($d->pid);
                    $package_name = addslashes($package_name);
                    if ($d->uid > 0) {
                        $u = get_user_by('ID', $d->uid);
                        echo "{$d->pid},\"{$package_name}\",{$d->uid},\"{$u->display_name}\",\"{$u->user_email}\",{$d->oid},{$d->year}-{$d->month}-{$d->day},{$d->timestamp}\r\n";
                    } else
                        echo "{$d->pid},\"{$package_name}\",-,\"-\",\"-\",{$d->oid},{$d->year}-{$d->month}-{$d->day},{$d->timestamp}\r\n";
                }
                ob_flush();
            }
            die();
        }
    }
}
