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
 * Wp_Posts_Carousel_Settings class
 */
class WP_Posts_Carousel_Settings {
    static $settings = array();
    const OPTION_KEY = 'wp-posts-carousel_settings';

    /**
     * constructor
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        self::get_settings();
    }

    /**
     * register settings
     */
    public function register_settings() {
        register_setting(
            'wp-posts-carousel',
            self::OPTION_KEY,
            array(
                'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
            )
        );
    }

    /**
     * get default settings
     */
    public static function get_default_settings() {
        $default_breakpoints = array(
            320  => 2,
            480  => 3,
            768  => 3,
            1024 => 4,
            1200 => 4,
        );

        return array_merge(
            array(
            'include_font_awesome'       => false,
            'include_in_footer'          => false,
	        'is_debug_mode'              => false,
            'load_owl_assets'            => true,
            'load_owl_css'               => true,
            'load_mousewheel_asset'      => true,
            'swiper_source'              => 'bundled',
            'load_swiper_css'            => true,
            'global_breakpoints'          => self::sanitize_breakpoints(
                apply_filters( 'wp_posts_carousel_global_breakpoints', $default_breakpoints ),
                $default_breakpoints
            ),
            ),
            class_exists( 'WP_Posts_Carousel_Icons' )
                ? WP_Posts_Carousel_Icons::default_settings()
                : array(
                    'icon_library' => 'fontawesome',
                    'icon_source'  => 'local',
                )
        );
    }

    /**
     * set settings
     */
    public static function get_settings() {
        $settings = get_option( self::OPTION_KEY );
        $settings = is_array( $settings ) ? $settings : array();

        return self::$settings = self::sanitize_settings(
            array_merge( self::get_default_settings(), $settings ),
            self::get_default_settings()
        );
    }

    public static function get_asset_diagnostics() {
        $diagnostics = array_merge(
            self::detect_registered_asset_library( wp_scripts(), 'script', 'owl', array( 'owl.carousel', 'owl-carousel', 'owlcarousel' ) ),
            self::detect_registered_asset_library( wp_styles(), 'style', 'owl', array( 'owl.carousel', 'owl-carousel', 'owlcarousel' ) ),
            self::detect_registered_asset_library( wp_scripts(), 'script', 'swiper', array( 'swiper' ) ),
            self::detect_registered_asset_library( wp_styles(), 'style', 'swiper', array( 'swiper' ) ),
            // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Detects an asset registered by another plugin or theme; this string is never requested or enqueued.
            self::detect_registered_asset_library( wp_scripts(), 'script', 'fontawesome', array( 'font-awesome', 'fontawesome', 'font awesome', 'kit.fontawesome' ) ),
            self::detect_registered_asset_library( wp_styles(), 'style', 'fontawesome', array( 'font-awesome', 'fontawesome', 'font awesome' ) ),
            self::detect_registered_asset_library( wp_scripts(), 'script', 'tabler', array( 'tabler-icons', 'tabler.icons', '@tabler/icons' ) ),
            self::detect_registered_asset_library( wp_styles(), 'style', 'tabler', array( 'tabler-icons', 'tabler.icons', '@tabler/icons' ) )
        );

        return apply_filters( 'wp_posts_carousel_asset_diagnostics', self::unique_asset_diagnostics( $diagnostics ) );
    }

    private static function detect_registered_asset_library( $registry, $asset_type, $library, array $needles ) {
        if ( ! is_object( $registry ) || empty( $registry->registered ) || ! is_array( $registry->registered ) ) {
            return array();
        }

        $detected = array();

        foreach ( $registry->registered as $handle => $asset ) {
            $src = isset( $asset->src ) ? (string) $asset->src : '';

            if ( self::is_own_asset( $handle, $src ) ) {
                continue;
            }

            $haystack = strtolower( (string) $handle . ' ' . $src );

            foreach ( $needles as $needle ) {
                if ( false === strpos( $haystack, strtolower( $needle ) ) ) {
                    continue;
                }

                $detected[] = array(
                    'library' => $library,
                    'assetType' => $asset_type,
                    'handle' => sanitize_key( $handle ),
                    'src' => esc_url_raw( $src ),
                    'source' => 'wordpress',
                );
                break;
            }
        }

        return $detected;
    }

    private static function is_own_asset( $handle, $src ) {
        $handle = (string) $handle;
        $src = (string) $src;

        if ( 0 === strpos( $handle, 'wp-posts-carousel-' ) ) {
            return true;
        }

        $own_handles = array(
            'owl.carousel',
            'owl.carousel.style',
            'jquery-mousewheel',
            'wp-posts-carousel-frontend-loader',
            'wp-posts-carousel-js-react',
        );

        if ( in_array( $handle, $own_handles, true ) && self::is_own_asset_src( $src ) ) {
            return true;
        }

        return self::is_own_asset_src( $src );
    }

    private static function is_own_asset_src( $src ) {
        if ( ! $src ) {
            return false;
        }

        $src = html_entity_decode( (string) $src );
        $src_path = wp_parse_url( $src, PHP_URL_PATH );

        if ( ! $src_path && 0 === strpos( $src, '/' ) ) {
            $src_path = $src;
        }

        $plugin_url = defined( 'WP_POSTS_CAROUSEL_DIR_URL' ) ? WP_POSTS_CAROUSEL_DIR_URL : '';

        if ( $plugin_url && 0 === strpos( $src, $plugin_url ) ) {
            return true;
        }

        if ( $src_path && false !== strpos( $src_path, '/wp-posts-carousel/' ) ) {
            return true;
        }

        if ( $src_path && false !== strpos( $src_path, '/plugins/wp-posts-carousel/' ) ) {
            return true;
        }

        return false;
    }

    private static function unique_asset_diagnostics( array $diagnostics ) {
        $seen = array();
        $out = array();

        foreach ( $diagnostics as $diagnostic ) {
            $key = implode( '|', array(
                isset( $diagnostic['library'] ) ? $diagnostic['library'] : '',
                isset( $diagnostic['assetType'] ) ? $diagnostic['assetType'] : '',
                isset( $diagnostic['handle'] ) ? $diagnostic['handle'] : '',
                isset( $diagnostic['src'] ) ? $diagnostic['src'] : '',
            ) );

            if ( isset( $seen[ $key ] ) ) {
                continue;
            }

            $seen[ $key ] = true;
            $out[] = $diagnostic;
        }

        return $out;
    }

    public static function sanitize_settings( $settings, $base = null ) {
        $settings = is_array( $settings ) ? $settings : array();
        $defaults = self::get_default_settings();
        $base = is_array( $base ) ? array_merge( $defaults, $base ) : $defaults;
        $boolean_keys = array(
            'include_font_awesome',
            'include_in_footer',
            'is_debug_mode',
            'load_owl_assets',
            'load_owl_css',
            'load_mousewheel_asset',
            'load_swiper_css',
        );
        $sanitized = array();

        foreach ( $defaults as $key => $default ) {
            $value = array_key_exists( $key, $settings ) ? $settings[ $key ] : $base[ $key ];

            if ( in_array( $key, $boolean_keys, true ) ) {
                $sanitized[ $key ] = self::truthy( $value );
                continue;
            }

            if ( $key === 'swiper_source' ) {
                $value = sanitize_key( $value );
                $sanitized[ $key ] = in_array( $value, array( 'bundled', 'global' ), true ) ? $value : $default;
                continue;
            }

            if ( $key === 'icon_library' ) {
                $sanitized[ $key ] = class_exists( 'WP_Posts_Carousel_Icons' )
                    ? WP_Posts_Carousel_Icons::normalize_library( $value )
                    : sanitize_key( $value );
                continue;
            }

            if ( $key === 'icon_source' ) {
                $sanitized[ $key ] = class_exists( 'WP_Posts_Carousel_Icons' )
                    ? WP_Posts_Carousel_Icons::normalize_source( $value )
                    : sanitize_key( $value );
                continue;
            }

            if ( $key === 'global_breakpoints' ) {
                $sanitized[ $key ] = self::sanitize_breakpoints(
                    apply_filters(
                        'wp_posts_carousel_settings_global_breakpoints',
                        self::sanitize_breakpoints( $value, $default ),
                        $settings,
                        $base
                    ),
                    $default
                );
                continue;
            }

            $sanitized[ $key ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
        }

        return apply_filters( 'wp_posts_carousel_sanitized_settings', $sanitized, $settings, $base );
    }

    private static function truthy( $value ) {
        return $value === true || $value === 1 || $value === '1' || $value === 'true' || $value === 'on';
    }

    private static function sanitize_breakpoints( $value, $default ) {
        if ( is_object( $value ) ) {
            $value = get_object_vars( $value );
        }

        $value = is_array( $value ) ? $value : array();
        $out = array();

        foreach ( $value as $width => $items ) {
            if ( is_object( $items ) ) {
                $items = get_object_vars( $items );
            }

            $width = absint( $width );

            if ( ! $width ) {
                continue;
            }

            if ( is_array( $items ) && isset( $items['items'] ) ) {
                $items = $items['items'];
            }

            $items = absint( $items );

            if ( ! $items ) {
                continue;
            }

            $out[ $width ] = $items;
        }

        if ( empty( $out ) ) {
            $out = $default;
        }

        ksort( $out );

        return $out;
    }
	/**
	 * Save settings
	 *
	 * Array keys must be whitelisted (IE must be keys of self::$defaults
	 *
	 * @param array $settings
	 */
	public static function save_settings( array  $settings ){
		$settings = self::sanitize_settings( $settings, self::get_settings() );
		update_option( self::OPTION_KEY, $settings );

        return $settings;
	}

}
