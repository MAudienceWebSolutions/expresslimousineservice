<?php
// For backwards compat, load the v1 template tag if it doesn't exist.
if ( ! function_exists( 'soliloquy_slider' ) ) {
    /**
     * Primary template tag for outputting Soliloquy sliders in templates (v1).
     *
     * @since 2.1.0
     *
     * @param int $slider_id The ID of the slider to load.
     * @param bool $return   Flag to echo or return the slider HTML.
     */
    function soliloquy_slider( $id, $return = false ) {

        // First test to see if the slider can be found by ID. If so, run that.
        $by_id = Soliloquy::get_instance()->get_slider( $id );
        if ( $by_id ) {
            return soliloquy( $id, 'id', array(), $return );
        }

        // If not by ID, it must be a slug, so return the slug.
        return soliloquy( $id, 'slug', array(), $return );

    }
}

// For backwards compat, load the v1 base class if it does not exist.
if ( ! class_exists( 'Tgmsp' ) ) {
    /**
     * Legacy init class for Soliloquy. The sole purpose of this class existing
     * is to allow legacy addons and functions to remain useful and not cause
     * undue stress or errors. It is also good for legacy addons to allow updating.
     *
     * @since 2.1.1
     *
     * @package Soliloquy
     * @author  Thomas Griffin
     */
    class Tgmsp {

        /**
         * Legacy properties that need to be defined.
         *
         * @since 2.1.1
         *
         * @var mixed
         */
        private static $key;
        private static $instance;
        public $version      = '2.1.1';
        private static $file = __FILE__;

        /**
         * Legacy constructor.
         *
         * @since 2.1.1
         */
        public function __construct() {

            // Set the object instance.
            self::$instance = $this;

            // Make the license key piece backwards compat.
            global $soliloquy_license;
            $soliloquy_license = get_option( 'soliloquy_license_key' );

        }

        /**
         * Legacy activation hook.
         *
         * @since 2.1.1
         */
        public function activation() {}

        /**
         * Legacy widget register.
         *
         * @since 2.1.1
         */
        public function widget() {}

        /**
         * Legacy plugin initializer.
         *
         * @since 2.1.1
         */
        public function init() {}

        /**
         * Legacy autoloader.
         *
         * @sincw 2.1.1
         */
        public static function autoload( $classname ) {}

        /**
         * Legacy instance retrieval method.
         *
         * @since 2.1.1
         */
        public static function get_instance() {

            return self::$instance;

        }

        /**
         * Legacy license key method. Returns the v2 license key method.
         *
         * @since 2.1.1
         */
        public static function get_key() {

            return Soliloquy::get_instance()->get_license_key();

        }

        /**
         * Legacy filepath getter. Returns the v2 filepath instance instead.
         *
         * @since 2.1.1
         */
        public static function get_file() {

            return Soliloquy::get_instance()->file;

        }

        /**
         * Legacy slider retrieval method. Returns the v2 method for grabbing sliders.
         *
         * @since 1.3.0
         */
        public static function get_sliders() {

            return Soliloquy::get_instance()->get_sliders();

        }

        /**
         * Legacy sreen helper. Returns the v2 screen helper.
         *
         * @since 2.1.1
         */
        public static function is_soliloquy_screen() {

            return Soliloquy::is_soliloquy_screen();

        }

        /**
         * Legacy sreen helper. Returns the v2 screen helper.
         *
         * @since 2.1.1
         */
        public static function is_soliloquy_add_edit_screen() {

            return Soliloquy::is_soliloquy_add_edit_screen();

        }

    }

    // Initialize the legacy class so addons can be updated and used.
    $tgmsp = new Tgmsp();
}

// For backwards compat, load the v1 strings class.
if ( ! class_exists( 'Tgmsp_Strings' ) ) {
    /**
     * Legacy strings class for Soliloquy.
     *
     * @since 2.1.1
     *
     * @package Soliloquy
     * @author  Thomas Griffin
     */
    class Tgmsp_Strings {

        /**
         * Legacy properties that need to be defined.
         *
         * @since 2.1.
         *
         * @var mixed
         */
        private static $instance;
        public $strings = array();

        /**
         * Legacy constructor.
         *
         * @since 2.1.1
         */
        public function __construct() {

            self::$instance = $this;

        }

        /**
         * Legacy instance retrieval method.
         *
         * @since 2.1.1
         */
        public static function get_instance() {

            return self::$instance;

        }

    }

    // Initialize the legacy class to prevent fatal errors.
    $tgmsp_strings = new Tgmsp_Strings();
}

// For backwards compat, load the v1 plugin updater.
if ( ! class_exists( 'Tgmsp_Updater' ) ) {
    /**
     * Legacy updater class for v1 addons.
     *
     * @since 1.0.0
     *
     * @package Soliloquy
     * @author  Thomas Griffin
     */
    class Tgmsp_Updater {

        /**
         * Plugin name.
         *
         * @since 1.0.0
         *
         * @var bool|string
         */
        public $plugin_name = false;

        /**
         * Plugin slug.
         *
         * @since 1.0.0
         *
         * @var bool|string
         */
        public $plugin_slug = false;

        /**
         * Plugin path.
         *
         * @since 1.0.0
         *
         * @var bool|string
         */
        public $plugin_path = false;

        /**
         * URL of the plugin.
         *
         * @since 1.0.0
         *
         * @var bool|string
         */
        public $plugin_url = false;

        /**
         * Remote URL for getting plugin updates.
         *
         * @since 1.0.0
         *
         * @var bool|string
         */
        public $remote_url = false;

        /**
         * Version number of the plugin.
         *
         * @since 1.0.0
         *
         * @var bool|int
         */
        public $version = false;

        /**
         * License key for the plugin.
         *
         * @since 1.0.0
         *
         * @var bool|string
         */
        public $key = false;

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
         *
         * @param array $config Array of updater config args.
         */
        public function __construct( array $config ) {

            if ( class_exists( 'Soliloquy_Updater' ) ) {
                return new Soliloquy_Updater( $config );
            }

        }

    }
}