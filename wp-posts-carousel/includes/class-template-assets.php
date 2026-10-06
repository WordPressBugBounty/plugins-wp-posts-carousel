<?php

if (!defined('ABSPATH')) {
    exit;
}

/** @internal Shared stylesheet resolution for PHP and lazy-loaded carousels. */
final class WP_Posts_Carousel_Template_Assets
{
    public static function stylesheet($template)
    {
        if (empty($template['name']) || empty($template['style']) || empty($template['style_url']) || !is_file($template['style'])) {
            return null;
        }

        return array(
            'handle' => 'wp-posts-carousel-template-' . sanitize_key($template['name']),
            'url' => esc_url_raw($template['style_url']),
            'version' => (string) filemtime($template['style']),
        );
    }

    public static function enqueue($template)
    {
        $style = self::stylesheet($template);
        if ($style) {
            wp_enqueue_style($style['handle'], $style['url'], array(), $style['version']);
        }
    }

    public static function enqueue_content($content, &$visited)
    {
        if (preg_match_all('/' . get_shortcode_regex(array('wp_posts_carousel')) . '/s', (string) $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if ($match[1] === '[' && $match[6] === ']') {
                    continue;
                }
                $atts = shortcode_parse_atts($match[3]);
                self::enqueue_carousel(isset($atts['id']) ? $atts['id'] : 0);
            }
        }
        self::enqueue_blocks(parse_blocks((string) $content), $visited);
    }

    private static function enqueue_blocks($blocks, &$visited)
    {
        foreach ($blocks as $block) {
            $attrs = isset($block['attrs']) ? $block['attrs'] : array();
            if ($block['blockName'] === 'wp-posts-carousel/carousel') {
                self::enqueue_carousel(isset($attrs['carouselId']) ? $attrs['carouselId'] : 0);
            }
            if ($block['blockName'] === 'core/block' && !empty($attrs['ref'])) {
                $ref = absint($attrs['ref']);
                if (!isset($visited[$ref])) {
                    $visited[$ref] = true;
                    $post = get_post($ref);
                    if ($post instanceof WP_Post) {
                        self::enqueue_content($post->post_content, $visited);
                    }
                }
            }
            if (!empty($block['innerBlocks'])) {
                self::enqueue_blocks($block['innerBlocks'], $visited);
            }
        }
    }

    public static function enqueue_carousel($id)
    {
        $id = absint($id);
        if (!$id) {
            return;
        }
        $config = WP_Posts_Carousel_Repository::get_config($id);
        if ($config) {
            self::enqueue(WP_Posts_Carousel_Data::template($config));
        }
    }

    public static function enqueue_elementor($elements)
    {
        foreach ((array) $elements as $element) {
            if (!is_array($element)) {
                continue;
            }
            if (isset($element['widgetType']) && $element['widgetType'] === 'wp_posts_carousel') {
                self::enqueue_carousel(isset($element['settings']['carousel_id']) ? $element['settings']['carousel_id'] : 0);
            }
            if (!empty($element['elements'])) {
                self::enqueue_elementor($element['elements']);
            }
        }
    }
}
