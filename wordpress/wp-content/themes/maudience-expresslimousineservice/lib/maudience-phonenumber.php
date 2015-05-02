<?php
/*
#
#   PHONENUMBER
#   
#
*/
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
        $lm_array = get_option('maudience_phone_number');
        //check if user is on mobile if so make the number a link
        if (wp_is_mobile())
        {
            return '<a href="tel:+'.$lm_array["id_number"].'">'.format_phonenumber($lm_array["id_number"]).'</a>';
        } else {
            return format_phonenumber($lm_array["id_number"]);
        }
    }
    add_shortcode( 'phonenumber', 'phonenumber_shortcode' );

    class maudience_phonenumber_settings {
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
                    'Phone Number', 
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
                $this->options = get_option( 'maudience_phone_number' );
                ?>
                <div class="wrap">
                    <?php screen_icon(); ?>
                    <h2>Phone Number</h2>           
                    <form method="post" action="options.php">
                    <?php
                        // This prints out all hidden setting fields
                        settings_fields( 'maudience_phone_options' );   
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
                register_setting(
                    'maudience_phone_options', // Option group
                    'maudience_phone_number', // Option name
                    array( $this, 'sanitize' ) // Sanitize
                );
                add_settings_section(
                    'setting_section_id', // ID
                    'Enter your site specific Phone Number Below:', // Title
                    array( $this, 'print_section_info' ), // Callback
                    'maudience-setting-admin' // Page
                );  
                add_settings_field(
                    'id_number', // ID
                    'ID Number', // Title 
                    array( $this, 'id_number_callback' ), // Callback
                    'maudience-setting-admin', // Page
                    'setting_section_id' // Section           
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
                if( isset( $input['id_number'] ) )
                    $new_input['id_number'] = absint( $input['id_number'] );
                return $new_input;
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

            public function id_number_callback()
            {
                printf(
                    '<input type="text" id="id_number" name="maudience_phone_number[id_number]" value="%s" />',
                    isset( $this->options['id_number'] ) ? esc_attr( $this->options['id_number']) : ''
                );
            }
    }

    if( is_admin() ) { $maudience_phonenumber_settings = new maudience_phonenumber_settings(); }