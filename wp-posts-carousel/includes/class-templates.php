<?php

/**
 * Author: Marcin Gierada
 * Author URI: https://coolcatideas.com/
 * Author Email: info@coolcatideas.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WP_Posts_Carousel_Templates class
 */
class WP_Posts_Carousel_Templates
{

    protected $templates = array();

    /**
     * constructor
     */
    public function __construct()
    {
        $this->includes();
    }

    /**
     *
     */
    public function includes()
    {
        include_once('class-template.php');
    }

    public function get_templates()
    {
        $this->templates = self::discover_templates();

        return $this->templates;
    }


    /**
     *
     */
    public function get_defaults_templates()
    {
        return self::discover_templates();
    }

    public static function find_template($template_name)
    {
        $template_name = self::normalize_template_name(str_replace('.css', '', $template_name));

        if (!$template_name) {
            return array(
                'status' => false,
                'error' => __('Template name is not valid', 'wp-posts-carousel'),
            );
        }

        $template = self::get_template($template_name);

        if (!$template) {
            return array(
                'status' => false,
                /* translators: %s: carousel template name. */
                'error' => sprintf(__('Could not find template: %s', 'wp-posts-carousel'), $template_name),
            );
        }

        if (empty($template['render']) || !file_exists($template['render'])) {
            return array(
                'status' => false,
                /* translators: %s: carousel template name. */
                'error' => sprintf(__('Template view file is missing: %s', 'wp-posts-carousel'), $template_name),
            );
        }

        if (!empty($template['style']) && file_exists($template['style'])) {
            $template_style_handle = 'wp-posts-carousel-template-' . $template_name;
            wp_enqueue_style(
                $template_style_handle,
                $template['style_url'],
                array(),
                $template['version'] ? $template['version'] : WP_POSTS_CAROUSEL_VERSION
            );
        }

        return apply_filters(
            'wp_posts_carousel_template',
            $template,
            $template_name
        );
    }

    public static function discover_templates()
    {
        $templates = array();

        foreach (self::template_paths() as $base_path => $base_url) {
            if (!is_dir($base_path)) {
                continue;
            }

            $folders = glob(trailingslashit($base_path) . '*', GLOB_ONLYDIR);
            if (!$folders) {
                continue;
            }

            foreach ($folders as $folder) {
                $template = self::load_template_manifest($folder, trailingslashit($base_url) . basename($folder) . '/');
                if (!$template || isset($templates[$template['name']])) {
                    continue;
                }

                $templates[$template['name']] = $template;
            }
        }

        return array_values(apply_filters('wp_posts_carousel_templates', $templates));
    }

    public static function get_template($template_name)
    {
        $template_name = self::normalize_template_name($template_name);

        foreach (self::discover_templates() as $template) {
            if ($template['name'] === $template_name) {
                return $template;
            }
        }

        return null;
    }

    public static function normalize_template_name($template_name)
    {
        $template_name = sanitize_key($template_name);
        $aliases = apply_filters(
            'wp_posts_carousel_template_aliases',
            array(
                'wp-posts-carousel-default-template' => 'default',
            )
        );

        return isset($aliases[$template_name]) ? sanitize_key($aliases[$template_name]) : $template_name;
    }

    public static function template_paths()
    {
        $upload_dir = wp_upload_dir(null, false);
        $paths = array(
            WP_POSTS_CAROUSEL_DIR_PATH . 'templates' => WP_POSTS_CAROUSEL_DIR_URL . 'templates',
            get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'wp-posts-carousel' . DIRECTORY_SEPARATOR . 'templates' => get_stylesheet_directory_uri() . '/wp-posts-carousel/templates',
        );

        if (empty($upload_dir['error'])) {
            $paths[trailingslashit($upload_dir['basedir']) . 'wp-posts-carousel/templates'] = trailingslashit($upload_dir['baseurl']) . 'wp-posts-carousel/templates';
        }

        return apply_filters('wp_posts_carousel_templates_paths', $paths);
    }

    private static function load_template_manifest($template_dir, $template_url)
    {
        $manifest_path = trailingslashit($template_dir) . 'template.json';

        if (!file_exists($manifest_path)) {
            return null;
        }

        $manifest = json_decode(file_get_contents($manifest_path), true);
        if (!is_array($manifest)) {
            return null;
        }

        $name = sanitize_key(isset($manifest['name']) ? $manifest['name'] : basename($template_dir));
        $render = self::resolve_template_file($template_dir, isset($manifest['render']) ? $manifest['render'] : 'views/template.php');
        $style = self::resolve_template_file($template_dir, isset($manifest['style']) ? $manifest['style'] : 'assets/css/style.css');
        $screenshot = isset($manifest['screenshot']) ? $manifest['screenshot'] : 'screenshot.png';
        $screenshot_path = self::resolve_template_file($template_dir, $screenshot);

        if (!$screenshot_path) {
            $screenshot_url = WP_POSTS_CAROUSEL_DIR_URL . 'assets/images/template-placeholder.png';
        } else {
            $screenshot_url = trailingslashit($template_url) . ltrim($screenshot, '/');
        }

        $template = array(
            'status' => true,
            'name' => $name,
            'template_name' => sanitize_text_field(isset($manifest['title']) ? $manifest['title'] : $name),
            'description' => sanitize_text_field(isset($manifest['description']) ? $manifest['description'] : ''),
            'version' => sanitize_text_field(isset($manifest['version']) ? $manifest['version'] : '2.0.0'),
            'author' => sanitize_text_field(isset($manifest['author']) ? $manifest['author'] : ''),
            'author_url' => esc_url_raw(isset($manifest['author_url']) ? $manifest['author_url'] : ''),
            'tags' => isset($manifest['tags']) && is_array($manifest['tags']) ? array_map('sanitize_key', $manifest['tags']) : array(),
            'supports' => isset($manifest['supports']) && is_array($manifest['supports']) ? $manifest['supports'] : array(),
            'fields' => isset($manifest['fields']) && is_array($manifest['fields']) ? $manifest['fields'] : array(),
            'feature' => sanitize_key(isset($manifest['feature']) ? $manifest['feature'] : ''),
            'pro' => !empty($manifest['pro']),
            'dir' => $template_dir,
            'dir_url' => trailingslashit($template_url),
            'manifest' => $manifest_path,
            'render' => $render,
            'style' => $style,
            'style_url' => $style ? trailingslashit($template_url) . ltrim(isset($manifest['style']) ? $manifest['style'] : 'assets/css/style.css', '/') : '',
            'screenshot' => $screenshot_url,
            'compatible' => array(
                'status' => true,
                'error' => null,
            ),
        );

        $template['availability'] = WP_Posts_Carousel_Features::template_status($template);
        $template['locked'] = $template['availability']['locked'];

        return $template;
    }

    private static function resolve_template_file($template_dir, $relative_path)
    {
        $relative_path = ltrim((string) $relative_path, '/');
        $path = trailingslashit($template_dir) . $relative_path;

        return file_exists($path) ? $path : '';
    }

}
