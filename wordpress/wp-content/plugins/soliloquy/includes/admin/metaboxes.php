<?php
/**
 * Metabox class.
 *
 * @since 1.0.0
 *
 * @package Soliloquy
 * @author  Thomas Griffin
 */
class Soliloquy_Metaboxes {

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

        // Load metabox assets.
        add_action( 'admin_enqueue_scripts', array( $this, 'meta_box_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'meta_box_scripts' ) );

        // Load the metabox hooks and filters.
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 100 );

        // Load all tabs.
        add_action( 'soliloquy_tab_images', array( $this, 'images_tab' ) );
        add_action( 'soliloquy_tab_config', array( $this, 'config_tab' ) );
        add_action( 'soliloquy_tab_mobile', array( $this, 'mobile_tab' ) );
        add_action( 'soliloquy_tab_misc', array( $this, 'misc_tab' ) );

        // Add action to save metabox config options.
        add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );

    }

    /**
     * Loads styles for our metaboxes.
     *
     * @since 1.0.0
     *
     * @return null Return early if not on the proper screen.
     */
    public function meta_box_styles() {

        if ( 'post' !== get_current_screen()->base ) {
            return;
        }

        // We always need to load metabox.css so we fix 4.0 styling on media grid
        // Load necessary metabox styles.
        wp_register_style( $this->base->plugin_slug . '-metabox-style', plugins_url( 'assets/css/metabox.css', $this->base->file ), array(), $this->base->version );
        wp_enqueue_style( $this->base->plugin_slug . '-metabox-style' );

        if ( isset( get_current_screen()->post_type ) && in_array( get_current_screen()->post_type, $this->get_skipped_posttypes() ) ) {
            return;
        }
        
        // If WordPress version < 4.0, add attachment-details-modal-support.css
        // This contains the 4.0 CSS to make the attachment window display correctly
        $version = (float) get_bloginfo( 'version' );
        if ( $version < 4 ) {
            wp_register_style( $this->base->plugin_slug . '-attachment-details-modal-support', plugins_url( 'assets/css/attachment-details-modal-support.css', $this->base->file ), array(), $this->base->version );
            wp_enqueue_style( $this->base->plugin_slug . '-attachment-details-modal-support' );
        }

        // Fire a hook to load in custom metabox styles.
        do_action( 'soliloquy_metabox_styles' );

    }

    /**
     * Loads scripts for our metaboxes.
     *
     * @since 1.0.0
     *
     * @global int $id      The current post ID.
     * @global object $post The current post object..
     * @return null         Return early if not on the proper screen.
     */
    public function meta_box_scripts( $hook ) {

        global $id, $post;

        if ( isset( get_current_screen()->base ) && 'post' !== get_current_screen()->base ) {
            return;
        }

        if ( isset( get_current_screen()->post_type ) && in_array( get_current_screen()->post_type, $this->get_skipped_posttypes() ) ) {
            return;
        }

        // Set the post_id for localization.
        $post_id = isset( $post->ID ) ? $post->ID : (int) $id;

        // Load necessary metabox scripts.
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_media( array( 'post' => $post_id ) );

        // Load necessary metabox scripts.
        wp_enqueue_script( 'plupload-handlers' );
        wp_register_script( $this->base->plugin_slug . '-codemirror', plugins_url( 'assets/js/codemirror.js', $this->base->file ), array(), $this->base->version, true );
        wp_register_style( $this->base->plugin_slug . '-codemirror', plugins_url( 'assets/css/codemirror.css', $this->base->file ), array(), $this->base->version );
        wp_enqueue_script( $this->base->plugin_slug . '-codemirror' );
        wp_enqueue_style( $this->base->plugin_slug . '-codemirror' );

        wp_register_script( $this->base->plugin_slug . '-metabox-script', plugins_url( 'assets/js/min/metabox-min.js', $this->base->file ), array( 'jquery', 'plupload-handlers', 'quicktags', 'jquery-ui-sortable', $this->base->plugin_slug . '-codemirror' ), $this->base->version, true );
        wp_enqueue_script( $this->base->plugin_slug . '-metabox-script' );
        wp_localize_script(
            $this->base->plugin_slug . '-metabox-script',
            'soliloquy_metabox',
            array(
                'ajax'           => admin_url( 'admin-ajax.php' ),
                'change_nonce'   => wp_create_nonce( 'soliloquy-change-type' ),
                'slider'         => esc_attr__( 'Click Here to Insert Slides from Other Sources', 'soliloquy' ),
                'id'             => $post_id,
                'width'          => Soliloquy_Common::get_instance()->get_config_default( 'slider_width' ),
                'height'         => Soliloquy_Common::get_instance()->get_config_default( 'slider_height' ),
                'htmlcode'       => __( 'HTML Slide Code', 'soliloquy' ),
                'htmlslide'      => __( 'HTML Slide Title', 'soliloquy' ),
                'htmlplace'      => __( 'Enter HTML slide title here...', 'soliloquy' ),
                'htmlstart'      => __( '<!-- Enter your HTML code here for this slide (you can delete this line). -->', 'soliloquy' ),
                'htmluse'        => __( 'Select Thumbnail', 'soliloquy' ),
                'import'         => __( 'You must select a file to import before continuing.', 'soliloquy' ),
                'insert_nonce'   => wp_create_nonce( 'soliloquy-insert-images' ),
                'inserting'      => __( 'Inserting...', 'soliloquy' ),
                'library_search' => wp_create_nonce( 'soliloquy-library-search' ),
                'load_image'     => wp_create_nonce( 'soliloquy-load-image' ),
                'load_slider'    => wp_create_nonce( 'soliloquy-load-slider' ),
                'path'           => plugin_dir_path( 'assets' ),
                'plupload'       => $this->get_plupload_init( $post_id ),
                'refresh_nonce'  => wp_create_nonce( 'soliloquy-refresh' ),
                'remove'         => __( 'Are you sure you want to remove this slide from the slider?', 'soliloquy' ),
                'remove_nonce'   => wp_create_nonce( 'soliloquy-remove-slide' ),
                'removeslide'    => __( 'Remove', 'soliloquy' ),
                'save_nonce'     => wp_create_nonce( 'soliloquy-save-meta' ),
                'saving'         => __( 'Saving...', 'soliloquy' ),
                'sort'           => wp_create_nonce( 'soliloquy-sort' ),
                'videocaption'   => __( 'Video Slide Caption', 'soliloquy' ),
                'videoslide'     => __( 'Video Slide Title', 'soliloquy' ),
                'videoplace'     => __( 'Enter video slide title here...', 'soliloquy' ),
                'videotitle'     => __( 'Video Slide URL', 'soliloquy' ),
                'videothumb'     => __( 'Video Slide Placeholder Image', 'soliloquy' ),
                'videosrc'       => __( 'Enter your video placeholder image URL here (or leave blank to pull from video itself)...', 'soliloquy' ),
                'videoselect'    => __( 'Choose Video Placeholder Image', 'soliloquy' ),
                'videodelete'    => __( 'Remove Video Placeholder Image', 'soliloquy' ),
                'videooutput'    => __( 'Enter your video URL here...', 'soliloquy' ),
                'videoframe'     => __( 'Choose a Video Placeholder Image', 'soliloquy' ),
                'videouse'       => __( 'Select Placeholder Image', 'soliloquy' )
            )
        );

        // Form Conditionals
        wp_register_script( 'jquery-form-conditionals', plugins_url( 'assets/js/min/jquery.form-conditionals-min.js', $this->base->file ), array( 'jquery', 'plupload-handlers', 'quicktags', 'jquery-ui-sortable', $this->base->plugin_slug . '-codemirror' ), $this->base->version, true );
        wp_enqueue_script( 'jquery-form-conditionals' );

        // If on an Soliloquy post type, add custom CSS for hiding specific things.
        if ( isset( get_current_screen()->post_type ) && 'soliloquy' == get_current_screen()->post_type ) {
            add_action( 'admin_head', array( $this, 'meta_box_css' ) );
        }

        // Fire a hook to load custom metabox scripts.
        do_action( 'soliloquy_metabox_scripts' );

    }

    /**
     * Returns custom plupload init properties for the media uploader.
     *
     * @since 1.0.0
     *
     * @param int $post_id The current post ID.
     * @return array       Array of plupload init data.
     */
    public function get_plupload_init( $post_id ) {

        // Prepare $_POST form variables and apply backwards compat filter.
        $post_params = array(
            'post_id'  => $post_id,
            '_wpnonce' => wp_create_nonce( 'media-form' ),
            'type'     => '',
            'tab'      => '',
            'short'    => 3
        );
        $post_params = apply_filters( 'upload_post_params', $post_params );

        // Prepare upload size parameters.
        $max_upload_size = wp_max_upload_size();

        // Prepare the plupload init array.
        $plupload_init = array(
            'runtimes'            => 'html5,silverlight,flash,html4',
            'browse_button'       => 'soliloquy-plupload-browse-button',
            'container'           => 'soliloquy-plupload-upload-ui',
            'drop_element'        => 'soliloquy-drag-drop-area',
            'file_data_name'      => 'async-upload',
            'multiple_queues'     => true,
            'max_file_size'       => $max_upload_size . 'b',
            'url'                 => admin_url( 'async-upload.php' ),
            'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
            'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
            'filters'             => array(
                array(
                    'title'       => __( 'Allowed Files', 'soliloquy' ),
                    'extensions'  => '*'
                ),
            ),
            'multipart'           => true,
            'urlstream_upload'    => true,
            'multipart_params'    => $post_params,
            'resize'              => false
        );

        // If we are on a mobile device, disable multi selection.
        if ( wp_is_mobile() ) {
            $plupload_init['multi_selection'] = false;
        }

        // Apply backwards compat filter.
        $plupload_init = apply_filters( 'plupload_init', $plupload_init );

        // Return and apply a custom filter to our init data.
        return apply_filters( 'soliloquy_plupload_init', $plupload_init, $post_id );

    }

    /**
     * Hides unnecessary meta box items on Soliloquy post type screens.
     *
     * @since 1.0.0
     */
    public function meta_box_css() {

        ?>
        <style type="text/css">.misc-pub-section:not(.misc-pub-post-status) { display: none; }</style>
        <?php

        // Fire action for CSS on Soliloquy post type screens.
        do_action( 'soliloquy_admin_css' );

    }

    /**
     * Creates metaboxes for handling and managing sliders.
     *
     * @since 1.0.0
     */
    public function add_meta_boxes() {

        // Let's remove all of those dumb metaboxes from our post type screen to control the experience.
        $this->remove_all_the_metaboxes();

        // Get all public post types.
        $post_types = get_post_types( array( 'public' => true ) );

        // Splice the soliloquy post type since it is not visible to the public by default.
        $post_types[] = 'soliloquy';

        // Loops through the post types and add the metaboxes.
        foreach ( (array) $post_types as $post_type ) {
            // Don't output boxes on these post types.
            if ( in_array( $post_type, $this->get_skipped_posttypes() ) ) {
                continue;
            }

            add_meta_box( 'soliloquy', __( 'Soliloquy Settings', 'soliloquy' ), array( $this, 'meta_box_callback' ), $post_type, 'normal', apply_filters( 'soliloquy_metabox_priority', 'high' ) );
        }

    }

    /**
     * Removes all the metaboxes except the ones I want on MY POST TYPE. RAGE.
     *
     * @since 1.0.0
     *
     * @global array $wp_meta_boxes Array of registered metaboxes.
     * @return smile $for_my_buyers Happy customers with no spammy metaboxes!
     */
    public function remove_all_the_metaboxes() {

        global $wp_meta_boxes;

        // This is the post type you want to target. Adjust it to match yours.
        $post_type  = 'soliloquy';

        // These are the metabox IDs you want to pass over. They don't have to match exactly. preg_match will be run on them.
        $pass_over  = array( 'submitdiv', 'soliloquy' );

        // All the metabox contexts you want to check.
        $contexts   = array( 'normal', 'advanced', 'side' );

        // All the priorities you want to check.
        $priorities = array( 'high', 'core', 'default', 'low' );

        // Loop through and target each context.
        foreach ( $contexts as $context ) {
            // Now loop through each priority and start the purging process.
            foreach ( $priorities as $priority ) {
                if ( isset( $wp_meta_boxes[$post_type][$context][$priority] ) ) {
                    foreach ( (array) $wp_meta_boxes[$post_type][$context][$priority] as $id => $metabox_data ) {
                        // If the metabox ID to pass over matches the ID given, remove it from the array and continue.
                        if ( in_array( $id, $pass_over ) ) {
                            unset( $pass_over[$id] );
                            continue;
                        }

                        // Otherwise, loop through the pass_over IDs and if we have a match, continue.
                        foreach ( $pass_over as $to_pass ) {
                            if ( preg_match( '#^' . $id . '#i', $to_pass ) ) {
                                continue;
                            }
                        }

                        // If we reach this point, remove the metabox completely.
                        unset( $wp_meta_boxes[$post_type][$context][$priority][$id] );
                    }
                }
            }
        }

    }

    /**
     * Callback for displaying content in the registered metabox.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function meta_box_callback( $post ) {

        // Keep security first.
        wp_nonce_field( 'soliloquy', 'soliloquy' );

        // Check for our meta overlay helper.
        $slider_data = get_post_meta( $post->ID, '_sol_slider_data', true );
        $helper      = get_post_meta( $post->ID, '_sol_just_published', true );
        $class       = '';
        if ( $helper ) {
            $class = 'soliloquy-helper-needed';
        }

        ?>
        <div id="soliloquy-tabs" class="soliloquy-clear <?php echo $class; ?>">
            <?php $this->meta_helper( $post, $slider_data ); ?>
            <ul id="soliloquy-tabs-nav" class="soliloquy-clear">
                <?php $i = 0; foreach ( (array) $this->get_soliloquy_tab_nav() as $id => $title ) : $class = 0 === $i ? 'soliloquy-active' : ''; ?>
                    <li class="<?php echo $class; ?>"><a href="#soliloquy-tab-<?php echo $id; ?>" title="<?php echo $title; ?>"><?php echo $title; ?></a></li>
                <?php $i++; endforeach; ?>
            </ul>
            <?php $i = 0; foreach ( (array) $this->get_soliloquy_tab_nav() as $id => $title ) : $class = 0 === $i ? 'soliloquy-active' : ''; ?>
                <div id="soliloquy-tab-<?php echo $id; ?>" class="soliloquy-tab soliloquy-clear <?php echo $class; ?>">
                    <?php do_action( 'soliloquy_tab_' . $id, $post ); ?>
                </div>
            <?php $i++; endforeach; ?>
        </div>
        <?php

    }

    /**
     * Callback for getting all of the tabs for Soliloquy sliders.
     *
     * @since 1.0.0
     *
     * @return array Array of tab information.
     */
    public function get_soliloquy_tab_nav() {

        $tabs = array(
            'images'     => __( 'Images', 'soliloquy' ),
            'config'     => __( 'Config', 'soliloquy' ),
            'mobile'     => __( 'Mobile', 'soliloquy' ),
        );
        $tabs = apply_filters( 'soliloquy_tab_nav', $tabs );

        // "Misc" tab is required.
        $tabs['misc'] = __( 'Misc', 'soliloquy' );

        return $tabs;

    }

    /**
     * Callback for displaying the UI for main images tab.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function images_tab( $post ) {

        // Output a notice if missing cropping extensions because Soliloquy needs them.
        if ( ! $this->has_gd_extension() && ! $this->has_imagick_extension() ) {
            ?>
            <div class="error below-h2">
                <p><strong><?php _e( 'The GD or Imagick libraries are not installed on your server. Soliloquy requires at least one (preferably Imagick) in order to crop images and may not work properly without it. Please contact your webhost and ask them to compile GD or Imagick for your PHP install.', 'soliloquy' ); ?></strong></p>
            </div>
            <?php
        }

        // Output the slider type selection items.
        ?>
        <ul id="soliloquy-types-nav" class="soliloquy-clear">
            <li class="soliloquy-type-label"><span><?php _e( 'Slider Type', 'soliloquy' ); ?></span></li>
            <?php $i = 0; foreach ( (array) $this->get_soliloquy_types( $post ) as $id => $title ) : ?>
                <li><label for="soliloquy-type-<?php echo $id; ?>"><input id="soliloquy-type-<?php echo sanitize_html_class( $id ); ?>" type="radio" name="_soliloquy[type]" value="<?php echo $id; ?>" <?php checked( $this->get_config( 'type', $this->get_config_default( 'type' ) ), $id ); ?> /> <?php echo $title; ?></label></li>
            <?php $i++; endforeach; ?>
            <li class="soliloquy-type-spinner"><span class="spinner soliloquy-spinner"></span></li>
        </ul>
        <?php

        // Output the display based on the type of slider being created.
        echo '<div id="soliloquy-slider-main" class="soliloquy-clear">';
            $this->images_display( $this->get_config( 'type', $this->get_config_default( 'type' ) ), $post );
        echo '</div>';

    }

    /**
     * Returns the types of sliders available.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     * @return array       Array of slider types to choose.
     */
    public function get_soliloquy_types( $post ) {

        $types = array(
            'default' => __( 'Default', 'soliloquy' )
        );

        return apply_filters( 'soliloquy_slider_types', $types, $post );

    }

    /**
     * Determines the Images tab display based on the type of slider selected.
     *
     * @since 1.0.0
     *
     * @param string $type The type of display to output.
     * @param object $post The current post object.
     */
    public function images_display( $type = 'default', $post ) {

        // Output a unique hidden field for settings save testing for each type of slider.
        echo '<input type="hidden" name="_soliloquy[type_' . $type . ']" value="1" />';

        // Output the display based on the type of slider available.
        switch ( $type ) {
            case 'default' :
                $this->do_default_display( $post );
                break;
            default:
                do_action( 'soliloquy_display_' . $type, $post );
                break;
        }

    }

    /**
     * Callback for displaying the default slider UI.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function do_default_display( $post ) {
        
        // Output the custom media upload form.
        Soliloquy_Media::get_instance()->media_upload_form();

        // Prepare output data.
        $slider_data = get_post_meta( $post->ID, '_sol_slider_data', true );
        ?>
        <ul id="soliloquy-output" class="soliloquy-clear">
            <?php if ( ! empty( $slider_data['slider'] ) ) : ?>
                <?php foreach ( $slider_data['slider'] as $id => $data ) : ?>
                    <?php echo $this->get_slider_item( $id, $data, ( ! empty( $data['type'] ) ? $data['type'] : 'image' ), $post->ID ); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        <?php $this->media_library( $post );

    }

    /**
     * Inserts the meta icon for displaying useful slider meta like shortcode and template tag.
     *
     * @since 1.0.0
     *
     * @param object $post        The current post object.
     * @param array $slider_data Array of slider data for the current post.
     * @return null               Return early if this is an auto-draft.
     */
    public function meta_helper( $post, $slider_data ) {

        if ( isset( $post->post_status ) && 'auto-draft' == $post->post_status ) {
            return;
        }

        // Check for our meta overlay helper.
        $helper = get_post_meta( $post->ID, '_sol_just_published', true );
        $class  = '';
        if ( $helper ) {
            $class = 'soliloquy-helper-active';
            delete_post_meta( $post->ID, '_sol_just_published' );
        }

        ?>
        <div class="soliloquy-meta-helper <?php echo $class; ?>">
            <span class="soliloquy-meta-close-text"><?php _e( '(click the icon to open and close the overlay dialog)', 'soliloquy' ); ?></span>
            <a href="#" class="soliloquy-meta-icon" title="<?php esc_attr__( 'Click here to view meta information about this slider.', 'soliloquy' ); ?>"></a>
            <div class="soliloquy-meta-information">
                <p><?php _e( 'You can place this slider anywhere into your posts, pages, custom post types or widgets by using <strong>one</strong> the shortcode(s) below:', 'soliloquy' ); ?></p>
                <code><?php echo '[soliloquy id="' . $post->ID . '"]'; ?></code>
                <?php if ( ! empty( $slider_data['config']['slug'] ) ) : ?>
                    <br><code><?php echo '[soliloquy slug="' . $slider_data['config']['slug'] . '"]'; ?></code>
                <?php endif; ?>
                <p><?php _e( 'You can also place this slider into your template files by using <strong>one</strong> the template tag(s) below:', 'soliloquy' ); ?></p>
                <code><?php echo 'if ( function_exists( \'soliloquy\' ) ) { soliloquy( \'' . $post->ID . '\' ); }'; ?></code>
                <?php if ( ! empty( $slider_data['config']['slug'] ) ) : ?>
                    <br><code><?php echo 'if ( function_exists( \'soliloquy\' ) ) { soliloquy( \'' . $slider_data['config']['slug'] . '\', \'slug\' ); }'; ?></code>
                <?php endif; ?>
            </div>
        </div>
        <?php

    }

    /**
     * Callback for displaying the UI for selecting images from the media library to insert.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function media_library( $post ) {

        ?>
        <div id="soliloquy-upload-ui-wrapper">
            <div id="soliloquy-upload-ui" class="soliloquy-image-meta" style="display: none;">
                <div class="media-modal wp-core-ui">
                    <a class="media-modal-close" href="#"><span class="media-modal-icon"></span></a>
                    <div class="media-modal-content">
                        <div class="media-frame soliloquy-media-frame wp-core-ui hide-menu soliloquy-meta-wrap">
                            <div class="media-frame-title">
                                <h1><?php _e( 'Insert Slides into Slider', 'soliloquy' ); ?></h1>
                            </div>
                            <div class="media-frame-router">
                                <div class="media-router">
                                    <a href="#" class="media-menu-item active" data-soliloquy-content="image-slides"><?php _e( 'Image Slides', 'soliloquy' ); ?></a>
                                    <a href="#" class="media-menu-item" data-soliloquy-content="video-slides"><?php _e( 'Video Slides', 'soliloquy' ); ?></a>
                                    <a href="#" class="media-menu-item" data-soliloquy-content="html-slides"><?php _e( 'HTML Slides', 'soliloquy' ); ?></a>
                                    <?php do_action( 'soliloquy_modal_router', $post ); ?>
                                </div><!-- end .media-router -->
                            </div><!-- end .media-frame-router -->
                            <?php $this->image_slides_content( $post ); ?>
                            <?php $this->video_slides_content( $post ); ?>
                            <?php $this->html_slides_content( $post ); ?>
                            <?php do_action( 'soliloquy_modal_content', $post ); ?>
                            <div class="media-frame-toolbar">
                                <div class="media-toolbar">
                                    <div class="media-toolbar-primary">
                                        <a href="#" class="soliloquy-media-insert button media-button button-large button-primary media-button-insert" title="<?php esc_attr_e( 'Insert Slides into Slider', 'soliloquy' ); ?>"><?php _e( 'Insert Slides into Slider', 'soliloquy' ); ?></a>
                                    </div><!-- end .media-toolbar-primary -->
                                </div><!-- end .media-toolbar -->
                            </div><!-- end .media-frame-toolbar -->
                        </div><!-- end .media-frame -->
                    </div><!-- end .media-modal-content -->
                </div><!-- end .media-modal -->
                <div class="media-modal-backdrop"></div>
            </div><!-- end .soliloquy-image-meta -->
        </div><!-- end #soliloquy-upload-ui-wrapper-->
        <?php

    }

    /**
     * Outputs the image slides content in the modal selection window.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function image_slides_content( $post ) {

        ?>
        <div id="soliloquy-image-slides">
            <div class="media-frame-content">
                <div class="attachments-browser">
                    <div class="media-toolbar soliloquy-library-toolbar">
                        <div class="media-toolbar-primary">
                            <input type="search" placeholder="<?php esc_attr_e( 'Search', 'soliloquy' ); ?>" id="soliloquy-slider-search" class="search" value="" />
                        </div>
                        <div class="media-toolbar-secondary">
                            <a class="button media-button button-large button-secodary soliloquy-load-library" href="#" data-soliloquy-offset="20"><?php _e( 'Load More Images from Library', 'soliloquy' ); ?></a>
                            <span class="spinner soliloquy-spinner"></span>
                        </div>
                    </div>
                    <?php $library = get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'posts_per_page' => 20 ) ); ?>
                    <?php if ( $library ) : ?>
                    <ul class="attachments soliloquy-slider">
                    <?php foreach ( (array) $library as $image ) :
                        $has_slider = get_post_meta( $image->ID, '_sol_has_slider', true );
                        $class       = $has_slider && in_array( $post->ID, (array) $has_slider ) ? ' selected soliloquy-in-slider' : ''; ?>
                        <li class="attachment<?php echo $class; ?>" data-attachment-id="<?php echo absint( $image->ID ); ?>">
                            <div class="attachment-preview landscape">
                                <div class="thumbnail">
                                    <div class="centered">
                                        <?php $src = wp_get_attachment_image_src( $image->ID, 'thumbnail' ); ?>
                                        <img src="<?php echo esc_url( $src[0] ); ?>" />
                                    </div>
                                </div>
                                <a class="check" href="#"><div class="media-modal-icon"></div></a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    </ul><!-- end .soliloquy-meta -->
                    <?php endif; ?>
                    <div class="media-sidebar">
                        <div class="soliloquy-meta-sidebar">
                            <h3><?php _e( 'Helpful Tips', 'soliloquy' ); ?></h3>
                            <strong><?php _e( 'Selecting Images', 'soliloquy' ); ?></strong>
                            <p><?php _e( 'You can insert any image from your Media Library into your slider. If the image you want to insert is not shown on the screen, you can either click on the "Load More Images from Library" button to load more images or use the search box to find the images you are looking for.', 'soliloquy' ); ?></p>
                        </div><!-- end .soliloquy-meta-sidebar -->
                    </div><!-- end .media-sidebar -->
                </div><!-- end .attachments-browser -->
            </div><!-- end .media-frame-content -->
        </div><!-- end #soliloquy-image-slides -->
        <?php

    }

    /**
     * Outputs the video slides content in the modal selection window.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function video_slides_content( $post ) {

        ?>
        <div id="soliloquy-video-slides" style="display:none;">
            <div class="media-frame-content">
                <div class="attachments-browser">
                    <div class="soliloquy-meta attachments soliloquy-ui-content">
                        <p class="no-margin-top"><a href="#" class="soliloquy-add-video-slide button button-large button-primary" data-soliloquy-video-number="1" title="<?php esc_attr_e( 'Add Video Slide', 'soliloquy' ); ?>"><?php _e( 'Add Video Slide', 'soliloquy' ); ?></a></p>
                    </div><!-- end .soliloquy-meta -->
                    <div class="media-sidebar">
                        <div class="soliloquy-meta-sidebar">
                            <h3><?php _e( 'Helpful Tips', 'soliloquy' ); ?></h3>
                            <strong>Creating Video Slides</strong>
                            <p><?php _e( 'Video links can be from either YouTube, Vimeo, Wistia or local videos. They <strong>must</strong> follow one of the formats listed below:', 'soliloquy' ) ?></p>
                            
                            <div class="soliloquy-accepted-urls">                               
                                <span><strong><?php _e( 'YouTube URLs', 'soliloquy' ); ?></strong></span>
                                <span>youtube.com/v/{vidid}</span>
                                <span>youtube.com/vi/{vidid}</span>
                                <span>youtube.com/?v={vidid}</span>
                                <span>youtube.com/?vi={vidid}</span>
                                <span>youtube.com/watch?v={vidid}</span>
                                <span>youtube.com/watch?vi={vidid}</span>
                                <span>youtu.be/{vidid}</span><br />
                            
                                <span><strong><?php _e( 'Vimeo URLs', 'soliloquy' ); ?></strong></span>
                                <span>vimeo.com/{vidid}</span>
                                <span>vimeo.com/groups/tvc/videos/{vidid}</span>
                                <span>player.vimeo.com/video/{vidid}</span><br />
                            
                                <span><strong><?php _e( 'Wistia URLs', 'soliloquy' ); ?></strong></span>
                                <span>*wistia.com/medias/*</span>
                                <span>*wistia.com/embed/*</span>
                                <span>*wi.st/medias/*</span>
                                <span>*wi.st/embed/*</span><br />

                                <span><strong><?php _e( 'Local URLs', 'soliloquy' ); ?></strong></span>
                                <span>http://yoursite.com/video.mp4</span>
                                <span>http://yoursite.com/video.flv</span>
                                <span>http://yoursite.com/video.ogv</span>
                                <span>http://yoursite.com/video.webm</span>
                            </div>
                        </div><!-- end .soliloquy-meta-sidebar -->
                    </div><!-- end .media-sidebar -->
                </div><!-- end .attachments-browser -->
            </div><!-- end .media-frame-content -->
        </div><!-- end #soliloquy-image-slides -->
        <?php

    }

    /**
     * Outputs the html slides content in the modal selection window.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function html_slides_content( $post ) {

        ?>
        <div id="soliloquy-html-slides" style="display:none;">
            <div class="media-frame-content">
                <div class="attachments-browser">
                    <div class="soliloquy-meta attachments soliloquy-ui-content">
                        <p class="no-margin-top"><a href="#" class="soliloquy-add-html-slide button button-large button-primary" data-soliloquy-html-number="1" title="<?php esc_attr_e( 'Add HTML Slide', 'soliloquy' ); ?>"><?php _e( 'Add HTML Slide', 'soliloquy' ); ?></a></p>
                    </div><!-- end .soliloquy-meta -->
                    <div class="media-sidebar">
                        <div class="soliloquy-meta-sidebar">
                            <h3><?php _e( 'Helpful Tips', 'soliloquy' ); ?></h3>
                            <strong><?php _e( 'Creating HTML Slides', 'soliloquy' ) ?></strong>
                            <p><?php _e( 'Each HTML slide should have its own unique name (for identification purposes) and code for outputting into the slider. The code will be inserted inside of the slide <code>&lt;li&gt;</code> tag and can be styled with custom CSS as you need.', 'soliloquy' ); ?></p>
                        </div><!-- end .soliloquy-meta-sidebar -->
                    </div><!-- end .media-sidebar -->
                </div><!-- end .attachments-browser -->
            </div><!-- end .media-frame-content -->
        </div><!-- end #soliloquy-image-slides -->
        <?php

    }

    /**
     * Callback for displaying the UI for setting slider config options.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function config_tab( $post ) {

        ?>
        <div id="soliloquy-config">
            <p class="soliloquy-intro"><?php _e( 'The settings below adjust the basic configuration options for the slider display.', 'soliloquy' ); ?></p>
            <table class="form-table">
                <tbody>
                    <tr id="soliloquy-config-slider-theme-box">
                        <th scope="row">
                            <label for="soliloquy-config-slider-theme"><?php _e( 'Slider Theme', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <select id="soliloquy-config-slider-theme" name="_soliloquy[slider_theme]">
                                <?php foreach ( (array) $this->get_slider_themes() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'slider_theme', $this->get_config_default( 'slider_theme' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Sets the theme for the slider display.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-slider-size-box">
                        <th scope="row">
                            <label for="soliloquy-config-slider-width"><?php _e( 'Slider Dimensions', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <select id="soliloquy-config-slider-size" name="_soliloquy[slider_size]">
                                <?php foreach ( (array) $this->get_slider_sizes() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>" data-soliloquy-width="<?php echo $data['width']; ?>" data-soliloquy-height="<?php echo $data['height']; ?>"<?php selected( $data['value'], $this->get_config( 'slider_size', $this->get_config_default( 'slider_size' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select><br>
                            <input id="soliloquy-config-slider-width" type="number" name="_soliloquy[slider_width]" value="<?php echo absint( $this->get_config( 'slider_width', $this->get_config_default( 'slider_width' ) ) ); ?>" /> &#215; <input id="soliloquy-config-slider-height" type="number" name="_soliloquy[slider_height]" value="<?php echo absint( $this->get_config( 'slider_height', $this->get_config_default( 'slider_height' ) ) ); ?>" /> <span class="soliloquy-unit"><?php _e( 'px', 'soliloquy' ); ?></span>
                            <p class="description"><?php _e( 'Sets the width and height dimensions for the slider.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-transition-box">
                        <th scope="row">
                            <label for="soliloquy-config-transition"><?php _e( 'Slider Transition', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <select id="soliloquy-config-transition" name="_soliloquy[transition]" data-conditional="soliloquy-config-slider-speed-box,soliloquy-config-caption-delay-box,soliloquy-config-auto-box,soliloquy-config-arrows-box,soliloquy-config-control-box,soliloquy-config-pauseplay-box,soliloquy-config-loop-box,soliloquy-config-keyboard-box,soliloquy-config-css-box,soliloquy-config-delay-box,soliloquy-config-start-box" data-conditional-value="ticker" data-conditional-display="false">
                                <?php foreach ( (array) $this->get_slider_transitions() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'transition', $this->get_config_default( 'transition' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Sets the type of transition for the slider. Note: The Ticker transition is designed for image slides only, and does not provide interactive functionality (thumbnails, navigation arrows etc). It\'s designed as a basic, continuous scrolling slideshow.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-slider-duration-box">
                        <th scope="row">
                            <label for="soliloquy-config-duration"><?php _e( 'Slider Transition Duration', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-duration" type="number" name="_soliloquy[duration]" value="<?php echo absint( $this->get_config( 'duration', $this->get_config_default( 'duration' ) ) ); ?>" /> <span class="soliloquy-unit"><?php _e( 'ms', 'soliloquy' ); ?></span>
                            <p class="description"><?php _e( 'Sets the amount of time between each slide transition <strong>(in milliseconds)</strong>.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-slider-speed-box">
                        <th scope="row">
                            <label for="soliloquy-config-speed"><?php _e( 'Slider Transition Speed', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-speed" type="number" name="_soliloquy[speed]" value="<?php echo absint( $this->get_config( 'speed', $this->get_config_default( 'speed' ) ) ); ?>" /> <span class="soliloquy-unit"><?php _e( 'ms', 'soliloquy' ); ?></span>
                            <p class="description"><?php _e( 'Sets the transition speed when moving from one slide to the next <strong>(in milliseconds)</strong>.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-position-box">
                        <th scope="row">
                            <label for="soliloquy-config-position"><?php _e( 'Slider Position', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <select id="soliloquy-config-position" name="_soliloquy[position]">
                                <?php foreach ( (array) $this->get_slider_positions() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'position', $this->get_config_default( 'position' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Sets the position of the slider on the page.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-gutter-box">
                        <th scope="row">
                            <label for="soliloquy-config-gutter"><?php _e( 'Slider Gutter', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-gutter" type="number" name="_soliloquy[gutter]" value="<?php echo absint( $this->get_config( 'gutter', $this->get_config_default( 'gutter' ) ) ); ?>" /> <span class="soliloquy-unit"><?php _e( 'px', 'soliloquy' ); ?></span>
                            <p class="description"><?php _e( 'Sets the gutter between the slider and your content based on slider position.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-caption-position-box">
                        <th scope="row">
                            <label for="soliloquy-config-position-delay"><?php _e( 'Caption Position', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <select id="soliloquy-caption-position" name="_soliloquy[caption_position]">
                                <?php foreach ( (array) $this->get_caption_positions() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'caption_position', $this->get_config_default( 'caption_position' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'The position of the caption for each slide, if specified.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-caption-delay-box">
                        <th scope="row">
                            <label for="soliloquy-config-caption-delay"><?php _e( 'Caption Transition Delay', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-caption-delay" type="number" name="_soliloquy[caption_delay]" value="<?php echo absint( $this->get_config( 'caption_delay', $this->get_config_default( 'caption_delay' ) ) ); ?>" /> <span class="soliloquy-unit"><?php _e( 'ms', 'soliloquy' ); ?></span>
                            <p class="description"><?php _e( 'The number of milliseconds to delay displaying the caption after the slide has appeared <strong>(in milliseconds)</strong>. Set to zero for caption to display immediately.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-auto-box">
                        <th scope="row">
                            <label for="soliloquy-config-auto"><?php _e( 'Autostart Slider?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-auto" type="checkbox" name="_soliloquy[auto]" value="<?php echo $this->get_config( 'auto', $this->get_config_default( 'auto' ) ); ?>" <?php checked( $this->get_config( 'auto', $this->get_config_default( 'auto' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'If disabled, visitors will need to manually progress through the slider.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-arrows-box">
                        <th scope="row">
                            <label for="soliloquy-config-arrows"><?php _e( 'Show Slider Arrows?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-arrows" type="checkbox" name="_soliloquy[arrows]" value="<?php echo $this->get_config( 'arrows', $this->get_config_default( 'arrows' ) ); ?>" <?php checked( $this->get_config( 'arrows', $this->get_config_default( 'arrows' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables slider navigation arrows.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-control-box">
                        <th scope="row">
                            <label for="soliloquy-config-control"><?php _e( 'Show Slider Control Nav?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-control" type="checkbox" name="_soliloquy[control]" value="<?php echo $this->get_config( 'control', $this->get_config_default( 'control' ) ); ?>" <?php checked( $this->get_config( 'control', $this->get_config_default( 'control' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables slider control (typically circles) navigation.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-pauseplay-box">
                        <th scope="row">
                            <label for="soliloquy-config-pauseplay"><?php _e( 'Show Pause/Play Controls?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-pauseplay" type="checkbox" name="_soliloquy[pauseplay]" value="<?php echo $this->get_config( 'pauseplay', $this->get_config_default( 'pauseplay' ) ); ?>" <?php checked( $this->get_config( 'pauseplay', $this->get_config_default( 'pauseplay' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables slider pause/play elements.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-hover-box">
                        <th scope="row">
                            <label for="soliloquy-config-hover"><?php _e( 'Pause on Hover?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-hover" type="checkbox" name="_soliloquy[hover]" value="<?php echo $this->get_config( 'hover', $this->get_config_default( 'hover' ) ); ?>" <?php checked( $this->get_config( 'hover', $this->get_config_default( 'hover' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Pauses the slider (if set to autostart) when a visitor hovers over the slider.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-loop-box">
                        <th scope="row">
                            <label for="soliloquy-config-loop"><?php _e( 'Loop Slider?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-loop" type="checkbox" name="_soliloquy[loop]" value="<?php echo $this->get_config( 'loop', $this->get_config_default( 'loop' ) ); ?>" <?php checked( $this->get_config( 'loop', $this->get_config_default( 'loop' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables slider looping.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-random-box">
                        <th scope="row">
                            <label for="soliloquy-config-random"><?php _e( 'Randomize Slider?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-random" type="checkbox" name="_soliloquy[random]" value="<?php echo $this->get_config( 'random', $this->get_config_default( 'random' ) ); ?>" <?php checked( $this->get_config( 'random', $this->get_config_default( 'random' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Randomizes slides in the slider.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-keyboard-box">
                        <th scope="row">
                            <label for="soliloquy-config-keyboard"><?php _e( 'Enable Keyboard Navigation?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-keyboard" type="checkbox" name="_soliloquy[keyboard]" value="<?php echo $this->get_config( 'keyboard', $this->get_config_default( 'keyboard' ) ); ?>" <?php checked( $this->get_config( 'keyboard', $this->get_config_default( 'keyboard' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables keyboard navigation for the slider.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-css-box">
                        <th scope="row">
                            <label for="soliloquy-config-css"><?php _e( 'Use CSS Transitions?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-css" type="checkbox" name="_soliloquy[css]" value="<?php echo $this->get_config( 'css', $this->get_config_default( 'css' ) ); ?>" <?php checked( $this->get_config( 'css', $this->get_config_default( 'css' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables hardware accelerated transitions via CSS.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-smooth-box">
                        <th scope="row">
                            <label for="soliloquy-config-smooth"><?php _e( 'Use Adaptive Height?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-smooth" type="checkbox" name="_soliloquy[smooth]" value="<?php echo $this->get_config( 'smooth', $this->get_config_default( 'smooth' ) ); ?>" <?php checked( $this->get_config( 'smooth', $this->get_config_default( 'smooth' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Allows the slider to adapt smoothly to slides with different sizes.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-dimensions-box">
                        <th scope="row">
                            <label for="soliloquy-config-dimensions"><?php _e( 'Set Dimensions on Images?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-dimensions" type="checkbox" name="_soliloquy[dimensions]" value="<?php echo $this->get_config( 'dimensions', $this->get_config_default( 'dimensions' ) ); ?>" <?php checked( $this->get_config( 'dimensions', $this->get_config_default( 'dimensions' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the width and height attributes on the img element. Only needs to be enabled if you need to meet Google Pagespeeds requirements.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-slider-box">
                        <th scope="row">
                            <label for="soliloquy-config-slider"><?php _e( 'Crop Images in Slider?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-slider" type="checkbox" name="_soliloquy[slider]" value="<?php echo $this->get_config( 'slider', $this->get_config_default( 'slider' ) ); ?>" <?php checked( $this->get_config( 'slider', $this->get_config_default( 'slider' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables image cropping based on slider dimensions <strong>(recommended)</strong>.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-delay-box">
                        <th scope="row">
                            <label for="soliloquy-config-delay"><?php _e( 'Slider Delay', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-delay" type="number" name="_soliloquy[delay]" value="<?php echo absint( $this->get_config( 'delay', $this->get_config_default( 'delay' ) ) ); ?>" /> <span class="soliloquy-unit"><?php _e( 'ms', 'soliloquy' ); ?></span>
                            <p class="description"><?php _e( 'If autostarting, this sets a delay before the slider should begin transitioning <strong>(in milliseconds)</strong>.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-start-box">
                        <th scope="row">
                            <label for="soliloquy-config-start"><?php _e( 'Start On Slide', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-start" type="number" name="_soliloquy[start]" value="<?php echo absint( $this->get_config( 'start', $this->get_config_default( 'start' ) ) ); ?>" />
                            <p class="description"><?php _e( 'The starting slide number (index based, starts at 0).', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-aria-live-box">
                        <th scope="row">
                            <label for="soliloquy-config-aria-live"><?php _e( 'ARIA Live Value', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <select id="soliloquy-config-aria-live" name="_soliloquy[aria_live]">
                                <?php foreach ( (array) $this->get_aria_live_values() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'aria_live', $this->get_config_default( 'aria_live' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Accessibility: Defines the priority with which screen readers should treat updates to this slider.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>

                    <?php do_action( 'soliloquy_config_box', $post ); ?>
                </tbody>
            </table>
        </div>
        <?php

    }

    /**
     * Callback for displaying the UI for setting slider mobile options.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function mobile_tab( $post ) {

        ?>
        <div id="soliloquy-config">
            <p class="soliloquy-intro"><?php _e( 'The settings below adjust configuration options for the slider display when viewed on a mobile device.', 'soliloquy' ); ?></p>
            <table class="form-table">
                <tbody>
                    <tr id="soliloquy-config-mobile-box">
                        <th scope="row">
                            <label for="soliloquy-config-mobile"><?php _e( 'Create Mobile Slider Images?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-mobile" type="checkbox" name="_soliloquy[mobile]" value="<?php echo $this->get_config( 'mobile', $this->get_config_default( 'mobile' ) ); ?>" <?php checked( $this->get_config( 'mobile', $this->get_config_default( 'mobile' ) ), 1 ); ?> data-conditional="soliloquy-config-mobile-size-box" />
                            <span class="description"><?php _e( 'Enables or disables creating specific images for mobile devices.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-mobile-size-box">
                        <th scope="row">
                            <label for="soliloquy-config-mobile-width"><?php _e( 'Mobile Dimensions', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-mobile-width" type="number" name="_soliloquy[mobile_width]" value="<?php echo absint( $this->get_config( 'mobile_width', $this->get_config_default( 'mobile_width' ) ) ); ?>" /> &#215; <input id="soliloquy-config-mobile-height" type="number" name="_soliloquy[mobile_height]" value="<?php echo absint( $this->get_config( 'mobile_height', $this->get_config_default( 'mobile_height' ) ) ); ?>" /> <span class="soliloquy-unit"><?php _e( 'px', 'soliloquy' ); ?></span>
                            <p class="description"><?php _e( 'These will be the sizes used for images displayed on mobile devices.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-mobile-caption-box">
                        <th scope="row">
                            <label for="soliloquy-config-mobile-caption"><?php _e( 'Show Captions on Mobile?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-mobile-caption" type="checkbox" name="_soliloquy[mobile_caption]" value="<?php echo $this->get_config( 'mobile_caption', $this->get_config_default( 'mobile_caption' ) ); ?>" <?php checked( $this->get_config( 'mobile_caption', $this->get_config_default( 'mobile_caption' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables captions to be displayed on mobile.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>

                    <?php do_action( 'soliloquy_mobile_box', $post ); ?>
                </tbody>
            </table>
        </div>
        <?php

    }

    /**
     * Callback for displaying the UI for setting slider miscellaneous options.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function misc_tab( $post ) {

        ?>
        <div id="soliloquy-misc">
            <p class="soliloquy-intro"><?php _e( 'The settings below adjust the miscellaneous settings for the slider lightbox display.', 'soliloquy' ); ?></p>
            <table class="form-table">
                <tbody>
                    <tr id="soliloquy-config-title-box">
                        <th scope="row">
                            <label for="soliloquy-config-title"><?php _e( 'Slider Title', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-title" type="text" name="_soliloquy[title]" value="<?php echo $this->get_config( 'title', $this->get_config_default( 'title' ) ); ?>" />
                            <p class="description"><?php _e( 'Internal slider title for identification in the admin.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-slug-box">
                        <th scope="row">
                            <label for="soliloquy-config-slug"><?php _e( 'Slider Slug', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-slug" type="text" name="_soliloquy[slug]" value="<?php echo $this->get_config( 'slug', $this->get_config_default( 'slug' ) ); ?>" />
                            <p class="description"><?php _e( '<strong>Unique</strong> internal slider slug for identification and advanced slider queries.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-classes-box">
                        <th scope="row">
                            <label for="soliloquy-config-classes"><?php _e( 'Custom Slider Classes', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <textarea id="soliloquy-config-classes" rows="5" cols="75" name="_soliloquy[classes]" placeholder="<?php _e( 'Enter custom slider CSS classes here, one per line.', 'soliloquy' ); ?>"><?php echo implode( "\n", (array) $this->get_config( 'classes', $this->get_config_default( 'classes' ) ) ); ?></textarea>
                            <p class="description"><?php _e( 'Adds custom CSS classes to this slider. Enter one class per line.', 'soliloquy' ); ?></p>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-import-export-box">
                        <th scope="row">
                            <label for="soliloquy-config-import-slider"><?php _e( 'Import/Export Slider', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <form></form>
                            <?php 
                            $import_url = 'auto-draft' == $post->post_status ? add_query_arg( array( 'post' => $post->ID, 'action' => 'edit', 'soliloquy-imported' => true ), admin_url( 'post.php' ) ) : add_query_arg( 'soliloquy-imported', true ); 
                            $import_url = esc_url( $import_url );
                            ?>
                            <form action="<?php echo $import_url; ?>" id="soliloquy-config-import-slider-form" class="soliloquy-import-form" method="post" enctype="multipart/form-data">
                                <input id="soliloquy-config-import-slider" type="file" name="soliloquy_import_slider" />
                                <input type="hidden" name="soliloquy_import" value="1" />
                                <input type="hidden" name="soliloquy_post_id" value="<?php echo $post->ID; ?>" />
                                <?php wp_nonce_field( 'soliloquy-import', 'soliloquy-import' ); ?>
                                <?php submit_button( __( 'Import Slider', 'soliloquy' ), 'secondary', 'soliloquy-import-submit', false ); ?>
                                <span class="spinner soliloquy-spinner"></span>
                            </form>
                            <form id="soliloquy-config-export-slider-form" method="post">
                                <input type="hidden" name="soliloquy_export" value="1" />
                                <input type="hidden" name="soliloquy_post_id" value="<?php echo $post->ID; ?>" />
                                <?php wp_nonce_field( 'soliloquy-export', 'soliloquy-export' ); ?>
                                <?php submit_button( __( 'Export Slider', 'soliloquy' ), 'secondary', 'soliloquy-export-submit', false ); ?>
                            </form>
                        </td>
                    </tr>
                    <tr id="soliloquy-config-rtl-box">
                        <th scope="row">
                            <label for="soliloquy-config-rtl"><?php _e( 'Enable RTL Support?', 'soliloquy' ); ?></label>
                        </th>
                        <td>
                            <input id="soliloquy-config-rtl" type="checkbox" name="_soliloquy[rtl]" value="<?php echo $this->get_config( 'rtl', $this->get_config_default( 'rtl' ) ); ?>" <?php checked( $this->get_config( 'rtl', $this->get_config_default( 'rtl' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables RTL support in Soliloquy for right-to-left languages.', 'soliloquy' ); ?></span>
                        </td>
                    </tr>
                    
                    <?php do_action( 'soliloquy_misc_box', $post ); ?>
                </tbody>
            </table>
        </div>
        <?php

    }

    /**
     * Callback for saving values from Soliloquy metaboxes.
     *
     * @since 1.0.0
     *
     * @param int $post_id The current post ID.
     * @param object $post The current post object.
     */
    public function save_meta_boxes( $post_id, $post ) {

        // Bail out if we fail a security check.
        if ( ! isset( $_POST['soliloquy'] ) || ! wp_verify_nonce( $_POST['soliloquy'], 'soliloquy' ) || ! isset( $_POST['_soliloquy'] ) ) {
            return;
        }

        // Bail out if running an autosave, ajax, cron or revision.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Bail if this is not the correct post type.
        if ( isset( $post->post_type ) && in_array( $post->post_type, array_keys( $this->get_skipped_posttypes() ) ) ) {
            return;
        }

        // Bail out if the user doesn't have the correct permissions to update the slider.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Sanitize all user inputs.
        $settings = get_post_meta( $post_id, '_sol_slider_data', true );
        if ( empty( $settings ) ) {
            $settings = array();
        }

        // If the ID of the slider is not set or is lost, replace it now.
        if ( empty( $settings['id'] ) || ! $settings['id'] ) {
            $settings['id'] = $post_id;
        }

        // Save the config settings.
        $settings['config']['type']          = isset( $_POST['_soliloquy']['type'] ) ? $_POST['_soliloquy']['type'] : $this->get_config_default( 'type' );
        $settings['config']['slider_size']   = esc_attr( $_POST['_soliloquy']['slider_size'] );
        $settings['config']['slider_theme']  = esc_attr( $_POST['_soliloquy']['slider_theme'] );
        $settings['config']['slider_width']  = absint( $_POST['_soliloquy']['slider_width'] );
        $settings['config']['slider_height'] = absint( $_POST['_soliloquy']['slider_height'] );
        $settings['config']['position']      = esc_attr( $_POST['_soliloquy']['position'] );
        $settings['config']['transition']    = esc_attr( $_POST['_soliloquy']['transition'] );
        $settings['config']['duration']      = absint( $_POST['_soliloquy']['duration'] );
        $settings['config']['speed']         = absint( $_POST['_soliloquy']['speed'] );
        $settings['config']['caption_position'] = esc_attr( $_POST['_soliloquy']['caption_position'] );
        $settings['config']['caption_delay'] = absint( $_POST['_soliloquy']['caption_delay'] );
        $settings['config']['gutter']        = absint( $_POST['_soliloquy']['gutter'] );
        $settings['config']['auto']          = isset( $_POST['_soliloquy']['auto'] ) ? 1 : 0;
        $settings['config']['smooth']        = isset( $_POST['_soliloquy']['smooth'] ) ? 1 : 0;
        $settings['config']['dimensions']    = isset( $_POST['_soliloquy']['dimensions'] ) ? 1 : 0;
        $settings['config']['arrows']        = isset( $_POST['_soliloquy']['arrows'] ) ? 1 : 0;
        $settings['config']['control']       = isset( $_POST['_soliloquy']['control'] ) ? 1 : 0;
        $settings['config']['pauseplay']     = isset( $_POST['_soliloquy']['pauseplay'] ) ? 1 : 0;
        $settings['config']['mobile_caption']= isset( $_POST['_soliloquy']['mobile_caption'] ) ? 1 : 0;
        $settings['config']['hover']         = isset( $_POST['_soliloquy']['hover'] ) ? 1 : 0;
        $settings['config']['slider']        = isset( $_POST['_soliloquy']['slider'] ) ? 1 : 0;
        $settings['config']['mobile']        = isset( $_POST['_soliloquy']['mobile'] ) ? 1 : 0;
        $settings['config']['mobile_width']  = absint( $_POST['_soliloquy']['mobile_width'] );
        $settings['config']['mobile_height'] = absint( $_POST['_soliloquy']['mobile_height'] );
        $settings['config']['keyboard']      = isset( $_POST['_soliloquy']['keyboard'] ) ? 1 : 0;
        $settings['config']['css']           = isset( $_POST['_soliloquy']['css'] ) ? 1 : 0;
        $settings['config']['loop']          = isset( $_POST['_soliloquy']['loop'] ) ? 1 : 0;
        $settings['config']['random']        = isset( $_POST['_soliloquy']['random'] ) ? 1 : 0;
        $settings['config']['delay']         = absint( $_POST['_soliloquy']['delay'] );
        $settings['config']['start']         = absint( $_POST['_soliloquy']['start'] );
        $settings['config']['aria_live']     = esc_attr( $_POST['_soliloquy']['aria_live'] );

        // Misc
        $settings['config']['classes']       = explode( "\n", $_POST['_soliloquy']['classes'] );
        $settings['config']['title']         = trim( strip_tags( $_POST['_soliloquy']['title'] ) );
        $settings['config']['slug']          = sanitize_text_field( $_POST['_soliloquy']['slug'] );
        $settings['config']['rtl']           = ( isset( $_POST['_soliloquy']['rtl'] ) ? 1 : 0 );

        // If on an soliloquy post type, map the title and slug of the post object to the custom fields if no value exists yet.
        if ( isset( $post->post_type ) && 'soliloquy' == $post->post_type ) {
            if ( empty( $settings['config']['title'] ) ) {
                $settings['config']['title'] = trim( strip_tags( $post->post_title ) );
            }

            if ( empty( $settings['config']['slug'] ) ) {
                $settings['config']['slug'] = sanitize_text_field( $post->post_name );
            }
        }
        
        // Get publish/draft status from Post
        $settings['status'] = $post->post_status;
        
        // Provide a filter to override settings.
        $settings = apply_filters( 'soliloquy_save_settings', $settings, $post_id, $post );

        // Update the post meta.
        update_post_meta( $post_id, '_sol_slider_data', $settings );

        // If the post has just been published for the first time
        // 1. set meta field for the slider meta overlay helper.
        // 2. mark all slides as published
        if ( isset( $post->post_date ) && isset( $post->post_modified ) && $post->post_date === $post->post_modified ) {
            update_post_meta( $post_id, '_sol_just_published', true );
            $settings = $this->change_slider_states( $post_id );
        }
        
        // If the crop option is checked, crop images accordingly.
        if ( isset( $settings['config']['slider'] ) && $settings['config']['slider'] ) {
            $args = array(
                'position' => 'c',
                'width'    => $this->get_config( 'slider_width', $this->get_config_default( 'slider_width' ) ),
                'height'   => $this->get_config( 'slider_height', $this->get_config_default( 'slider_height' ) ),
                'quality'  => 100,
                'retina'   => false
            );
            $args = apply_filters( 'soliloquy_crop_image_args', $args );
            $this->crop_images( $args, $post_id );
        }

        // If the mobile option is checked, crop images for mobile accordingly.
        if ( isset( $settings['config']['slider'] ) && $settings['config']['slider'] ) {
            if ( isset( $settings['config']['mobile'] ) && $settings['config']['mobile'] ) {
                $args = array(
                    'position' => 'c',
                    'width'    => $this->get_config( 'mobile_width', $this->get_config_default( 'mobile_width' ) ),
                    'height'   => $this->get_config( 'mobile_height', $this->get_config_default( 'mobile_height' ) ),
                    'quality'  => 100,
                    'retina'   => false
                );
                $args = apply_filters( 'soliloquy_crop_image_args', $args );
                $this->crop_images( $args, $post_id );
            }
        }
        
        // Fire a hook for addons that need to utilize the cropping feature.
        // (i.e. crops images for thumbnails if thumbnails addon active)
        do_action( 'soliloquy_saved_settings', $settings, $post_id, $post );
        
        // Finally, flush all slider caches to ensure everything is up to date.
        $this->flush_slider_caches( $post_id, $settings['config']['slug'] );

    }

    /**
     * Helper method for retrieving the slider layout for an item in the admin.
     *
     * @since 1.0.0
     *
     * @param int $id The  ID of the item to retrieve.
     * @param array $data  Array of data for the item.
     * @param string $type The type of slide to retrieve.
     * @param int $post_id The current post ID.
     * @return string The  HTML output for the slider item.
     */
    public function get_slider_item( $id, $data, $type, $post_id = 0 ) {

        switch ( $type ) {
            case 'image' :
                $item = $this->get_slider_image( $id, $data, $post_id );
                break;
            case 'video' :
                $item = $this->get_slider_video( $id, $data, $post_id );
                break;
            case 'html' :
                $item = $this->get_slider_html( $id, $data, $post_id );
                break;
        }

        return apply_filters( 'soliloquy_slide_item', $item, $id, $data, $type, $post_id );

    }

    /**
     * Helper method for retrieving the slider image layout in the admin.
     *
     * @since 1.0.0
     *
     * @param int $id The  ID of the item to retrieve.
     * @param array $data  Array of data for the item.
     * @param int $post_id The current post ID.
     * @return string The  HTML output for the slider item.
     */
    public function get_slider_image( $id, $data, $post_id = 0 ) {

        $thumbnail = wp_get_attachment_image_src( $id, 'thumbnail' ); 
        ob_start(); ?>
        <li id="<?php echo $id; ?>" class="soliloquy-slide soliloquy-image soliloquy-status-<?php echo $data['status']; ?>" data-soliloquy-slide="<?php echo $id; ?>">
            <img src="<?php echo esc_url( $thumbnail[0] ); ?>" alt="<?php esc_attr_e( $data['alt'] ); ?>" />
            <a href="#" class="soliloquy-remove-slide" title="<?php esc_attr_e( 'Remove Image Slide from Slider?', 'soliloquy' ); ?>"></a>
            <a href="#" class="soliloquy-modify-slide" title="<?php esc_attr_e( 'Modify Image Slide', 'soliloquy' ); ?>"></a>
            <?php echo $this->get_slider_image_meta( $id, $data, $post_id ); ?>
        </li>
        <?php
        return ob_get_clean();

    }

    /**
     * Helper method for retrieving the slider video layout in the admin.
     *
     * @since 1.0.0
     *
     * @param int $id The  ID of the item to retrieve.
     * @param array $data  Array of data for the item.
     * @param int $post_id The current post ID.
     * @return string The  HTML output for the slider item.
     */
    public function get_slider_video( $id, $data, $post_id = 0 ) {
        
        ob_start(); ?>
        <li id="<?php echo $id; ?>" class="soliloquy-slide soliloquy-video soliloquy-status-<?php echo $data['status']; ?>" data-soliloquy-slide="<?php echo $id; ?>">
            <span class="overlay"><?php _e( 'Video', 'soliloquy' ); ?></span>
            <div class="soliloquy-video-wrap">
                <div class="soliloquy-video-inside">
                    <div class="soliloquy-video-table">
                        <?php
                        // If thumbnail exists, display it
                        if ( isset( $data['src'] ) AND !empty( $data['src']) ) {
                            ?>
                            <img src="<?php echo esc_url( $data['src'] ); ?>" />
                            <?php
                        } else {
                            ?>
                            <h4 class="no-margin"><?php echo $data['title']; ?></h4>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <a href="#" class="soliloquy-remove-slide" title="<?php esc_attr_e( 'Remove Video Slide from Slider?', 'soliloquy' ); ?>"></a>
            <a href="#" class="soliloquy-modify-slide" title="<?php esc_attr_e( 'Modify Video Slide', 'soliloquy' ); ?>"></a>
            
            <?php echo $this->get_slider_video_meta( $id, $data, $post_id ); ?>
        </li>
        <?php
        return ob_get_clean();

    }

    /**
     * Helper method for retrieving the slider HTML layout in the admin.
     *
     * @since 1.0.0
     *
     * @param int $id The  ID of the item to retrieve.
     * @param array $data  Array of data for the item.
     * @param int $post_id The current post ID.
     * @return string The  HTML output for the slider item.
     */
    public function get_slider_html( $id, $data, $post_id = 0 ) {

        ob_start(); ?>
        <li id="<?php echo $id; ?>" class="soliloquy-slide soliloquy-html soliloquy-status-<?php echo $data['status']; ?>" data-soliloquy-slide="<?php echo $id; ?>">
            <span class="overlay"><?php _e( 'HTML', 'soliloquy' ); ?></span>
            <div class="soliloquy-html-wrap">
                <div class="soliloquy-html-inside">
                    <div class="soliloquy-html-table">
                        <h4 class="no-margin"><?php echo $data['title']; ?></h4>
                    </div>
                </div>
            </div>
                
            <a href="#" class="soliloquy-remove-slide" title="<?php esc_attr_e( 'Remove HTML Slide from Slider?', 'soliloquy' ); ?>"></a>
            <a href="#" class="soliloquy-modify-slide" title="<?php esc_attr_e( 'Modify HTML Slide', 'soliloquy' ); ?>"></a>
            
            <?php echo $this->get_slider_html_meta( $id, $data, $post_id ); ?>
        </li>
        <?php
        return ob_get_clean();

    }

    /**
     * Helper method for retrieving the slider image metadata.
     *
     * @since 1.0.0
     *
     * @param int $id      The ID of the item to retrieve.
     * @param array $data  Array of data for the item.
     * @param int $post_id The current post ID.
     * @return string      The HTML output for the slider item.
     */
    public function get_slider_image_meta( $id, $data, $post_id ) {

        ob_start();
        ?>
        <div id="soliloquy-meta-<?php echo $id; ?>" class="soliloquy-meta-container" style="display:none;">
            <div class="media-modal wp-core-ui">
                <!-- Close -->
                <a class="media-modal-close" href="#"><span class="media-modal-icon"></span></a>
                
                
                <div class="media-modal-content">
                     <div class="edit-attachment-frame mode-select hide-menu hide-router soliloquy-media-frame soliloquy-meta-wrap">
                        
                        <!-- Back / Next Buttons -->
                        <div class="edit-media-header">
                            <button class="left dashicons" data-attachment-id="">
                                <span class="screen-reader-text"><?php _e( 'Edit previous media item', 'soliloquy' ); ?></span>
                            </button>
                            <button class="right dashicons" data-attachment-id="">
                                <span class="screen-reader-text"><?php _e( 'Edit next media item', 'soliloquy' ); ?></span>
                            </button>
                        </div>
                        
                        <!-- Title -->
                        <div class="media-frame-title">
                            <h1><?php _e( 'Edit Metadata', 'soliloquy' ); ?></h1>
                        </div>
                        
                        <!-- Content -->
                        <div class="media-frame-content" id="soliloquy-meta-table-<?php echo $id; ?>">
                            <div tabindex="0" role="checkbox" class="attachment-details save-ready">
                                <!-- Left -->
                                <div class="attachment-media-view portrait">
                                    <div class="thumbnail thumbnail-image">
                                        <?php do_action( 'soliloquy_before_preview', $id, $data, $post_id ); ?> 
                                        <img class="details-image" src="<?php echo $data['src']; ?>" draggable="false" />
                                        <?php do_action( 'soliloquy_after_preview', $id, $data, $post_id ); ?> 
                                    </div>
                                </div>
                                
                                <!-- Right -->
                                <div class="attachment-info">
                                    <!-- Details -->
                                    <div class="details">
                                        <!-- Images + SEO -->
                                        <div class="filename">
                                            <strong><?php _e( 'Images and SEO', 'soliloquy' ); ?></strong>
                                            <?php _e( 'Images are a small but important part of your overall SEO strategy. In order to get the most SEO benefits from your slider, it is recommended that you fill out each applicable field with SEO friendly information about the image.', 'soliloquy' ); ?>
                                            <br /><br />
                                        </div>
                                        
                                        <!-- Title -->
                                        <div class="filename">
                                            <strong><?php _e( 'Title', 'soliloquy' ); ?></strong>
                                            <?php _e( 'Sets the image title attribute for the image.', 'soliloquy' ); ?>
                                            <br /><br />
                                        </div>
                                        
                                        <!-- Alt Text -->
                                        <div class="filename">
                                            <strong><?php _e( 'Alt Text', 'soliloquy' ); ?></strong>
                                            <?php _e( 'The image alt text is used for SEO, so make sure you complete this field!', 'soliloquy' ); ?>
                                            <br /><br />
                                        </div>
                                        
                                        <!-- Hyperlink -->
                                        <div class="filename">
                                            <strong><?php _e( 'Hyperlink', 'soliloquy' ); ?></strong>
                                            <?php _e( 'The image hyperlink field is used when you click on an image in the slider.', 'soliloquy' ); ?>
                                            <br /><br />
                                        </div>
                                        
                                        <!-- Caption -->
                                        <div class="filename">
                                            <strong><?php _e( 'Caption', 'soliloquy' ); ?></strong>
                                            <?php _e( 'Captions can take any type of HTML content, such as <code>form</code>, <code>iframe</code> and <code>h1</code> tags.', 'soliloquy' ); ?>
                                            <br /><br />
                                        </div>
                                    </div>
                                    
                                    <?php do_action( 'soliloquy_before_image_meta_table', $id, $data, $post_id ); ?>
                                    <!-- Settings -->
                                    <div class="settings">
                                        <?php do_action( 'soliloquy_before_meta_table', $id, $data, $post_id ); ?>
                                        <?php do_action( 'soliloquy_before_meta_settings', $id, $data, $post_id ); ?>
                                        
                                        <!-- Status -->
                                        <label class="setting">
                                            <span class="name"><?php _e( 'Status', 'soliloquy' ); ?></span>
                                            <select id="soliloquy-status-<?php echo $id; ?>" class="soliloquy-status" name="_soliloquy[meta_status]" size="1" data-soliloquy-meta="status">
                                                <option value="pending"<?php selected( 'active', $data['status'] ); ?>><?php _e( 'Draft', 'soliloquy' ); ?></option>
                                                <option value="active"<?php selected( 'active', $data['status'] ); ?>><?php _e( 'Published', 'soliloquy' ); ?></option>
                                            </select>
                                        </label>
                                        
                                        <!-- Title -->
                                        <label class="setting">
                                            <span class="name"><?php _e( 'Title', 'soliloquy' ); ?></span>
                                            <input id="soliloquy-title-<?php echo $id; ?>" class="soliloquy-title" type="text" name="_soliloquy[meta_title]" value="<?php echo ( ! empty( $data['title'] ) ? esc_attr( $data['title'] ) : '' ); ?>" data-soliloquy-meta="title" />
                                        </label>
                                        
                                        <!-- Alt Text -->
                                        <?php do_action( 'soliloquy_before_image_meta_alt', $id, $data, $post_id ); ?>
                                        <label class="setting">
                                            <span class="name"><?php _e( 'Alt Text', 'soliloquy' ); ?></span>
                                            <input id="soliloquy-alt-<?php echo $id; ?>" class="soliloquy-alt" type="text" name="_soliloquy[meta_alt]" value="<?php echo ( ! empty( $data['alt'] ) ? esc_attr( $data['alt'] ) : '' ); ?>" data-soliloquy-meta="alt" />
                                        </label>
                                        
                                        <!-- Link -->
                                        <?php do_action( 'soliloquy_before_image_meta_link', $id, $data, $post_id ); ?>
                                        <label class="setting">
                                            <span class="name"><?php _e( 'Hyperlink', 'soliloquy' ); ?></span>
                                            <input id="soliloquy-link-<?php echo $id; ?>" class="soliloquy-link" type="text" name="_soliloquy[meta_link]" value="<?php echo ( ! empty( $data['link'] ) ? esc_url( $data['link'] ) : '' ); ?>" data-soliloquy-meta="link" />
                                        </label>
                                        
                                        <!-- Open Link in New Tab -->
                                        <?php do_action( 'soliloquy_before_image_meta_tab', $id, $data, $post_id ); ?>
                                        <label class="setting">
                                            <span class="name"><?php _e( 'Open Link in New Tab? ', 'soliloquy' ); ?></span>
                                            <input id="soliloquy-link-tab-<?php echo $id; ?>" class="soliloquy-tab" type="checkbox" name="_soliloquy[meta_tab]" value="<?php echo ( ! empty( $data['linktab'] ) && $data['linktab'] ? 1 : 0 ); ?>" <?php checked( ( ! empty( $data['linktab'] ) && $data['linktab'] ? 1 : 0 ), 1 ); ?> data-soliloquy-meta="linktab" />
                                        </label>
                                        
                                        <!-- Caption -->
                                        <?php do_action( 'soliloquy_before_image_meta_caption', $id, $data, $post_id ); ?>
                                        <div class="setting">
                                            <span class="name"><?php _e( 'Caption', 'soliloquy' ); ?></span>
                                            <?php 
                                            $caption = ( ! empty( $data['caption'] ) ? $data['caption'] : '' );
                                            wp_editor( $caption, 'soliloquy-caption-' . $id, array( 
                                                'media_buttons' => false, 
                                                'wpautop'       => false, 
                                                'tinymce'       => false, 
                                                'textarea_name' => '_soliloquy[meta_caption]', 
                                                'quicktags' => array( 
                                                    'buttons' => 'strong,em,link,ul,ol,li,close' 
                                                ),
                                            ) ); 
                                            ?> 
                                        </div>
                                        
                                        <?php do_action( 'soliloquy_after_image_meta_settings', $id, $data, $post_id ); ?>
                                        <?php do_action( 'soliloquy_after_meta_settings', $id, $data, $post_id ); ?>
                                    </div>
                                    <!-- /.settings -->  
                                    
                                    <?php do_action( 'soliloquy_after_image_meta_table', $id, $data, $post_id ); ?>
                                    <?php do_action( 'soliloquy_after_meta_table', $id, $data, $post_id ); ?>
                                    
                                    <!-- Actions -->
                                    <div class="actions">
                                        <a href="#" class="soliloquy-meta-submit button media-button button-large button-primary media-button-insert" title="<?php esc_attr_e( 'Save Metadata', 'soliloquy' ); ?>" data-soliloquy-item="<?php echo $id; ?>"><?php _e( 'Save Metadata', 'soliloquy' ); ?></a>
                                
                                        <!-- Save Spinner -->
                                        <span class="settings-save-status">
                                            <span class="spinner"></span>
                                            <span class="saved"><?php _e( 'Saved.', 'soliloquy' ); ?></span>
                                        </span>
                                    </div>
                                    <!-- /.actions -->
                                
                                </div>
                            </div>
                        </div>
                    </div><!-- end .media-frame -->
                </div><!-- end .media-modal-content -->
            </div><!-- end .media-modal -->
            
            <div class="media-modal-backdrop"></div>
        </div>
        <?php
        return ob_get_clean();

    }

    /**
     * Helper method for retrieving the slider video metadata.
     *
     * @since 1.0.0
     *
     * @param int $id      The ID of the item to retrieve.
     * @param array $data  Array of data for the item.
     * @param int $post_id The current post ID.
     * @return string      The HTML output for the slider item.
     */
    public function get_slider_video_meta( $id, $data, $post_id ) {

        ob_start();
        ?>
        <div id="soliloquy-meta-<?php echo $id; ?>" class="soliloquy-meta-container" style="display:none;">
            <div class="media-modal wp-core-ui">
                <!-- Close -->
                <a class="media-modal-close" href="#"><span class="media-modal-icon"></span></a>
                
                <div class="media-modal-content">
                     <div class="edit-attachment-frame mode-select hide-menu hide-router soliloquy-media-frame soliloquy-meta-wrap">
                        
                        <!-- Back / Next Buttons -->
                        <div class="edit-media-header">
                            <button class="left dashicons" data-attachment-id="">
                                <span class="screen-reader-text"><?php _e( 'Edit previous media item', 'soliloquy' ); ?></span>
                            </button>
                            <button class="right dashicons" data-attachment-id="">
                                <span class="screen-reader-text"><?php _e( 'Edit next media item', 'soliloquy' ); ?></span>
                            </button>
                        </div>
                        
                        <!-- Title -->
                        <div class="media-frame-title">
                            <h1><?php _e( 'Edit Metadata', 'soliloquy' ); ?></h1>
                        </div>
                        
                        <!-- Content -->
                        <div class="media-frame-content" id="soliloquy-meta-table-<?php echo $id; ?>">
                            <div tabindex="0" role="checkbox" class="attachment-details save-ready">
                                <!-- Left -->
                                <div class="attachment-media-view portrait">
                                    <div class="thumbnail thumbnail-image">
                                        <?php do_action( 'soliloquy_before_preview', $id, $data, $post_id ); ?> 
                                        <img class="details-image src" src="<?php echo $data['src']; ?>" draggable="false" />
                                        
                                        <!-- Choose Video Placeholder Image + Remove Video Placeholder Image -->
                                        <a href="#" class="soliloquy-thumbnail button button-primary" data-field="soliloquy-src" title="<?php _e( 'Choose Video Placeholder Image', 'soliloquy' ); ?>"><?php _e( 'Choose Video Placeholder Image', 'soliloquy' ); ?></a>
                                        <a href="#" class="soliloquy-thumbnail-delete button button-secondary" data-field="soliloquy-src" title="<?php _e( 'Remove Video Placeholder Image', 'soliloquy' ); ?>"><?php _e( 'Remove Video Placeholder Image', 'soliloquy' ); ?></a>
                                        <?php do_action( 'soliloquy_after_preview', $id, $data, $post_id ); ?> 
                                    </div>
                                </div>
                                
                                <!-- Right -->
                                <div class="attachment-info">
                                    <!-- Details -->
                                    <div class="details">
                                        <!-- Title -->
                                        <div class="filename">
                                            <strong><?php _e( 'Title', 'soliloquy' ); ?></strong>
                                            <?php _e( 'The title can take any type of HTML. You can adjust the position of the titles in the main Lightbox settings.', 'soliloquy' ); ?>
                                            <br /><br />
                                        </div>
                                        
                                        <!-- URL -->
                                        <div class="filename">
                                            <strong><?php _e( 'URL', 'soliloquy' ); ?></strong>
                                            <?php _e( 'The URL of the YouTube / Vimeo video.', 'soliloquy' ); ?>
                                            <br /><br />
                                        </div>
                                        
                                        <!-- Placeholder -->
                                        <div class="filename">
                                            <strong><?php _e( 'Placeholder', 'soliloquy' ); ?></strong>
                                            <?php _e( 'Either choose a video placeholder image, or let Soliloquy automatically fetch one from the video source.', 'soliloquy' ); ?>
                                            <br /><br />
                                        </div>
                                        
                                        <!-- Caption -->
                                        <div class="filename">
                                            <strong><?php _e( 'Caption', 'soliloquy' ); ?></strong>
                                            <?php _e( 'Captions can take any type of HTML content, such as <code>form</code>, <code>iframe</code> and <code>h1</code> tags.', 'soliloquy' ); ?>
                                            <br /><br />
                                        </div>
                                    </div>
                                    
                                    <?php do_action( 'soliloquy_before_video_meta_table', $id, $data, $post_id ); ?>
                                    <!-- Settings -->
                                    <div class="settings">
                                        <?php do_action( 'soliloquy_before_meta_table', $id, $data, $post_id ); ?>
                                        <?php do_action( 'soliloquy_before_meta_settings', $id, $data, $post_id ); ?>
                                        
                                        <!-- Status -->
                                        <label class="setting">
                                            <span class="name"><?php _e( 'Status', 'soliloquy' ); ?></span>
                                            <select id="soliloquy-status-<?php echo $id; ?>" class="soliloquy-status" name="_soliloquy[meta_status]" size="1" data-soliloquy-meta="status">
                                                <option value="pending"<?php selected( 'active', $data['status'] ); ?>><?php _e( 'Draft', 'soliloquy' ); ?></option>
                                                <option value="active"<?php selected( 'active', $data['status'] ); ?>><?php _e( 'Published', 'soliloquy' ); ?></option>
                                            </select>
                                        </label>
                                        
                                        <!-- Title -->
                                        <label class="setting">
                                            <span class="name"><?php _e( 'Title', 'soliloquy' ); ?></span>
                                            <input id="soliloquy-title-<?php echo $id; ?>" class="soliloquy-title" type="text" name="_soliloquy[meta_title]" value="<?php echo ( ! empty( $data['title'] ) ? esc_attr( $data['title'] ) : '' ); ?>" data-soliloquy-meta="title" />
                                        </label>
                                        
                                        <!-- URL -->
                                        <?php do_action( 'soliloquy_before_video_meta_url', $id, $data, $post_id ); ?>
                                        <label class="setting">
                                            <span class="name"><?php _e( 'URL', 'soliloquy' ); ?></span>
                                            <input id="soliloquy-url-<?php echo $id; ?>" class="soliloquy-url" type="text" name="_soliloquy[meta_url]" value="<?php echo ( ! empty( $data['url'] ) ? esc_url( $data['url'] ) : '' ); ?>" data-soliloquy-meta="url" />
                                        </label>
                                        
                                        <!-- Placeholder -->
                                        <?php do_action( 'soliloquy_before_video_meta_thumb', $id, $data, $post_id ); ?>
                                        <label class="setting">
                                            <span class="name"><?php _e( 'Placeholder', 'soliloquy' ); ?></span>
                                            <input id="soliloquy-src-<?php echo $id; ?>" class="soliloquy-src" type="text" name="_soliloquy[meta_src]" value="<?php echo ( ! empty( $data['src'] ) ? esc_url( $data['src'] ) : '' ); ?>" data-soliloquy-meta="src" />
                                        </label>
                                        
                                        <!-- Caption -->
                                        <?php do_action( 'soliloquy_before_video_meta_caption', $id, $data, $post_id ); ?>
                                        <div class="setting">
                                            <span class="name"><?php _e( 'Caption', 'soliloquy' ); ?></span>
                                            <?php 
                                            $caption = ( ! empty( $data['caption'] ) ? $data['caption'] : '' );
                                            wp_editor( $caption, 'soliloquy-caption-' . $id, array( 
                                                'media_buttons' => false, 
                                                'wpautop'       => false, 
                                                'tinymce'       => false, 
                                                'textarea_name' => '_soliloquy[meta_caption]', 
                                                'quicktags' => array( 
                                                    'buttons' => 'strong,em,link,ul,ol,li,close' 
                                                ),
                                            ) ); 
                                            ?> 
                                        </div>
                                        
                                        <?php do_action( 'soliloquy_after_video_meta_settings', $id, $data, $post_id ); ?>
                                        <?php do_action( 'soliloquy_after_meta_settings', $id, $data, $post_id ); ?>
                                    </div>
                                    <!-- /.settings -->  
                                    
                                    <?php do_action( 'soliloquy_after_video_meta_table', $id, $data, $post_id ); ?>
                                    <?php do_action( 'soliloquy_after_meta_table', $id, $data, $post_id ); ?>
                                    
                                    <!-- Actions -->
                                    <div class="actions">
                                        <a href="#" class="soliloquy-meta-submit button media-button button-large button-primary media-button-insert" title="<?php esc_attr_e( 'Save Metadata', 'soliloquy' ); ?>" data-soliloquy-item="<?php echo $id; ?>"><?php _e( 'Save Metadata', 'soliloquy' ); ?></a>
                                
                                        <!-- Save Spinner -->
                                        <span class="settings-save-status">
                                            <span class="spinner"></span>
                                            <span class="saved"><?php _e( 'Saved.', 'soliloquy' ); ?></span>
                                        </span>
                                    </div>
                                    <!-- /.actions -->
                                
                                </div>
                            </div>
                        </div>
                    </div><!-- end .media-frame -->
                </div><!-- end .media-modal-content -->
            </div><!-- end .media-modal -->
            
            <div class="media-modal-backdrop"></div>
        </div>
        <?php
        return ob_get_clean();

    }

    /**
     * Helper method for retrieving the slider HTML metadata.
     *
     * @since 1.0.0
     *
     * @param int $id      The ID of the item to retrieve.
     * @param array $data  Array of data for the item.
     * @param int $post_id The current post ID.
     * @return string      The HTML output for the slider item.
     */
    public function get_slider_html_meta( $id, $data, $post_id ) {

        ob_start();
        ?>
        <div id="soliloquy-meta-<?php echo $id; ?>" class="soliloquy-meta-container" style="display:none;">
            <div class="media-modal wp-core-ui">
                <!-- Close -->
                <a class="media-modal-close" href="#"><span class="media-modal-icon"></span></a>
                
                <div class="media-modal-content">
                     <div class="edit-attachment-frame mode-select hide-menu hide-router soliloquy-media-frame soliloquy-meta-wrap">
                        
                        <!-- Back / Next Buttons -->
                        <div class="edit-media-header">
                            <button class="left dashicons" data-attachment-id="">
                                <span class="screen-reader-text"><?php _e( 'Edit previous media item', 'soliloquy' ); ?></span>
                            </button>
                            <button class="right dashicons" data-attachment-id="">
                                <span class="screen-reader-text"><?php _e( 'Edit next media item', 'soliloquy' ); ?></span>
                            </button>
                        </div>
                        
                        <!-- Title -->
                        <div class="media-frame-title">
                            <h1><?php _e( 'Edit Metadata', 'soliloquy' ); ?></h1>
                        </div>
                        
                        <!-- Content -->
                        <div class="media-frame-content" id="soliloquy-meta-table-<?php echo $id; ?>">
                            <div tabindex="0" role="checkbox" class="attachment-details save-ready">
                                <!-- Left -->
                                <div class="attachment-media-view portrait">
                                    <div class="thumbnail thumbnail-image">
                                        <?php do_action( 'soliloquy_before_preview', $id, $data, $post_id ); ?> 
                                        
                                        <?php echo ( ! empty( $data['code'] ) ? $data['code'] : '' ); ?>
                                        
                                        <?php do_action( 'soliloquy_after_preview', $id, $data, $post_id ); ?>  
                                    </div>
                                </div>
                                
                                <!-- Right -->
                                <div class="attachment-info">
                                    <!-- Details -->
                                    <div class="details">
                                        <!-- Title -->
                                        <div class="filename">
                                            <strong><?php _e( 'Title', 'soliloquy' ); ?></strong>
                                            <?php _e( 'The title can take any type of HTML. You can adjust the position of the titles in the main Lightbox settings.', 'soliloquy' ); ?>
                                            <br /><br />
                                        </div>
                                        
                                        <!-- Code -->
                                        <div class="filename">
                                            <strong><?php _e( 'Code', 'soliloquy' ); ?></strong>
                                            <?php _e( 'Remember, custom HTML slides are, in fact, custom. You are responsible for styling and manipulating the code you insert into the slide; with great power comes great responsibility.', 'soliloquy' ); ?>
                                            <br /><br />
                                        </div>
                                    </div>
                                    
                                    <?php do_action( 'soliloquy_before_html_meta_table', $id, $data, $post_id ); ?>
                                    <!-- Settings -->
                                    <div class="settings">
                                        <?php do_action( 'soliloquy_before_meta_table', $id, $data, $post_id ); ?>
                                        <?php do_action( 'soliloquy_before_meta_settings', $id, $data, $post_id ); ?>
                                        
                                        <!-- Status -->
                                        <label class="setting">
                                            <span class="name"><?php _e( 'Status', 'soliloquy' ); ?></span>
                                            <select id="soliloquy-status-<?php echo $id; ?>" class="soliloquy-status" name="_soliloquy[meta_status]" size="1" data-soliloquy-meta="status">
                                                <option value="pending"<?php selected( 'active', $data['status'] ); ?>><?php _e( 'Draft', 'soliloquy' ); ?></option>
                                                <option value="active"<?php selected( 'active', $data['status'] ); ?>><?php _e( 'Published', 'soliloquy' ); ?></option>
                                            </select>
                                        </label>
                                        
                                        <!-- Title -->
                                        <label class="setting">
                                            <span class="name"><?php _e( 'Title', 'soliloquy' ); ?></span>
                                            <input id="soliloquy-title-<?php echo $id; ?>" class="soliloquy-title" type="text" name="_soliloquy[meta_title]" value="<?php echo ( ! empty( $data['title'] ) ? esc_attr( $data['title'] ) : '' ); ?>" data-soliloquy-meta="title" />
                                        </label>
                                        
                                        <!-- Code -->
                                        <?php do_action( 'soliloquy_before_html_meta_code', $id, $data, $post_id ); ?>
                                        <div class="code">
                                            <span class="name"><?php _e( 'Code', 'soliloquy' ); ?></span>
                                            <textarea id="soliloquy-code-<?php echo $id; ?>" name="_soliloquy[meta_code]" class="soliloquy-html-code" data-soliloquy-meta="code"><?php echo ( ! empty( $data['code'] ) ? $data['code'] : '' ); ?></textarea>
                                        </div>
                                                 
                                        <?php do_action( 'soliloquy_after_html_meta_settings', $id, $data, $post_id ); ?>
                                        <?php do_action( 'soliloquy_after_meta_settings', $id, $data, $post_id ); ?>
                                    </div>
                                    <!-- /.settings -->  
                                    
                                    <?php do_action( 'soliloquy_after_html_meta_table', $id, $data, $post_id ); ?>
                                    <?php do_action( 'soliloquy_after_meta_table', $id, $data, $post_id ); ?>
                                    
                                    <!-- Actions -->
                                    <div class="actions">
                                        <a href="#" class="soliloquy-meta-submit button media-button button-large button-primary media-button-insert" title="<?php esc_attr_e( 'Save Metadata', 'soliloquy' ); ?>" data-soliloquy-item="<?php echo $id; ?>"><?php _e( 'Save Metadata', 'soliloquy' ); ?></a>
                                
                                        <!-- Save Spinner -->
                                        <span class="settings-save-status">
                                            <span class="spinner"></span>
                                            <span class="saved"><?php _e( 'Saved.', 'soliloquy' ); ?></span>
                                        </span>
                                    </div>
                                    <!-- /.actions -->
                                
                                </div>
                            </div>
                        </div>
                    </div><!-- end .media-frame -->
                </div><!-- end .media-modal-content -->
            </div><!-- end .media-modal -->
            
            <div class="media-modal-backdrop"></div>
        </div>
        <?php
        return ob_get_clean();

    }

    /**
     * Helper method to change a slider state from pending to active. This is done
     * automatically on post save. For previewing sliders before publishing,
     * simply click the "Preview" button and Soliloquy will load all the images present
     * in the slider at that time.
     *
     * @since 1.0.0
     *
     * @param int $id The current post ID.
     * @return array Slider
     */
    public function change_slider_states( $post_id ) {
        
        $slider_data = get_post_meta( $post_id, '_sol_slider_data', true );
        if ( ! empty( $slider_data['slider'] ) ) {
            foreach ( (array) $slider_data['slider'] as $id => $item ) {
                $slider_data['slider'][$id]['status'] = 'active';
            }
        }
        
        update_post_meta( $post_id, '_sol_slider_data', $slider_data );
        
        return $slider_data;

    }

    /**
     * Helper method to crop slider images to the specified sizes.
     *
     * @since 1.0.0
     *
     * @param array $args  Array of args used when cropping the images.
     * @param int $post_id The current post ID.
     */
    public function crop_images( $args, $post_id ) {

        // Gather all available images to crop.
        $slider_data = get_post_meta( $post_id, '_sol_slider_data', true );
        $images      = ! empty( $slider_data['slider'] ) ? $slider_data['slider'] : false;
        $common      = Soliloquy_Common::get_instance();

        // Loop through the images and crop them.
        if ( $images ) {
            // Increase the time limit to account for large image sets and suspend cache invalidations.
            set_time_limit( Soliloquy_Common::get_instance()->get_max_execution_time() );
            wp_suspend_cache_invalidation( true );

            foreach ( $images as $id => $item ) {
                // Get the full image attachment. If it does not return the data we need, skip over it.
                $image = wp_get_attachment_image_src( $id, 'full' );
                if ( ! is_array( $image ) ) {
                    // Check for video/HTML slide and possibly use a thumbnail instead.
                    if ( ( isset( $item['type'] ) && 'video' == $item['type'] || isset( $item['type'] ) && 'html' == $item['type'] ) && ! empty( $item['thumb'] ) ) {
                        $image = $item['thumb'];
                    } else {
                        continue;
                    }
                } else {
                    $image = $image[0];
                }

                // Allow image to be filtered to use a different thumbnail than the main image.
                $image = apply_filters( 'soliloquy_cropped_image', $image, $id, $item, $args, $post_id );

                // Generate the cropped image.
                $cropped_image = $common->resize_image( $image, $args['width'], $args['height'], true, $args['position'], $args['quality'], $args['retina'], $slider_data );

                // If there is an error, possibly output error message, otherwise woot!
                if ( is_wp_error( $cropped_image ) ) {
                    // If debugging is defined, print out the error.
                    if ( defined( 'SOLILOQUY_CROP_DEBUG' ) && SOLILOQUY_CROP_DEBUG ) {
                        echo '<pre>' . var_export( $cropped_image->get_error_message(), true ) . '</pre>';
                    }
                }
            }

            // Turn off cache suspension and flush the cache to remove any cache inconsistencies.
            wp_suspend_cache_invalidation( false );
            wp_cache_flush();
        }

    }

    /**
     * Helper method to flush slider caches once a slider is updated.
     *
     * @since 1.0.0
     *
     * @param int $post_id The current post ID.
     * @param string $slug The unique slider slug.
     */
    public function flush_slider_caches( $post_id, $slug ) {

        Soliloquy_Common::get_instance()->flush_slider_caches( $post_id, $slug );

    }

    /**
     * Helper method for retrieving config values.
     *
     * @since 1.0.0
     *
     * @global int $id        The current post ID.
     * @global object $post   The current post object.
     * @param string $key     The config key to retrieve.
     * @param string $default A default value to use.
     * @return string         Key value on success, empty string on failure.
     */
    public function get_config( $key, $default = false ) {

        global $id, $post;

        // Get the current post ID. If ajax, grab it from the $_POST variable.
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            $post_id = absint( $_POST['post_id'] );
        } else {
            $post_id = isset( $post->ID ) ? $post->ID : (int) $id;
        }

        $settings = get_post_meta( $post_id, '_sol_slider_data', true );
        if ( isset( $settings['config'][$key] ) ) {
            return $settings['config'][$key];
        } else {
            return $default ? $default : '';
        }

    }

    /**
     * Helper method for setting default config values.
     *
     * @since 1.0.0
     *
     * @param string $key The default config key to retrieve.
     * @return string Key value on success, false on failure.
     */
    public function get_config_default( $key ) {

        $instance = Soliloquy_Common::get_instance();
        return $instance->get_config_default( $key );

    }

    /**
     * Helper method for retrieving slide meta values.
     *
     * @since 1.0.0
     *
     * @global int $id        The current post ID.
     * @global object $post   The current post object.
     * @param string $key     The config key to retrieve.
     * @param int $attach_id  The attachment ID to target.
     * @param string $default A default value to use.
     * @return string         Key value on success, empty string on failure.
     */
    public function get_meta( $key, $attach_id, $default = false ) {

        global $id, $post;

        // Get the current post ID. If ajax, grab it from the $_POST variable.
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            $post_id = absint( $_POST['post_id'] );
        } else {
            $post_id = isset( $post->ID ) ? $post->ID : (int) $id;
        }

        $settings = get_post_meta( $post_id, '_sol_slider_data', true );
        if ( isset( $settings['slider'][$attach_id][$key] ) ) {
            return $settings['slider'][$attach_id][$key];
        } else {
            return $default ? $default : '';
        }

    }

    /**
     * Helper method for setting default meta values.
     *
     * @since 1.0.0
     *
     * @param string $key    The default meta key to retrieve.
     * @param int $attach_id The attachment ID to target.
     * @return string        Key value on success, false on failure.
     */
    public function get_meta_default( $key, $attach_id ) {

        $instance = Soliloquy_Common::get_instance();
        return $instance->get_meta_default( $key, $attach_id );

    }

    /**
     * Helper method for retrieving slider sizes.
     *
     * @since 1.0.0
     *
     * @return array Array of slider size data.
     */
    public function get_slider_sizes() {

        $instance = Soliloquy_Common::get_instance();
        return $instance->get_slider_sizes();

    }

    /**
     * Helper method for retrieving slider themes.
     *
     * @since 1.0.0
     *
     * @return array Array of slider theme data.
     */
    public function get_slider_themes() {

        $instance = Soliloquy_Common::get_instance();
        return $instance->get_slider_themes();

    }

    /**
     * Helper method for retrieving slider transitions.
     *
     * @since 1.0.0
     *
     * @return array Array of thumbnail transition data.
     */
    public function get_slider_transitions() {

        $instance = Soliloquy_Common::get_instance();
        return $instance->get_slider_transitions();

    }

    /**
     * Helper method for retrieving slider positions.
     *
     * @since 1.0.0
     *
     * @return array Array of thumbnail position data.
     */
    public function get_slider_positions() {

        $instance = Soliloquy_Common::get_instance();
        return $instance->get_slider_positions();

    }

    /**
     * Helper method for retrieving caption positions.
     *
     * @since 2.4.1.1
     *
     * @return array Array of caption position data.
     */
    public function get_caption_positions() {

        $instance = Soliloquy_Common::get_instance();
        return $instance->get_caption_positions();

    }

    /**
     * Helper method for retrieving aria-live priorities
     *
     * @since 2.4.0.9
     *
     * @return array Array of aria-live priorities
     */
    public function get_aria_live_values() {

        $instance = Soliloquy_Common::get_instance();
        return $instance->get_aria_live_values();

    }

    /**
     * Returns the post types to skip for loading Soliloquy metaboxes.
     *
     * @since 1.0.0
     *
     * @param bool $soliloquy Whether or not to include the Soliloquy post type.
     * @return array Array of skipped posttypes.
     */
    public function get_skipped_posttypes( $soliloquy = false ) {

        $post_types = get_post_types();
        if ( ! $soliloquy ) {
            unset( $post_types['soliloquy'] );
        }
        return apply_filters( 'soliloquy_skipped_posttypes', $post_types );

    }

    /**
     * Flag to determine if the GD library has been compiled.
     *
     * @since 1.0.0
     *
     * @return bool True if has proper extension, false otherwise.
     */
    public function has_gd_extension() {

        return extension_loaded( 'gd' ) && function_exists( 'gd_info' );

    }

    /**
     * Flag to determine if the Imagick library has been compiled.
     *
     * @since 1.0.0
     *
     * @return bool True if has proper extension, false otherwise.
     */
    public function has_imagick_extension() {

        return extension_loaded( 'imagick' );

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The Soliloquy_Metaboxes object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Soliloquy_Metaboxes ) ) {
            self::$instance = new Soliloquy_Metaboxes();
        }

        return self::$instance;

    }

}

// Load the metabox class.
$soliloquy_metaboxes = Soliloquy_Metaboxes::get_instance();