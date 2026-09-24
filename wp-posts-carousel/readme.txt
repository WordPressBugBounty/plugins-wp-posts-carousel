=== WP Posts Carousel All In One ===
Contributors: coolcatideas
Tags: carousel, slider, posts, gutenberg, custom post types
Requires at least: 6.2
Tested up to: 7.1.2
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create a carousel from posts, pages, products or manual slides, then reuse it with Gutenberg or a shortcode.

== Description ==

Use WP Posts Carousel All In One for recent posts, selected pages, custom post types, product links or slides you arrange by hand. The visual editor shows the chosen content and layout while you work. When you update a saved carousel, every place that uses it receives the change.

= What you can do without a license =

* Create unlimited carousel definitions.
* Choose a saved carousel in the Gutenberg block or copy its generated shortcode from the carousel list.
* Display posts and custom post types with taxonomy-aware queries.
* Select WooCommerce products as a custom post type and display them as standard linked content cards. Prices, sale state and buying actions require Pro.
* Add frontend taxonomy filters above a carousel.
* Map post meta or ACF fields into badges, CTA labels, custom URLs, image URLs, accents and video data.
* Tune lazy images, first-image priority and image size override.
* Use manual slide order for editorial sequences, featured cards and visual compositions.
* Choose Owl Carousel or Swiper rendering.
* Control bundled Owl/Swiper assets when the theme already loads its own libraries.
* Use the bundled carousel templates.
* Order a carousel by statistics already collected by WordPress Popular Posts.
* Extend queries and output through the documented developer API.

= What Pro adds =

Install the Pro plugin next to the base plugin when you need current WooCommerce prices, sale labels, ratings and add-to-cart actions. The same Pro package also adds bestseller and top-rated sources, Commerce Luxe, Story Commerce, Video Showcase, Elementor, engagement reports, a progress bar or seconds countdown before the next autoplay slide, and WordPress Multisite tools. You do not need a separate WooCommerce or template purchase.

Product and Pro information:
https://coolcatideas.com/products/wp-posts-carousel-all-in-one

Full documentation is published online at https://coolcatideas.com/docs/wp-posts-carousel-all-in-one/2.0.0/.

= Support =

Use the product repository under https://github.com/orgs/cool-cat-ideas/repositories for public questions and bug reports. This channel has no guaranteed response time. When you need a private request with an agreed scope, buy a support service on the product page; the request and further messages are then handled in the Cool Cat Ideas customer panel.

= Marketplace =

The admin loads product news, templates and integrations from the Cool Cat Ideas marketplace. If the service is unavailable, saved carousels keep working; the admin shows cached content or an unavailable state and retries.

== External services ==

= Cool Cat Ideas =

Opening the plugin admin automatically requests the product feed from `https://coolcatideas.com/api/products/wp-posts-carousel-all-in-one/plugin-feed/` when it needs fresh data. The request includes the product identifier, language and news limit. Results are cached; retrying refreshes them. The service receives the server IP address. Feed requests use a product-specific User-Agent without the WordPress version or website URL. Loading remote catalog images also connects the browser to the image host, which receives the browser IP address.

Only after you accept the review consent and click **Save feedback**, the plugin sends a review to `https://coolcatideas.com/api/product-reviews/submit` for moderation and possible publication. It sends the rating, comment, product and plugin version, platform, admin section, website URL, language, environment, Pro status, submission time, WordPress review-page preferences, consent text/version/acceptance and a duplicate-prevention hash. The review is not published automatically. The consent text is included in the plugin; opening the dialog does not fetch it from an external service.

[Privacy policy](https://coolcatideas.com/privacy-policy/) · [Store terms (in Polish)](https://coolcatideas.com/pl/regulamin/)

= Optional icon CDN =

Icon files are local by default. If you select **CDN** in compatibility settings, browsers load Font Awesome from `maxcdn.bootstrapcdn.com` or Tabler Icons from `cdn.jsdelivr.net` on pages that use those icons. The provider receives the visitor's IP address and browser request headers; a referrer may be sent according to the browser's referrer policy. The plugin does not send review or license data with these requests.

[jsDelivr/BootstrapCDN privacy policy](https://www.jsdelivr.com/terms/privacy-policy) · [Terms of use](https://www.jsdelivr.com/terms/terms-of-use)

== Source code ==

Shared CCI Admin UI repository: https://github.com/cool-cat-ideas/cci-admin-ui. Source for the pinned version: https://github.com/cool-cat-ideas/cci-admin-ui/tree/v1.0.0. Product build instructions are in docs/building-from-source.md in the product source repository. Building requires the matching published UI source tag and release archives pinned by the product lockfile.

== Installation ==

Requires WordPress 6.2 or later and PHP 7.4 or later.

1. Open **Plugins > Add New > Upload Plugin**.
2. Upload the ZIP and activate **WP Posts Carousel All In One**.
3. Open **WP Posts Carousel > Carousels**.
4. Click **Add New**, choose a content source and check the preview.
5. Save the carousel.
6. Insert the **WP Posts Carousel** block on a page and choose the saved carousel.
7. Open the page on desktop and mobile.

If the carousel is empty, check that the selected posts are published, the chosen category has content and the item limit is greater than zero.

== Frequently Asked Questions ==

= Does it work if my theme already loads Owl Carousel or Swiper? =

Yes. Compatibility settings can disable bundled Owl assets, use a global Swiper instance and show warnings when another Owl/Swiper asset is detected.

= Is Pro required? =

No. The base plugin creates and renders content carousels, including linked cards for the WooCommerce `product` post type. Pro is needed for current store data, buying actions and the other paid tools listed above.

= Can templates and integrations be installed as add-ons? =

Other no-cost plugins can add templates or content sources. The paid WPC tools are already part of the single Pro plugin and are not bought as separate packages.

= Does the WordPress Popular Posts integration require another CCI add-on? =

No. The integration is built into the base plugin. Activate WordPress Popular Posts, then choose one of its ordering options in the carousel editor.

= Can developers change queries and output? =

Yes. The online developer documentation describes supported actions and filters for queries, templates and integrations.

== Screenshots ==

1. Manage saved carousels, copy their shortcodes and check publication status and product news.
2. Browse integrations that extend the available content sources and ordering options.
3. Compare installed templates and browse additional layouts. The demo includes Pro templates and optional extensions.
4. Control carousel libraries and icon assets, and set the default responsive breakpoints.
5. Choose the rendering engine and slide behavior, then drag cards to arrange the carousel sequence.
6. Select posts, pages or products, limit the result count and filter the source by taxonomy.
7. Choose which card elements appear and set title and excerpt limits. The price and sale controls shown require Pro.
8. Search for existing content or add a custom image slide, then arrange its position in the sequence.
9. Configure visitor-facing taxonomy filters and image loading options for the carousel.
10. Each saved carousel has its own shortcode, publication status and last update date.
11. Open the carousel preview in the editor and inspect its layout at a selected width.
12. Place a single-card product carousel beside article text. This example uses Aurora Cards and Pro WooCommerce buying features.
13. Present WordPress articles as image cards with excerpts and links to the full posts.
14. Aurora Cards is an optional template extension. The displayed prices, sale badges and Add to cart actions are supplied by Pro.
15. Product cards combine categories, prices and buying actions with arrow navigation and a progress indicator. Shown with Pro active.
16. A split layout places the featured image beside the article title, excerpt and Read more link.
17. Story Commerce combines a wide opening slide and supporting cards into a numbered shopping story. Included in Pro.
18. Video Showcase pairs the active video with a playlist of entries and timestamps. Included in Pro.
19. Commerce Luxe presents product images, sale prices, quantity controls and Add to cart buttons on a dark carousel background. Included in Pro.

== Changelog ==

= 2.0.0 =
* Added a redesigned visual administration experience.
* Added dedicated carousel storage instead of relying on post meta.
* Added visual carousel editor with responsive settings and manual slide order.
* Added Owl Carousel and Swiper renderer support.
* Added compatibility controls for bundled Owl/Swiper assets.
* Added bundled templates and an add-on-ready template registry.
* Added Gutenberg block with saved-carousel selector, frontend filters, performance controls and custom field mapping.
* Added marketplace endpoint support for official Cool Cat Ideas product feeds.
* Added Pro-ready feature and license gating hooks.

== Upgrade Notice ==

= 2.0.0 =
Initial public release of the rebuilt carousel editor and rendering system.
