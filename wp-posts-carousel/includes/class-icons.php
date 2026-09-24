<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Posts_Carousel_Icons
{
    const DEFAULT_LIBRARY = 'fontawesome';
    const DEFAULT_SOURCE = 'local';

    public static function default_settings()
    {
        return array(
            'icon_library' => self::DEFAULT_LIBRARY,
            'icon_source' => self::DEFAULT_SOURCE,
        );
    }

    public static function libraries()
    {
        $libraries = array(
            'fontawesome' => array(
                'label' => __('Font Awesome', 'wp-posts-carousel'),
                'class_base' => 'fa',
                'class_prefix' => 'fa-',
                'defaults' => array(
                    'previous' => 'angle-left',
                    'next' => 'angle-right',
                    'up' => 'angle-up',
                    'down' => 'angle-down',
                ),
                'example' => 'angle-left',
                'assets' => array(
                    'local' => array(
                        'handle' => 'wp-posts-carousel-font-awesome',
                        'path' => 'libraries/font-awesome/css/font-awesome.min.css',
                        'url' => WP_POSTS_CAROUSEL_DIR_URL . 'libraries/font-awesome/css/font-awesome.min.css',
                    ),
                    'cdn' => array(
                        'handle' => 'wp-posts-carousel-font-awesome-cdn',
                        // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Optional, documented icon-font inclusion; directory guideline 8 permits font CDNs. Local files are the default.
                        'url' => 'https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css',
                        'version' => '4.3.0',
                    ),
                ),
            ),
            'tabler' => array(
                'label' => __('Tabler Icons', 'wp-posts-carousel'),
                'class_base' => 'ti',
                'class_prefix' => 'ti-',
                'defaults' => array(
                    'previous' => 'chevron-left',
                    'next' => 'chevron-right',
                    'up' => 'chevron-up',
                    'down' => 'chevron-down',
                ),
                'example' => 'chevron-left',
                'assets' => array(
                    'local' => array(
                        'handle' => 'wp-posts-carousel-tabler-icons',
                        'path' => 'libraries/tabler-icons/css/tabler-icons.min.css',
                        'url' => WP_POSTS_CAROUSEL_DIR_URL . 'libraries/tabler-icons/css/tabler-icons.min.css',
                    ),
                    'cdn' => array(
                        'handle' => 'wp-posts-carousel-tabler-icons-cdn',
                        // phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Optional, documented icon-font inclusion; directory guideline 8 permits font CDNs. Local files are the default.
                        'url' => 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.44.0/dist/tabler-icons.min.css',
                        'version' => '3.44.0',
                    ),
                ),
            ),
        );

        return apply_filters('wp_posts_carousel_icon_libraries', $libraries);
    }

    public static function public_libraries()
    {
        $public = array();

        foreach (self::libraries() as $key => $library) {
            $key = sanitize_key($key);

            if (!$key) {
                continue;
            }

            $public[] = array(
                'value' => $key,
                'label' => sanitize_text_field(isset($library['label']) ? $library['label'] : $key),
                'classBase' => self::sanitize_class_token(isset($library['class_base']) ? $library['class_base'] : ''),
                'classPrefix' => self::sanitize_class_token(isset($library['class_prefix']) ? $library['class_prefix'] : ''),
                'defaults' => self::sanitize_icon_defaults(isset($library['defaults']) ? $library['defaults'] : array()),
                'example' => self::sanitize_icon_name(isset($library['example']) ? $library['example'] : ''),
            );
        }

        return $public;
    }

    public static function public_sources()
    {
        $sources = array(
            array(
                'value' => 'local',
                'label' => __('Local plugin files', 'wp-posts-carousel'),
                'description' => __('Load the selected icon CSS from this plugin.', 'wp-posts-carousel'),
            ),
            array(
                'value' => 'cdn',
                'label' => __('CDN', 'wp-posts-carousel'),
                'description' => __('Load the selected icon CSS from a public CDN.', 'wp-posts-carousel'),
            ),
            array(
                'value' => 'existing',
                'label' => __('Already loaded by theme or plugin', 'wp-posts-carousel'),
                'description' => __('Do not enqueue icon CSS. Use classes from assets already present on the page.', 'wp-posts-carousel'),
            ),
        );

        return apply_filters('wp_posts_carousel_icon_sources', $sources);
    }

    public static function settings($settings)
    {
        $settings = is_array($settings) ? $settings : array();
        $merged = array_merge(self::default_settings(), $settings);
        $merged['icon_library'] = self::normalize_library($merged['icon_library']);
        $merged['icon_source'] = self::normalize_source($merged['icon_source']);

        return apply_filters('wp_posts_carousel_icon_settings', $merged, $settings);
    }

    public static function normalize_library($library)
    {
        $library = sanitize_key($library);
        $libraries = self::libraries();

        if ($library && isset($libraries[$library])) {
            return $library;
        }

        if (isset($libraries[self::DEFAULT_LIBRARY])) {
            return self::DEFAULT_LIBRARY;
        }

        $keys = array_keys($libraries);

        return !empty($keys[0]) ? sanitize_key($keys[0]) : self::DEFAULT_LIBRARY;
    }

    public static function normalize_source($source)
    {
        $source = sanitize_key($source);
        $sources = array_map(
            function ($item) {
                return isset($item['value']) ? sanitize_key($item['value']) : '';
            },
            self::public_sources()
        );

        return in_array($source, $sources, true) ? $source : self::DEFAULT_SOURCE;
    }

    public static function sanitize_icon_name($icon)
    {
        $icon = trim(sanitize_text_field((string) $icon));
        $icon = preg_replace('/\s+/', '-', $icon);

        return preg_replace('/[^A-Za-z0-9_-]/', '', $icon);
    }

    public static function enqueue_assets($settings, $context = array())
    {
        $settings = self::settings($settings);
        $source = $settings['icon_source'];

        if ($source === 'existing') {
            return false;
        }

        $libraries = self::libraries();
        $library_key = $settings['icon_library'];
        $library = isset($libraries[$library_key]) ? $libraries[$library_key] : array();

        if (empty($library['assets'][$source]) || !is_array($library['assets'][$source])) {
            return false;
        }

        $should_load = true;

        if ($library_key === 'fontawesome') {
            $should_load = (bool) apply_filters(
                'wp_posts_carousel_load_font_awesome',
                true,
                $settings
            );
            $should_load = $should_load || !empty($settings['include_font_awesome']);
        }

        $should_load = (bool) apply_filters(
            'wp_posts_carousel_load_icon_assets',
            $should_load,
            $settings,
            $library,
            is_array($context) ? $context : array()
        );

        if (!$should_load) {
            return false;
        }

        $asset = $library['assets'][$source];
        $handle = self::sanitize_handle(isset($asset['handle']) ? $asset['handle'] : 'wp-posts-carousel-' . $library_key . '-icons');
        $url = isset($asset['url']) ? esc_url_raw($asset['url']) : '';

        if (!$handle || !$url) {
            return false;
        }

        wp_enqueue_style(
            $handle,
            $url,
            isset($asset['dependencies']) && is_array($asset['dependencies']) ? $asset['dependencies'] : array(),
            self::asset_version($asset)
        );

        return $handle;
    }

    public static function navigation_icons($params = array(), $config = array())
    {
        $settings = class_exists('WP_Posts_Carousel_Settings')
            ? WP_Posts_Carousel_Settings::get_settings()
            : self::default_settings();
        $settings = self::settings($settings);
        $params = is_array($params) ? $params : array();
        $library_key = !empty($params['nav_icon_library'])
            ? self::normalize_library($params['nav_icon_library'])
            : $settings['icon_library'];
        $source = $settings['icon_source'];
        $library = self::library($library_key);
        $defaults = self::sanitize_icon_defaults(isset($library['defaults']) ? $library['defaults'] : array());
        $icons = array(
            'library' => $library_key,
            'source' => $source,
        );

        foreach (array('previous', 'next', 'up', 'down') as $direction) {
            $param_key = 'nav_icon_' . $direction;
            $name = !empty($params[$param_key])
                ? self::sanitize_icon_name($params[$param_key])
                : (isset($defaults[$direction]) ? $defaults[$direction] : '');
            $icons[$direction] = self::build_icon_data($direction, $name, $library_key, $library);
        }

        return apply_filters('wp_posts_carousel_navigation_icons', $icons, $params, $config, $settings);
    }

    public static function navigation_icon($direction, $params = array())
    {
        $direction = self::direction_key($direction);
        $params = is_array($params) ? $params : array();
        $icons = isset($params['navigation_icons']) && is_array($params['navigation_icons'])
            ? $params['navigation_icons']
            : self::navigation_icons($params);
        $icon = isset($icons[$direction]) && is_array($icons[$direction]) ? $icons[$direction] : array();

        if (!$icon) {
            $fallback_icons = self::navigation_icons($params);
            $icon = isset($fallback_icons[$direction]) && is_array($fallback_icons[$direction])
                ? $fallback_icons[$direction]
                : array();
        }

        if (isset($icon['library'])) {
            unset($icon['library']);
        }

        return apply_filters('wp_posts_carousel_navigation_icon', $icon, $direction, $params);
    }

    public static function navigation_icon_html($direction, $params = array())
    {
        $icon = self::navigation_icon($direction, $params);
        $html = isset($icon['html']) ? (string) $icon['html'] : '';

        if (!$html) {
            $class_name = isset($icon['className']) ? $icon['className'] : '';
            $html = '<i class="' . esc_attr($class_name) . '" aria-hidden="true"></i>';
        }

        return apply_filters('wp_posts_carousel_navigation_icon_html', $html, $direction, $params, $icon);
    }

    private static function library($library_key)
    {
        $libraries = self::libraries();
        $library_key = self::normalize_library($library_key);

        if (isset($libraries[$library_key])) {
            return $libraries[$library_key];
        }

        if (isset($libraries[self::DEFAULT_LIBRARY])) {
            return $libraries[self::DEFAULT_LIBRARY];
        }

        return !empty($libraries) ? reset($libraries) : array();
    }

    private static function build_icon_data($direction, $name, $library_key, $library)
    {
        $direction = self::direction_key($direction);
        $name = self::sanitize_icon_name($name);
        $base = self::sanitize_class_token(isset($library['class_base']) ? $library['class_base'] : '');
        $prefix = self::sanitize_class_token(isset($library['class_prefix']) ? $library['class_prefix'] : '');
        $class_names = trim($base . ' ' . $prefix . $name);

        $data = array(
            'library' => sanitize_key($library_key),
            'direction' => $direction,
            'name' => $name,
            'className' => $class_names,
            'html' => '<i class="' . esc_attr($class_names) . '" aria-hidden="true" data-wpc-icon-library="' . esc_attr($library_key) . '" data-wpc-icon-name="' . esc_attr($name) . '"></i>',
        );

        return apply_filters('wp_posts_carousel_build_navigation_icon', $data, $direction, $library_key, $library);
    }

    private static function sanitize_icon_defaults($defaults)
    {
        $defaults = is_array($defaults) ? $defaults : array();
        $out = array();

        foreach (array('previous', 'next', 'up', 'down') as $direction) {
            $out[$direction] = self::sanitize_icon_name(isset($defaults[$direction]) ? $defaults[$direction] : '');
        }

        return $out;
    }

    private static function direction_key($direction)
    {
        $direction = sanitize_key($direction);

        if (in_array($direction, array('previous', 'prev', 'left'), true)) {
            return 'previous';
        }

        if (in_array($direction, array('next', 'right'), true)) {
            return 'next';
        }

        if ($direction === 'up') {
            return 'up';
        }

        if ($direction === 'down') {
            return 'down';
        }

        return 'previous';
    }

    private static function sanitize_class_token($value)
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', (string) $value);
    }

    private static function sanitize_handle($handle)
    {
        return preg_replace('/[^A-Za-z0-9_.-]/', '', (string) $handle);
    }

    private static function asset_version($asset)
    {
        if (!empty($asset['version'])) {
            return sanitize_text_field((string) $asset['version']);
        }

        if (!empty($asset['path'])) {
            $path = WP_POSTS_CAROUSEL_DIR_PATH . ltrim($asset['path'], '/');

            if (file_exists($path)) {
                return (string) filemtime($path);
            }
        }

        return WP_POSTS_CAROUSEL_VERSION;
    }
}
