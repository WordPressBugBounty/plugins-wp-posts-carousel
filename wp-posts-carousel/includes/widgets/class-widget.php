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

/**
 * WP_Posts_Carousel_Widget class
 */
class WP_Posts_Carousel_Widget extends WP_Widget {

    /**
     * constructor
     */
    public function __construct() {
        parent::__construct(
            'wp_posts_carousel',
            'WP Posts Carousel',
            array(
                'classname'   => 'widget_wp_posts_carousel',
                'description' =>  __( 'Show posts in Wp Posts Carousel', 'wp-posts-carousel' )
            )
        );
    }

    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', isset( $instance['title'] ) ? $instance['title'] : '' );

        echo wp_kses_post( $args['before_widget'] );

        if ( $title ) {
            echo wp_kses_post( $args['before_title'] );
            echo wp_kses_post( $title );
            echo wp_kses_post( $args['after_title'] );
        }
        if ( ! empty( $instance['carousel'] ) ) {
            echo do_shortcode( '[wp_posts_carousel id="' . absint( $instance['carousel'] ) . '"]' );
        }

        echo wp_kses_post( $args['after_widget'] );
    }

    /*
     * the configuration form.
     */
    public function form( $instance ) {

    ?>
        <div class="cci-wpc-widget-form">
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'wp-posts-carousel' ); ?>:</label>
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( array_key_exists( 'title', $instance ) ? $instance['title'] : '' ); ?>" />
            </p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'carousel' ) ); ?>"><?php esc_html_e( 'Carousel', 'wp-posts-carousel' ); ?>:</label>
                <br />
                <select class="select widefat cci-wpc-field cci-wpc-field-required" name="<?php echo esc_attr( $this->get_field_name( 'carousel' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'carousel' ) ); ?>" size="1" required>
                    <option value=""></option>
                <?php
                    $carousels = WP_Posts_Carousel_Repository::all();
                    foreach( $carousels as $carousel ) {
                        echo '<option value="' . esc_attr( $carousel['ID'] ) . '" ' . selected( isset( $instance['carousel'] ) ? $instance['carousel'] : '', $carousel['ID'], false ) . '>' . esc_html( $carousel['post_title'] ) . '</option>';
                    }
                ?>
                </select>
            </p>
        </div>
<?php
    }
}

add_action("widgets_init", function() {
	return register_widget('Wp_Posts_Carousel_Widget');
});
?>
