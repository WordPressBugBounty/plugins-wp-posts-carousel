<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_Posts_Carousel_Features
{
    public static function all()
    {
        $features = array(
            'shortcode' => self::feature(
                __('Shortcode rendering', 'wp-posts-carousel'),
                __('Render carousels with the wp_posts_carousel shortcode.', 'wp-posts-carousel'),
                true,
                'core'
            ),
            'php_renderer' => self::feature(
                __('PHP renderer', 'wp-posts-carousel'),
                __('Server-rendered carousel markup and fallback output.', 'wp-posts-carousel'),
                true,
                'core'
            ),
            'template_registry' => self::feature(
                __('Template registry', 'wp-posts-carousel'),
                __('Manifest-based templates loaded from the plugin, theme, uploads, or add-ons.', 'wp-posts-carousel'),
                true,
                'core'
            ),
            'query_builder' => self::feature(
                __('Query builder', 'wp-posts-carousel'),
                __('Build carousel items from post types, taxonomies, and manual selections.', 'wp-posts-carousel'),
                true,
                'core'
            ),
            'manual_curation' => self::feature(
                __('Manual curation', 'wp-posts-carousel'),
                __('Manually order selected posts in a carousel.', 'wp-posts-carousel'),
                true,
                'core'
            ),
            'analytics' => self::feature(
                __('Basic analytics', 'wp-posts-carousel'),
                __('Show local carousel configuration insights without visitor tracking.', 'wp-posts-carousel'),
                true,
                'core'
            ),
            'react_frontend' => self::feature(
                __('React frontend renderer', 'wp-posts-carousel'),
                __('Lazy-mounted React rendering and enhancements for public carousel output.', 'wp-posts-carousel'),
                true,
                'core'
            ),
        );

        $base_features = $features;
        $features = apply_filters('wp_posts_carousel_features', $features);

        if (!is_array($features)) {
            $features = array();
        }

        foreach ($base_features as $key => $feature) {
            if (!isset($features[$key])) {
                $features[$key] = $feature;
            }
        }

        foreach ($features as $key => $feature) {
            $features[$key] = self::normalize($key, $feature);
        }

        return self::enforce_feature_gates($features);
    }

    public static function enabled($key)
    {
        $key = sanitize_key($key);
        $features = self::all();

        return isset($features[$key]) && !empty($features[$key]['enabled']);
    }

    public static function public_data()
    {
        $features = self::all();

        foreach ($features as $key => $feature) {
            unset($features[$key]['internal']);
        }

        return $features;
    }

    public static function template_status($template)
    {
        $feature = '';

        if (!empty($template['feature'])) {
            $feature = $template['feature'];
        } elseif (!empty($template['pro'])) {
            $feature = 'pro_templates';
        }

        if (!$feature) {
            return array(
                'locked' => false,
                'feature' => '',
                'message' => '',
            );
        }

        $features = self::all();
        $enabled = isset($features[$feature]) && !empty($features[$feature]['enabled']);

        $message = '';
        if (!$enabled) {
            /* translators: %s: required product feature name. */
            $message = sprintf(__('Requires feature: %s', 'wp-posts-carousel'), isset($features[$feature]['label']) ? $features[$feature]['label'] : $feature);
        }

        if (!$enabled && isset($features[$feature]['plan']) && $features[$feature]['plan'] === 'paid') {
            $message = __('Requires an active WP Posts Carousel Pro license for this domain.', 'wp-posts-carousel');
        }

        return array(
            'locked' => !$enabled,
            'feature' => $feature,
            'message' => $message,
        );
    }

    public static function license_allows($feature_key, $context = array())
    {
        $feature_key = sanitize_key($feature_key);

        return (bool) apply_filters(
            'wp_posts_carousel_license_allows_feature',
            false,
            $feature_key,
            is_array($context) ? $context : array()
        );
    }

    public static function paid_feature_keys()
    {
        $reserved = array(
            'pro_templates',
            'analytics_pro',
            'elementor_integration',
            'woocommerce_advanced',
            'commerce_luxe_template',
            'story_templates',
            'video_showcase_template',
            'video_slides',
            'autoplay_progress_indicator',
            'multisite',
        );
        $additional = (array) apply_filters('wp_posts_carousel_paid_feature_keys', array());

        return array_values(array_unique(array_filter(array_map(
            'sanitize_key',
            array_merge($reserved, $additional)
        ))));
    }

    private static function feature($label, $description, $enabled, $source)
    {
        return array(
            'label' => $label,
            'description' => $description,
            'enabled' => (bool) $enabled,
            'source' => $source,
        );
    }

    private static function normalize($key, $feature)
    {
        if (!is_array($feature)) {
            $feature = array('enabled' => (bool) $feature);
        }

        $key = sanitize_key($key);
        $source = isset($feature['source']) ? sanitize_key($feature['source']) : 'custom';
        $plan = isset($feature['plan']) ? sanitize_key($feature['plan']) : (self::is_paid_feature_key($key) ? 'paid' : '');
        $required_features = self::normalize_requirements(isset($feature['requiredFeatures']) ? $feature['requiredFeatures'] : (isset($feature['required_features']) ? $feature['required_features'] : (isset($feature['requires']) ? $feature['requires'] : array())));

        return array(
            'key' => $key,
            'label' => isset($feature['label']) ? sanitize_text_field($feature['label']) : $key,
            'description' => isset($feature['description']) ? sanitize_text_field($feature['description']) : '',
            'enabled' => !empty($feature['enabled']),
            'source' => $source,
            'expires' => isset($feature['expires']) ? sanitize_text_field($feature['expires']) : '',
            'plan' => $plan,
            'requiresPro' => !empty($feature['requiresPro']) || $plan === 'paid' || $plan === 'pro',
            'requiredFeatures' => $required_features,
            'required_features' => $required_features,
            'internal' => isset($feature['internal']) ? $feature['internal'] : array(),
        );
    }

    private static function enforce_feature_gates($features)
    {
        foreach ($features as $key => $feature) {
            if (!self::feature_requires_pro($key, $feature)) {
                continue;
            }

            $features[$key]['plan'] = 'paid';
            $features[$key]['requiresPro'] = true;

            if (!self::license_allows($key, array('context' => 'features'))) {
                $features[$key]['enabled'] = false;
            }
        }

        foreach ($features as $key => $feature) {
            $requirements = isset($feature['requiredFeatures']) ? (array) $feature['requiredFeatures'] : array();
            foreach ($requirements as $requirement) {
                $requirement = sanitize_key($requirement);
                if (!$requirement) {
                    continue;
                }

                $requirement_enabled = !empty($features[$requirement]['enabled'])
                    || self::license_allows($requirement, array('context' => 'features', 'feature' => $key));

                if (!$requirement_enabled) {
                    $features[$key]['enabled'] = false;
                    break;
                }
            }
        }

        return $features;
    }

    private static function feature_requires_pro($key, $feature)
    {
        $key = sanitize_key($key);
        if (self::is_paid_feature_key($key)) {
            return true;
        }

        $plan = isset($feature['plan']) ? sanitize_key($feature['plan']) : '';
        if (!empty($feature['requiresPro']) || $plan === 'paid' || $plan === 'pro') {
            return true;
        }

        foreach ((array) (isset($feature['requiredFeatures']) ? $feature['requiredFeatures'] : array()) as $requirement) {
            if (self::is_paid_feature_key($requirement)) {
                return true;
            }
        }

        return false;
    }

    private static function is_paid_feature_key($key)
    {
        return in_array(sanitize_key($key), self::paid_feature_keys(), true);
    }

    private static function normalize_requirements($requirements)
    {
        if (is_string($requirements)) {
            $requirements = preg_split('/[,|]/', $requirements);
        }

        if (!is_array($requirements)) {
            $requirements = array();
        }

        return array_values(array_unique(array_filter(array_map('sanitize_key', $requirements))));
    }
}
