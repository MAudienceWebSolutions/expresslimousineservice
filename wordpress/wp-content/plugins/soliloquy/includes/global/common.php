<?php
/**
 * Common class.
 *
 * @since 1.0.0
 *
 * @package Soliloquy
 * @author  Thomas Griffin
 */
class Soliloquy_Common {

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

    }

    /**
     * Helper method for retrieving slider sizes.
     *
     * @since 1.0.0
     *
     * @global array $_wp_additional_image_sizes Array of registered image sizes.
     * @return array                             Array of slider size data.
     */
    public function get_slider_sizes() {

        $sizes = array(
            array(
                'value'  => 'default',
                'name'   => __( 'Default', 'soliloquy' ),
                'width'  => 0,
                'height' => 0
            )
        );

        $sizes[] = array(
            'value'  => 'full_width',
            'name'   => __( 'Full Width', 'soliloquy' ),
            'width'  => 0,
            'height' => 0
        );

        global $_wp_additional_image_sizes;
        $wp_sizes = get_intermediate_image_sizes();
        foreach ( (array) $wp_sizes as $size ) {
            if ( isset( $_wp_additional_image_sizes[$size] ) ) {
                $width  = absint( $_wp_additional_image_sizes[$size]['width'] );
                $height = absint( $_wp_additional_image_sizes[$size]['height'] );
            } else {
                $width  = absint( get_option( $size . '_size_w' ) );
                $height = absint( get_option( $size . '_size_h' ) );
            }

            if ( ! $width && ! $height ) {
                $sizes[] = array(
                    'value'  => $size,
                    'name'   => ucwords( str_replace( array( '-', '_' ), ' ', $size ) ),
                    'width'  => 0,
                    'height' => 0
                );
            } else {
                $sizes[] = array(
                    'value'  => $size,
                    'name'   => ucwords( str_replace( array( '-', '_' ), ' ', $size ) ) . ' (' . $width . ' &#215; ' . $height . ')',
                    'width'  => $width,
                    'height' => $height
                );
            }
        }

        return apply_filters( 'soliloquy_slider_sizes', $sizes );

    }

    /**
     * Helper method for retrieving slider themes.
     *
     * @since 1.0.0
     *
     * @return array Array of slider theme data.
     */
    public function get_slider_themes() {

        $themes = array(
            array(
                'value' => 'base',
                'name'  => __( 'Base', 'soliloquy' ),
                'file'  => $this->base->file
            ),
            array(
                'value' => 'classic',
                'name'  => __( 'Classic', 'soliloquy' ),
                'file'  => $this->base->file
            )
        );

        return apply_filters( 'soliloquy_slider_themes', $themes );

    }

    /**
     * Helper method for retrieving slider transitions.
     *
     * @since 1.0.0
     *
     * @return array Array of slider transition data.
     */
    public function get_slider_transitions() {

        $transitions = array(
            array(
                'value' => 'fade',
                'name'  => __( 'Fade', 'soliloquy' )
            ),
            array(
                'value' => 'horizontal',
                'name'  => __( 'Scroll Horizontal', 'soliloquy' )
            ),
            array(
                'value' => 'vertical',
                'name'  => __( 'Scroll Vertical', 'soliloquy' )
            ),
            array(
                'value' => 'ticker',
                'name'  => __( 'Ticker (Continuous) Scroll Horizontal', 'soliloquy' )
            )
        );

        return apply_filters( 'soliloquy_slider_transitions', $transitions );

    }

    /**
     * Helper method for retrieving slider positions.
     *
     * @since 1.0.0
     *
     * @return array Array of slider position data.
     */
    public function get_slider_positions() {

        $positions = array(
            array(
                'value' => 'center',
                'name'  => __( 'Center', 'soliloquy' )
            ),
            array(
                'value' => 'left',
                'name'  => __( 'Left', 'soliloquy' )
            ),
            array(
                'value' => 'right',
                'name'  => __( 'Right', 'soliloquy' )
            ),
            array(
                'value' => 'none',
                'name'  => __( 'None', 'soliloquy' )
            )
        );

        return apply_filters( 'soliloquy_slider_positions', $positions );

    }

    /**
     * Helper method for retrieving caption positions.
     *
     * @since 2.4.1.1
     *
     * @return array Array of slider position data.
     */
    public function get_caption_positions() {

        $positions = array(
            array(
                'value' => 'top',
                'name'  => __( 'Top', 'soliloquy' )
            ),
            array(
                'value' => 'bottom',
                'name'  => __( 'Bottom', 'soliloquy' )
            ),
            array(
                'value' => 'left',
                'name'  => __( 'Left', 'soliloquy' )
            ),
            array(
                'value' => 'right',
                'name'  => __( 'Right', 'soliloquy' )
            ),
        );

        return apply_filters( 'soliloquy_caption_positions', $positions );

    }

    /**
     * Helper method for retrieving aria-live priorities
     *
     * @since 2.4.0.9
     *
     * @return array Array of aria-live priorities
     */
    public function get_aria_live_values() {

        $values = array(
            array(
                'value' => 'off',
                'name'  => __( 'Off', 'soliloquy' )
            ),
            array(
                'value' => 'polite',
                'name'  => __( 'Polite', 'soliloquy' )
            ),
            array(
                'value' => 'assertive',
                'name'  => __( 'Assertive', 'soliloquy' )
            ),
        );

        return apply_filters( 'soliloquy_aria_live_values', $values );

    }

    /**
     * Helper method for setting default config values.
     *
     * @since 1.0.0
     *
     * @global int $id      The current post ID.
     * @global object $post The current post object.
     * @param string $key   The default config key to retrieve.
     * @return string       Key value on success, false on failure.
     */
    public function get_config_default( $key ) {

        global $id, $post;

        // Get the current post ID. If ajax, grab it from the $_POST variable.
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            $post_id = absint( $_POST['post_id'] );
        } else {
            $post_id = isset( $post->ID ) ? $post->ID : (int) $id;
        }

        // Prepare default values.
        $defaults = $this->get_config_defaults( $post_id );

        // Return the key specified.
        return isset( $defaults[$key] ) ? $defaults[$key] : false;

    }

    /**
     * Retrieves the slider config defaults.
     *
     * @since 1.0.0
     *
     * @param int $post_id The current post ID.
     * @return array       Array of slider config defaults.
     */
    public function get_config_defaults( $post_id ) {

        $defaults = array(
            'type'              => 'default',
            'slider_theme'      => 'base',
            'slider_size'       => 'default',
            'slider_width'      => 960,
            'slider_height'     => 300,
            'transition'        => 'fade',
            'duration'          => 5000,
            'speed'             => 400,
            'position'          => 'center',
            'gutter'            => 20,
            'slider'            => 1,
            'caption_position'  => 'bottom',
            'caption_delay'     => 0,
            'mobile'            => 1,
            'mobile_width'      => 600,
            'mobile_height'     => 200,
            'auto'              => 1,
            'smooth'            => 1,
            'dimensions'        => 0,
            'arrows'            => 1,
            'control'           => 1,
            'pauseplay'         => 0,
            'mobile_caption'    => 0,
            'hover'             => 0,
            'keyboard'          => 1,
            'css'               => 1,
            'loop'              => 1,
            'random'            => 0,
            'delay'             => 0,
            'start'             => 0,
            'aria_live'         => 'polite',

            // Misc
            'classes'       => array(),
            'title'         => '',
            'slug'          => '',
            'rtl'           => 0,
        );
        return apply_filters( 'soliloquy_defaults', $defaults, $post_id );

    }

    /**
     * Helper method for setting default meta values.
     *
     * @since 1.0.0
     *
     * @global int $id       The current post ID.
     * @global object $post  The current post object.
     * @param string $key    The default config key to retrieve.
     * @param int $attach_id The attachment ID to target.
     * @return string        Key value on success, false on failure.
     */
    public function get_meta_default( $key, $attach_id ) {

        global $id, $post;

        // Get the current post ID. If ajax, grab it from the $_POST variable.
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            $post_id = absint( $_POST['post_id'] );
        } else {
            $post_id = isset( $post->ID ) ? $post->ID : (int) $id;
        }

        // Prepare default values.
        $defaults = $this->get_meta_defaults( $post_id, $attach_id );

        // Return the key specified.
        return isset( $defaults[$key] ) ? $defaults[$key] : false;

    }

    /**
     * Retrieves the slider meta defaults.
     *
     * @since 1.0.0
     *
     * @param int $post_id   The current post ID.
     * @param int $attach_id The current attachment ID.
     * @return array         Array of slider meta defaults.
     */
    public function get_meta_defaults( $post_id, $attach_id ) {

        $defaults = array(
            'title'   => '',
            'alt'     => '',
            'link'    => '',
            'caption' => '',
            'thumb'   => '',
            'url'     => '',
            'src'     => '',
            'code'    => ''

        );
        return apply_filters( 'soliloquy_meta_defaults', $defaults, $post_id, $attach_id );

    }

    /**
     * Returns an array of self hosted video supported file types
     * Edit this to extend support, but bear in mind mediaelementplayer's limitations
     *
     * @since 2.4.1.4
     *
     * @return array Supported File Types
     */
    public function get_self_hosted_supported_filetypes() {

        $file_types = array(
            'mp4',
            'flv',
            'ogv',
            'webm',
        );

        $file_types = apply_filters( 'soliloquy_get_self_hosted_supported_filetypes', $file_types );

        return $file_types;

    }

    /**
     * Converts the given array to a string
     *
     * @since 2.4.1.4
     *
     * @param string $glue Glue to join array values together
     * @return string Supported File Types
     */
    public function get_self_hosted_supported_filetypes_string( $glue = '|' ) {

        $file_types = $this->get_self_hosted_supported_filetypes();
        $file_types_str = '';
        foreach ( $file_types as $file_type ) {
            $file_types_str .= '.' . $file_type . $glue;
        }

        // Trim final glue
        if ( ! empty( $glue ) ) {
            $file_types_str = rtrim( $file_types_str, $glue );
        }

        return $file_types_str;

    }

    /**
     * API method for cropping images.
     *
     * @since 1.0.0
     *
     * @global object $wpdb The $wpdb database object.
     *
     * @param string $url      The URL of the image to resize.
     * @param int $width       The width for cropping the image.
     * @param int $height      The height for cropping the image.
     * @param bool $crop       Whether or not to crop the image (default yes).
     * @param string $align    The crop position alignment.
     * @param bool $retina     Whether or not to make a retina copy of image.
     * @param array $data      Array of slider data (optional).
     * @return WP_Error|string Return WP_Error on error, URL of resized image on success.
     */
    public function resize_image( $url, $width = null, $height = null, $crop = true, $align = 'c', $quality = 100, $retina = false, $data = array() ) {

        global $wpdb;

        // Get common vars.
        $args   = array( $url, $width, $height, $crop, $align, $quality, $retina, $data );
        $common = $this->get_image_info( $args );

        // Unpack variables if an array, otherwise return WP_Error.
        if ( is_wp_error( $common ) ) {
            return $common;
        } else {
            extract( $common );
        }

        // If the destination width/height values are the same as the original, don't do anything.
        if ( $orig_width === $dest_width && $orig_height === $dest_height ) {
            return $url;
        }

        // If the file doesn't exist yet, we need to create it.
        if ( ! file_exists( $dest_file_name ) ) {
            // We only want to resize Media Library images, so we can be sure they get deleted correctly when appropriate.
            $get_attachment = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE guid='%s'", $url ) );

            // Load the WordPress image editor.
            $editor = wp_get_image_editor( $file_path );

            // If an editor cannot be found, the user needs to have GD or Imagick installed.
            if ( is_wp_error( $editor ) ) {
                return new WP_Error( 'soliloquy-error-no-editor', __( 'No image editor could be selected. Please verify with your webhost that you have either the GD or Imagick image library compiled with your PHP install on your server.', 'soliloquy' ) );
            }

            // Set the image editor quality.
            $editor->set_quality( $quality );

            // If cropping, process cropping.
            if ( $crop ) {
                $src_x = $src_y = 0;
                $src_w = $orig_width;
                $src_h = $orig_height;

                $cmp_x = $orig_width / $dest_width;
                $cmp_y = $orig_height / $dest_height;

                // Calculate x or y coordinate and width or height of source
                if ( $cmp_x > $cmp_y ) {
                    $src_w = round( $orig_width / $cmp_x * $cmp_y );
                    $src_x = round( ($orig_width - ($orig_width / $cmp_x * $cmp_y)) / 2 );
                } else if ( $cmp_y > $cmp_x ) {
                    $src_h = round( $orig_height / $cmp_y * $cmp_x );
                    $src_y = round( ($orig_height - ($orig_height / $cmp_y * $cmp_x)) / 2 );
                }

                // Positional cropping.
                if ( $align && $align != 'c' ) {
                    if ( strpos( $align, 't' ) !== false || strpos( $align, 'tr' ) !== false || strpos( $align, 'tl' ) !== false ) {
                        $src_y = 0;
                    }

                    if ( strpos( $align, 'b' ) !== false || strpos( $align, 'br' ) !== false || strpos( $align, 'bl' ) !== false ) {
                        $src_y = $orig_height - $src_h;
                    }

                    if ( strpos( $align, 'l' ) !== false ) {
                        $src_x = 0;
                    }

                    if ( strpos ( $align, 'r' ) !== false ) {
                        $src_x = $orig_width - $src_w;
                    }
                }

                // Crop the image.
                $editor->crop( $src_x, $src_y, $src_w, $src_h, $dest_width, $dest_height );
            } else {
                // Just resize the image.
                $editor->resize( $dest_width, $dest_height );
            }

            // Save the image.
            $saved = $editor->save( $dest_file_name );

            // Print possible out of memory errors.
            if ( is_wp_error( $saved ) ) {
                @unlink( $dest_file_name );
                return $saved;
            }

            // Add the resized dimensions and alignment to original image metadata, so the images
            // can be deleted when the original image is delete from the Media Library.
            if ( $get_attachment ) {
                $metadata = wp_get_attachment_metadata( $get_attachment[0]->ID );

                if ( isset( $metadata['image_meta'] ) ) {
                    $md = $saved['width'] . 'x' . $saved['height'];

                    if ( $crop ) {
                        $md .= $align ? "_${align}" : "_c";
                    }

                    $metadata['image_meta']['resized_images'][] = $md;
                    wp_update_attachment_metadata( $get_attachment[0]->ID, $metadata );
                }
            }

            // Set the resized image URL.
            $resized_url = str_replace( basename( $url ), basename( $saved['path'] ), $url );
        } else {
            // Set the resized image URL.
            $resized_url = str_replace( basename( $url ), basename( $dest_file_name ), $url );
        }

        // Return the resized image URL.
        return $resized_url;

    }

    /**
     * Helper method to return common information about an image.
     *
     * @since 1.0.0
     *
     * @param array $args      List of resizing args to expand for gathering info.
     * @return WP_Error|string Return WP_Error on error, array of data on success.
     */
    public function get_image_info( $args ) {

        // Unpack arguments.
        list( $url, $width, $height, $crop, $align, $quality, $retina, $data ) = $args;

        // Return an error if no URL is present.
        if ( empty( $url ) ) {
            return new WP_Error( 'soliloquy-error-no-url', __( 'No image URL specified for cropping.', 'soliloquy' ) );
        }

        // Get the image file path.
        $urlinfo       = parse_url( $url );
        $wp_upload_dir = wp_upload_dir();

        // Interpret the file path of the image.
        if ( preg_match( '/\/[0-9]{4}\/[0-9]{2}\/.+$/', $urlinfo['path'], $matches ) ) {
            $file_path = $wp_upload_dir['basedir'] . $matches[0];
        } else {
            $pathinfo    = parse_url( $url );
            $uploads_dir = is_multisite() ? '/files/' : '/wp-content/';
            $file_path   = ABSPATH . str_replace( dirname( $_SERVER['SCRIPT_NAME'] ) . '/', '', strstr( $pathinfo['path'], $uploads_dir ) );
            $file_path   = preg_replace( '/(\/\/)/', '/', $file_path );
        }

        // Attempt to stream and import the image if it does not exist based on URL provided.
        if ( ! file_exists( $file_path ) ) {
            return new WP_Error( 'soliloquy-error-no-file', __( 'No file could be found for the image URL specified.', 'soliloquy' ) );
        }

        // Get original image size.
        $size = @getimagesize( $file_path );

        // If no size data obtained, return an error.
        if ( ! $size ) {
            return new WP_Error( 'soliloquy-error-no-size', __( 'The dimensions of the original image could not be retrieved for cropping.', 'soliloquy' ) );
        }

        // Set original width and height.
        list( $orig_width, $orig_height, $orig_type ) = $size;

        // Generate width or height if not provided.
        if ( $width && ! $height ) {
            $height = floor( $orig_height * ($width / $orig_width) );
        } else if ( $height && ! $width ) {
            $width = floor( $orig_width * ($height / $orig_height) );
        } else if ( ! $width && ! $height ) {
            return new WP_Error( 'soliloquy-error-no-size', __( 'The dimensions of the original image could not be retrieved for cropping.', 'soliloquy' ) );
        }

        // Allow for different retina image sizes.
        $retina = $retina ? ( $retina === true ? 2 : $retina ) : 1;

        // Destination width and height variables
        $dest_width  = $width * $retina;
        $dest_height = $height * $retina;

        // Some additional info about the image.
        $info = pathinfo( $file_path );
        $dir  = $info['dirname'];
        $ext  = $info['extension'];
        $name = wp_basename( $file_path, ".$ext" );

        // Suffix applied to filename
        $suffix = "${dest_width}x${dest_height}";

        // Set alignment information on the file.
        if ( $crop ) {
            $suffix .= ( $align ) ? "_${align}" : "_c";
        }

        // Get the destination file name
        $dest_file_name = "${dir}/${name}-${suffix}.${ext}";

        // Return the info.
        $image_info = array(
            'dir'            => $dir,
            'name'           => $name,
            'ext'            => $ext,
            'suffix'         => $suffix,
            'orig_width'     => $orig_width,
            'orig_height'    => $orig_height,
            'orig_type'      => $orig_type,
            'dest_width'     => $dest_width,
            'dest_height'    => $dest_height,
            'file_path'      => $file_path,
            'dest_file_name' => $dest_file_name,
        );
        return apply_filters( 'soliloquy_get_image_info', $image_info, $data );

    }

    /**
     * Helper method to flush slider caches once a slider is updated.
     *
     * @since 1.0.0
     *
     * @param int $post_id The current post ID.
     * @param string $slug The unique slider slug.
     */
    public function flush_slider_caches( $post_id, $slug = '' ) {

        // Delete known slider caches.
        delete_transient( '_sol_cache_' . $post_id );
        delete_transient( '_sol_cache_all' );

        // Possibly delete slug slider cache if available.
        if ( ! empty( $slug ) ) {
            delete_transient( '_sol_cache_' . $slug );
        }

        // Run a hook for Addons to access.
        do_action( 'soliloquy_flush_caches', $post_id, $slug );

    }

    /**
     * Helper method to return the max execution time for scripts.
     *
     * @since 1.0.0
     *
     * @param int $time The max execution time available for PHP scripts.
     */
    public function get_max_execution_time() {

        $time = ini_get( 'max_execution_time' );
        return ! $time || empty( $time ) ? (int) 0 : $time;

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The Soliloquy_Common object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Soliloquy_Common ) ) {
            self::$instance = new Soliloquy_Common();
        }

        return self::$instance;

    }

}

// Load the common class.
$soliloquy_common = Soliloquy_Common::get_instance();