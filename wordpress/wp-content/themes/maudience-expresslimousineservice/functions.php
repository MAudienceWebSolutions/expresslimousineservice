<?php

//global $maudience_client_slug = '';
define( 'MAUDIENCE_CLIENT_SLUG', 'expresslimousineservice' );
require_once('lib/custom-post-types.php');
require_once('lib/maudience-contactinfo.php');

/*
#
#   REGISTER JS AND CSS
#
*/

    function maudience_scripts() {
        //enqueue parent styles
        wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

        //
        wp_enqueue_style( MAUDIENCE_CLIENT_SLUG, get_stylesheet_directory_uri()."/lib/css/style.css" );

        // wp_enqueue_script(
        //     'custom-js',
        //     get_stylesheet_directory_uri() . '/custom.js',
        //     array( 'jquery' )
        // );

        // if (is_front_page()) {
        //     wp_enqueue_script(
        //         'jssor',
        //         get_stylesheet_directory_uri() . '/js/jssor.js',
        //         array( 'jquery' )
        //     );
        //     wp_enqueue_script(
        //         'jssorslider',
        //         get_stylesheet_directory_uri() . '/js/jssor.slider.js',
        //         array( 'jquery' )
        //     );
        // }

        // wp_enqueue_script('jquery-ui-accordion');
    }
    add_action( 'wp_enqueue_scripts', 'maudience_scripts'/*, 15*/ );


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

        register_sidebar( array(
            'name' => 'Home Top Left',
            'id' => 'home-top-left',
            'before_widget' => '<div id="home-top-left" class="home-top-left">',
            'after_widget' => '</div>',
            'before_title' => '<h2 class="rounded">',
            'after_title' => '</h2>',
        ) );

        register_sidebar( array(
            'name' => 'Home Top Right',
            'id' => 'home-top-right',
            'before_widget' => '<div id="home-top-right" class="home-top-right">',
            'after_widget' => '</div>',
            'before_title' => '<h2 class="rounded">',
            'after_title' => '</h2>',
        ) );

        register_sidebar( array(
            'name' => 'Home Middle',
            'id' => 'home-middle',
            'before_widget' => '<div id="home-middle" class="home-middle">',
            'after_widget' => '</div>',
            'before_title' => '<h2 class="rounded">',
            'after_title' => '</h2>',
        ) );

        register_sidebar( array(
            'name' => 'Home Bottom Left',
            'id' => 'home-bottom-left',
            'before_widget' => '<div id="home-bottom-left" class="home-bottom-left">',
            'after_widget' => '</div>',
            'before_title' => '<h2 class="rounded">',
            'after_title' => '</h2>',
        ) );

        register_sidebar( array(
            'name' => 'Home Bottom Right',
            'id' => 'home-bottom-right',
            'before_widget' => '<div id="home-bottom-right" class="home-bottom-right">',
            'after_widget' => '</div>',
            'before_title' => '<h2 class="rounded">',
            'after_title' => '</h2>',
        ) );

        register_sidebar( array(
            'name' => 'Footer Widget Left',
            'id' => 'footer-widget-left',
            'before_widget' => '<div id="footer-widget-left" class="footer-widget-left">',
            'after_widget' => '</div>',
            'before_title' => '<h2 class="rounded">',
            'after_title' => '</h2>',
        ) );

        register_sidebar( array(
            'name' => 'Footer Widget Center',
            'id' => 'footer-widget-center',
            'before_widget' => '<div id="footer-widget-center" class="footer-widget-center">',
            'after_widget' => '</div>',
            'before_title' => '<h2 class="rounded">',
            'after_title' => '</h2>',
        ) );

        register_sidebar( array(
            'name' => 'Footer Widget Right',
            'id' => 'footer-widget-right',
            'before_widget' => '<div id="footer-widget-right" class="footer-widget-right">',
            'after_widget' => '</div>',
            'before_title' => '<h2 class="rounded">',
            'after_title' => '</h2>',
        ) );
    }
    add_action( 'widgets_init', 'maudience_widgets_init', 100 );

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
    
    function my_wp_nav_menu_args( $args = '' ) {
        $args['theme_location'] = 'primary';
        $args['link_before'] = '<span>';
        $args['link_after'] = '</span>';
        return $args;
    }
    add_filter( 'wp_nav_menu_args', 'my_wp_nav_menu_args' );
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
        // wp_register_style('googleFonts', 'http://fonts.googleapis.com/css?family=Signika:400,700|Open+Sans:400italic,700italic,400,700&amp;subset=latin,latin-ext');
        wp_register_style('googleFonts', 'http://fonts.googleapis.com/css?family=Open+Sans+Condensed:300|Open+Sans:400,800,700|Oswald');
        wp_enqueue_style( 'googleFonts');
    }
    add_action('wp_print_styles', 'load_fonts');
/*
#
#   RETURN CUSTOM POST TYPES
#
#
#   Set $how_many to all to return all posts of the specified type
#   Set $title_or_content to 'both' to display both title and content
*/
    function maudience_return_custom_posts($post_type, $how_many, $title_or_content = 'title') {
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
            <li class="custom-post-type-list-item <?php echo get_post_type() ?>">
                <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="entry-page-image">
                            <?php the_post_thumbnail(); ?>
                        </div><!-- .entry-page-image -->
                    <?php endif; ?>
                    <div class="post-content-wrap">

                        <?php if ( $title_or_content === 'title' ) : ?>
                            <div class="custom-post-title">
                                <?php the_title(); ?>
                            </div><!-- .custom-post-title -->
                        <?php elseif ( $title_or_content === 'content' ) : ?>
                            <div class="custom-post-content">
                                <?php the_content(); ?>
                            </div><!-- .custom-post-title -->
                        <?php elseif ( $title_or_content === 'both' ) : ?>
                            <div class="custom-post-title">
                                <?php the_title(); ?>
                            </div><!-- .custom-post-title -->
                            <div class="custom-post-content">
                                <?php the_content(); ?>
                            </div><!-- .custom-post-title -->
                        <?php endif; ?>

                        <?php if ( get_post_meta( get_the_ID(), '_ma_meta_value_key1', true ) ) : ?>
                            <div class="custom-post-capacity">
                                <span><?php echo esc_html(get_post_meta( get_the_ID(), '_ma_meta_value_key1', true )); ?></span>                           
                            </div><!-- .custom-post-capacity -->
                        <?php endif; ?>

                        <?php if ( get_post_meta( get_the_ID(), '_ma_meta_value_key2', true ) ) : ?>
                            <div class="custom-post-upselltext">
                                <span><?php echo esc_html(get_post_meta( get_the_ID(), '_ma_meta_value_key2', true )); ?></span>                            
                            </div><!-- .custom-post-upselltext -->
                        <?php endif; ?>

                        <?php if ( get_post_meta( get_the_ID(), '_ma_meta_value_key3', true ) ) : ?>
                            <div class="custom-post-authormeta">
                                <span><?php echo esc_html(get_post_meta( get_the_ID(), '_ma_meta_value_key3', true )); ?></span>                            
                            </div><!-- .custom-post-upselltext -->
                        <?php endif; ?>
                    </div>
                </a>
                <div class="reserve-now-wrap"><a class="els-button" href='/reservations'>Reserve Now ></a></div><!-- .custom-post-upselltext -->              
               <!--  <div class="cpt-button-wrap">
                    <a class="ctl-button ctl-viewdetails-button" href="<?php the_permalink() ?>" />View Details <span>>></span></a>
                    <a class="ctl-button ctl-inquire-button" href="/contact/" />Inquire</a>
                </div> -->
            </li>
            <?php
          endwhile;
        endif;
        wp_reset_query();  // Restore global post data stomped by the_post().
        ?></ul><?php
    }
    
    // Add [output_posts type=posts amount=5] shortcode
    function output_posts_shortcode( $atts ){
        extract(shortcode_atts(array(
            'type' => posts,//defaults
            'amount' => 5,
            'output' => 'title',
        ), $atts));

        maudience_return_custom_posts( $type, $amount, $output);
    }
    add_shortcode( 'output_posts', 'output_posts_shortcode' );
/*
#
#
*/

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