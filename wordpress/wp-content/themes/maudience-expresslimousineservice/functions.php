<?php

//global $maudience_client_slug = '';
define( 'MAUDIENCE_CLIENT_SLUT', 'enter_client_name_here' );
require_once('lib/custom-post-types.php');
require_once('lib/maudience-phonenumber.php');

/*
#
#   REGISTER JS AND CSS
#
*/
    // function maudience_scripts() {
    //     wp_enqueue_script(
    //         'custom-js',
    //         get_stylesheet_directory_uri() . '/custom.js',
    //         array( 'jquery' )
    //     );
    //     if (is_front_page()) {
    //         wp_enqueue_script(
    //             'jssor',
    //             get_stylesheet_directory_uri() . '/js/jssor.js',
    //             array( 'jquery' )
    //         );
    //         wp_enqueue_script(
    //             'jssorslider',
    //             get_stylesheet_directory_uri() . '/js/jssor.slider.js',
    //             array( 'jquery' )
    //         );
    //     }
    //     wp_enqueue_script('jquery-ui-accordion');
    // }
    // add_action( 'wp_enqueue_scripts', 'maudience_scripts' );
    // function maudience_enqueue_parent_style() {
    //     wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    // }
    // add_action( 'wp_enqueue_scripts', 'maudience_enqueue_parent_style' );

/**
 * Enqueue scripts and styles
 */

    function maudience_scripts() {
        wp_enqueue_style( MAUDIENCE_CLIENT_SLUT.'-css', get_stylesheet_directory_uri()."lib/css/style.css" );
    }
    add_action( 'wp_enqueue_scripts', 'maudience_scripts', 15 );

/*
#
#   REGISTER SIDEBARS/WIDGET AREAS
#   
#
*/
    function maudience_widgets_init() {
        // register_sidebar( array(
        //     'name' => 'Pre Content Widget Area',
        //     'id' => 'pre-content-widget',
        //     'before_widget' => '<div id="pre-content-widget" class="pre-content-widget">',
        //     'after_widget' => '</div>',
        //     'before_title' => '<h2 class="rounded">',
        //     'after_title' => '</h2>',
        // ) );
    }
    add_action( 'widgets_init', 'maudience_widgets_init' );
/*
#
#   REGISTER MENUS
#   
#
*/
    function maudience_menus_init() {
      register_nav_menus(
        array(
          'social-media-navigation' => __( 'Social Media Navigation' )
        )
      );
    }
    add_action( 'init', 'maudience_menus_init' );

/*
#
#   WHITE LABEL
#
*/
        /* ADD ADMIN CSS STYLES HERE */

        function ma_custom_admin_styles() {

            // $menu_background_color = 'rgba(4, 55, 137,1)';
            // $menu_link_color = 'rgba(249,190,25,0.6)';
            // $menu_currentnav_color = 'rgba(249,190,25,1)';
            // $menu_linkhover_color_gradcolorone = '#f9f9f9';
            // $menu_linkhover_color_gradcolortwo = '#c9c9c9';
            $menu_background_color = 'add-color-here';
            $menu_link_color = 'add-color-here';
            $menu_currentnav_color = 'add-color-here';
            $menu_linkhover_color_gradcolorone = 'add-color-here';
            $menu_linkhover_color_gradcolortwo = 'add-color-here';

            echo '<style type="text/css">
               /* Styles here! */

                body {font-family: Futura, "Trebuchet MS", Arial, sans-serif;}

               /*change sidebar icon for testimonials, staff, tips, videos */
               /*

                uncomment and set custom post type admin icon styles here

                #menu-posts-testimonials .dashicons-admin-post:before,
                #menu-posts-testimonials .dashicons-format-standard:before { content:"\f155"; }
                #menu-posts-staff .dashicons-admin-post:before,
                #menu-posts-staff .dashicons-format-standard:before { content:"\f307"; }
                #menu-posts-carcarevideos .dashicons-admin-post:before,
                #menu-posts-carcarevideos .dashicons-format-standard:before { content:"\f126"; }
                #menu-posts-carcaretips .dashicons-admin-post:before,
                #menu-posts-carcaretips .dashicons-format-standard:before { content:"\f339"; }

                */
               
               /* change admin menu coloring */ 

                /*MENU BACKGROUND COLOR*/
                #adminmenu, #adminmenu .wp-submenu,
                #adminmenuback, #adminmenuwrap { background-color: '.$menu_background_color.'; }

                /*MENU LINK COLOR*/
                #adminmenu .wp-submenu a { color: '.$menu_link_color.'; }

                /*MENU CURRENT NAV COLOR*/
                #adminmenu .opensub .wp-submenu li.current a,
                #adminmenu .wp-submenu li.current, 
                #adminmenu .wp-submenu li.current a, 
                #adminmenu .wp-submenu li.current a:focus, 
                #adminmenu .wp-submenu li.current a:hover, 
                #adminmenu a.wp-has-current-submenu:focus+.wp-submenu li.current a { '.$menu_currentnav_color.'; }

                /*MENU LINK HOVER COLOR*/
                #adminmenu li.menu-top:hover,
                #adminmenu li.opensub>a.menu-top, 
                #adminmenu li>a.menu-top:focus { background: linear-gradient(to bottom,'.$menu_linkhover_color_gradcolorone.' 37%,'.$menu_linkhover_color_gradcolortwo.' 100%); }

             </style>';
        }
        add_action('admin_head', 'ma_custom_admin_styles');

        //* Replace WordPress login logo with your own
        function ma_custom_login_logo() {
            echo '<style type="text/css">
            body { font-family: Futura, "Trebuchet MS", Arial, sans-serif; }
            h1 a 
            { 
                background-image:url('.get_stylesheet_directory_uri().'/img/european-motors-menu-logo.png) !important; 
                background-size: 211px auto !important;
                height: 200px !important;
                width: 311px !important; 
                margin-bottom: 0 !important; 
                padding-bottom: 0 !important; 
            }
            .login form { margin-top: 10px !important; border: 1px solid #f9be19; }
            .login {background:#043789;}
            </style>';
        }
        add_action('login_head', 'ma_custom_login_logo');

        //* Change the URL of the WordPress login logo
        function ma_url_login_logo(){ return get_bloginfo( 'wpurl' ); }
        add_filter('login_headerurl', 'ma_url_login_logo');

        //* Login Screen: Change login logo hover text
        function ma_login_logo_url_title() { return 'Thank You For Working With Maudience'; }
        add_filter( 'login_headertitle', 'ma_login_logo_url_title' );

        //* Login Screen: Don't inform user which piece of credential was incorrect
        function ma_failed_login () { return 'The login information you have entered is incorrect. Please try again.'; }
        add_filter ( 'login_errors', 'ma_failed_login' );

        //* Modify the admin footer text
        function ma_modify_footer_admin () { echo '<span id="footer-meta"><a href="http://maudience.com" target="_blank">Thank You For Working With Maudience</a></span>'; }
        add_filter('admin_footer_text', 'ma_modify_footer_admin');

        //* Add theme info box into WordPress Dashboard
        function ma_add_dashboard_widgets() { wp_add_dashboard_widget('wp_dashboard_widget', 'Theme Details', 'ma_theme_info'); }
        add_action('wp_dashboard_setup', 'ma_add_dashboard_widgets' );

    /*
    #   Create widget info for above function: ma_add_dashboard_widgets
    */

        function ma_theme_info() {
          echo "
              <ul>
                  <li><strong>Developed By:</strong> MAudience</li>
                  <li><strong>Website:</strong> <a href='http://maudience.com'>http://www.maudience.com</a></li>
                  <li><strong>Developer Contact:</strong> <a href='mailto:pete@maudience.com'>pete@maudience.com</a></li>
              </ul>"
          ;
        }

/*
#
#   ENABLE SHORTCODE IN WIDGETS
#
*/

    add_filter('widget_text', 'do_shortcode');

/*
# SPEED OPTIMIZATIONS
# 
*/
    // Remove jquery migrate as is not needed
    if(!is_admin()) add_filter( 'wp_default_scripts', 'dequeue_jquery_migrate' );
    function dequeue_jquery_migrate( &$scripts){
        $scripts->remove( 'jquery');
        $scripts->add( 'jquery', false, array( 'jquery-core' ), '1.10.2' );
    }
    
    //load jquery from google
    if (!is_admin()) add_action("wp_enqueue_scripts", "maudience_jquery_enqueue", 11);
    function maudience_jquery_enqueue() {
        wp_deregister_script('jquery');
        // wp_register_script('jquery', "http" . ($_SERVER['SERVER_PORT'] == 443 ? "s" : "") . "://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js", false, null, true);
        wp_register_script('jquery', "http" . ($_SERVER['SERVER_PORT'] == 443 ? "s" : "") . "://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js", false, null, true);
        wp_enqueue_script('jquery');
    }

    //added lazy load styles to style.css so deregister
    // add_action( 'wp_print_styles', 'maudience_deregister_styles', 100 );
    // function maudience_deregister_styles() {
    //   wp_deregister_style( 'image-lazy-load-frontend' );
    // }

/*
#
#   SPEED OPTIMIZATIONS
#   -Load all fonts from google
#
#
*/
    function load_fonts() {
        wp_dequeue_style( 'twentytwelve-fonts' );
        wp_deregister_style( 'twentytwelve-fonts' );
        wp_register_style('googleFonts', 'http://fonts.googleapis.com/css?family=Signika:400,700|Open+Sans:400italic,700italic,400,700&amp;subset=latin,latin-ext');
        wp_enqueue_style( 'googleFonts');
    }
    add_action('wp_print_styles', 'load_fonts');
/*
#
#   RETURN CUSTOM POST TYPES
#
#   return all if $how_many equals 'all'
#
*/
    function maudience_return_custom_posts($post_type, $how_many) {
        ?><ul class='custom-post-type-list'><?php
        if( $how_many !='all' ){ $posts_per_page = $how_many; }
        $args=array(
          'post_type' => $post_type,
          'post_status' => 'publish',
          'posts_per_page' => $posts_per_page,
          'caller_get_posts'=> 1
        );
        $my_query = null;
        $my_query = new WP_Query($args);
        if( $my_query->have_posts() ) :
          while ($my_query->have_posts()) : $my_query->the_post(); ?>
            <li class='custom-post-type-list-item'>
                <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="entry-page-image">
                            <?php the_post_thumbnail(); ?>
                        </div><!-- .entry-page-image -->
                    <?php endif; ?>
                    <div class="post-image-wrap">

                        <div class="custom-post-title">
                            <?php the_title(); ?>
                        </div><!-- .custom-post-title -->

                        <?php if ( get_post_meta( get_the_ID(), '_ma_meta_value_key2', true ) ) : ?>
                            <div class="custom-post-capacity">
                                <?php echo esc_html(get_post_meta( get_the_ID(), '_ma_meta_value_key2', true )); ?>                           
                            </div><!-- .custom-post-capacity -->
                        <?php endif; ?>

                        <?php if ( get_post_meta( get_the_ID(), '_ma_meta_value_key1', true ) ) : ?>
                            <div class="custom-post-upselltext">
                                <?php echo esc_html(get_post_meta( get_the_ID(), '_ma_meta_value_key1', true ))." Person"; ?>                            
                            </div><!-- .custom-post-upselltext -->
                        <?php endif; ?>

                        
                    </div>
                </a>
                <div class="cpt-button-wrap">
                    <a class="ctl-button ctl-viewdetails-button" href="<?php the_permalink() ?>" />View Details <span>>></span></a>
                    <a class="ctl-button ctl-inquire-button" href="/contact/" />Inquire</a>
                </div>
            </li>
            <?php
          endwhile;
        endif;
        wp_reset_query();  // Restore global post data stomped by the_post().
        ?></ul><?php
    }
    add_filter("gform_submit_button", "form_submit_button", 10, 2);

    function form_submit_button($button, $form){
        $button_title = $form['button']['text'];
        return "<button class='button' id='gform_submit_button_{$form["id"]}'>".$button_title."</button>";
    }
/*
#
#   END
#
*/