<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Author: Marcin Gierada
 * Author URI: https://coolcatideas.com/
 * Author Email: info@coolcatideas.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
/*
 *
 * DUE TO THE OPTIMIZATION
 * PLEASE DON'T REMOVE ANY STATEMENTS
 *
 */
$template = null;
$media = $this->slide_media_html();
$media_kind = $this->slide_media_kind();
$eyebrow = $this->slide_display_value('eyebrow');
$kicker = $this->slide_display_value('kicker');
$story_step = $this->slide_display_value('story_step');

$woocommerce_card = !empty( $this->slide['html']['woocommerce_card'] ) ? $this->slide['html']['woocommerce_card'] : '';

$template .= '<article class="cci-wpc-slide cci-wpc-free-template cci-wpc-free-template-light cci-wpc-slides-' . esc_attr( $this->params['all_items'] ) . ' ' . esc_attr( $this->slide_layout_classes() ) . '"' . $this->slide_layout_attributes() . '>';
        if ( $woocommerce_card ) {
            $template .= do_action( 'wp_posts_carousel_before_item_content', $this->params );
            $template .= $woocommerce_card;
            $template .= do_action( 'wp_posts_carousel_after_item_content', $this->params );
            $template .= '</article>';

            return $template;
        }

    $template .= '<div class="cci-wpc-card cci-wpc-card-post cci-wpc-container">';
        $template .= do_action( 'wp_posts_carousel_before_item_content', $this->params );

            if ( $this->params['show_featured_image'] === 'true' && $media ) {
                $template .= '<div class="cci-wpc-card-media cci-wpc-image">';
            if ( $media_kind === 'image' ) {
            $template .= apply_filters( 'wp_posts_carousel_item_featured_image', $media, array(
                'post_url' => $this->post_url,
                'post'     => $this->post,
                'image'    => $this->image[0],
                'params'   => $this->params,
                'slide'    => $this->slide,
            ) );
            } else {
                $template .= $media;
            }
                $template .= '</div>';
            }


            $template .= '<div class="cci-wpc-card-body cci-wpc-details">';

                if ( $this->params['show_category'] === 'true' && ( $this->category || $eyebrow || $kicker || $story_step ) ) {
                    $template .= '<div class="cci-wpc-card-taxonomy cci-wpc-categories">';
                    if ( $eyebrow ) {
                        $template .= '<span class="cci-wpc-category cci-wpc-eyebrow">' . esc_html( $eyebrow ) . '</span>';
                    }
                    if ( $kicker ) {
                        $template .= '<span class="cci-wpc-category cci-wpc-kicker">' . esc_html( $kicker ) . '</span>';
                    }
                    if ( $story_step ) {
                        $template .= '<span class="cci-wpc-category cci-wpc-step">' . esc_html( $story_step ) . '</span>';
                    }
                    $template .= apply_filters( 'wp_posts_carousel_item_categories', $this->category, array(
                        'categories_list' => $this->categories_list,
                        'post'            => $this->post,
                        'params'          => $this->params,
                    ) );
                    $template .= '</div>';
                }


                if ( $this->params['show_title'] === 'true' ) {
                    $template .= '<h3 class="cci-wpc-card-title cci-wpc-title">';
                    $template .= apply_filters( 'wp_posts_carousel_item_title', $this->title, array(
                        'post_url' => $this->post_url,
                        'post'     => $this->post,
                        'params'   => $this->params,
                    ) );
                    $template .= '</h3>';
                }


                if ( $this->params['show_description'] !== 'false' ) {
                    $template .= '<div class="cci-wpc-card-description cci-wpc-description">';
                $template .= apply_filters( 'wp_posts_carousel_item_description', $this->description, array(
                    'post'    => $this->post,
                    'params'  => $this->params,
                ) );
                    $template .= '</div>';
                }

                $template .= '<div class="cci-wpc-card-meta cci-wpc-post-details">';
                if ( $this->params['show_created_date'] === 'true' ) {
                    $template .= '<span class="cci-wpc-created-date">';
                $template .= apply_filters( 'wp_posts_carousel_item_created_date', $this->created_date, array(
                    'post'    => $this->post,
                    'params'  => $this->params,
                ) );
                    $template .= '</span>';
                }

                if ( $this->params['show_author'] === 'true' ) {
                    $template .= '<span class="cci-wpc-author">';
                $template .= apply_filters( 'wp_posts_carousel_item_author', $this->author, array(
                    'post'    => $this->post,
                    'params'  => $this->params,
                ) );
                    $template .= '</span>';
                }
                $template .= '</div>';

                if ( $this->params['show_tags'] === 'true' ) {
                    $template .= '<div class="cci-wpc-card-tags cci-wpc-tags">';
                $template .= apply_filters( 'wp_posts_carousel_item_tags', $this->tags, array(
                    'tags_list' => $this->tags_list,
                    'post'      => $this->post,
                    'params'    => $this->params,
                ) );
                    $template .= '</div>';
                }

                if ( $this->params['show_more_button'] === 'true' ) {
                    $template .= '<div class="cci-wpc-card-actions cci-wpc-buttons">';
                $template .= apply_filters( 'wp_posts_carousel_item_buttons', $this->buttons, array(
                    'post'      => $this->post,
                    'post_url'  => $this->post_url,
                    'params'    => $this->params,
                ) );
                    $template .= '</div>';
                }

            $template .= '</div>';

        $template .= do_action( 'wp_posts_carousel_after_item_content', $this->params );
    $template .= '</div>';
$template .= '</article>';

return $template;
