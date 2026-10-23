<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Optional image previews shared by the PHP and React renderers. */
class WP_Posts_Carousel_Hover_Image {

	public static function apply( $slide, $config, $post = null, $item = array() ) {
		$slide['hover_image'] = array();
		if ( empty( $config['image_hover']['enabled'] ) || empty( $slide['image']['src'] ) ||
			( ! empty( $slide['media']['kind'] ) && $slide['media']['kind'] !== 'image' ) ||
			( $post && ! has_post_thumbnail( $post ) ) ) {
			return $slide;
		}

		if ( $post ) {
			foreach ( $config['curation']['items'] ?? array() as $candidate ) {
				if ( ( $candidate['type'] ?? 'post' ) !== 'custom' && (int) ( $candidate['id'] ?? 0 ) === (int) $post->ID ) {
					$item = $candidate;
					break;
				}
			}
		}

		$source = $config['image_hover']['source'] ?? 'content';
		$url    = self::safe_url( $item['hover_image_url'] ?? '' );
		$size   = $config['performance']['image_size'] ?? 'inherit';
		$size   = $size === 'inherit' ? 'large' : $size;
		if ( ! $url && $source === 'gallery' && $post && $post->post_type === 'product' && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $post->ID );
			foreach ( $product ? $product->get_gallery_image_ids() : array() as $id ) {
				$candidate = wp_get_attachment_image_url( $id, $size );
				if ( (int) $id !== (int) $slide['image']['id'] && self::different( $candidate, $slide['image']['src'] ) ) {
					$url = self::safe_url( $candidate );
					break;
				}
			}
		} elseif ( ! $url && $source === 'content' ) {
			// Read stored markup only: do not execute blocks, shortcodes or remote requests.
			$html = $post ? $post->post_content : ( $item['description'] ?? '' );
			$tags = new WP_HTML_Tag_Processor( $html );
			while ( $tags->next_tag( 'IMG' ) ) {
				$candidate = self::safe_url( $tags->get_attribute( 'src' ) );
				$classes   = (string) $tags->get_attribute( 'class' );
				$id        = preg_match( '/\bwp-image-(\d+)\b/', $classes, $match ) ? (int) $match[1] : 0;
				if ( $post && $id && $id === (int) $slide['image']['id'] ) {
					continue;
				}
				if ( $candidate && self::different( $candidate, $slide['image']['src'] ) ) {
					$url = $id ? self::safe_url( wp_get_attachment_image_url( $id, $size ) ) : $candidate;
					if ($url) break;
				}
			}
		}

		$url = self::safe_url( apply_filters( 'wp_posts_carousel_hover_image_url', $url, $slide, $config, $post, $item ) );
		if ( ! $url || ! self::different( $url, $slide['image']['src'] ) ) {
			return $slide;
		}
		$slide['hover_image'] = array( 'src' => $url );
		foreach ( array( 'featured_image', 'woocommerce_card', 'post_type_card' ) as $key ) {
			if (empty( $slide['html'][ $key ] )) continue;
			$tags = new WP_HTML_Tag_Processor( $slide['html'][ $key ] );
			if ( $tags->next_tag( 'IMG' ) ) {
				$tags->set_attribute( 'data-wpc-hover-src', $url );
				$slide['html'][ $key ] = $tags->get_updated_html();
			}
		}
		return $slide;
	}

	private static function safe_url( $url ) {
		if ( ! is_string( $url ) || ! $url) return '';
		if (strpos( $url, '/' ) === 0 && strpos( $url, '//' ) !== 0) $url = home_url( $url );
		if ( ! preg_match( '#^https?://#i', $url )) return '';
		$url = esc_url_raw( $url, array( 'http', 'https' ) );
		return in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ? $url : '';
	}

	private static function different( $candidate, $primary ) {
		// WordPress may use different generated sizes of the same attachment.
		$path = static function ( $url ) {
			return strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) .
				preg_replace( '/-\d+x\d+(?=\.[^.]+$)/', '', (string) wp_parse_url( $url, PHP_URL_PATH ) );
		};
		return $candidate && $path( $candidate ) !== $path( $primary );
	}
}
