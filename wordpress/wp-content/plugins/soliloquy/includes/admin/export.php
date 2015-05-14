<?php
/**
 * Export class.
 *
 * @since 1.0.0
 *
 * @package Soliloquy
 * @author  Thomas Griffin
 */
class Soliloquy_Export {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Path to the file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Holds the base class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $base;

    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Load the base class object.
        $this->base = Soliloquy::get_instance();

        // Export a slider.
        $this->export_slider();

    }

    /**
     * Exports a Soliloquy slider.
     *
     * @since 1.0.0
     *
     * @return null Return early if failing proper checks to export the slider.
     */
    public function export_slider() {

        if ( ! $this->has_exported_slider() ) {
            return;
        }

        if ( ! $this->verify_exported_slider() ) {
            return;
        }

        if ( ! $this->can_export_slider() ) {
            return;
        }

        // Ignore the user aborting the action.
        ignore_user_abort( true );

        // Grab the proper data.
        $post_id = absint( $_POST['soliloquy_post_id'] );
        $data    = get_post_meta( $post_id, '_sol_slider_data', true );

        // Append the in_slider data checker to the data array.
        $data['in_slider'] = get_post_meta( $post_id, '_sol_in_slider', true );

        // Set the proper headers.
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=soliloquy-' . $post_id . '-' . date( 'm-d-Y' ) . '.json' );
        header( 'Expires: 0' );

        // Make the settings downloadable to a JSON file and die.
        die( json_encode( $data ) );

    }

    /**
     * Helper method to determine if a slider export is available.
     *
     * @since 1.0.0
     *
     * @return bool True if an exported slider is available, false otherwise.
     */
    public function has_exported_slider() {

        return ! empty( $_POST['soliloquy_export'] );

    }

    /**
     * Helper method to determine if a slider export nonce is valid and verified.
     *
     * @since 1.0.0
     *
     * @return bool True if the nonce is valid, false otherwise.
     */
    public function verify_exported_slider() {

        return isset( $_POST['soliloquy-export'] ) && wp_verify_nonce( $_POST['soliloquy-export'], 'soliloquy-export' );

    }

    /**
     * Helper method to determine if the user can actually export the slider.
     *
     * @since 1.0.0
     *
     * @return bool True if the user can export the slider, false otherwise.
     */
    public function can_export_slider() {

        return apply_filters( 'soliloquy_export_cap', current_user_can( 'manage_options' ) );

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The Soliloquy_Export object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Soliloquy_Export ) ) {
            self::$instance = new Soliloquy_Export();
        }

        return self::$instance;

    }

}

// Load the export class.
$soliloquy_export = Soliloquy_Export::get_instance();