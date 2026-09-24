<?php
/**
 * Author: Marcin Gierada
 * Author URI: https://coolcatideas.com/
 * Author Email: info@coolcatideas.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WP_Posts_Carousel_Admin class
 */
class WP_Posts_Carousel_Frontend
{
    /**
     * Track editor asset enqueue contexts in the current request.
     *
     * The block editor may load the editor UI and the canvas iframe as separate
     * documents. A single boolean guard can enqueue assets in the sidebar/admin
     * page while leaving the actual preview canvas without Owl, template styles
     * or FontAwesome.
     */
    private static $editor_assets_enqueued = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('init', array( $this, 'includes' ));

        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_editor_scripts'), 99);
            add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_scripts'), 99);
            add_action('enqueue_block_assets', array($this, 'enqueue_block_canvas_assets'), 99);
            add_action('elementor/editor/after_enqueue_scripts', array($this, 'enqueue_editor_scripts'), 99);
            add_action('elementor/editor/after_enqueue_styles', array($this, 'enqueue_editor_scripts'), 99);
            add_action('elementor/preview/enqueue_scripts', array($this, 'enqueue_editor_scripts'), 99);
            add_action('elementor/preview/enqueue_styles', array($this, 'enqueue_editor_scripts'), 99);
        } else {
            add_action('wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 99);
        }
    }

    public function includes()
    {
        include_once('class-carousel-generator.php');
        include_once('class-shortcode-decode.php');
    }

    /**
     * enqueue scripts and styles
     */
    public function enqueue_scripts()
    {
        if (!$this->should_enqueue_frontend_assets()) {
            return;
        }

        $this->enqueue_assets('frontend');
    }

    private function should_enqueue_frontend_assets()
    {
        global $wp_query;

        $posts = array();
        $queried_object = get_queried_object();

        if ($queried_object instanceof WP_Post) {
            $posts[] = $queried_object;
        }

        if ($wp_query instanceof WP_Query && is_array($wp_query->posts)) {
            foreach ($wp_query->posts as $post) {
                if ($post instanceof WP_Post) {
                    $posts[$post->ID] = $post;
                }
            }
        }

        $should_enqueue = is_active_widget(false, false, 'wp_posts_carousel', true);

        foreach ($posts as $post) {
            $content = (string) $post->post_content;

            if (
                has_shortcode($content, 'wp_posts_carousel')
                || has_block('wp-posts-carousel/carousel', $content)
                || false !== strpos((string) get_post_meta($post->ID, '_elementor_data', true), 'wp_posts_carousel')
            ) {
                $should_enqueue = true;
                break;
            }
        }

        return (bool) apply_filters(
            'wp_posts_carousel_should_enqueue_frontend_assets',
            $should_enqueue,
            array_values($posts)
        );
    }

    public function enqueue_editor_scripts()
    {
        if (! $this->should_enqueue_editor_assets()) {
            return;
        }

        $this->enqueue_assets('admin-preview');
    }

    public function enqueue_block_canvas_assets()
    {
        if (!is_admin()) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (
            $screen
            && method_exists($screen, 'is_block_editor')
            && ! $screen->is_block_editor()
            && ! in_array($screen->base, array('post', 'post-new', 'site-editor', 'widgets'), true)
        ) {
            return;
        }

        $this->enqueue_assets('block-editor-canvas');
    }

    private function should_enqueue_editor_assets()
    {
        if (!is_admin()) {
            return false;
        }

        if (!empty(self::$editor_assets_enqueued['admin-preview'])) {
            return false;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if ($screen === null) {
            return true;
        }

        if (method_exists($screen, 'is_block_editor') && $screen->is_block_editor()) {
            return true;
        }

        return in_array(
            $screen->base,
            array(
                'post',
                'post-new',
                'edit',
                'elementor',
                'edit-elementor_library',
                'elementor_library',
            ),
            true
        );
    }

    private function enqueue_assets($context = 'frontend')
    {
        if ('frontend' !== $context && !empty(self::$editor_assets_enqueued[$context])) {
            return;
        }

        $settings = WP_Posts_Carousel_Settings::get_settings();
        $include_in_footer = !empty($settings['include_in_footer']);
        $load_owl_assets = (bool) apply_filters('wp_posts_carousel_load_owl_assets', !empty($settings['load_owl_assets']), $settings);
        $load_owl_css = (bool) apply_filters('wp_posts_carousel_load_owl_css', !empty($settings['load_owl_css']), $settings);
        $load_mousewheel_asset = (bool) apply_filters('wp_posts_carousel_load_mousewheel_asset', !empty($settings['load_mousewheel_asset']), $settings);
        $owl_script_handle = $this->asset_handle(apply_filters('wp_posts_carousel_owl_script_handle', 'owl.carousel', $settings));
        $mousewheel_script_handle = $this->asset_handle(apply_filters('wp_posts_carousel_mousewheel_script_handle', 'jquery-mousewheel', $settings));
        $owl_style_handle = $this->asset_handle(apply_filters('wp_posts_carousel_owl_style_handle', 'owl.carousel.style', $settings));
        $swiper_source = sanitize_key(apply_filters('wp_posts_carousel_swiper_source', $settings['swiper_source'], $settings));
        $swiper_source = in_array($swiper_source, array('bundled', 'global'), true) ? $swiper_source : 'bundled';

        if (in_array($context, array('admin-preview', 'block-editor-canvas'), true)) {
            $load_owl_assets = true;
            $load_owl_css = true;
        }

        if ($load_owl_assets && $owl_script_handle) {
            wp_register_script($owl_script_handle, WP_POSTS_CAROUSEL_DIR_URL . 'libraries/owl.carousel/owl.carousel.min.js', array( 'jquery' ), '2.3.4', $include_in_footer);
            wp_enqueue_script($owl_script_handle);
        }

        if ($load_mousewheel_asset && $mousewheel_script_handle) {
            wp_register_script($mousewheel_script_handle, WP_POSTS_CAROUSEL_DIR_URL . 'libraries/owl.carousel/jquery.mousewheel.min.js', array( 'jquery' ), '3.1.12', $include_in_footer);
            wp_enqueue_script($mousewheel_script_handle);
        }

        wp_register_script('wp-posts-carousel-frontend-loader', WP_POSTS_CAROUSEL_DIR_URL . 'assets/js/frontend-loader.js', array(), $this->asset_version('assets/js/frontend-loader.js'), true);
        wp_localize_script(
            'wp-posts-carousel-frontend-loader',
            'wpPostsCarouselRuntimeConfig',
            apply_filters(
                'wp_posts_carousel_frontend_runtime_config',
                array(
                    'assets' => array(
                        'swiperSource' => $swiper_source,
                        'loadSwiperCss' => (bool) apply_filters('wp_posts_carousel_load_swiper_css', !empty($settings['load_swiper_css']), $settings),
                    ),
                    'labels' => array(
                        'previousSlide' => __('Previous slide', 'wp-posts-carousel'),
                        'nextSlide' => __('Next slide', 'wp-posts-carousel'),
                        /* translators: %d: slide number. */
                        'goToSlide' => __('Go to slide %d', 'wp-posts-carousel'),
                    ),
                ),
                $settings
            )
        );

        if ($load_owl_css && $owl_style_handle) {
            wp_register_style($owl_style_handle, WP_POSTS_CAROUSEL_DIR_URL . 'libraries/owl.carousel/assets/owl.carousel.min.css', array(), '2.3.4');
            wp_enqueue_style($owl_style_handle);
        }

        wp_enqueue_style(
            'wp-posts-carousel-frontend',
            WP_POSTS_CAROUSEL_DIR_URL . 'assets/css/frontend.css',
            array(),
            $this->asset_version('assets/css/frontend.css')
        );

        $this->enqueue_template_styles();

        wp_enqueue_script('jquery-effects-core');
        wp_enqueue_script('wp-posts-carousel-frontend-loader');

        if (class_exists('WP_Posts_Carousel_Icons')) {
            WP_Posts_Carousel_Icons::enqueue_assets(
                $settings,
                array(
                    'context' => $context,
                )
            );
        }

        wp_register_style('wp-posts-carousel-accessibility', false, array(), WP_POSTS_CAROUSEL_VERSION);
        wp_enqueue_style('wp-posts-carousel-accessibility');
        wp_add_inline_style(
            'wp-posts-carousel-accessibility',
            '.cci-wpc-carousel .screen-reader-text,.cci-wpc-render-preview .screen-reader-text,.cci-wpc-react-controls .screen-reader-text{clip:rect(1px,1px,1px,1px);clip-path:inset(50%);height:1px;margin:-1px;overflow:hidden;padding:0;position:absolute;width:1px;word-wrap:normal;}'
        );

        do_action(
            'wp_posts_carousel_after_enqueue_scripts',
            array(
                'context' => $context,
            )
        );

        if (is_admin()) {
            do_action(
                'wp_posts_carousel_in_admin_after_enqueue_scripts',
                array(
                    'context' => $context,
                )
            );
        }

        if ('frontend' !== $context) {
            self::$editor_assets_enqueued[$context] = true;
        }
    }

    private function enqueue_template_styles()
    {
        if (!class_exists('WP_Posts_Carousel_Templates')) {
            return;
        }

        foreach (WP_Posts_Carousel_Templates::discover_templates() as $template) {
            if (empty($template['name']) || empty($template['style']) || empty($template['style_url']) || !file_exists($template['style'])) {
                continue;
            }

            $style_version = (string) filemtime($template['style']);

            wp_enqueue_style(
                'wp-posts-carousel-template-' . sanitize_key($template['name']),
                $template['style_url'],
                array(),
                $style_version ? $style_version : (!empty($template['version']) ? $template['version'] : WP_POSTS_CAROUSEL_VERSION)
            );
        }
    }

    private function asset_version($asset)
    {
        $path = WP_POSTS_CAROUSEL_DIR_PATH . ltrim($asset, '/');

        return file_exists($path) ? (string) filemtime($path) : WP_POSTS_CAROUSEL_VERSION;
    }

    private function asset_handle($handle)
    {
        return preg_replace('/[^A-Za-z0-9_.-]/', '', (string) $handle);
    }
}

return new WP_Posts_Carousel_Frontend();
