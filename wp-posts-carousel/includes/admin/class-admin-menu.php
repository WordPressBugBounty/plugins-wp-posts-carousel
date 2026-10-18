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
 * WP_Posts_Carousel_Menu class
 */
class WP_Posts_Carousel_Menu {

    /**
     * constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    /**
     * add menu
     */
    public function admin_menu() {
        add_menu_page(
            'CCI Posts Carousel All In One',
            'CCI Posts Carousel All In One',
            'edit_wp_posts_carousels',
            'cci-posts-carousel',
            array( 'WP_Posts_Carousel_Carousels', 'manage' )
        );
    }

}

return new WP_Posts_Carousel_Menu();
