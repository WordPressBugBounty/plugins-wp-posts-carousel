<?php
/**
 * Author: Marcin Gierada
 * Author URI: https://coolcatideas.com/
 * Author Email: info@coolcatideas.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds clear WP Posts Carousel add-on information to WordPress > Plugins.
 */
class WP_Posts_Carousel_Admin_Plugins_Page
{
    const PRO_PLUGIN = 'wp-posts-carousel-pro/wp-posts-carousel-pro.php';

    public function __construct()
    {
        add_action('admin_init', array($this, 'normalize_dependency_display_name'), 1);
        add_action('admin_head-plugins.php', array($this, 'print_styles'));
        add_action('admin_notices', array($this, 'render_summary_notices'));
        add_filter('plugin_action_links_' . WP_POSTS_CAROUSEL_BASENAME, array($this, 'base_plugin_action_links'), 20);
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 20, 4);

        foreach (array_keys($this->known_plugins()) as $plugin_file) {
            add_filter(
                'plugin_action_links_' . $plugin_file,
                function ($links) use ($plugin_file) {
                    return $this->plugin_action_links($links, $plugin_file);
                },
                20
            );
        }
    }

    public function print_styles()
    {
        ?>
        <style>
            .plugins .cci-wpc-addon-meta-badge {
                display: inline-flex;
                align-items: center;
                border: 1px solid #fcd34d;
                background: #fffbeb;
                color: #b45309;
                border-radius: 999px;
                font-size: 11px;
                font-weight: 700;
                line-height: 1;
                padding: 4px 8px;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .plugins .cci-wpc-addon-meta-badge-free {
                border-color: #bbf7d0;
                background: #f0fdf4;
                color: #166534;
            }

            .plugins .cci-wpc-addon-inline-status {
                color: #50575e;
                font-weight: 600;
            }

            .plugins .cci-wpc-addon-inline-status-ready {
                color: #166534;
            }

            .plugins .cci-wpc-addon-inline-status-warning {
                color: #8a4f00;
            }

            .plugins .cci-wpc-addon-inline-status-blocked {
                color: #b32d2e;
            }
        </style>
        <?php
    }

    /**
     * Keep WordPress' native dependency row while correcting the public
     * WordPress.org name cached for this locally developed product.
     */
    public function normalize_dependency_display_name()
    {
        global $pagenow;

        if ($pagenow !== 'plugins.php' && $pagenow !== 'plugin-install.php') {
            return;
        }

        $dependencies = get_site_transient('wp_plugin_dependencies_plugin_data');
        $dependencies = is_array($dependencies) ? $dependencies : array();
        $dependency = isset($dependencies['wp-posts-carousel']) && is_array($dependencies['wp-posts-carousel'])
            ? $dependencies['wp-posts-carousel']
            : array();

        $dependency['name'] = 'WP Posts Carousel All In One';
        $dependency['Name'] = 'WP Posts Carousel All In One';
        $dependency['slug'] = 'wp-posts-carousel';
        $dependency['last_updated'] = gmdate('Y-m-d H:i:s');
        $dependencies['wp-posts-carousel'] = $dependency;

        set_site_transient('wp_plugin_dependencies_plugin_data', $dependencies, 0);
        set_site_transient('wp_plugin_dependencies_plugin_timeout_wp-posts-carousel', true, 12 * HOUR_IN_SECONDS);
    }

    public function plugin_row_meta($links, $file, $plugin_data = array(), $status = '')
    {
        $plugins = $this->known_plugins();

        if (!isset($plugins[$file])) {
            return $links;
        }

        $definition = $plugins[$file];
        $badge_class = !empty($definition['free']) ? ' cci-wpc-addon-meta-badge-free' : '';
        $state = $this->status_for_plugin($file, $definition);
        $tone = isset($state['tone']) ? sanitize_html_class($state['tone']) : 'info';

        if (!empty($definition['badge'])) {
            $links[] = '<span class="cci-wpc-addon-meta-badge' . esc_attr($badge_class) . '">' . esc_html($definition['badge']) . '</span>';
        }

        $links[] = '<span class="cci-wpc-addon-inline-status cci-wpc-addon-inline-status' . esc_attr($tone) . '">' . esc_html($state['title']) . '</span>';
        $links[] = '<span>' . esc_html($definition['meta']) . '</span>';

        return $links;
    }

    public function base_plugin_action_links($links)
    {
        array_unshift(
            $links,
            '<a href="' . esc_url(admin_url('admin.php?page=wp-posts-carousel')) . '">' . esc_html__('Open WP Posts Carousel', 'wp-posts-carousel') . '</a>'
        );

        return $links;
    }

    public function plugin_action_links($links, $plugin_file)
    {
        $dashboard_link = '<a href="' . esc_url(admin_url('admin.php?page=wp-posts-carousel')) . '">' . esc_html__('Open WP Posts Carousel', 'wp-posts-carousel') . '</a>';

        if ($plugin_file === self::PRO_PLUGIN) {
            array_unshift($links, $dashboard_link);
            return $links;
        }

        $definition = $this->known_plugins()[$plugin_file] ?? null;

        if (!$definition) {
            return $links;
        }

        $custom_links = array($dashboard_link);
        $missing_dependencies = $this->missing_required_plugins($definition);

        foreach ($missing_dependencies as $dependency_file => $dependency_label) {
            $custom_links[] = $this->action_link($this->dependency_action($dependency_file, $dependency_label));
            break;
        }

        if (!empty($definition['free'])) {
            return array_merge($custom_links, $links);
        }

        if (empty($missing_dependencies) && !$this->license_allows_feature($definition['feature'], $plugin_file, $definition['type'])) {
            $custom_links[] = '<a href="' . esc_url(admin_url('admin.php?page=wp-posts-carousel')) . '">' . esc_html__('Activate Pro license', 'wp-posts-carousel') . '</a>';
        }

        return array_merge($custom_links, $links);
    }

    public function render_summary_notices()
    {
        if (!$this->is_plugins_screen()) {
            return;
        }

        $issues = $this->summary_issues();

        foreach ($issues as $issue) {
            printf(
                '<div class="notice notice-%1$s"><p><strong>%2$s</strong> %3$s %4$s</p></div>',
                esc_attr($issue['type']),
                esc_html($issue['title']),
                esc_html($issue['message']),
                !empty($issue['action'])
                    ? '<a class="button button-small" href="' . esc_url($issue['action']['url']) . '">' . esc_html($issue['action']['label']) . '</a>'
                    : ''
            );
        }
    }

    private function status_for_plugin($plugin_file, array $definition)
    {
        if (!empty($definition['free'])) {
            return $this->free_status($plugin_file, $definition);
        }

        if ($plugin_file === self::PRO_PLUGIN) {
            return $this->pro_status($plugin_file);
        }

        return $this->paid_addon_status($plugin_file, $definition);
    }

    private function free_status($plugin_file, array $definition)
    {
        $missing_dependencies = $this->missing_required_plugins($definition);

        if (!empty($missing_dependencies)) {
            return array(
                'tone' => 'blocked',
                'title' => sprintf(
                    /* translators: %s: plugin names */
                    __('Requires: %s.', 'wp-posts-carousel'),
                    $this->format_plugin_labels($missing_dependencies)
                ),
                'message' => $definition['missing_dependency'],
                'actions' => array($this->dependency_action(array_key_first($missing_dependencies), reset($missing_dependencies)), $this->dashboard_action()),
            );
        }

        if ($this->is_plugin_active($plugin_file)) {
            return array(
                'tone' => 'ready',
                'title' => __('The add-on is active.', 'wp-posts-carousel'),
                'message' => $definition['ready'],
                'actions' => array($this->dashboard_action()),
            );
        }

        return array(
            'tone' => 'warning',
            'title' => __('The add-on is inactive.', 'wp-posts-carousel'),
            'message' => __('Activate it to use this integration in carousel queries.', 'wp-posts-carousel'),
            'actions' => array($this->activate_action($plugin_file), $this->dashboard_action()),
        );
    }

    private function pro_status($plugin_file)
    {
        $license = $this->license_data();

        if (!$this->is_plugin_active($plugin_file)) {
            return array(
                'tone' => 'warning',
                'title' => __('WP Posts Carousel All In One Pro is installed but inactive.', 'wp-posts-carousel'),
                'message' => __('Activate the Pro plugin, then enter the license key in the carousel dashboard.', 'wp-posts-carousel'),
                'actions' => array($this->activate_action($plugin_file), $this->dashboard_action(__('Open license panel', 'wp-posts-carousel'))),
            );
        }

        if (($license['status'] ?? '') === 'active') {
            return array(
                'tone' => !empty($license['writeLocked']) ? 'warning' : 'ready',
                'title' => !empty($license['writeLocked'])
                    ? __('Pro is running in read-only mode.', 'wp-posts-carousel')
                    : __('Pro license is active.', 'wp-posts-carousel'),
                'message' => !empty($license['writeLocked'])
                    ? __('Frontend carousels remain available, but Pro editing is locked until the license server responds again.', 'wp-posts-carousel')
                    : __('All bundled Pro modules are available on this site.', 'wp-posts-carousel'),
                'actions' => array($this->dashboard_action()),
            );
        }

        return array(
            'tone' => 'warning',
            'title' => __('The Pro plugin is active, but the license is missing.', 'wp-posts-carousel'),
            'message' => __('Enter a license key to unlock Pro templates, integrations and editor controls.', 'wp-posts-carousel'),
            'actions' => array($this->dashboard_action(__('Activate license', 'wp-posts-carousel'), 'button button-primary')),
        );
    }

    private function paid_addon_status($plugin_file, array $definition)
    {
        $missing_dependencies = $this->missing_required_plugins($definition);

        if (!empty($missing_dependencies)) {
            return array(
                'tone' => 'blocked',
                'title' => sprintf(
                    /* translators: %s: plugin names */
                    __('Requires: %s.', 'wp-posts-carousel'),
                    $this->format_plugin_labels($missing_dependencies)
                ),
                'message' => $definition['missing_dependency'],
                'actions' => array($this->dependency_action(array_key_first($missing_dependencies), reset($missing_dependencies)), $this->dashboard_action()),
            );
        }

        if (!$this->is_plugin_active(self::PRO_PLUGIN)) {
            return array(
                'tone' => 'warning',
                'title' => __('Requires WP Posts Carousel All In One Pro.', 'wp-posts-carousel'),
                'message' => __('This add-on is installed, but Pro features remain locked until WP Posts Carousel All In One Pro is active.', 'wp-posts-carousel'),
                'actions' => array($this->activate_action(self::PRO_PLUGIN), $this->dashboard_action(__('Open license panel', 'wp-posts-carousel'))),
            );
        }

        $feature = sanitize_key($definition['feature']);

        if (!$this->license_allows_feature($feature, $plugin_file, $definition['type'])) {
            return array(
                'tone' => 'warning',
                'title' => __('This add-on requires a Pro license.', 'wp-posts-carousel'),
                'message' => $definition['license_required'],
                'actions' => array($this->dashboard_action(__('Activate license', 'wp-posts-carousel'), 'button button-primary')),
            );
        }

        if (!$this->is_plugin_active($plugin_file)) {
            return array(
                'tone' => 'ready',
                'title' => __('The license is ready. Activate the add-on.', 'wp-posts-carousel'),
                'message' => __('Your license includes this add-on. Activate it to make it available in the carousel editor.', 'wp-posts-carousel'),
                'actions' => array($this->activate_action($plugin_file), $this->dashboard_action()),
            );
        }

        return array(
            'tone' => 'ready',
            'title' => __('The add-on is ready to use.', 'wp-posts-carousel'),
            'message' => $definition['ready'],
            'actions' => array($this->dashboard_action()),
        );
    }

    private function known_plugins()
    {
        return array(
            self::PRO_PLUGIN => array(
                'name' => 'WP Posts Carousel All In One Pro',
                'badge' => 'PRO',
                'meta' => __('License valid for this site.', 'wp-posts-carousel'),
            ),
        );
    }

    private function summary_issues()
    {
        $issues = array();
        $known_plugins = $this->known_plugins();
        $paid_addons = array_keys(array_filter(
            $known_plugins,
            function ($definition, $plugin_file) {
                return $plugin_file !== self::PRO_PLUGIN && empty($definition['free']);
            },
            ARRAY_FILTER_USE_BOTH
        ));
        $installed_paid_addons = array_filter($paid_addons, array($this, 'is_plugin_installed'));

        foreach ($known_plugins as $plugin_file => $definition) {
            if ($plugin_file === self::PRO_PLUGIN || !$this->is_plugin_installed($plugin_file)) {
                continue;
            }

            $missing_dependencies = $this->missing_required_plugins($definition);

            if (empty($missing_dependencies)) {
                continue;
            }

            $first_dependency_file = array_key_first($missing_dependencies);
            $first_dependency_label = reset($missing_dependencies);

            $issues[] = array(
                'type' => 'error',
                'title' => __('A required plugin is missing.', 'wp-posts-carousel'),
                'message' => sprintf(
                    /* translators: 1: add-on name, 2: plugin names */
                    __('%1$s requires an active plugin: %2$s.', 'wp-posts-carousel'),
                    $definition['name'],
                    $this->format_plugin_labels($missing_dependencies)
                ),
                'action' => $this->dependency_action($first_dependency_file, $first_dependency_label),
            );
        }

        if (!empty($installed_paid_addons) && !$this->is_plugin_active(self::PRO_PLUGIN)) {
            $issues[] = array(
                'type' => 'warning',
                'title' => __('Pro add-ons are installed.', 'wp-posts-carousel'),
                'message' => __('Activate WP Posts Carousel All In One Pro, then activate the license in the carousel dashboard.', 'wp-posts-carousel'),
                'action' => $this->is_plugin_installed(self::PRO_PLUGIN)
                    ? array(
                        'label' => __('Activate WP Posts Carousel All In One Pro', 'wp-posts-carousel'),
                        'url' => $this->activate_url(self::PRO_PLUGIN),
                    )
                    : $this->dashboard_action(__('Open WP Posts Carousel', 'wp-posts-carousel')),
            );
        }

        $license = $this->license_data();
        $license_inactive = !empty($installed_paid_addons) && ($license['status'] ?? '') !== 'active';

        if ($license_inactive) {
            $issues[] = array(
                'type' => 'warning',
                'title' => __('The WP Posts Carousel All In One Pro license is inactive.', 'wp-posts-carousel'),
                'message' => __('Installed Pro templates and integrations remain locked until a license is activated for this site.', 'wp-posts-carousel'),
                'action' => $this->dashboard_action(__('Activate license', 'wp-posts-carousel')),
            );
        }

        return $issues;
    }

    private function license_data()
    {
        return apply_filters(
            'wp_posts_carousel_license_data',
            array(
                'status' => 'inactive',
                'label' => __('Inactive', 'wp-posts-carousel'),
            )
        );
    }

    private function license_allows_feature($feature, $plugin_file, $type)
    {
        return (bool) apply_filters(
            'wp_posts_carousel_license_allows_feature',
            false,
            $feature,
            array(
                'addon' => $plugin_file,
                'type' => $type,
                'context' => 'admin',
            )
        );
    }

    private function dashboard_action($label = '', $class = 'button')
    {
        return array(
            'label' => $label ? $label : __('Open WP Posts Carousel', 'wp-posts-carousel'),
            'url' => admin_url('admin.php?page=wp-posts-carousel'),
            'class' => $class,
        );
    }

    private function activate_action($plugin_file)
    {
        return array(
            'label' => __('Activate plugin', 'wp-posts-carousel'),
            'url' => $this->activate_url($plugin_file),
            'class' => 'button button-primary',
        );
    }

    private function activate_url($plugin_file)
    {
        return wp_nonce_url(
            self_admin_url('plugins.php?action=activate&plugin=' . rawurlencode($plugin_file)),
            'activate-plugin_' . $plugin_file
        );
    }

    private function is_plugin_active($plugin_file)
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active($plugin_file)
            || (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network($plugin_file));
    }

    private function is_plugin_installed($plugin_file)
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();

        return isset($plugins[$plugin_file]);
    }

    private function is_plugins_screen()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        return $screen && $screen->id === 'plugins';
    }

    private function missing_required_plugins(array $definition)
    {
        $missing = array();
        $required_plugins = isset($definition['requires_plugins']) && is_array($definition['requires_plugins'])
            ? $definition['requires_plugins']
            : array();

        foreach ($required_plugins as $plugin_file => $label) {
            if (!$this->is_plugin_active($plugin_file)) {
                $missing[$plugin_file] = $label;
            }
        }

        return $missing;
    }

    private function format_plugin_labels(array $plugins)
    {
        return implode(', ', array_values($plugins));
    }

    private function dependency_action($plugin_file, $label)
    {
        if ($this->is_plugin_installed($plugin_file)) {
            return array(
                'label' => sprintf(
                    /* translators: %s: plugin name */
                    __('Activate %s', 'wp-posts-carousel'),
                    $label
                ),
                'url' => $this->activate_url($plugin_file),
                'class' => 'button button-primary',
            );
        }

        return array(
            'label' => sprintf(
                /* translators: %s: plugin name */
                __('Install %s', 'wp-posts-carousel'),
                $label
            ),
            'url' => add_query_arg(
                array(
                    'tab' => 'search',
                    'type' => 'term',
                    's' => $label,
                ),
                self_admin_url('plugin-install.php')
            ),
            'class' => 'button button-primary',
        );
    }

    private function action_link(array $action)
    {
        return '<a href="' . esc_url($action['url']) . '">' . esc_html($action['label']) . '</a>';
    }
}

return new WP_Posts_Carousel_Admin_Plugins_Page();
