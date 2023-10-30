<?php
/**
 * Plugin Name: Guildenberg Helper Plugin
 * Description: Helps you with setting up your Black Friday sales for Guildenberg. Show host information on your landing pages using shortcodes.
 * Version: 1.0
 * Author: Bowe Frankema
 * Author URI: https://guildenberg.com
 */

class Guildenberg_Helper_Plugin
{
    public function __construct()
    {
        // Hook into WordPress 'init' action to register the custom post type
        add_action('init', [$this, 'create_gb_hosts_cpt']);

        // Add settings page for the plugin
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_init', [$this, 'register_settings']);

        // Hook into WordPress to register shortcode
        add_shortcode('gb-host-all', [$this, 'gb_host_all_shortcode']);
        add_shortcode('gb-host-name', [$this, 'gb_host_name_shortcode']);
        add_shortcode('gb-host-description', [$this, 'gb_host_description_shortcode']);
        add_shortcode('gb-host-logo', [$this, 'gb_host_logo_shortcode']);
        add_shortcode('gb-host-slug', [$this, 'gb_host_slug_shortcode']);

         // Hook for plugin activation
        register_activation_hook(__FILE__, [$this, 'plugin_activation']);

        // Hook for checking if plugin is just activated
        add_action('admin_init', [$this, 'check_activation_redirect']);
    }

    public function plugin_activation()
    {
        add_option('guildenberg_helper_activated', 'yes');
    }

    public function check_activation_redirect()
    {
        if (get_option('guildenberg_helper_activated') === 'yes') {
            // Make sure to delete the flag so this doesn't run every time
            delete_option('guildenberg_helper_activated');

            // Perform your redirection here
            wp_redirect(admin_url('options-general.php?page=guildenberg-helper-settings'));
            exit;
        }
    }

    public function add_plugin_page()
    {
        // Add settings page under the "Settings" menu
        add_options_page(
            'Guildenberg Helper Settings',
            'Guildenberg Helper',
            'manage_options',
            'guildenberg-helper-settings',
            [$this, 'create_admin_page']
        );
    }

    public function create_admin_page()
    {
        ?>
        <div class="wrap">
            <img src="https://guildenberg.com/wp-content/uploads/2023/02/guildenberg-logo.png.webp" style="max-width: 300px; margin-top: 20px;">
            <h1 style="font-weight: bold;
    margin-bottom: 20px;
    font-size: 17px;
    color: #0594C2;
    text-transform: uppercase;">Show Host Info on Your Landing Pages</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('guildenberg_helper_option_group');
                do_settings_sections('guildenberg-helper-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings()
    {
        register_setting(
            'guildenberg_helper_option_group',
            'guildenberg_helper_affiliate_refs'
        );

        add_settings_section(
            'guildenberg_helper_general_section',
            'Tracking Your URL Parameters',
            function () {
                echo '<p>Here you can configure the Affilate URL parameters that you want the shortcodes to look out for on your landing pages.</p>';
            },
            'guildenberg-helper-settings'
        );

        add_settings_field(
            'affiliate_refs',
            'Affiliate Tracking URL Parameters',
            [$this, 'render_affiliate_refs_field'],
            'guildenberg-helper-settings',
            'guildenberg_helper_general_section'
        );
    }

    public function render_affiliate_refs_field()
    {
        $value = get_option('guildenberg_helper_affiliate_refs', 'ref');
        echo "<input type='text' name='guildenberg_helper_affiliate_refs' value='$value' />";
        echo '<p class="description">Enter one or multiple parameters you are using to track affiliates, without the "?=" (For example, "ref,aff").
        <br>This would correspond to <br>'. get_site_url(). '/landing/?ref=hosting-company and <br>'. get_site_url(). '/?aff=hosting-company </p>

         <h2>How to automatically show Host information on your landing pages.</h2>
        <p>You will have a new Post Type called GB Hosts, this is where you add your host info you would like to show.</p>
        <ul>
            <li><code>slug</code>This needs to match up with your affiliate parameter. ie if your url is /?ref=kinsta you name the slug "kinsta"</li>
            <li><code>Post Title</code> ie Kinsta Hosting</li>
            <li><code>Post Content</code>This is the description for Kinsta</li>
            <li><code>Featured Image</code> - The logo of Kinsta.</li>
        </ul>

        <h2>Done? Here\'s how to use the shortcodes:</h2>
        <p>Use the following shortcodes anywhere on your landing pages. If there is no match between your Affiliate URL and a host nothing is shown.</p>
        <ul>
            <li><code>[gb-host-name]</code> - Displays the Host name</li>
            <li><code>[gb-host-description]</code> - Displays the Host description</li>
            <li><code>[gb-host-logo]</code> - Displays the GB Host logo</li>
            <li><code>[gb-host-all]</code> - Displays a simple template you can overwrite with all information. To overwrite in your theme folder create a template called host-information.php. You can now customise the template as needed!</li>
        </ul>

        <h2>Advanced Usage  - Use <code>guildenberg_get_slug()</code> for display conditions in your Page Builder/code.</h2>
        In some pagebuilders you can conditionally show/hide sections.
        For example in Bricks Builder you use the display conditions and use this helper to get the slug of the host:
            <img src="'. plugin_dir_url(__FILE__) . 'images/conditions.png" style="max-width: 300px; margin-top: 20px; display: block;">
        ';
    }

    public function create_gb_hosts_cpt()
    {
        $labels = [
            'name' => _x('GB Hosts', 'Post type general name', 'textdomain'),
            'singular_name' => _x('GB Host', 'Post type singular name', 'textdomain'),
            'menu_name' => _x('GB Hosts', 'Admin Menu text', 'textdomain'),
            'name_admin_bar' => _x('GB Host', 'Add New on Toolbar', 'textdomain'),
            'add_new' => __('Add New', 'textdomain'),
            'add_new_item' => __('Add New GB Host', 'textdomain'),
            'new_item' => __('New GB Host', 'textdomain'),
            'edit_item' => __('Edit GB Host', 'textdomain'),
            'view_item' => __('View GB Host', 'textdomain'),
            'all_items' => __('All GB Hosts', 'textdomain'),
            'search_items' => __('Search GB Hosts', 'textdomain'),
            'parent_item_colon' => __('Parent GB Hosts:', 'textdomain'),
            'not_found' => __('No GB Hosts found.', 'textdomain'),
            'not_found_in_trash' => __('No GB Hosts found in Trash.', 'textdomain'),
            'featured_image' => _x('GB Host Featured Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'textdomain'),
            'set_featured_image' => _x('Set featured image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'textdomain'),
            'remove_featured_image' => _x('Remove featured image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'textdomain'),
            'use_featured_image' => _x('Use as featured image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'textdomain'),
            'archives' => _x('GB Host archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'textdomain'),
            'insert_into_item' => _x('Insert into GB Host', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'textdomain'),
            'uploaded_to_this_item' => _x('Uploaded to this GB Host', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'textdomain'),
            'filter_items_list' => _x('Filter GB Hosts list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'textdomain'),
            'items_list_navigation' => _x('GB Hosts list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'textdomain'),
            'items_list' => _x('GB Hosts list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'textdomain'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'gb-hosts'],
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields', 'slug'],
        ];

        register_post_type('gb_hosts', $args);
    }

    public function gb_host_name_shortcode($atts)
    {
        $ref = $this->get_ref();
        if (!$ref) {
            return '';
        }

        $host = $this->get_gb_host_by_ref($ref);
        if (!$host) {
            return '';
        }

        return esc_html($host->post_title);
    }

    // Shortcode to display GB Host description (content)
    public function gb_host_description_shortcode($atts)
    {
        $ref = $this->get_ref();
        if (!$ref) {
            return '';
        }

        $host = $this->get_gb_host_by_ref($ref);
        if (!$host) {
            return '';
        }

        return apply_filters('the_content', $host->post_content);
    }

    public function gb_host_slug_shortcode($atts)
    {
        $ref = $this->get_ref();
        if (!$ref) {
            return '';
        }

        $host = $this->get_gb_host_by_ref($ref);
        if (!$host) {
            return '';
        }

        $slug = $host->post_name;
        if (!$slug) {
            return '';
        }

        return $slug;

    }

    // Shortcode to display GB Host logo (featured image)
    public function gb_host_logo_shortcode($atts)
    {
        $ref = $this->get_ref();
        if (!$ref) {
            return '';
        }

        $host = $this->get_gb_host_by_ref($ref);
        if (!$host) {
            return '';
        }

        $thumbnail_id = get_post_thumbnail_id($host->ID);
        if (!$thumbnail_id) {
            return '';
        }

        return wp_get_attachment_image($thumbnail_id, 'full');
    }

    public function gb_host_all_shortcode($atts)
    {
        $ref = $this->get_ref();
        if (!$ref) {
            return '';
        }

        // WP_Query arguments for fetching the relevant 'gb_hosts' post
        $args = [
            'name' => $ref,
            'post_type' => 'gb_hosts',
            'numberposts' => 1,
            'post_status' => 'publish',
        ];

        // Run WP_Query
        $query = new WP_Query($args);

        // If no matching 'gb_hosts' post is found, exit the function gracefully
        if (!$query->have_posts()) {
            return ''; // Return an empty string
        }

        // Start output buffering
        ob_start();

        // Locate the template file for rendering the post
        $template_path = locate_template('gb-host-template.php');

        // Fall back to default template if custom template is not found
        if (!$template_path) {
            $template_path = plugin_dir_path(__FILE__) . 'templates/host-information.php';
        }

        // Loop through the posts and include the template file for each
        while ($query->have_posts()):
            $query->the_post();
            include $template_path;
        endwhile;

        // Reset post data
        wp_reset_postdata();

        // Get the buffered content and end output buffering
        return ob_get_clean();
    }

    private function get_gb_host_by_ref($ref)
    {
        $args = [
            'name' => $ref,
            'post_type' => 'gb_hosts',
            'numberposts' => 1,
            'post_status' => 'publish',
        ];

        $hosts = get_posts($args);
        return $hosts ? array_shift($hosts) : null;
    }

    private function get_ref()
    {
        $refKeys = get_option('guildenberg_helper_affiliate_refs', 'ref');
        $refKeys = explode(',', $refKeys);

        foreach ($refKeys as $key) {
            $key = trim($key);
            if (isset($_COOKIE[$key])) {
                return $_COOKIE[$key];
            }
            if (isset($_GET[$key])) {
                return $_GET[$key];
            }
        }

        return null;
    }
}

// Initialize the plugin
new Guildenberg_Helper_Plugin();

function guildenberg_get_slug() {

    $refKeys = get_option('guildenberg_helper_affiliate_refs', 'ref');
    $refKeys = explode(',', $refKeys);

    foreach ($refKeys as $key) {
        $key = trim($key);
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
    }
    return null;
}

