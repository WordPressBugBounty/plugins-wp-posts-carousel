=== WP Posts Carousel All In One ===
Contributors: coolcatideas
Tags: carousel, slider, posts, gutenberg, custom post types
Requires at least: 6.2
Tested up to: 7.1.3
Requires PHP: 7.4
Stable tag: 2.0.2
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Give your content a place to shine. Create responsive carousels with a visual editor, live preview, templates and a Gutenberg block.

== Description ==

Your latest articles, your favourite guides, the pages you wish more people would discover. Bring them together in a carousel that invites visitors to keep exploring.

[See it in action](https://wordpress-demo.coolcatideas.com/) | [Explore the product](https://coolcatideas.com/products/wp-posts-carousel-all-in-one/) | [Read the guide](https://coolcatideas.com/docs/wp-posts-carousel-all-in-one/2.0.0/) | [Get help](https://github.com/cool-cat-ideas/wp-posts-carousel-all-in-one/issues)

**WP Posts Carousel All In One** gives you a visual editor to choose your content, arrange your slides and preview the result. Add your saved carousel with a Gutenberg block or shortcode, then reuse it across your site. Change it once and every place using it stays in sync.

We're **Cool Cat Ideas**, and we're proud of this plugin. We built it for the enjoyable part of putting a page together: choosing what deserves attention and finding the right way to present it. We'd love to see what you create.

= Plenty to create with, right from the start =

* **Your content, your selection.** Display posts, pages and custom post types. Pick categories and tags, or choose individual items for a hand-picked collection.
* **A visual editor with a live preview.** Arrange slides by dragging them into place and check the result before publishing.
* **Layouts for large and small screens.** Set responsive breakpoints and adjust how your carousel behaves at different widths.
* **Templates you can make your own.** Start with Default, Light or Simple, then choose the card details, title lengths and excerpts that suit your page.
* **Place it where it belongs.** Choose a saved carousel in the Gutenberg block or paste its shortcode. Create as many carousels as you need.
* **Mix in your own slides.** Add custom image slides for announcements, featured links or an editorial sequence.
* **Help visitors narrow their choices.** Add category or tag filters above a carousel.
* **Put your custom fields to work.** Use post meta or ACF fields for badges, button labels, links and images.
* **Bring popular posts back into view.** Use reading statistics from [WordPress Popular Posts](https://wordpress.org/plugins/wordpress-popular-posts/) when that plugin is installed.
* **Choose how it runs.** Use Owl Carousel or Swiper, adjust navigation and autoplay, and control image loading and shared library compatibility.

The features above are included in this plugin. No Pro license is needed to start building.

= A few ideas for your first carousel =

Give your homepage a row of recent articles. Bring a set of useful guides together on a landing page. Create a hand-picked selection of pages for visitors who are new to your site. Or display WooCommerce products as linked content cards to help people browse your shop.

[Browse the live examples](https://wordpress-demo.coolcatideas.com/) and find a starting point for your own site. Some examples use Pro features or optional extensions; the screenshots below identify them.

= Take it further with Pro =

When your carousel becomes part of the shopping experience, **WP Posts Carousel Pro** adds:

* **WooCommerce shopping cards** with current prices, sale labels, ratings and Add to cart actions, plus bestseller and top-rated selections.
* **Commerce Luxe and Story Commerce** for product presentations and shopping stories.
* **Video Showcase** for a video player with an accompanying playlist.
* **Elementor integration** to place carousels while building your pages.
* **Engagement reports** to see how visitors interact with your carousels.
* **Autoplay progress indicators** and tools for working across WordPress Multisite.

These features come together in one Pro plugin, installed alongside this one. WooCommerce prices and buying actions require Pro; standard linked product cards do not.

[Explore Pro and compare editions](https://coolcatideas.com/products/wp-posts-carousel-all-in-one/)

= Made to grow with your ideas =

Optional extensions can add more layouts and content sources. [Aurora Cards](https://coolcatideas.com/products/wp-posts-carousel-aurora-cards/) adds another card design, while [Related Content](https://coolcatideas.com/products/wp-posts-carousel-related-content/) finds posts or products related to the page being viewed. Both are separate, no-cost additions.

For developers, the [documentation](https://coolcatideas.com/docs/wp-posts-carousel-all-in-one/2.0.0/) covers hooks and filters for your own queries, templates and integrations.

= We'd love to hear from you =

Need a hand, spotted a bug or have an idea? [Open an issue on GitHub](https://github.com/cool-cat-ideas/wp-posts-carousel-all-in-one/issues). Tell us what you're trying to build and include your WordPress and plugin versions so we can help. Please keep passwords, license keys and other private details out of public issues.

For help with an agreed scope, [contact Cool Cat Ideas](https://coolcatideas.com/contact/) about a private support service.

== Installation ==

Requires WordPress 6.2 or later and PHP 7.4 or later.

1. Open **Plugins > Add New > Upload Plugin**.
2. Upload the ZIP and activate **WP Posts Carousel All In One**.
3. Open **WP Posts Carousel** and click **New carousel**.
4. Choose your content and template, arrange the slides and check the preview.
5. Save, then add the **WP Posts Carousel** block to a page and select your carousel. You can also paste its shortcode into a Shortcode block.
6. Publish your page and enjoy your new carousel.

For a walkthrough, see the [getting started guide](https://coolcatideas.com/docs/wp-posts-carousel-all-in-one/2.0.0/).

== Frequently Asked Questions ==

= Why did my carousels stop working after upgrading from 1.x to 2.x? =

Version 2.x is a rewrite of the plugin, including the carousel data model, editor, widgets and shortcode configuration. Settings from 1.x are not automatically converted. Open WP Posts Carousel > Dashboard, click New carousel, recreate each carousel, then save and publish it. Replace every old shortcode and select the new carousel in the current block or widget wherever it was displayed. Creating a replacement alone does not reconnect those placements. Clear cache after saving and check the published pages on desktop and mobile. The [upgrade guide](https://coolcatideas.com/docs/wp-posts-carousel-all-in-one/2.0.x/upgrading-from-1.x/) includes the steps and a screenshot of the new dashboard.

= Can I reuse the same carousel on several pages? =

Yes. Select the same saved carousel in the Gutenberg block or reuse its shortcode. Changes to that carousel apply everywhere it is used.

= Can I choose how the carousel looks on mobile? =

Yes. Use responsive settings to adjust the layout for different screen widths and check the result in the editor preview.

= Does it work if my theme already loads Owl Carousel or Swiper? =

Yes. Compatibility settings can disable bundled Owl assets, use a global Swiper instance and show warnings when another Owl/Swiper asset is detected.

= Is Pro required? =

No. The base plugin creates and renders content carousels, including linked cards for the WooCommerce `product` post type. Pro is needed for current store data, buying actions and the other paid tools listed above.

= Can templates and integrations be installed as add-ons? =

Other no-cost plugins can add templates or content sources. The paid WPC tools are already part of the single Pro plugin and are not bought as separate packages.

= Does the WordPress Popular Posts integration require another CCI add-on? =

No. The integration is built into the base plugin. Activate WordPress Popular Posts, then choose one of its ordering options in the carousel editor.

= Can developers change queries and output? =

Yes. The [developer documentation](https://coolcatideas.com/docs/wp-posts-carousel-all-in-one/2.0.0/) describes supported actions and filters for queries, templates and integrations.

= Why is my carousel empty? =

Check that the selected content is published, the chosen categories or tags contain matching items, and the item limit is greater than zero.

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

== External services ==

= Product news and catalog =

The plugin admin fetches news, templates and integrations from [Cool Cat Ideas](https://coolcatideas.com/) when its cached catalog needs refreshing. Requests include the product identifier, language and news limit. The service receives the server IP; catalog image hosts receive the browser IP and request headers. Feed requests do not add the site URL or WordPress version. Your saved carousels keep working if the service is unavailable. [Privacy policy](https://coolcatideas.com/privacy-policy/) | [Store terms](https://coolcatideas.com/terms-and-conditions/).

= Optional feedback =

Only when you accept the review consent and click **Save feedback**, the plugin sends your rating and comment to Cool Cat Ideas for moderation. The submission includes product and site context (versions, platform, website URL, language, environment and Pro status), the admin section, time, review-page preferences, consent details and a duplicate-prevention hash. [Privacy policy](https://coolcatideas.com/privacy-policy/).

= Optional icon CDN =

Icons are local by default. Choosing **CDN** in compatibility settings loads Font Awesome from BootstrapCDN or Tabler Icons from jsDelivr. These hosts receive the browser IP and request headers, potentially including the referrer. [CDN service](https://www.jsdelivr.com/) | [Privacy policy](https://www.jsdelivr.com/terms/privacy-policy) | [Terms of use](https://www.jsdelivr.com/terms/terms-of-use).

== Source code ==

[Plugin source](https://github.com/cool-cat-ideas/wp-posts-carousel-all-in-one) | [Build instructions](https://github.com/cool-cat-ideas/wp-posts-carousel-all-in-one/blob/main/docs/building-from-source.md) | [CCI Admin UI source (v1.0.0)](https://github.com/cool-cat-ideas/cci-admin-ui/tree/v1.0.0)

The plugin includes compiled JavaScript and CSS. The repositories above contain the readable sources and build tools; the product lockfile pins the shared UI packages.

== Changelog ==

= 2.0.2 =
* Fix retrying a carousel after a temporary API or template stylesheet loading error.
* Reuse slide data when a REST response includes both JSON and HTML, avoiding a duplicate content query.
* Load only the template styles used on the page, including carousels added dynamically. Keep all template styles available in editor previews.
* Declare compatibility with WordPress 7.1.3.

= 2.0.1 =
* Fix child-theme template precedence. Incomplete overrides fall back to the bundled template.
* Exclude manifests without a usable PHP view from template discovery.
* Clarify settings, stylesheet overrides and supported extension contracts.

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

= 2.0.2 =
More reliable carousel loading and fewer duplicate queries and unused template styles. Existing 2.x carousel settings are preserved.

= 2.0.0 =
Initial public release of the rebuilt carousel editor and rendering system.
