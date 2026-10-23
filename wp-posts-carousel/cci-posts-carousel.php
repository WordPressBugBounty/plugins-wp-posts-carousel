<?php

/**
 * Plugin Name: CCI Posts Carousel All In One
 * Plugin URI: https://coolcatideas.com/products/cci-posts-carousel-all-in-one
 * Description: Create reusable carousels for posts, pages, products and manual slides, then place them with Gutenberg or a shortcode. Pro adds live WooCommerce data, buying actions, sales templates, Elementor and reports.
 * Version: 2.1.1
 * Requires at least: 6.2
 * Tested up to: 7.1.3
 * Requires PHP: 7.4
 * Author: Cool Cat Ideas
 * Author URI: https://coolcatideas.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 *
 * Text Domain: cci-posts-carousel
 * Domain Path: /languages/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . '/includes/support/class-product-identity.php';
// Loading a renamed package during WordPress activation must not redefine the legacy API.
if ( ! class_exists( 'WP_Posts_Carousel', false ) ) {

	/**
	 * Wp_Posts_Carousel class
	 */
	class WP_Posts_Carousel {

		public $version      = '2.1.1';
		public $settings     = array();
		public $templates    = array();
		public $integrations = array();

		/**
		 * constructor
		 */
		public function __construct() {
			$this->define_constants();
			$this->includes();
			$this->init_hooks();
		}

		/**
		 * constants
		 */
		private function define_constants() {
			$this->define( 'WP_POSTS_CAROUSEL_FILE', __FILE__ );
			$this->define( 'WP_POSTS_CAROUSEL_BASENAME', plugin_basename( __FILE__ ) );
			$this->define( 'WP_POSTS_CAROUSEL_DIR_URL', plugin_dir_url( __FILE__ ) );
			$this->define( 'WP_POSTS_CAROUSEL_DIR_PATH', plugin_dir_path( __FILE__ ) );
			$this->define( 'WP_POSTS_CAROUSEL_VERSION', $this->version );
		}

		/**
		 * define constant
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * include files
		 */
		public function includes() {
			include_once 'includes/bootstrap/class-init.php';
			include_once 'includes/support/class-utils.php';
			include_once 'includes/support/class-icons.php';
			include_once 'includes/carousels/class-carousel-config.php';
			include_once 'includes/carousels/class-carousel-repository.php';
			include_once 'includes/features/class-features.php';
			include_once 'includes/carousels/class-slide-view-model.php';
			include_once 'includes/carousels/class-hover-image.php';
			include_once 'includes/frontend/class-carousel-query-builder.php';
			include_once 'includes/templates/class-templates.php';
			include_once 'includes/carousels/class-render-context.php';
			include_once 'includes/templates/class-template-assets.php';
			include_once 'includes/carousels/class-carousel-data.php';
			include_once 'includes/api/class-api.php';
			include_once 'includes/widgets/class-widget.php';
			include_once 'includes/blocks/class-blocks.php';

			include_once 'includes/integrations/class-integrations.php';

			include_once 'includes/admin/class-admin-settings.php';

			$this->define( 'WP_POSTS_CAROUSEL_DEBUG', false );

			if ( is_admin() ) {
				include_once 'includes/admin/class-admin.php';
			}

			// Frontend assets are also needed in editor previews (Gutenberg/Elementor), so
			// load core frontend asset wiring for admin contexts too.
			include_once 'includes/frontend/class-frontend.php';
		}

		/**
		 * init hooks
		 */
		private function init_hooks() {
			add_action( 'init', array( $this, 'init' ), 0 );

			if ( is_admin() ) {
				// clear settings
				register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );
			}
		}

		/**
		 * deactivate the plugin
		 */
		public function deactivation() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			// Deactivation must preserve saved carousels and compatibility settings.
		}

		/**
		 * init
		 */
		public function init() {
			// i18n
			$this->load_plugin_textdomain();

			// settings
			$this->settings = new Wp_Posts_Carousel_Settings();
		}

		/**
		 * load Localisation files.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'cci-posts-carousel', false, dirname( WP_POSTS_CAROUSEL_BASENAME ) . '/languages/' );
		}
	}



	if ( ! function_exists( 'WP_Posts_Carousel' ) ) {
		function WP_Posts_Carousel() {
			global $WP_Posts_Carousel;

			if ( ! isset( $WP_Posts_Carousel ) ) {
				$WP_Posts_Carousel = new WP_Posts_Carousel();
			}

			return $WP_Posts_Carousel;
		}
	}
	WP_Posts_Carousel();

}
