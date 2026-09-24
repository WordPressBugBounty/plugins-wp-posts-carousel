<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Posts_Carousel_Query_Builder
{
    public static function build($config)
    {
        $source = isset($config['source']) ? $config['source'] : array();
        $curation = isset($config['curation']) ? $config['curation'] : array();
        $manual_ids = self::manual_ids($curation);

        $args = array(
            'post_type' => isset($source['post_types']) ? (array) $source['post_types'] : array('post'),
            'post_status' => 'publish',
            'posts_per_page' => isset($source['limit']) ? absint($source['limit']) : 10,
            'orderby' => isset($source['order_by']) ? $source['order_by'] : 'ID',
            'order' => isset($source['order']) ? $source['order'] : 'DESC',
            'post__not_in' => isset($source['exclude']) ? array_map('absint', (array) $source['exclude']) : array(),
            'no_found_rows' => true,
        );

        if (!empty($manual_ids)) {
            $args['post__in'] = $manual_ids;
            $args['orderby'] = 'post__in';
            $args['posts_per_page'] = count($manual_ids);
            $manual_post_types = self::manual_post_types($curation);

            if (!empty($manual_post_types)) {
                $args['post_type'] = $manual_post_types;
            }
        }

        $tax_query = self::tax_query($source);
        $frontend_tax_query = self::frontend_tax_query($config);

        if (!empty($frontend_tax_query)) {
            $tax_query = empty($tax_query)
                ? array('relation' => 'AND')
                : array(
                    'relation' => 'AND',
                    $tax_query,
                );

            foreach ($frontend_tax_query as $clause) {
                $tax_query[] = $clause;
            }
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        return apply_filters('wp_posts_carousel_query_args', $args, $config);
    }

    private static function manual_ids($curation)
    {
        $ids = array();

        if (!isset($curation['items']) || !is_array($curation['items'])) {
            return $ids;
        }

        foreach ($curation['items'] as $item) {
            if (isset($item['id'])) {
                $ids[] = absint($item['id']);
            }
        }

        return array_values(array_filter($ids));
    }

    private static function manual_post_types($curation)
    {
        $post_types = array();

        if (!isset($curation['items']) || !is_array($curation['items'])) {
            return $post_types;
        }

        foreach ($curation['items'] as $item) {
            if (!is_array($item) || (isset($item['type']) && $item['type'] === 'custom')) {
                continue;
            }

            $post_type = isset($item['post_type']) ? $item['post_type'] : (isset($item['postType']) ? $item['postType'] : '');
            $post_type = sanitize_key($post_type);

            if ($post_type) {
                $post_types[] = $post_type;
            }
        }

        return array_values(array_unique($post_types));
    }

    private static function tax_query($source)
    {
        if (empty($source['taxonomies']) || !is_array($source['taxonomies'])) {
            return array();
        }

        $tax_query = array(
            'relation' => isset($source['relation']) && strtolower($source['relation']) === 'and' ? 'AND' : 'OR',
        );

        foreach ($source['taxonomies'] as $post_type_group) {
            if (empty($post_type_group['taxonomies']) || !is_array($post_type_group['taxonomies'])) {
                continue;
            }

            foreach ($post_type_group['taxonomies'] as $taxonomy) {
                if (empty($taxonomy['name']) || empty($taxonomy['terms']) || !is_array($taxonomy['terms'])) {
                    continue;
                }

                $terms = array();
                foreach ($taxonomy['terms'] as $term) {
                    if (is_array($term) && isset($term['value'])) {
                        $terms[] = absint($term['value']);
                    }
                }

                if (empty($terms)) {
                    continue;
                }

                $tax_query[] = array(
                    'taxonomy' => sanitize_key($taxonomy['name']),
                    'field' => 'term_id',
                    'terms' => $terms,
                    'operator' => isset($taxonomy['operator']) && strtolower($taxonomy['operator']) === 'not in' ? 'NOT IN' : 'IN',
                );
            }
        }

        return $tax_query;
    }

    private static function frontend_tax_query($config)
    {
        $filters = isset($config['frontend_filters']) && is_array($config['frontend_filters'])
            ? $config['frontend_filters']
            : array();

        if (empty($filters['enabled'])) {
            return array();
        }

        $prefix = isset($filters['param_prefix']) ? sanitize_key($filters['param_prefix']) : 'wpcf_';
        $prefix = $prefix ? $prefix : 'wpcf_';
        $allowed_taxonomies = self::frontend_filter_taxonomies($config);
        $tax_query = array();

        foreach ($allowed_taxonomies as $taxonomy) {
            $param = $prefix . $taxonomy;
            $terms = self::request_term_ids($param);

            if (empty($terms)) {
                continue;
            }

            $tax_query[] = array(
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => $terms,
                'operator' => 'IN',
            );
        }

        return $tax_query;
    }

    public static function frontend_filter_taxonomies($config)
    {
        $filters = isset($config['frontend_filters']) && is_array($config['frontend_filters'])
            ? $config['frontend_filters']
            : array();
        $configured = isset($filters['taxonomies']) ? array_values(array_filter(array_map('sanitize_key', (array) $filters['taxonomies']))) : array();

        if (!empty($configured)) {
            return array_values(array_filter($configured, 'taxonomy_exists'));
        }

        $source = isset($config['source']) && is_array($config['source']) ? $config['source'] : array();
        $from_query = array();

        if (!empty($source['taxonomies']) && is_array($source['taxonomies'])) {
            foreach ($source['taxonomies'] as $group) {
                if (empty($group['taxonomies']) || !is_array($group['taxonomies'])) {
                    continue;
                }

                foreach ($group['taxonomies'] as $taxonomy) {
                    if (!empty($taxonomy['name'])) {
                        $from_query[] = sanitize_key($taxonomy['name']);
                    }
                }
            }
        }

        if (!empty($from_query)) {
            return array_values(array_filter(array_unique($from_query), 'taxonomy_exists'));
        }

        $post_types = !empty($source['post_types']) ? (array) $source['post_types'] : array('post');
        $taxonomies = array();

        foreach ($post_types as $post_type) {
            foreach ((array) get_object_taxonomies(sanitize_key($post_type), 'objects') as $taxonomy) {
                if (!empty($taxonomy->public) && !empty($taxonomy->query_var)) {
                    $taxonomies[] = $taxonomy->name;
                }
            }
        }

        return array_values(array_unique(array_filter(array_map('sanitize_key', $taxonomies))));
    }

    private static function request_term_ids($param)
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
}
