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
 * WP_Posts_Carousel_Template class
 */
class WP_Posts_Carousel_Template
{

    public $template_details = array();

    /**
     * constructor
     */
    public function __construct()
    {
    }

    /**
     * set template details from file
     */
    public function set_template_details($path)
    {
        if (!$path || !file_exists($path)) {
            return;
        }

        $template = array();
        $screenshot_file = 'screenshot.png';
        $template_headers = array(
            'template_name'                       => 'Plugin Name',
            'template_url'                        => 'Plugin URI',
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

        $template = get_file_data($path, $template_headers);

        if (count($template) > 0) {
            $template['dir']        = dirname($path);
            $tmp_template_name_array = explode('/', $template['dir']);

            $template['name']       = end($tmp_template_name_array);
            $template['dir_url']    = plugin_dir_url($path);
            $template['compatible'] = $this->check_template($template);

            if (file_exists($template['dir'] . '/' . $screenshot_file)) {
                $template['screenshot'] = $template['dir_url'] . $screenshot_file;
            } else {
                $template['screenshot'] = WP_POSTS_CAROUSEL_DIR_URL . 'assets/images/template-placeholder.png';
            }
        }

        // $template_details[$template['name']] = $template;
        return $template;
    }

    /**
     * check compatibility with plugin version
     */
    public function check_template($template_details)
    {
        // $output = array(
        //     'status' => null,
        //     'error'  => null,
        // );
        $required_version = trim((string) $template_details['requires_wp_posts_carousel_at_least']);

        if ($required_version && version_compare(WP_POSTS_CAROUSEL_VERSION, $required_version, '<')) {
            return array(
                'status' => false,
                /* translators: %s: carousel template name. */
                'error'  => sprintf(__("This template '%s' is not compatible with the current version of this plugin", 'wp-posts-carousel'), $template_details['template_name']),
            );
        }

        return array(
            'status' => true,
            'error'  => null
        );
    }
}
