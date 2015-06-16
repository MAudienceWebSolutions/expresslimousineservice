<?php
/**
 * Plugin Name: Soliloquy - Themes Addon
 * Plugin URI:  http://enviraslider.com
 * Description: Enables custom themes for Soliloquy sliders.
 * Author:      Thomas Griffin
 * Author URI:  http://thomasgriffinmedia.com
 * Version:     2.1.4
 * Text Domain: soliloquy-themes
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

// Define necessary addon constants.
define( 'SOLILOQUY_THEMES_PLUGIN_NAME', 'Soliloquy - Themes Addon' );
define( 'SOLILOQUY_THEMES_PLUGIN_VERSION', '2.1.4' );
define( 'SOLILOQUY_THEMES_PLUGIN_SLUG', 'soliloquy-themes' );

add_action( 'plugins_loaded', 'soliloquy_themes_plugins_loaded' );
/**
 * Ensures the full Soliloquy plugin is active before proceeding.
 *
 * @since 1.0.0
 *
 * @return null Return early if Soliloquy is not active.
 */
function soliloquy_themes_plugins_loaded() {

    // Bail if the main class does not exist.
    if ( ! class_exists( 'Soliloquy' ) ) {
        return;
    }

    // Fire up the addon.
    add_action( 'soliloquy_init', 'soliloquy_themes_plugin_init' );

	// Loads the plugin textdomain for translation
    load_plugin_textdomain( SOLILOQUY_THEMES_PLUGIN_SLUG, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    
}

/**
 * Loads all of the addon hooks and filters.
 *
 * @since 1.0.0
 */
function soliloquy_themes_plugin_init() {

    add_action( 'soliloquy_updater', 'soliloquy_themes_updater' );
    add_filter( 'soliloquy_slider_themes', 'soliloquy_themes_slider_themes' );

}

/**
 * Initializes the addon updater.
 *
 * @since 1.0.0
 *
 * @param string $key The user license key.
 */
function soliloquy_themes_updater( $key ) {

    $args = array(
        'plugin_name' => SOLILOQUY_THEMES_PLUGIN_NAME,
        'plugin_slug' => SOLILOQUY_THEMES_PLUGIN_SLUG,
        'plugin_path' => plugin_basename( __FILE__ ),
        'plugin_url'  => trailingslashit( WP_PLUGIN_URL ) . SOLILOQUY_THEMES_PLUGIN_SLUG,
        'remote_url'  => 'http://soliloquywp.com/',
        'version'     => SOLILOQUY_THEMES_PLUGIN_VERSION,
        'key'         => $key
    );
    $soliloquy_themes_updater = new Soliloquy_Updater( $args );

}

/**
 * Adds custom slider themes to the available list of slider themes.
 *
 * @since 1.0.0
 *
 * @param array $themes  Array of slider themes.
 * @return array $themes Amended array of slider themes.
 */
function soliloquy_themes_slider_themes( $themes ) {

    // Add custom themes here.
    $themes[] = array(
        'value' => 'karisma',
        'name'  => __( 'Karisma', 'soliloquy-themes' ),
        'file'  => __FILE__
    );

    $themes[] = array(
        'value' => 'metro',
        'name'  => __( 'Metro', 'soliloquy-themes' ),
        'file'  => __FILE__
    );

    return $themes;

}