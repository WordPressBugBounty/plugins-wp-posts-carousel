<?php

/**
 * Author: Marcin Gierada
 * Author URI: https://coolcatideas.com/
 * Author Email: info@coolcatideas.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if (! defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

/**
 * WP_Posts_Carousel_Admin class
 */
class WP_Posts_Carousel_Admin
{
  /**
   * Constructor
   */
  public function __construct()
  {
    add_action('init', array($this, 'includes'));


    add_action('admin_enqueue_scripts', array($this, 'register_scripts'), 99);
    add_action('admin_head', array($this, 'wp_head'));
  }

  public function includes()
  {
    include_once 'class-admin-carousels.php';
    include_once 'class-admin-menu.php';
    include_once 'class-admin-plugins-page.php';
  }

  /**
   * Add the plugin url in the head tag
   */
  public function wp_head()
  {
    echo '<script>var wp_posts_carousel_url="' . esc_js(WP_POSTS_CAROUSEL_DIR_URL) . '";</script>';
  }

  /**
   * Register scripts and styles
   */
  public function register_scripts()
  {
    if (! $this->should_load_editor_preview_assets()) {
      return;
    }

    global $WP_Posts_Carousel;

    wp_enqueue_media();
    $this->enqueue_preview_assets();

    $marketplace_feed_endpoint = $this->marketplace_proxy_endpoint(array('limit' => 5));
    $marketplace_initial_feed = $this->marketplace_initial_feed(array('limit' => 5));

    $admin_style_asset = 'assets/css/admin-v2.css';

    if (file_exists(WP_POSTS_CAROUSEL_DIR_PATH . $admin_style_asset)) {
      wp_register_style(
        'wp-posts-carousel-admin-v2',
        WP_POSTS_CAROUSEL_DIR_URL . $admin_style_asset,
        array(),
        $this->asset_version($admin_style_asset)
      );
      wp_enqueue_style('wp-posts-carousel-admin-v2');
    }

    wp_register_script('wp-posts-carousel-js-react', WP_POSTS_CAROUSEL_DIR_URL . 'assets/js/admin-v2.js', array('wp-i18n', 'wp-element'), $this->asset_version('assets/js/admin-v2.js'), true);
    wp_localize_script(
      'wp-posts-carousel-js-react',
      'wpPostsCarouselPluginData',
      array(
        'pluginUrl' => 'admin.php?page=wp-posts-carousel',
        'assetsUrl' => esc_url_raw(WP_POSTS_CAROUSEL_DIR_URL),
        'restRoot' => esc_url_raw(rest_url()),
        'pluginVersion' => WP_POSTS_CAROUSEL_VERSION,
        'adminDate' => array(
          'locale' => function_exists('get_user_locale') ? get_user_locale() : get_locale(),
          'dateFormat' => (string) get_option('date_format'),
          'timeFormat' => (string) get_option('time_format'),
          'dateTimeFormat' => trim((string) get_option('date_format') . ' ' . (string) get_option('time_format')),
          'timeZone' => function_exists('wp_timezone_string') ? wp_timezone_string() : (string) get_option('timezone_string'),
        ),
        'features' => class_exists('WP_Posts_Carousel_Features') ? WP_Posts_Carousel_Features::public_data() : array(),
        'permalinkStructure' => get_option('permalink_structure'),
        'nonce' => wp_create_nonce('wp_rest'),
        'canManageSettings' => current_user_can('manage_wp_posts_carousel_options'),
        'isPro' => (bool) apply_filters('wp_posts_carousel_is_pro', false),
        'license' => apply_filters(
          'wp_posts_carousel_license_data',
          array(
            'status' => 'free',
            'label' => __('Free', 'wp-posts-carousel'),
            'expires' => '',
          )
        ),
        'marketplaceFeedEndpoint' => esc_url_raw($marketplace_feed_endpoint),
        'newsEndpoint' => esc_url_raw($marketplace_feed_endpoint),
        'templateStoreEndpoint' => esc_url_raw($marketplace_feed_endpoint),
        'integrationStoreEndpoint' => esc_url_raw($marketplace_feed_endpoint),
        'marketplaceRetryDelay' => 120000,
        'marketplaceInitialFeed' => $marketplace_initial_feed,
        'documentationUrl' => esc_url_raw(apply_filters('wp_posts_carousel_documentation_url', '')),
        'upgradeUrl' => esc_url_raw(apply_filters(
          'wp_posts_carousel_upgrade_url',
          class_exists('WP_Posts_Carousel_API') ? WP_Posts_Carousel_API::MARKETPLACE_PRODUCT_URL : 'https://coolcatideas.com/products/wp-posts-carousel-all-in-one'
        )),
        'reviewFeedbackEndpoint' => esc_url_raw(rest_url('wp-posts-carousel/v1/admin/review-feedback')),
        'wordpressReviewUrl' => esc_url_raw(apply_filters('wp_posts_carousel_wordpress_review_url', 'https://wordpress.org/support/plugin/wp-posts-carousel/reviews/#new-post')),
        'reviewEndpoint' => esc_url_raw(rest_url('wp-posts-carousel/v1/admin/review-feedback')),
        'reviewUrl' => esc_url_raw(apply_filters('wp_posts_carousel_review_url', 'https://wordpress.org/support/plugin/wp-posts-carousel/reviews/#new-post')),
        'i18n' => array(
          'actions' => __('Actions', 'wp-posts-carousel'),
          'addNew' => __('Add New', 'wp-posts-carousel'),
          'dashboard' => __('Dashboard', 'wp-posts-carousel'),
          'date' => __('Date', 'wp-posts-carousel'),
          'delete' => __('Delete', 'wp-posts-carousel'),
          'draft' => __('Draft', 'wp-posts-carousel'),
          'duplicate' => __('Duplicate', 'wp-posts-carousel'),
          'edit' => __('Edit', 'wp-posts-carousel'),
          'reviewConsentContent' => __('I agree to send this rating and review to Cool Cat Ideas for moderation and possible publication on the product page. The review will not be published automatically.', 'wp-posts-carousel'),
          'loading' => __('Loading...', 'wp-posts-carousel'),
          'menu' => __('Menu', 'wp-posts-carousel'),
          'published' => __('Published', 'wp-posts-carousel'),
          'settings' => __('Settings', 'wp-posts-carousel'),
          'status' => __('Status', 'wp-posts-carousel'),
          'title' => __('Title', 'wp-posts-carousel'),
          'tools' => __('Tools', 'wp-posts-carousel'),
        ),
      )
    );

    wp_enqueue_script('wp-posts-carousel-js-react');

    wp_set_script_translations('wp-posts-carousel-js-react', 'wp-posts-carousel', WP_POSTS_CAROUSEL_DIR_PATH . 'languages');
    do_action('wp_posts_carousel_in_admin_after_enqueue_scripts', array());
  }

  private function is_carousel_admin_page()
  {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    return $screen && $screen->id === 'toplevel_page_wp-posts-carousel';
  }

  private function should_load_editor_preview_assets()
  {
    if ($this->is_carousel_admin_page()) {
      return true;
    }

    if (function_exists('is_block_editor') && is_block_editor()) {
      return true;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && in_array($screen->base, array('post', 'post-new', 'page', 'widget'), true)) {
      return true;
    }

    $editor_params = array('elementor-preview', 'elementor-editor', 'elementor-preview-nonce');
    foreach ($editor_params as $param_name) {
      if (!empty($_GET[$param_name]) || !empty($_POST[$param_name])) {
        return true;
      }
    }

    if (
      isset($_REQUEST['action']) &&
      is_string($_REQUEST['action']) &&
      false !== strpos(sanitize_text_field(wp_unslash($_REQUEST['action'])), 'elementor')
    ) {
      return true;
    }

    return false;
  }

  private function asset_version($asset)
  {
    $path = WP_POSTS_CAROUSEL_DIR_PATH . ltrim($asset, '/');

    return file_exists($path) ? (string) filemtime($path) : WP_POSTS_CAROUSEL_VERSION;
  }

  private function enqueue_preview_assets()
  {
    $settings = class_exists('WP_Posts_Carousel_Settings')
      ? WP_Posts_Carousel_Settings::get_settings()
      : array();
    $load_owl_assets = (bool) apply_filters('wp_posts_carousel_load_owl_assets', !empty($settings['load_owl_assets']), $settings);
    $load_owl_css = (bool) apply_filters('wp_posts_carousel_load_owl_css', !empty($settings['load_owl_css']), $settings);
    $load_mousewheel_asset = (bool) apply_filters('wp_posts_carousel_load_mousewheel_asset', !empty($settings['load_mousewheel_asset']), $settings);
    $owl_script_handle = $this->asset_handle(apply_filters('wp_posts_carousel_owl_script_handle', 'owl.carousel', $settings));
    $mousewheel_script_handle = $this->asset_handle(apply_filters('wp_posts_carousel_mousewheel_script_handle', 'jquery-mousewheel', $settings));
    $owl_style_handle = $this->asset_handle(apply_filters('wp_posts_carousel_owl_style_handle', 'owl.carousel.style', $settings));
    $swiper_source = sanitize_key(apply_filters('wp_posts_carousel_swiper_source', $settings['swiper_source'] ?? 'bundled', $settings));
    $swiper_source = in_array($swiper_source, array('bundled', 'global'), true) ? $swiper_source : 'bundled';
    $load_owl_assets = true;
    $load_owl_css = true;

    if ($load_owl_assets && $owl_script_handle) {
      wp_register_script($owl_script_handle, WP_POSTS_CAROUSEL_DIR_URL . 'libraries/owl.carousel/owl.carousel.min.js', array('jquery'), '2.3.4', true);
      wp_enqueue_script($owl_script_handle);
    }

    if ($load_mousewheel_asset && $mousewheel_script_handle) {
      wp_register_script($mousewheel_script_handle, WP_POSTS_CAROUSEL_DIR_URL . 'libraries/owl.carousel/jquery.mousewheel.min.js', array('jquery'), '3.1.12', true);
      wp_enqueue_script($mousewheel_script_handle);
    }

    if ($load_owl_css && $owl_style_handle) {
      wp_register_style($owl_style_handle, WP_POSTS_CAROUSEL_DIR_URL . 'libraries/owl.carousel/assets/owl.carousel.min.css', array(), $this->asset_version('libraries/owl.carousel/assets/owl.carousel.min.css'));
      wp_enqueue_style($owl_style_handle);
    }

    wp_enqueue_style(
      'wp-posts-carousel-frontend',
      WP_POSTS_CAROUSEL_DIR_URL . 'assets/css/frontend.css',
      array(),
      $this->asset_version('assets/css/frontend.css')
    );

    $this->enqueue_icon_assets($settings);
    $this->enqueue_accessibility_styles();

    wp_register_script('wp-posts-carousel-frontend-loader', WP_POSTS_CAROUSEL_DIR_URL . 'assets/js/frontend-loader.js', array(), $this->asset_version('assets/js/frontend-loader.js'), true);
    wp_localize_script(
      'wp-posts-carousel-frontend-loader',
      'wpPostsCarouselRuntimeConfig',
      apply_filters(
        'wp_posts_carousel_frontend_runtime_config',
        array(
          'assets' => array(
            'swiperSource' => $swiper_source,
            'loadSwiperCss' => (bool) apply_filters('wp_posts_carousel_load_swiper_css', !empty($settings['load_swiper_css']), $settings),
          ),
        ),
        $settings
      )
    );
    wp_enqueue_script('wp-posts-carousel-frontend-loader');

    if (class_exists('WP_Posts_Carousel_Templates')) {
      foreach (WP_Posts_Carousel_Templates::discover_templates() as $template) {
        if (empty($template['name']) || empty($template['style']) || empty($template['style_url']) || !file_exists($template['style'])) {
          continue;
        }

        $style_version = (string) filemtime($template['style']);

        wp_enqueue_style(
          'wp-posts-carousel-template-' . sanitize_key($template['name']),
          $template['style_url'],
          array(),
          $style_version ? $style_version : (!empty($template['version']) ? $template['version'] : WP_POSTS_CAROUSEL_VERSION)
        );
      }
    }

    do_action('wp_posts_carousel_admin_preview_assets', $settings);
  }

  private function enqueue_icon_assets($settings)
  {
    if (class_exists('WP_Posts_Carousel_Icons')) {
      WP_Posts_Carousel_Icons::enqueue_assets(
        $settings,
        array(
          'context' => 'admin-preview',
        )
      );
    }
  }

  private function enqueue_accessibility_styles()
  {
    wp_register_style('wp-posts-carousel-accessibility', false, array(), WP_POSTS_CAROUSEL_VERSION);
    wp_enqueue_style('wp-posts-carousel-accessibility');
    wp_add_inline_style(
      'wp-posts-carousel-accessibility',
      '.cci-wpc-carousel .screen-reader-text,.cci-wpc-render-preview .screen-reader-text,.cci-wpc-react-controls .screen-reader-text{clip:rect(1px,1px,1px,1px);clip-path:inset(50%);height:1px;margin:-1px;overflow:hidden;padding:0;position:absolute;width:1px;word-wrap:normal;}'
    );
  }

  private function asset_handle($handle)
  {
    return preg_replace('/[^A-Za-z0-9_.-]/', '', (string) $handle);
  }

  private function marketplace_proxy_endpoint($args = array())
  {
    $url = trailingslashit(rest_url('wp-posts-carousel/v1/admin/plugin-feed'));

    return add_query_arg(
      array_merge(
        array(
          'locale' => $this->marketplace_locale(),
        ),
        $args
      ),
      $url
    );
  }

  private function marketplace_initial_feed($args = array())
  {
    $locale = $this->marketplace_locale();
    $limit = isset($args['limit']) ? absint($args['limit']) : 5;
    $limit = $limit ? min($limit, 20) : 5;
    $cache_key = $this->marketplace_cache_key($locale, $limit);
    $stale_cache_key = $cache_key . '_stale';
    $cached = get_transient($cache_key);

    if (WP_Posts_Carousel_API::is_valid_marketplace_feed($cached)) {
      return $this->prepare_marketplace_initial_feed($cached, 'bootstrap-cache', true, false);
    }

    $stale = get_transient($stale_cache_key);

    if (WP_Posts_Carousel_API::is_valid_marketplace_feed($stale)) {
      return $this->prepare_marketplace_initial_feed($stale, 'bootstrap-stale-cache', true, true);
    }

    $remote = $this->fetch_marketplace_initial_feed($locale, $limit);

    if (is_array($remote)) {
      set_transient($cache_key, $remote, $this->marketplace_cache_ttl());
      set_transient($stale_cache_key, $remote, $this->marketplace_stale_cache_ttl());

      return $this->prepare_marketplace_initial_feed($remote, 'bootstrap-remote', false, false);
    }

    return null;
  }

  private function prepare_marketplace_initial_feed($feed, $source, $cached, $stale)
  {
    $feed = is_array($feed) ? $feed : array();
    $feed = WP_Posts_Carousel_API::normalize_marketplace_feed_urls($feed);
    $feed['_meta'] = array_merge(
      isset($feed['_meta']) && is_array($feed['_meta']) ? $feed['_meta'] : array(),
      array(
        'ok' => !$stale,
        'source' => $source,
        'cached' => (bool) $cached,
        'stale' => (bool) $stale,
        'initial' => true,
        'retryAfter' => 120,
      )
    );

    return $feed;
  }

  private function fetch_marketplace_initial_feed($locale, $limit)
  {
    foreach ($this->marketplace_remote_endpoints($locale, $limit) as $endpoint) {
      $response = wp_remote_get(
        $endpoint,
        array(
          'timeout' => 2,
          'user-agent' => 'WP Posts Carousel product feed',
          'headers' => array(
            'Accept' => 'application/json',
          ),
        )
      );

      if (is_wp_error($response)) {
        continue;
      }

      $code = (int) wp_remote_retrieve_response_code($response);
      $data = json_decode(wp_remote_retrieve_body($response), true);

      if ($code >= 200 && $code < 300 && WP_Posts_Carousel_API::is_valid_marketplace_feed($data)) {
        $data = WP_Posts_Carousel_API::normalize_marketplace_feed_urls($data);
        $data['_meta'] = array_merge(
          isset($data['_meta']) && is_array($data['_meta']) ? $data['_meta'] : array(),
          array(
            'ok' => true,
            'source' => 'remote',
            'cached' => false,
            'stale' => false,
            'fetchedAt' => current_time('mysql'),
            'retryAfter' => 120,
          )
        );

        return $data;
      }
    }

    return null;
  }

  private function marketplace_remote_endpoints($locale, $limit)
  {
    $bases = array_unique(array_filter(array(
      $this->configured_marketplace_api_base(),
      class_exists('WP_Posts_Carousel_API') ? WP_Posts_Carousel_API::MARKETPLACE_API_BASE : 'https://coolcatideas.com',
    )));

    return array_map(
      function ($base) use ($locale, $limit) {
        $product_slug = class_exists('WP_Posts_Carousel_API')
          ? WP_Posts_Carousel_API::MARKETPLACE_PRODUCT_SLUG
          : 'wp-posts-carousel-all-in-one';
        $url = untrailingslashit($base) . '/api/products/' . rawurlencode($product_slug) . '/plugin-feed/';

        return add_query_arg(
          array(
            'locale' => $locale,
            'limit' => $limit,
          ),
          $url
        );
      },
      $bases
    );
  }

  private function configured_marketplace_api_base()
  {
    $constant = class_exists('WP_Posts_Carousel_API')
      ? WP_Posts_Carousel_API::MARKETPLACE_API_BASE_OVERRIDE
      : 'WP_POSTS_CAROUSEL_MARKETPLACE_API_BASE';
    $base = '';

    if (defined($constant)) {
      $base = constant($constant);
    }

    if (!$base) {
      $environment_base = getenv($constant);

      if (is_string($environment_base) && $environment_base !== '') {
        $base = $environment_base;
      }
    }

    $base = apply_filters('wp_posts_carousel_marketplace_api_base', $base);
    $base = is_string($base) ? trim($base) : '';

    return $base ? esc_url_raw($base) : '';
  }

  private function marketplace_cache_key($locale, $limit)
  {
    return 'wpc_marketplace_feed_' . md5($locale . '|' . (int) $limit);
  }

  private function marketplace_cache_ttl()
  {
    return class_exists('WP_Posts_Carousel_API') ? WP_Posts_Carousel_API::MARKETPLACE_CACHE_TTL : 600;
  }

  private function marketplace_stale_cache_ttl()
  {
    return class_exists('WP_Posts_Carousel_API') ? WP_Posts_Carousel_API::MARKETPLACE_STALE_CACHE_TTL : DAY_IN_SECONDS;
  }

  private function marketplace_locale()
  {
    $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
    $locale = strtolower(substr((string) $locale, 0, 2));
    $locale = in_array($locale, array('pl', 'en'), true) ? $locale : 'en';

    return sanitize_key(apply_filters('wp_posts_carousel_marketplace_locale', $locale));
  }
}

return new WP_Posts_Carousel_Admin();
