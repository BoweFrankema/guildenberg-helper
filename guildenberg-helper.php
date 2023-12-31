<?php
/**
 * Plugin Name: Guildenberg Helper Plugin
 * Description: Helps you with setting up your Black Friday sales for Guildenberg. Show host information on your landing pages using shortcodes.
 * Version: 1.0.1
 * Author: Bowe Frankema
 * Author URI: https://guildenberg.com
 * GitHub Plugin URI: https://github.com/BoweFrankema/guildenberg-helper
 * Primary Branch: main
 */

class Guildenberg_Helper_Plugin
{
    private $host_data;
    public function __construct()
    {
        $this->host_data = $this->get_normalized_gb_host_by_ref();
        // Hook into WordPress 'init' action to register the custom post type

        // Add settings page for the plugin
        add_action("admin_menu", [$this, "add_plugin_page"]);
        add_action("admin_init", [$this, "register_settings"]);

        // Hook into WordPress to register shortcode
        add_shortcode("gb-host-all", [$this, "gb_host_all_shortcode"]);
        add_shortcode("gb-host-name", [$this, "gb_host_name_shortcode"]);
        add_shortcode("gb-host-description", [
            $this,
            "gb_host_description_shortcode",
        ]);
        add_shortcode("gb-host-logo", [$this, "gb_host_logo_shortcode"]);
        add_shortcode("gb-host-slug", [$this, "gb_host_slug_shortcode"]);

        // Hook for plugin activation
        register_activation_hook(__FILE__, [$this, "plugin_activation"]);

        // Hook for checking if plugin is just activated
        add_action("admin_init", [$this, "check_activation_redirect"]);
    }

    public function plugin_activation()
    {
        add_option("guildenberg_helper_activated", "yes");
    }

    public function check_activation_redirect()
    {
        if (get_option("guildenberg_helper_activated") === "yes") {
            // Make sure to delete the flag so this doesn't run every time
            delete_option("guildenberg_helper_activated");

            // Perform your redirection here
            wp_redirect(
                admin_url(
                    "options-general.php?page=guildenberg-helper-settings"
                )
            );
            exit();
        }
    }

    public function add_plugin_page()
    {
        // Add settings page under the "Settings" menu
        add_options_page(
            "Guildenberg Helper Settings",
            "Guildenberg Helper",
            "manage_options",
            "guildenberg-helper-settings",
            [$this, "create_admin_page"]
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
                settings_fields("guildenberg_helper_option_group");
                do_settings_sections("guildenberg-helper-settings");
                submit_button();?>
            </form>
        </div>
        <?php
    }

    public function register_settings()
    {
        register_setting(
            "guildenberg_helper_option_group",
            "guildenberg_helper_affiliate_refs"
        );

        add_settings_section(
            "guildenberg_helper_general_section",
            "Tracking Your URL Parameters",
            function () {
                echo "<p>Here you can configure the Affilate URL parameters that you want the shortcodes to look out for on your landing pages.</p>";
            },
            "guildenberg-helper-settings"
        );

        add_settings_field(
            "affiliate_refs",
            "Affiliate Tracking URL Parameters",
            [$this, "render_affiliate_refs_field"],
            "guildenberg-helper-settings",
            "guildenberg_helper_general_section"
        );
    }

    public function render_affiliate_refs_field()
    {
        $value = get_option("guildenberg_helper_affiliate_refs", "?ref=");
        echo "<input type='text' name='guildenberg_helper_affiliate_refs' value='$value' />";
        echo '<p class="description">Enter the exact affiliate string you want to use. For example "?ref=" or "?aff=" or "#" <br><br>Visit <a href="https://github.com/BoweFrankema/guildenberg-helper" target="_blank">The GitHub Repo to see how you can overwrite the configuration file easily.</a></p><br<br>

        <h2>Your Current Configuration:</h2>
        Here is the configuration currently being used. You most like need to customise this for your affiliate system.';
        echo "<ul>";

        $theme_config_path = get_stylesheet_directory() . "/config/hosts.php";

        if (file_exists($theme_config_path)) {
            $hosts_config = include get_stylesheet_directory() .
                "/config/hosts.php";
        } else {
            $hosts_config = include plugin_dir_path(__FILE__) .
                "config/hosts.php";
        }

        foreach ($hosts_config as $slug => $host) {
            echo "<li><strong>" . esc_html($host["name"]) . "</strong> ";
            echo " - " .
                esc_url(get_site_url()) .
                "/" .
                get_option("guildenberg_helper_affiliate_refs", "ref") .
                "<strong>" .
                esc_html($slug) .
                "</strong></li>";
        }

        echo "</ul>";

        echo '<h2>Default Logo</h2>
        When someone visits your landing page and no affiliate link is found the default logo is shown. You can overwrite this by adding a logo.png file to your theme\'s config folder. <br><br>
        Current logo<br>
        <img style="display:block; width: 240px; height:auto;" src="' .
            get_stylesheet_directory_uri() .
            '/config/default.png"</img>
        ';

        echo '<h2>Here\'s how to use the shortcodes:</h2>
        <p>Use the following shortcodes anywhere on your landing pages. If there is no match between your Affiliate URL and a host nothing is shown.</p>
        <ul>
            <li><code>[gb-host-name]</code> - Displays the Host name</li>
            <li><code>[gb-host-description]</code> - Displays the Host description</li>
            <li><code>[gb-host-logo]</code> - Displays the GB Host logo</li>
        </ul>


        <h2>Advanced Usage  - Use <code>guildenberg_get_slug()</code> for display conditions in your Page Builder/code.</h2>
        In some pagebuilders you can conditionally show/hide sections.
        For example in Bricks Builder you use the display conditions and use this helper to get the slug of the host:
            <img src="' .
            plugin_dir_url(__FILE__) .
            'img/conditions.png" style="max-width: 300px; margin-top: 20px; display: block;">
        ';
    }

    public function gb_host_name_shortcode($atts)
    {
        return $this->host_data
            ? esc_html(
                "Limited Black Friday deal offered by " . $this->host_data->name
            )
            : "Limited Black Friday Deal";
    }

    public function gb_host_description_shortcode($atts)
    {
        return $this->host_data
            ? apply_filters("the_content", $this->host_data->description)
            : "";
    }

    public function gb_host_slug_shortcode($atts)
    {
        return $this->host_data ? $this->host_data->slug : "";
    }

    public function gb_host_logo_shortcode($atts)
    {
        if ($this->host_data && $this->host_data->thumbnail_id) {
            return '<img src="' . $this->host_data->thumbnail_id . '" />';
        } else {
            // Replace with the correct path to the default image
            return '<img src="' .
                get_stylesheet_directory_uri() .
                '/config/default.png" />';
        }
    }
    public function gb_host_all_shortcode($atts)
    {
        if (!$this->host_data) {
            return "";
        }

        // Construct the output based on the host data
        $output = "<div>" . esc_html($this->host_data->name) . "</div>"; // Example, adjust as needed

        return $output;
    }

    private function get_gb_host_by_ref($ref)
    {
        // First, try to load from the theme's folder
        $theme_config_path = get_stylesheet_directory() . "/config/hosts.php";

        if (file_exists($theme_config_path)) {
            $hosts_config = include $theme_config_path;
            if (isset($hosts_config[$ref])) {
                return (object) $hosts_config[$ref];
            }
        }

        // If not found in the theme, fall back to your plugin's directory
        $plugin_config_path = plugin_dir_path(__FILE__) . "config/hosts.php";

        if (file_exists($plugin_config_path)) {
            $hosts_config = include $plugin_config_path;
            if (isset($hosts_config[$ref])) {
                return (object) $hosts_config[$ref];
            }
        }

        // If still not found, return null
        return null;
    }

    private function get_normalized_gb_host_by_ref()
    {
        $ref = $this->get_ref();
        if (!$ref) {
            return null;
        }

        $host = $this->get_gb_host_by_ref($ref);



        if ($host === null) {
            return null;
        }

        $host_data = new stdClass();

        if (is_object($host)) {
            // Adjusted to use 'logo' instead of 'thumbnail_id'
            // and use the array key as the slug
            $host_data->name = $host->name;
            $host_data->description = $host->custom_intro;
            $host_data->slug = $ref; // Array key serves as the slug

            // Check for the logo URL in the plugin directory
            $plugin_logo_url = plugin_dir_url(__FILE__) . $host->logo;
            if (file_exists(plugin_dir_path(__FILE__) . $host->logo)) {
                $host_data->thumbnail_id = $plugin_logo_url;
            } else {
                // Logo not found in the plugin directory, check in the active theme's directory
                $theme_logo_url = get_stylesheet_directory_uri() . $host->logo;
                if (
                    file_exists(get_stylesheet_directory() . "/" . $host->logo)
                ) {
                    $host_data->thumbnail_id = $theme_logo_url;
                } else {
                    // If logo is not found in either location, use a default value
                    $host_data->thumbnail_id = ""; // You can set a default URL here if needed
                }
            }
        } else {
            // Assuming $host is a WP_Post object
            $host_data->name = $host->post_title;
            $host_data->description = apply_filters(
                "the_content",
                $host->post_content
            );
            $host_data->slug = $host->post_name;
            $host_data->thumbnail_id = get_post_thumbnail_id($host->ID);
        }

        return $host_data;
    }

    private function get_ref()
    {
        $refKey = trim(
            str_replace(
                ["?", "="],
                "",
                get_option("guildenberg_helper_affiliate_refs", "ref")
            )
        );

        if (isset($_COOKIE[$refKey])) {
            return $_COOKIE[$refKey];
        }
        if (isset($_GET[$refKey])) {
            return $_GET[$refKey];
        }

        return null;
    }
}

// Initialize the plugin
add_action("plugins_loaded", function () {
    // Initialize the plugin
    new Guildenberg_Helper_Plugin();
});


function guildenberg_get_slug()
{
    $refKey = trim(
        str_replace(
            ["?", "="],
            "",
            get_option("guildenberg_helper_affiliate_refs", "ref")
        )
    );

    if (isset($_COOKIE[$refKey])) {
        return $_COOKIE[$refKey];
    }
    if (isset($_GET[$refKey])) {
        return $_GET[$refKey];
    }

    return null;
}

//Plugin Update Checker
require_once plugin_dir_path(__FILE__) .
    "plugin-update-checker/plugin-update-checker.php";
    use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
    // Initialize the update checker
    $myUpdateChecker = PucFactory::buildUpdateChecker(
        "https://github.com/BoweFrankema/guildenberg-helper",
        __FILE__, // This should refer to your main plugin file
        "guildenberg-helper"
    );

    // Set the branch that contains the stable release
    $myUpdateChecker->setBranch("main");
