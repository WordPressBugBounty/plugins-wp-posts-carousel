<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Posts_Carousel_Blocks
{
    const BLOCK_NAME = 'wp-posts-carousel/carousel';

    public function __construct()
    {
        add_action('init', array($this, 'register'));
    }

    public function register()
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        $script = 'wp-posts-carousel-carousel-block';
        $script_path = 'blocks/carousel/index.js';

        wp_register_script(
            $script,
            WP_POSTS_CAROUSEL_DIR_URL . $script_path,
            array('wp-api-fetch', 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n'),
            $this->asset_version($script_path),
            true
        );

        wp_register_style(
            'wp-posts-carousel-frontend',
            WP_POSTS_CAROUSEL_DIR_URL . 'assets/css/frontend.css',
            array(),
            $this->asset_version('assets/css/frontend.css')
        );

        register_block_type(
            self::BLOCK_NAME,
            array(
                'api_version' => 2,
                'editor_script' => $script,
                'style' => 'wp-posts-carousel-frontend',
                'editor_style' => 'wp-posts-carousel-frontend',
                'render_callback' => array($this, 'render'),
                'attributes' => array(
                    'carouselId' => array(
                        'type' => 'number',
                        'default' => 0,
                    ),
                    'id' => array(
                        'type' => 'number',
                        'default' => 0,
                    ),
                    'renderer' => array(
                        'type' => 'string',
                        'default' => '',
                    ),
                ),
                'supports' => array(
                    'align' => array('wide', 'full'),
                    'html' => false,
                ),
            )
        );
    }

    public function render($attributes)
    {
        return self::render_carousel($attributes);
    }

    public static function render_carousel($attributes)
    {
        $attributes = is_array($attributes) ? $attributes : array();
        $carousel_id = isset($attributes['carouselId'])
            ? absint($attributes['carouselId'])
            : (isset($attributes['id']) ? absint($attributes['id']) : 0);

        $editor_context = self::is_editor_context();

        if (!$carousel_id) {
            return $editor_context
                ? self::render_admin_placeholder(esc_html__('Select a carousel.', 'wp-posts-carousel'))
                : '';
        }

        if (!class_exists('WP_Posts_Carousel_Generator')) {
            include_once WP_POSTS_CAROUSEL_DIR_PATH . 'includes/frontend/class-carousel-generator.php';
        }

        if (!class_exists('WP_Posts_Carousel_Generator')) {
            return '';
        }

        $editor_config = null;

        if ($editor_context) {
            if (!class_exists('WP_Posts_Carousel_Config')) {
                include_once WP_POSTS_CAROUSEL_DIR_PATH . 'includes/class-carousel-config.php';
            }

            if (!class_exists('WP_Posts_Carousel_Repository')) {
                include_once WP_POSTS_CAROUSEL_DIR_PATH . 'includes/class-carousel-repository.php';
            }

            if (!class_exists('WP_Posts_Carousel_Features')) {
                include_once WP_POSTS_CAROUSEL_DIR_PATH . 'includes/class-features.php';
            }

            $config = WP_Posts_Carousel_Repository::get_config($carousel_id);
            $editor_config = $config;

            if (!$config) {
                return self::render_admin_placeholder(
                    __('Selected carousel is not available.', 'wp-posts-carousel'),
                    __('The selected carousel definition could not be loaded.', 'wp-posts-carousel')
                );
            }

            $missing_features = WP_Posts_Carousel_Config::missing_required_features(
                $config,
                array('context' => 'admin')
            );

            if (!empty($missing_features)) {
                return self::render_admin_placeholder(
                    __('This carousel requires additional features.', 'wp-posts-carousel'),
                    self::feature_list_message($missing_features),
                    esc_url_raw(apply_filters(
                        'wp_posts_carousel_upgrade_url',
                        'https://coolcatideas.com/products/wp-posts-carousel-all-in-one'
                    ))
                );
            }
        }

        $generator = new WP_Posts_Carousel_Generator();
        $renderer = isset($attributes['renderer']) ? sanitize_key($attributes['renderer']) : '';
        if ($editor_context) {
            $editor_engine = is_array($editor_config) && !empty($editor_config['engine']['name'])
                ? sanitize_key((string) $editor_config['engine']['name'])
                : 'owl-carousel';
            $default_editor_renderer = $editor_engine === 'swiper' ? 'react' : 'php';
            $renderer = sanitize_key((string) apply_filters(
                'wp_posts_carousel_block_editor_renderer',
                $default_editor_renderer,
                $attributes,
                $carousel_id
            ));
            $renderer = $renderer ? $renderer : $default_editor_renderer;
        }

        $output = $generator->generate(
            array(
                'id' => $carousel_id,
                'renderer' => $renderer,
            ),
            0
        );

        if ($editor_context && !$output) {
            return self::render_admin_placeholder(
                __('This carousel is empty.', 'wp-posts-carousel'),
                __('No slides were rendered. Check the carousel settings and posts.', 'wp-posts-carousel')
            );
        }

        return $output;
    }

    private static function render_admin_placeholder($title, $description = '', $cta_url = '')
    {
        $output = '<div class="cci-wpc-block-placeholder cci-wpc-block-placeholder-notice">'
            . '<strong>' . esc_html($title) . '</strong>';

        if ($description) {
            $output .= '<p>' . esc_html($description) . '</p>';
        }

        if ($cta_url) {
            $output .= '<p><a href="' . esc_url($cta_url) . '" target="_blank" rel="noopener noreferrer">'
                . esc_html__('Open store', 'wp-posts-carousel')
                . '</a></p>';
        }

        return $output . '</div>';
    }

    private static function feature_list_message($required_features)
    {
        $features = class_exists('WP_Posts_Carousel_Features') ? WP_Posts_Carousel_Features::all() : array();
        $messages = array();

        foreach ((array) $required_features as $feature) {
            $feature = sanitize_key((string) $feature);

            if (!$feature) {
                continue;
            }

            $messages[] = isset($features[$feature]['label'])
                ? $features[$feature]['label']
                : $feature;
        }

        if (empty($messages)) {
            return __('Missing premium feature.', 'wp-posts-carousel');
        }

        return sprintf(
            /* translators: %s: comma-separated required product feature names. */
            __('Missing required features: %s.', 'wp-posts-carousel'),
            implode(', ', array_map('esc_html', $messages))
        );
    }

    private static function is_editor_context()
    {
        if (is_admin()) {
            return true;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return true;
        }

        if (function_exists('wp_is_json_request') && wp_is_json_request()) {
            return true;
        }

        if (function_exists('wp_is_serving_rest_request') && wp_is_serving_rest_request()) {
            return true;
        }

        if (is_customize_preview() || is_preview()) {
            return true;
        }

        $editor_request_args = array(
            'elementor-preview',
            'elementor-editor',
            'elementor-preview-nonce',
            'action',
        );
        foreach ($editor_request_args as $arg_name) {
            if (isset($_REQUEST[$arg_name]) && is_scalar($_REQUEST[$arg_name])) {
                $arg_value = sanitize_text_field(wp_unslash((string) $_REQUEST[$arg_name]));
                if ($arg_name === 'action' && strpos($arg_value, 'elementor') !== false) {
                    return true;
                }

                if ($arg_name !== 'action' && $arg_value) {
                    return true;
                }
            }
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        if (
            strpos($request_uri, '/wp/v2/block-renderer/') !== false
            || (isset($_REQUEST['rest_route']) && is_scalar($_REQUEST['rest_route']) && strpos(sanitize_text_field(wp_unslash((string) $_REQUEST['rest_route'])), '/wp/v2/block-renderer/') !== false)
        ) {
            return true;
        }

        if (
            isset($_REQUEST['context']) && is_scalar($_REQUEST['context']) &&
            sanitize_key(wp_unslash((string) $_REQUEST['context'])) === 'edit'
        ) {
            return true;
        }

        $referer = isset($_SERVER['HTTP_REFERER']) && is_string($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
        if (strpos($referer, home_url('/wp-admin/')) === 0) {
            return true;
        }

        return false;
    }

    private function asset_version($asset)
    {
        $path = WP_POSTS_CAROUSEL_DIR_PATH . ltrim($asset, '/');

        return file_exists($path) ? (string) filemtime($path) : WP_POSTS_CAROUSEL_VERSION;
    }
}

return new WP_Posts_Carousel_Blocks();
