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
 * WP_Posts_Carousel_Utils class
 */
class WP_Posts_Carousel_Utils
{

    /**
     * get defaults
     */
    public static function get_carousel_default_params()
    {
        $params = apply_filters(
            'wp_posts_carousel_params_defaults',
            array(
                'post_selection_options' => array(
                    'post_types'          => array(array('value' => 'post', 'label' => __('Posts', 'wp-posts-carousel'), 'post_type' => 'post')),
                    'taxonomies'          => array(),
                    'order_by'            => 'id',
                    'ordering'            => 'asc',
                    'posts'               => array(),
                    'exclude'             => '',
                    'relation'            => 'or',
                    'operator'            => 'in',
                    'all_items'           => 10,
                ),
                'carousel_type'       => 'owl-carousel',
                'renderer'            => 'php',
                'template'            => 'default',
                'frontend_filters'    => array(
                    'enabled' => false,
                    'taxonomies' => array(),
                    'show_all_option' => true,
                    'all_label' => __('All', 'wp-posts-carousel'),
                    'param_prefix' => 'wpcf_',
                    'term_limit' => 12,
                ),
                'performance_options' => array(
                    'lazy_images' => true,
                    'first_image_eager' => false,
                    'image_size' => 'inherit',
                ),
                'field_mapping'       => array(
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
                'display_options' => array(
                    'post' => array(
                        'show_title'          => true,
                        'title_length'        => 0,
                        'show_created_date'   => true,
                        'show_description'    => 'excerpt',
                        'description_length'  => 0,
                        'allow_shortcodes'    => false,
                        'show_categories'     => true,
                        'show_tags'           => false,
                        'show_more_button'    => true,
                        'show_featured_image' => true,
                        'show_author'         => false,
                        'featured_image_source'        => 'thumbnail',
                        'featured_image_width'         => 100,
                        'featured_image_width_unit'    => '%',
                        'featured_image_height'        => 100,
                        'featured_image_height_unit'   => '%',
                    ),
                    'product' => array(
                        'show_title'          => true,
                        'title_length'        => 0,
                        'show_created_date'   => true,
                        'show_description'    => 'excerpt',
                        'description_length'  => 0,
                        'allow_shortcodes'    => false,
                        'show_categories'     => true,
                        'show_tags'           => false,
                        'show_more_button'    => true,
                        'show_featured_image' => true,
                        'show_author'         => false,
                        'featured_image_source'        => 'thumbnail',
                        'featured_image_width'         => 100,
                        'featured_image_width_unit'    => '%',
                        'featured_image_height'        => 100,
                        'featured_image_height_unit'   => '%',
                    ),
                ),
                'carousel_options' => array(
                    'rtl'                 => false,
                    'loop'                => true,
                    'rewind'              => false,
                    'center'              => false,
                    'auto_play'           => true,
                    'autoplay_indicator'  => 'none',
                    'stop_on_hover'       => true,
                    'auto_play_timeout'   => 1200,
                    'auto_play_speed'     => 800,
                    'nav'                 => true,
                    'nav_speed'           => 800,
                    'dots'                => true,
                    'dots_speed'          => 800,
                    'stage_padding'       => 0,
                    'margin'              => 5,
                    'lazy_load'           => false,
                    'lazy_load_eager'     => 0,
                    'mouse_drag'          => true,
                    'mouse_wheel'         => true,
                    'touch_drag'          => true,
                    'pull_drag'           => true,
                    'free_drag'           => false,
                    'slide_by'            => 1,
                    'start_position'      => 0,
                    'direction'           => 'horizontal',
                    'pagination_type'     => 'bullets',
                    'keyboard'            => false,
                    'grab_cursor'         => false,
                    'mousewheel_release_on_edges' => false,
                    'mousewheel_force_to_axis' => false,
                    'mousewheel_sensitivity' => 1,
                    'touch_ratio'         => 1,
                    'watch_overflow'      => true,
                    'transition_effect'   => 'slide',
                    'fallback_easing'     => 'linear',
                    'animate_in'          => '',
                    'animate_out'         => '',
                    'auto_width'          => false,
                    'auto_height'         => true,
                    'nav_icon_library'    => '',
                    'nav_icon_previous'   => '',
                    'nav_icon_next'       => '',
                    'nav_icon_up'         => '',
                    'nav_icon_down'       => '',
                    'breakpoints' => apply_filters(
                        'wp_posts_carousel_params_breakpoints',
                        array(
                            320  => array('items' => 2, 'responsive_options' => array()),
                            480  => array('items' => 3, 'responsive_options' => array()),
                            768  => array('items' => 3, 'responsive_options' => array()),
                            1024 => array('items' => 4, 'responsive_options' => array()),
                            1200 => array('items' => 4, 'responsive_options' => array()),
                        )
                    )
                )


            )
        );

        return $params;
    }

    public static function get_post_types()
    {
        $post_types = array();

        $array = get_post_types(
            array(
                'public'            => 'true',
                'show_in_nav_menus' => true,
            ),
            'objects'
        );

        if ($array) {
            foreach ($array as $key => $item) {
                $post_types[] = array(
                    'value'   => $key,
                    'label' => $item->label,
                );
            }
        }

        return $post_types;
    }

    public static function get_taxonomies()
    {
        $taxonomies = array();

        $args = array(
            'public'   => true,
        );
        $output = 'objects'; // or objects
        $operator = 'or'; // 'and' or 'or'

        $array = get_taxonomies($args, $output, $operator);

        if ($array) {
            foreach ($array as $key => $item) {
                if ($item->query_var !== false) {
                    $taxonomies[$item->object_type[0]][] = array(
                        'label' => $item->label,
                        'labels' => $item->labels,
                        'name' => $item->name,
                        'object_type' => $item->object_type,
                        'query_var' => $item->query_var,
                        'item' => $item
                    );
                }
            }
        }
        return apply_filters('wp_posts_carousel_post_criteria_meta_box_taxonomies', $taxonomies);
    }

    public static function get_operators()
    {
        $array = array(
            array(
                'value'   => 'in',
                'label' => __('In', 'wp-posts-carousel'),
            ),
            array(
                'value'   => 'not in',
                'label' => __('Not in', 'wp-posts-carousel'),
            ),
        );

        return apply_filters('wp_posts_carousel_post_criteria_meta_box_operators', $array);
    }

    public static function get_orders_by()
    {
        $array = array(
            array(
                'value'   => 'id',
                'label' => __('By id', 'wp-posts-carousel'),
            ),
            array(
                'value'   => 'post_title',
                'label' => __('By title', 'wp-posts-carousel'),
            ),
            array(
                'value'   => 'post_date',
                'label' => __('By date', 'wp-posts-carousel'),
            ),
            array(
                'value'   => 'post_modified',
                'label' => __('By modified date', 'wp-posts-carousel'),
            ),
            array(
                'value'   => 'posts_order',
                'label' => __('By posts order', 'wp-posts-carousel'),
            ),
        );

        return apply_filters('wp_posts_carousel_post_criteria_meta_box_orders_by', $array);
    }

    public static function get_orderings()
    {
        $array = array(
            array(
                'value'   => 'asc',
                'label' => __('Ascending', 'wp-posts-carousel'),
            ),
            array(
                'value'   => 'desc',
                'label' => __('Descending', 'wp-posts-carousel'),
            ),
            array(
                'value'   => 'random',
                'label' => __('Random', 'wp-posts-carousel'),
            ),
        );

        return apply_filters('wp_posts_carousel_post_criteria_meta_box_get_orderings', $array);
    }

    public static function get_descriptions()
    {
        $array = array(
            array(
                'value'   => 'false',
                'label' => __('No', 'wp-posts-carousel'),
            ),
            array(
                'value'   => 'excerpt',
                'label' => __('Excerpt', 'wp-posts-carousel'),
            ),
            array(
                'value'   => 'content',
                'label' => __('Full content', 'wp-posts-carousel'),
            ),
        );

        return apply_filters('wp_posts_carousel_display_meta_box_get_descriptions', $array);
    }

    public static function get_image_sources()
    {
        $array = array(
            array(
                'value'   => 'thumbnail',
                'label' => __('Thumbnail', 'wp-posts-carousel'),
            ),
            array(
                'value'   => 'medium',
                'label' => __('Medium', 'wp-posts-carousel'),
            ),
            array(
                'value'   => 'large',
                'label' => __('Large', 'wp-posts-carousel'),
            ),
            array(
                'value'   => 'full',
                'label' => __('Full', 'wp-posts-carousel'),
            ),
        );

        return apply_filters('wp_posts_carousel_display_meta_box_get_image_sources', $array);
    }

    public static function get_performance_image_sizes()
    {
        $sizes = array(
            array(
                'value' => 'inherit',
                'label' => __('Use display setting', 'wp-posts-carousel'),
            ),
            array(
                'value' => 'thumbnail',
                'label' => __('Thumbnail', 'wp-posts-carousel'),
            ),
            array(
                'value' => 'medium',
                'label' => __('Medium', 'wp-posts-carousel'),
            ),
            array(
                'value' => 'medium_large',
                'label' => __('Medium large', 'wp-posts-carousel'),
            ),
            array(
                'value' => 'large',
                'label' => __('Large', 'wp-posts-carousel'),
            ),
            array(
                'value' => 'full',
                'label' => __('Full', 'wp-posts-carousel'),
            ),
        );

        return apply_filters('wp_posts_carousel_performance_image_sizes', $sizes);
    }

    public static function get_field_mapping_targets()
    {
        $targets = array(
            array('value' => 'eyebrow', 'label' => __('Eyebrow / badge', 'wp-posts-carousel')),
            array('value' => 'kicker', 'label' => __('Kicker / secondary text', 'wp-posts-carousel')),
            array('value' => 'story_step', 'label' => __('Story step', 'wp-posts-carousel')),
            array('value' => 'duration', 'label' => __('Video duration', 'wp-posts-carousel')),
            array('value' => 'accent', 'label' => __('Accent color', 'wp-posts-carousel')),
            array('value' => 'price_text', 'label' => __('Price text fallback', 'wp-posts-carousel')),
            array('value' => 'button_label', 'label' => __('Button label', 'wp-posts-carousel')),
            array('value' => 'url', 'label' => __('Custom URL', 'wp-posts-carousel')),
            array('value' => 'image_url', 'label' => __('Custom image URL', 'wp-posts-carousel')),
        );

        return apply_filters('wp_posts_carousel_field_mapping_targets', $targets);
    }

    public static function get_animations()
    {
        $array = array(
            'linear',
            'swing',
            'easeInQuad',
            'easeOutQuad',
            'easeInOutQuad',
            'easeInCubic',
            'easeOutCubic',
            'easeInOutCubic',
            'easeInQuart',
            'easeOutQuart',
            'easeInOutQuart',
            'easeInQuint',
            'easeOutQuint',
            'easeInOutQuint',
            'easeInExpo',
            'easeOutExpo',
            'easeInOutExpo',
            'easeInSine',
            'easeOutSine',
            'easeInOutSine',
            'easeInCirc',
            'easeOutCirc',
            'easeInOutCirc',
            'easeInElastic',
            'easeOutElastic',
            'easeInOutElastic',
            'easeInBack',
            'easeOutBack',
            'easeInOutBack',
            'easeInBounce',
            'easeOutBounce',
            'easeInOutBounce',
        );

        $new_array = array();
        foreach ($array as $item) {
            array_push($new_array, ['label' => $item, 'value' => $item]);
        }
        return apply_filters('wp_posts_carousel_carousel_meta_box_get_animations', $new_array);
    }

    public static function get_relations()
    {
        return array(
            array(
                'value'   => 'and',
                'label' => __('And', 'wp-posts-carousel'),
            ),
            array(
                'value'   => 'or',
                'label' => __('Or', 'wp-posts-carousel'),
            )
        );
    }

    public static function get_units()
    {
        return array(
            array(
                'value'   => '%',
                'label' => '%',
            ),
            array(
                'value'   => 'px',
                'label' => 'px',
            ),
            array(
                'value'   => 'rem',
                'label' => 'rem',
            ),
            array(
                'value'   => 'em',
                'label' => 'em',
            ),
        );
    }

    public static function get_display_options()
    {
        $display_options = array();

        $post_display_options = array(
            array(
                'name' => 'show_title',
                'type' => 'radio',
                'default_value' => true,
                'label' => __('Show title', 'wp-posts-carousel')
            ),
            array(
                'name' => 'title_length',
                'type' => 'number',
                'default_value' => 0,
                'min' => 0,
                'step' => 1,
                'unit' => __('chars', 'wp-posts-carousel'),
                'label' => __('Title character limit', 'wp-posts-carousel')
            ),
            array(
                'name' => 'show_author',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Show author', 'wp-posts-carousel')
            ),
            array(
                'name' => 'show_created_date',
                'type' => 'radio',
                'default_value' => true,
                'label' => __('Show created date', 'wp-posts-carousel')
            ),
            array(
                'name' => 'show_description',
                'type' => 'select',
                'default_value' => null,
                'label' => __('Show description', 'wp-posts-carousel'),
                'required' => true,
                'options' => self::get_descriptions()
            ),
            array(
                'name' => 'description_length',
                'type' => 'number',
                'default_value' => 0,
                'min' => 0,
                'step' => 1,
                'unit' => __('chars', 'wp-posts-carousel'),
                'label' => __('Description character limit', 'wp-posts-carousel')
            ),
            array(
                'name' => 'show_categories',
                'type' => 'radio',
                'default_value' => true,
                'label' => __('Show categories', 'wp-posts-carousel')
            ),
            array(
                'name' => 'show_tags',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Show tags', 'wp-posts-carousel')
            ),
            array(
                'name' => 'show_more_button',
                'type' => 'radio',
                'default_value' => true,
                'label' => __('Show more button', 'wp-posts-carousel')
            ),
            array(
                'name' => 'show_featured_image',
                'type' => 'radio',
                'default_value' => true,
                'label' => __('Show featured image', 'wp-posts-carousel')
            ),
            array(
                'name' => 'featured_image_source',
                'type' => 'select',
                'default_value' => null,
                'label' => __('Image source', 'wp-posts-carousel'),
                'required' => true,
                'options' => self::get_image_sources()
            ),
            array(
                'name' => 'featured_image_width_group',
                'type' => 'group',
                'label' => __('Image width', 'wp-posts-carousel'),
                'label_for' => 'featured_image_width',
                'fields' => array(
                    array(
                        'name' => 'featured_image_width',
                        'type' => 'number',
                        'default_value' => 100,
                    ),
                    array(
                        'name' => 'featured_image_width_unit',
                        'type' => 'select',
                        'default_value' => '%',
                        'options' => self::get_units(),
                        'style' => array('width' => '70px', 'display' => 'inline-flex', 'marginLeft' => '10px')
                    ),
                ),
            ),
            array(
                'name' => 'featured_image_height_group',
                'type' => 'group',
                'label' => __('Image height', 'wp-posts-carousel'),
                'label_for' => 'featured_image_height',
                'fields' => array(
                    array(
                        'name' => 'featured_image_height',
                        'type' => 'number',
                        'default_value' => 100,
                    ),
                    array(
                        'name' => 'featured_image_height_unit',
                        'type' => 'select',
                        'default_value' => '%',
                        'options' => self::get_units(),
                        'style' => array('width' => '70px', 'display' => 'inline-flex', 'marginLeft' => '10px')
                    ),
                )
            ),
        );

        $display_options['post'] = $post_display_options;
        $display_options['product'] = $post_display_options;

        return apply_filters('wp_posts_carousel_carousel_display_options', $display_options);
    }


    public static function get_carousel_types()
    {
        $carousel_types = array();

        // array_push($carousel_types, array(
        // 	'name' => 'Tiny Slider 2',
        // 	'version' => '2.9.3',
        // 	'homepage' => 'https://github.com/ganlanyuan/tiny-slider'
        // ));
        array_push($carousel_types, array(
            'carousel_name' => 'Owl Carousel 2',
            'name' => 'owl-carousel',
            'description' => '',
            'version' => '2.3.4',
            'homepage_url' => 'https://github.com/OwlCarousel2',
            'screenshot' => ''
        ));

        array_push($carousel_types, array(
            'carousel_name' => 'Swiper',
            'name' => 'swiper',
            'description' => '',
            'version' => '12.2.0',
            'homepage_url' => 'https://swiperjs.com/',
            'screenshot' => ''
        ));

        return apply_filters('wp_posts_carousel_carousel_types', $carousel_types);
    }

    public static function get_frontend_renderers()
    {
        $renderers = array(
            array(
                'name' => 'php',
                'label' => __('PHP fallback', 'wp-posts-carousel'),
                'description' => __('Server-rendered carousel output. Best default for SEO and broad compatibility.', 'wp-posts-carousel'),
                'feature' => 'php_renderer',
                'enabled' => WP_Posts_Carousel_Features::enabled('php_renderer'),
            ),
            array(
                'name' => 'react',
                'label' => __('React enhancement', 'wp-posts-carousel'),
                'description' => __('Loads React only near the viewport and enhances the PHP-rendered carousel.', 'wp-posts-carousel'),
                'feature' => 'react_frontend',
                'enabled' => WP_Posts_Carousel_Features::enabled('react_frontend'),
            ),
        );

        return apply_filters('wp_posts_carousel_frontend_renderers_dictionary', $renderers);
    }


    public static function get_carousel_options()
    {
        $carousel_options = array();

        $carousel_options['owl-carousel'] = array(
            array(
                'name' => 'slide_by',
                'type' => 'number',
                'default_value' => 1,
                'label' => __('Slide by', 'wp-posts-carousel'),
                'description' => __('Number of slides going on one "click".', 'wp-posts-carousel'),
                'endAdornment' => __('items', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'start_position',
                'type' => 'number',
                'default_value' => 1,
                'label' => __('Start position', 'wp-posts-carousel'),
                'description' => __('The initial index of the slide. Counting from 0', 'wp-posts-carousel'),
                'endAdornment' => __('item index', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'rtl',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('RTL', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'loop',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Inifnity loop', 'wp-posts-carousel'),
                'description' => __('Duplicate last and first items to get loop illusion.', 'wp-posts-carousel'),
            ),
            array(
                'name' => 'center',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Center item', 'wp-posts-carousel'),
                'description' => __('Center the active slide in the viewport.', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'margin',
                'type' => 'number',
                'default_value' => '',
                'label' => __('Margin', 'wp-posts-carousel'),
                'endAdornment' => __('px', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'stage_padding',
                'type' => 'number',
                'default_value' => '',
                'label' => __('Stage padding', 'wp-posts-carousel'),
                'description' => __('Padding left and right on stage (can see neighbours).', 'wp-posts-carousel'),
                'endAdornment' => __('px', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),

            array(
                'name' => 'stop_on_hover',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Pause on mouse hover', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'auto_play',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Auto play', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'auto_play_timeout',
                'type' => 'number',
                'default_value' => '',
                'label' => __('Autoplay interval timeout', 'wp-posts-carousel'),
                'endAdornment' => __('ms', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'nav',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Show "next" and "prev" buttons', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'nav_speed',
                'type' => 'number',
                'default_value' => '',
                'label' => __('Navigation speed', 'wp-posts-carousel'),
                'endAdornment' => __('ms', 'wp-posts-carousel'),
                'min' => 1,
                'responsive_option' => true,
            ),
            array(
                'name' => 'dots',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Show dots navigation', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'dots_speed',
                'type' => 'number',
                'default_value' => '',
                'label' => __('Dots speed', 'wp-posts-carousel'),
                'endAdornment' => __('ms', 'wp-posts-carousel'),
                'min' => 1,
                'responsive_option' => true,
            ),
            array(
                'name' => 'lazy_load',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Delays loading of images', 'wp-posts-carousel'),
                'description' => __('Lazy load images. data-src and data-src-retina for highres. Also load images into background inline style if element is not <img>', 'wp-posts-carousel'),
            ),
            array(
                'name' => 'lazy_load_eager',
                'type' => 'number',
                'default_value' => '',
                'label' => __('Lazy Load Eager', 'wp-posts-carousel'),
                'description' => __('Eagerly pre-loads images to the right (and left when loop is enabled) based on how many items you want to preload.', 'wp-posts-carousel'),
                'endAdornment' => __('items', 'wp-posts-carousel'),
                'min' => 1,
                'responsive_option' => true,
            ),
            array(
                'name' => 'mouse_drag',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Mouse events', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'mouse_wheel',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Mousewheel scrolling', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'touch_drag',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Touch events', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'pull_drag',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Pull drag', 'wp-posts-carousel'),
                'description' => __('Stage pull to edge.', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'free_drag',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Free drag', 'wp-posts-carousel'),
                'description' => __('Item pull to edge.', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'transition_effect',
                'type' => 'select',
                'default_value' => 'slide',
                'label' => __('Transition effect', 'wp-posts-carousel'),
                'description' => __('Choose how slides animate when the carousel moves.', 'wp-posts-carousel'),
                'options' => array(
                    array('label' => __('Slide', 'wp-posts-carousel'), 'value' => 'slide'),
                    array('label' => __('Fade', 'wp-posts-carousel'), 'value' => 'fade'),
                ),
            ),
            array(
                'name' => 'fallback_easing',
                'type' => 'select',
                'default_value' => 'linear',
                'label' => __('Easing', 'wp-posts-carousel'),
                'description' => __('Motion curve used by Owl Carousel when CSS transitions are not available.', 'wp-posts-carousel'),
                'required' => true,
                'options' => self::get_animations()
            ),
            array(
                'name' => 'animate_in',
                'type' => 'text',
                'default_value' => '',
                'label' => __('Animation in class', 'wp-posts-carousel'),
                'description' => __('Class for CSS3 animation in.', 'wp-posts-carousel'),
            ),
            array(
                'name' => 'animate_out',
                'type' => 'text',
                'default_value' => '',
                'label' => __('Animation out class', 'wp-posts-carousel'),
                'description' => __('Class for CSS3 animation out.', 'wp-posts-carousel'),
            ),
            array(
                'name' => 'auto_width',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Auto width', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
            array(
                'name' => 'auto_height',
                'type' => 'radio',
                'default_value' => false,
                'label' => __('Auto height', 'wp-posts-carousel'),
                'responsive_option' => true,
            ),
        );

        return apply_filters('wp_posts_carousel_carousel_options', $carousel_options);
    }


    public static function get_carousel_breakpoint_item_options()
    {
        $carousel_options = self::get_carousel_options();
        $new_carousel_options = array();

        if (!empty($carousel_options)) {
            foreach ($carousel_options as $carousel => $carousel_fields) {
                if (!empty($carousel_fields)) {
                    foreach ($carousel_fields as $field => $options) {
                        if (array_key_exists('responsive_option', $options)) {
                            $new_carousel_options[$carousel][$field] = $options;
                        }
                    }
                }
            }
        }

        return apply_filters('wp_posts_carousel_carousel_breakpoint_item_options', $new_carousel_options);
    }

    public static function get_tooltip($text = null, $type = 'help')
    {
        if ($text == null) {
            return null;
        }

        if (in_array($type, array('help', 'warning'))) {
            switch ($type) {
                case 'warning':
                    $type = 'warning';
                    break;
                case 'help':
                default:
                    $type = 'editor-help';
                    break;
            }
        }
        return '<a href="" title="' . esc_html($text) . '" class="cci-wpc-tooltip tooltip-' . esc_html($type) . '"><span class="dashicons dashicons-' . esc_attr($type) . '" title="' . __('Hint', 'wp-posts-carousel') . '"></span></a>';
    }

    public static function get_tip($text = null, $type = 'help')
    {
        if ($text == null || !in_array($type, array('help', 'warning'))) {
            return null;
        }
        return '<p class="cci-wpc-tip tip-' . esc_html($type) . '">' . $text . '</p>';
    }

    public static function parse_breakpoints($params)
    {
        if (!$params) {
            return null;
        }
        $out                 = '';
        $plugin_options      = get_option('wp-posts-carousel_options');
        $breakpoints         = array();
        $default_breakpoints = '320:{items:2},480:{items:3},768:{items:3},1024:{items:4},1200:{items:4}';

        $items_to_show = array();

        if (array_key_exists('items_to_show', $params)) {
            $items_to_show = $params['items_to_show'];
        } elseif (array_key_exists('breakpoints', $params)) {
            $items_to_show = $params['breakpoints'];
        }

        if (!empty($items_to_show)) {
            if (is_string($items_to_show) && $items_to_show !== '') {
                $data = explode(',', $items_to_show);
                foreach ($data as $points) {
                    preg_match('/(\d+):(\d+)/', $points, $matches);

                    if (array_key_exists(1, $matches) && array_key_exists(2, $matches)) {
                        $breakpoints[$matches[1]] = $matches[2];
                    }
                }
            } elseif (is_array($items_to_show) && !empty($items_to_show)) {
                $breakpoints = $items_to_show;
            }

            ksort($breakpoints);

            if (count($breakpoints) > 0) {
                foreach ($breakpoints as $width => $items) {
                    if (is_array($items) && isset($items['items'])) {
                        $items = $items['items'];
                    }

                    if (intval($width) > 0 && intval($items) > 0) {
                        $out .= ',' . intval($width) . ':{items:' . intval($items) . '}';
                    }
                }
            }
        }

        if ($out !== '') {
            return $out;
        } else {
            return $default_breakpoints;
        }
    }

    public static function get_breakpoints()
    {
        $breakpoints = array();
        $defaults    = self::get_carousel_default_params();

        $items_to_show = $defaults['carousel_options']['breakpoints'];

        if (!empty($items_to_show)) {
            ksort($items_to_show);

            foreach ($items_to_show as $width => $items) {
                if ($width < 768) {
                    $breakpoints['mobile'][$width] = $items;
                } elseif ($width < 1024) {
                    $breakpoints['tablet'][$width] = $items;
                } else {
                    $breakpoints['laptop'][$width] = $items;
                }
            }
        }
        return $breakpoints;
    }

    public static function get_carousels()
    {
        // query args
        $args = array(
            'post_type'           => 'wp-posts-carousel',
            'ignore_sticky_posts' => true,
            'orderby'             => 'value',
            'order'               => 'desc',
            'post_status'         => array('publish'),
        );

        $args = apply_filters('wp_posts_carousel_carousels_in_admin_query', $args);

        // get the result
        $result = new WP_Query($args);
        return $result->posts;
    }

    /**
     * get carousel
     */
    public static function get_carousel($id)
    {
        if (!$id) {
            return null;
        }

        // query args
        $args = array(
            'post_type'           => 'wp-posts-carousel',
            'ignore_sticky_posts' => true,
            'orderby'             => 'value',
            'order'               => 'desc',
            'post_status'         => array('publish'),
            'p'                   => $id,
        );

        // get the result
        $result = new WP_Query($args);

        if ($result->found_posts === 1) {
            return reset($result->posts);
        } else {
            return null;
        }
    }

    /**
     * get carousel params
     */
    public static function get_carousel_params($id)
    {
        $carousel_params = array();

        if (!$id) {
            return $carousel_params;
        }

        $post_meta = get_post_meta($id, 'wp_posts_carousel');

        if (!empty($post_meta) && array_key_exists(0, $post_meta)) {
            $carousel_params = (array) json_decode($post_meta[0]);
        }

        return $carousel_params;
    }

    /**
     * debug message
     */
    public static function debug_message($msg, $is_comment = false)
    {
        $out = null;

        if ($is_comment == true) {
            $out .= "\r\n";
            $out .= "/**\r\n";
            $out .= "*\r\n";
            $out .= "* WP Posts Carousel DEBUG MESSAGE\r\n";
            $out .= '* version: ' . WP_POSTS_CAROUSEL_VERSION . "\r\n";
            $out .= '* message: ' . $msg . "\r\n";
            $out .= "*\r\n";
            $out .= "*/\r\n";
        }

        return $out;
    }
}
