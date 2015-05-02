<?php

/*
#
#   ADD CUSTOM CONTENT TYPES 
#
*/
    /**
     * Custom Post Types, on the fly creation
     *
     **/
    // function lm_custom_post_type_creator($post_type_name, $description, $public, $menu_position, $supports, $has_archive, $irreg_plural, $slug) {
    //   if ($irreg_plural) {$plural = 's';} else {$plural = '';}
    //   $labels = array(
    //     'name'               => _x( $post_type_name, 'post type general name' ),
    //     'singular_name'      => _x( strtolower($post_type_name), 'post type singular name' ),
    //     'add_new'            => _x( 'Add New', 'book' ),
    //     'add_new_item'       => __( 'Add New '.$post_type_name),
    //     'edit_item'          => __( 'Edit '.$post_type_name ),
    //     'new_item'           => __( 'New '.$post_type_name ),
    //     'all_items'          => __( 'All '.$post_type_name.$plural ),
    //     'view_item'          => __( 'View '.$post_type_name ),
    //     'search_items'       => __( 'Search'.$post_type_name.$plural ),
    //     'not_found'          => __( 'No '.$post_type_name.$plural.' found' ),
    //     'not_found_in_trash' => __( 'No '.$post_type_name.$plural.' found in the Trash' ), 
    //     'parent_item_colon'  => '',
    //     'menu_name'          => $post_type_name
    //   );
    //   $args = array(
    //     'labels'        => $labels,
    //     'description'   => $description,
    //     'public'        => $public,
    //     'menu_position' => $menu_position,
    //     'supports'      => $supports,
    //     'rewrite' => array('slug' => $slug),
    //     'has_archive'   => $has_archive,
    //   );
    //   register_post_type( $post_type_name, $args ); 
    // }
    // add_action( 'init', lm_custom_post_type_creator('Vehicles', 'Holds our fleet vehicles', true, 4, array( 'title', 'editor', 'thumbnail' ), true, false, 'fleet'));
    // add_action( 'init', lm_custom_post_type_creator('Staff', 'Holds our staff specific data', true, 5, array( 'title', 'editor', 'thumbnail' ), true, false));
    // add_action( 'init', lm_custom_post_type_creator('Car Care Tips', 'Holds our car care tips.', true, 6, array( 'title', 'editor', 'thumbnail', 'excerpt' ), true, false));
    // add_action( 'init', lm_custom_post_type_creator('Car Care Videos', 'Holds our car care videos.', true, 7, array( 'title', 'editor', 'thumbnail' ), true, false));

/**
 * Adds a box to the main column on the Post and Page edit screens.
 */

    // function lm_add_meta_box() {
    //     add_meta_box(
    //         'lm_vehicles_capacity',
    //         __( 'Capacity', 'lm_textdomain' ),
    //         'lm_meta_box_callback1',
    //         'vehicles',//$screen
    //         'side',
    //         'high'
    //     );
    //     add_meta_box(
    //         'lm_vehicles_upselltext',
    //         __( 'Upsell Text', 'lm_textdomain' ),
    //         'lm_meta_box_callback2',
    //         'vehicles',//$screen
    //         'side',
    //         'high'
    //     );
    // }
    // add_action( 'add_meta_boxes', 'lm_add_meta_box' );

	/**
	 * Prints the box content.
	 * 
	 * @param WP_Post $post The object for the current post/page.
	 */

	    // function lm_meta_box_callback1( $post ) {
	    //     // Add an nonce field so we can check for it later.
	    //     wp_nonce_field( 'lm_meta_box', 'lm_meta_box_nonce' );
	    //     /*
	    //      * Use get_post_meta() to retrieve an existing value
	    //      * from the database and use the value for the form.
	    //      */
	    //     $value = get_post_meta( $post->ID, '_lm_meta_value_key1', true );
	    //     echo '<label for="lm_new_field1">';
	    //     _e( 'Description for this field', 'lm_textdomain' );
	    //     echo '</label> ';
	    //     echo '<input type="text" id="lm_new_field1" name="lm_new_field1" value="' . esc_attr( $value ) . '" size="25" />';
	    // }

	    // function lm_meta_box_callback2( $post ) {
	    //     // Add an nonce field so we can check for it later.
	    //     wp_nonce_field( 'lm_meta_box', 'lm_meta_box_nonce' );
	    //     /*
	    //      * Use get_post_meta() to retrieve an existing value
	    //      * from the database and use the value for the form.
	    //      */
	    //     $value = get_post_meta( $post->ID, '_lm_meta_value_key2', true );
	    //     echo '<label for="lm_new_field2">';
	    //     _e( 'Description for this field', 'lm_textdomain' );
	    //     echo '</label> ';
	    //     echo '<input type="text" id="lm_new_field2" name="lm_new_field2" value="' . esc_attr( $value ) . '" size="25" />';
	    // }
	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */

		// function lm_save_meta_box_data( $post_id ) {
		//     /*
		//      * We need to verify this came from our screen and with proper authorization,
		//      * because the save_post action can be triggered at other times.
		//      */
		//     // Check if our nonce is set.
		//     if ( ! isset( $_POST['lm_meta_box_nonce'] ) ) {
		//         return;
		//     }
		//     // Verify that the nonce is valid.
		//     if ( ! wp_verify_nonce( $_POST['lm_meta_box_nonce'], 'lm_meta_box' ) ) {
		//         return;
		//     }
		//     // If this is an autosave, our form has not been submitted, so we don't want to do anything.
		//     if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		//         return;
		//     }
		//     // Check the user's permissions.
		//     if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
		//         if ( ! current_user_can( 'edit_page', $post_id ) ) {
		//             return;
		//         }
		//     } else {
		//         if ( ! current_user_can( 'edit_post', $post_id ) ) {
		//             return;
		//         }
		//     }
		//     /* OK, it's safe for us to save the data now. */
		//     // Make sure that it is set.
		//     if ( ! isset( $_POST['lm_new_field1'] ) || ! isset( $_POST['lm_new_field2'] ) ) {
		//         return;
		//     }
		//     // Sanitize user input.
		//     $my_data1 = sanitize_text_field( $_POST['lm_new_field1'] );
		//     $my_data2 = sanitize_text_field( $_POST['lm_new_field2'] );
		//     // Update the meta field in the database.
		//     update_post_meta( $post_id, '_lm_meta_value_key1', $my_data1 );
		//     update_post_meta( $post_id, '_lm_meta_value_key2', $my_data2 );
		// }
		// add_action( 'save_post', 'lm_save_meta_box_data' );
	
	/*
	#
	#   Make Archives.php Include Custom Post Types
	#   http://css-tricks.com/snippets/wordpress/make-archives-php-include-custom-post-types/
	#
	*/
	/*
	    --    ADD CUSTOM POST TYPES HERE   --
	*/
	    // function namespace_add_custom_types( $query ) {
	    //   if( is_category() || is_tag() && empty( $query->query_vars['suppress_filters'] ) ) {
	    //         $query->set( 'post_type', array( 'post', 'post-type-name' ));
	    //         return $query;
	    //     }
	    // }
	    // add_filter( 'pre_get_posts', 'namespace_add_custom_types' );
	/*
	#
	#   Define what post types to search
	#   The hook needed to search ALL content
	#
	*/
	/*
	    --    ADD CUSTOM POST TYPES HERE   --
	*/
	    // function searchAll( $query ) {
	    //     if ( $query->is_search ) {
	    //         $query->set( 'post_type', array( 'post', 'page', 'feed', 'products', 'people'));
	    //     }
	    //     return $query;
	    // }
	    // add_filter( 'the_search_query', 'searchAll' );