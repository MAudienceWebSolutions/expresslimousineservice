<?php
/*
#
#   CONTACT INFO: PHONENUMBER, EMAIL
#   
#
*/
    /* PHONE NUMBER */

        function format_phonenumber( $arg ) {
            $data = '+'.$arg;
            if(  preg_match( '/^\+\d(\d{3})(\d{3})(\d{4})$/', $data,  $matches ) )
            {
                $result = '('.$matches[1] . ')&nbsp;' .$matches[2] . '-' . $matches[3];
                return $result;
            }
        }
        
        // Add [phonenumber] shortcode
        function phonenumber_shortcode( $atts ){
            //retrieve phone number from database
            $ma_array = get_option('maudience_contactinfo');
            //check if user is on mobile if so make the number a link
            if (wp_is_mobile())
            {
                return '<a href="tel:+'.$ma_array["id_phonenumber"].'">'.format_phonenumber($ma_array["id_phonenumber"]).'</a>';
            } else {
                return format_phonenumber($ma_array["id_phonenumber"]);
            }
        }
        add_shortcode( 'phonenumber', 'phonenumber_shortcode' );

    /* EMAIL */

        
        // Add [phonenumber] shortcode
        function contactemail_shortcode( $atts ){
            //retrieve email from database
            $ma_array = get_option('maudience_contactinfo');
            return '<a href="mailto:'.$ma_array["id_contactemail"].'">'.$ma_array["id_contactemail"].'</a>';
        }
        add_shortcode( 'contactemail', 'contactemail_shortcode' );

    class maudience_contactinfo_settings {
        /**
         * Holds the values to be used in the fields callbacks
         */

            private $options;
            
        /**
         * Start up
         */

            public function __construct()
            {
                add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
                add_action( 'admin_init', array( $this, 'page_init' ) );
            }

        /**
         * Add options page
         */

            public function add_plugin_page()
            {
                // This page will be under "Settings"
                add_options_page(
                    'Settings Admin', 
                    'Contact Info', 
                    'manage_options', 
                    'maudience-setting-admin', 
                    array( $this, 'create_admin_page' )
                );
            }

        /**
         * Options page callback
         */

            public function create_admin_page()
            {
                // Set class property
                $this->options = get_option( 'maudience_contactinfo' );
                ?>
                <div class="wrap">
                    <?php screen_icon(); ?>
                    <h2>Phone Number</h2>           
                    <form method="post" action="options.php">
                    <?php
                        // This prints out all hidden setting fields
                        settings_fields( 'maudience_contactinfo_options' );   
                        do_settings_sections( 'maudience-setting-admin' );
                        submit_button(); 
                    ?>
                    </form>
                </div>
                <?php
            }

        /**
         * Register and add settings
         */

            public function page_init()
            {        
                add_settings_section(
                    'setting_section_contactinfo', // ID
                    'Enter your site specific Contact Info below:', // Title
                    array( $this, 'print_section_info' ), // Callback
                    'maudience-setting-admin' // Page
                );  

                register_setting(
                    'maudience_contactinfo_options', // Option group
                    'maudience_contactinfo', // Option name
                    array( $this, 'sanitize' ) // Sanitize
                );
                
                add_settings_field(
                    'id_phonenumber', // ID
                    'Phone Number', // Title 
                    array( $this, 'id_phonenumber_callback' ), // Callback
                    'maudience-setting-admin', // Page
                    'setting_section_contactinfo' // Section           
                ); 
                  
                add_settings_field(
                    'id_contactemail', // ID
                    'Contact Email', // Title 
                    array( $this, 'id_contactemail_callback' ), // Callback
                    'maudience-setting-admin', // Page
                    'setting_section_contactinfo' // Section           
                );      
            }

        /**
         * Sanitize each setting field as needed
         *
         * @param array $input Contains all settings fields as array keys
         */

            public function sanitize( $input )
            {
                $new_input = array();
                if( isset( $input['id_phonenumber'] ) )
                    $new_input['id_phonenumber'] = absint( $input['id_phonenumber'] );
                if( isset( $input['id_contactemail'] ) )
                    $new_input['id_contactemail'] = sanitize_email($input);
                return $input;
            }

        /** 
         * Print the Section text
         */

            public function print_section_info()
            {
                print 'This creates a shortcode that outputs the site specific phone number as a link on mobile but only as text on desktop<br/><br/>Usage Info:<br/> - Use [phonenumber] to activate<br/> - Make sure you add your country code to the begining (1 for North America), ie: 12223334444';
            }

        /** 
         * Get the settings option array and print one of its values
         */

            public function id_phonenumber_callback()
            {
                printf(
                    '<input type="text" id="id_phonenumber" name="maudience_contactinfo[id_phonenumber]" value="%s" />',
                    isset( $this->options['id_phonenumber'] ) ? esc_attr( $this->options['id_phonenumber']) : ''
                );
            }

            public function id_contactemail_callback()
            {
                printf(
                    '<input type="text" id="id_contactemail" name="maudience_contactinfo[id_contactemail]" value="%s" />',
                    isset( $this->options['id_contactemail'] ) ? esc_attr( $this->options['id_contactemail']) : ''
                );
            }
    }

    if( is_admin() ) { $maudience_contactinfo_settings = new maudience_contactinfo_settings(); }