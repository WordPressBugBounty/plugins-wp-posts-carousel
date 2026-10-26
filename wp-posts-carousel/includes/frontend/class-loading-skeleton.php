<?php

defined( 'ABSPATH' ) || exit;

/** Loading presentation for carousels whose cards arrive asynchronously. */
class WP_Posts_Carousel_Loading_Skeleton {
	/** Reserve the native card geometry before either library wraps the slides. */
	public static function initial_layout( $config, $instance_id ) {
		$options = $config['engine']['options'];
		$two_rows = WP_Posts_Carousel_Config::uses_two_rows( $config );
		$selector = '#' . sanitize_html_class( $instance_id );
		$breakpoints = $options['items_to_show'] ?? array( 0 => 1 );
		ksort( $breakpoints, SORT_NUMERIC );
		$rules = $selector . '{--wpc-initial-columns:1;--wpc-initial-rows:' . ( $two_rows ? 2 : 1 ) . ';--wpc-initial-row-gap:' . absint( $options['row_gap'] ?? 24 ) . 'px;}';
		if ( ( $config['engine']['name'] ?? '' ) === 'swiper' && ( $options['direction'] ?? '' ) === 'vertical' ) {
			$height = absint( $options['vertical_height'] ?? 480 ) + ( ! empty( $options['nav'] ) ? 112 : 0 );
			$rules .= $selector . '{--wpc-loading-default-height:' . $height . 'px;}';
		}
		$heights = array();
		foreach ( $config['template']['display'] ?? array() as $display ) {
			if ( ( $display['featured_image_height_unit'] ?? '' ) === 'px' && ! empty( $display['featured_image_height'] ) ) {
				$heights[] = absint( $display['featured_image_height'] );
			}
		}
		if ( $heights ) {
			$rules .= $selector . '{--wpc-skeleton-default-image-height:' . max( $heights ) . 'px;}';
		}
		foreach ( $breakpoints as $width => $columns ) {
			$rules .= '@media(min-width:' . absint( $width ) . 'px){' . $selector . '{--wpc-initial-columns:' . max( 1, absint( $columns ) ) . ';}}';
		}
		if ( $two_rows ) {
			$rules .= '@media(max-width:' . absint( $options['rows_mobile_max'] ?? 767 ) . 'px){' . $selector . '{--wpc-initial-rows:' . absint( $options['mobile_rows'] ?? 1 ) . ';}}';
		}
		// Scope to direct cards: the rule stops applying as soon as a library
		// supplies its viewport. The same markup remains usable without JavaScript.
		$rules .= $selector . ':has(>.cci-wpc-slide){display:grid!important;grid-auto-flow:column;grid-template-rows:repeat(var(--wpc-initial-rows),minmax(0,1fr));grid-auto-columns:calc((100% - (var(--wpc-initial-columns) - 1)*var(--wpc-carousel-gap,0px))/var(--wpc-initial-columns));column-gap:var(--wpc-carousel-gap,0px);row-gap:var(--wpc-initial-row-gap);overflow:clip;}';
		$rules .= $selector . ':has(>.cci-wpc-slide)> .cci-wpc-slide{min-width:0;width:100%;height:100%;margin:0;}';
		$rules .= $selector . ':is([data-rows-ready="fallback"],[data-wpc-lazy-state="error"],[data-flow-error]):has(>.cci-wpc-slide){overflow:auto;}';
		return '<style data-wpc-initial-layout>' . $rules . '</style><noscript><style>' . $selector . ':has(>.cci-wpc-slide){overflow:auto;}</style></noscript>';
	}

	public static function render( $config, $post_id, $instance_id, $template ) {
		$options = $config['engine']['options'];
		$breakpoints = ! empty( $options['items_to_show'] ) ? $options['items_to_show'] : array( 0 => 1 );
		$rows = WP_Posts_Carousel_Config::uses_two_rows( $config ) ? 2 : 1;
		$vertical = ( $config['engine']['name'] ?? '' ) === 'swiper' && ( $options['direction'] ?? '' ) === 'vertical';
		if ( $vertical ) {
			$breakpoints = array( 0 => 1 );
		}
		$args = array(
			'post_id' => absint( $post_id ),
			'instance_id' => $instance_id,
			'config' => $config,
			'template' => $template,
			'rows' => $rows,
			'card_count' => min( 24, max( 1, max( array_values( $breakpoints ) ) ) * $rows ),
		);
		$default = WP_POSTS_CAROUSEL_DIR_PATH . 'templates/partials/skeleton.php';
		$path = locate_template( array( 'cci-posts-carousel/skeleton.php' ), false, false );
		if ( ! $path && is_readable( $template['dir'] . '/skeleton.php' ) ) {
			$path = $template['dir'] . '/skeleton.php';
		}
		$path = apply_filters( 'wp_posts_carousel_skeleton_template', $path ?: $default, $args );
		if ( ! is_string( $path ) || ! is_file( $path ) || ! is_readable( $path ) ) {
			$path = $default;
		}
		ob_start();
		include $path;
		$html = ob_get_clean();
		$filtered = apply_filters( 'wp_posts_carousel_skeleton_html', $html, $args );
		$html = is_string( $filtered ) ? $filtered : $html;
		$selector = '[data-wpc-skeleton-for="' . sanitize_html_class( $instance_id ) . '"]';
		$rules = '';
		if ( $vertical ) {
			$rules = $selector . '{min-height:' . absint( $options['vertical_height'] ?? 480 ) . 'px;}';
		}
		foreach ( $breakpoints as $width => $columns ) {
			$columns = max( 1, min( 12, absint( $columns ) ) );
			$rules .= '@media(min-width:' . absint( $width ) . 'px){' . $selector . '{--wpc-skeleton-columns:' . $columns . ';}' . $selector . ' .cci-wpc-skeleton-card{display:block;}' . $selector . ' .cci-wpc-skeleton-card:nth-child(n+' . ( $columns * $rows + 1 ) . '){display:none;}}';
		}
		if ( $rows === 2 && ( $options['mobile_rows'] ?? 1 ) === 1 ) {
			foreach ( $breakpoints as $width => $columns ) {
				$rules .= '@media(min-width:' . absint( $width ) . 'px) and (max-width:' . absint( $options['rows_mobile_max'] ?? 767 ) . 'px){' . $selector . ' .cci-wpc-skeleton-card:nth-child(n+' . ( min( 12, absint( $columns ) ) + 1 ) . '){display:none;}}';
			}
		}
		return '<div data-wpc-loading-presentation data-wpc-loading-state="loading">'
			. '<style>' . $rules . '</style>'
			. '<div data-wpc-skeleton-for="' . esc_attr( $instance_id ) . '" class="cci-wpc-skeleton" aria-hidden="true" inert>' . $html . '</div>'
			. '<p class="screen-reader-text" data-wpc-loading-message role="status" data-wpc-error-label="' . esc_attr__( 'The carousel could not be loaded.', 'cci-posts-carousel' ) . '" data-wpc-loading-label="' . esc_attr__( 'Loading carousel…', 'cci-posts-carousel' ) . '">' . esc_html__( 'Loading carousel…', 'cci-posts-carousel' ) . '</p>'
			. '<button type="button" data-wpc-loading-retry hidden>' . esc_html__( 'Try again', 'cci-posts-carousel' ) . '</button>'
			. '</div>';
	}
}
