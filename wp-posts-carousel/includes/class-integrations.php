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
 * WP_Posts_Carousel_Integrations class
 */
class WP_Posts_Carousel_Integrations {

    protected $integrations = array();

    /**
     * constructor
     */
    public function __construct() {
        $this->includes();

        // load default integrations
        $defaults = $this->get_default_integrations();

        // load installed integrations
        $installed_integrations = array();

        // load integrations from API
        $others_integrations = array();
        if ( is_admin() ) {
            $others_integrations = $this->get_others_integrations();
        }

        // return integrations as objects list
        $this->integrations = array_merge( $defaults, $installed_integrations, $others_integrations );
    }

    /**
     *
     */
    public function includes() {
        include_once( 'class-integration.php' );
    }

    public function get_integrations() {
        return $this->integrations;
    }

    /**
     *
     */
    public function get_default_integrations() {
        $integrations = array();
        $directory = WP_POSTS_CAROUSEL_DIR_PATH . 'integrations';

        if ( is_dir( $directory ) ) {
            $folders = scandir( $directory );

            if ( count( $folders ) > 0 ) {
                if ( array_key_exists( 0, $folders ) ) {
                    unset( $folders[0] );
                }
                if ( array_key_exists( 1, $folders ) ) {
                    unset( $folders[1] );
                }

                foreach ( $folders as $folder ) {
                    $dir = $directory . '/' . $folder . '/' . $folder . '.php';

                    if ( file_exists( $dir ) ) {
                        $integration_obj = include_once ( $dir );

                        if ( is_object( $integration_obj ) && $integration_obj->integration_details ) {
                            $integrations = array_merge( $integrations, $integration_obj->integration_details );
                        }
                    }
                }
            }
        }

        return $integrations;
    }

    /**
     * get integrations from the Cool Cat Ideas API
     */
    public function get_others_integrations() {
        $integrations = array();

        if ( class_exists( 'WP_Posts_Carousel_API' ) ) {
            $api = new WP_Posts_Carousel_API();
            $api->get_integrations();
        }
        return $integrations;
    }
}
?>
