<?php
/**
 * Author: Marcin Gierada
 * Author URI: https://coolcatideas.com/
 * Author Email: info@coolcatideas.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_Posts_Carousel_Wordpress_Popular_Posts_Query' ) ) :

/**
 * WP_Posts_Carousel_Popular_Posts integration class
 */
class WP_Posts_Carousel_Wordpress_Popular_Posts_Query extends WP_Query {
    private $args;
    private $params;
    private $interval;

    /**
     * constructor
     */
    public function __construct( $args, $params ) {
        $this->args = $args;
        $this->params = $params;

        add_filter( 'posts_fields', array( $this, 'posts_fields') );
        add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ) );
        add_filter( 'posts_join_paged', array( $this, 'posts_join_paged') );
        add_filter( 'posts_groupby', array( $this, 'posts_groupby' ) );
        add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );

        parent::__construct( $this->args );

        remove_filter( 'posts_fields', array( $this, 'posts_fields' ) );
        remove_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ) );
        remove_filter( 'posts_join_paged', array( $this, 'posts_join_paged' ) );
        remove_filter( 'posts_groupby', array( $this, 'posts_groupby' ) );
        remove_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
    }

    public function posts_fields( $sql ) {
        return $sql . ', SUM(p.pageviews) AS views';
    }

    public function posts_where_paged( $sql ) {
        $intervals = WP_Posts_Carousel_Utils::getShows();

        $this->interval = apply_filters( 'wp_posts_carousel_wordpress_popular_posts_shows_custom_interval', '1 YEAR', array(
            'params' => $this->params
        ) );

        if ( $this->interval !== NULL ) {
            return $sql . ' AND post_date > DATE_SUB("' . current_time('mysql') . '", INTERVAL ' . $this->interval . ')';
        } else {
            return $sql;
        }
    }

    public function posts_join_paged( $sql ) {
        global $wpdb;

        if ( $this->interval !== NULL ) {
            return $sql . 'JOIN ' . $wpdb->prefix . 'popularpostssummary as p ON (p.postid = ' . $wpdb->prefix . 'posts.ID AND p.last_viewed > DATE_SUB("' . current_time('mysql') . '", INTERVAL ' . $this->interval . '))';
        } else {
            return $sql . 'JOIN ' . $wpdb->prefix . 'popularpostssummary as p ON (p.postid = ' . $wpdb->prefix . 'posts.ID)';
        }
    }

    public function posts_groupby( $sql ) {
        return $sql . 'ID';
    }

    public function posts_orderby( $sql ) {
        return 'views ' . $this->params['ordering'];
    }
}

endif;
