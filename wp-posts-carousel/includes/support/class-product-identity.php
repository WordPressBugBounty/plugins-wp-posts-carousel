<?php
/** Keep saved content and activation state while public package names change. */
if (!defined('ABSPATH')) exit;
if (!class_exists('CCI_Carousel_Identity', false)) {
final class CCI_Carousel_Identity
{
    const PACKAGES = array(
        'wp-posts-carousel/wp-posts-carousel.php' => 'cci-posts-carousel/cci-posts-carousel.php',
        'wp-posts-carousel-pro/wp-posts-carousel-pro.php' => 'cci-posts-carousel-pro/cci-posts-carousel-pro.php',
        'related-content-for-wp-posts-carousel/related-content-for-wp-posts-carousel.php' => 'related-content-for-cci-posts-carousel/related-content-for-cci-posts-carousel.php',
        'aurora-cards-for-wp-posts-carousel/aurora-cards-for-wp-posts-carousel.php' => 'aurora-cards-for-cci-posts-carousel/aurora-cards-for-cci-posts-carousel.php',
    );

    public static function migrateList(array $active, array $available)
    {
        $result = array();
        foreach ($active as $plugin) {
            $next = isset(self::PACKAGES[$plugin]) && in_array(self::PACKAGES[$plugin], $available, true)
                ? self::PACKAGES[$plugin] : $plugin;
            if (!in_array($next, $result, true)) $result[] = $next;
        }
        return $result;
    }

    public static function activate($networkWide, $activatingPlugin)
    {
        $siteIDs = is_multisite() && $networkWide ? get_sites(array('fields' => 'ids', 'number' => 0)) : array(get_current_blog_id());
        $network = is_multisite() ? (array) get_site_option('active_sitewide_plugins', array()) : array();
        $states = array();
        foreach ($siteIDs as $siteID) {
            if (is_multisite()) switch_to_blog($siteID);
            try { $states[$siteID] = (array) get_option('active_plugins', array()); }
            finally { if (is_multisite()) restore_current_blog(); }
        }
        $available = array();
        foreach (self::PACKAGES as $new) if (is_file(WP_PLUGIN_DIR . '/' . $new)) $available[] = $new;
        foreach ($states as $siteID => $state) {
            if (is_multisite()) switch_to_blog($siteID);
            try {
                if (!get_option('cci_carousel_identity_backup')) add_option('cci_carousel_identity_backup', array('activePlugins' => array_values(array_diff($state, array($activatingPlugin))), 'createdAt' => gmdate('c')), '', false);
                $next = self::migrateList($state, $available);
                if ($next !== $state) update_option('active_plugins', $next);
            } finally { if (is_multisite()) restore_current_blog(); }
        }
        if (is_multisite()) {
            if (!get_site_option('cci_carousel_network_identity_backup')) {
                $previous = $network; unset($previous[$activatingPlugin]);
                add_site_option('cci_carousel_network_identity_backup', array('activePlugins' => $previous, 'createdAt' => gmdate('c')));
            }
            foreach (self::PACKAGES as $old => $new) {
                if (!isset($network[$old]) || !in_array($new, $available, true)) continue;
                if (!isset($network[$new])) $network[$new] = $network[$old];
                unset($network[$old]);
            }
            update_site_option('active_sitewide_plugins', $network);
        }
    }

    public static function redirectLegacyAdmin()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET' || ($_GET['page'] ?? '') !== 'wp-posts-carousel' || !current_user_can('edit_wp_posts_carousels')) return;
        $url = add_query_arg('page', 'cci-posts-carousel', admin_url('admin.php?' . ($_SERVER['QUERY_STRING'] ?? '')));
        wp_safe_redirect($url, 301);
        exit;
    }
}
// WordPress 6.5+ understands a dependency rename through its native slug filter.
add_filter('wp_plugin_dependencies_slug', static function ($slug) {
    return $slug === 'wp-posts-carousel' ? 'cci-posts-carousel' : $slug;
});
add_action('admin_init', array('CCI_Carousel_Identity', 'redirectLegacyAdmin'));
add_action('activated_plugin', static function ($plugin, $networkWide) {
    if (in_array($plugin, CCI_Carousel_Identity::PACKAGES, true)) {
        CCI_Carousel_Identity::activate($networkWide, $plugin);
    }
}, 10, 2);

}
