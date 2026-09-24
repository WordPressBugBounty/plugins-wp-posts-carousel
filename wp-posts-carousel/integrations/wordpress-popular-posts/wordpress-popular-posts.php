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

if ( class_exists( 'WP_Posts_Carousel_Wordpress_Popular_Posts' ) ) {
    return false;
}

/**
 * WP_Posts_Carousel_Popular_Posts integration class
 */
class WP_Posts_Carousel_Wordpress_Popular_Posts extends WP_Posts_Carousel_Integration {
    private $args;
    private $params;
    private $interval;

    /**
     * instance of this class
     */
    protected static $instance = null;

    /**
     * constructor
     */
    public function __construct() {
        $this->integration_details = parent::set_integration_details( __FILE__ );

        // includes
        $this->includes();
    }

    public function includes() {
        if ( is_admin() ) {
            include_once( 'includes/admin/class-admin.php' );
        } else {
            include_once( 'includes/frontend/class-frontend.php' );
        }
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

return WP_Posts_Carousel_Wordpress_Popular_Posts::get_instance();
?>