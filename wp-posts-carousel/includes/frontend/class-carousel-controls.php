<?php

defined( 'ABSPATH' ) || exit;

/** Optional, theme-owned control markup for the native Swiper renderer. */
class WP_Posts_Carousel_Controls {
	public static function render( $config, $post_id, $instance_id, $template ) {
		if ( ( $config['engine']['name'] ?? '' ) !== 'swiper' ) {
			return '';
		}
		$args = array(
			'post_id'     => absint( $post_id ),
			'instance_id' => $instance_id,
			'config'      => $config,
			'template'    => $template,
		);
		$path = locate_template( array( 'cci-posts-carousel/controls.php' ), false, false );
		if ( ! $path && is_readable( $template['dir'] . '/controls.php' ) ) {
			$path = $template['dir'] . '/controls.php';
		}
		$path = apply_filters( 'wp_posts_carousel_controls_template', $path, $args );
		$html = '';
		if ( is_string( $path ) && is_file( $path ) && is_readable( $path ) ) {
			ob_start();
			include $path;
			$html = ob_get_clean();
		}
		$filtered = apply_filters( 'wp_posts_carousel_controls_html', $html, $args );
		return is_string( $filtered ) ? $filtered : $html;
	}
}
