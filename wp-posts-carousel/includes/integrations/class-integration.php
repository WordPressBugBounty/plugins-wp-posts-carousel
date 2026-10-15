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
 * WP_Posts_Carousel_Integration class
 */
class WP_Posts_Carousel_Integration {

    public $integration_details = array();

    /**
     * constructor
     */
    public function __construct() {

    }

    /**
     * set integration details from file
     */
    public function set_integration_details( $path ) {
        if ( ! $path || ! file_exists( $path ) ) {
            return;
        }

        $integration = array();
        $screenshot_file = 'screenshot.png';
        $integration_headers = array(
            'integration_name'                    => 'Plugin Name',
            'integration_url'                     => 'Plugin URI',
            'description'                         => 'Description',
            'tags'                                => 'Tags',
            'author'                              => 'Author',
            'author_url'                          => 'Author URI',
            'author_email'                        => 'Author Email',
            'version'                             => 'Version',
            'requires_wp_posts_carousel_at_least' => 'Requires WP Posts Carousel at least',
            'license'                             => 'License',
            'license_url'                         => 'License URI',
        );

        $integration = get_file_data( $path, $integration_headers );

        if ( count( $integration ) > 0 ) {
            $integration['dir']        = dirname( $path );
            $integration['name']       = basename( $integration['dir'] );
            $integration['dir_url']    = plugin_dir_url( $path );
            $integration['compatible'] = $this->check_integration( $integration );

            if ( file_exists( $integration['dir'] . '/' . $screenshot_file ) ) {
                $integration['screenshot'] = $integration['dir_url'] . $screenshot_file;
            } else {
                $integration['screenshot'] = WP_POSTS_CAROUSEL_DIR_URL . 'assets/images/template-placeholder.png';
            }
        }

        if ( empty( $integration['name'] ) ) {
            return array();
        }

        $this->integration_details[$integration['name']] = $integration;

        return $this->integration_details;
    }

    /**
     * check compatibility with plugin version
     */
    public function check_integration( $integration_details ) {
        $required_version = trim( (string) $integration_details['requires_wp_posts_carousel_at_least'] );

        if ( $required_version && version_compare( WP_POSTS_CAROUSEL_VERSION, $required_version, '<' ) ) {
            return array(
                'status' => false,
                /* translators: %s: carousel integration name. */
                'error'  => sprintf( __( "This integration '%s' is not compatible with the current version of this plugin", 'wp-posts-carousel' ), $integration_details['integration_name'] ),
            );
        }

        return array(
            'status' => true,
            'error'  => null
        );
    }
}
?>
