<?php
/**
 * Posttype admin class.
 *
 * @since 1.0.0
 *
 * @package Soliloquy
 * @author  Thomas Griffin
 */
class Soliloquy_Posttype_Admin {

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

        // Remove quick editing from the Soliloquy post type row actions.
        add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );

        // Manage post type columns.
        add_filter( 'manage_edit-soliloquy_columns', array( $this, 'soliloquy_columns' ) );
        add_filter( 'manage_soliloquy_posts_custom_column', array( $this, 'soliloquy_custom_columns' ), 10, 2 );

        // Update post type messages.
        add_filter( 'post_updated_messages', array( $this, 'messages' ) );

        // Force the menu icon to be scaled to proper size (for Retina displays).
        add_action( 'admin_head', array( $this, 'menu_icon' ) );

        // Check if any soliloquyv2 post types still exist, and if so migrate them once
        add_action( 'init', array( $this, 'maybe_fix_soliloquyv2_cpts' ) );

    }

    /**
     * Customize the post columns for the Soliloquy post type.
     *
     * @since 1.0.0
     *
     * @param array $columns  The default columns.
     * @return array $columns Amended columns.
     */
    public function soliloquy_columns( $columns ) {

        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'title'     => __( 'Title', 'soliloquy' ),
            'shortcode' => __( 'Shortcode', 'soliloquy' ),
            'template'  => __( 'Template Tag', 'soliloquy' ),
            'images'    => __( 'Number of Images', 'soliloquy' ),
            'modified'  => __( 'Last Modified', 'soliloquy' ),
            'date'      => __( 'Date', 'soliloquy' )
        );

        return $columns;

    }

    /**
     * Add data to the custom columns added to the Soliloquy post type.
     *
     * @since 1.0.0
     *
     * @global object $post  The current post object.
     * @param string $column The name of the custom column.
     * @param int $post_id   The current post ID.
     */
    public function soliloquy_custom_columns( $column, $post_id ) {

        global $post;
        $post_id = absint( $post_id );

        switch ( $column ) {
            case 'shortcode' :
                echo '<code>[soliloquy id="' . $post_id . '"]</code>';
                break;

            case 'template' :
                echo '<code>if ( function_exists( \'soliloquy\' ) ) { soliloquy( \'' . $post_id . '\' ); }</code>';
                break;

            case 'images' :
                $slider_data = get_post_meta( $post_id, '_sol_slider_data', true );
                echo ( ! empty( $slider_data['slider'] ) ? count( $slider_data['slider'] ) : 0 );
                break;

            case 'modified' :
                the_modified_date();
                break;
        }

    }

    /**
     * Filter out unnecessary row actions from the Soliloquy post table.
     *
     * @since 1.0.0
     *
     * @param array $actions  Default row actions.
     * @param object $post    The current post object.
     * @return array $actions Amended row actions.
     */
    public function row_actions( $actions, $post ) {

        if ( isset( get_current_screen()->post_type ) && 'soliloquy' == get_current_screen()->post_type ) {
            unset( $actions['inline hide-if-no-js'] );
        }

        return apply_filters( 'soliloquy_row_actions', $actions, $post );

    }

    /**
     * Contextualizes the post updated messages.
     *
     * @since 1.0.0
     *
     * @global object $post    The current post object.
     * @param array $messages  Array of default post updated messages.
     * @return array $messages Amended array of post updated messages.
     */
    public function messages( $messages ) {

        global $post;

        // Contextualize the messages.
        $messagesArr = array(
            0  => '',
            1  => __( 'Soliloquy slider updated.', 'soliloquy' ),
            2  => __( 'Soliloquy slider custom field updated.', 'soliloquy' ),
            3  => __( 'Soliloquy slider custom field deleted.', 'soliloquy' ),
            4  => __( 'Soliloquy slider updated.', 'soliloquy' ),
            5  => isset( $_GET['revision'] ) ? sprintf( __( 'Soliloquy slider restored to revision from %s.', 'soliloquy' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6  => __( 'Soliloquy slider published.', 'soliloquy' ),
            7  => __( 'Soliloquy slider saved.', 'soliloquy' ),
            8  => __( 'Soliloquy slider submitted.', 'soliloquy' ),
            9  => sprintf( __( 'Soliloquy slider scheduled for: <strong>%1$s</strong>.', 'soliloquy' ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
            10 => __( 'Soliloquy slider draft updated.', 'soliloquy' )
        );
        $messages['soliloquy'] = apply_filters( 'soliloquy_messages', $messagesArr);

        return $messages;

    }

    /**
     * Forces the Soliloquy menu icon width/height for Retina devices.
     *
     * @since 1.0.0
     */
    public function menu_icon() {

        ?>
        <style type="text/css">#menu-posts-soliloquy .wp-menu-image img { width: 16px; height: 16px; }</style>
        <?php

    }

    /**
     * Maybe fixes a v1 to v2 upgrade where the sliders end up with the soliloquyv2
     * post type, when it should be the soliloquy CPT.
     *
     * Once run, sets an option in wp_options so we don't run this every time.
     *
     * @since 2.4.1
     */
    public function maybe_fix_soliloquyv2_cpts() {

        global $fixedSliders;

        // Check if this routine has already run
        $soliloquy_upgrade_cpts = get_option( 'soliloquy_upgrade_cpts' );
        if ( $soliloquy_upgrade_cpts ) {
            return;
        }

        // Retrieve any soliloquyv2 sliders and convert the post type back to the proper CPT.
        $v2_sliders = get_posts(
            array(
                'post_type'      => 'soliloquyv2',
                'posts_per_page' => -1,
            )
        );

        // If no soliloquyv2 CPT posts exist, bail
        if ( count( $v2_sliders ) == 0 ) {
            update_option( 'soliloquy_upgrade_cpts', true );
            return;
        }

        // Loop through the sliders, grab the data, delete and backwards convert them back to 'soliloquy' post type.
        $fixedSliders = 0;
        foreach ( (array) $v2_sliders as $slider ) {
            // Grab any slider meta and add the attachment ID to the data array.
            $slider_meta = get_post_meta( $slider->ID, '_sol_slider_data', true );
            if ( ! empty( $slider_meta['slider'] ) ) {
                foreach ( $slider_meta['slider'] as $id => $data ) {
                    $slider_meta['slider'][$id]['id'] = $id;
                }
            }

            update_post_meta( $slider->ID, '_sol_slider_data', $slider_meta );

            $data = array(
                'ID'        => $slider->ID,
                'post_type' => 'soliloquy'
            );
            wp_update_post( $data );

            // Increment count for notice
            $fixedSliders++;
        }
        
        // Make sure this doesn't run again
        update_option( 'soliloquy_upgrade_cpts', true );

        // Output an admin notice so the user knows what happened
        if ( $fixedSliders > 0 ) {
            add_action( 'admin_notices', array( $this, 'fixed_soliloquyv2_cpts' ) );
        }

    }

    /**
     * Outputs a WordPress style notification to tell the user how many sliders were
     * fixed after running the soliloquyv2 --> soliloquy CPT migration automatically
     *
     * @since 2.4.1
     */
    public function fixed_soliloquyv2_cpts() {
        global $fixedSliders;
        
        ?>
        <div class="updated">
            <p><strong><?php echo $fixedSliders . __( ' slider(s) fixed successfully. This is a one time operation, and you don\'t need to do anything else.', 'soliloquy' ); ?></strong></p>
        </div>
        <?php
            
    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The Soliloquy_Posttype_Admin object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Soliloquy_Posttype_Admin ) ) {
            self::$instance = new Soliloquy_Posttype_Admin();
        }

        return self::$instance;

    }

}

// Load the posttype admin class.
$soliloquy_posttype_admin = Soliloquy_Posttype_Admin::get_instance();