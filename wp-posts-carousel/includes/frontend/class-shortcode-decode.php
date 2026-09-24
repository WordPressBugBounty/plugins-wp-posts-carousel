<?php
/*
 * Author: Marcin Gierada
 * Author URI: https://coolcatideas.com/
 * Author Email: info@coolcatideas.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WP_Posts_Carousel_Shortcode_Decode {
    private static $index = 0;

    public static function initialize( $atts, $content = null, $code = '' ) {
        self::$index++;

        $generator = new WP_Posts_Carousel_Generator();
        return $generator->generate( $atts, self::$index );
    }
}
add_shortcode( 'wp_posts_carousel', array( 'WP_Posts_Carousel_Shortcode_Decode', 'initialize' ) );
?>
