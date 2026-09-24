<?php
/**
 * Author: Marcin Gierada
 * Author URI: https://coolcatideas.com/
 * Author Email: info@coolcatideas.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Wp_Posts_Carousel_Settings
 */
class WP_Posts_Carousel_Carousels {

    /**
     * constructor
     */
    public function __construct() {
        add_action( 'load-edit.php', array( $this, 'redirect_native_editor' ) );
        add_action( 'load-post-new.php', array( $this, 'redirect_native_editor' ) );
        add_action( 'load-post.php', array( $this, 'redirect_native_editor' ) );
    }

    /**
     * manage carousels
     */
    public static function manage() {
        include_once( 'views/html-carousels-page.php' );
    }

    /**
     * Send legacy native post screens to the React administration page.
     */
    public function redirect_native_editor() {
        global $pagenow;

        $post_type = isset( $_GET['post_type'] )
            ? sanitize_key( wp_unslash( $_GET['post_type'] ) )
            : '';
        $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

        if ( $post_id ) {
            $post_type = get_post_type( $post_id );
        }

        if ( 'wp-posts-carousel' !== $post_type ) {
            return;
        }

        $query_args = array( 'page' => 'wp-posts-carousel' );

        if ( 'post-new.php' === $pagenow ) {
            $query_args['action'] = 'add-new';
        } elseif ( 'post.php' === $pagenow && $post_id ) {
            $query_args['action'] = 'edit';
            $query_args['id'] = $post_id;
        }

        wp_safe_redirect( add_query_arg( $query_args, admin_url( 'admin.php' ) ) );
        exit;
    }
}

return new WP_Posts_Carousel_Carousels();
