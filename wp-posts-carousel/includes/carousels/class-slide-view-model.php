<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Posts_Carousel_Slide_View_Model
{
    public static function from_post($post, $params, $config = array())
    {
        $post = get_post($post);
        if (!$post) {
            return array();
        }

        $post_type = get_post_type($post);
        $display_params = self::display_params_for_post_type($post_type, $post, $params, $config);
        $taxonomies = self::detect_taxonomies($post_type);
        $image = self::featured_image($post, $display_params);
        $mapped_image_url = self::mapped_field($post, $config, 'image_url');

        if ($mapped_image_url) {
            $image['src'] = esc_url_raw($mapped_image_url);
            $image['srcset'] = '';
            $image['sizes'] = '';
        }

        $categories = self::terms($post->ID, $taxonomies['category']);
        $tags = self::terms($post->ID, $taxonomies['tag']);
        $description = self::description($post, $display_params);
        $title_text = self::limited_field_text(get_the_title($post), $display_params, 'title_length');
        $url = self::mapped_field($post, $config, 'url') ?: get_permalink($post->ID);
        $button_label = self::mapped_field($post, $config, 'button_label');

        if ($button_label) {
            $display_params['button_label'] = $button_label;
        }

        $layout = self::layout($post->ID, $config);
        $display = self::post_display($post, $config, $display_params);

        $slide = array(
            'id' => (int) $post->ID,
            'type' => 'post',
            'post_type' => $post_type,
            'source' => array(
                'kind' => 'wp_post',
                'id' => (int) $post->ID,
            ),
            'post' => $post,
            'url' => $url,
            'title' => array(
                'text' => $title_text,
                'html' => self::title_html($title_text, $url, $display_params),
            ),
            'image' => $image,
            'description' => $description,
            'display' => array(
                'eyebrow' => isset($display['eyebrow']) ? $display['eyebrow'] : '',
                'kicker' => isset($display['kicker']) ? $display['kicker'] : '',
                'story_step' => isset($display['story_step']) ? $display['story_step'] : '',
                'duration' => isset($display['duration']) ? $display['duration'] : '',
                'accent' => isset($display['accent']) ? $display['accent'] : '',
            ),
            'commerce' => array(
                'price_html' => isset($display['price_html']) ? $display['price_html'] : '',
                'price_text' => isset($display['price_text']) ? $display['price_text'] : '',
                'on_sale' => !empty($display['on_sale']),
            ),
            'meta' => array(
                'created_date' => get_the_date('', $post),
                'author' => get_the_author_meta('display_name', $post->post_author),
                'author_url' => get_author_posts_url($post->post_author),
                'categories' => $categories,
                'tags' => $tags,
            ),
            'actions' => array(
                'buttons_html' => self::buttons_html($post, $url, $display_params),
            ),
            'layout' => array(
                'span' => $layout['span'],
                'group' => $layout['group'],
                'crop_x' => $layout['crop_x'],
                'crop_y' => $layout['crop_y'],
                'strip_index' => $layout['strip_index'],
                'strip_count' => $layout['strip_count'],
            ),
            'html' => array(),
        );

        $card_context = array(
            'description' => $description,
            'categories' => $categories,
            'tags' => $tags,
            'created_date' => $slide['meta']['created_date'],
            'author' => $slide['meta']['author'],
            'url' => $url,
            'image' => $image,
        );
        $post_type_card = self::post_type_card_html($post, $display_params, $config, $card_context);

        $slide['html'] = array(
            'featured_image' => self::featured_image_html($slide, $display_params),
            'woocommerce_card' => $post_type_card,
            'post_type_card' => $post_type_card,
            'title' => $slide['title']['html'],
            'description' => $slide['description']['html'],
            'created_date' => $slide['meta']['created_date'],
            'author' => $slide['meta']['author'],
            'categories' => self::truthy(isset($display_params['show_categories']) ? $display_params['show_categories'] : true) ? self::terms_html($categories) : '',
            'tags' => self::truthy(isset($display_params['show_tags']) ? $display_params['show_tags'] : false) ? self::terms_html($tags) : '',
            'buttons' => $slide['actions']['buttons_html'],
        );

        $slide = apply_filters('wp_posts_carousel_slide_view_model', $slide, $post, $params, $config);
        $slide['html'] = apply_filters('wp_posts_carousel_slide_html_parts', $slide['html'], $slide, $params, $config);

        return $slide;
    }

    public static function from_custom_item($item, $params, $config = array())
    {
        if (!is_array($item)) {
            return array();
        }

        $id = isset($item['id']) ? sanitize_text_field($item['id']) : '';
        $item_params = self::custom_item_display_params($item, $params);
        $title = self::limited_field_text(isset($item['label']) ? sanitize_text_field($item['label']) : '', $item_params, 'title_length');
        $raw_description = isset($item['description']) ? wp_kses_post($item['description']) : '';
        $description_limit = self::field_limit($item_params, 'description_length');
        $description_text = self::limited_text($raw_description, $description_limit);
        $description = $description_limit > 0 ? esc_html($description_text) : $raw_description;
        $url = isset($item['url']) ? esc_url_raw($item['url']) : '';
        $image_url = isset($item['image_url']) ? esc_url_raw($item['image_url']) : '';
        $image_url = self::demo_custom_item_image_url($id, $image_url);
        $button_label = isset($item['button_label']) ? sanitize_text_field($item['button_label']) : __('read more', 'wp-posts-carousel');
        $video_url = isset($item['video_url']) ? esc_url_raw($item['video_url']) : '';
        $youtube_url = isset($item['youtube_url']) ? esc_url_raw($item['youtube_url']) : '';
        $media_kind = isset($item['media_kind']) ? sanitize_key($item['media_kind']) : 'image';
        $media_kind = in_array($media_kind, array('image', 'video', 'youtube'), true) ? $media_kind : 'image';
        $video_allowed = self::video_media_allowed($item, 'custom_slide');

        if (!$video_allowed) {
            $video_url = '';
            $youtube_url = '';
            $media_kind = 'image';
        }

        if ($youtube_url) {
            $media_kind = 'youtube';
        } elseif ($video_url && $media_kind === 'image') {
            $media_kind = 'video';
        }

        $video_poster = isset($item['video_poster']) ? esc_url_raw($item['video_poster']) : $image_url;
        $media_options = array(
            'autoplay' => self::truthy(isset($item['video_autoplay']) ? $item['video_autoplay'] : false),
            'loop' => self::truthy(isset($item['video_loop']) ? $item['video_loop'] : false),
            'controls' => self::truthy(isset($item['video_controls']) ? $item['video_controls'] : true),
            'start' => self::video_start_seconds(isset($item['video_start']) ? $item['video_start'] : (isset($item['start_time']) ? $item['start_time'] : '')),
        );
        $youtube_embed_url = self::youtube_embed_url($youtube_url ?: $video_url, $media_options);

        if ($media_kind === 'youtube' && !$youtube_embed_url) {
            $media_kind = 'image';
        } elseif ($media_kind === 'video' && !$video_url) {
            $media_kind = 'image';
        }

        $slide = array(
            'id' => $id,
            'type' => 'custom',
            'post_type' => isset($item['post_type']) ? sanitize_key($item['post_type']) : 'custom',
            'source' => array(
                'kind' => 'custom',
                'id' => $id,
            ),
            'post' => null,
            'url' => $url,
            'title' => array(
                'text' => $title,
                'html' => self::custom_title_html($title, $url, $item_params),
            ),
            'image' => array(
                'id' => 0,
                'src' => $image_url,
                'alt' => $title,
                'width' => 0,
                'height' => 0,
            ),
            'media' => array(
                'kind' => $media_kind,
                'video_url' => $video_url,
                'youtube_url' => $youtube_url,
                'embed_url' => $youtube_embed_url,
                'poster' => $video_poster,
                'autoplay' => $media_options['autoplay'],
                'loop' => $media_options['loop'],
                'controls' => $media_options['controls'],
                'start' => $media_options['start'],
                'playlist' => $video_allowed ? self::custom_playlist_data(isset($item['playlist']) ? $item['playlist'] : array(), $item_params) : array(),
            ),
            'display' => array(
                'eyebrow' => isset($item['eyebrow']) ? sanitize_text_field($item['eyebrow']) : '',
                'kicker' => isset($item['kicker']) ? sanitize_text_field($item['kicker']) : '',
                'story_step' => isset($item['story_step']) ? sanitize_text_field($item['story_step']) : '',
                'duration' => isset($item['duration']) ? sanitize_text_field($item['duration']) : '',
                'accent' => isset($item['accent']) ? sanitize_hex_color($item['accent']) : '',
            ),
            'description' => array(
                'mode' => 'custom',
                'text' => $description_text,
                'html' => $description,
            ),
            'meta' => array(
                'created_date' => '',
                'author' => '',
                'author_url' => '',
                'categories' => array(),
                'tags' => array(),
            ),
            'actions' => array(
                'buttons_html' => self::custom_button_html($url, $button_label, $item_params),
            ),
            'layout' => self::item_layout($item),
            'html' => array(),
        );

        $slide['html'] = array(
            'featured_image' => self::custom_featured_image_html($slide, $item_params),
            'title' => $slide['title']['html'],
            'description' => $slide['description']['html'],
            'created_date' => '',
            'author' => '',
            'categories' => '',
            'tags' => '',
            'buttons' => $slide['actions']['buttons_html'],
        );

        $slide = apply_filters('wp_posts_carousel_custom_slide_view_model', $slide, $item, $params, $config);
        $slide['html'] = apply_filters('wp_posts_carousel_slide_html_parts', $slide['html'], $slide, $params, $config);

        return $slide;
    }

    private static function custom_playlist_data($playlist, $params)
    {
        if (!is_array($playlist)) {
            return array();
        }

        $output = array();

        foreach (array_values($playlist) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = isset($item['id']) && $item['id'] !== '' ? sanitize_text_field($item['id']) : 'playlist-' . ($index + 1);
            $title = self::limited_field_text(isset($item['label']) ? sanitize_text_field($item['label']) : '', $params, 'title_length');
            $raw_description = isset($item['description']) ? wp_kses_post($item['description']) : '';
            $description_limit = self::field_limit($params, 'description_length');
            $description_text = self::limited_text($raw_description, $description_limit);
            $description = $description_limit > 0 ? esc_html($description_text) : $raw_description;
            $url = isset($item['url']) ? esc_url_raw($item['url']) : '';
            $image_url = isset($item['image_url']) ? esc_url_raw($item['image_url']) : '';
            $image_url = self::demo_custom_item_image_url($id, $image_url);
            $video_url = isset($item['video_url']) ? esc_url_raw($item['video_url']) : '';
            $youtube_url = isset($item['youtube_url']) ? esc_url_raw($item['youtube_url']) : '';
            $media_kind = isset($item['media_kind']) ? sanitize_key($item['media_kind']) : 'image';
            $media_kind = in_array($media_kind, array('image', 'video', 'youtube'), true) ? $media_kind : 'image';
            $video_allowed = self::video_media_allowed($item, 'custom_playlist_item');

            if (!$video_allowed) {
                $video_url = '';
                $youtube_url = '';
                $media_kind = 'image';
            }

            if ($youtube_url) {
                $media_kind = 'youtube';
            } elseif ($video_url && $media_kind === 'image') {
                $media_kind = 'video';
            }

            $media_options = array(
                'autoplay' => self::truthy(isset($item['video_autoplay']) ? $item['video_autoplay'] : false),
                'loop' => self::truthy(isset($item['video_loop']) ? $item['video_loop'] : false),
                'controls' => self::truthy(isset($item['video_controls']) ? $item['video_controls'] : true),
                'start' => self::video_start_seconds(isset($item['video_start']) ? $item['video_start'] : (isset($item['start_time']) ? $item['start_time'] : '')),
            );
            $youtube_embed_url = self::youtube_embed_url($youtube_url ?: $video_url, $media_options);

            if ($media_kind === 'youtube' && !$youtube_embed_url) {
                $media_kind = 'image';
            } elseif ($media_kind === 'video' && !$video_url) {
                $media_kind = 'image';
            }

            $video_poster = isset($item['video_poster']) ? esc_url_raw($item['video_poster']) : $image_url;

            $output[] = array(
                'id' => $id,
                'type' => 'custom_video',
                'url' => $url,
                'title' => array(
                    'text' => $title,
                    'html' => self::custom_title_html($title, $url, $params),
                ),
                'description' => array(
                    'mode' => 'custom',
                    'text' => $description_text,
                    'html' => $description,
                ),
                'image' => array(
                    'id' => 0,
                    'src' => $image_url,
                    'alt' => $title,
                    'width' => 0,
                    'height' => 0,
                ),
                'media' => array(
                    'kind' => $media_kind,
                    'video_url' => $video_url,
                    'youtube_url' => $youtube_url,
                    'embed_url' => $youtube_embed_url,
                    'poster' => $video_poster,
                    'autoplay' => $media_options['autoplay'],
                    'loop' => $media_options['loop'],
                    'controls' => $media_options['controls'],
                    'start' => $media_options['start'],
                ),
                'display' => array(
                    'eyebrow' => isset($item['eyebrow']) ? sanitize_text_field($item['eyebrow']) : '',
                    'kicker' => isset($item['kicker']) ? sanitize_text_field($item['kicker']) : '',
                    'story_step' => isset($item['story_step']) ? sanitize_text_field($item['story_step']) : '',
                    'duration' => isset($item['duration']) ? sanitize_text_field($item['duration']) : '',
                    'accent' => '',
                ),
            );
        }

        return $output;
    }

    public static function public_data($slide)
    {
        if (!is_array($slide)) {
            return array();
        }

        unset($slide['post']);

        if (!empty($slide['meta']['categories'])) {
            $slide['meta']['categories'] = self::public_terms($slide['meta']['categories']);
        }

        if (!empty($slide['meta']['tags'])) {
            $slide['meta']['tags'] = self::public_terms($slide['meta']['tags']);
        }

        if (!empty($slide['meta']['author_url'])) {
            $slide['meta']['author_url'] = esc_url_raw($slide['meta']['author_url']);
        }

        return apply_filters('wp_posts_carousel_public_slide_data', $slide);
    }

    private static function demo_custom_item_image_url($id, $image_url)
    {
        if (!$id) {
            return $image_url;
        }

        $demo_post_keys = array(
            'demo-rail-desk-kit' => 'app-store-strip',
            'demo-rail-editor-bag' => 'manual-mix',
            'demo-rail-focus-light' => 'responsive-carousel',
            'demo-rail-launch-shoe' => 'woocommerce-campaign',
            'demo-rail-reading-pack' => 'storefront-highlights',
        );

        if (empty($demo_post_keys[$id])) {
            return $image_url;
        }

        if ($image_url && strpos($image_url, 'picsum.photos') === false) {
            return $image_url;
        }

        static $cache = array();

        if (isset($cache[$id])) {
            return $cache[$id];
        }

        $posts = get_posts(
            array(
                'post_type' => 'post',
                'post_status' => 'any',
                'meta_key' => '_wp_posts_carousel_demo_key',
                'meta_value' => $demo_post_keys[$id],
                'fields' => 'ids',
                'numberposts' => 1,
                'no_found_rows' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );

        $post_id = !empty($posts[0]) ? (int) $posts[0] : 0;
        $thumbnail = $post_id ? get_the_post_thumbnail_url($post_id, 'full') : '';

        $cache[$id] = $thumbnail ?: WP_POSTS_CAROUSEL_DIR_URL . 'assets/images/template-placeholder.png';

        return $cache[$id];
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

    private static function video_start_seconds($value)
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return max(0, absint($value));
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        if (preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/i', $value, $matches) && !empty($matches[0])) {
            $hours = isset($matches[1]) && $matches[1] !== '' ? absint($matches[1]) : 0;
            $minutes = isset($matches[2]) && $matches[2] !== '' ? absint($matches[2]) : 0;
            $seconds = isset($matches[3]) && $matches[3] !== '' ? absint($matches[3]) : 0;

            return ($hours * 3600) + ($minutes * 60) + $seconds;
        }

        $parts = array_values(array_filter(explode(':', $value), static function ($part) {
            return $part !== '';
        }));

        if (empty($parts) || count($parts) > 3) {
            return 0;
        }

        $parts = array_map('absint', $parts);

        if (count($parts) === 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        }

        if (count($parts) === 2) {
            return ($parts[0] * 60) + $parts[1];
        }

        return $parts[0];
    }

    private static function youtube_embed_url($url, $options = array())
    {
        if (!$url) {
            return '';
        }

        $parts = wp_parse_url($url);
        if (empty($parts['host'])) {
            return '';
        }

        $host = strtolower($parts['host']);
        $path = isset($parts['path']) ? $parts['path'] : '';
        $video_id = '';
        $query = array();

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        if (strpos($host, 'youtu.be') !== false) {
            $video_id = trim($path, '/');
        } elseif (strpos($host, 'youtube.com') !== false) {
            if (!empty($query['v'])) {
                $video_id = $query['v'];
            } elseif (preg_match('#/(embed|shorts)/([^/?]+)#', $path, $matches)) {
                $video_id = $matches[2];
            }
        }

        $video_id = preg_replace('/[^A-Za-z0-9_-]/', '', $video_id);

        if (!$video_id) {
            return '';
        }

        $params = array(
            'enablejsapi' => '1',
            'rel' => '0',
            'modestbranding' => '1',
            'playsinline' => '1',
        );

        $origin = function_exists('home_url') ? home_url() : '';
        if ($origin) {
            $params['origin'] = $origin;
        }

        if (!empty($options['autoplay'])) {
            $params['autoplay'] = '1';
            $params['mute'] = '1';
        }

        if (isset($options['controls']) && !$options['controls']) {
            $params['controls'] = '0';
        }

        $start = isset($options['start']) ? absint($options['start']) : 0;

        if (!$start && !empty($query)) {
            if (!empty($query['start'])) {
                $start = self::video_start_seconds($query['start']);
            } elseif (!empty($query['t'])) {
                $start = self::video_start_seconds($query['t']);
            }
        }

        if ($start > 0) {
            $params['start'] = $start;
        }

        if (!empty($options['loop'])) {
            $params['loop'] = '1';
            $params['playlist'] = $video_id;
        }

        return 'https://www.youtube-nocookie.com/embed/' . $video_id . '?' . http_build_query($params, '', '&');
    }

    private static function detect_taxonomies($post_type)
    {
        $category = 'category';
        $tag = 'post_tag';

        if ($post_type === 'product') {
            $category = taxonomy_exists('product_cat') ? 'product_cat' : $category;
            $tag = taxonomy_exists('product_tag') ? 'product_tag' : $tag;
        }

        foreach ((array) get_object_taxonomies($post_type, 'objects') as $taxonomy) {
            if (preg_match('/category|(^|[_-])cat$/i', $taxonomy->name)) {
                $category = $taxonomy->name;
            } elseif (preg_match('/tag|(^|[_-])tag$/i', $taxonomy->name)) {
                $tag = $taxonomy->name;
            }
        }

        return apply_filters('wp_posts_carousel_post_type_taxonomies', array(
            'category' => $category,
            'tag' => $tag,
        ), $post_type);
    }

    private static function featured_image($post, $params)
    {
        $source = isset($params['featured_image_source']) ? $params['featured_image_source'] : 'thumbnail';
        $attachment_id = get_post_thumbnail_id($post->ID);
        $image = $attachment_id ? wp_get_attachment_image_src($attachment_id, $source) : false;

        if (!$image || empty($image[0])) {
            $image = array(
                apply_filters('wp_posts_carousel_item_featured_image_placeholder', WP_POSTS_CAROUSEL_DIR_URL . 'assets/images/placeholder.png'),
                0,
                0,
            );
        }

        return array(
            'id' => (int) $attachment_id,
            'src' => $image[0],
            'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true) ?: get_the_title($post),
            'width' => isset($image[1]) ? (int) $image[1] : 0,
            'height' => isset($image[2]) ? (int) $image[2] : 0,
            'srcset' => $attachment_id ? (string) wp_get_attachment_image_srcset($attachment_id, $source) : '',
            'sizes' => $attachment_id ? (string) wp_get_attachment_image_sizes($attachment_id, $source) : '',
        );
    }

    private static function layout($post_id, $config)
    {
        $layout = array(
            'span' => 1,
            'group' => '',
            'crop_x' => 0,
            'crop_y' => 0,
            'strip_index' => 0,
            'strip_count' => 1,
        );

        if (empty($config['curation']['items']) || !is_array($config['curation']['items'])) {
            return $layout;
        }

        foreach ($config['curation']['items'] as $item) {
            if (empty($item['id']) || (int) $item['id'] !== (int) $post_id) {
                continue;
            }

            return array(
                'span' => isset($item['span']) ? max(1, min(6, absint($item['span']))) : 1,
                'group' => isset($item['group']) ? sanitize_text_field($item['group']) : '',
                'crop_x' => isset($item['crop_x']) ? max(-100, min(100, intval($item['crop_x']))) : 0,
                'crop_y' => isset($item['crop_y']) ? max(-100, min(100, intval($item['crop_y']))) : 0,
                'strip_index' => isset($item['strip_index']) ? max(0, absint($item['strip_index'])) : 0,
                'strip_count' => isset($item['strip_count']) ? max(1, absint($item['strip_count'])) : 1,
            );
        }

        return $layout;
    }

    private static function item_layout($item)
    {
        return array(
            'span' => isset($item['span']) ? max(1, min(6, absint($item['span']))) : 1,
            'group' => isset($item['group']) ? sanitize_text_field($item['group']) : '',
            'crop_x' => isset($item['crop_x']) ? max(-100, min(100, intval($item['crop_x']))) : 0,
            'crop_y' => isset($item['crop_y']) ? max(-100, min(100, intval($item['crop_y']))) : 0,
            'strip_index' => isset($item['strip_index']) ? max(0, absint($item['strip_index'])) : 0,
            'strip_count' => isset($item['strip_count']) ? max(1, absint($item['strip_count'])) : 1,
        );
    }

    private static function title_html($title, $url, $params)
    {
        if (!self::truthy(isset($params['show_title']) ? $params['show_title'] : true)) {
            return '';
        }

        return '<a href="' . esc_url($url) . '">' . esc_html($title) . '</a>';
    }

    private static function featured_image_html($slide, $params)
    {
        if (!self::truthy(isset($params['show_featured_image']) ? $params['show_featured_image'] : true)) {
            return '';
        }

        $width = esc_attr((isset($params['featured_image_width']) ? $params['featured_image_width'] : 100) . (isset($params['featured_image_width_unit']) ? $params['featured_image_width_unit'] : '%'));
        $height = esc_attr((isset($params['featured_image_height']) ? $params['featured_image_height'] : 100) . (isset($params['featured_image_height_unit']) ? $params['featured_image_height_unit'] : '%'));
        $loading = !empty($params['lazy_images']) || !empty($params['lazy_load']) ? ' loading="lazy"' : '';
        $srcset = !empty($slide['image']['srcset']) ? ' srcset="' . esc_attr($slide['image']['srcset']) . '"' : '';
        $sizes = !empty($slide['image']['sizes']) ? ' sizes="' . esc_attr($slide['image']['sizes']) . '"' : '';

        return '<a href="' . esc_url($slide['url']) . '"><img alt="' . esc_attr($slide['image']['alt']) . '" style="max-width:' . $width . ';height:' . $height . ';" src="' . esc_url($slide['image']['src']) . '"' . $srcset . $sizes . $loading . ' decoding="async"></a>';
    }

    private static function custom_title_html($title, $url, $params)
    {
        if (!self::truthy(isset($params['show_title']) ? $params['show_title'] : true) || !$title) {
            return '';
        }

        if ($url) {
            return '<a href="' . esc_url($url) . '">' . esc_html($title) . '</a>';
        }

        return esc_html($title);
    }

    private static function custom_featured_image_html($slide, $params)
    {
        if (!self::truthy(isset($params['show_featured_image']) ? $params['show_featured_image'] : true) || empty($slide['image']['src'])) {
            return '';
        }

        $width = esc_attr((isset($params['featured_image_width']) ? $params['featured_image_width'] : 100) . (isset($params['featured_image_width_unit']) ? $params['featured_image_width_unit'] : '%'));
        $height = esc_attr((isset($params['featured_image_height']) ? $params['featured_image_height'] : 100) . (isset($params['featured_image_height_unit']) ? $params['featured_image_height_unit'] : '%'));
        $loading = !empty($params['lazy_images']) || !empty($params['lazy_load']) ? ' loading="lazy"' : '';
        $srcset = !empty($slide['image']['srcset']) ? ' srcset="' . esc_attr($slide['image']['srcset']) . '"' : '';
        $sizes = !empty($slide['image']['sizes']) ? ' sizes="' . esc_attr($slide['image']['sizes']) . '"' : '';
        $image = '<img alt="' . esc_attr($slide['image']['alt']) . '" style="max-width:' . $width . ';height:' . $height . ';" src="' . esc_url($slide['image']['src']) . '"' . $srcset . $sizes . $loading . ' decoding="async">';

        return $slide['url'] ? '<a href="' . esc_url($slide['url']) . '">' . $image . '</a>' : $image;
    }

    private static function custom_button_html($url, $label, $params)
    {
        if (!self::truthy(isset($params['show_more_button']) ? $params['show_more_button'] : true) || !$url) {
            return '';
        }

        return '<a href="' . esc_url($url) . '" class="cci-wpc-more-button button">' . esc_html($label) . '</a>';
    }

    private static function description($post, $params)
    {
        $mode = isset($params['show_description']) ? $params['show_description'] : 'excerpt';
        $html = '';

        if ($mode === 'excerpt') {
            $html = get_the_excerpt($post);
        } elseif ($mode === 'content') {
            $content = get_post_field('post_content', $post->ID);
            $html = self::truthy(isset($params['allow_shortcodes']) ? $params['allow_shortcodes'] : false) ? do_shortcode($content) : wpautop($content);
        }

        $limit = self::field_limit($params, 'description_length');
        $text = self::limited_text($html, $limit);

        if ($limit > 0 && $text !== '') {
            $html = esc_html($text);
        }

        return array(
            'mode' => $mode,
            'text' => $text,
            'html' => $html,
        );
    }

    private static function field_limit($params, $key)
    {
        if (!is_array($params) || !isset($params[$key])) {
            return 0;
        }

        return max(0, absint($params[$key]));
    }

    private static function custom_item_display_params($item, $params)
    {
        $display_params = is_array($params) ? $params : array();

        foreach (array('title_length', 'description_length') as $key) {
            if (isset($item[$key])) {
                $display_params[$key] = max(0, absint($item[$key]));
            }
        }

        return $display_params;
    }

    private static function limited_field_text($text, $params, $key)
    {
        return self::limited_text($text, self::field_limit($params, $key));
    }

    private static function limited_text($text, $limit)
    {
        $text = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $text)));

        if (!$text || $limit <= 0) {
            return $text;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);

        if ($length <= $limit) {
            return $text;
        }

        $cut = function_exists('mb_substr') ? mb_substr($text, 0, $limit) : substr($text, 0, $limit);
        $trimmed = preg_replace('/\s+\S*$/u', '', $cut);
        $cut = $trimmed ? $trimmed : $cut;

        return rtrim($cut, " \t\n\r\0\x0B.,;:-") . '...';
    }

    private static function display_params_for_post_type($post_type, $post, $params, $config)
    {
        $display = isset($config['template']['display']) && is_array($config['template']['display'])
            ? $config['template']['display']
            : array();
        $post_type = sanitize_key($post_type);
        $type_display = isset($display[$post_type]) && is_array($display[$post_type])
            ? $display[$post_type]
            : array();
        $defaults = self::display_defaults_for_post_type($post_type);
        $display_params = array_merge((array) $params, $defaults, $type_display);

        return apply_filters('wp_posts_carousel_post_type_display_params', $display_params, $post_type, $post, $params, $config);
    }

    private static function display_defaults_for_post_type($post_type)
    {
        $defaults = array(
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
            'show_author' => false,
            'featured_image_source' => 'thumbnail',
            'featured_image_width' => 100,
            'featured_image_width_unit' => '%',
            'featured_image_height' => 100,
            'featured_image_height_unit' => '%',
        );

        return apply_filters('wp_posts_carousel_post_type_display_defaults', $defaults, $post_type);
    }

    private static function post_display($post, $config, $display_params = array())
    {
        $display = array(
            'eyebrow' => '',
            'kicker' => '',
            'story_step' => '',
            'accent' => '',
            'price_html' => '',
            'price_text' => '',
            'on_sale' => false,
        );

        return self::apply_mapped_display($display, $post, $config);
    }

    private static function apply_mapped_display($display, $post, $config)
    {
        foreach (array('eyebrow', 'kicker', 'story_step', 'duration', 'price_text') as $key) {
            $value = self::mapped_field($post, $config, $key);

            if ($value !== '') {
                $display[$key] = $value;
            }
        }

        $accent = self::mapped_field($post, $config, 'accent');
        if ($accent !== '') {
            $color = sanitize_hex_color($accent);

            if ($color) {
                $display['accent'] = $color;
            }
        }

        if (!empty($display['price_text']) && empty($display['price_html'])) {
            $display['price_html'] = esc_html($display['price_text']);
        }

        return $display;
    }

    private static function mapped_field($post, $config, $key)
    {
        if (empty($config['field_mapping']) || !is_array($config['field_mapping'])) {
            return '';
        }

        $meta_key = isset($config['field_mapping'][$key]) ? sanitize_key($config['field_mapping'][$key]) : '';

        if (!$meta_key) {
            return '';
        }

        $value = get_post_meta($post->ID, $meta_key, true);

        if (is_array($value) || is_object($value)) {
            return '';
        }

        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (in_array($key, array('url', 'image_url'), true)) {
            return esc_url_raw($value);
        }

        if ($key === 'accent') {
            return sanitize_hex_color($value) ?: '';
        }

        return sanitize_text_field($value);
    }

    private static function post_type_card_html($post, $display_params, $config, $context)
    {
        $post_type = get_post_type($post);
        $html = '';

        return apply_filters('wp_posts_carousel_post_type_card_html', $html, $post, $post_type, $display_params, $config, $context);
    }

    private static function display_enabled($values, $key, $default = true)
    {
        if (!is_array($values) || !array_key_exists($key, $values)) {
            return $default;
        }

        return self::truthy($values[$key]);
    }

    private static function terms($post_id, $taxonomy)
    {
        $terms = get_the_terms($post_id, $taxonomy);

        if (!is_array($terms)) {
            return array();
        }

        return array_map(
            function ($term) use ($taxonomy) {
                return array(
                    'id' => (int) $term->term_id,
                    'name' => $term->name,
                    'taxonomy' => $taxonomy,
                    'url' => get_term_link($term),
                );
            },
            $terms
        );
    }

    private static function terms_html($terms)
    {
        $links = array();

        foreach ((array) $terms as $term) {
            if (is_wp_error($term['url'])) {
                continue;
            }

            $links[] = '<a href="' . esc_url($term['url']) . '">' . esc_html($term['name']) . '</a>';
        }

        return implode('<span>, </span>', $links);
    }

    private static function public_terms($terms)
    {
        $public_terms = array();

        foreach ((array) $terms as $term) {
            $public_terms[] = array(
                'id' => isset($term['id']) ? (int) $term['id'] : 0,
                'name' => isset($term['name']) ? sanitize_text_field($term['name']) : '',
                'taxonomy' => isset($term['taxonomy']) ? sanitize_key($term['taxonomy']) : '',
                'url' => isset($term['url']) && !is_wp_error($term['url']) ? esc_url_raw($term['url']) : '',
            );
        }

        return $public_terms;
    }

    private static function buttons_html($post, $url, $params)
    {
        if (!self::truthy(isset($params['show_more_button']) ? $params['show_more_button'] : true)) {
            return '';
        }

        $is_product = get_post_type($post) === 'product';
        $label = $is_product
            ? __('View product', 'wp-posts-carousel')
            : __('read more', 'wp-posts-carousel');
        $label = !empty($params['button_label']) ? sanitize_text_field($params['button_label']) : $label;
        $class = $is_product
            ? 'cci-wpc-more-button cci-wpc-product-button button'
            : 'cci-wpc-more-button button';

        return '<a href="' . esc_url($url) . '" class="' . esc_attr($class) . '">' . esc_html($label) . '</a>';
    }

    private static function truthy($value)
    {
        return $value === true || $value === 'true' || $value === 1 || $value === '1';
    }
}
