<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Posts_Carousel_Repository
{
    public static function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'wp_posts_carousel';
    }

    public static function all()
    {
        global $wpdb;

        $posts_table = $wpdb->posts;
        $users_table = $wpdb->users;
        $table = self::table_name();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.*, wpc.params, wpc.post_modified AS carousel_modified, u.user_nicename
                FROM %i p
                INNER JOIN %i wpc ON p.ID = wpc.post_id
                LEFT JOIN %i u ON p.post_author = u.ID
                WHERE p.post_type = 'wp-posts-carousel' AND p.post_status NOT IN ('trash', 'auto-draft', 'inherit')
                ORDER BY p.ID DESC",
                $posts_table,
                $table,
                $users_table
            ),
            ARRAY_A
        );
    }

    public static function get($post_id)
    {
        global $wpdb;

        $post_id = absint($post_id);
        if (!$post_id) {
            return null;
        }

        $posts_table = $wpdb->posts;
        $users_table = $wpdb->users;
        $table = self::table_name();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT p.*, wpc.params, wpc.post_modified AS carousel_modified, u.user_nicename
                FROM %i p
                INNER JOIN %i wpc ON p.ID = wpc.post_id
                LEFT JOIN %i u ON p.post_author = u.ID
                WHERE p.ID = %d AND p.post_type = 'wp-posts-carousel'
                LIMIT 1",
                $posts_table,
                $table,
                $users_table,
                $post_id
            ),
            ARRAY_A
        );
    }

    public static function get_config($post_id)
    {
        $carousel = self::get($post_id);

        if (!$carousel || empty($carousel['params'])) {
            return null;
        }

        $params = json_decode($carousel['params'], true);
        if (!is_array($params)) {
            return null;
        }

        return WP_Posts_Carousel_Config::normalize($params);
    }

    public static function create($title, $params)
    {
        $status = self::status_from_params($params, 'publish');
        $post_id = wp_insert_post(
            array(
                'post_author' => get_current_user_id(),
                'post_title' => sanitize_text_field($title),
                'post_name' => sanitize_title($title),
                'post_status' => $status,
                'post_type' => 'wp-posts-carousel',
            ),
            true
        );

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        $saved = self::save_params($post_id, $params, true);

        if (is_wp_error($saved)) {
            wp_delete_post($post_id, true);
            return $saved;
        }

        return get_post($post_id);
    }

    public static function update($post_id, $title, $params)
    {
        $post_id = absint($post_id);
        $existing = get_post($post_id);

        if (!$existing || $existing->post_type !== 'wp-posts-carousel') {
            return new WP_Error('wp_posts_carousel_not_found', __('Carousel not found', 'wp-posts-carousel'), array('status' => 404));
        }

        $updated = wp_update_post(
            array(
                'ID' => $post_id,
                'post_title' => sanitize_text_field($title),
                'post_name' => sanitize_title($title),
                'post_status' => self::status_from_params($params, $existing->post_status),
            ),
            true
        );

        if (is_wp_error($updated)) {
            return $updated;
        }

        $saved = self::save_params($post_id, $params, false);

        if (is_wp_error($saved)) {
            return $saved;
        }

        return get_post($post_id);
    }

    public static function delete($post_id)
    {
        global $wpdb;

        $post_id = absint($post_id);

        if (!wp_delete_post($post_id, true)) {
            return new WP_Error('wp_posts_carousel_delete_failed', __('Failed delete carousel', 'wp-posts-carousel'), array('status' => 500));
        }

        $wpdb->delete(self::table_name(), array('post_id' => $post_id), array('%d'));

        return true;
    }

    public static function duplicate($post_id)
    {
        $carousel = self::get($post_id);

        if (!$carousel || empty($carousel['params'])) {
            return new WP_Error('wp_posts_carousel_not_found', __('Carousel not found', 'wp-posts-carousel'), array('status' => 404));
        }

        $config = json_decode($carousel['params'], true);

        if (!is_array($config)) {
            return new WP_Error('wp_posts_carousel_invalid_params', __('Carousel params are invalid', 'wp-posts-carousel'), array('status' => 500));
        }

        $config = WP_Posts_Carousel_Config::normalize($config);
        $config['status'] = 'draft';

        return self::create(
            /* translators: %s: original carousel title. */
            sprintf(__('%s copy', 'wp-posts-carousel'), $carousel['post_title']),
            $config
        );
    }

    private static function save_params($post_id, $params, $insert = false)
    {
        global $wpdb;

        $config = WP_Posts_Carousel_Config::normalize($params);
        $data = array(
            'params' => wp_json_encode($config),
            'post_modified' => current_time('mysql'),
        );
        $formats = array('%s', '%s');

        if ($insert) {
            $data['post_id'] = absint($post_id);
            $formats[] = '%d';

            $result = $wpdb->insert(self::table_name(), $data, $formats);
        } else {
            $exists = (int) $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT COUNT(*) FROM %i WHERE post_id = %d',
                    self::table_name(),
                    absint($post_id)
                )
            );

            if ($exists > 0) {
                $result = $wpdb->update(self::table_name(), $data, array('post_id' => absint($post_id)), $formats, array('%d'));
            } else {
                $data['post_id'] = absint($post_id);
                $formats[] = '%d';
                $result = $wpdb->insert(self::table_name(), $data, $formats);
            }
        }

        if ($result === false) {
            return new WP_Error('wp_posts_carousel_params_save_failed', __('Failed save carousel params', 'wp-posts-carousel'), array('status' => 500));
        }

        return true;
    }

    private static function status_from_params($params, $default = 'publish')
    {
        if ($params instanceof WP_REST_Request) {
            $params = $params->get_params();
        }

        if (!is_array($params)) {
            return $default;
        }

        $status = isset($params['status']) ? $params['status'] : (isset($params['post_status']) ? $params['post_status'] : $default);
        $status = sanitize_key($status);

        return in_array($status, array('draft', 'publish'), true) ? $status : $default;
    }
}
