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
 * WP_Posts_Carousel_Admin class
 */
class WP_Posts_Carousel_API
{
  const MARKETPLACE_API_BASE = 'https://coolcatideas.com';
  const MARKETPLACE_API_BASE_OVERRIDE = 'WP_POSTS_CAROUSEL_MARKETPLACE_API_BASE';
  const MARKETPLACE_PRODUCT_SLUG = 'wp-posts-carousel-all-in-one';
  const MARKETPLACE_PRODUCT_URL = 'https://coolcatideas.com/products/wp-posts-carousel-all-in-one';
  const MARKETPLACE_CACHE_TTL = 600;
  const MARKETPLACE_STALE_CACHE_TTL = DAY_IN_SECONDS;
  protected $site_url = 'http://demuo.wp-api.org/wp-json/wp/v2/';

  /**
   * Constructor
   */
  public function __construct()
  {

    add_action('rest_api_init', array($this, 'prefix_register_my_comment_route'));
  }


  function prefix_register_my_comment_route()
  {
    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/posts',
      array(
        array(
          'methods'  => 'post',
          'callback' => array($this, 'get_custom_posts'),
          'permission_callback' => $this->checkPermissions(['read_wp_posts_carousel', 'create_wp_posts_carousels']),
        ),
      )
    );

    // register_rest_route(
    // 	'wp-posts-carousel/v1/admin',
    // 	'/categories',
    // 	array(
    // 		array(
    // 			'methods'  => 'post',
    // 			'callback' => array($this, 'get_custom_categories'),
    // 			'permission_callback' => $this->checkPermissions(['read_wp_posts_carousel', 'create_wp_posts_carousels']),
    // 		),
    // 	)
    // );

    // register_rest_route(
    // 	'wp-posts-carousel/v1/admin',
    // 	'/tags',
    // 	array(
    // 		array(
    // 			'methods'  => 'post',
    // 			'callback' => array($this, 'get_custom_tags'),
    // 			'permission_callback' => $this->checkPermissions(['read_wp_posts_carousel', 'create_wp_posts_carousels']),
    // 		),
    // 	)
    // );



    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/terms',
      array(
        array(
          'methods'  => 'post',
          'callback' => array($this, 'get_custom_terms'),
          'permission_callback' => $this->checkPermissions(['read_wp_posts_carousel', 'create_wp_posts_carousels']),
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/defaultSettings',
      array(
        array(
          'methods'  => 'GET',
          'callback' => array($this, 'get_default_settings'),
          'permission_callback' => $this->checkPermissions(['read_wp_posts_carousel', 'create_wp_posts_carousels'])
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/templates',
      array(
        array(
          'methods'  => 'GET',
          'callback' => array($this, 'get_templates'),
          'permission_callback' => $this->checkPermissions('read_wp_posts_carousel')
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/plugin-feed',
      array(
        array(
          'methods'  => 'GET',
          'callback' => array($this, 'get_plugin_feed'),
          'permission_callback' => $this->checkPermissions('read_wp_posts_carousel')
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/review-feedback',
      array(
        array(
          'methods'  => 'POST',
          'callback' => array($this, 'submit_review_feedback'),
          'permission_callback' => $this->checkPermissions('read_wp_posts_carousel')
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/postsFromQuery',
      array(
        array(
          'methods'  => 'POST',
          'callback' => array($this, 'get_posts_from_query'),
          'permission_callback' => $this->checkPermissions(['read_wp_posts_carousel', 'create_wp_posts_carousels'])
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/settings',
      array(
        array(
          'methods'  => 'GET',
          'callback' => array($this, 'get_settings'),
          'permission_callback' => $this->checkPermissions('manage_wp_posts_carousel_options')
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/settings',
      array(
        array(
          'methods'  => 'POST',
          'callback' => array($this, 'update_settings'),
          'permission_callback' => $this->checkPermissions('manage_wp_posts_carousel_options')
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/diagnostics',
      array(
        array(
          'methods'  => 'GET',
          'callback' => array($this, 'get_diagnostics'),
          'permission_callback' => $this->checkPermissions('manage_wp_posts_carousel_options')
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/carousel',
      array(
        array(
          'methods'  => 'POST',
          'callback' => array($this, 'create_carousel'),
          'permission_callback' => $this->checkPermissions('create_wp_posts_carousels')
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/carousel/(?P<id>\d+)',
      array(
        array(
          'methods'  => 'GET',
          'callback' => array($this, 'get_carousel'),
          'permission_callback' => $this->checkPermissions('read_wp_posts_carousel'),
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/carousel/(?P<id>\d+)/duplicate',
      array(
        array(
          'methods'  => 'POST',
          'callback' => array($this, 'duplicate_carousel'),
          'permission_callback' => $this->checkPermissions(array('read_wp_posts_carousel', 'create_wp_posts_carousels')),
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/carousel/(?P<id>\d+)/preview',
      array(
        array(
          'methods'  => 'GET',
          'callback' => array($this, 'get_carousel_preview'),
          'permission_callback' => $this->checkPermissions('read_wp_posts_carousel'),
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/carousel/preview',
      array(
        array(
          'methods'  => 'POST',
          'callback' => array($this, 'preview_carousel_draft'),
          'permission_callback' => $this->checkPermissions(array('read_wp_posts_carousel', 'create_wp_posts_carousels')),
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/carousel/(?P<id>\d+)',
      array(
        array(
          'methods'  => 'PUT',
          'callback' => array($this, 'update_carousel'),
          'permission_callback' => $this->checkPermissions('edit_wp_posts_carousel'),
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/carousel/(?P<id>\d+)',
      array(
        array(
          'methods'  => 'DELETE',
          'callback' => array($this, 'delete_carousel'),
          'permission_callback' => $this->checkPermissions('delete_wp_posts_carousel'),
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1/admin',
      '/carousels',
      array(
        array(
          'methods'  => 'GET',
          'callback' => array($this, 'get_carousels'),
          'permission_callback' => $this->checkPermissions('read_wp_posts_carousel'),
        ),
      )
    );

    register_rest_route(
      'wp-posts-carousel/v1',
      '/carousel/(?P<id>\d+)',
      array(
        array(
          'methods'  => 'GET',
          'callback' => array($this, 'get_public_carousel'),
          'permission_callback' => '__return_true',
        ),
      )
    );
  }

  public function prepare_post_option($post)
  {
    $thumbnail_id = get_post_thumbnail_id($post);
    $post_options = array(
      'value' => strval($post->ID),
      'label' => $post->post_title,
      'post_type' => $post->post_type,
      'postType' => $post->post_type,
      'thumbnail' => $thumbnail_id ? get_the_post_thumbnail_url($post, 'thumbnail') : '',
      'thumbnail_alt' => $thumbnail_id ? get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) : '',
    );

    return apply_filters('wp_posts_carousel_prepare_post_option', $post_options, $post);
  }

  public function checkPermissions($permission)
  {
    if ($permission) {
      if (is_array($permission)) {
        foreach ($permission as $value) {
          if (!current_user_can($value)) {
            return '__return_false';
          }
        }
        return '__return_true';
      } else if (current_user_can($permission)) {
        return '__return_true';
      }
    }
    return '__return_false';
  }

  public function get_custom_posts(WP_REST_Request $request)
  {
    $post_types = array();
    $request_post_types = $request['post_types'];

    if (!$request_post_types && isset($request['post_selection_options']['post_types'])) {
      $request_post_types = $request['post_selection_options']['post_types'];
    }

    if ($request_post_types) {
      foreach ($request_post_types as $post_type) {
        if (is_array($post_type) && isset($post_type['value'])) {
          $post_types[] = sanitize_key($post_type['value']);
        } elseif (is_string($post_type)) {
          $post_types[] = sanitize_key($post_type);
        }
      }
    }

    if (empty($post_types)) {
      $post_types = array('post');
    }

    $args = array(
      'post_type' => $post_types,
      'orderby' => 'post_title'
    );

    if (isset($request['s']) && !empty($request['s'])) {
      $args['s'] = sanitize_text_field($request['s']);
    }

    $query = new WP_Query($args);
    $posts = $query->posts;

    $result = array_map(array($this, 'prepare_post_option'), $posts);
    wp_reset_postdata();

    return new WP_REST_Response($result, 200);
  }
  public function prepare_term_option($tag)
  {
    $post_options = array('value' => strval($tag->term_id), 'label' => $tag->name, 'post_type' => $tag->taxonomy);

    return apply_filters('wp_posts_carousel_prepare_term_option', $post_options, $tag);
  }

  public function get_custom_terms(WP_REST_Request $request)
  {
    if (!isset($request['post_type']) && !isset($request['taxonomy'])) {
      return false;
    }

    //$post_type = 	sanitize_text_field($request['post_type']);
    $taxonomy = sanitize_text_field($request['taxonomy']);

    $args = array(
      'taxonomy' => $taxonomy,
      'hide_empty' => false,
    );

    if (isset($request['s']) && !empty($request['s'])) {
      $args['s'] = sanitize_text_field($request['s']);
    }
    $terms = get_terms($args);

    $result = array_map(array($this, 'prepare_term_option'), $terms);
    wp_reset_postdata();

    return new WP_REST_Response($result, 200);
  }


  public function get_posts_from_query(WP_REST_Request $request)
  {
    $result = array();
    $config = WP_Posts_Carousel_Config::normalize($request);
    $query_config = $config;
    $query_config['source']['mode'] = 'query';
    $query_config['curation']['mode'] = 'query';
    $query_config['curation']['items'] = array();
    $args = WP_Posts_Carousel_Query_Builder::build($query_config);

    $loop = new WP_Query($args);
    global $post;

    while ($loop->have_posts()) {
      $loop->the_post();
      $result[] = (object) $this->prepare_post_option($post);
    }

    wp_reset_postdata();

    return new WP_REST_Response($result, 200);
  }

  public function get_public_carousel(WP_REST_Request $request)
  {
    $post_id = absint($request['id']);
    $instance_id = isset($request['instance_id']) ? sanitize_html_class($request['instance_id']) : '';
    $renderer = isset($request['renderer']) ? sanitize_key($request['renderer']) : '';
    $is_editor_context = sanitize_key((string) $request->get_param('context')) === 'edit'
      || sanitize_key((string) $request->get_param('editor_preview')) === '1';
    $data = WP_Posts_Carousel_Data::get(
      $post_id,
      array(
        'instance_id' => $instance_id ? $instance_id : 'wp-posts-carousel_' . $post_id . '-api',
      )
    );

    if (!$data) {
      return new WP_Error(
        'wp_posts_carousel_not_found',
        __('Carousel not found', 'wp-posts-carousel'),
        array('status' => 404)
      );
    }

    if (isset($data['config']) && is_array($data['config'])) {
      $data['html'] = $this->render_carousel_preview_html(
        $data['config'],
        array(
          'id' => $post_id,
          'index' => 0,
          'instance_id' => $instance_id,
          'renderer' => $renderer,
          'force_editor_renderer' => $is_editor_context,
        )
      );
    }

    return new WP_REST_Response($data, 200);
  }

  public function get_carousel_preview(WP_REST_Request $request)
  {
    $post_id = absint($request['id']);
    $config = WP_Posts_Carousel_Repository::get_config($post_id);
    $data = WP_Posts_Carousel_Data::get(
      $post_id,
      array(
        'context' => 'edit',
        'instance_id' => 'wp-posts-carousel_' . $post_id . '-admin-preview',
      )
    );

    if (!$data) {
      return new WP_Error(
        'wp_posts_carousel_preview_not_found',
        __('Carousel preview not found', 'wp-posts-carousel'),
        array('status' => 404)
      );
    }

    if ($config) {
      $data['html'] = $this->render_carousel_preview_html(
        $config,
        array(
          'id' => $post_id,
          'index' => 0,
          'instance_id' => 'wp-posts-carousel_' . $post_id . '-admin-preview',
          'force_editor_renderer' => true,
        )
      );
    }

    return new WP_REST_Response($data, 200);
  }

  public function preview_carousel_draft(WP_REST_Request $request)
  {
    $values = $request->get_json_params();

    if (!is_array($values)) {
      $values = $request->get_params();
    }

    $config = WP_Posts_Carousel_Config::normalize($values);
    $preview_args = array(
      'id' => isset($values['id']) ? absint($values['id']) : 0,
      'title' => isset($values['title']) && $values['title'] !== ''
        ? sanitize_text_field($values['title'])
        : __('Unsaved carousel preview', 'wp-posts-carousel'),
      'status' => isset($values['status']) ? sanitize_key($values['status']) : 'draft',
      'context' => 'preview',
      'instance_id' => 'wp-posts-carousel-admin-preview-' . time(),
    );

    $data = WP_Posts_Carousel_Data::from_config(
      $config,
      $preview_args
    );

    if (!$data) {
      return new WP_Error(
        'wp_posts_carousel_preview_not_available',
        __('Carousel preview is not available for this configuration.', 'wp-posts-carousel'),
        array('status' => 400)
      );
    }

    $data['html'] = $this->render_carousel_preview_html(
      $config,
      array(
        'id' => $preview_args['id'],
        'index' => 0,
        'instance_id' => $preview_args['instance_id'],
        'renderer' => isset($config['renderer']) ? sanitize_key($config['renderer']) : '',
        'force_editor_renderer' => true,
      )
    );

    return new WP_REST_Response($data, 200);
  }

  private function render_carousel_preview_html($config, $args = array())
  {
    if (!class_exists('WP_Posts_Carousel_Generator')) {
      return '';
    }

    $generator = new WP_Posts_Carousel_Generator();

    return $generator->generate_from_config($config, $args);
  }



  public function get_default_settings(WP_REST_Request $request, $post = null)
  {
    $WP_Posts_Carousel_Templates = new WP_Posts_Carousel_Templates();

    $dictionaries = array(
      'post_types' => WP_Posts_Carousel_Utils::get_post_types(),
      'taxonomies' => WP_Posts_Carousel_Utils::get_taxonomies(),
      'order_by' =>  WP_Posts_Carousel_Utils::get_orders_by(),
      'ordering' => WP_Posts_Carousel_Utils::get_orderings(),
      'description' =>  WP_Posts_Carousel_Utils::get_descriptions(),
      'image_sources' => WP_Posts_Carousel_Utils::get_image_sources(),
      'image_units' => WP_Posts_Carousel_Utils::get_units(),
      'relations' => WP_Posts_Carousel_Utils::get_relations(),
      'operators' => WP_Posts_Carousel_Utils::get_operators(),
      'breakpoints' => WP_Posts_Carousel_Utils::get_breakpoints(),
      'carousel_types' => WP_Posts_Carousel_Utils::get_carousel_types(),
      'frontend_renderers' => WP_Posts_Carousel_Utils::get_frontend_renderers(),
      'templates' => $WP_Posts_Carousel_Templates->get_templates(),
      'display_options' => WP_Posts_Carousel_Utils::get_display_options(),
      'carousel_options' => WP_Posts_Carousel_Utils::get_carousel_options(),
      'performance_image_sizes' => WP_Posts_Carousel_Utils::get_performance_image_sizes(),
      'field_mapping_targets' => WP_Posts_Carousel_Utils::get_field_mapping_targets(),
      'animations' => WP_Posts_Carousel_Utils::get_animations(),
      'icon_libraries' => class_exists('WP_Posts_Carousel_Icons') ? WP_Posts_Carousel_Icons::public_libraries() : array(),
      'icon_sources' => class_exists('WP_Posts_Carousel_Icons') ? WP_Posts_Carousel_Icons::public_sources() : array()
    );

    $default_values = WP_Posts_Carousel_Utils::get_carousel_default_params();
    $plugin_settings = WP_Posts_Carousel_Settings::get_settings();

    if (!empty($plugin_settings['global_breakpoints'])) {
      $default_values['carousel_options']['items_to_show'] = $plugin_settings['global_breakpoints'];
      $default_values['carousel_options']['breakpoints'] = $plugin_settings['global_breakpoints'];
    }

    $settings = array(
      'default_values' => apply_filters('wp_posts_carousel_default_values', $default_values, $post),
      'dictionaries'   => apply_filters('wp_posts_carousel_dictionaries', $dictionaries, $post),
      'features'       => WP_Posts_Carousel_Features::public_data(),
    );

    return apply_filters('wp_posts_carousel_default_settings', $settings, $post);
  }

  public function get_settings(WP_REST_Request $request)
  {
    $settings = new WP_Posts_Carousel_Settings();
    $data = array(
      'settings' => $settings->get_settings(),
      'assetDiagnostics' => WP_Posts_Carousel_Settings::get_asset_diagnostics(),
      'iconLibraries' => class_exists('WP_Posts_Carousel_Icons') ? WP_Posts_Carousel_Icons::public_libraries() : array(),
      'iconSources' => class_exists('WP_Posts_Carousel_Icons') ? WP_Posts_Carousel_Icons::public_sources() : array(),
    );
    return $data;
  }

  public function submit_review_feedback(WP_REST_Request $request)
  {
    $params = $request->get_json_params();

    if (!is_array($params)) {
      $params = $request->get_params();
    }

    $params = is_array($params) ? $this->normalize_request_data($params) : array();
    $rating = isset($params['rating']) ? absint($params['rating']) : 0;
    $feedback = isset($params['comment'])
      ? sanitize_textarea_field((string) $params['comment'])
      : (isset($params['feedback']) ? sanitize_textarea_field((string) $params['feedback']) : '');
    $source = isset($params['section'])
      ? sanitize_key((string) $params['section'])
      : (isset($params['source']) ? sanitize_key((string) $params['source']) : 'dashboard');
    $share_with_wordpress = isset($params['alsoOpenReviewPage'])
      ? rest_sanitize_boolean($params['alsoOpenReviewPage'])
      : (isset($params['alsoWordPressOrg'])
        ? rest_sanitize_boolean($params['alsoWordPressOrg'])
        : (isset($params['shareWithWordPress'])
        ? rest_sanitize_boolean($params['shareWithWordPress'])
        : rest_sanitize_boolean($params['share_with_wordpress'] ?? false)));

    if ($rating < 1 || $rating > 5) {
      return new WP_Error(
        'wp_posts_carousel_review_rating_required',
        __('Please select a rating before saving feedback.', 'wp-posts-carousel'),
        array('status' => 400)
      );
    }

    $review_consent = isset($params['reviewConsent']) && is_array($params['reviewConsent'])
      ? $params['reviewConsent']
      : array();
    $consent_content = isset($review_consent['content'])
      ? sanitize_textarea_field((string) $review_consent['content'])
      : '';
    if (empty($review_consent['accepted']) || $consent_content === '') {
      return new WP_Error(
        'wp_posts_carousel_review_consent_required',
        __('Please accept the required consent before sending your review.', 'wp-posts-carousel'),
        array('status' => 400)
      );
    }

    $payload = array(
      'productSlug' => self::MARKETPLACE_PRODUCT_SLUG,
      'rating' => $rating,
      'body' => $feedback,
      'feedback' => $feedback,
      'comment' => $feedback,
      'source' => $source,
      'section' => $source,
      'share_with_wordpress' => (bool) $share_with_wordpress,
      'alsoOpenReviewPage' => (bool) $share_with_wordpress,
      'alsoWordPressOrg' => (bool) $share_with_wordpress,
      'wordpress_review_url' => $this->wordpress_review_url(),
      'site_url' => esc_url_raw(home_url()),
      'siteUrl' => esc_url_raw(home_url()),
      'site_locale' => sanitize_text_field(get_locale()),
      'locale' => sanitize_text_field(get_locale()),
      'plugin_version' => WP_POSTS_CAROUSEL_VERSION,
      'moduleVersion' => WP_POSTS_CAROUSEL_VERSION,
      'platform' => 'wordpress',
      'submissionContext' => 'module-admin',
      'environment' => in_array(wp_get_environment_type(), array('production', 'staging'), true)
        ? wp_get_environment_type()
        : 'staging',
      'is_pro' => (bool) apply_filters('wp_posts_carousel_is_pro', false),
      'created_at' => current_time('mysql'),
      'reviewConsent' => array(
        'accepted' => true,
        'codeName' => isset($review_consent['codeName'])
          ? preg_replace('/[^a-zA-Z0-9_-]+/', '', sanitize_text_field((string) $review_consent['codeName']))
          : '',
        'content' => $consent_content,
        'locale' => isset($review_consent['locale'])
          ? sanitize_text_field((string) $review_consent['locale'])
          : 'en',
        'version' => isset($review_consent['version']) ? absint($review_consent['version']) : 0,
      ),
    );
    $payload['idempotencyKey'] = hash(
      'sha256',
      implode('|', array(
        self::MARKETPLACE_PRODUCT_SLUG,
        $payload['siteUrl'],
        (string) $rating,
        $feedback,
        $source,
        (string) floor(time() / 600),
      ))
    );

    $payload = apply_filters('wp_posts_carousel_review_feedback_payload', $payload, $request);

    $api_base = $this->configured_marketplace_api_base();
    $api_base = $api_base ? $api_base : self::MARKETPLACE_API_BASE;
    $remote_endpoint = apply_filters(
      'wp_posts_carousel_review_feedback_remote_endpoint',
      untrailingslashit($api_base) . '/api/product-reviews/submit',
      $payload,
      $request
    );
    $remote_response = wp_remote_post(
      esc_url_raw($remote_endpoint),
      array(
        'timeout' => 10,
        'blocking' => true,
        'headers' => array(
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ),
        'body' => wp_json_encode($payload),
      )
    );

    if (is_wp_error($remote_response)) {
      return new WP_Error(
        'wp_posts_carousel_feedback_unavailable',
        __('Feedback could not be sent. Please try again.', 'wp-posts-carousel'),
        array('status' => 502, 'details' => $remote_response->get_error_message())
      );
    }

    $remote_status = (int) wp_remote_retrieve_response_code($remote_response);
    $remote_payload = json_decode((string) wp_remote_retrieve_body($remote_response), true);
    if (
      $remote_status < 200
      || $remote_status >= 300
      || !is_array($remote_payload)
      || empty($remote_payload['ok'])
    ) {
      return new WP_Error(
        'wp_posts_carousel_feedback_rejected',
        __('Feedback could not be sent. Please try again.', 'wp-posts-carousel'),
        array(
          'status' => 502,
          'details' => is_array($remote_payload) && isset($remote_payload['error'])
            ? sanitize_text_field((string) $remote_payload['error'])
            : __('The feedback service returned an invalid response.', 'wp-posts-carousel'),
        )
      );
    }

    do_action('wp_posts_carousel_review_feedback_submitted', $payload, $request, $remote_payload);

    return new WP_REST_Response(
      array(
        'success' => true,
        'ok' => true,
        'feedback' => array(
          'id' => $remote_payload['id'] ?? null,
          'status' => $remote_payload['status'] ?? 'pending',
        ),
        'wordpressReviewUrl' => $payload['wordpress_review_url'],
      ),
      200
    );
  }

  public function update_settings(WP_REST_Request $request)
  {
    $settings = $request->get_json_params();

    if (empty($settings)) {
      $settings = $request->get_params();
    }

    $settings = $this->normalize_request_data($settings);
    $settings = is_array($settings) ? $settings : array();

    return new WP_REST_Response(array(
      'settings' => WP_Posts_Carousel_Settings::save_settings($settings),
    ), 200);
  }

  public function get_diagnostics(WP_REST_Request $request)
  {
    $settings = WP_Posts_Carousel_Settings::get_settings();
    $license = apply_filters(
      'wp_posts_carousel_license_data',
      array(
        'status' => 'free',
        'label' => __('Free', 'wp-posts-carousel'),
        'expires' => '',
      )
    );
    $theme = wp_get_theme();

    $diagnostics = array(
      'generatedAt' => current_time('mysql'),
      'site' => array(
        'homeUrl' => esc_url_raw(home_url()),
        'siteUrl' => esc_url_raw(site_url()),
        'locale' => sanitize_text_field(get_locale()),
        'timezone' => sanitize_text_field(wp_timezone_string()),
        'multisite' => is_multisite(),
        'environment' => function_exists('wp_get_environment_type') ? sanitize_key(wp_get_environment_type()) : '',
      ),
      'wordpress' => array(
        'version' => get_bloginfo('version'),
        'debug' => defined('WP_DEBUG') && WP_DEBUG,
        'memoryLimit' => WP_MEMORY_LIMIT,
        'maxMemoryLimit' => WP_MAX_MEMORY_LIMIT,
        'permalinkStructure' => sanitize_text_field((string) get_option('permalink_structure')),
      ),
      'server' => array(
        'phpVersion' => PHP_VERSION,
        'serverSoftware' => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : '',
        'curlVersion' => function_exists('curl_version') ? sanitize_text_field((string) (curl_version()['version'] ?? '')) : '',
        'opensslLoaded' => extension_loaded('openssl'),
      ),
      'plugin' => array(
        'version' => WP_POSTS_CAROUSEL_VERSION,
        'isPro' => (bool) apply_filters('wp_posts_carousel_is_pro', false),
        'features' => class_exists('WP_Posts_Carousel_Features') ? array_keys(WP_Posts_Carousel_Features::all()) : array(),
      ),
      'license' => $this->safe_license_diagnostics($license),
      'theme' => array(
        'name' => sanitize_text_field($theme->get('Name')),
        'version' => sanitize_text_field($theme->get('Version')),
        'template' => sanitize_text_field($theme->get_template()),
        'stylesheet' => sanitize_text_field($theme->get_stylesheet()),
      ),
      'settings' => array(
        'load_owl_assets' => !empty($settings['load_owl_assets']),
        'load_owl_css' => !empty($settings['load_owl_css']),
        'load_mousewheel_asset' => !empty($settings['load_mousewheel_asset']),
        'swiper_source' => sanitize_key($settings['swiper_source'] ?? 'bundled'),
        'load_swiper_css' => !empty($settings['load_swiper_css']),
        'icon_library' => sanitize_key($settings['icon_library'] ?? 'fontawesome'),
        'icon_source' => sanitize_key($settings['icon_source'] ?? 'local'),
        'global_breakpoints' => $settings['global_breakpoints'] ?? array(),
        'include_in_footer' => !empty($settings['include_in_footer']),
        'is_debug_mode' => !empty($settings['is_debug_mode']),
      ),
      'assetDiagnostics' => WP_Posts_Carousel_Settings::get_asset_diagnostics(),
      'activePlugins' => $this->active_plugins_diagnostics(),
      'logs' => array_values(array_filter(array(
        $this->diagnostic_log_entry(
          'license',
          isset($license['lastError']) ? sanitize_text_field((string) $license['lastError']) : '',
          isset($license['checkedAt']) ? sanitize_text_field((string) $license['checkedAt']) : ''
        ),
      ))),
    );

    return new WP_REST_Response(
      apply_filters('wp_posts_carousel_diagnostics', $diagnostics, $request),
      200
    );
  }

  private function normalize_request_data($value)
  {
    if (is_object($value)) {
      $value = get_object_vars($value);
    }

    if (!is_array($value)) {
      return $value;
    }

    $normalized = array();

    foreach ($value as $key => $item) {
      $normalized[$key] = $this->normalize_request_data($item);
    }

    return $normalized;
  }

  private function wordpress_review_url()
  {
    $default_url = 'https://wordpress.org/support/plugin/wp-posts-carousel/reviews/#new-post';
    $url = apply_filters('wp_posts_carousel_wordpress_review_url', $default_url);

    return esc_url_raw(apply_filters('wp_posts_carousel_review_url', $url));
  }

  private function safe_license_diagnostics($license)
  {
    $license = is_array($license) ? $license : array();
    $safe_keys = array(
      'status',
      'label',
      'plan',
      'expires',
      'graceUntil',
      'domain',
      'lastError',
      'checkedAt',
      'features',
    );

    $safe = array();

    foreach ($safe_keys as $key) {
      if (!array_key_exists($key, $license)) {
        continue;
      }

      if ('features' === $key && is_array($license[$key])) {
        $safe[$key] = array_values(array_map('sanitize_key', $license[$key]));
        continue;
      }

      $safe[$key] = sanitize_text_field((string) $license[$key]);
    }

    return $safe;
  }

  private function active_plugins_diagnostics()
  {
    if (!function_exists('get_plugins')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $plugins = get_plugins();
    $active = array_flip((array) get_option('active_plugins', array()));
    $network_active = is_multisite() ? (array) get_site_option('active_sitewide_plugins', array()) : array();
    $items = array();

    foreach ($plugins as $file => $data) {
      if (!isset($active[$file]) && !isset($network_active[$file])) {
        continue;
      }

      $items[] = array(
        'file' => sanitize_text_field($file),
        'name' => sanitize_text_field($data['Name'] ?? $file),
        'version' => sanitize_text_field($data['Version'] ?? ''),
        'networkActive' => isset($network_active[$file]),
      );
    }

    usort($items, function ($first, $second) {
      return strcasecmp($first['name'], $second['name']);
    });

    return $items;
  }

  private function diagnostic_log_entry($type, $message, $date = '')
  {
    if (!$message) {
      return null;
    }

    return array(
      'type' => sanitize_key($type),
      'message' => sanitize_text_field($message),
      'date' => sanitize_text_field($date),
    );
  }

  /**
   * get templates list
   */
  public function get_templates(WP_REST_Request $request)
  {
    if (!class_exists('WP_Posts_Carousel_Templates')) {
      include_once WP_POSTS_CAROUSEL_DIR_PATH . 'includes/class-templates.php';
    }

    $templates = class_exists('WP_Posts_Carousel_Templates')
      ? WP_Posts_Carousel_Templates::discover_templates()
      : array();

    return new WP_REST_Response(array(
      'templates' => $templates,
    ), 200);
  }

  /** A successful HTTP response must also contain a current, complete product feed. */
  public static function is_valid_marketplace_feed($feed)
  {
    return is_array($feed)
      && isset($feed['product']) && is_array($feed['product'])
      && isset($feed['news']) && is_array($feed['news'])
      && (!isset($feed['success']) || $feed['success'] !== false)
      && (!isset($feed['_meta']['ok']) || $feed['_meta']['ok'] !== false)
      && empty($feed['_meta']['stale'])
      && (!isset($feed['_meta']['source']) || $feed['_meta']['source'] !== 'empty');
  }

  /** Normalize public links without changing feed content or external destinations. */
  public static function normalize_marketplace_feed_urls($feed)
  {
    if (!is_array($feed)) {
      return $feed;
    }

    if (isset($feed['product']['pro']['url'])) {
      $feed['product']['pro']['url'] = self::normalize_marketplace_public_url($feed['product']['pro']['url']);
    }
    if (isset($feed['product']['free']['urls']) && is_array($feed['product']['free']['urls'])) {
      $feed['product']['free']['urls'] = self::normalize_marketplace_url_set($feed['product']['free']['urls']);
    }
    foreach (array('templates', 'extensions') as $collection) {
      if (!isset($feed[$collection]) || !is_array($feed[$collection])) {
        continue;
      }
      foreach ($feed[$collection] as $index => $item) {
        if (isset($item['urls']) && is_array($item['urls'])) {
          $feed[$collection][$index]['urls'] = self::normalize_marketplace_url_set($item['urls']);
        }
      }
    }
    if (isset($feed['news']) && is_array($feed['news'])) {
      foreach ($feed['news'] as $index => $item) {
        foreach (array('url', 'sourceUrl') as $key) {
          if (isset($item[$key])) {
            $feed['news'][$index][$key] = self::normalize_marketplace_public_url($item[$key]);
          }
        }
      }
    }

    return $feed;
  }

  private static function normalize_marketplace_url_set($urls)
  {
    foreach ($urls as $key => $url) {
      if ($url !== null) {
        $urls[$key] = self::normalize_marketplace_public_url($url);
      }
    }

    return $urls;
  }

  private static function normalize_marketplace_public_url($url)
  {
    if (!is_string($url) || trim($url) === '') {
      return '';
    }

    $url = trim($url);
    $parts = wp_parse_url($url);
    if (!is_array($parts) || isset($parts['user']) || isset($parts['pass'])) {
      return '';
    }

    $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
    if ($scheme !== '' && !in_array($scheme, array('http', 'https'), true)) {
      return '';
    }

    $host = isset($parts['host']) ? strtolower(trim($parts['host'], '[]')) : '';
    $path = isset($parts['path']) ? $parts['path'] : '';
    $is_catalog_path = (bool) preg_match('#^/(?:products|(?:en/|pl/)?docs)(?:/|$)#', $path);
    $is_relative = $host === '' && $scheme === '' && strpos($url, '/') === 0;
    // A server listen address can leak from the API request origin. It is not a public destination.
    $is_bind_address = in_array($host, array('0.0.0.0', '::'), true);

    if ($is_relative || $is_bind_address) {
      if (!$is_catalog_path) {
        return '';
      }
      $url = self::MARKETPLACE_API_BASE . $path;
      if (isset($parts['query'])) {
        $url .= '?' . $parts['query'];
      }
      if (isset($parts['fragment'])) {
        $url .= '#' . $parts['fragment'];
      }
    } elseif ($host === '') {
      return '';
    } elseif ($scheme === '') {
      $url = 'https:' . $url;
    }

    return esc_url_raw($url, array('http', 'https'));
  }

  public function get_plugin_feed(WP_REST_Request $request)
  {
    $locale = $this->marketplace_locale($request->get_param('locale'));
    $limit = absint($request->get_param('limit'));
    $limit = $limit ? min($limit, 20) : 5;
    $force = rest_sanitize_boolean($request->get_param('force'));
    $cache_key = $this->marketplace_cache_key($locale, $limit);
    $stale_cache_key = $cache_key . '_stale';

    if (!$force) {
      $cached = get_transient($cache_key);

      if (self::is_valid_marketplace_feed($cached)) {
        $cached = self::normalize_marketplace_feed_urls($cached);
        $cached['_meta'] = array_merge(
          isset($cached['_meta']) && is_array($cached['_meta']) ? $cached['_meta'] : array(),
          array(
            'ok' => true,
            'source' => 'cache',
            'cached' => true,
            'retryAfter' => 120,
          )
        );

        return new WP_REST_Response($cached, 200);
      }

    }

    // Expired data is only a fallback after a real refresh attempt below.
    $endpoints = $this->marketplace_endpoints(
      'plugin-feed',
      array(
        'locale' => $locale,
        'limit' => $limit,
      )
    );
    $last_error = '';

    foreach ($endpoints as $endpoint) {
      $response = wp_remote_get(
        $endpoint,
        array(
          'timeout' => 8,
          'user-agent' => 'WP Posts Carousel product feed',
          'headers' => array(
            'Accept' => 'application/json',
          ),
        )
      );

      if (is_wp_error($response)) {
        $last_error = $response->get_error_message();
        continue;
      }

      $code = (int) wp_remote_retrieve_response_code($response);
      $body = wp_remote_retrieve_body($response);
      $data = json_decode($body, true);

      if ($code < 200 || $code >= 300 || !self::is_valid_marketplace_feed($data)) {
        $last_error = $code < 200 || $code >= 300
          /* translators: %d: HTTP response status code. */
          ? sprintf(__('Marketplace feed returned HTTP %d.', 'wp-posts-carousel'), $code)
          : __('Marketplace feed returned an invalid response.', 'wp-posts-carousel');
        continue;
      }

      $data = self::normalize_marketplace_feed_urls($data);
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

      set_transient($cache_key, $data, self::MARKETPLACE_CACHE_TTL);
      set_transient($stale_cache_key, $data, self::MARKETPLACE_STALE_CACHE_TTL);

      return new WP_REST_Response($data, 200);
    }

    return $this->marketplace_error_response($last_error, $stale_cache_key);
  }

  private function marketplace_error_response($message, $stale_cache_key)
  {
    $stale = get_transient($stale_cache_key);

    if (self::is_valid_marketplace_feed($stale)) {
      $stale = self::normalize_marketplace_feed_urls($stale);
      $stale['_meta'] = array_merge(
        isset($stale['_meta']) && is_array($stale['_meta']) ? $stale['_meta'] : array(),
        array(
          'ok' => false,
          'source' => 'stale-cache',
          'cached' => true,
          'stale' => true,
          'error' => sanitize_text_field($message),
          'retryAfter' => 120,
        )
      );

      return new WP_REST_Response($stale, 200);
    }

    return new WP_REST_Response(
      array(
        'product' => null,
        'templates' => array(),
        'extensions' => array(),
        'news' => array(),
        '_meta' => array(
          'ok' => false,
          'source' => 'empty',
          'cached' => false,
          'stale' => false,
          'error' => sanitize_text_field($message ? $message : __('Marketplace feed is temporarily unavailable.', 'wp-posts-carousel')),
          'retryAfter' => 120,
        ),
      ),
      200
    );
  }

  private function marketplace_endpoints($resource, $args = array())
  {
    $bases = array_unique(array_filter(array(
      $this->configured_marketplace_api_base(),
      self::MARKETPLACE_API_BASE,
    )));

    return array_map(
      function ($base) use ($resource, $args) {
        $url = untrailingslashit($base) . '/api/products/' . rawurlencode(self::MARKETPLACE_PRODUCT_SLUG) . '/' . trailingslashit(sanitize_key($resource));

        return add_query_arg($args, $url);
      },
      $bases
    );
  }

  private function configured_marketplace_api_base()
  {
    $base = '';

    if (defined(self::MARKETPLACE_API_BASE_OVERRIDE)) {
      $base = constant(self::MARKETPLACE_API_BASE_OVERRIDE);
    }

    if (!$base) {
      $environment_base = getenv(self::MARKETPLACE_API_BASE_OVERRIDE);

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

  private function marketplace_locale($locale = '')
  {
    if (!$locale) {
      $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
    }

    $locale = strtolower(substr((string) $locale, 0, 2));

    return in_array($locale, array('pl', 'en'), true) ? $locale : 'en';
  }

  public function get_carousels(WP_REST_Request $request)
  {
    $result = WP_Posts_Carousel_Repository::all();

    return new WP_REST_Response(is_array($result) ? $result : array(), 200);
  }

  public function create_carousel(WP_REST_Request $request)
  {
    $title = isset($request['title']) ? $request['title'] : __('Untitled carousel', 'wp-posts-carousel');
    $params = $request->get_json_params();

    if (!is_array($params)) {
      $params = $request->get_params();
    }

    $params = $this->normalize_request_data($params);
    $params = is_array($params) ? $params : array();
    $pre_save_error = apply_filters('wp_posts_carousel_pre_save_error', null, $params, $request, 'create');

    if (is_wp_error($pre_save_error)) {
      return $pre_save_error;
    }

    $post = WP_Posts_Carousel_Repository::create($title, $params);

    if (is_wp_error($post)) {
      return $post;
    }

    return new WP_REST_Response($post, 200);
  }

  public function get_carousel(WP_REST_Request $request)
  {
    $post_id = absint($request['id']);
    $post = WP_Posts_Carousel_Repository::get($post_id);

    if (!empty($post)) {
      $config = WP_Posts_Carousel_Config::normalize(json_decode($post['params'], true));
      $post['config'] = $config;
      $post['params'] = wp_json_encode(WP_Posts_Carousel_Config::to_form_values($config));
      $settings = $this->get_default_settings($request, $post);

      return new WP_REST_Response(array_merge(array('post' => $post), $settings), 200);
    }

    return new WP_Error(__('Failed get carousel', 'wp-posts-carousel'), $request, array('status' => 500));
  }

  public function prepare_posts_options($settings, $post)
  {
    $params = json_decode($post['params']);

    $array = array(
      'posts' => array_map(array($this, 'prepare_post_option'), get_posts(array(
        'post__in' => $params->posts,
        'post_type' => $params->post_types
      ))),
      'exclude' => array_map(array($this, 'prepare_post_option'), get_posts(array(
        'post__in' => $params->exclude,
        'post_type' => $params->post_types
      )))
    );

    return array_merge($settings,  $array);
  }



  public function update_carousel(WP_REST_Request $request)
  {
    $title = isset($request['title']) ? $request['title'] : __('Untitled carousel', 'wp-posts-carousel');
    $params = $request->get_json_params();

    if (!is_array($params)) {
      $params = $request->get_params();
    }

    $params = $this->normalize_request_data($params);
    $params = is_array($params) ? $params : array();

    $existing_config = WP_Posts_Carousel_Repository::get_config($request['id']);
    if (is_array($existing_config) && !array_key_exists('requirements', $params) && !empty($existing_config['requirements'])) {
      $params['requirements'] = $existing_config['requirements'];
    }

    $pre_save_error = apply_filters('wp_posts_carousel_pre_save_error', null, $params, $request, 'update');

    if (is_wp_error($pre_save_error)) {
      return $pre_save_error;
    }

    $post = WP_Posts_Carousel_Repository::update($request['id'], $title, $params);

    if (is_wp_error($post)) {
      return $post;
    }

    return new WP_REST_Response($post, 200);
  }

  public function delete_carousel(WP_REST_Request $request)
  {
    $deleted = WP_Posts_Carousel_Repository::delete($request['id']);

    if (is_wp_error($deleted)) {
      return $deleted;
    }

    return new WP_REST_Response(true, 200);
  }

  public function duplicate_carousel(WP_REST_Request $request)
  {
    $post = WP_Posts_Carousel_Repository::duplicate($request['id']);

    if (is_wp_error($post)) {
      return $post;
    }

    return new WP_REST_Response($post, 200);
  }
}

return new WP_Posts_Carousel_API();
