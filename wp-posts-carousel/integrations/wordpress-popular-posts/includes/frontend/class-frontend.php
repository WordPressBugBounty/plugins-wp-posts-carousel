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
 * WP_Posts_Carousel_Popular_Posts integration class
 */
class WP_Posts_Carousel_Wordpress_Popular_Posts_Frontend extends WP_Query {

    /**
     * constructor
     */
    public function __construct() {
        add_action( 'init', array( $this, 'includes' ) );
    }

    public function includes() {
        include_once( 'includes/frontend/class-query.php' );
    }
}