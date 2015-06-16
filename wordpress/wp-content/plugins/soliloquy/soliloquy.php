<?php
/**
 * Plugin Name: Soliloquy
 * Plugin URI:  http://soliloquywp.com
 * Description: Soliloquy is best responsive WordPress slider plugin.
 * Author:      Thomas Griffin
 * Author URI:  http://thomasgriffinmedia.com
 * Version:     2.4.1.6
 * Text Domain: soliloquy
 * Domain Path: languages
 *
 * Soliloquy is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Soliloquy is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Soliloquy. If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class.
 *
 * @since 1.0.0
 *
 * @package Soliloquy
 * @author  Thomas Griffin
 */
class Soliloquy {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $version = '2.4.1.6';

    /**
     * The name of the plugin.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_name = 'Soliloquy';

    /**
     * Unique plugin slug identifier.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_slug = 'soliloquy';

    /**
     * Plugin file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Fire a hook before the class is setup.
        do_action( 'soliloquy_pre_init' );

        // Load the plugin textdomain.
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

        // Load the plugin widget.
        add_action( 'widgets_init', array( $this, 'widget' ) );

        // Load the plugin.
        add_action( 'init', array( $this, 'init' ), 0 );

    }

    /**
     * Loads the plugin textdomain for translation.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain( $this->plugin_slug, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    }

    /**
     * Registers the Soliloquy widget.
     *
     * @since 1.0.0
     */
    public function widget() {

        register_widget( 'Soliloquy_Widget' );

    }

    /**
     * Loads the plugin into WordPress.
     *
     * @since 1.0.0
     */
    public function init() {

        // Run hook once Soliloquy has been initialized.
        do_action( 'soliloquy_init' );

        // Load admin only components.
        if ( is_admin() ) {
            $this->require_admin();
            $this->require_updater();
        }

        // Load global components.
        $this->require_global();

    }

    /**
     * Loads all admin related files into scope.
     *
     * @since 1.0.0
     */
    public function require_admin() {

        require plugin_dir_path( __FILE__ ) . 'includes/admin/ajax.php';
        require plugin_dir_path( __FILE__ ) . 'includes/admin/common.php';
        require plugin_dir_path( __FILE__ ) . 'includes/admin/editor.php';
        require plugin_dir_path( __FILE__ ) . 'includes/admin/export.php';
        require plugin_dir_path( __FILE__ ) . 'includes/admin/import.php';
        require plugin_dir_path( __FILE__ ) . 'includes/admin/license.php';
        require plugin_dir_path( __FILE__ ) . 'includes/admin/media.php';
        require plugin_dir_path( __FILE__ ) . 'includes/admin/metaboxes.php';
        require plugin_dir_path( __FILE__ ) . 'includes/admin/posttype.php';
        require plugin_dir_path( __FILE__ ) . 'includes/admin/settings.php';
        require plugin_dir_path( __FILE__ ) . 'includes/admin/vimeo.php';
        
    }

    /**
     * Loads all updater related files and functions into scope.
     *
     * @since 1.0.0
     *
     * @return null Return early if the license key is not set or there are key errors.
     */
    public function require_updater() {

        // Retrieve the license key. If it is not set, return early.
        $key = $this->get_license_key();
        if ( ! $key ) {
            return;
        }

        // If there are any errors with the key itself, return early.
        if ( $this->get_license_key_errors() ) {
            return;
        }

        // Load the updater class.
        require plugin_dir_path( __FILE__ ) . 'includes/admin/updater.php';

        // Go ahead and initialize the updater.
        $args = array(
            'plugin_name' => $this->plugin_name,
            'plugin_slug' => $this->plugin_slug,
            'plugin_path' => plugin_basename( __FILE__ ),
            'plugin_url'  => trailingslashit( WP_PLUGIN_URL ) . $this->plugin_slug,
            'remote_url'  => 'http://soliloquywp.com/',
            'version'     => $this->version,
            'key'         => $key
        );
        $soliloquy_updater = new Soliloquy_Updater( $args );

        // Fire a hook for Addons to register their updater since we know the key is present.
        do_action( 'soliloquy_updater', $key );

    }

    /**
     * Loads all global files into scope.
     *
     * @since 1.0.0
     */
    public function require_global() {

        require plugin_dir_path( __FILE__ ) . 'includes/global/common.php';
        require plugin_dir_path( __FILE__ ) . 'includes/global/legacy.php';
        require plugin_dir_path( __FILE__ ) . 'includes/global/posttype.php';
        require plugin_dir_path( __FILE__ ) . 'includes/global/shortcode.php';
        require plugin_dir_path( __FILE__ ) . 'includes/global/widget.php';

    }

    /**
     * Returns a slider based on ID.
     *
     * Honors the slider post's status (publish, draft etc)
     *
     * @since 1.0.0
     *
     * @param int $id     The slider ID used to retrieve a slider.
     * @return array|bool Array of slider data or false if none found.
     */
    public function get_slider( $id ) {
	    
        // Attempt to return the transient first, otherwise generate the new query to retrieve the data.
        if ( false === ( $slider = get_transient( '_sol_cache_' . $id ) ) ) {
            $slider = $this->_get_slider( $id );
            if ( $slider ) {
                set_transient( '_sol_cache_' . $id, $slider, DAY_IN_SECONDS );
            }
        }
        
        // Check status of slider
        if ( isset( $slider['status'] ) ) {
	        if ( $slider['status'] == 'draft' || $slider['status'] == 'pending' ) {
	        	// Public site, slider is set to draft, so don't display it
				return;
	        }
        }

        // Return the slider data
        return $slider;

    }

    /**
     * Internal method that returns a slider based on ID.
     *
     * Ignores the slider post's status (publish, draft etc), so can be used for admin previews etc.
     *
     * @since 1.0.0
     *
     * @param int $id     The slider ID used to retrieve a slider.
     * @return array|bool Array of slider data or false if none found.
     */
    public function _get_slider( $id ) {

        return get_post_meta( $id, '_sol_slider_data', true );

    }

    /**
     * Returns a slider based on slug.
     *
     * @since 1.0.0
     *
     * @param string $slug The slider slug used to retrieve a slider.
     * @return array|bool  Array of slider data or false if none found.
     */
    public function get_slider_by_slug( $slug ) {
	    
        // Attempt to return the transient first, otherwise generate the new query to retrieve the data.
        if ( false === ( $slider = get_transient( '_sol_cache_' . $slug ) ) ) {
            $slider = $this->_get_slider_by_slug( $slug );
            if ( $slider ) {
                set_transient( '_sol_cache_' . $slug, $slider, DAY_IN_SECONDS );
            }
       	}
       	
       	// Check status of slider
        if ( isset( $slider['status'] ) ) {
	        if ( $slider['status'] == 'draft' || $slider['status'] == 'pending' ) {
	        	// Public site, slider is set to draft, so don't display it
				return;
	        }
        }

        // Return the slider data.
        return $slider;

    }

    /**
     * Internal method that returns a slider based on slug.
     *
     * @since 1.0.0
     *
     * @param string $slug The slider slug used to retrieve a slider.
     * @return array|bool  Array of slider data or false if none found.
     */
    public function _get_slider_by_slug( $slug ) {

        $sliders = $this->get_sliders();
        if ( ! $sliders ) {
            return false;
        }

        // Loop through the sliders to find a match by slug.
        $ret = false;
        foreach ( $sliders as $data ) {
	        
            if ( ( $data['config']['type'] != 'fc' && empty( $data['slider'] ) ) || empty( $data['config']['slug'] ) ) {
                continue;
            }

            if ( $data['config']['slug'] == $slug ) {
                $ret = $data;
                break;
            }
        }

        // Return the slider data.
        return $ret;

    }

    /**
     * Returns all sliders created on the site.
     *
     * @since 1.0.0
     *
     * @return array|bool Array of slider data or false if none found.
     */
    public function get_sliders() {

        // Attempt to return the transient first, otherwise generate the new query to retrieve the data.
        if ( false === ( $sliders = get_transient( '_sol_cache_all' ) ) ) {
            $sliders = $this->_get_sliders();
            if ( $sliders ) {
                set_transient( '_sol_cache_all', $sliders, DAY_IN_SECONDS );
            }
        }

        // Return the slider data.
        return $sliders;

    }

    /**
     * Internal method that returns all sliders created on the site.
     *
     * @since 1.0.0
     *
     * @return array|bool Array of slider data or false if none found.
     */
    public function _get_sliders() {

        $sliders = get_posts(
            array(
                'post_type'     => 'any',
                'no_found_rows' => true,
                'cache_results' => false,
                'nopaging'      => true,
                'fields'        => 'ids',
                'meta_query'    => array(
                    array(
                        'key' => '_sol_slider_data'
                    )
                )
            )
        );
        if ( empty( $sliders ) ) {
            return false;
        }

        // Now loop through all the sliders found and only use sliders that have images in them.
        $ret = array();
        foreach ( $sliders as $id ) {
            $data = get_post_meta( $id, '_sol_slider_data', true );
            if ( empty( $data['slider'] ) && 'default' == Soliloquy_Shortcode::get_instance()->get_config( 'type', $data ) || 'dynamic' == Soliloquy_Shortcode::get_instance()->get_config( 'type', $data ) ) {
                continue;
            }

            $ret[] = $data;
        }

        // Return the slider data.
        return $ret;

    }

    /**
     * Returns the license key for Soliloquy.
     *
     * @since 1.0.0
     *
     * @return string $key The user's license key for Soliloquy.
     */
    public function get_license_key() {

        $option = get_option( 'soliloquy' );
        $key    = false;
        if ( empty( $option['key'] ) ) {
            if ( defined( 'SOLILOQUY_LICENSE_KEY' ) ) {
                $key = SOLILOQUY_LICENSE_KEY;
            }
        } else {
            $key = $option['key'];
        }

        return apply_filters( 'soliloquy_license_key', $key );

    }

    /**
     * Returns the license key type for Soliloquy.
     *
     * @since 1.0.0
     *
     * @return string $type The user's license key type for Soliloquy.
     */
    public function get_license_key_type() {

        $option = get_option( 'soliloquy' );
        return $option['type'];

    }

    /**
     * Returns possible license key error flag.
     *
     * @since 1.0.0
     *
     * @return bool True if there are license key errors, false otherwise.
     */
    public function get_license_key_errors() {

        $option = get_option( 'soliloquy' );
        return isset( $option['is_expired'] ) && $option['is_expired'] || isset( $option['is_disabled'] ) && $option['is_disabled'] || isset( $option['is_invalid'] ) && $option['is_invalid'];

    }

    /**
     * Loads the default plugin options.
     *
     * @since 1.0.0
     *
     * @return array Array of default plugin options.
     */
    public static function default_options() {

        $ret = array(
            'key'         => '',
            'type'        => '',
            'is_expired'  => false,
            'is_disabled' => false,
            'is_invalid'  => false
        );

        return apply_filters( 'soliloquy_default_options', $ret );

    }

    /**
     * Getter method for retrieving the main plugin filepath.
     *
     * @since 1.2.0
     */
    public static function get_file() {

        return self::$file;

    }

    /**
     * Helper flag method for any Soliloquy screen.
     *
     * @since 1.2.0
     *
     * @return bool True if on a Soliloquy screen, false if not.
     */
    public static function is_soliloquy_screen() {

        $current_screen = get_current_screen();

        if ( ! $current_screen ) {
            return false;
        }

        if ( 'soliloquy' == $current_screen->post_type ) {
            return true;
        }

        return false;

    }

    /**
     * Helper flag method for the Add/Edit Soliloquy screens.
     *
     * @since 1.2.0
     *
     * @return bool True if on a Soliloquy Add/Edit screen, false if not.
     */
    public static function is_soliloquy_add_edit_screen() {

        $current_screen = get_current_screen();

        if ( ! $current_screen ) {
            return false;
        }

        if ( 'soliloquy' == $current_screen->post_type && 'post' == $current_screen->base ) {
            return true;
        }

        return false;

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The Soliloquy object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Soliloquy ) ) {
            self::$instance = new Soliloquy();
        }

        return self::$instance;

    }

}

register_activation_hook( __FILE__, 'soliloquy_activation_hook' );
/**
 * Fired when the plugin is activated.
 *
 * @since 1.0.0
 *
 * @global int $wp_version      The version of WordPress for this install.
 * @global object $wpdb         The WordPress database object.
 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false otherwise.
 */
function soliloquy_activation_hook( $network_wide ) {

    global $wp_version;
    if ( version_compare( $wp_version, '3.5.1', '<' ) && ! defined( 'SOLILOQUY_FORCE_ACTIVATION' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( sprintf( __( 'Sorry, but your version of WordPress does not meet Soliloquy\'s required version of <strong>3.5.1</strong> to run properly. The plugin has been deactivated. <a href="%s">Click here to return to the Dashboard</a>.', 'soliloquy' ), get_admin_url() ) );
    }

    $instance = Soliloquy::get_instance();

    if ( is_multisite() && $network_wide ) {
        global $wpdb;
        $site_list = $wpdb->get_results( "SELECT * FROM $wpdb->blogs ORDER BY blog_id" );
        foreach ( (array) $site_list as $site ) {
            switch_to_blog( $site->blog_id );

            // Set default license option.
            $option = get_option( 'soliloquy' );
            if ( ! $option || empty( $option ) ) {
                update_option( 'soliloquy', Soliloquy::default_options() );
            }

            restore_current_blog();
        }
    } else {
        // Set default license option.
        $option = get_option( 'soliloquy' );
        if ( ! $option || empty( $option ) ) {
            update_option( 'soliloquy', Soliloquy::default_options() );
        }
    }

}

register_uninstall_hook( __FILE__, 'soliloquy_uninstall_hook' );
/**
 * Fired when the plugin is uninstalled.
 *
 * @since 1.0.0
 *
 * @global object $wpdb The WordPress database object.
 */
function soliloquy_uninstall_hook() {

    $instance = Soliloquy::get_instance();

    if ( is_multisite() ) {
        global $wpdb;
        $site_list = $wpdb->get_results( "SELECT * FROM $wpdb->blogs ORDER BY blog_id" );
        foreach ( (array) $site_list as $site ) {
            switch_to_blog( $site->blog_id );
            delete_option( 'soliloquy' );
            restore_current_blog();
        }
    } else {
        delete_option( 'soliloquy' );
    }

}

// Load the main plugin class.
$soliloquy = Soliloquy::get_instance();

// Conditionally load the template tag.
if ( ! function_exists( 'soliloquy' ) ) {
    /**
     * Primary template tag for outputting Soliloquy sliders in templates.
     *
     * @since 1.0.0
     *
     * @param int $slider_id The ID of the slider to load.
     * @param string $type   The type of field to query.
     * @param array $args    Associative array of args to be passed.
     * @param bool $return   Flag to echo or return the slider HTML.
     */
    function soliloquy( $id, $type = 'id', $args = array(), $return = false ) {

        // If we have args, build them into a shortcode format.
        $args_string = '';
        if ( ! empty( $args ) ) {
            foreach ( (array) $args as $key => $value ) {
                $args_string .= ' ' . $key . '="' . $value . '"';
            }
        }

        // Build the shortcode.
        $shortcode = ! empty( $args_string ) ? '[soliloquy ' . $type . '="' . $id . '"' . $args_string . ']' : '[soliloquy ' . $type . '="' . $id . '"]';

        // Return or echo the shortcode output.
        if ( $return ) {
            return do_shortcode( $shortcode );
        } else {
            echo do_shortcode( $shortcode );
        }

    }
}