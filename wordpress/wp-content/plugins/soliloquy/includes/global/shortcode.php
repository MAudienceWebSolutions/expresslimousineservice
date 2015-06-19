<?php
/**
 * Shortcode class.
 *
 * @since 1.0.0
 *
 * @package Soliloquy
 * @author  Thomas Griffin
 */
class Soliloquy_Shortcode {

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
     * Holds the slider data.
     *
     * @since 1.0.0
     *
     * @var array
     */
    public $data;

    /**
     * Holds slider IDs for init firing checks.
     *
     * @since 1.0.0
     *
     * @var array
     */
    public $done = array();

    /**
     * Iterator for sliders on the page.
     *
     * @since 1.0.0
     *
     * @var int
     */
    public $counter = 1;

    /**
     * Flag for YouTube videos.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public $youtube = false;

    /**
     * Flag for Vimeo videos.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public $vimeo = false;

    /**
     * Flag for Wistia videos.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public $wistia = false;

    /**
     * Flag for Local hosted videos.
     *
     * @since 2.4.1.4
     *
     * @var bool
     */
    public $local = false;

    /**
     * Flag for HTML slides.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public $html = false;

    /**
     * Holds image URLs for indexing.
     *
     * @since 1.0.0
     *
     * @var array
     */
    public $index = array();

    /**
     * Holds all of the stylesheets for Soliloquy.
     *
     * @since 1.0.0
     *
     * @var array
     */
    public $stylesheets = array();

    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Load the base class object.
        $this->base = Soliloquy::get_instance();

        // Register the main slider style.
        $this->stylesheets[] = array(
            'id'    => 'soliloquy-style-css',
            'href'  => esc_url( add_query_arg( 'ver', $this->base->version, plugins_url( 'assets/css/soliloquy.css', $this->base->file ) ) ),
        );
         
        // Register main slider script.
        wp_register_script( $this->base->plugin_slug . '-script', plugins_url( 'assets/js/min/soliloquy-min.js', $this->base->file ), array( 'jquery' ), $this->base->version, true );
        
        // Load hooks and filters.
        add_shortcode( 'soliloquy', array( $this, 'shortcode' ) );
        add_filter( 'widget_text', 'do_shortcode' );

    }

    /**
     * Creates the shortcode for the plugin.
     *
     * @since 1.0.0
     *
     * @global object $post The current post object.
     *
     * @param array $atts Array of shortcode attributes.
     * @return string     The slider output.
     */
    public function shortcode( $atts ) {

        global $post;

        // If no attributes have been passed, the slider should be pulled from the current post.
        $slider_id = false;
        if ( empty( $atts ) ) {
            $slider_id = $post->ID;
            $data      = is_preview() ? $this->base->_get_slider( $slider_id ) : $this->base->get_slider( $slider_id );
        } else if ( isset( $atts['id'] ) ) {
            $slider_id = (int) $atts['id'];
            $data      = is_preview() ? $this->base->_get_slider( $slider_id ) : $this->base->get_slider( $slider_id );
        } else if ( isset( $atts['slug'] ) ) {
            $slider_id = $atts['slug'];
            $data      = is_preview() ? $this->base->_get_slider_by_slug( $slider_id ) : $this->base->get_slider_by_slug( $slider_id );
        } else {
            // A custom attribute must have been passed. Allow it to be filtered to grab data from a custom source.
            $data = apply_filters( 'soliloquy_custom_slider_data', false, $atts, $post );
        }

        // If there is no data and the attribute used is an ID, try slug as well.
        if ( ! $data && isset( $atts['id'] ) ) {
            $slider_id = $atts['id'];
            $data      = is_preview() ? $this->base->_get_slider_by_slug( $slider_id ) : $this->base->get_slider_by_slug( $slider_id );
        }

        // Allow the data to be filtered before it is stored and used to create the slider output.
        $data = apply_filters( 'soliloquy_pre_data', $data, $slider_id );
        
        // If there is no data to output or the slider is inactive, do nothing.
        if ( ! $data || empty( $data['slider'] ) || isset( $data['status'] ) && 'inactive' == $data['status'] && ! is_preview() ) {
            return false;
        }

        // If the data is to be randomized, do it now.
        if ( $this->get_config( 'random', $data ) ) {
            $data = $this->shuffle( $data );
        }

        // Prepare variables.
        $this->data[$data['id']]  = $data;
        $this->index[$data['id']] = array();
        $slider                   = '';
        $i                        = 1;

        // If this is a feed view, customize the output and return early.
        if ( is_feed() ) {
            return $this->do_feed_output( $data );
        }

        // Load scripts
        wp_localize_script(
            $this->base->plugin_slug . '-script',
            'soliloquy_ajax',
            array(
                'ajax'           => admin_url( 'admin-ajax.php' ),
                'ajax_nonce'     => wp_create_nonce( 'soliloquy-ajax-nonce' ),
            )
        );
        wp_enqueue_script( $this->base->plugin_slug . '-script' );

        // Load custom slider themes if necessary.
        if ( 'base' !== $this->get_config( 'slider_theme', $data ) ) {
            $this->load_slider_theme( $this->get_config( 'slider_theme', $data ) );
        }
        
        // Filter CSS
        $this->stylesheets = apply_filters( 'soliloquy_css', $this->stylesheets, $data );
        
        // Load CSS and JS
        // For AJAX requests, we do this in a different way to get things working
        if ( $this->is_ajax_request() ) {
            add_filter( 'soliloquy_output', array( $this, 'slider_init_ajax' ), 1000, 2 );
        } else {
            $this->get_stylesheets( $data );
            add_action( 'wp_footer', array( $this, 'slider_init' ), 1000 );  
        }
        

        // Run a hook before the slider output begins but after scripts and inits have been set.
        do_action( 'soliloquy_before_output', $data );

        // Container for all of this slider
        $slider = '<div class="' . $this->get_slider_container_classes( $data ) . '" data-soliloquy-loaded="0">';

        // Apply a filter before starting the slider HTML.
        $slider = apply_filters( 'soliloquy_output_start', $slider, $data );

        // If mobile is set, add the filter to add in a mobile src attribute.
        if ( $this->get_config( 'mobile', $data ) ) {
            add_filter( 'soliloquy_output_image_attr', array( $this, 'mobile_image' ), 999, 4 );
        }

        // If positioning is set, add the filter to add the custom positioning style.
        if ( $this->get_config( 'position', $data ) ) {
            add_filter( 'soliloquy_output_container_style', array( $this, 'position_slider' ), 999, 2 );
        }

        // If using the full width option, run a special setting on the width/height.
        if ( 'full_width' == $this->get_config( 'slider_size', $data ) ) {
            add_filter( 'soliloquy_output_container_style', array( $this, 'full_width' ), 999, 2 );
        }

        // Build out the slider HTML.
        $slider .= '<div aria-live="' . $this->get_config( 'aria_live', $data ) . '" id="soliloquy-container-' . sanitize_html_class( $data['id'] ) . '" class="' . $this->get_slider_classes( $data ) . '" style="max-width:' . $this->get_config( 'slider_width', $data ) . 'px;max-height:' . $this->get_config( 'slider_height', $data ) . 'px;' . apply_filters( 'soliloquy_output_container_style', '', $data ) . '"' . apply_filters( 'soliloquy_output_container_attr', '', $data ) . '>';
            $slider .= '<ul id="soliloquy-' . sanitize_html_class( $data['id'] ) . '" class="soliloquy-slider soliloquy-slides soliloquy-wrap soliloquy-clear">';
                $slider = apply_filters( 'soliloquy_output_before_container', $slider, $data );

                foreach ( (array) $data['slider'] as $id => $item ) {
                    // Skip over images that are pending (ignore if in Preview mode).
                    if ( isset( $item['status'] ) && 'pending' == $item['status'] && ! is_preview() ) {
                        continue;
                    }

                    // Allow filtering of individual items.
                    $item     = apply_filters( 'soliloquy_output_item_data', $item, $id, $data, $i );

                    $slider   = apply_filters( 'soliloquy_output_before_item', $slider, $id, $item, $data, $i );
                    $output   = '<li aria-hidden="true" class="' . $this->get_slider_item_classes( $item, $i, $data ) . '"' . apply_filters( 'soliloquy_output_item_attr', '', $id, $item, $data, $i ) . ' draggable="false" style="' . apply_filters( 'soliloquy_output_item_style', 'list-style:none;', $id, $item, $data, $i ) . '">';
                        $output .= $this->get_slide( $id, $item, $data, $i );
                    $output .= '</li>';
                    $output  = apply_filters( 'soliloquy_output_single_item', $output, $id, $item, $data, $i );
                    $slider .= $output;
                    $slider  = apply_filters( 'soliloquy_output_after_item', $slider, $id, $item, $data, $i );

                    // Increment the iterator.
                    $i++;
                }

                $slider = apply_filters( 'soliloquy_output_after_container', $slider, $data );
            $slider .= '</ul>';
            $slider  = apply_filters( 'soliloquy_output_end', $slider, $data );
        $slider .= '</div>';

        // Increment the counter.
        $this->counter++;

        // Remove any contextual filters so they don't affect other sliders on the page.
        if ( $this->get_config( 'mobile', $data ) ) {
            remove_filter( 'soliloquy_output_image_attr', array( $this, 'mobile_image' ), 999, 4 );
        }

        if ( $this->get_config( 'position', $data ) ) {
            remove_filter( 'soliloquy_output_container_style', array( $this, 'position_slider' ), 999, 2 );
        }

        if ( 'full_width' == $this->get_config( 'slider_size', $data ) ) {
            remove_filter( 'soliloquy_output_container_style', array( $this, 'full_width' ), 999, 2 );
        }

        // Add no JS fallback support.
        $no_js_css = '<style type="text/css">#soliloquy-container-' . sanitize_html_class( $data['id'] ) . '{opacity:1}#soliloquy-container-' . sanitize_html_class( $data['id'] ) . ' li > .soliloquy-caption{display:none}#soliloquy-container-' . sanitize_html_class( $data['id'] ) . ' li:first-child > .soliloquy-caption{display:block}</style>';
        $no_js   = '<noscript>';
        $no_js  .= apply_filters( 'soliloquy_output_no_js', $no_js_css, $data );

        $index   = $this->get_indexable_images( $data['id'] );

        $no_js  .= '<div class="soliloquy-no-js" style="display:none;visibility:hidden;height:0;line-height:0;opacity:0;">' . $index . '</div>';
        $no_js  .= '</noscript>';
        $slider .= $no_js;

        // Filter slider output
        $slider = apply_filters( 'soliloquy_output', $slider, $data );

        // Close outer container
        $slider .= '</div>';

        return $slider;

    }

    /**
     * Retrieves an individual slide for the slider.
     *
     * @since 1.0.0
     *
     * @param int|string $id The ID for the slide.
     * @param array $item    Array of data for the slide.
     * @param array $data    Array of data for the slider.
     * @param int $i         The number of the slide in the slider.
     * @return string        HTML markup for the slide.
     */
    public function get_slide( $id, $item, $data, $i ) {

        $type = ! empty( $item['type'] ) ? $item['type'] : 'image';
        switch ( $type ) {
            case 'image' :
                $slide = $this->get_image_slide( $id, $item, $data, $i );
                break;
            case 'video' :
                $slide = $this->get_video_slide( $id, $item, $data, $i );
                break;
            case 'html' :
                $slide = $this->get_html_slide( $id, $item, $data, $i );
                break;
        }

        return apply_filters( 'soliloquy_output_slide', $slide, $id, $item, $data, $i );

    }

    /**
     * Retrieves an individual image slide for the slider.
     *
     * @since 1.0.0
     *
     * @param int|string $id The ID for the slide.
     * @param array $item    Array of data for the slide.
     * @param array $data    Array of data for the slider.
     * @param int $i         The number of the slide in the slider (starts at 1)
     * @return string        HTML markup for the image slide.
     */
    public function get_image_slide( $id, $item, $data, $i ) {

        // Grab our image src and prepare our output.
        $imagesrc = $this->get_image_src( $id, $item, $data );
        $output   = '';
        
        // If our image is linked, link it.
        if ( ! empty( $item['link'] ) ) {
            $output  = apply_filters( 'soliloquy_output_before_link', $output, $id, $item, $data, $i );
            if ( ! empty( $item['linktab'] ) && $item['linktab'] ) {
                $output .= '<a href="' . esc_url( $item['link'] ) . '" class="soliloquy-link" title="' . esc_attr( $item['title'] ) . '" target="_blank"' . apply_filters( 'soliloquy_output_link_attr', '', $id, $item, $data, $i ) . '>';
            } else {
                $output .= '<a href="' . esc_url( $item['link'] ) . '" class="soliloquy-link" title="' . esc_attr( $item['title'] ) . '"' . apply_filters( 'soliloquy_output_link_attr', '', $id, $item, $data, $i ) . '>';
            }
        }

        if ( ! empty( $imagesrc ) ) {
            $disable_preloading = apply_filters( 'soliloquy_disable_preloading', false, $data );
            $output  = apply_filters( 'soliloquy_output_before_image', $output, $id, $item, $data, $i );
            if ( 1 === $i && ! $this->is_mobile() || $disable_preloading === true ) {
                $output .= '<img id="soliloquy-image-' . sanitize_html_class( $id ) . '" class="' . $this->get_slider_item_image_classes( $item, $i, $data ) . '" src="' . esc_url( $imagesrc ) . '"' . ( $this->get_config( 'dimensions', $data ) ? ' width="' . $this->get_config( 'slider_width', $data ) . '" height="' . $this->get_config( 'slider_height', $data ) . '"' : '' ) . ' alt="' . esc_attr( $item['alt'] ) . '"' . apply_filters( 'soliloquy_output_image_attr', '', $id, $item, $data, $i ) . ' />';
            } else {
                $output .= '<img id="soliloquy-image-' . sanitize_html_class( $id ) . '" class="' . $this->get_slider_item_image_classes( $item, $i, $data, true ) . '" src="' . esc_url( plugins_url( 'assets/css/images/holder.gif', dirname( dirname( __FILE__ ) ) ) ) . '"' . ( $this->get_config( 'dimensions', $data ) ? ' width="' . $this->get_config( 'slider_width', $data ) . '" height="' . $this->get_config( 'slider_height', $data ) . '"' : '' ) . ' data-soliloquy-src="' . esc_url( $imagesrc ) . '" alt="' . esc_attr( $item['alt'] ) . '"' . apply_filters( 'soliloquy_output_image_attr', '', $id, $item, $data, $i ) . ' />';
            }
            $output  = apply_filters( 'soliloquy_output_after_image', $output, $id, $item, $data, $i );
        }

        // If our image is linked, close the link.
        if ( ! empty( $item['link'] ) ) {
            $output .= '</a>';
        }

        $output = apply_filters( 'soliloquy_output_after_link', $output, $id, $item, $data, $i );

        // If we have a caption, output the caption.
        if ( ! empty( $item['caption'] ) ) {
            $output  = apply_filters( 'soliloquy_output_before_caption', $output, $id, $item, $data, $i );
            $output .= '<div class="soliloquy-caption soliloquy-caption-' . $this->get_config( 'caption_position', $data ) . ( $this->get_config( 'mobile_caption', $data ) ? ' soliloquy-caption-mobile' : '') . '"><div class="soliloquy-caption-inside">';
                $caption = apply_filters( 'soliloquy_output_caption', $item['caption'], $id, $item, $data, $i );
                $output .= do_shortcode( $caption );
            $output .= '</div></div>';
            $output  = apply_filters( 'soliloquy_output_after_caption', $output, $id, $item, $data, $i );
        }

        // Return our inner image slide HTML.
        return apply_filters( 'soliloquy_output_image_slide', $output, $id, $item, $data, $i );

    }

    /**
     * Retrieves an individual video slide for the slider.
     *
     * @since 1.0.0
     *
     * @param int|string $id The ID for the slide.
     * @param array $item    Array of data for the slide.
     * @param array $data    Array of data for the slider.
     * @param int $i         The number of the slide in the slider.
     * @return string        HTML markup for the video slide.
     */
    public function get_video_slide( $id, $item, $data, $i ) {

        // Grab our image src, video type and video ID.
        // $type = ( $this->is_mobile() ? 'mobile' : 'slider' );
        $imagesrc = $this->get_image_src( $id, $item, $data );
        $vid_type = $this->get_video_data( $id, $item, $data, 'type' );
        $vid_id   = $this->get_video_data( $id, $item, $data );
        $output   = '';
        
        // If there is an error retrieving the video type or ID, return with an error message.
        if ( ! $vid_type || ! $vid_id ) {
            $error_message = __( '<p><strong>Oops - it looks like we had trouble retrieving data about the video you requested. Please make sure your video URL is supported and in the proper format.</strong></p>', 'soliloquy' );
            return apply_filters( 'soliloquy_output_video_error', $error_message, $id, $item, $data, $i );
        }

        // We need to link our video slides to process click handlers to play videos.
        $output  = apply_filters( 'soliloquy_output_before_link', $output, $id, $item, $data, $i );
        $output .= '<a href="#" class="soliloquy-video-link" title="' . esc_attr( $item['title'] ) . '"' . apply_filters( 'soliloquy_output_link_attr', '', $id, $item, $data, $i ) . '>';

            $output  = apply_filters( 'soliloquy_output_before_video', $output, $id, $item, $data, $i );
            switch ( $vid_type ) {

                case 'local':

                    // Get MIME type
                    $content_type = 'video/mp4'; // Works with most video types
                    $mime_type_parts = explode( '.', $item['url'] );
                    if ( is_array( $mime_type_parts ) && count( $mime_type_parts ) > 0 ) {
                    $ext = $mime_type_parts[ count( $mime_type_parts ) - 1 ];              
                        switch ( $ext ) {
                            case 'mp4':
                                $content_type = 'video/mp4';
                                break;
                            case 'ogv':
                                $content_type = 'video/ogg';
                                break;
                            case 'ogg':
                                $content_type = 'application/ogg';
                                break;
                            case 'webm':
                                $content_type = 'video/webm';
                                break;
                        }
                    }

                    $output .= '<video id="' . sanitize_html_class( $id ) . '-holder" width="100%" height="100%" cover="' . esc_url( $imagesrc ) . '" preload="metadata"><source type="' . $content_type . '" src="' . $item['url'] . '" /></video>';
                    $output .= '<span class="soliloquy-video-icon soliloquy-' . $vid_type . '-video" data-soliloquy-video-type="' . $vid_type . '" data-soliloquy-video-id="' . $id . '" data-soliloquy-video-holder="' . sanitize_html_class( $id ) . '"></span>';
                    break;

                default:
                    if ( 1 === $i && ! $this->is_mobile() ) {
                        $output .= '<img id="soliloquy-video-' . sanitize_html_class( $id ) . '" class="' . $this->get_slider_item_video_classes( $item, $i, $data, $vid_type ) . '" src="' . esc_url( $imagesrc ) . '"' . ( $this->get_config( 'dimensions', $data ) ? ' width="' . $this->get_config( 'slider_width', $data ) . '" height="' . $this->get_config( 'slider_height', $data ) . '"' : '' ) . ' alt="' . esc_attr( $item['title'] ) . '"' . apply_filters( 'soliloquy_output_image_attr', '', $id, $item, $data, $i ) . ' />';
                    } else {
                        $output .= '<img id="soliloquy-video-' . sanitize_html_class( $id ) . '" class="' . $this->get_slider_item_video_classes( $item, $i, $data, $vid_type, true ) . '" src="' . esc_url( plugins_url( 'assets/css/images/holder.gif', dirname( dirname( __FILE__ ) ) ) ) . '"' . ( $this->get_config( 'dimensions', $data ) ? ' width="' . $this->get_config( 'slider_width', $data ) . '" height="' . $this->get_config( 'slider_height', $data ) . '"' : '' ) . ' data-soliloquy-src="' . esc_url( $imagesrc ) . '" alt="' . esc_attr( $item['title'] ) . '"' . apply_filters( 'soliloquy_output_image_attr', '', $id, $item, $data, $i ) . ' />';
                    }
                    $output .= '<span class="soliloquy-video-icon soliloquy-' . $vid_type . '-video" data-soliloquy-video-type="' . $vid_type . '" data-soliloquy-video-id="' . $vid_id . '" data-soliloquy-video-holder="' . sanitize_html_class( $id ) . '"></span>';
                    $output .= '<div id="' . sanitize_html_class( $id ) . '-holder" class="soliloquy-video-holder" data-soliloquy-slider-id="' . $data['id'] . '"></div>';
                    break;
            }

            $output  = apply_filters( 'soliloquy_output_after_video', $output, $id, $item, $data, $i );

        // Close our video link.
        $output .= '</a>';

        $output = apply_filters( 'soliloquy_output_after_link', $output, $id, $item, $data, $i );

        // If we have a caption, output the caption.
        if ( ! empty( $item['caption'] ) ) {
            $output  = apply_filters( 'soliloquy_output_before_caption', $output, $id, $item, $data, $i );
            $output .= '<div class="soliloquy-caption soliloquy-caption-' . $this->get_config( 'caption_position', $data ) . ( $this->get_config( 'mobile_caption', $data ) ? ' soliloquy-caption-mobile' : '') . '"><div class="soliloquy-caption-inside">';
                $caption = apply_filters( 'soliloquy_output_caption', $item['caption'], $id, $item, $data, $i );
                $output .= do_shortcode( $caption );
            $output .= '</div></div>';
            $output  = apply_filters( 'soliloquy_output_after_caption', $output, $id, $item, $data, $i );
        }

        // Return our inner image slide HTML.
        return apply_filters( 'soliloquy_output_video_slide', $output, $id, $item, $data, $i );

    }

    /**
     * Retrieves data about a video slide based on the video URL.
     *
     * @since 1.0.0
     *
     * @param int|string $id The ID for the slide.
     * @param array $item    Array of data for the slide.
     * @param array $data    Array of slider data.
     * @param string $key    The type of data to retrieve ('id' by default).
     * @return bool|string   False if unsuccessful, otherwise the data requested about the video.
     */
    public function get_video_data( $id, $item, $data, $key = 'id' ) {
        
        // If no video URL is set, return false.
        if ( empty( $item['url'] ) ) {
            return false;
        }

        $instance = Soliloquy_Common::get_instance();

        // Use regex to grab data about the video from the URL provided.
        $source = '';
        if ( preg_match( '#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#', $item['url'], $y_matches ) ) {
            // Set source, flag and enqueue our script.
            $source                             = 'youtube';
            $this->youtube                      = true;
            $this->data[$data['id']]['youtube'] = true;
            wp_enqueue_script( $this->base->plugin_slug . '-' . $source, 'https://www.youtube.com/iframe_api', array(), $this->base->version, true );
        } else if ( preg_match( '#(?:https?:\/\/(?:[\w]+\.)*vimeo\.com(?:[\/\w]*\/videos?)?\/([0-9]+)[^\s]*)#i', $item['url'], $v_matches ) ) {
            $source                           = 'vimeo';
            $this->vimeo                      = true;
            $this->data[$data['id']]['vimeo'] = true;
            
            wp_enqueue_script( $this->base->plugin_slug . '-' . $source, '//secure-a.vimeocdn.com/js/froogaloop2.min.js', array(), $this->base->version, true );
        } else if ( preg_match( '/https?:\/\/(.+)?(wistia.com|wi.st)\/.*/i', $item['url'], $w_matches ) ) {
            $source                            = 'wistia';
            $this->wistia                      = true;
            $this->data[$data['id']]['wistia'] = true;
            wp_enqueue_script( $this->base->plugin_slug . '-' . $source, '//fast.wistia.net/static/embed_shepherd-v1.js', array(), $this->base->version, true );
        } elseif ( preg_match( '/(' . $instance->get_self_hosted_supported_filetypes_string() . ')/', $item['url'], $l_matches ) ) {
            // Self hosted
            $source                            = 'local';
            $this->local                       = true;
            $this->data[ $data['id'] ]['local']= true;

            // Enqueue WP mediaplayerelement
            wp_enqueue_script( 'wp-mediaelement' );
            wp_enqueue_style( 'wp-mediaelement' );
        } else {
            $source = apply_filters( 'soliloquy_video_source', '', $id, $item, $data );
        }

        // If no source can be found, we can't find the ID either, so return false.
        if ( empty( $source ) ) {
            return false;
        }
        
        // Return the requested data.
        switch ( $key ) {
            case 'id' :
                if ( 'youtube' == $source ) {
                    $ret = $y_matches[0];
                    
                    // Strip ? param if it still exists
                    // This fixes buggy URLS e.g. mAeaUPejVgU?list=UUSZ7JbZ882uAgM6BcJjgmdQ
                    if ( strpos ($ret, '?') !== false ) {
                        $url_parts = explode( '?', $ret );
                        $ret = $url_parts[0];
                    }
                    
                } else if ( 'vimeo' == $source ) {
                    $ret = $v_matches[1];
                } else if ( 'wistia' == $source ) {
                    $parts = explode( '/', $w_matches[0] );
                    $ret   = array_pop( $parts );
                } else if ( 'local' == $source ) {
                    $ret   = $item['url'];
                } else {
                    $ret = apply_filters( 'soliloquy_video_id', false, $source, $id, $item, $data );
                }
                break;
            case 'type' :
                $ret = $source;
                break;
            case 'url' :
                if ( 'youtube' == $source ) {
                    $ret = esc_url( add_query_arg( $this->get_youtube_args( $data ), '//youtube.com/embed/' . $y_matches[0] ) );
                } else if ( 'vimeo' == $source ) {
                    $ret = esc_url( add_query_arg( $this->get_vimeo_args( $data ), '//player.vimeo.com/video/' . $v_matches[1] ) );
                } else if ( 'wistia' == $source ) {
                    $parts = explode( '/', $w_matches[0] );
                    $id    = array_pop( $parts );
                    $ret   = esc_url( add_query_arg( $this->get_wistia_args( $data ), '//fast.wistia.net/embed/iframe/' . $id ) );
                } else if ( 'local' == $source ) {
                    $ret   = $item['url'];
                } else {
                    $ret = apply_filters( 'soliloquy_video_url', false, $source, $id, $item, $data );
                }
                break;
            default :
                $ret = apply_filters( 'soliloquy_video_data', false, $source, $id, $item, $data );
                break;
        }
        
        return apply_filters( 'soliloquy_video_data', $ret, $id, $item, $data, $key );

    }

    /**
     * Retrieves an individual HTML slide for the slider.
     *
     * @since 1.0.0
     *
     * @param int|string $id The ID for the slide.
     * @param array $item    Array of data for the slide.
     * @param array $data    Array of data for the slider.
     * @param int $i         The number of the slide in the slider.
     * @return string        HTML markup for the HTML slide.
     */
    public function get_html_slide( $id, $item, $data, $i ) {

        // Set the HTML flag to true.
        $this->html = true;

        $output  = '<div class="soliloquy-html-sentinel soliloquy-clear" style="position:static;display:block;visibility:hidden;z-index:-10000;top:0;left:0;width:100%;height:' . $this->get_config( 'slider_height', $data ) . 'px;">';
            $output .= '<div class="soliloquy-html-content soliloquy-clear">';
                $output  = apply_filters( 'soliloquy_output_before_html', $output, $id, $item, $data, $i );
                $output .= ! empty( $item['code'] ) ? do_shortcode( $item['code'] ) : '';
                $output  = apply_filters( 'soliloquy_output_after_html', $output, $id, $item, $data, $i );
            $output .= '</div>';
        $output .= '</div>';

        return apply_filters( 'soliloquy_output_html_slide', $output, $id, $item, $data, $i );

    }

    /**
     * Outputs the slider init script in the footer.
     *
     * @since 1.0.0
     */
    public function slider_init() {

        $ie_hover = false;
        foreach ( $this->data as $id => $data ) {
            // Prevent multiple init scripts for the same slider ID.
            if ( in_array( $data['id'], $this->done ) ) {
                continue;
            }
            $this->done[] = $data['id'];
            ?>
            <script type="text/javascript">
                <?php $this->slider_init_single( $data ); ?>
            </script>
            <?php
        }

    }

    /**
     * Outputs JS and CSS to the end of a slider, when the slider has
     * been loaded via an AJAX call
     *
     * @since 2.4.1.6
     *
     * @param string $slider Slider HTMl
     * @param array $data Slider Data
     */
    public function slider_init_ajax( $slider, $data ) {

        // CSS
        foreach ( $this->stylesheets as $stylesheet ) {
            // Skip main CSS, as this will have already been loaded
            if ( $stylesheet['id'] == 'soliloquy-style-css' ) {
                continue;
            }

            $slider .= '<link rel="stylesheet" id="' . $stylesheet['id'] . '" href="' . $stylesheet['href'] . '" type="text/css" media="all" />';
        }

        // JS
        // Failing
        /*
        $js = $this->slider_init_single( $data, true );
        var_dump($js);
        $slider .= '<script>' . $js . '</script>';
        */

        return $slider;

    }

    /**
    * Generates Javascript for initialising a single slider
    *
    * @since 2.4.1.6
    *
    * @param array $data Slider Data
    */
    public function slider_init_single( $data, $return = false ) {

        ob_start(); 
        do_action( 'soliloquy_api_start_global', $data ); 
        ?>
        if ( typeof soliloquy_slider === 'undefined' || false === soliloquy_slider ) {
            soliloquy_slider = {};
        }

        <?php if ( ! empty( $data['youtube'] ) ) : ?>
        if ( typeof soliloquy_youtube === 'undefined' || false === soliloquy_youtube ) {
            soliloquy_youtube = {};
        }
        <?php endif; ?>

        <?php if ( ! empty( $data['vimeo'] ) ) : ?>
        if ( typeof soliloquy_vimeo === 'undefined' || false === soliloquy_vimeo ) {
            soliloquy_vimeo = {};
        }
        <?php endif; ?>

        <?php if ( ! empty( $data['wistia'] ) ) : ?>
        if ( typeof soliloquy_wistia === 'undefined' || false === soliloquy_wistia ) {
            soliloquy_wistia = {};
        }
        <?php endif; ?>

        <?php if ( ! empty( $data['local'] ) ) : ?>
        if ( typeof soliloquy_local === 'undefined' || false === soliloquy_local ) {
            soliloquy_local = {};
        }
        <?php endif; ?>

        var soliloquy_width_<?php echo $data['id']; ?> = jQuery('#soliloquy-container-<?php echo $data['id']; ?>').width() < <?php echo $this->get_config( 'slider_width', $data ); ?> ? <?php echo $this->get_config( 'slider_width', $data ); ?> : jQuery('#soliloquy-container-<?php echo $data['id']; ?>').width();
        jQuery('#soliloquy-container-<?php echo $data['id']; ?>').css('height', Math.round(soliloquy_width_<?php echo $data['id']; ?>/(<?php echo $this->get_config( 'slider_width', $data ); ?>/<?php echo $this->get_config( 'slider_height', $data ); ?>))).fadeTo(300, 1);

        jQuery(document).ready(function($){
            <?php do_action( 'soliloquy_api_start', $data ); ?>

            var soliloquy_container_<?php echo $data['id']; ?> = $('#soliloquy-container-<?php echo $data['id']; ?>'),
                soliloquy_<?php echo $data['id']; ?> = $('#soliloquy-<?php echo $data['id']; ?>'),
                soliloquy_holder_<?php echo $data['id']; ?> = $('#soliloquy-<?php echo $data['id']; ?>').find('.soliloquy-preload');

            if ( 0 !== soliloquy_holder_<?php echo $data['id']; ?>.length ) {
                <?php if ( $this->get_config( 'mobile', $data ) ) : ?>
                var soliloquy_mobile = soliloquyIsMobile(),
                soliloquy_src_attr   = soliloquy_mobile ? 'data-soliloquy-src-mobile' : 'data-soliloquy-src';
                <?php else : ?>
                var soliloquy_src_attr = 'data-soliloquy-src';
                <?php endif; ?>
                soliloquy_holder_<?php echo $data['id']; ?>.each(function() {
                    var soliloquy_src = $(this).attr(soliloquy_src_attr);
                    if ( typeof soliloquy_src === 'undefined' || false === soliloquy_src ) {
                        return;
                    }

                    var soliloquy_image = new Image();
                    soliloquy_image.src = soliloquy_src;
                    $(this).attr('src', soliloquy_src).removeAttr(soliloquy_src_attr);
                });
            }

            <?php do_action( 'soliloquy_api_preload', $data ); ?>

            <?php 
            // Process video handlers.
            if ( ! empty( $data['youtube'] ) ) : ?>
            soliloquy_container_<?php echo $data['id']; ?>.find('.soliloquy-video-youtube').each(function(){
                if ( ! $(this).attr('src') ) {
                    $(this).css('height', Math.round($('#soliloquy-container-<?php echo $data['id']; ?>').width()/(<?php echo $this->get_config( 'slider_width', $data ); ?>/<?php echo $this->get_config( 'slider_height', $data ); ?>)));
                }
            });
            $(document).on('click.soliloquyYouTube<?php echo $data['id']; ?>', '.soliloquy-youtube-video', function(e){
                e.preventDefault();
                var $this  = $(this),
                    id     = $this.data('soliloquy-video-id'),
                    hold   = $this.data('soliloquy-video-holder') + '-holder',
                    width  = $('#soliloquy-container-<?php echo $data['id']; ?>').width(),
                    height = $('#soliloquy-container-<?php echo $data['id']; ?>').height();
                soliloquyYouTubeVids(<?php echo json_encode( $this->get_youtube_args( $data ) ); ?>, id, width, height, hold, jQuery);
            });
            <?php endif; ?>

            <?php if ( ! empty( $data['vimeo'] ) ) : ?>
            soliloquy_container_<?php echo $data['id']; ?>.find('.soliloquy-video-vimeo').each(function(){
                if ( ! $(this).attr('src') ) {
                    $(this).css('height', Math.round($('#soliloquy-container-<?php echo $data['id']; ?>').width()/(<?php echo $this->get_config( 'slider_width', $data ); ?>/<?php echo $this->get_config( 'slider_height', $data ); ?>)));
                }
            });
            $(document).on('click.soliloquyVimeo<?php echo $data['id']; ?> touchstart.soliloquyVimeo<?php echo $data['id']; ?> touchend.soliloquyVimeo<?php echo $data['id']; ?>', '.soliloquy-vimeo-video', function(e){
                e.preventDefault();
                var $this  = $(this),
                    id     = $this.data('soliloquy-video-id'),
                    hold   = $this.data('soliloquy-video-holder') + '-holder',
                    width  = $('#soliloquy-container-<?php echo $data['id']; ?>').width(),
                    height = $('#soliloquy-container-<?php echo $data['id']; ?>').height();
                soliloquyVimeoVids(<?php echo json_encode( $this->get_vimeo_args( $data ) ); ?>, id, width, height, hold, jQuery);
            });
            <?php endif; ?>

            <?php if ( ! empty( $data['wistia'] ) ) : ?>
            soliloquy_container_<?php echo $data['id']; ?>.find('.soliloquy-video-wistia').each(function(){
                if ( ! $(this).attr('src') ) {
                    $(this).css('height', Math.round($('#soliloquy-container-<?php echo $data['id']; ?>').width()/(<?php echo $this->get_config( 'slider_width', $data ); ?>/<?php echo $this->get_config( 'slider_height', $data ); ?>)));
                }
            });
            $(document).on('click.soliloquyWistia<?php echo $data['id']; ?> touchstart.soliloquyWistia<?php echo $data['id']; ?> touchend.soliloquyWistia<?php echo $data['id']; ?>', '.soliloquy-wistia-video', function(e){
                e.preventDefault();
                var $this  = $(this),
                    id     = $this.data('soliloquy-video-id'),
                    hold   = $this.data('soliloquy-video-holder') + '-holder',
                    width  = $('#soliloquy-container-<?php echo $data['id']; ?>').width(),
                    height = $('#soliloquy-container-<?php echo $data['id']; ?>').height();
                soliloquyWistiaVids(<?php echo json_encode( $this->get_wistia_args( $data ) ); ?>, id, width, height, hold, jQuery);
            });
            <?php endif; ?>

            <?php if ( ! empty( $data['local'] ) ) : ?>
            soliloquy_container_<?php echo $data['id']; ?>.find('.soliloquy-video-local').each(function(){
                if ( ! $(this).attr('src') ) {
                    $(this).css('height', Math.round($('#soliloquy-container-<?php echo $data['id']; ?>').width()/(<?php echo $this->get_config( 'slider_width', $data ); ?>/<?php echo $this->get_config( 'slider_height', $data ); ?>)));
                }
            });
            $(document).on('click.soliloquyLocal<?php echo $data['id']; ?> touchstart.soliloquyLocal<?php echo $data['id']; ?> touchend.soliloquyLocal<?php echo $data['id']; ?>', '.soliloquy-local-video', function(e){
                e.preventDefault();
                var $this  = $(this),
                    id     = $this.data('soliloquy-video-id'),
                    hold   = $this.data('soliloquy-video-holder') + '-holder',
                    width  = $('#soliloquy-container-<?php echo $data['id']; ?>').width(),
                    height = $('#soliloquy-container-<?php echo $data['id']; ?>').height();
                soliloquyLocalVids(<?php echo json_encode( $this->get_local_video_args( $data ) ); ?>, id, width, height, hold, jQuery);
            });
            <?php endif; ?>

            soliloquy_slider['<?php echo $data['id']; ?>'] = soliloquy_<?php echo $data['id']; ?>.soliloquy({
                <?php do_action( 'soliloquy_api_config_start', $data ); ?>
                slideSelector: '.soliloquy-item',
                <?php
                if ( $this->get_config( 'transition', $data ) == 'ticker' ) {
                    // Ticker is a very basic transition which doesn't support any other slider options other than the ones below
                    // useCSS is disabled, because pausing on hover won't work otherwise
                    ?>
                    ticker: true,
                    speed: <?php echo ( $this->get_config( 'duration', $data ) * count( $data['slider'] ) ); ?>,
                    tickerHover: <?php echo $this->get_config( 'hover', $data ); ?>,
                    useCSS: false,
                    <?php
                } else {
                    ?>
                    speed: <?php echo $this->get_config( 'speed', $data ); ?>,
                    pause: <?php echo $this->get_config( 'duration', $data ); ?>,
                    auto: <?php echo $this->get_config( 'auto', $data ); ?>,
                    keyboard: <?php echo $this->get_config( 'keyboard', $data ); ?>,
                    useCSS: <?php echo ( 'horizontal' == $this->get_config( 'transition', $data ) || 'vertical' == $this->get_config( 'transition', $data ) ? 0 : $this->get_config( 'css', $data ) ); ?>,
                    startSlide: <?php echo absint( $this->get_config( 'start', $data ) ); ?>,
                    autoHover: <?php echo $this->get_config( 'hover', $data ); ?>,
                    autoDelay: <?php echo $this->get_config( 'delay', $data ); ?>,
                    <?php if ( $this->get_config( 'smooth', $data ) ) : ?>
                    adaptiveHeight: 1,
                    adaptiveHeightSpeed: <?php echo apply_filters( 'soliloquy_adaptive_height_speed', 400, $data ); ?>,
                    <?php endif; ?>
                    <?php if ( $this->get_config( 'loop', $data ) ) : ?>
                    infiniteLoop: 1,
                    <?php else : ?>
                    infiniteLoop: 0,
                    hideControlOnEnd: 1,
                    <?php endif; ?>
                    mode: '<?php echo $this->get_config( 'transition', $data ); ?>',
                    pager: <?php echo $this->get_config( 'control', $data ); ?>,
                    controls: <?php echo $this->get_config( 'arrows', $data ); ?>,
                    <?php if ( $this->get_config( 'pauseplay', $data ) ) : ?>
                    autoControls: 1,
                    autoControlsCombine: 1,
                    <?php else : ?>
                    autoControls: 0,
                    autoControlsCombine: 0,
                    <?php endif; ?>
                    <?php
                }
                ?>
                nextText: '<?php echo apply_filters( 'soliloquy_next_text', '', $data ); ?>',
                prevText: '<?php echo apply_filters( 'soliloquy_prev_text', '', $data ); ?>',
                startText: '<?php echo apply_filters( 'soliloquy_start_text', '', $data ); ?>',
                stopText: '<?php echo apply_filters( 'soliloquy_stop_text', '', $data ); ?>',
                <?php do_action( 'soliloquy_api_config_callback', $data ); ?>
                onSliderLoad: function(currentIndex){
                    soliloquy_container_<?php echo $data['id']; ?>.find('.soliloquy-active-slide').removeClass('soliloquy-active-slide').attr('aria-hidden','true');
                    soliloquy_container_<?php echo $data['id']; ?>.css({'height':'auto','background-image':'none'});
                    if ( soliloquy_container_<?php echo $data['id']; ?>.find('.soliloquy-slider li').size() > 1 ) {
                        soliloquy_container_<?php echo $data['id']; ?>.find('.soliloquy-controls').fadeTo(300, 1);
                    } else {
                        soliloquy_container_<?php echo $data['id']; ?>.find('.soliloquy-controls').addClass('soliloquy-hide');
                    }
                    soliloquy_<?php echo $data['id']; ?>.find('.soliloquy-item:not(.soliloquy-clone):eq(' + currentIndex + ')').addClass('soliloquy-active-slide').attr('aria-hidden','false');
                    soliloquy_container_<?php echo $data['id']; ?>.find('.soliloquy-clone').find('*').removeAttr('id');

                    soliloquy_container_<?php echo $data['id']; ?>.find('.soliloquy-controls-direction').attr('aria-label','carousel buttons').attr('aria-controls', '<?php echo 'soliloquy-container-' . $data['id']; ?>');
                    soliloquy_container_<?php echo $data['id']; ?>.find('.soliloquy-controls-direction a.soliloquy-prev').attr('aria-label','previous');
                    soliloquy_container_<?php echo $data['id']; ?>.find('.soliloquy-controls-direction a.soliloquy-next').attr('aria-label','next');
                  
                    $(window).trigger('resize');

                    soliloquy_container_<?php echo $data['id']; ?>.parent().attr('data-soliloquy-loaded', 1);

                    <?php do_action( 'soliloquy_api_on_load', $data ); ?>
                },
                onSlideBefore: function(element, oldIndex, newIndex){
                    soliloquy_container_<?php echo $data['id']; ?>.find('.soliloquy-active-slide').removeClass('soliloquy-active-slide').attr('aria-hidden','true');
                    $(element).addClass('soliloquy-active-slide').attr('aria-hidden','false');
                    <?php if ( ! empty( $data['youtube'] ) ) : ?>
                    $.each(soliloquy_youtube, function(id, yt){
                        yt.pauseVideo();
                    });
                    <?php endif; ?>
                    <?php if ( ! empty( $data['vimeo'] ) ) : ?>
                    $.each(soliloquy_vimeo, function(id, vm){
                        vm.api('pause');
                    });
                    <?php endif; ?>
                    <?php if ( ! empty( $data['wistia'] ) ) : ?>
                    $.each(soliloquy_wistia, function(id, wi){
                        wi.pause();
                    });
                    <?php endif; ?>
                    <?php if ( ! empty( $data['local'] ) ) : ?>
                    $.each(soliloquy_local, function(id, lo){
                        lo.pause();
                    });
                    <?php endif; ?>
                    <?php if ( $this->get_config( 'caption_delay', $data ) > 0 ) : ?>
                    $('div.soliloquy-caption', $(element)).hide();
                    <?php endif; ?>
                    <?php do_action( 'soliloquy_api_before_transition', $data ); ?>
                },
                onSlideAfter: function(element, oldIndex, newIndex){
                    <?php if ( $this->get_config( 'caption_delay', $data ) > 0 ) : ?>
                    setTimeout(function() {
                        $('div.soliloquy-caption', $(element)).fadeIn();    
                    }, <?php echo $this->get_config( 'caption_delay', $data ); ?>);
                    <?php endif; ?>
                    <?php do_action( 'soliloquy_api_after_transition', $data ); ?>
                }
                <?php do_action( 'soliloquy_api_config_end', $data ); ?>
            });

            <?php do_action( 'soliloquy_api_slider', $data ); ?>

            <?php 
            // Process HTML slide helpers if we have HTML slides.
            if ( $this->html ) : ?>
            $(window).on({
                'resize' : function(){
                    var soliloquy_html_slides = soliloquy_<?php echo $data['id']; ?>.find('.soliloquy-html-sentinel');
                    $.each(soliloquy_html_slides, function(i, el){
                        $(this).height(Math.round(soliloquy_container_<?php echo $data['id']; ?>.width()/(<?php echo $this->get_config( 'slider_width', $data ); ?>/<?php echo $this->get_config( 'slider_height', $data ); ?>)));
                    });
                }
            });
            <?php endif; ?>

            <?php do_action( 'soliloquy_api_end', $data ); ?>
        });
        
        <?php 
        // Minify before outputting to improve page load time.
        do_action( 'soliloquy_api_end_global', $data ); 
        $result = $this->minify( ob_get_clean() );

        // Return or echo
        if ( $return ) {
            return $result;
        }

        echo $result;

    }

    /**
    * Checks if the given request is an AJAX request
    * 
    * @since 2.4.1.6
    *
    * @return bool AJAX Request
    */
    public function is_ajax_request() {

        // Assume false
        $is_ajax_request = false;

        // Check server headers for HTTP_X_REQUESTED_WITH
        if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) {
            $is_ajax_request = true;
        }

        // @TODO Could add some more checks here, as not all servers set HTTP_X_REQUESTED_WITH
        return $is_ajax_request;

    }

    /**
     * Helper method for loading slider stylesheets.
     *
     * @since 1.0.0
     *
     * @param array $data The slider data to use for retrieval.
     * @return string     String of stylesheet declarations.
     */
    public function get_stylesheets( $data ) {
        
        foreach ( $this->stylesheets as $stylesheet ) {
            wp_enqueue_style( $stylesheet['id'], $stylesheet['href'], false, $this->base->version, 'all' );
        }

    }

    /**
     * Loads a custom slider display theme.
     *
     * @since 1.0.0
     *
     * @param string $theme The custom theme slug to load.
     */
    public function load_slider_theme( $theme ) {
        
        // Loop through the available themes and enqueue the one called.
        foreach ( Soliloquy_Common::get_instance()->get_slider_themes() as $array => $data ) {
            if ( $theme !== $data['value'] ) {
                continue;
            }

            $this->stylesheets[] = array(
                'id'    => $this->base->plugin_slug . $theme . '-theme-style-css',
                'href'  => esc_url( add_query_arg( 'ver', $this->base->version, plugins_url( 'themes/' . $theme . '/style.css', $data['file'] ) ) ),
            );
            
            break;
        }

    }

    /**
     * Helper method for adding custom slider classes for the outer container.
     *
     * @since 2.4.2
     *
     * @param array $data The slider data to use for retrieval.
     * @return string     String of space separated slider classes.
     */
    public function get_slider_container_classes( $data ) {

        // Set default class.
        $classes   = array();
        $classes[] = 'soliloquy-outer-container';

        // Allow filtering of classes and then return what's left.
        $classes = apply_filters( 'soliloquy_output_container_classes', $classes, $data );
        return trim( implode( ' ', array_map( 'trim', array_map( 'sanitize_html_class', array_unique( $classes ) ) ) ) );

    }

    /**
     * Helper method for adding custom slider classes.
     *
     * @since 1.0.0
     *
     * @param array $data The slider data to use for retrieval.
     * @return string     String of space separated slider classes.
     */
    public function get_slider_classes( $data ) {

        // Set default class.
        $classes   = array();
        $classes[] = 'soliloquy-container';

        // Add custom class based on the transition.
        $classes[] = 'soliloquy-transition-' . $this->get_config( 'transition', $data );

        // Add backwards compat for previous transition classes.
        $transition = $this->get_config( 'transition', $data );
        if ( 'fade' == $transition ) {
            $classes[] = 'soliloquy-' . $transition;
        } else {
            $classes[] = 'soliloquy-slide-' . $transition;
        }

        // Add classes for UI element specific things.
        if ( $this->get_config( 'control', $data ) ) {
            $classes[] = 'soliloquy-controls-active';
        }

        if ( $this->get_config( 'arrows', $data ) ) {
            $classes[] = 'soliloquy-arrows-active';
        }

        if ( $this->get_config( 'pauseplay', $data ) ) {
            $classes[] = 'soliloquy-pauseplay-active';
        }

        // If we have custom classes defined for this slider, output them now.
        foreach ( (array) $this->get_config( 'classes', $data ) as $class ) {
            $classes[] = $class;
        }

        // Add custom class based on the theme.
        $classes[] = 'soliloquy-theme-' . $this->get_config( 'slider_theme', $data );
        
        // If the slider has RTL support, add a class for it.
        if ( $this->get_config( 'rtl', $data ) ) {
            $classes[] = 'soliloquy-rtl';
        }

        // Allow filtering of classes and then return what's left.
        $classes = apply_filters( 'soliloquy_output_classes', $classes, $data );
        return trim( implode( ' ', array_map( 'trim', array_map( 'sanitize_html_class', array_unique( $classes ) ) ) ) );

    }

    /**
     * Helper method for adding custom slider classes.
     *
     * @since 1.0.4
     *
     * @param array $item Array of item data.
     * @param int $i      The current position in the slider.
     * @param array $data The slider data to use for retrieval.
     * @return string     String of space separated slider item classes.
     */
    public function get_slider_item_classes( $item, $i, $data ) {

        // Set default class.
        $classes   = array();
        $classes[] = 'soliloquy-item';
        $classes[] = 'soliloquy-item-' . $i;

        if ( isset( $item['id'] ) ) {
            $classes[] = 'soliloquy-id-' . $item['id'];        
        }

        // Set the type of slide as a class.
        $classes[] = ! empty( $item['type'] ) ? 'soliloquy-' . $item['type'] . '-slide' : 'soliloquy-image-slide';

        // Allow filtering of classes and then return what's left.
        $classes = apply_filters( 'soliloquy_output_item_classes', $classes, $item, $i, $data );
        return trim( implode( ' ', array_map( 'trim', array_map( 'sanitize_html_class', array_unique( $classes ) ) ) ) );

    }
    
    /**
     * Helper method for adding custom slider image classes.
     *
     * @since 2.4.0
     *
     * @param array $item   Array of item data.
     * @param int   $i      The current position in the slider.
     * @param array $data   The slider data to use for retrieval.
     * @param bool  $mobile Mobile
     * @return string       String of space separated slider item image classes.
     */
    public function get_slider_item_image_classes( $item, $i, $data, $mobile = false ) {

        // Set default class.
        $classes   = array();
        $classes[] = 'soliloquy-image';
        $classes[] = 'soliloquy-image-' . $i;

        // Add preload class if mobile
        if ( $mobile ) {
            $classes[] = 'soliloquy-preload';
        }

        // Allow filtering of classes and then return what's left.
        $classes = apply_filters( 'soliloquy_output_item_image_classes', $classes, $item, $i, $data, $mobile );
        return trim( implode( ' ', array_map( 'trim', array_map( 'sanitize_html_class', array_unique( $classes ) ) ) ) );

    }
    
     /**
     * Helper method for adding custom slider image classes.
     *
     * @since 2.4.0
     *
     * @param array     $item       Array of item data.
     * @param int       $i          The current position in the slider.
     * @param array     $data       The slider data to use for retrieval.
     * @param string    $vid_type   Video Type
     * @param bool      $mobile     Is Mobile
     * @return string               String of space separated slider item video image classes.
     */
    public function get_slider_item_video_classes( $item, $i, $data, $vid_type, $mobile = false ) {

        // Set default class.
        $classes   = array();
        $classes[] = 'soliloquy-image';
        $classes[] = 'soliloquy-video-thumb';
        $classes[] = 'soliloquy-video-' . $vid_type;
        $classes[] = 'soliloquy-video-' . $i;

        // Add preload class if mobile
        if ( $mobile ) {
            $classes[] = 'soliloquy-preload';
        }

        // Allow filtering of classes and then return what's left.
        $classes = apply_filters( 'soliloquy_output_item_video_classes', $classes, $item, $i, $data, $vid_type, $mobile );
        return trim( implode( ' ', array_map( 'trim', array_map( 'sanitize_html_class', array_unique( $classes ) ) ) ) );

    }

    /**
     * Helper method to retrieve the proper image src attribute based on slider settings.
     *
     * @since 1.0.0
     *
     * @param int $id      The image attachment ID to use.
     * @param array $item  Slider item data.
     * @param array $data  The slider data to use for retrieval.
     * @param string $type The type of cropped image to retrieve.
     * @return string      The proper image src attribute for the image.
     */
    public function get_image_src( $id, $item, $data, $type = 'slider' ) {
        
        // Before we do anything, we need to ensure that the default slider type is being used. If not, return the src provided.
        $slider_type = $this->get_config( 'type', $data );
        if ( 'default' !== $slider_type && 'fc' !== $slider_type && 'dynamic' !== $slider_type ) {
            return apply_filters( 'soliloquy_image_src', $item['src'], $id, $item, $data );
        }
        
        // Get the full image src. If it does not return the data we need, return the image link instead.
        $size_type  = $this->get_config( 'slider_size', $data );
        $size       = 'default' == $size_type || 'thumbnails' == $type || 'full_width' == $size_type ? 'full' : $size_type;
        $src        = wp_get_attachment_image_src( $id, $size );
        $image      = ! empty( $src[0] ) ? $src[0] : false;

        // Allow image to be filtered to use a different thumbnail than the main image.
        if ( 'thumbnails' == $type ) {
            $image = apply_filters( 'soliloquy_cropped_image', $image, $id, $item, $data, $data['id'] );
        }
        
        // If no image, return with the base link.
        if ( ! $image ) {
            $image = ! empty( $item['src'] ) ? $item['src'] : false;
            
            if ( ! $image ) {
                if ( ! empty( $item['link'] ) ) {
                    return apply_filters( 'soliloquy_no_image_src', '', $id, $item, $data );
                } else {
                    // If working with a video slide, default to a video thumbnail of the video.
                    if ( isset( $item['type'] ) && 'video' == $item['type'] ) {
                        $image = $this->get_video_thumbnail( $id, $item, $data );
                        if ( ! $image ) {
                            return apply_filters( 'soliloquy_no_image_src', '', $id, $item, $data );
                        }
                    } else {
                        return apply_filters( 'soliloquy_no_image_src', '', $id, $item, $data );
                    }
                }
            }
        }
        
        // Prep our indexable images.
        if ( $image && 'mobile' !== $type ) {
            $this->index[$data['id']][$id] = array(
                'src' => $image,
                'alt' => ! empty( $item['alt'] ) ? $item['alt'] : ''
            );
        }

        // If we are not dealing with a full sized image, don't do any cropping.
        if ( 'full' !== $size ) {
            return apply_filters( 'soliloquy_image_src', $image, $id, $item, $data );
        }

        // Generate the cropped image if necessary.
        $type = 'thumbnails' !== $type ? apply_filters( 'soliloquy_crop_type', $type, $id, $item, $data ) : $type;
        if ( empty( $type ) ) {
            return apply_filters( 'soliloquy_no_image_type', $item['link'], $id, $item, $data );
        }
        
        // If the setting exists, go onward with cropping.
        if ( isset( $data['config']['slider'] ) && $data['config']['slider'] ) {
            if ( isset( $data['config'][$type] ) && $data['config'][$type] ) {
                $common = Soliloquy_Common::get_instance();
                $args = array(
                    'position' => 'c',
                    'width'    => $this->get_config( $type . '_width', $data ),
                    'height'   => $this->get_config( $type . '_height', $data ),
                    'quality'  => 100,
                    'retina'   => false
                );
                $args = apply_filters( 'soliloquy_crop_image_args', $args, $id, $item, $data, $type );
                $cropped_image = $common->resize_image( $image, $args['width'], $args['height'], true, $args['position'], $args['quality'], $args['retina'], $data );

                // If there is an error, possibly output error message and return the default image src.
                if ( is_wp_error( $cropped_image ) ) {
                    // If debugging is defined, print out the error.
                    if ( defined( 'SOLILOQUY_CROP_DEBUG' ) && SOLILOQUY_CROP_DEBUG ) {
                        echo '<pre>' . var_export( $cropped_image->get_error_message(), true ) . '</pre>';
                    }

                    // Return the non-cropped image as a fallback.
                    return apply_filters( 'soliloquy_image_src', $image, $id, $item, $data );
                } else {
                    return apply_filters( 'soliloquy_image_src', $cropped_image, $id, $item, $data );
                }
            } else {
                return apply_filters( 'soliloquy_image_src', $image, $id, $item, $data );
            }
        } else if ( 'thumbnails' == $type && isset( $data['config']['thumbnails'] ) && $data['config']['thumbnails'] ) {
            if ( isset( $data['config']['thumbnails_crop'] ) && $data['config']['thumbnails_crop'] ) {
                $common = Soliloquy_Common::get_instance();
                $args = array(
                    'position' => 'c',
                    'width'    => $this->get_config( 'thumbnails_width', $data ),
                    'height'   => $this->get_config( 'thumbnails_height', $data ),
                    'quality'  => 100,
                    'retina'   => false
                );
                $args = apply_filters( 'soliloquy_crop_image_args', $args, $id, $item, $data, $type );
                $cropped_image = $common->resize_image( $image, $args['width'], $args['height'], true, $args['position'], $args['quality'], $args['retina'], $data );

                // If there is an error, possibly output error message and return the default image src.
                if ( is_wp_error( $cropped_image ) ) {
                    // If debugging is defined, print out the error.
                    if ( defined( 'SOLILOQUY_CROP_DEBUG' ) && SOLILOQUY_CROP_DEBUG ) {
                        echo '<pre>' . var_export( $cropped_image->get_error_message(), true ) . '</pre>';
                    }

                    // Return the non-cropped image as a fallback.
                    return apply_filters( 'soliloquy_image_src', $image, $id, $item, $data );
                } else {
                    return apply_filters( 'soliloquy_image_src', $cropped_image, $id, $item, $data );
                }
            } else {
                return apply_filters( 'soliloquy_image_src', $image, $id, $item, $data );
            }
        } else if ( 'lightbox' == $type && isset( $data['config']['lightbox_thumbs'] ) && $data['config']['lightbox_thumbs'] ) {
            if ( isset( $data['config']['thumbnails_crop'] ) && $data['config']['thumbnails_crop'] ) {
                $common = Soliloquy_Common::get_instance();
                $args = array(
                    'position' => 'c',
                    'width'    => $this->get_config( 'lightbox_twidth', $data ),
                    'height'   => $this->get_config( 'lightbox_theight', $data ),
                    'quality'  => 100,
                    'retina'   => false
                );
                $args = apply_filters( 'soliloquy_crop_image_args', $args, $id, $item, $data, $type );
                $cropped_image = $common->resize_image( $image, $args['width'], $args['height'], true, $args['position'], $args['quality'], $args['retina'], $data );

                // If there is an error, possibly output error message and return the default image src.
                if ( is_wp_error( $cropped_image ) ) {
                    // If debugging is defined, print out the error.
                    if ( defined( 'SOLILOQUY_CROP_DEBUG' ) && SOLILOQUY_CROP_DEBUG ) {
                        echo '<pre>' . var_export( $cropped_image->get_error_message(), true ) . '</pre>';
                    }

                    // Return the non-cropped image as a fallback.
                    return apply_filters( 'soliloquy_image_src', $image, $id, $item, $data );
                } else {
                    return apply_filters( 'soliloquy_image_src', $cropped_image, $id, $item, $data );
                }
            } else {
                return apply_filters( 'soliloquy_image_src', $image, $id, $item, $data );
            }
        } else {
            return apply_filters( 'soliloquy_image_src', $image, $id, $item, $data );
        }

    }

    /**
     * Helper method for grabbing the default video thumbnail from the video provider.
     *
     * @since 2.2.0
     *
     * @param int|string $id The ID for the slide.
     * @param array $item    Array of data for the slide.
     * @param array $data    Array of slider data.
     * @return bool|string   False if unsuccessful, otherwise the video thumbnail.
     */
    public function get_video_thumbnail( $id, $item, $data ) {
        
        $thumb = false;
        $id    = $this->get_video_data( $id, $item, $data );
        $type  = $this->get_video_data( $id, $item, $data, 'type' );

        switch ( $type ) {
            case 'youtube' :
                $thumb = 'http://img.youtube.com/vi/' . $id . '/maxresdefault.jpg';
                break;
            case 'vimeo' :
                $res = wp_remote_get( 'http://vimeo.com/api/v2/video/' . $id . '.json' );
                $bod = wp_remote_retrieve_body( $res );
                $api = json_decode( $bod, true );
                if ( ! empty( $api[0] ) && ! empty( $api[0]['thumbnail_large'] ) ) {
                    $thumb = $api[0]['thumbnail_large'];
                }
                break;
            case 'wistia' :
                $res = wp_remote_get( 'http://fast.wistia.net/oembed?url=' . urlencode( $item['url'] ) );
                $bod = wp_remote_retrieve_body( $res );
                $api = json_decode( $bod, true );
                if ( ! empty( $api['thumbnail_url'] ) ) {
                    $thumb = remove_query_arg( 'image_crop_resized', $api['thumbnail_url'] );
                }
                break;
        }

        // If we have reached this point, we need to stream and save the image into the database to prevent multiple stream lookups.
        if ( $thumb ) {
            if ( ! class_exists( 'Soliloquy_Import' ) ) {
                require plugin_dir_path( $this->base->file ) . 'includes/admin/import.php';
            }

            $stream = Soliloquy_Import::get_instance()->import_remote_image( $thumb, $data, $data['id'], 0, true );
            if ( empty( $stream['error'] ) || isset( $stream['error'] ) && ! $stream['error'] ) {
                $thumb = $stream['url'];
                $data  = get_post_meta( $data['id'], '_sol_slider_data', true );
                $data['slider'][$item['id']]['src']   = $thumb;
                update_post_meta( $data['id'], '_sol_slider_data', $data );
                Soliloquy_Common::get_instance()->flush_slider_caches( $data['id'], $this->get_config( 'slug', $data ) );
            }
        }

        return apply_filters( 'soliloquy_video_thumbnail', $thumb, $id, $item, $data );

    }

    /**
     * Helper method for positioning the slider.
     *
     * @since 1.0.0
     *
     * @param string $style  String of slider container styles.
     * @param array $data    Array of slider data.
     * @return string $style Amended string of slider container styles.
     */
    public function position_slider( $style, $data ) {

        $gutter   = $this->get_config( 'gutter', $data );
        $position = '';
        switch ( $this->get_config( 'position', $data ) ) {
            case 'center' :
                $position .= 'margin:0 auto ' . $gutter . 'px;';
                break;
            case 'left' :
                $position .= 'float:left;margin:0 ' . $gutter . 'px ' . $gutter . 'px 0;';
                break;
            case 'right' :
                $position .= 'float:right;margin:0 0 ' . $gutter . 'px ' . $gutter . 'px;';
                break;
            case 'none' :
                $position = '';
                break;
        }

        $position = apply_filters( 'soliloquy_position_style', $position, $data );
        return $style . $position;

    }

    /**
     * Helper method for making a full width slider.
     *
     * @since 1.0.0
     *
     * @param string $style  String of slider container styles.
     * @param array $data    Array of slider data.
     * @return string $style Amended string of slider container styles.
     */
    public function full_width( $style, $data ) {

        $full_width = 'max-width:100%;max-height:none;';
        return $style . $full_width;

    }

    /**
     * Helper method for retrieving the mobile image src attribute.
     *
     * @since 1.0.0
     *
     * @param string $attr  String of image attributes.
     * @param int $id       The ID of the image attachment.
     * @param array $item   The array of date for the image.
     * @param array $data   Array of slider data.
     * @return string $attr Amended string of image attributes.
     */
    public function mobile_image( $attr, $id, $item, $data ) {

        $mobile_image = $this->get_image_src( $id, $item, $data, 'mobile' );
        return $attr . ' data-soliloquy-src-mobile="' . esc_url( $mobile_image ) . '"';

    }

    /**
     * Shuffles and randomizes images in a slider.
     *
     * @since 1.0.0
     *
     * @param array $data  The slider data to use for retrieval.
     * @return array $data Shuffled slider data.
     */
    public function shuffle( $data ) {

        // Return early there are no items to shuffle.
        if ( ! is_array( $data['slider'] ) ) {
            return $data;
        }

        // Prepare variables.
        $random = array();
        $keys   = array_keys( $data['slider'] );

        // Shuffle the keys and loop through them to create a new, randomized array of images.
        shuffle( $keys );
        foreach ( $keys as $key ) {
            $random[$key] = $data['slider'][$key];
        }

        // Return the randomized image array.
        $data['slider'] = $random;
        return $data;

    }

    /**
     * Helper method for retrieving config values.
     *
     * @since 1.0.0
     *
     * @param string $key The config key to retrieve.
     * @param array $data The slider data to use for retrieval.
     * @return string     Key value on success, default if not set.
     */
    public function get_config( $key, $data ) {

        $instance = Soliloquy_Common::get_instance();

        // If we are on a mobile device, some config keys have mobile equivalents, which we need to check instead
        if ( wp_is_mobile() ) {
            $mobile_keys = array();
            $mobile_keys = apply_filters( 'soliloquy_get_config_mobile_keys', $mobile_keys );
            
            if ( array_key_exists( $key, $mobile_keys ) ) {
                // Use the mobile array key to get the config value
                $key = $mobile_keys[ $key ];
            }
        }

        return isset( $data['config'][$key] ) ? $data['config'][$key] : $instance->get_config_default( $key );

    }

    /**
     * Helper method for retrieving meta values.
     *
     * @since 1.0.0
     *
     * @param string $key    The config key to retrieve.
     * @param int $attach_id The attachment ID to target.
     * @param array $data    The slider data to use for retrieval.
     * @return string        Key value on success, default if not set.
     */
    public function get_meta( $key, $attach_id, $data ) {

        $instance = Soliloquy_Common::get_instance();
        return isset( $data['slider'][$attach_id][$key] ) ? $data['slider'][$attach_id][$key] : $instance->get_meta_default( $key, $attach_id );

    }

    /**
     * Helper method to minify a string of data.
     *
     * @since 1.0.4
     *
     * @param string $string  String of data to minify.
     * @return string $string Minified string of data.
     */
    public function minify( $string, $stripDoubleForwardslashes = true ) {
        
        // Added a switch for stripping double forwardslashes
        // This can be disabled when using URLs in JS, to ensure http:// doesn't get removed
        // All other comment removal and minification will take place

        $stripDoubleForwardslashes = apply_filters( 'soliloquy_minify_strip_double_forward_slashes', $stripDoubleForwardslashes );
        
        if ( $stripDoubleForwardslashes ) {
            $clean = preg_replace( '/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/', '', $string );
        } else {
            // Use less aggressive method
            $clean = preg_replace( '!/\*.*?\*/!s', '', $string );
            $clean = preg_replace( '/\n\s*\n/', "\n", $clean );
        }
        
        $clean = str_replace( array( "\r\n", "\r", "\t", "\n", '  ', '    ', '     ' ), '', $clean );

        return apply_filters( 'soliloquy_minified_string', $clean, $string );

    }

    /**
     * Outputs only the first image of the slider inside a regular <div> tag
     * to avoid styling issues with feeds.
     *
     * @since 1.0.0
     *
     * @param array $data     Array of slider data.
     * @return string $slider Custom slider output for feeds.
     */
    public function do_feed_output( $data ) {

        $slider = '<div class="soliloquy-feed-output">';
            foreach ( $data['slider'] as $id => $item ) {
                // Skip over images that are pending (ignore if in Preview mode).
                if ( isset( $item['status'] ) && 'pending' == $item['status'] && ! is_preview() ) {
                    continue;
                }

                $imagesrc = $this->get_image_src( $id, $item, $data );
                $slider  .= '<img class="soliloquy-feed-image" src="' . esc_url( $imagesrc ) . '" title="' . esc_attr( $item['title'] ) . '" alt="' . esc_attr( $item['alt'] ) . '" />';
                break;
             }
        $slider .= '</div>';

        return apply_filters( 'soliloquy_feed_output', $slider, $data );

    }

    /**
     * Returns the query args to be passed to YouTube videos.
     *
     * @since 1.0.0
     *
     * @param array $data Array of slider data.
     */
    public function get_youtube_args( $data ) {
    
        $args = array(
            'enablejsapi'    => 1,
            'version'        => 3,
            'wmode'          => 'transparent',
            'rel'            => 0,
            'showinfo'       => 0,
            'modestbranding' => 1,
            'autoplay'       => 1,
            'origin'         => get_home_url()
        );

        return apply_filters( 'soliloquy_youtube_args', $args, $data );

    }

    /**
     * Returns the query args to be passed to Vimeo videos.
     *
     * @since 1.0.0
     *
     * @param array $data Array of slider data.
     */
    public function get_vimeo_args( $data ) {

        $args = array(
            'api'        => 1,
            'wmode'      => 'transparent',
            'byline'     => 0,
            'title'      => 0,
            'portrait'   => 0,
            'autoplay'   => 1,
            'badge'      => 0,
            'fullscreen' => 1
        );

        return apply_filters( 'soliloquy_vimeo_args', $args, $data );

    }

    /**
     * Returns the query args to be passed to Wistia videos.
     *
     * @since 1.0.0
     *
     * @param array $data Array of slider data.
     */
    public function get_wistia_args( $data ) {

        $args = array(
            'version'               => 'v1',
            'wmode'                 => 'opaque',
            'volumeControl'         => 1,
            'controlsVisibleOnLoad' => 1,
            'videoFoam'             => 1
        );

        return apply_filters( 'soliloquy_wistia_args', $args, $data );

    }

    /**
     * Returns the query args to be passed to Local videos.
     *
     * @since 2.4.1.4
     *
     * @param array $data Array of slider data.
     */
    public function get_local_video_args( $data ) {

        $args = array(
            'autoplay'  => 1,
            'playpause' => 1,
            'progress'  => 1,
            'current'   => 1,
            'duration'  => 1,
            'volume'    => 1,
        );

        return apply_filters( 'soliloquy_local_video_args', $args, $data );

    }

    /**
     * Flag for detecting a mobile device server-side.
     *
     * @since 1.0.0
     *
     * @return bool True if on a mobile device, false otherwise.
     */
    public function is_mobile() {

        // If the user agent header is not set, return false.
        if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
            return false;
        }

        // Test for a mobile browser.
        $user_agent = stripslashes( $_SERVER['HTTP_USER_AGENT'] );
        if ( preg_match( '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $user_agent ) || preg_match( '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr( $user_agent, 0, 4 ) ) ) {
            return true;
        }

        // Return wp_is_mobile for the final check.
        return wp_is_mobile();

    }

    /**
     * Returns a set of indexable image links to allow SEO indexing for preloaded images.
     *
     * @since 1.0.0
     *
     * @param mixed $id       The slider ID to target.
     * @return string $images String of indexable image HTML.
     */
    public function get_indexable_images( $id ) {

        // If there are no images, don't do anything.
        $images = '';
        $i      = 1;
        if ( empty( $this->index[$id] ) ) {
            return $images;
        }

        foreach ( (array) $this->index[$id] as $attach_id => $data ) {
            $images .= '<img class="soliloquy-image soliloquy-no-js-image" src="' . esc_url( $data['src'] ) . '" alt="' . esc_attr( $data['alt'] ) . '" />';
            $i++;
        }

        return apply_filters( 'soliloquy_indexable_images', $images, $this->index, $id );

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The Soliloquy_Shortcode object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Soliloquy_Shortcode ) ) {
            self::$instance = new Soliloquy_Shortcode();
        }

        return self::$instance;

    }

}

// Load the shortcode class.
$soliloquy_shortcode = Soliloquy_Shortcode::get_instance();