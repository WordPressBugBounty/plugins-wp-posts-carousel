<?php
/**
 * Plugin Name: Light Template for WP Posts Carousel
 * Plugin URI: https://coolcatideas.com/products/wp-posts-carousel-all-in-one
 * Description: Free, light template
 * Tags: carousel, posts carousel, wordpress carousel, wordpress owl carousel, owl carousel, wp posts carousel compact, compact theme, clean theme, flat theme
 * Author: Cool Cat Ideas
 * Author URI: https://coolcatideas.com/
 * Version: 2.0.0
 * Requires WP Posts Carousel at least: 2.0.0
 * Requires at least: 6.2
 * Tested up to: 7.1.2
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_Posts_Carousel_Light_Template' ) ) :

/**
 * WP_Posts_Carousel_Light_Template class
 */
class WP_Posts_Carousel_Light_Template extends WP_Posts_Carousel_Template {

    /**
     * instance of this class
     */
    protected static $instance = null;

    /**
     * constructor
     */
    public function __construct() {
        $this->template_details = parent::set_template_details( __FILE__ );
    }

    /**
     * return an instance of this class
     */
    public static function get_instance() {
        // if the single instance hasn't been set, set it now
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}

return WP_Posts_Carousel_Light_Template::get_instance();
endif;
