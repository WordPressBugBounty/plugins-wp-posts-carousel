<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Posts_Carousel_Data
{
    public static function get($post_id, $args = array())
    {
        $post_id = absint($post_id);
        $args = wp_parse_args(
            $args,
            array(
                'context' => 'view',
                'instance_id' => 'wp-posts-carousel_' . $post_id . '-api',
            )
        );

        $carousel = WP_Posts_Carousel_Repository::get($post_id);
        if (!$carousel || ($args['context'] === 'view' && $carousel['post_status'] !== 'publish')) {
            return null;
        }

        $config = WP_Posts_Carousel_Repository::get_config($post_id);
        if (!$config) {
            return null;
        }

        if (!WP_Posts_Carousel_Config::requirements_met($config, array('context' => $args['context'] === 'view' ? 'frontend' : 'admin'))) {
            return null;
        }

        $template = self::template($config);
        if (!$template) {
            return null;
        }
        $config = self::apply_template_runtime_overrides($config, $template);

        $runtime_params = self::runtime_params($config, $args['instance_id']);
        $slides = array_map(
            array('WP_Posts_Carousel_Slide_View_Model', 'public_data'),
            self::slides($config, $runtime_params)
        );

        $runtime_params['post_count'] = count($slides);

        $data = array(
            'id' => $post_id,
            'title' => $carousel['post_title'],
            'status' => $carousel['post_status'],
            'config' => $config,
            'engine' => array(
                'name' => $config['engine']['name'],
                'options' => $runtime_params,
            ),
            'template' => self::public_template($template),
            'slides' => $slides,
            'features' => WP_Posts_Carousel_Features::public_data(),
        );

        return apply_filters('wp_posts_carousel_public_carousel_data', $data, $post_id, $config);
    }

    public static function from_config($config, $args = array())
    {
        $config = WP_Posts_Carousel_Config::normalize($config);
        $args = wp_parse_args(
            $args,
            array(
                'id' => 0,
                'title' => __('Carousel preview', 'wp-posts-carousel'),
                'status' => 'draft',
                'context' => 'edit',
                'instance_id' => 'wp-posts-carousel-admin-preview',
            )
        );

        if (!WP_Posts_Carousel_Config::requirements_met($config, array('context' => $args['context'] === 'view' ? 'frontend' : 'admin'))) {
            return null;
        }

        $template = self::template($config);
        if (!$template) {
            return null;
        }
        $config = self::apply_template_runtime_overrides($config, $template);

        $runtime_params = self::runtime_params($config, $args['instance_id']);
        $slides = array_map(
            array('WP_Posts_Carousel_Slide_View_Model', 'public_data'),
            self::slides($config, $runtime_params)
        );

        $runtime_params['post_count'] = count($slides);

        $data = array(
            'id' => absint($args['id']),
            'title' => sanitize_text_field($args['title']),
            'status' => sanitize_key($args['status']),
            'config' => $config,
            'engine' => array(
                'name' => $config['engine']['name'],
                'options' => $runtime_params,
            ),
            'template' => self::public_template($template),
            'slides' => $slides,
            'features' => WP_Posts_Carousel_Features::public_data(),
            'context' => sanitize_key($args['context']),
        );

        return apply_filters('wp_posts_carousel_preview_data', $data, $config, $args);
    }

    public static function slides($config, $runtime_params)
    {
        $post_slides = self::post_slides($config, $runtime_params);
        $items = isset($config['curation']['items']) && is_array($config['curation']['items'])
            ? $config['curation']['items']
            : array();

        if (empty($items)) {
            return array_values($post_slides);
        }

        $slides = array();
        $used_post_ids = array();

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (isset($item['type']) && $item['type'] === 'custom') {
                $slides[] = WP_Posts_Carousel_Slide_View_Model::from_custom_item($item, $runtime_params, $config);
                continue;
            }

            $post_id = isset($item['id']) ? absint($item['id']) : 0;
            if ($post_id && isset($post_slides[$post_id])) {
                $slides[] = $post_slides[$post_id];
                $used_post_ids[] = $post_id;
            }
        }

        foreach ($post_slides as $post_id => $slide) {
            if (!in_array((int) $post_id, $used_post_ids, true)) {
                $slides[] = $slide;
            }
        }

        return array_values(array_filter($slides));
    }

    private static function apply_template_runtime_overrides($config, $template)
    {
        return apply_filters('wp_posts_carousel_template_runtime_config', $config, $template);
    }

    private static function post_slides($config, $runtime_params)
    {
        $items = isset($config['curation']['items']) && is_array($config['curation']['items'])
            ? $config['curation']['items']
            : array();
        $has_manual_items = !empty($items);
        $manual_post_ids = array();

        foreach ($items as $item) {
            if (is_array($item) && (!isset($item['type']) || $item['type'] !== 'custom') && !empty($item['id'])) {
                $manual_post_ids[] = absint($item['id']);
            }
        }

        if ($has_manual_items && empty(array_filter($manual_post_ids))) {
            return array();
        }

        $query = new WP_Query(WP_Posts_Carousel_Query_Builder::build($config));
        $slides = array();

        while ($query->have_posts()) {
            $query->the_post();
            $slide = WP_Posts_Carousel_Slide_View_Model::from_post(get_post(), $runtime_params, $config);
            $slides[(int) get_the_ID()] = $slide;
        }

        wp_reset_postdata();

        return $slides;
    }

    public static function runtime_params($config, $instance_id = '')
    {
        $display = isset($config['template']['display']) ? $config['template']['display'] : array();
        $post_display = isset($display['post']) ? $display['post'] : array();
        $options = isset($config['engine']['options']) ? $config['engine']['options'] : array();
        $source = isset($config['source']) ? $config['source'] : array();
        $style = isset($config['style']) && is_array($config['style']) ? $config['style'] : array();
        $performance = isset($config['performance']) && is_array($config['performance']) ? $config['performance'] : array();

        $params = array_merge(
            array(
                'id' => $instance_id,
                'all_items' => isset($source['limit']) ? $source['limit'] : 10,
                'show_title' => true,
                'title_length' => 0,
                'show_created_date' => true,
                'show_description' => 'excerpt',
                'description_length' => 0,
                'allow_shortcodes' => false,
                'show_categories' => true,
                'show_tags' => false,
                'show_more_button' => true,
                'show_featured_image' => true,
                'featured_image_source' => 'thumbnail',
                'featured_image_width' => 100,
                'featured_image_width_unit' => '%',
                'featured_image_height' => 100,
                'featured_image_height_unit' => '%',
                'rtl' => false,
                'loop' => true,
                'rewind' => false,
                'center' => false,
                'auto_play' => true,
                'autoplay_indicator' => 'none',
                'stop_on_hover' => true,
                'auto_play_timeout' => 1200,
                'auto_play_speed' => 800,
                'nav' => true,
                'nav_speed' => 800,
                'dots' => true,
                'dots_speed' => 800,
                'stage_padding' => 0,
                'margin' => 5,
                'lazy_load' => false,
                'lazy_load_eager' => 0,
                'mouse_drag' => true,
                'mouse_wheel' => false,
                'touch_drag' => true,
                'pull_drag' => true,
                'free_drag' => false,
                'slide_by' => 1,
                'start_position' => 0,
                'direction' => 'horizontal',
                'pagination_type' => 'bullets',
                'keyboard' => false,
                'grab_cursor' => false,
                'mousewheel_release_on_edges' => false,
                'mousewheel_force_to_axis' => false,
                'mousewheel_sensitivity' => 1,
                'touch_ratio' => 1,
                'watch_overflow' => true,
                'transition_effect' => 'slide',
                'fallback_easing' => 'linear',
                'animate_in' => '',
                'animate_out' => '',
                'auto_width' => false,
                'auto_height' => true,
                'nav_icon_library' => '',
                'nav_icon_previous' => '',
                'nav_icon_next' => '',
                'nav_icon_up' => '',
                'nav_icon_down' => '',
                'breakpoints' => array(),
            ),
            $post_display,
            $options
        );

        $params['id'] = $instance_id;
        $params['lazy_images'] = array_key_exists('lazy_images', $performance) ? (bool) $performance['lazy_images'] : true;
        $params['first_image_eager'] = !empty($performance['first_image_eager']);

        if (!empty($performance['image_size']) && $performance['image_size'] !== 'inherit') {
            $params['featured_image_source'] = sanitize_key($performance['image_size']);
        }

        $params['show_category'] = isset($params['show_categories']) ? $params['show_categories'] : true;
        $params['navigation_icons'] = class_exists('WP_Posts_Carousel_Icons')
            ? WP_Posts_Carousel_Icons::navigation_icons($params, $config)
            : array();

        return apply_filters('wp_posts_carousel_runtime_params', $params, $config);
    }

    public static function template($config)
    {
        $template_name = isset($config['template']['name']) ? $config['template']['name'] : 'default';
        $template = WP_Posts_Carousel_Templates::find_template($template_name);

        if (!empty($template['status']) && empty($template['locked'])) {
            return $template;
        }

        if ($template_name !== 'default') {
            $fallback = WP_Posts_Carousel_Templates::find_template('default');

            if (!empty($fallback['status']) && empty($fallback['locked'])) {
                $fallback['fallback_for'] = sanitize_key($template_name);
                $fallback['fallback_message'] = !empty($template['error'])
                    ? sanitize_text_field($template['error'])
                    : __('Requested carousel template is unavailable. Rendering with the default template.', 'wp-posts-carousel');

                return apply_filters('wp_posts_carousel_template_fallback', $fallback, $template, $config);
            }
        }

        return null;
    }

    private static function public_template($template)
    {
        return array(
            'name' => $template['name'],
            'title' => $template['template_name'],
            'description' => $template['description'],
            'version' => $template['version'],
            'supports' => $template['supports'],
            'fields' => $template['fields'],
            'pro' => $template['pro'],
            'locked' => $template['locked'],
            'availability' => $template['availability'],
        );
    }
}
