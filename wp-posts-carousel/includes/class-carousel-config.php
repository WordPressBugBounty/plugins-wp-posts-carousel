<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Posts_Carousel_Config
{
    const VERSION = 2;

    public static function defaults()
    {
        return array(
            'version' => self::VERSION,
            'renderer' => 'php',
            'source' => array(
                'mode' => 'query',
                'post_types' => array('post'),
                'taxonomies' => array(),
                'order_by' => 'ID',
                'order' => 'DESC',
                'limit' => 10,
                'exclude' => array(),
            ),
            'curation' => array(
                'mode' => 'query',
                'items' => array(),
                'pattern' => array(),
            ),
            'engine' => array(
                'name' => 'owl-carousel',
                'options' => array(),
            ),
            'template' => array(
                'name' => 'default',
                'display' => array(),
            ),
            'frontend_filters' => array(
                'enabled' => false,
                'taxonomies' => array(),
                'show_all_option' => true,
                'all_label' => 'All',
                'param_prefix' => 'wpcf_',
                'term_limit' => 12,
            ),
            'performance' => array(
                'lazy_images' => true,
                'first_image_eager' => false,
                'image_size' => 'inherit',
            ),
            'field_mapping' => array(
                'eyebrow' => '',
                'kicker' => '',
                'story_step' => '',
                'duration' => '',
                'accent' => '',
                'price_text' => '',
                'button_label' => '',
                'url' => '',
                'image_url' => '',
            ),
            'requirements' => array(
                'required_features' => array(),
            ),
        );
    }

    public static function normalize($input)
    {
        if ($input instanceof WP_REST_Request) {
            $input = $input->get_params();
        }

        if (!is_array($input)) {
            $input = array();
        }

        if (isset($input['version']) && (int) $input['version'] === self::VERSION) {
            return self::sanitize_config(wp_parse_args($input, self::defaults()));
        }

        return self::from_form_values($input);
    }

    public static function from_form_values($values)
    {
        $defaults = self::defaults();
        $post_selection = isset($values['post_selection_options']) && is_array($values['post_selection_options'])
            ? $values['post_selection_options']
            : $values;

        $posts = self::option_list_to_items(self::array_get($post_selection, 'posts', array()));
        $exclude = self::option_list_to_ids(self::array_get($post_selection, 'exclude', array()));
        $post_types = self::option_list_to_values(self::array_get($post_selection, 'post_types', array('post')));

        if (empty($post_types)) {
            $post_types = $defaults['source']['post_types'];
        }

        $config = $defaults;
        $config['renderer'] = sanitize_key(self::array_get($values, 'renderer', 'php'));
        $config['source'] = array(
            'mode' => empty($posts) ? 'query' : 'manual',
            'post_types' => $post_types,
            'taxonomies' => self::array_get($post_selection, 'taxonomies', array()),
            'relation' => strtolower(self::array_get($post_selection, 'relation', 'or')),
            'order_by' => self::normalize_order_by(self::array_get($post_selection, 'order_by', 'ID')),
            'order' => strtoupper(self::array_get($post_selection, 'ordering', 'DESC')),
            'limit' => absint(self::array_get($post_selection, 'all_items', 10)),
            'exclude' => $exclude,
        );
        $config['curation'] = array(
            'mode' => empty($posts) ? 'query' : 'manual',
            'items' => $posts,
            'pattern' => self::sanitize_pattern(self::array_get($values, 'curation_pattern', array())),
        );
        $config['engine'] = array(
            'name' => sanitize_key(self::array_get($values, 'carousel_type', 'owl-carousel')),
            'options' => self::sanitize_engine_options(self::array_get($values, 'carousel_options', array())),
        );
        $config['template'] = array(
            'name' => self::normalize_template_name(self::array_get($values, 'template', 'default')),
            'display' => self::array_get($values, 'display_options', array()),
        );
        $config['frontend_filters'] = self::array_get($values, 'frontend_filters', $defaults['frontend_filters']);
        $config['performance'] = self::array_get($values, 'performance_options', self::array_get($values, 'performance', $defaults['performance']));
        $config['field_mapping'] = self::array_get($values, 'field_mapping', $defaults['field_mapping']);
        $config['requirements'] = self::array_get($values, 'requirements', $defaults['requirements']);

        return self::sanitize_config($config);
    }

    public static function to_form_values($config)
    {
        $config = self::normalize($config);

        return array(
            'post_selection_options' => array(
                'post_types' => self::values_to_options($config['source']['post_types']),
                'taxonomies' => $config['source']['taxonomies'],
                'order_by' => $config['source']['order_by'],
                'ordering' => strtolower($config['source']['order']),
                'posts' => self::items_to_options($config['curation']['items']),
                'exclude' => self::values_to_options($config['source']['exclude']),
                'relation' => isset($config['source']['relation']) ? $config['source']['relation'] : 'or',
                'all_items' => $config['source']['limit'],
            ),
            'renderer' => $config['renderer'],
            'carousel_type' => $config['engine']['name'],
            'template' => $config['template']['name'],
            'carousel_options' => $config['engine']['options'],
            'display_options' => $config['template']['display'],
            'frontend_filters' => $config['frontend_filters'],
            'performance_options' => $config['performance'],
            'field_mapping' => $config['field_mapping'],
            'curation_pattern' => isset($config['curation']['pattern']) ? $config['curation']['pattern'] : array(),
            'requirements' => isset($config['requirements']) ? $config['requirements'] : array('required_features' => array()),
        );
    }

    public static function missing_required_features($config, $context = array())
    {
        $config = self::normalize($config);
        $context = is_array($context) ? $context : array();
        $requirements = isset($config['requirements']) && is_array($config['requirements'])
            ? $config['requirements']
            : array();
        $required_features = isset($requirements['required_features']) && is_array($requirements['required_features'])
            ? $requirements['required_features']
            : array();
        $missing = array();

        foreach ($required_features as $feature) {
            $feature = sanitize_key($feature);

            if (
                $feature
                && !WP_Posts_Carousel_Features::enabled($feature)
                && !WP_Posts_Carousel_Features::license_allows($feature, $context)
            ) {
                $missing[] = $feature;
            }
        }

        return apply_filters('wp_posts_carousel_missing_required_features', array_values(array_unique($missing)), $config, $context);
    }

    public static function requirements_met($config, $context = array())
    {
        return count(self::missing_required_features($config, $context)) === 0;
    }

    private static function sanitize_config($config)
    {
        $defaults = self::defaults();
        $config = wp_parse_args($config, $defaults);
        $config['version'] = self::VERSION;
        $config['renderer'] = sanitize_key(self::array_get($config, 'renderer', 'php'));

        if (!in_array($config['renderer'], self::allowed_renderers($config), true)) {
            $config['renderer'] = 'php';
        }

        foreach (array('source', 'curation', 'engine', 'template', 'frontend_filters', 'performance', 'field_mapping', 'requirements') as $section) {
            if (!isset($config[$section]) || !is_array($config[$section])) {
                $config[$section] = $defaults[$section];
            }
        }

        $config['source']['mode'] = sanitize_key(self::array_get($config['source'], 'mode', 'query'));
        $config['source']['post_types'] = array_values(array_filter(array_map('sanitize_key', (array) self::array_get($config['source'], 'post_types', array('post')))));
        $config['source']['order_by'] = self::normalize_order_by(self::array_get($config['source'], 'order_by', 'ID'));
        $config['source']['order'] = strtoupper(self::array_get($config['source'], 'order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $config['source']['limit'] = max(1, absint(self::array_get($config['source'], 'limit', 10)));
        $config['source']['exclude'] = array_values(array_filter(array_map('absint', (array) self::array_get($config['source'], 'exclude', array()))));

        $config['curation']['mode'] = sanitize_key(self::array_get($config['curation'], 'mode', 'query'));
        $config['curation']['items'] = self::sanitize_items(self::array_get($config['curation'], 'items', array()));
        $config['curation']['pattern'] = self::sanitize_pattern(self::array_get($config['curation'], 'pattern', array()));

        $config['engine']['name'] = sanitize_key(self::array_get($config['engine'], 'name', 'owl-carousel'));
        $config['engine']['options'] = self::sanitize_engine_options(self::array_get($config['engine'], 'options', array()));
        $config['template']['name'] = self::normalize_template_name(self::array_get($config['template'], 'name', 'default'));
        $config['template']['display'] = is_array(self::array_get($config['template'], 'display', array()))
            ? self::array_get($config['template'], 'display', array())
            : array();
        unset($config['style']);
        $config['frontend_filters'] = self::sanitize_frontend_filters($config['frontend_filters']);
        $config['performance'] = self::sanitize_performance_options($config['performance']);
        $config['field_mapping'] = self::sanitize_field_mapping($config['field_mapping']);
        $required_features = self::array_get(
            $config['requirements'],
            'required_features',
            self::array_get($config['requirements'], 'features', array())
        );
        $config['requirements']['required_features'] = self::sanitize_required_features($required_features);

        if ($config['engine']['name'] === 'swiper' && WP_Posts_Carousel_Features::enabled('react_frontend') && in_array('react', self::allowed_renderers($config), true)) {
            $config['renderer'] = 'react';
        }

        return apply_filters('wp_posts_carousel_config', $config);
    }

    private static function allowed_renderers($config)
    {
        $renderers = apply_filters('wp_posts_carousel_frontend_renderers', array('php', 'react'), $config, array());

        return array_values(array_filter(array_map('sanitize_key', (array) $renderers)));
    }

    private static function normalize_template_name($template_name)
    {
        $template_name = sanitize_key($template_name);

        if (class_exists('WP_Posts_Carousel_Templates')) {
            return WP_Posts_Carousel_Templates::normalize_template_name($template_name);
        }

        return $template_name === 'wp-posts-carousel-default-template' ? 'default' : $template_name;
    }

    private static function sanitize_items($items)
    {
        $output = array();

        foreach ((array) $items as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (isset($item['type']) && sanitize_key($item['type']) === 'custom') {
                $output[] = self::sanitize_custom_item($item);
                continue;
            }

            $id = absint(self::array_get($item, 'id', self::array_get($item, 'value', 0)));
            if (!$id) {
                continue;
            }

            $output[] = array(
                'type' => sanitize_key(self::array_get($item, 'type', 'post')),
                'post_type' => sanitize_key(self::array_get($item, 'post_type', self::array_get($item, 'postType', 'post'))),
                'id' => $id,
                'label' => sanitize_text_field(self::array_get($item, 'label', '')),
                'span' => max(1, min(6, absint(self::array_get($item, 'span', self::array_get(self::array_get($item, 'layout', array()), 'span', 1))))),
                'group' => sanitize_text_field(self::array_get($item, 'group', self::array_get(self::array_get($item, 'layout', array()), 'group', ''))),
                'crop_x' => max(-100, min(100, intval(self::array_get($item, 'crop_x', self::array_get(self::array_get($item, 'layout', array()), 'crop_x', 0))))),
                'crop_y' => max(-100, min(100, intval(self::array_get($item, 'crop_y', self::array_get(self::array_get($item, 'layout', array()), 'crop_y', 0))))),
                'strip_index' => max(0, absint(self::array_get($item, 'strip_index', self::array_get(self::array_get($item, 'layout', array()), 'strip_index', 0)))),
                'strip_count' => max(1, absint(self::array_get($item, 'strip_count', self::array_get(self::array_get($item, 'layout', array()), 'strip_count', 1)))),
            );
        }

        return $output;
    }

    private static function sanitize_custom_item($item)
    {
        $id = sanitize_text_field(self::array_get($item, 'id', self::array_get($item, 'value', '')));
        $video_allowed = self::video_media_allowed($item, 'sanitize_custom_item');

        if (!$id) {
            $id = 'custom-' . wp_generate_uuid4();
        }

        $sanitized = array(
            'type' => 'custom',
            'post_type' => sanitize_key(self::array_get($item, 'post_type', self::array_get($item, 'postType', 'custom'))),
            'id' => $id,
            'label' => sanitize_text_field(self::array_get($item, 'label', '')),
            'description' => wp_kses_post(self::array_get($item, 'description', '')),
            'url' => esc_url_raw(self::array_get($item, 'url', '')),
            'image_url' => esc_url_raw(self::array_get($item, 'image_url', self::array_get($item, 'thumbnail', ''))),
            'button_label' => sanitize_text_field(self::array_get($item, 'button_label', '')),
            'media_kind' => $video_allowed ? self::sanitize_media_kind(self::array_get($item, 'media_kind', 'image')) : 'image',
            'video_url' => $video_allowed ? esc_url_raw(self::array_get($item, 'video_url', '')) : '',
            'youtube_url' => $video_allowed ? esc_url_raw(self::array_get($item, 'youtube_url', '')) : '',
            'video_poster' => $video_allowed ? esc_url_raw(self::array_get($item, 'video_poster', '')) : '',
            'video_autoplay' => $video_allowed ? self::sanitize_bool(self::array_get($item, 'video_autoplay', false)) : false,
            'video_start' => $video_allowed ? sanitize_text_field(self::array_get($item, 'video_start', self::array_get($item, 'start_time', ''))) : '',
            'video_loop' => $video_allowed ? self::sanitize_bool(self::array_get($item, 'video_loop', false)) : false,
            'video_controls' => $video_allowed ? self::sanitize_bool(self::array_get($item, 'video_controls', true)) : true,
            'eyebrow' => sanitize_text_field(self::array_get($item, 'eyebrow', '')),
            'kicker' => sanitize_text_field(self::array_get($item, 'kicker', '')),
            'story_step' => sanitize_text_field(self::array_get($item, 'story_step', '')),
            'duration' => sanitize_text_field(self::array_get($item, 'duration', '')),
            'playlist' => $video_allowed ? self::sanitize_custom_playlist(self::array_get($item, 'playlist', array())) : array(),
            'accent' => sanitize_hex_color(self::array_get($item, 'accent', '')),
            'span' => max(1, min(6, absint(self::array_get($item, 'span', self::array_get(self::array_get($item, 'layout', array()), 'span', 1))))),
            'group' => sanitize_text_field(self::array_get($item, 'group', self::array_get(self::array_get($item, 'layout', array()), 'group', ''))),
            'crop_x' => max(-100, min(100, intval(self::array_get($item, 'crop_x', self::array_get(self::array_get($item, 'layout', array()), 'crop_x', 0))))),
            'crop_y' => max(-100, min(100, intval(self::array_get($item, 'crop_y', self::array_get(self::array_get($item, 'layout', array()), 'crop_y', 0))))),
            'strip_index' => max(0, absint(self::array_get($item, 'strip_index', self::array_get(self::array_get($item, 'layout', array()), 'strip_index', 0)))),
            'strip_count' => max(1, absint(self::array_get($item, 'strip_count', self::array_get(self::array_get($item, 'layout', array()), 'strip_count', 1)))),
        );

        return apply_filters('wp_posts_carousel_sanitize_custom_item', $sanitized, $item);
    }

    private static function sanitize_custom_playlist($playlist)
    {
        if (!is_array($playlist)) {
            return array();
        }

        $output = array();

        foreach ($playlist as $item) {
            if (!is_array($item)) {
                continue;
            }
            $video_allowed = self::video_media_allowed($item, 'sanitize_custom_playlist');

            $output[] = array(
                'id' => sanitize_text_field(self::array_get($item, 'id', '')),
                'label' => sanitize_text_field(self::array_get($item, 'label', '')),
                'description' => wp_kses_post(self::array_get($item, 'description', '')),
                'url' => esc_url_raw(self::array_get($item, 'url', '')),
                'image_url' => esc_url_raw(self::array_get($item, 'image_url', self::array_get($item, 'thumbnail', ''))),
                'media_kind' => $video_allowed ? self::sanitize_media_kind(self::array_get($item, 'media_kind', 'image')) : 'image',
                'video_url' => $video_allowed ? esc_url_raw(self::array_get($item, 'video_url', '')) : '',
                'youtube_url' => $video_allowed ? esc_url_raw(self::array_get($item, 'youtube_url', '')) : '',
                'video_poster' => $video_allowed ? esc_url_raw(self::array_get($item, 'video_poster', '')) : '',
                'video_autoplay' => $video_allowed ? self::sanitize_bool(self::array_get($item, 'video_autoplay', false)) : false,
                'video_start' => $video_allowed ? sanitize_text_field(self::array_get($item, 'video_start', self::array_get($item, 'start_time', ''))) : '',
                'video_loop' => $video_allowed ? self::sanitize_bool(self::array_get($item, 'video_loop', false)) : false,
                'video_controls' => $video_allowed ? self::sanitize_bool(self::array_get($item, 'video_controls', true)) : true,
                'eyebrow' => sanitize_text_field(self::array_get($item, 'eyebrow', '')),
                'kicker' => sanitize_text_field(self::array_get($item, 'kicker', '')),
                'story_step' => sanitize_text_field(self::array_get($item, 'story_step', '')),
                'duration' => sanitize_text_field(self::array_get($item, 'duration', '')),
            );
        }

        return $output;
    }

    private static function sanitize_pattern($pattern)
    {
        if (is_string($pattern)) {
            $pattern = array('rhythm' => explode(',', $pattern));
        }

        if (!is_array($pattern)) {
            return array();
        }

        $rhythm = self::array_get($pattern, 'rhythm', array());
        if (is_string($rhythm)) {
            $rhythm = explode(',', $rhythm);
        }

        $rhythm = array_values(array_filter(array_map('sanitize_key', array_map('trim', (array) $rhythm))));
        $preset = sanitize_key(self::array_get($pattern, 'preset', ''));

        return array(
            'rhythm' => $rhythm,
            'preset' => $preset,
        );
    }

    private static function sanitize_required_features($features)
    {
        if (is_string($features)) {
            $features = explode(',', $features);
        }

        return array_values(array_unique(array_filter(array_map('sanitize_key', (array) $features))));
    }

    private static function sanitize_frontend_filters($filters)
    {
        if (!is_array($filters)) {
            $filters = array();
        }

        $prefix = sanitize_key(self::array_get($filters, 'param_prefix', 'wpcf_'));

        return array(
            'enabled' => self::sanitize_bool(self::array_get($filters, 'enabled', false)),
            'taxonomies' => array_values(array_unique(array_filter(array_map('sanitize_key', (array) self::array_get($filters, 'taxonomies', array()))))),
            'show_all_option' => self::sanitize_bool(self::array_get($filters, 'show_all_option', true)),
            'all_label' => sanitize_text_field(self::array_get($filters, 'all_label', 'All')),
            'param_prefix' => $prefix ? $prefix : 'wpcf_',
            'term_limit' => max(1, min(50, absint(self::array_get($filters, 'term_limit', 12)))),
        );
    }

    private static function sanitize_performance_options($performance)
    {
        if (!is_array($performance)) {
            $performance = array();
        }

        $image_size = sanitize_key(self::array_get($performance, 'image_size', 'inherit'));
        $allowed_sizes = array('inherit', 'thumbnail', 'medium', 'medium_large', 'large', 'full');

        return array(
            'lazy_images' => self::sanitize_bool(self::array_get($performance, 'lazy_images', true)),
            'first_image_eager' => self::sanitize_bool(self::array_get($performance, 'first_image_eager', false)),
            'image_size' => in_array($image_size, $allowed_sizes, true) ? $image_size : 'inherit',
        );
    }

    private static function sanitize_field_mapping($mapping)
    {
        $mapping = is_array($mapping) ? $mapping : array();
        $keys = array('eyebrow', 'kicker', 'story_step', 'duration', 'accent', 'price_text', 'button_label', 'url', 'image_url');
        $output = array();

        foreach ($keys as $key) {
            $output[$key] = sanitize_key(self::array_get($mapping, $key, ''));
        }

        return $output;
    }

    private static function sanitize_engine_options($options)
    {
        if (!is_array($options)) {
            return array();
        }

        $output = array();

        foreach ($options as $key => $value) {
            $key = is_numeric($key) ? absint($key) : sanitize_key($key);

            if ($key === 'items_to_show' || $key === 'breakpoints') {
                $output[$key] = self::sanitize_items_to_show($value);
                continue;
            }

            if ($key === 'nav_icon_library') {
                $value = sanitize_key($value);
                $libraries = class_exists('WP_Posts_Carousel_Icons')
                    ? array_keys(WP_Posts_Carousel_Icons::libraries())
                    : array('fontawesome');
                $output[$key] = $value && in_array($value, $libraries, true) ? $value : '';
                continue;
            }

            if (in_array($key, array('nav_icon_previous', 'nav_icon_next', 'nav_icon_up', 'nav_icon_down'), true)) {
                $output[$key] = class_exists('WP_Posts_Carousel_Icons')
                    ? WP_Posts_Carousel_Icons::sanitize_icon_name($value)
                    : sanitize_text_field($value);
                continue;
            }

            if (is_array($value)) {
                $output[$key] = self::sanitize_engine_options($value);
            } elseif (is_bool($value)) {
                $output[$key] = $value;
            } elseif (is_numeric($value)) {
                $output[$key] = $value + 0;
            } else {
                $output[$key] = sanitize_text_field($value);
            }
        }

        return $output;
    }

    private static function sanitize_items_to_show($items)
    {
        if (is_string($items)) {
            $normalized = array();
            foreach (explode(',', $items) as $point) {
                if (preg_match('/(\d+):(\d+)/', $point, $matches)) {
                    $normalized[absint($matches[1])] = max(1, absint($matches[2]));
                }
            }

            return $normalized;
        }

        if (!is_array($items)) {
            return array();
        }

        $normalized = array();

        foreach ($items as $width => $value) {
            $width = absint($width);

            if (!$width) {
                continue;
            }

            $item_count = is_array($value) && isset($value['items']) ? $value['items'] : $value;
            $normalized[$width] = max(1, absint($item_count));
        }

        ksort($normalized);

        return $normalized;
    }

    private static function option_list_to_values($options)
    {
        if (!is_array($options)) {
            return array($options);
        }

        $values = array();
        foreach ($options as $option) {
            if (is_array($option) && isset($option['value'])) {
                $values[] = $option['value'];
            } elseif (is_object($option) && isset($option->value)) {
                $values[] = $option->value;
            } else {
                $values[] = $option;
            }
        }

        return array_values(array_filter(array_map('sanitize_key', $values)));
    }

    private static function option_list_to_ids($options)
    {
        return array_values(array_filter(array_map('absint', self::option_list_to_values($options))));
    }

    private static function option_list_to_items($options)
    {
        $items = array();

        foreach ((array) $options as $option) {
            if (is_array($option)) {
                if (isset($option['type']) && sanitize_key($option['type']) === 'custom') {
                    $items[] = self::sanitize_custom_item($option);
                    continue;
                }

                $items[] = array(
                    'type' => 'post',
                    'post_type' => self::array_get($option, 'post_type', self::array_get($option, 'postType', 'post')),
                    'id' => self::array_get($option, 'value', 0),
                    'label' => self::array_get($option, 'label', ''),
                    'span' => self::array_get($option, 'span', 1),
                    'group' => self::array_get($option, 'group', ''),
                    'crop_x' => self::array_get($option, 'crop_x', 0),
                    'crop_y' => self::array_get($option, 'crop_y', 0),
                    'strip_index' => self::array_get($option, 'strip_index', 0),
                    'strip_count' => self::array_get($option, 'strip_count', 1),
                );
            }
        }

        return self::sanitize_items($items);
    }

    private static function values_to_options($values)
    {
        $options = array();

        foreach ((array) $values as $value) {
            $options[] = array(
                'value' => (string) $value,
                'label' => (string) $value,
            );
        }

        return $options;
    }

    private static function items_to_options($items)
    {
        $options = array();

        foreach ((array) $items as $item) {
            $video_allowed = self::video_media_allowed($item, 'item_form_option');
            $option = array(
                'value' => (string) self::array_get($item, 'id', ''),
                'label' => self::array_get($item, 'label', ''),
                'type' => self::array_get($item, 'type', 'post'),
                'post_type' => self::array_get($item, 'post_type', 'post'),
                'postType' => self::array_get($item, 'post_type', 'post'),
                'description' => self::array_get($item, 'description', ''),
                'url' => self::array_get($item, 'url', ''),
                'image_url' => self::array_get($item, 'image_url', ''),
                'button_label' => self::array_get($item, 'button_label', ''),
                'media_kind' => $video_allowed ? self::array_get($item, 'media_kind', 'image') : 'image',
                'video_url' => $video_allowed ? self::array_get($item, 'video_url', '') : '',
                'youtube_url' => $video_allowed ? self::array_get($item, 'youtube_url', '') : '',
                'video_poster' => $video_allowed ? self::array_get($item, 'video_poster', '') : '',
                'video_autoplay' => $video_allowed ? self::array_get($item, 'video_autoplay', false) : false,
                'video_start' => $video_allowed ? self::array_get($item, 'video_start', '') : '',
                'video_loop' => $video_allowed ? self::array_get($item, 'video_loop', false) : false,
                'video_controls' => $video_allowed ? self::array_get($item, 'video_controls', true) : true,
                'eyebrow' => self::array_get($item, 'eyebrow', ''),
                'kicker' => self::array_get($item, 'kicker', ''),
                'story_step' => self::array_get($item, 'story_step', ''),
                'duration' => self::array_get($item, 'duration', ''),
                'playlist' => $video_allowed ? self::array_get($item, 'playlist', array()) : array(),
                'accent' => self::array_get($item, 'accent', ''),
                'span' => self::array_get($item, 'span', 1),
                'group' => self::array_get($item, 'group', ''),
                'crop_x' => self::array_get($item, 'crop_x', 0),
                'crop_y' => self::array_get($item, 'crop_y', 0),
                'strip_index' => self::array_get($item, 'strip_index', 0),
                'strip_count' => self::array_get($item, 'strip_count', 1),
                'thumbnail' => self::array_get($item, 'image_url', self::post_thumbnail_url(self::array_get($item, 'id', 0))),
                'thumbnail_alt' => self::array_get($item, 'label', self::post_thumbnail_alt(self::array_get($item, 'id', 0))),
            );

            $options[] = apply_filters('wp_posts_carousel_item_form_option', $option, $item);
        }

        return $options;
    }

    private static function sanitize_media_kind($media_kind)
    {
        $media_kind = sanitize_key($media_kind);

        return in_array($media_kind, array('image', 'video', 'youtube'), true) ? $media_kind : 'image';
    }

    private static function video_media_allowed($item, $context)
    {
        return (bool) apply_filters(
            'wp_posts_carousel_video_media_allowed',
            false,
            is_array($item) ? $item : array(),
            array('context' => $context)
        );
    }

    private static function sanitize_bool($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), array('1', 'true', 'yes', 'on'), true);
    }

    private static function post_thumbnail_url($post_id)
    {
        $post_id = absint($post_id);

        return $post_id ? (get_the_post_thumbnail_url($post_id, 'thumbnail') ?: '') : '';
    }

    private static function post_thumbnail_alt($post_id)
    {
        $thumbnail_id = $post_id ? get_post_thumbnail_id($post_id) : 0;

        return $thumbnail_id ? get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) : '';
    }

    private static function normalize_order_by($order_by)
    {
        $map = array(
            'id' => 'ID',
            'posts_order' => 'post__in',
            'post_title' => 'post_title',
            'post_date' => 'post_date',
            'post_modified' => 'post_modified',
        );

        $map = apply_filters('wp_posts_carousel_order_by_map', $map);
        $order_by = sanitize_key($order_by);

        return isset($map[$order_by]) ? $map[$order_by] : 'ID';
    }

    private static function array_get($array, $key, $default = null)
    {
        if (is_array($array) && array_key_exists($key, $array)) {
            return $array[$key];
        }

        return $default;
    }
}
