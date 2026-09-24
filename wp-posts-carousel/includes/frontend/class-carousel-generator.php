<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Posts_Carousel_Generator
{
    private $carousel_id = null;
    private $carousel_params = array();
    private $params = array();
    private $template = array();
    private $slide = array();
    private $post = null;
    private $post_url = '';
    private $image = array('', 0, 0);
    private $featured_image = '';
    private $title = '';
    private $description = '';
    private $post_description = '';
    private $tags = '';
    private $tags_list = array();
    private $created_date = '';
    private $author = '';
    private $categories = '';
    private $category = '';
    private $categories_list = array();
    private $buttons = '';

    public function generate($atts, $index)
    {
        $atts = shortcode_atts(array('id' => 0, 'renderer' => '', 'lazy' => false), $atts, 'wp_posts_carousel');
        $post_id = absint($atts['id']);

        if (!$post_id) {
            return '';
        }

        $carousel = WP_Posts_Carousel_Repository::get($post_id);
        if (!$carousel || ($carousel['post_status'] !== 'publish' && !current_user_can('edit_wp_posts_carousel', $post_id))) {
            return '';
        }

        $config = WP_Posts_Carousel_Repository::get_config($post_id);
        if (!$config) {
            return '';
        }

        if (!WP_Posts_Carousel_Config::requirements_met($config, array('context' => 'frontend'))) {
            return '';
        }

        return $this->render_config($config, $post_id, $atts, $index);
    }

    public function generate_from_config($config, $args = array())
    {
        if (!is_array($config)) {
            return '';
        }

        $config = WP_Posts_Carousel_Config::normalize($config);

        if (!WP_Posts_Carousel_Config::requirements_met($config, array('context' => 'frontend'))) {
            return '';
        }

        $args = wp_parse_args(
            $args,
            array(
                'id' => 0,
                'index' => 0,
                'renderer' => '',
                'instance_id' => '',
                'force_editor_renderer' => false,
                'lazy' => false,
            )
        );

        $atts = array(
            'id' => absint($args['id']),
            'renderer' => sanitize_key($args['renderer']),
            'instance_id' => sanitize_html_class($args['instance_id']),
            'force_editor_renderer' => ! empty($args['force_editor_renderer']),
            'lazy' => ! empty($args['lazy']),
        );

        return $this->render_config($config, absint($args['id']), $atts, absint($args['index']));
    }

    private function render_config($config, $post_id, $atts, $index)
    {
        $instance_id = isset($atts['instance_id']) ? sanitize_html_class($atts['instance_id']) : '';
        $this->carousel_id = $instance_id ? $instance_id : $this->generate_id($post_id, $index);
        $this->carousel_params = WP_Posts_Carousel_Data::runtime_params($config, $this->carousel_id);
        $this->params = $this->legacy_params($this->carousel_params);
        $this->template = WP_Posts_Carousel_Data::template($config);
        $extended_config = apply_filters('wp_posts_carousel_runtime_config', $config, $this->template, $this->carousel_id, $this);
        if (is_array($extended_config) && $extended_config !== $config) {
            $config = $extended_config;
            $this->carousel_params = WP_Posts_Carousel_Data::runtime_params($config, $this->carousel_id);
            $this->params = $this->legacy_params($this->carousel_params);
        }
        $renderer = $this->resolve_renderer($atts, $config);
        $react_mode = $this->resolve_react_mode($renderer, $atts, $config);
        $lazy_render = $this->truthy(isset($atts['lazy']) ? $atts['lazy'] : false);

        if (!$this->template) {
            return '';
        }

        if (isset($this->template['status']) && $this->template['status'] === false) {
            return '<div class="cci-wpc-error">' . esc_html($this->template['error']) . '</div>';
        }

        $slides = array();
        $has_merged_slides = false;

        if (!$lazy_render) {
            $slides = WP_Posts_Carousel_Data::slides($config, $this->carousel_params);

            if (count($slides) < 1) {
                return '';
            }

            $has_merged_slides = $this->has_merged_slides($slides);
        }

        $this->carousel_params['post_count'] = count($slides);
        $this->carousel_params['has_merged_slides'] = $has_merged_slides;
        $this->params = $this->legacy_params($this->carousel_params);
        $engine_name = isset($config['engine']['name']) ? sanitize_key($config['engine']['name']) : 'owl-carousel';
        $engine_class = $engine_name === 'owl-carousel'
            ? 'owl-carousel'
            : 'cci-wpc-engine-' . sanitize_html_class($engine_name);

        $api_url = $post_id ? rest_url('wp-posts-carousel/v1/carousel/' . $post_id) : '';
        $config_version = isset($config['version']) ? $config['version'] : '2.0.0';
        $template_name = sanitize_html_class($this->template['name']);
        $template_classes = trim('cci-wpc-carousel cci-wpc-' . $template_name . '-template-theme');
        $template_classes = trim($template_classes . ($has_merged_slides ? ' cci-wpc-carousel-has-merged-slides' : ''));
        $filter_prefix = !empty($config['frontend_filters']['param_prefix'])
            ? sanitize_key($config['frontend_filters']['param_prefix'])
            : 'wpcf_';
        $demo_key = sanitize_key((string) get_post_meta($post_id, '_wp_posts_carousel_demo_key', true));
        $this->carousel_params['demo_key'] = $demo_key;
        $this->carousel_params['wpc_effective_slide_by'] = $this->effective_slide_by($this->carousel_params);
        $this->carousel_params['nav_prev_html'] = $this->carousel_nav_html($this->carousel_params, 'previous');
        $this->carousel_params['nav_next_html'] = $this->carousel_nav_html($this->carousel_params, 'next');
		$template_classes = trim($template_classes . ($demo_key ? ' cci-wpc-demo-' . $demo_key : ''));
        $demo_key_attribute = $demo_key ? ' data-wpc-demo-key="' . esc_attr($demo_key) . '"' : '';
        $root_style_rules = array(
            '--wpc-carousel-gap:' . absint(isset($this->carousel_params['margin']) ? $this->carousel_params['margin'] : 0) . 'px;',
        );

        if ($renderer === 'react' && in_array($react_mode, array('full', 'render'), true)) {
            $root_style_rules[] = 'min-height:360px;';
            $root_style_rules[] = 'overflow:hidden;';
            $root_style_rules[] = 'visibility:hidden;';
        } elseif ($lazy_render) {
            $root_style_rules[] = 'display:block;';
            $root_style_rules[] = 'min-height:360px;';
            $root_style_rules[] = 'overflow:hidden;';
        }

        $root_style = ' style="' . esc_attr(implode('', $root_style_rules)) . '"';
        $filterbar = $this->frontend_filters_html($config, $post_id);
        $lazy_render_attribute = $lazy_render ? ' data-wpc-lazy-render="true" aria-busy="true"' : '';
        $out = $filterbar . '<div id="' . esc_attr($this->carousel_id) . '" class="' . esc_attr($template_classes) . ' ' . esc_attr($engine_class) . '" data-wpc-id="' . esc_attr($post_id) . '"' . $demo_key_attribute . $root_style . $lazy_render_attribute . ' data-wpc-renderer="' . esc_attr($renderer) . '" data-wpc-react-mode="' . esc_attr($react_mode) . '" data-wpc-api="' . esc_url($api_url) . '" data-wpc-engine="' . esc_attr($engine_name) . '" data-wpc-template="' . esc_attr($this->template['name']) . '" data-wpc-filter-prefix="' . esc_attr($filter_prefix) . '" data-wpc-config-version="' . esc_attr($config_version) . '" data-wpc-prev-label="' . esc_attr__('previous', 'wp-posts-carousel') . '" data-wpc-next-label="' . esc_attr__('next', 'wp-posts-carousel') . '">';

        if ($lazy_render || ($renderer === 'react' && in_array($react_mode, array('full', 'render'), true))) {
            return $out . '</div>';
        }

        foreach ($slides as $slide_index => $slide) {
            $this->prepare_slide($slide, $config, $slide_index);
            $slide = $this->slide;
            $out .= $this->render_slide_template();
        }

        $out .= '</div>';

        if ($engine_name === 'owl-carousel') {
            $out .= $this->carousel($this->carousel_params);
        }

        return apply_filters('wp_posts_carousel_rendered', $out, $config, $post_id);
    }

    private function generate_id($post_id, $index)
    {
        return 'wp-posts-carousel_' . absint($post_id) . '-' . absint($index);
    }

    private function render_slide_template()
    {
        if (empty($this->template['render']) || !file_exists($this->template['render'])) {
            return '';
        }

        ob_start();
        $included = include $this->template['render'];
        $output = ob_get_clean();

        if (is_string($included)) {
            $output .= $included;
        }

        return $output;
    }

    private function resolve_renderer($atts, $config)
    {
        $renderer = isset($atts['renderer']) ? sanitize_key($atts['renderer']) : '';
        $renderer_explicit = $renderer !== '';
        $force_editor = $this->is_editor_render_request($atts);

        if (!$renderer && isset($config['renderer'])) {
            $renderer = sanitize_key($config['renderer']);
        }

        $engine_name = isset($config['engine']['name']) ? sanitize_key($config['engine']['name']) : 'owl-carousel';
        $renderer = apply_filters('wp_posts_carousel_frontend_renderer', $renderer ? $renderer : 'php', $config, $atts);
        $renderer = sanitize_key($renderer);

        if (
            $force_editor &&
            ! $renderer_explicit &&
            WP_Posts_Carousel_Features::enabled('react_frontend')
        ) {
            $renderer = 'react';
        }

        if ($renderer === 'react' && !WP_Posts_Carousel_Features::enabled('react_frontend')) {
            return 'php';
        }

        if (
            ! $force_editor &&
            'swiper' === $engine_name &&
            WP_Posts_Carousel_Features::enabled('react_frontend')
        ) {
            $renderer = 'react';
        }

        $renderers = apply_filters('wp_posts_carousel_frontend_renderers', array('php', 'react'), $config, $atts);
        $renderers = array_map('sanitize_key', array_filter((array) $renderers));

        return in_array($renderer, $renderers, true) ? $renderer : 'php';
    }

    private function resolve_react_mode($renderer, $atts, $config)
    {
        $engine = isset($config['engine']['name']) ? sanitize_key($config['engine']['name']) : 'owl-carousel';
        $force_editor = $this->is_editor_render_request($atts);
        $mode = $renderer === 'react' && ( $force_editor || 'swiper' === $engine )
            ? 'full'
            : 'enhance';
        $mode = apply_filters('wp_posts_carousel_react_render_mode', $mode, $renderer, $config, $atts);
        $mode = sanitize_key($mode);

        return in_array($mode, array('enhance', 'full', 'render'), true) ? $mode : 'enhance';
    }

    private function is_editor_render_request($atts = array())
    {
        if (!empty($atts['force_editor_renderer'])) {
            return true;
        }

        if (isset($_REQUEST['context']) && is_scalar($_REQUEST['context']) && sanitize_key(wp_unslash((string) $_REQUEST['context'])) === 'edit') {
            return true;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        if (
            strpos($request_uri, '/wp/v2/block-renderer/') !== false
            || (isset($_REQUEST['rest_route']) && is_scalar($_REQUEST['rest_route']) && strpos(sanitize_text_field(wp_unslash((string) $_REQUEST['rest_route'])), '/wp/v2/block-renderer/') !== false)
        ) {
            return true;
        }

        $editor_request_args = array(
            'elementor-preview',
            'elementor-editor',
            'elementor-preview-nonce',
            'action',
        );
        foreach ($editor_request_args as $arg_name) {
            if (!isset($_REQUEST[$arg_name]) || !is_scalar($_REQUEST[$arg_name])) {
                continue;
            }

            $arg_value = sanitize_text_field(wp_unslash((string) $_REQUEST[$arg_name]));
            if ($arg_name === 'action' && strpos($arg_value, 'elementor') !== false) {
                return true;
            }

            if ($arg_name !== 'action' && $arg_value !== '') {
                return true;
            }
        }

        if (
            function_exists('wp_is_serving_rest_request') &&
            wp_is_serving_rest_request() &&
            isset($_REQUEST['context']) && is_scalar($_REQUEST['context']) &&
            sanitize_key(wp_unslash((string) $_REQUEST['context'])) === 'edit'
        ) {
            return true;
        }

        if (
            (function_exists('is_customize_preview') && is_customize_preview()) ||
            (function_exists('is_preview') && is_preview())
        ) {
            return true;
        }

        if (
            isset($_SERVER['HTTP_REFERER']) && is_string($_SERVER['HTTP_REFERER']) &&
            strpos(esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])), home_url('/wp-admin/')) === 0
        ) {
            return true;
        }

        return false;
    }

    private function prepare_slide($slide, $config, $slide_index = 0)
    {
        $this->slide = is_array($slide) ? $slide : WP_Posts_Carousel_Slide_View_Model::from_post($slide, $this->carousel_params, $config);
        $this->slide['_tracking_id'] = $this->slide_tracking_id($this->slide, $slide_index);
        $this->slide = $this->apply_slide_image_priority($this->slide, $config, $slide_index);
        $this->map_slide_to_legacy_properties($this->slide);

        do_action('wp_posts_carousel_slide_prepared', $this->slide, $this->post, $config, $this);
    }

    private function map_slide_to_legacy_properties($slide)
    {
        $this->post = isset($slide['post']) ? $slide['post'] : null;
        $this->post_url = isset($slide['url']) ? $slide['url'] : '';
        $this->image = array(
            isset($slide['image']['src']) ? $slide['image']['src'] : '',
            isset($slide['image']['width']) ? $slide['image']['width'] : 0,
            isset($slide['image']['height']) ? $slide['image']['height'] : 0,
        );
        $this->featured_image = isset($slide['html']['featured_image']) ? $slide['html']['featured_image'] : '';
        $this->title = isset($slide['html']['title']) ? $slide['html']['title'] : '';
        $this->description = isset($slide['html']['description']) ? $slide['html']['description'] : '';
        $this->post_description = $this->description;
        $this->created_date = isset($slide['html']['created_date']) ? $slide['html']['created_date'] : '';
        $this->author = isset($slide['html']['author']) ? $slide['html']['author'] : '';
        $this->categories = isset($slide['html']['categories']) ? $slide['html']['categories'] : '';
        $this->category = $this->categories;
        $this->categories_list = isset($slide['meta']['categories']) ? $slide['meta']['categories'] : array();
        $this->tags = isset($slide['html']['tags']) ? $slide['html']['tags'] : '';
        $this->tags_list = isset($slide['meta']['tags']) ? $slide['meta']['tags'] : array();
        $this->buttons = isset($slide['html']['buttons']) ? $slide['html']['buttons'] : '';
    }

    private function apply_slide_image_priority($slide, $config, $slide_index)
    {
        if (
            !is_array($slide) ||
            $slide_index !== 0 ||
            empty($config['performance']['first_image_eager']) ||
            empty($slide['html']) ||
            !is_array($slide['html'])
        ) {
            return $slide;
        }

        foreach (array('featured_image', 'woocommerce_card', 'post_type_card') as $key) {
            if (!empty($slide['html'][$key])) {
                $slide['html'][$key] = $this->promote_first_image_html($slide['html'][$key]);
            }
        }

        return $slide;
    }

    private function promote_first_image_html($html)
    {
        $html = preg_replace('/\sloading=(["\'])lazy\1/i', '', (string) $html, 1);

        if (strpos($html, 'loading=') === false) {
            $html = preg_replace('/<img\b/i', '<img loading="eager"', $html, 1);
        }

        if (strpos($html, 'fetchpriority=') === false) {
            $html = preg_replace('/<img\b/i', '<img fetchpriority="high"', $html, 1);
        }

        return $html;
    }

    private function frontend_filters_html($config, $post_id)
    {
        $filters = isset($config['frontend_filters']) && is_array($config['frontend_filters'])
            ? $config['frontend_filters']
            : array();

        if (empty($filters['enabled']) || !class_exists('WP_Posts_Carousel_Query_Builder')) {
            return '';
        }

        $taxonomies = WP_Posts_Carousel_Query_Builder::frontend_filter_taxonomies($config);

        if (empty($taxonomies)) {
            return '';
        }

        $prefix = isset($filters['param_prefix']) ? sanitize_key($filters['param_prefix']) : 'wpcf_';
        $prefix = $prefix ? $prefix : 'wpcf_';
        $term_limit = isset($filters['term_limit']) ? max(1, min(50, absint($filters['term_limit']))) : 12;
        $show_all = !array_key_exists('show_all_option', $filters) || $this->truthy($filters['show_all_option']);
        $all_label = !empty($filters['all_label']) ? sanitize_text_field($filters['all_label']) : __('All', 'wp-posts-carousel');
        $groups = array();

        foreach ($taxonomies as $taxonomy_name) {
            if (!taxonomy_exists($taxonomy_name)) {
                continue;
            }

            $taxonomy = get_taxonomy($taxonomy_name);
            $terms = get_terms(
                array(
                    'taxonomy' => $taxonomy_name,
                    'hide_empty' => true,
                    'number' => $term_limit,
                )
            );

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            $param = $prefix . $taxonomy_name;
            $active_terms = $this->active_filter_terms($param);
            $label = !empty($taxonomy->labels->singular_name) ? $taxonomy->labels->singular_name : $taxonomy_name;
            $items = '';

            if ($show_all) {
                $items .= '<a class="cci-wpc-filterbar-option' . (empty($active_terms) ? ' cci-wpc-active' : '') . '" href="' . esc_url($this->filter_url($param, 0)) . '" data-wpc-filter-param="' . esc_attr($param) . '" data-wpc-filter-value=""' . (empty($active_terms) ? ' aria-current="true"' : '') . '>' . esc_html($all_label) . '</a>';
            }

            foreach ($terms as $term) {
                $active = in_array((int) $term->term_id, $active_terms, true);
                $items .= '<a class="cci-wpc-filterbar-option' . ($active ? ' cci-wpc-active' : '') . '" href="' . esc_url($this->filter_url($param, $term->term_id)) . '" data-wpc-filter-param="' . esc_attr($param) . '" data-wpc-filter-value="' . esc_attr($term->term_id) . '"' . ($active ? ' aria-current="true"' : '') . '>' . esc_html($term->name) . '</a>';
            }

            $groups[] = '<div class="cci-wpc-filterbar-group"><span class="cci-wpc-filterbar-label">' . esc_html($label) . '</span><div class="cci-wpc-filterbar-options">' . $items . '</div></div>';
        }

        if (empty($groups)) {
            return '';
        }

        $html = '<nav class="cci-wpc-filterbar" data-wpc-filterbar-for="' . esc_attr($this->carousel_id) . '" data-wpc-id="' . esc_attr($post_id) . '" aria-label="' . esc_attr__('Carousel filters', 'wp-posts-carousel') . '">' . implode('', $groups) . '</nav>';

        return apply_filters('wp_posts_carousel_frontend_filterbar_html', $html, $config, $post_id, $this->carousel_id);
    }

    private function active_filter_terms($param)
    {
        if (!isset($_GET[$param])) {
            return array();
        }

        $raw = map_deep(wp_unslash($_GET[$param]), 'sanitize_text_field');
        $values = is_array($raw) ? $raw : explode(',', (string) $raw);
        $terms = array();

        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $term_id = absint($value);

            if ($term_id) {
                $terms[] = $term_id;
            }
        }

        return array_values(array_unique($terms));
    }

    private function filter_url($param, $term_id)
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $url = home_url($request_uri ?: '/');

        if (!$term_id) {
            return remove_query_arg($param, $url);
        }

        return add_query_arg($param, absint($term_id), $url);
    }

    private function legacy_params($params)
    {
        $legacy = $params;
        $legacy['show_category'] = isset($legacy['show_categories']) ? $legacy['show_categories'] : true;

        foreach ($legacy as $key => $value) {
            if (is_bool($value)) {
                $legacy[$key] = $value ? 'true' : 'false';
            }
        }

        return $legacy;
    }

    private function has_merged_slides($slides)
    {
        foreach ($slides as $slide) {
            $layout = isset($slide['layout']) && is_array($slide['layout']) ? $slide['layout'] : array();
            $span = isset($layout['span']) ? absint($layout['span']) : 1;

            if ($span > 1) {
                return true;
            }
        }

        return false;
    }

    private function slide_layout_classes()
    {
        $layout = isset($this->slide['layout']) && is_array($this->slide['layout']) ? $this->slide['layout'] : array();
        $span = isset($layout['span']) ? max(1, min(6, absint($layout['span']))) : 1;
        $group = isset($layout['group']) ? sanitize_html_class($layout['group']) : '';
        $media_kind = isset($this->slide['media']['kind']) ? sanitize_html_class($this->slide['media']['kind']) : '';

        $classes = array(
            'cci-wpc-layout-span-' . $span,
        );

        if ($group !== '') {
            $classes[] = 'cci-wpc-layout-group-' . $group;
        }

        if ($media_kind !== '') {
            $classes[] = 'cci-wpc-media-' . $media_kind;
        }

        return implode(' ', $classes);
    }

    private function slide_layout_attributes()
    {
        $layout = isset($this->slide['layout']) && is_array($this->slide['layout']) ? $this->slide['layout'] : array();
        $tracking_id = isset($this->slide['_tracking_id']) ? sanitize_text_field($this->slide['_tracking_id']) : '';
        $span = isset($layout['span']) ? max(1, min(6, absint($layout['span']))) : 1;
        $group = isset($layout['group']) ? sanitize_html_class($layout['group']) : '';
        $crop_x = isset($layout['crop_x']) ? max(-100, min(100, intval($layout['crop_x']))) : 0;
        $crop_y = isset($layout['crop_y']) ? max(-100, min(100, intval($layout['crop_y']))) : 0;
        $position_x = max(0, min(100, 50 + ($crop_x / 2)));
        $position_y = max(0, min(100, 50 + ($crop_y / 2)));
        $strip_offset = -1 * ($position_x * 0.8);
        $strip_index = isset($layout['strip_index']) ? max(0, absint($layout['strip_index'])) : 0;
        $strip_count = isset($layout['strip_count']) ? max(1, absint($layout['strip_count'])) : 1;
        $strip_left = -100 * $strip_index;
        $strip_position = $strip_count > 1 ? max(0, min(100, ($strip_index / ($strip_count - 1)) * 100)) : 50;
        $strip_size = max(1, $strip_count) * 100;
        $strip_image = isset($this->slide['image']['src']) ? esc_url_raw($this->slide['image']['src']) : '';
        $style = '--wpc-crop-x:' . $position_x . '%;--wpc-crop-y:' . $position_y . '%;--wpc-strip-offset:' . $strip_offset . '%;--wpc-strip-index:' . $strip_index . ';--wpc-strip-count:' . $strip_count . ';--wpc-strip-size:' . $strip_size . '%;--wpc-strip-left:' . $strip_left . '%;--wpc-strip-position:' . $strip_position . '%;';

        if ($strip_image !== '') {
            $style .= '--wpc-strip-image:url("' . esc_url($strip_image) . '");';
        }

        if (!empty($this->slide['display']['accent'])) {
            $accent = sanitize_hex_color($this->slide['display']['accent']);
            if ($accent) {
                $style .= '--wpc-slide-accent:' . $accent . ';';
            }
        }

        return sprintf(
            ' data-merge="%d" data-wpc-slide-id="%s" data-wpc-layout-span="%d" data-wpc-layout-group="%s" data-wpc-crop-x="%d" data-wpc-crop-y="%d" data-wpc-strip-index="%d" data-wpc-strip-count="%d" style="%s"',
            $span,
            esc_attr($tracking_id),
            $span,
            esc_attr($group),
            $crop_x,
            $crop_y,
            $strip_index,
            $strip_count,
            esc_attr($style)
        );
    }

    private function slide_tracking_id($slide, $slide_index)
    {
        $type = isset($slide['type']) ? sanitize_key($slide['type']) : 'slide';
        $source = isset($slide['source']) && is_array($slide['source']) ? $slide['source'] : array();
        $source_kind = isset($source['kind']) ? sanitize_key($source['kind']) : '';
        $source_id = isset($source['id']) ? sanitize_text_field((string) $source['id']) : '';
        $slide_id = isset($slide['id']) ? sanitize_text_field((string) $slide['id']) : '';

        if ($source_kind !== '' && $source_id !== '') {
            $raw = $source_kind . '-' . $source_id;
        } elseif ($slide_id !== '') {
            $raw = $type . '-' . $slide_id;
        } else {
            $raw = 'slide-' . (absint($slide_index) + 1);
        }

        $tracking_id = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $raw);
        $tracking_id = trim((string) $tracking_id, '-_');

        return substr($tracking_id !== '' ? $tracking_id : 'slide-' . (absint($slide_index) + 1), 0, 64);
    }

    private function slide_display_value($key)
    {
        return !empty($this->slide['display'][$key]) ? sanitize_text_field($this->slide['display'][$key]) : '';
    }

    private function slide_media_kind()
    {
        $media = isset($this->slide['media']) && is_array($this->slide['media']) ? $this->slide['media'] : array();
        $kind = isset($media['kind']) ? sanitize_key($media['kind']) : 'image';
        $provider = isset($media['video_provider']) ? sanitize_key($media['video_provider']) : '';
        $video_type = isset($media['video_type']) ? sanitize_key($media['video_type']) : '';

        if (
            !empty($media['youtube_url']) ||
            (
                (!empty($media['embed_url']) || !empty($media['video_embed_url'])) &&
                ($video_type === 'iframe' || in_array($provider, array('youtube', 'vimeo'), true))
            )
        ) {
            return 'youtube';
        }

        if (!empty($media['video_url']) || (!empty($media['video_embed_url']) && $video_type === 'video')) {
            return 'video';
        }

        return in_array($kind, array('image', 'video', 'youtube'), true) ? $kind : 'image';
    }

    private function slide_media_html()
    {
        $media = isset($this->slide['media']) && is_array($this->slide['media']) ? $this->slide['media'] : array();
        $kind = $this->slide_media_kind();

        if (in_array($kind, array('video', 'youtube'), true)) {
            $html = apply_filters(
                'wp_posts_carousel_video_media_html',
                '',
                $media,
                $this->slide,
                $this->featured_image,
                $kind
            );

            if (is_string($html) && trim($html) !== '') {
                return $html;
            }
        }

        return $this->featured_image;
    }

    private function carousel($params)
    {
        $mouse_wheel = '';
        $transition_effect = isset($params['transition_effect']) ? sanitize_key($params['transition_effect']) : 'slide';
        $animate_in = isset($params['animate_in']) ? sanitize_html_class($params['animate_in']) : '';
        $animate_out = isset($params['animate_out']) ? sanitize_html_class($params['animate_out']) : '';
        $nav_prev_html = !empty($params['nav_prev_html']) ? $params['nav_prev_html'] : $this->carousel_nav_html($params, 'previous');
        $nav_next_html = !empty($params['nav_next_html']) ? $params['nav_next_html'] : $this->carousel_nav_html($params, 'next');

        if ($transition_effect === 'fade' && $animate_out === '') {
            $animate_out = 'fadeOut';
        }

        if ($this->truthy($params['mouse_wheel'])) {
            $mouse_wheel = 'carousel.on("mousewheel", ".owl-stage", function(e) {
                if (e.deltaY > 0) {
                    carousel.trigger("next.owl");
                } else {
                    carousel.trigger("prev.owl");
                }
                e.preventDefault();
            });';
        }

        return '<script type="text/javascript">
            if (typeof jQuery !== "undefined") {
                jQuery(function($) {
                    var carousel = $("#' . esc_js($params['id']) . '");
                    if (!carousel.length || !carousel[0]) {
                        return;
                    }
                    if (typeof $.fn.owlCarousel !== "function") {
                        carousel.attr("data-wpc-engine-state", "missing-owl");
                        carousel.trigger("wp-posts-carousel:engine-missing", ["owl-carousel"]);
                        return;
                    }
                    var wpcCarouselInViewport = !("IntersectionObserver" in window);
                    function wpcAutoplaySlideIsActive(element) {
                        var item = $(element).closest(".owl-item");
                        var mediaItem = $(element).closest("[data-wpc-video-media-index]");

                        if (mediaItem.length && !mediaItem.hasClass("cci-wpc-active")) {
                            return false;
                        }

                        return !item.length || item.hasClass("active");
                    }
                    function wpcPlayAutoplayVideos() {
                        var canPlay = wpcCarouselInViewport && document.visibilityState !== "hidden" && carousel.hasClass("owl-loaded");

                        carousel.find("iframe[data-wpc-autoplay-frame=\"1\"]").each(function() {
                            var frame = this;
                            var shouldPlay = canPlay && wpcAutoplaySlideIsActive(frame);
                            var src = frame.getAttribute("data-wpc-src") || "";

                            if (!src) {
                                return;
                            }

                            if (shouldPlay) {
                                if (frame.getAttribute("src") !== src) {
                                    frame.setAttribute("src", src);
                                }
                                return;
                            }

                            if (frame.getAttribute("src") && frame.getAttribute("src") !== "about:blank") {
                                frame.setAttribute("src", "about:blank");
                            }
                        });
                        carousel.find("video[data-wpc-autoplay-video=\"1\"], video[autoplay]").each(function() {
                            var video = this;
                            var shouldPlay = canPlay && wpcAutoplaySlideIsActive(video);

                            video.muted = true;
                            video.playsInline = true;

                            if (!shouldPlay) {
                                video.pause();
                                return;
                            }

                            var playPromise = video.play();
                            if (playPromise && typeof playPromise.catch === "function") {
                                playPromise.catch(function() {});
                            }
                        });
                    }
                    if ("IntersectionObserver" in window) {
                        var autoplayObserver = new IntersectionObserver(function(entries) {
                            wpcCarouselInViewport = entries.some(function(entry) {
                                return entry.isIntersecting && entry.intersectionRatio > 0;
                            });
                            wpcPlayAutoplayVideos();
                        }, {
                            rootMargin: "0px 0px -8% 0px",
                            threshold: [0, 0.2]
                        });

                        autoplayObserver.observe(carousel[0]);
                    }
                    document.addEventListener("visibilitychange", wpcPlayAutoplayVideos);
                    carousel.on("initialized.owl.carousel translated.owl.carousel refreshed.owl.carousel", function() {
                        window.setTimeout(wpcPlayAutoplayVideos, 60);
                    });
                    carousel.owlCarousel({
                        rtl: ' . $this->js_bool($params['rtl']) . ',
                        loop: ' . ($params['post_count'] > 1 ? $this->js_bool($params['loop']) : 'false') . ',
                        rewind: ' . ($params['post_count'] > 1 ? $this->js_bool($params['rewind']) : 'false') . ',
                        center: ' . $this->js_bool($params['center']) . ',
                        nav: ' . $this->js_bool($params['nav']) . ',
                        smartSpeed: ' . absint($params['nav_speed']) . ',
                        navSpeed: ' . absint($params['nav_speed']) . ',
                        dots: ' . $this->js_bool($params['dots']) . ',
                        dotsSpeed: ' . absint($params['dots_speed']) . ',
                        lazyLoad: ' . $this->js_bool($params['lazy_load']) . ',
                        lazyLoadEager: ' . absint($params['lazy_load_eager']) . ',
                        autoplay: ' . $this->js_bool($params['auto_play']) . ',
                        autoplayHoverPause: ' . $this->js_bool($params['stop_on_hover']) . ',
                        autoplayTimeout: ' . absint($params['auto_play_timeout']) . ',
                        autoplaySpeed: ' . absint($params['auto_play_speed']) . ',
                        stagePadding: ' . absint($params['stage_padding']) . ',
                        margin: ' . (!empty($params['has_merged_slides']) ? 0 : absint($params['margin'])) . ',
                        merge: ' . $this->js_bool(!empty($params['has_merged_slides'])) . ',
                        mergeFit: true,
                        mouseDrag: ' . $this->js_bool($params['mouse_drag']) . ',
                        touchDrag: ' . $this->js_bool($params['touch_drag']) . ',
                        pullDrag: ' . $this->js_bool($params['pull_drag']) . ',
                        freeDrag: ' . $this->js_bool($params['free_drag']) . ',
                        startPosition: ' . absint($params['start_position']) . ',
                        dragEndSpeed: ' . absint($params['nav_speed']) . ',
                        slideBy: ' . absint($params['wpc_effective_slide_by']) . ',
                        fallbackEasing: "' . esc_js($params['fallback_easing']) . '",
                        animateIn: "' . esc_js($animate_in) . '",
                        animateOut: "' . esc_js($animate_out) . '",
                        responsiveClass: true,
                        navElement: "button",
                        navText: [ ' . wp_json_encode($nav_prev_html) . ', ' . wp_json_encode($nav_next_html) . ' ],
                        responsive: {
                            0: { items: 1 }' . WP_Posts_Carousel_Utils::parse_breakpoints($params) . '
                        },
                        autoWidth: ' . $this->js_bool($params['auto_width']) . ',
                        autoHeight: ' . $this->js_bool($params['auto_height']) . '
                    });
                    window.setTimeout(wpcPlayAutoplayVideos, 120);
                    ' . $mouse_wheel . '
                });
            }
        </script>';
    }

    private function carousel_nav_html($params, $direction)
    {
        $is_vertical = isset($params['direction']) && sanitize_key($params['direction']) === 'vertical';
        $icon_direction = $direction === 'previous'
            ? ($is_vertical ? 'up' : 'previous')
            : ($is_vertical ? 'down' : 'next');
        $label = $direction === 'previous'
            ? __('previous', 'wp-posts-carousel')
            : __('next', 'wp-posts-carousel');
        $icon_html = class_exists('WP_Posts_Carousel_Icons')
            ? WP_Posts_Carousel_Icons::navigation_icon_html($icon_direction, $params)
            : '<i class="fa fa-' . esc_attr($direction === 'previous' ? ($is_vertical ? 'angle-up' : 'angle-left') : ($is_vertical ? 'angle-down' : 'angle-right')) . '" aria-hidden="true"></i>';

        return $icon_html . '<span class="screen-reader-text">' . esc_html($label) . '</span>';
    }

    private function truthy($value)
    {
        return $value === true || $value === 'true' || $value === 1 || $value === '1';
    }

    private function js_bool($value)
    {
        return $this->truthy($value) ? 'true' : 'false';
    }

    /**
     * Return a safer navigation step for carousels whose visual story is grouped by page.
     *
     * @param array $params Carousel params.
     * @return int
     */
    private function effective_slide_by($params)
    {
        $slide_by = isset($params['slide_by']) ? absint($params['slide_by']) : 1;
        return max(1, $slide_by);
    }
}
