<?php

/**
 * Author: Marcin Gierada
 * Author URI: https://coolcatideas.com/
 * Author Email: info@coolcatideas.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WP_Posts_Carousel_Init class
 */
class WP_Posts_Carousel_Init
{
    const SCHEMA_VERSION = '1';
    const SCHEMA_VERSION_OPTION = 'wp_posts_carousel_db_version';

    /**
     * constructor
     */
    public function __construct()
    {
        add_action('init', array($this, 'register_post_type'), 5);
        add_action('init', array($this, 'create_table'), 6);
    }

    /**
     * register post type
     */
    public function register_post_type()
    {
        if (post_type_exists('wp-posts-carousel')) {
            return;
        }

        $carousel_cap = array(
            'edit_post'              => 'edit_wp_posts_carousel',
            'read_post'              => 'read_wp_posts_carousel',
            'delete_post'            => 'delete_wp_posts_carousel',
            'edit_posts'             => 'edit_wp_posts_carousels',
            'delete_posts'           => 'delete_wp_posts_carousels',
            'edit_others_posts'      => 'edit_others_wp_posts_carousels',
            'publish_posts'          => 'publish_wp_posts_carousels',
            'delete_published_posts' => 'delete_published_wp_posts_carousels',
            'read_private_posts'     => 'read_private_wp_posts_carousels',
            'create_posts'           => 'create_wp_posts_carousels',
            'manage_options'         => 'manage_wp_posts_carousel_options',
        );


        // get the the role object
        $admin_role = get_role('administrator');
        // grant the unfiltered_html capability
        foreach ($carousel_cap as $cap) {
            $admin_role->add_cap($cap, true);
        }

        register_post_type(
            'wp-posts-carousel',
            array(
                'labels'             => array(
                    'name'               => __('WP Posts Carousel', 'wp-posts-carousel'),
                    'singular_name'      => __('Carousel', 'wp-posts-carousel'),
                    'menu_name'          => __('WP Posts Carousel', 'wp-posts-carousel'),
                    'name_admin_bar'     => __('Carousel', 'wp-posts-carousel'),
                    'add_new'            => __('Add Carousel', 'wp-posts-carousel'),
                    'add_new_item'       => __('Add new Carousel', 'wp-posts-carousel'),
                    'new_item'           => __('New Carousel', 'wp-posts-carousel'),
                    'edit_item'          => __('Edit Carousel', 'wp-posts-carousel'),
                    'view_item'          => __('View ', 'wp-posts-carousel'),
                    'all_items'          => __('WP Posts Carousel', 'wp-posts-carousel'),
                    'search_items'       => __('Search Carousels', 'wp-posts-carousel'),
                    'not_found'          => __('Not found any Carousels', 'wp-posts-carousel'),
                    'not_found_in_trash' => __('Not found in trash', 'wp-posts-carousel')
                ),
                'description'         => null,
                'public'              => false,
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                // Carousels are edited through the plugin's React application.
                'show_ui'             => false,
                'show_in_menu'        => false,
                'show_in_nav_menus' => false,
                'show_in_admin_bar' => false,
                'query_var'           => 'wp-posts-carousel',
                'has_archive'         => false,
                'hierarchical'        => false,
                'rewrite'             => false,
                'menu_icon'           => 'dashicons-groups',
                'supports'            => array('title'),
                '_builtin' =>  false,
                'capability_type'     => 'page',
                'capabilities'        => $carousel_cap,
            )
        );
    }


    /**
     * create table
     */
    public function create_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp_posts_carousel';
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name))) === $table_name;

        if ($table_exists && get_option(self::SCHEMA_VERSION_OPTION) === self::SCHEMA_VERSION) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table_name} (
            ID integer NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            params longtext NOT NULL,
            post_modified datetime NULL,
            PRIMARY KEY (ID),
            KEY post_id (post_id)
        ) $charset_collate;";

        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }

        dbDelta($sql);
        $schema_error = $wpdb->last_error;

        if ($schema_error || $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name))) !== $table_name) {
            return new WP_Error(
                'wp_posts_carousel_table_create_failed',
                /* translators: %s: carousel database table name. */
                sprintf(__('Failed create table %s', 'wp-posts-carousel'), $table_name),
                array('status' => 500)
            );
        }

        update_option(self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION, false);
    }

}

return new WP_Posts_Carousel_Init();
