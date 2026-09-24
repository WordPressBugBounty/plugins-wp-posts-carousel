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
 * WP_Posts_Carousel_Wordpress_Popular_Posts_Meta_Boxes class
 */
class WP_Posts_Carousel_Wordpress_Popular_Posts_Meta_Boxes {

    /**
     * constructor
     */
    public function __construct() {

        // add filters
        add_filter( 'wp_posts_carousel_post_criteria_meta_box_shows', array( $this, 'wp_posts_carousel_post_criteria_meta_box_shows' ), 2, 1);

    }

    public function wp_posts_carousel_post_criteria_meta_box_shows( $shows ) {

        $custom_interval = apply_filters( 'wp_posts_carousel_wordpress_popular_posts_shows_custom_interval', '1 YEAR' );

        $plugin_shows = array(
            'wordpress-popular-posts-popular'          => __( 'Popular all the time', 'wp-posts-carousel' ),
            'wordpress-popular-posts-popular-1-day'    => __( 'Popular from 24 hours', 'wp-posts-carousel' ),
            'wordpress-popular-posts-popular-2-days'   => __( 'Popular from 2 days', 'wp-posts-carousel' ),
            'wordpress-popular-posts-popular-2-weeks'  => __( 'Popular from 2 weeks', 'wp-posts-carousel' ),
            'wordpress-popular-posts-popular-1-month'  => __( 'Popular from month', 'wp-posts-carousel' ),
            'wordpress-popular-posts-popular-2-months' => __( 'Popular from 2 months', 'wp-posts-carousel' ),
            'wordpress-popular-posts-popular-3-months' => __( 'Popular from 3 months', 'wp-posts-carousel' ),
            'wordpress-popular-posts-popular-custom'   => sprintf( __( 'Popular from custom interval', 'wp-posts-carousel' ) . ' (%s)', $custom_interval ),
        );

        return array_merge( $shows, $plugin_shows );
    }

}

return new WP_Posts_Carousel_Wordpress_Popular_Posts_Meta_Boxes();