<?php
/**
 * Handles all admin ajax interactions for the Soliloquy plugin.
 *
 * @since 1.0.0
 *
 * @package Soliloquy
 * @author  Thomas Griffin
 */

add_action( 'wp_ajax_soliloquy_upgrade_sliders', 'soliloquy_ajax_upgrade_sliders' );
/**
 * Upgrades sliders from v1 to v2. This also upgrades any current v2 users to the
 * proper post type. This is a mess and it was my fault. :-( I apologize to my customers
 * for making this so rough. You deserve better, and I will work hard to do better by
 * you! Thanks for hanging in there faithfully with me!
 *
 * @since 1.0.0
 */
function soliloquy_ajax_upgrade_sliders() {

    // Run a security check first.
    check_ajax_referer( 'soliloquy-upgrade', 'nonce' );

    // Increase the time limit to account for large slider sets and suspend cache invalidations.
    set_time_limit( Soliloquy_Common::get_instance()->get_max_execution_time() );
    wp_suspend_cache_invalidation( true );

    // Update the license key from v1 to v2 if necessary.
    $v2_license = get_option( 'soliloquy' );

    if ( ! $v2_license || empty( $v2_license['key'] ) || empty( $v2_license['type'] ) ) {
        $v1_license  = get_option( 'soliloquy_license_key' );
        $new_license = Soliloquy::default_options();

        if ( ! empty( $v1_license['license'] ) ) {
            $new_license['key'] = $v1_license['license'];
        }

        update_option( 'soliloquy', $new_license );

        // Force the new key to be validated.
        Soliloquy_License::get_instance()->validate_key( true );
    }

    // Grab all sliders from v1 and convert them to the new system.
    $sliders = get_posts(
        array(
            'post_type'      => 'soliloquy',
            'posts_per_page' => -1
        )
    );

    // Loop through sliders and convert them.
    foreach ( (array) $sliders as $slider ) {
        // Grab meta from the v1 and v2 sliders.
        $meta    = get_post_meta( $slider->ID, '_soliloquy_settings', true );
        $v2_meta = get_post_meta( $slider->ID, '_sol_slider_data', true );

        // Move meta from v1 to v2, or if already using v2, use v2 as a starting point.
        if ( empty( $v2_meta ) ) {
            $new_meta = array(
                'id'     => $slider->ID,
                'config' => array(),
                'slider' => array(),
                'status' => 'active'
            );
        } else {
            $new_meta = $v2_meta;
        }

        // Splice meta from v1 to v2.
        if ( ! empty( $new_meta['config']['gutter'] ) ) {
            $new_meta['config']['gutter'] = 0;
        }

        if ( ! empty( $new_meta['config']['position'] ) ) {
            $new_meta['config']['position'] = 'none';
        }

        if ( ! empty( $new_meta['config']['mobile'] ) ) {
            $new_meta['config']['mobile'] = 0;
        }

        $new_meta['config']['title'] = $slider->post_title;
        $new_meta['config']['slug']  = $slider->post_name;

        if ( ! empty( $meta['width'] ) ) {
            $new_meta['config']['slider_width'] = absint( $meta['width'] );
        }

        if ( ! empty( $meta['height'] ) ) {
            $new_meta['config']['slider_height'] = absint( $meta['height'] );
        }

        if ( ! empty( $meta['default'] ) && 'cropped' !== $meta['default'] ) {
            $new_meta['config']['slider'] = 0;
        }

        if ( ! empty( $meta['custom'] ) ) {
            if ( $meta['custom'] ) {
                if ( 'full' == $meta['custom'] ) {
                    $new_meta['config']['slider_size'] = 'default';
                } else {
                    $new_meta['config']['slider_size'] = $meta['custom'];

                    global $_wp_additional_image_sizes;
                    if ( isset( $_wp_additional_image_sizes[$meta['custom']] ) ) {
        				$width 	= absint( $_wp_additional_image_sizes[$meta['custom']]['width'] );
        				$height = absint( $_wp_additional_image_sizes[$meta['custom']]['height'] );
        			} else {
        				$width	= absint( get_option( $meta['custom'] . '_size_w' ) );
        				$height	= absint( get_option( $meta['custom'] . '_size_h' ) );
        			}

        			if ( $width ) {
            			$new_meta['config']['slider_width'] = $width;
        			}

        			if ( $height ) {
            			$new_meta['config']['slider_height'] = $height;
        			}
                }
            }
        }

        if ( ! empty( $meta['transition'] ) ) {
            if ( 'slide-horizontal' == $meta['transition'] ) {
                $new_meta['config']['transition'] = 'horizontal';
            } else if ( 'slide-vertical' == $meta['transition'] ) {
                $new_meta['config']['transition'] = 'vertical';
            } else {
                $new_meta['config']['transition'] = 'fade';
            }
        }

        if ( ! empty( $meta['speed'] ) ) {
            $new_meta['config']['duration'] = $meta['speed'];
        }

        if ( ! empty( $meta['duration'] ) ) {
            $new_meta['config']['speed'] = $meta['duration'];
        }

        if ( ! empty( $meta['animate'] ) ) {
            $new_meta['config']['auto'] = $meta['animate'];
        }

        if ( ! empty( $meta['navigation'] ) ) {
            $new_meta['config']['arrows'] = $meta['navigation'] ? 1 : 0;
        } else {
            $new_meta['config']['arrows'] = 0;
        }

        if ( ! empty( $meta['control'] ) ) {
            $new_meta['config']['control'] = $meta['control'] ? 1 : 0;
        } else {
            $new_meta['config']['control'] = 0;
        }

        if ( ! empty( $meta['keyboard'] ) ) {
            $new_meta['config']['keyboard'] = $meta['keyboard'];
        }

        if ( ! empty( $meta['pauseplay'] ) ) {
            $new_meta['config']['pauseplay'] = $meta['pauseplay'];
        }

        if ( ! empty( $meta['random'] ) ) {
            $new_meta['config']['random'] = $meta['random'];
        }

        if ( ! empty( $meta['number'] ) ) {
            $new_meta['config']['start'] = $meta['number'];
        }

        if ( ! empty( $meta['loop'] ) ) {
            $new_meta['config']['loop'] = $meta['loop'];
        }

        if ( ! empty( $meta['hover'] ) ) {
            $new_meta['config']['hover'] = $meta['hover'];
        }

        if ( ! empty( $meta['css'] ) ) {
            $new_meta['config']['css'] = $meta['css'];
        }

        if ( ! empty( $meta['smooth'] ) ) {
            $new_meta['config']['smooth'] = $meta['smooth'];
        }

        if ( ! empty( $meta['delay'] ) ) {
            $new_meta['config']['delay'] = $meta['delay'];
        }

        // Set to the classic theme to keep people from going nuts with a theme change.
        if ( ! empty( $meta['theme'] ) && 'metro' == $meta['theme'] ) {
            $new_meta['config']['slider_theme'] = 'metro';
        } else {
            $new_meta['config']['slider_theme'] = 'classic';
        }

        // Grab all attachments and add them to the slider.
        $attachments = get_posts(
            array(
    			'orderby' 		 => 'menu_order',
				'order' 		 => 'ASC',
				'post_type' 	 => 'attachment',
				'post_parent' 	 => $slider->ID,
				'post_status' 	 => null,
				'posts_per_page' => -1
            )
        );

        // Loop through attachments and add them to the slider.
        foreach ( (array) $attachments as $slide ) {
            switch ( $slide->post_mime_type ) {
                case 'soliloquy/video' :
                    $new_meta['slider'][$slide->ID] = array(
                        'status'  => 'active',
                        'id'      => $slide->ID,
                        'src'     => '',
                        'title'   => isset( $slide->post_title ) ? $slide->post_title : '',
                        'link'    => '',
                        'url'     => isset( $slide->post_content ) ? $slide->post_content : '',
                        'thumb'   => '',
                        'caption' => isset( $slide->post_excerpt ) ? $slide->post_excerpt : '',
                        'type'    => 'video'
                    );
                    break;
                case 'soliloquy/html' :
                    $new_meta['slider'][$slide->ID] = array(
                        'status' => 'active',
                        'id'     => $slide->ID,
                        'src'    => '',
                        'title'  => isset( $slide->post_title ) ? $slide->post_title : '',
                        'link'   => '',
                        'code'   => isset( $slide->post_content ) ? $slide->post_content : '',
                        'type'   => 'html'
                    );
                    break;
                default :
                    $url      = wp_get_attachment_image_src( $slide->ID, 'full' );
                    $alt_text = get_post_meta( $slide->ID, '_wp_attachment_image_alt', true );
                    $new_meta['slider'][$slide->ID] = array(
                        'status'  => 'active',
                        'id'      => $slide->ID,
                        'src'     => isset( $url[0] ) ? esc_url( $url[0] ) : '',
                        'title'   => get_the_title( $slide->ID ),
                        'link'    => get_post_meta( $slide->ID, '_soliloquy_image_link', true ),
                        'linktab' => get_post_meta( $slider->ID, '_soliloquy_image_link_tab', true ),
                        'alt'     => ! empty( $alt_text ) ? $alt_text : get_the_title( $slide->ID ),
                        'caption' => ! empty( $slide->post_excerpt ) ? $slide->post_excerpt : '',
                        'filter'  => get_post_meta( $slide->ID, '_soliloquy_filters_image_filter', true ),
                        'type'    => 'image'
                    );
                    break;
            }
        }

        // Convert v1 Pinterest addon data to v2 if necessary.
        $pinterest = get_post_meta( $slider->ID, '_soliloquy_pinterest', true );

        if ( ! empty( $pinterest['enable'] ) ) {
            $new_meta['config']['pinterest'] = $pinterest['enable'];
        }

        if ( ! empty( $pinterest['position'] ) ) {
            $new_meta['config']['pinterest_position'] = str_replace( '-', '_', $pinterest['position'] );
        }

        // Convert v1 carousel addon to v2 if necessary.
        $carousel = get_post_meta( $slider->ID, '_soliloquy_carousel', true );

        if ( ! empty( $carousel['width'] ) ) {
            $new_meta['config']['carousel'] = 1;
            $new_meta['config']['carousel_width'] = $carousel['width'];
        }

        if ( ! empty( $carousel['margin'] ) ) {
            $new_meta['config']['carousel_margin'] = $carousel['margin'];
        }

        if ( ! empty( $carousel['mminimum'] ) ) {
            $new_meta['config']['carousel_min'] = $carousel['minimum'];
        }

        if ( ! empty( $carousel['maximum'] ) ) {
            $new_meta['config']['carousel_maximum'] = $carousel['maximum'];
        }

        if ( ! empty( $carousel['move'] ) ) {
            $new_meta['config']['carousel_move'] = $carousel['move'];
        }

        // Convert v1 thumbnails addon to v2 if necessary.
        $thumbnails = get_post_meta( $slider->ID, '_soliloquy_thumbnails', true );

        if ( ! empty( $thumbnails['use'] ) ) {
            $new_meta['config']['thumbnails'] = $thumbnails['use'];
        }

        if ( ! empty( $thumbnails['width'] ) ) {
            $new_meta['config']['thumbnails_width'] = $thumbnails['width'];
        }

        if ( ! empty( $thumbnails['margin'] ) ) {
            $new_meta['config']['thumbnails_margin'] = $thumbnails['margin'];
        }

        if ( ! empty( $thumbnails['minimum'] ) ) {
            $new_meta['config']['thumbnails_num'] = $thumbnails['minimum'];
        }

        if ( ! empty( $thumbnails['position'] ) ) {
            $new_meta['config']['thumbnails_position'] = $thumbnails['position'];
        }

        // Convert v1 featured content to v 2 if necessary.
        $fc = get_post_meta( $slider->ID, '_soliloquy_fc', true );

        if ( ! empty( $meta['type'] ) && 'featured' == $meta['type'] ) {
            $new_meta['config']['type'] = 'fc';
        }

        if ( ! empty( $fc['post_types'] ) ) {
            $new_meta['config']['fc_post_types'] = $fc['post_types'];
        }

        if ( ! empty( $fc['terms'] ) ) {
            $new_meta['config']['fc_terms'] = $fc['terms'];
        }

        if ( ! empty( $fc['query'] ) ) {
            $new_meta['config']['fc_query'] = $fc['query'];
        }

        if ( ! empty( $fc['include_exclude'] ) ) {
            $new_meta['config']['fc_inc_ex'] = $fc['include_exclude'];
        }

        if ( ! empty( $fc['orderby'] ) ) {
            $new_meta['config']['fc_orderby'] = $fc['orderby'];
        }

        if ( ! empty( $fc['order'] ) ) {
            $new_meta['config']['fc_order'] = $fc['order'];
        }

        if ( ! empty( $fc['number'] ) ) {
            $new_meta['config']['fc_number'] = $fc['number'];
        }

        if ( ! empty( $fc['offset'] ) ) {
            $new_meta['config']['fc_offset'] = $fc['offset'];
        }

        if ( ! empty( $fc['post_status'] ) ) {
            $new_meta['config']['fc_status'] = $fc['post_status'];
        }

        if ( ! empty( $fc['post_url'] ) ) {
            $new_meta['config']['fc_post_url'] = $fc['post_url'];
        }

        if ( ! empty( $fc['post_title'] ) ) {
            $new_meta['config']['fc_post_title'] = $fc['post_title'];
        }

        if ( ! empty( $fc['post_title_link'] ) ) {
            $new_meta['config']['fc_post_title_link'] = $fc['post_title_link'];
        }

        if ( ! empty( $fc['content_type'] ) ) {
            $new_meta['config']['fc_content_type'] = str_replace( '-', '_', $fc['content_type'] );
        }

        if ( ! empty( $fc['post_content_length'] ) ) {
            $new_meta['config']['fc_content_length'] = $fc['post_content_length'];
        }

        if ( ! empty( $fc['ellipses'] ) ) {
            $new_meta['config']['fc_content_ellipses'] = $fc['ellipses'];
        }

        if ( ! empty( $fc['read_more'] ) ) {
            $new_meta['config']['fc_read_more'] = $fc['read_more'];
        }

        if ( ! empty( $fc['read_more_text'] ) ) {
            $new_meta['config']['fc_read_more_text'] = $fc['read_more_text'];
        }

        if ( ! empty( $fc['fallback'] ) ) {
            $new_meta['config']['fc_fallback'] = $fc['fallback'];
        }

        // Convert v1 Instagram addon to v2 if necessary.
        $instagram = get_post_meta( $slider->ID, '_soliloquy_instagram', true );

        // Update the Instagram db option from v1 to v2 if v2 does not already exist.
        $v2_auth = get_option( 'soliloquy_instagram' );

        if ( ! $v2_auth || empty( $v2_auth['token'] ) || empty( $v2_auth['id'] ) ) {
            $v1_auth  = get_option( 'soliloquy_instagram_data' );
            $new_auth = array();

            if ( ! empty( $v1_auth->access_token ) ) {
                $new_auth['token'] = $v1_auth->access_token;
            }

            if ( ! empty( $v1_auth->user->id ) ) {
                $new_auth['id'] = $v1_auth->user->id;
            }

            update_option( 'soliloquy_instagram', $new_auth );
        }

        if ( ! empty( $meta['type'] ) && 'instagram' == $meta['type'] ) {
            $new_meta['config']['type'] = 'instagram';
        }

        if ( ! empty( $instagram['number'] ) ) {
            $new_meta['config']['instagram_number'] = $instagram['number'];
        }

        if ( ! empty( $instagram['link'] ) ) {
            $new_meta['config']['instagram_link'] = $instagram['link'];
        }

        if ( ! empty( $instagram['caption'] ) ) {
            $new_meta['config']['instagram_caption'] = $instagram['caption'];
        }

        if ( ! empty( $instagram['random'] ) ) {
            $new_meta['config']['instagram_random'] = $instagram['random'];
        }

        if ( ! empty( $instagram['cache'] ) ) {
            $new_meta['config']['instagram_cache'] = $instagram['cache'];
        }

        // Convert v1 lightbox addon to v2 if necessary. This is a new lightbox engine so not a lot of crossover here.
        $lightbox = get_post_meta( $slider->ID, '_soliloquy_lightbox', true );

        if ( ! empty( $lightbox['auto'] ) ) {
            $new_meta['config']['lightbox'] = $lightbox['auto'];
        }

        if ( ! empty( $lightbox['lightbox_theme'] ) ) {
            $new_meta['config']['lightbox_theme'] = 'base';
        }

        // Update the post meta for the new slider.
        update_post_meta( $slider->ID, '_sol_slider_data', $new_meta );

        // Force the post to update.
        wp_update_post( array( 'ID' => $slider->ID, 'post_type' => 'soliloquy' ) );

        // Flush caches for any sliders.
        Soliloquy_Common::get_instance()->flush_slider_caches( $slider->ID, $new_meta['config']['slug'] );
    }

    // Now grab any v2 sliders and convert the post type back to the proper system.
    $v2_sliders = get_posts(
        array(
            'post_type'      => 'soliloquyv2',
            'posts_per_page' => -1
        )
    );

    // Loop through the sliders, grab the data, delete and backwards convert them back to 'soliloquy' post type.
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

        // Flush caches for any sliders.
        Soliloquy_Common::get_instance()->flush_slider_caches( $slider->ID );
    }

    // Turn off cache suspension and flush the cache to remove any cache inconsistencies.
    wp_suspend_cache_invalidation( false );
    wp_cache_flush();

    // Update the option to signify that upgrading is complete.
    update_option( 'soliloquy_upgrade', true );

    // Send back the response.
    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_soliloquy_change_type', 'soliloquy_ajax_change_type' );
/**
 * Changes the type of slider to the user selection.
 *
 * @since 1.0.0
 */
function soliloquy_ajax_change_type() {

    // Run a security check first.
    check_ajax_referer( 'soliloquy-change-type', 'nonce' );

    // Prepare variables.
    $post_id = absint( $_POST['post_id'] );
    $post    = get_post( $post_id );
    $type    = stripslashes( $_POST['type'] );

    // Retrieve the data for the type selected.
    ob_start();
    $instance = Soliloquy_Metaboxes::get_instance();
    $instance->images_display( $type, $post );
    $html = ob_get_clean();

    // Send back the response.
    echo json_encode( array( 'type' => $type, 'html' => $html ) );
    die;

}

add_action( 'wp_ajax_soliloquy_load_image', 'soliloquy_ajax_load_image' );
/**
 * Loads an image into a slider.
 *
 * @since 1.0.0
 */
function soliloquy_ajax_load_image() {

    // Run a security check first.
    check_ajax_referer( 'soliloquy-load-image', 'nonce' );

    // Prepare variables.
    $id      = absint( $_POST['id'] );
    $post_id = absint( $_POST['post_id'] );

    // Set post meta to show that this image is attached to one or more Soliloquy sliders.
    $has_slider = get_post_meta( $id, '_sol_has_slider', true );
    if ( empty( $has_slider ) ) {
        $has_slider = array();
    }

    $has_slider[] = $post_id;
    update_post_meta( $id, '_sol_has_slider', $has_slider );

    // Set post meta to show that this image is attached to a slider on this page.
    $in_slider = get_post_meta( $post_id, '_sol_in_slider', true );
    if ( empty( $in_slider ) ) {
        $in_slider = array();
    }

    $in_slider[] = $id;
    update_post_meta( $post_id, '_sol_in_slider', $in_slider );

    // Set data and order of image in slider.
    $slider_data = get_post_meta( $post_id, '_sol_slider_data', true );
    if ( empty( $slider_data ) ) {
        $slider_data = array();
    }

    // If no slider ID has been set, set it now.
    if ( empty( $slider_data['id'] ) ) {
        $slider_data['id'] = $post_id;
    }

    // Set data and update the meta information.
    $slider_data = soliloquy_ajax_prepare_slider_data( $slider_data, $id );
    update_post_meta( $post_id, '_sol_slider_data', $slider_data );

    // Run hook before building out the item.
    do_action( 'soliloquy_ajax_load_image', $id, $post_id );

    // Build out the individual HTML output for the slider image that has just been uploaded.
    $html = Soliloquy_Metaboxes::get_instance()->get_slider_item( $id, $slider_data['slider'][$id], 'image', $post_id );

    // Flush the slider cache.
    Soliloquy_Common::get_instance()->flush_slider_caches( $post_id );

    echo json_encode( $html );
    die;

}

add_action( 'wp_ajax_soliloquy_load_library', 'soliloquy_ajax_load_library' );
/**
 * Loads the Media Library images into the media modal window for selection.
 *
 * @since 1.0.0
 */
function soliloquy_ajax_load_library() {

    // Run a security check first.
    check_ajax_referer( 'soliloquy-load-slider', 'nonce' );

    // Prepare variables.
    $offset  = (int) $_POST['offset'];
    $search  = trim( stripslashes( $_POST['search'] ) );
    $post_id = absint( $_POST['post_id'] );
    $html    = '';

    // Build args
    $args = array(
        'post_type'     => 'attachment', 
        'post_mime_type'=> 'image', 
        'post_status'   => 'any', 
        'posts_per_page'=> 20, 
        'offset'        => $offset,
    );

    // Add search term to args if not empty
    if ( ! empty( $search ) ) {
        $args['s'] = $search;
    }

    // Grab the library contents
    $library = get_posts( $args );
    if ( $library ) {
        foreach ( (array) $library as $image ) {
            $has_slider = get_post_meta( $image->ID, '_sol_has_slider', true );
            $class       = $has_slider && in_array( $post_id, (array) $has_slider ) ? ' selected soliloquy-in-slider' : '';

            $html .= '<li class="attachment' . $class . '" data-attachment-id="' . absint( $image->ID ) . '">';
                $html .= '<div class="attachment-preview landscape">';
                    $html .= '<div class="thumbnail">';
                        $html .= '<div class="centered">';
                            $src = wp_get_attachment_image_src( $image->ID, 'thumbnail' );
                            $html .= '<img src="' . esc_url( $src[0] ) . '" />';
                        $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<a class="check" href="#"><div class="media-modal-icon"></div></a>';
                $html .= '</div>';
            $html .= '</li>';
        }
    }

    echo json_encode( array( 'html' => stripslashes( $html ) ) );
    die;

}

add_action( 'wp_ajax_soliloquy_insert_slides', 'soliloquy_ajax_insert_slides' );
/**
 * Inserts one or more slides into a slider.
 *
 * @since 1.0.0
 */
function soliloquy_ajax_insert_slides() {

    // Run a security check first.
    check_ajax_referer( 'soliloquy-insert-images', 'nonce' );

    // Prepare variables.
    $images  = ! empty( $_POST['images'] ) ? stripslashes_deep( (array) $_POST['images'] ) : array();
    $videos  = ! empty( $_POST['videos'] ) ? stripslashes_deep( (array) $_POST['videos'] ) : array();
    $html    = ! empty( $_POST['html'] )   ? stripslashes_deep( (array) $_POST['html'] )   : array();
    $post_id = absint( $_POST['post_id'] );

    // Grab and update any slider data if necessary.
    $in_slider = get_post_meta( $post_id, '_sol_in_slider', true );
    if ( empty( $in_slider ) ) {
        $in_slider = array();
    }

    // Set data and order of image in slider.
    $slider_data = get_post_meta( $post_id, '_sol_slider_data', true );
    if ( empty( $slider_data ) ) {
        $slider_data = array();
    }

    // If no slider ID has been set, set it now.
    if ( empty( $slider_data['id'] ) ) {
        $slider_data['id'] = $post_id;
    }

    // Loop through the images and add them to the slider.
    foreach ( (array) $images as $i => $id ) {
        // Update the attachment image post meta first.
        $has_slider = get_post_meta( $id, '_sol_has_slider', true );
        if ( empty( $has_slider ) ) {
            $has_slider = array();
        }

        $has_slider[] = $post_id;
        update_post_meta( $id, '_sol_has_slider', $has_slider );

        // Now add the image to the slider for this particular post.
        $in_slider[] = $id;
        $slider_data = soliloquy_ajax_prepare_slider_data( $slider_data, $id );
    }

    // Loop through the videos and add them to the slider.
    foreach ( (array) $videos as $i => $data ) {
        // Pass over if the main items necessary for the video are not set.
        if ( ! isset( $data['title'] ) || ! isset( $data['url'] ) || ! isset( $data['src'] ) ) {
            continue;
        }

        // Generate a custom ID for the video.
        // Note: we don't use sanitize_title_with_dashes as this retains special accented characters, resulting in jQuery errors
        // when subsequently trying to edit an exitsing slide.
        $id = $slider_data['id'] . '-' . preg_replace("/[^A-Za-z0-9]/", '', strtolower($data['title']));

        // Now add the image to the slider for this particular post.
        $in_slider[] = $id;
        $slider_data = soliloquy_ajax_prepare_slider_data( $slider_data, $id, 'video', $data );
    }

    // Loop through the HTML and add them to the slider.
    foreach ( (array) $html as $i => $data ) {
        // Pass over if the main items necessary for the video are not set.
        if ( empty( $data['title'] ) || empty( $data['code'] ) ) {
            continue;
        }

        // Generate a custom ID for the video.
        $id = $slider_data['id'] . '-' . preg_replace("/[^A-Za-z0-9]/", '', strtolower($data['title']));

        // Now add the image to the slider for this particular post.
        $in_slider[] = $id;
        $slider_data = soliloquy_ajax_prepare_slider_data( $slider_data, $id, 'html', $data );
    }

    // Update the slider data.
    update_post_meta( $post_id, '_sol_in_slider', $in_slider );
    update_post_meta( $post_id, '_sol_slider_data', $slider_data );

    // Run hook before finishing.
    do_action( 'soliloquy_ajax_insert_slides', $images, $videos, $html, $post_id );

    // Flush the slider cache.
    Soliloquy_Common::get_instance()->flush_slider_caches( $post_id );

    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_soliloquy_sort_images', 'soliloquy_ajax_sort_images' );
/**
 * Sorts images based on user-dragged position in the slider.
 *
 * @since 1.0.0
 */
function soliloquy_ajax_sort_images() {

    // Run a security check first.
    check_ajax_referer( 'soliloquy-sort', 'nonce' );

    // Prepare variables.
    $order       = explode( ',', $_POST['order'] );
    $post_id     = absint( $_POST['post_id'] );
    $slider_data = get_post_meta( $post_id, '_sol_slider_data', true );
    
    // Copy the slider config, removing the slides
    $new_order   = $slider_data;
    unset( $new_order['slider'] );
    $new_order['slider'] = array();

    // Loop through the order and generate a new array based on order received.
    foreach ( $order as $id ) {
        $new_order['slider'][$id] = $slider_data['slider'][$id];
    }

    // Update the slider data.
    update_post_meta( $post_id, '_sol_slider_data', $new_order );

    // Flush the slider cache.
    Soliloquy_Common::get_instance()->flush_slider_caches( $post_id );

    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_soliloquy_remove_slide', 'soliloquy_ajax_remove_slide' );
/**
 * Removes an image from a slider.
 *
 * @since 1.0.0
 */
function soliloquy_ajax_remove_slide() {

    // Run a security check first.
    check_ajax_referer( 'soliloquy-remove-slide', 'nonce' );

    // Prepare variables.
    $post_id     = absint( $_POST['post_id'] );
    $attach_id   = trim( $_POST['attachment_id'] );
    $slider_data = get_post_meta( $post_id, '_sol_slider_data', true );
    $in_slider   = get_post_meta( $post_id, '_sol_in_slider', true );
    $has_slider  = get_post_meta( $attach_id, '_sol_has_slider', true );

    // Unset the image from the slider, in_slider and has_slider checkers.
    unset( $slider_data['slider'][$attach_id] );

    if ( ( $key = array_search( $attach_id, (array) $in_slider ) ) !== false ) {
        unset( $in_slider[$key] );
    }

    if ( ( $key = array_search( $post_id, (array) $has_slider ) ) !== false ) {
        unset( $has_slider[$key] );
    }

    // Update the slider data.
    update_post_meta( $post_id, '_sol_slider_data', $slider_data );
    update_post_meta( $post_id, '_sol_in_slider', $in_slider );
    update_post_meta( $attach_id, '_sol_has_slider', $has_slider );

    // Run hook before finishing the reponse.
    do_action( 'soliloquy_ajax_remove_slide', $attach_id, $post_id );

    // Flush the slider cache.
    Soliloquy_Common::get_instance()->flush_slider_caches( $post_id );

    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_soliloquy_save_meta', 'soliloquy_ajax_save_meta' );
/**
 * Saves the metadata for an image in a slider.
 *
 * @since 1.0.0
 */
function soliloquy_ajax_save_meta() {

    // Run a security check first.
    check_ajax_referer( 'soliloquy-save-meta', 'nonce' );

    // Prepare variables.
    $post_id     = absint( $_POST['post_id'] );
    $attach_id   = $_POST['attach_id'];
    $meta        = $_POST['meta'];
    $slider_data = get_post_meta( $post_id, '_sol_slider_data', true );

    // Go ahead and ensure to store the attachment ID.
    $slider_data['slider'][$attach_id]['id'] = $attach_id;

    // Save the different types of default meta fields for images, videos and HTML slides.
    if ( isset( $meta['status'] ) ) {
        $slider_data['slider'][$attach_id]['status'] = trim( esc_html( $meta['status'] ) );
    }
    
    if ( isset( $meta['title'] ) ) {
        $slider_data['slider'][$attach_id]['title'] = trim( esc_html( $meta['title'] ) );
    }

    if ( isset( $meta['alt'] ) ) {
        $slider_data['slider'][$attach_id]['alt'] = trim( esc_html( $meta['alt'] ) );
    }

    if ( isset( $meta['link'] ) ) {
        $slider_data['slider'][$attach_id]['link'] = esc_url( $meta['link'] );
    }
    
    if ( isset( $meta['linktab'] ) && $meta['linktab'] ) {
        $slider_data['slider'][$attach_id]['linktab'] = 1;
    } else {
	    $slider_data['slider'][$attach_id]['linktab'] = 0;
    }

    if ( isset( $meta['caption'] ) ) {
        $slider_data['slider'][$attach_id]['caption'] = trim( $meta['caption'] );
    }

    if ( isset( $meta['url'] ) ) {
        $slider_data['slider'][$attach_id]['url'] = esc_url( $meta['url'] );
    }

    if ( isset( $meta['src'] ) ) {
        $slider_data['slider'][$attach_id]['src'] = esc_url( $meta['src'] );
    }

    if ( isset( $meta['code'] ) ) {
        $slider_data['slider'][$attach_id]['code'] = trim( $meta['code'] );
    }

    // Allow filtering of meta before saving.
    $slider_data = apply_filters( 'soliloquy_ajax_save_meta', $slider_data, $meta, $attach_id, $post_id );

    // Update the slider data.
    update_post_meta( $post_id, '_sol_slider_data', $slider_data );

    // Flush the slider cache.
    Soliloquy_Common::get_instance()->flush_slider_caches( $post_id );

    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_soliloquy_refresh', 'soliloquy_ajax_refresh' );
/**
 * Refreshes the DOM view for a slider.
 *
 * @since 1.0.0
 */
function soliloquy_ajax_refresh() {

    // Run a security check first.
    check_ajax_referer( 'soliloquy-refresh', 'nonce' );

    // Prepare variables.
    $post_id = absint( $_POST['post_id'] );
    $slider = '';

    // Grab all slider data.
    $slider_data = get_post_meta( $post_id, '_sol_slider_data', true );

    // If there are no slider items, don't do anything.
    if ( empty( $slider_data ) || empty( $slider_data['slider'] ) ) {
        echo json_encode( array( 'error' => true ) );
        die;
    }

    // Loop through the data and build out the slider view.
    foreach ( (array) $slider_data['slider'] as $id => $data ) {
        $slider .= Soliloquy_Metaboxes::get_instance()->get_slider_item( $id, $data, $data['type'], $post_id );
    }

    echo json_encode( array( 'success' => $slider ) );
    die;

}

add_action( 'wp_ajax_soliloquy_load_slider_data', 'soliloquy_ajax_load_slider_data' );
/**
 * Retrieves and return slider data for the specified ID.
 *
 * @since 1.0.0
 */
function soliloquy_ajax_load_slider_data() {

    // Prepare variables and grab the slider data.
    $slider_id   = absint( $_POST['post_id'] );
    $slider_data = get_post_meta( $slider_id, '_sol_slider_data', true );

    // Send back the slider data.
    echo json_encode( $slider_data );
    die;

}

add_action( 'wp_ajax_soliloquy_install_addon', 'soliloquy_ajax_install_addon' );
/**
 * Installs an Soliloquy addon.
 *
 * @since 1.0.0
 */
function soliloquy_ajax_install_addon() {

    // Run a security check first.
    check_ajax_referer( 'soliloquy-install', 'nonce' );

    // Install the addon.
    if ( isset( $_POST['plugin'] ) ) {
        $download_url = $_POST['plugin'];
        global $hook_suffix;

        // Set the current screen to avoid undefined notices.
        set_current_screen();

        // Prepare variables.
        $method = '';
        $url    = add_query_arg(
            array(
                'page' => 'soliloquy-settings'
            ),
            admin_url( 'admin.php' )
        );
        $url = esc_url( $url );

        // Start output bufferring to catch the filesystem form if credentials are needed.
        ob_start();
        if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, null ) ) ) {
            $form = ob_get_clean();
            echo json_encode( array( 'form' => $form ) );
            die;
        }

        // If we are not authenticated, make it happen now.
        if ( ! WP_Filesystem( $creds ) ) {
            ob_start();
            request_filesystem_credentials( $url, $method, true, false, null );
            $form = ob_get_clean();
            echo json_encode( array( 'form' => $form ) );
            die;
        }

        // We do not need any extra credentials if we have gotten this far, so let's install the plugin.
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once plugin_dir_path( Soliloquy::get_instance()->file ) . 'includes/admin/skin.php';

        // Create the plugin upgrader with our custom skin.
        $installer = new Plugin_Upgrader( $skin = new Soliloquy_Skin() );
        $installer->install( $download_url );

        // Flush the cache and return the newly installed plugin basename.
        wp_cache_flush();
        if ( $installer->plugin_info() ) {
            $plugin_basename = $installer->plugin_info();
            echo json_encode( array( 'plugin' => $plugin_basename ) );
            die;
        }
    }

    // Send back a response.
    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_soliloquy_activate_addon', 'soliloquy_ajax_activate_addon' );
/**
 * Activates an Soliloquy addon.
 *
 * @since 1.0.0
 */
function soliloquy_ajax_activate_addon() {

    // Run a security check first.
    check_ajax_referer( 'soliloquy-activate', 'nonce' );

    // Activate the addon.
    if ( isset( $_POST['plugin'] ) ) {
        $activate = activate_plugin( $_POST['plugin'] );

        if ( is_wp_error( $activate ) ) {
            echo json_encode( array( 'error' => $activate->get_error_message() ) );
            die;
        }
    }

    echo json_encode( true );
    die;

}

add_action( 'wp_ajax_soliloquy_deactivate_addon', 'soliloquy_ajax_deactivate_addon' );
/**
 * Deactivates an Soliloquy addon.
 *
 * @since 1.0.0
 */
function soliloquy_ajax_deactivate_addon() {

    // Run a security check first.
    check_ajax_referer( 'soliloquy-deactivate', 'nonce' );

    // Deactivate the addon.
    if ( isset( $_POST['plugin'] ) ) {
        $deactivate = deactivate_plugins( $_POST['plugin'] );
    }

    echo json_encode( true );
    die;

}

/**
 * Helper function to prepare the metadata for an image in a slider.
 *
 * @since 1.0.0
 *
 * @param array $slider_data  Array of data for the slider.
 * @param int $id             The Post ID to prepare data for.
 * @param string $type        The type of slide to prepare (defaults to image).
 * @param array $data         Data to be used for the slide.
 * @return array $slider_data Amended slider data with updated image metadata.
 */
function soliloquy_ajax_prepare_slider_data( $slider_data, $id, $type = 'image', $data = array() ) {

	// Get global option for slide status
	$publishingDefault = get_option( 'soliloquy-publishing-default', 'pending' );
	
    switch ( $type ) {
        case 'image' :
            $attachment = get_post( $id );
            $url        = wp_get_attachment_image_src( $id, 'full' );
            $alt_text   = get_post_meta( $id, '_wp_attachment_image_alt', true );
            $slide = array(
                'status'  		=> $publishingDefault,
                'id'      		=> $id,
                'attachment_id' => $id,
                'src'     		=> isset( $url[0] ) ? esc_url( $url[0] ) : '',
                'title'   		=> get_the_title( $id ),
                'link'    		=> '',
                'alt'     		=> ! empty( $alt_text ) ? $alt_text : get_the_title( $id ),
                'caption' 		=> ! empty( $attachment->post_excerpt ) ? $attachment->post_excerpt : '',
                'type'    		=> $type
            );
            break;
        case 'video' :
            $slide = array(
                'status'  => $publishingDefault,
                'id'      => $id,
                'src'     => isset( $data['src'] ) ? esc_url( $data['src'] ) : '',
                'title'   => isset( $data['title'] ) ? esc_html( $data['title'] ) : '',
                'url'     => isset( $data['url'] ) ? esc_url( $data['url'] ) : '',
                'caption' => isset( $data['caption'] ) ? trim( $data['caption'] ) : '',
                'type'    => $type
            );
            
            // If no thumbnail specified, attempt to get it from the video
			if ( empty( $data['src'] ) ) {
	            // Get Video Thumbnail
		        if ( preg_match( '#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#', $data['url'], $y_matches ) ) {
		            // YouTube
		            $videoID = $y_matches[0];
		            
		            // Get HD or SD thumbnail
		            $data['src'] = soliloquy_ajax_get_youtube_thumbnail_url($videoID);
		        } elseif ( preg_match( '#(?:https?:\/\/(?:[\w]+\.)*vimeo\.com(?:[\/\w]*\/videos?)?\/([0-9]+)[^\s]*)#i', $data['url'], $v_matches ) ) {
		            // Vimeo
		            $videoID = $v_matches[1];
		            
		            // Get highest resolution thumbnail
		            $data['src'] = soliloquy_ajax_get_vimeo_thumbnail_url($videoID);
		        } elseif ( preg_match( '/https?:\/\/(.+)?(wistia.com|wi.st)\/.*/i', $data['url'], $w_matches ) ) {
		            // Wistia
		            $parts = explode( '/', $w_matches[0] );
	                $videoID = array_pop( $parts );
	                
	                // Get image from API
	                $res = wp_remote_get( 'http://fast.wistia.net/oembed?url=' . urlencode( $item['url'] ) );
	                $bod = wp_remote_retrieve_body( $res );
	                $api = json_decode( $bod, true );
	                if ( ! empty( $api['thumbnail_url'] ) ) {
	                    $data['src'] = remove_query_arg( 'image_crop_resized', $api['thumbnail_url'] );
	                }
		        } else {
		            // Unknown
		            $videoID = false;
		        }
		        
		        // If a thumbnail was found, import it to the local filesystem
		        $stream = Soliloquy_Import::get_instance()->import_remote_image( $data['src'], $data, $id, 0, true );
                if ( ! is_wp_error( $stream ) ) {
    		        if ( empty( $stream['error'] ) || isset( $stream['error'] ) && ! $stream['error'] ) {
    	                $slide['attachment_id'] = $stream['attachment_id'];
    	                $slide['src'] = $stream['url'];
    	            }
                }
	        }
	            
            break;
        case 'html' :
            $slide = array(
                'status' => $publishingDefault,
                'id'     => $id,
                'title'  => isset( $data['title'] ) ? esc_html( $data['title'] ) : '',
                'code'   => isset( $data['code'] ) ? trim( $data['code'] ) : '',
                'type'   => $type
            );
            break;
    }

    // Add this slide to the start or end of the slider, depending on the setting
    $slide_position = get_option( 'soliloquy_slide_position' );
    switch ( $slide_position ) {
        case 'before':
            // Add slide to start of slides array
            $slider_data['slider'] = array( $id => $slide ) + $slider_data['slider'];
            break;
        case 'after':
        default: 
            // Add slide, this will default to the end of the array
            $slider_data['slider'][ $id ] = $slide;
            break;
    }

    // Filter and return
    $slider_data = apply_filters( 'soliloquy_ajax_item_data', $slider_data, $id, $type );

    return $slider_data;

}

/**
* Attempts to get a HD thumbnail URL for the given video ID.
* If a 120x90 grey placeholder image is returned, the video isn't HD, so
* the function will return the SD thumbnail URL
*
* @since 2.3.9.7
*
* @param string $videoID YouTube Video ID
* @return string HD or SD Thumbnail URL
*/
function soliloquy_ajax_get_youtube_thumbnail_url($videoID) {
	
	// Determine video URL
	$prefix = is_ssl() ? 'https' : 'http';
	$baseURL = $prefix . '://img.youtube.com/vi/' . $videoID . '/';
	$hdURL = $baseURL . 'maxresdefault.jpg'; // 1080p or 720p
	$sdURL = $baseURL . '0.jpg'; // 480x360
	
	// Get HD image from YouTube
	$imageData = wp_remote_get( $hdURL, array(
		'timeout' => 10,	
	) );
	
	// Check request worked
	if ( is_wp_error( $imageData ) || !isset( $imageData['body'] ) ) {
		// Failed - fallback to SD Thumbnail
		return $sdURL;	
	}
	
	// Get image size
	$imageSize = getimagesizefromstring( $imageData['body'] );	
	
	// Check request worked
	if ( !is_array( $imageSize ) ) {
		// Failed - fallback to SD Thumbnail
		return $sdURL;	
	}
	
	// Check image size isn't 120x90
	if ( $imageSize[0] == 120 && $imageSize[1] == 90) {
		// Failed - fallback to SD Thumbnail
		return $sdURL;
	}
	
	// Image is a valid YouTube HD thumbnail
	return $hdURL;
	
}

/**
* Attempts to get the highest resolution thumbnail URL for the given video ID.
*
* @since 2.3.9.7
*
* @param string $videoID Vimeo Video ID
* @return string Best resolution URL
*/
function soliloquy_ajax_get_vimeo_thumbnail_url($videoID) {
	
	// Get existing access token
	$vimeoAccessToken = get_option( 'soliloquy_vimeo_access_token' );
	
	// Load Vimeo API
	$vimeo = new Soliloquy_Vimeo( '5edbf52df73b6834db186409f88d2108df6a3d7f', '54e233c7ec90b22ad7cc77875b9a5a9d3083fa08' );
	$vimeo->setToken( $vimeoAccessToken );
	
	// Attempt to get video
	$response = $vimeo->request( '/videos/' . $videoID . '/pictures' );
	
	// Check response
	if ( $response['status'] != 200 ) {
		// May need a new access token
		// Clear old token + request a new one
		$vimeo->setToken( '' );
		$token = $vimeo->clientCredentials();
		$vimeoAccessToken = $token['body']['access_token'];
		$vimeo->setToken( $vimeoAccessToken );
		
		// Store new token in options data
		update_option( 'soliloquy_vimeo_access_token', $vimeoAccessToken );
		
		// Run request again
		$response = $vimeo->request( '/videos/' . $videoID . '/pictures' );
	}
	
	// Check response
	if ( $response['status'] != 200 ) {
		// Really a failure!
		return false;
	}
	
	// If here, we got the video details
	// Check thumbnails are in the response
	if ( !isset( $response['body']['data'] ) || !isset( $response['body']['data'][0] ) || !isset( $response['body']['data'][0]['sizes'] ) ) {
		return false;
	}
	
	// Get last item from the array index, as this is the highest resolution thumbnail
	$thumbnail = end( $response['body']['data'][0]['sizes'] );
	
	// Check thumbnail URL exists
	if ( !isset( $thumbnail['link'] ) ) {
		return false;
	}
	
	// Return thumbnail URL
	unset( $vimeo );
	return $thumbnail['link'];
	
}

add_action( 'wp_ajax_soliloquy_init_sliders', 'soliloquy_ajax_init_sliders' );
add_action( 'wp_ajax_nopriv_soliloquy_init_sliders', 'soliloquy_ajax_init_sliders' );
/**
 * Grabs JS and executes it for any uninitialised sliders on screen
 *
 * Used by soliloquyInitManually() JS function, which in turn is called
 * by AJAX requests e.g. after an Infinite Scroll event.
 *
 * @since 1.0.0
 */
function soliloquy_ajax_init_sliders() {

    // Run a security check first.
    check_ajax_referer( 'soliloquy-ajax-nonce', 'ajax_nonce' );

    // Check we have some slider IDs
    if ( ! isset( $_REQUEST['ids'] ) ) {
        die();
    }

    // Setup instance
    $instance = Soliloquy_Shortcode::get_instance();
    $base = Soliloquy::get_instance();

    // Build JS for each slider
    $js = '';
    foreach ( $_REQUEST['ids'] as $slider_id ) {
        // Get slider
        $data = $base->get_slider( $slider_id );

        // If no slider found, skip
        if ( ! $data ) {
            continue;
        }

        // Build JS for this slider
        $js .= $instance->slider_init_single( $data, true );
    }

    // Output JS
    echo $js;
    die();

}