<?php

/*
Plugin Name: Zopim Widget
Plugin URI: http://www.zopim.com/?iref=wp_plugin
Description: Zopim is an award winning chat solution that helps website owners to engage their visitors and convert customers into fans!
Author: Zopim
Version: 1.3.8
Author URI: http://www.zopim.com/?iref=wp_plugin
Text Domain: zopim
Domain path: /language
*/

define( 'VERSION_NUMBER', "1.3.8" );
define( 'ZOPIM_BASE_URL', "https://www.zopim.com/" );
define( 'ZOPIM_ACCOUNT_URL', "https://account.zopim.com/" );
define( 'ZOPIM_SIGNUP_REDIRECT_URL', ZOPIM_ACCOUNT_URL . "?aref=MjUxMjY4:1TeORR:9SP1e-iPTuAVXROJA6UU5seC8x4&visit_id=6ffe00ec3cfc11e2b5ab22000a1db8fa&utm_source=account%2Bsetup%2Bpage&utm_medium=link&utm_campaign=wp%2Bsignup2#signup" );
define( 'ZOPIM_GETACCOUNTDETAILS_URL', ZOPIM_BASE_URL . "plugins/getAccountDetails" );
define( 'ZOPIM_SETDISPLAYNAME_URL', ZOPIM_BASE_URL . "plugins/setDisplayName" );
define( 'ZOPIM_SETEDITOR_URL', ZOPIM_BASE_URL . "plugins/setEditor" );
define( 'ZOPIM_LOGIN_URL', ZOPIM_BASE_URL . "plugins/login" );
define( 'ZOPIM_SIGNUP_URL', ZOPIM_BASE_URL . "plugins/createTrialAccount" );
define( 'ZOPIM_DASHBOARD_LINK', "https://dashboard.zopim.com/?utm_source=wp&utm_medium=link&utm_campaign=wp%2Bdashboard" );
define( 'ZOPIM_SMALL_LOGO', "https://dashboard.zopim.com/assets/branding/zopim.com/chatman/online.png" );

require_once dirname( __FILE__ ) . '/accountconfig.php';


function load_zopim_style() {
	wp_register_style( 'zopim_style', plugins_url( 'zopim.css', __FILE__ ) );
	wp_enqueue_style( 'zopim_style' );
	wp_register_script( 'zopim_js', plugins_url( 'zopim.js', __FILE__ ) );
	wp_enqueue_script( 'zopim_js' );
}

function add_zopim_caps() {
	$role = get_role( 'administrator' );
	$role->add_cap( 'access_zopim' );
}

add_action( 'admin_enqueue_scripts', 'load_zopim_style' );
add_action( 'admin_init', 'add_zopim_caps' );

// We need some CSS to position the paragraph
function zopimme() {
	global $current_user;
	get_currentuserinfo();

	$code = get_option( 'zopimCode' );

	if ( ( $code == "" || $code == "zopim" ) && ( ! isset( $_GET['page'] ) && ! preg_match( "/zopim/",
				$_GET['page'] ) ) && ( ! preg_match( "/zopim/", $_SERVER["SERVER_NAME"] ) )
	) {
		return;
	}

	echo "<!--Embed from Zopim Live Chat Wordpress Plugin v" . VERSION_NUMBER . "-->
<!--Start of Zopim Live Chat Script-->
<script type=\"text/javascript\">
window.\$zopim||(function(d,s){var z=\$zopim=function(c){z._.push(c)},$=z.s=
d.createElement(s),e=d.getElementsByTagName(s)[0];z.set=function(o){z.set.
_.push(o)};z._=[];z.set._=[];$.async=!0;$.setAttribute('charset','utf-8');
$.src='//v2.zopim.com/?" . $code . "';z.t=+new Date;$.
type='text/javascript';e.parentNode.insertBefore($,e)})(document,'script');
</script>";

	echo '<script>';
	if ( isset( $current_user ) ):
		$firstname = $current_user->display_name;
		$useremail = $current_user->user_email;
		if ( $firstname != "" && $useremail != "" ):
			echo "\$zopim(function(){\$zopim.livechat.set({name: '$firstname', email: '$useremail'}); });";
		endif;
	endif;

	echo zopim_get_widget_options();
	echo '</script>';
	echo "<!--End of Zopim Live Chat Script-->";
}

function zopim_get_widget_options() {
	$opts = get_option( 'zopimWidgetOptions' );
	if ( $opts ) {
		return stripslashes( $opts );
	}

	//$opts = zopim_old_plugin_settings();
	$zopim_embed_opts = "\$zopim( function() {";
	$zopim_embed_opts .= "\n})";
	$opts = $zopim_embed_opts;

	update_option( 'zopimWidgetOptions', $opts );

	$list = array(
		'zopimLang',
		'zopimPosition',
		'zopimTheme',
		'zopimColor',
		'zopimUseGreetings',
		'zopimUseBubble',
		'zopimBubbleTitle',
		'zopimBubbleText',
		'zopimBubbleEnable',
		'zopimHideOnOffline'
	);

	foreach ( $list as $key ):
		delete_option( $key );
	endforeach;

	if ( $opts ) {
		return $opts;
	} else {
		return '';
	}
}


function zopim_old_plugin_settings() {
	$theoptions = array();

	if ( get_option( 'zopimLang' ) != "" && get_option( 'zopimLang' ) != "--" ) {
		$theoptions[] = " language: '" . get_option( 'zopimLang' ) . "'";
	}

	$zopim_embed_opts = '';
	$zopim_embed_opts .= "\$zopim( function() {";

	if ( count( $theoptions ) > 0 ) {
		$zopim_embed_opts .= '$zopim.livechat.set({' . implode( ", ", $theoptions ) . "});";
	}

	if ( get_option( 'zopimPosition' ) ) {
		$zopim_embed_opts .= "\n\$zopim.livechat.button.setPosition('" . get_option( 'zopimPosition' ) . "');";
	}
	if ( get_option( 'zopimTheme' ) ) {
		$zopim_embed_opts .= "\n\$zopim.livechat.window.setTheme('" . get_option( 'zopimTheme' ) . "');";
	}
	if ( get_option( 'zopimColor' ) ) {
		$zopim_embed_opts .= "\n\$zopim.livechat.window.setColor('" . get_option( 'zopimColor' ) . "');";
	}

	if ( get_option( 'zopimUseGreetings' ) == "zopimUseGreetings" ) {
		if ( get_option( 'zopimGreetings' ) != "" ) {
			$greetings = json_to_array( get_option( 'zopimGreetings' ) );
			foreach ( $greetings as $i => $v ) {
				foreach ( $v as $j => $k ) {
					$greetings->$i->$j = str_replace( "\r\n", "\\n", $greetings->$i->$j );
				}
			}
			$zopim_embed_opts .= "\n\$zopim.livechat.setGreetings({
'online' : ['" . addslashes( $greetings->online->bar ) . "', '" . addslashes( $greetings->online->window ) . "'],
'offline': ['" . addslashes( $greetings->offline->bar ) . "', '" . addslashes( $greetings->offline->window ) . "'],
'away'   : ['" . addslashes( $greetings->away->bar ) . "', '" . addslashes( $greetings->away->window ) . "']  });";
		}
	}

	if ( get_option( 'zopimUseBubble' ) == "zopimUseBubble" ) {
		if ( get_option( 'zopimBubbleTitle' ) ) {
			$zopim_embed_opts .= "\n\$zopim.livechat.bubble.setTitle('" . addslashes( get_option( 'zopimBubbleTitle' ) ) . "');";
		}
		if ( get_option( 'zopimBubbleText' ) ) {
			$zopim_embed_opts .= "\n\$zopim.livechat.bubble.setText('" . addslashes( get_option( 'zopimBubbleText' ) ) . "');";
		}
	}

	if ( get_option( 'zopimBubbleEnable' ) == "show" ) {
		$zopim_embed_opts .= "\n\$zopim.livechat.bubble.show(true);";
	} else if ( get_option( 'zopimBubbleEnable' ) == "hide" ) {
		$zopim_embed_opts .= "\n\$zopim.livechat.bubble.hide(true);";
	}

	// this must be called last
	if ( get_option( 'zopimHideOnOffline' ) == "zopimHideOnOffline" ) {
		$zopim_embed_opts .= "\n\$zopim.livechat.button.setHideWhenOffline(true);";
	}

	$zopim_embed_opts .= "\n})";

	return $zopim_embed_opts;

}

function zopim_create_menu() {
	//create new top-level menu
	add_menu_page( __( 'Account Configuration', 'zopim' ), __( 'Zopim Chat', 'zopim' ), 'access_zopim',
		'zopim_account_config', 'zopim_account_config', ZOPIM_SMALL_LOGO );
	//call register settings function
	add_action( 'admin_init', 'register_zopim_plugin_settings' );
}

// Register the option settings we will be using
function register_zopim_plugin_settings() {

	// Authentication and codes
	register_setting( 'zopim-settings-group', 'zopimCode' );
	register_setting( 'zopim-settings-group', 'zopimUsername' );
	register_setting( 'zopim-settings-group', 'zopimSalt' );

}

add_action( 'wp_footer', 'zopimme' );
// create custom plugin settings menu
add_action( 'admin_menu', 'zopim_create_menu' );

function zopim_post_request( $url, $_data, $optional_headers = null ) {
	$args     = array( 'body' => $_data );
	$response = wp_remote_post( $url, $args );
	if ( is_wp_error( $response ) ) {
		$error = array( 'wp_error' => $response->get_error_message() );

		return json_encode( $error );
	}

	return $response['body'];
}

function zopim_url_get( $filename ) {
	$response = wp_remote_get( $filename );

	if ( is_wp_error( $response ) ) {
		$error = array( 'wp_error' => $response->get_error_message() );

		return json_encode( $error );
	}

	return $response['body'];
}

function json_to_array( $json ) {
	require_once( 'JSON.php' );
	$jsonparser = new Services_JSON();

	return ( $jsonparser->decode( $json ) );
}

function to_json( $variable ) {
	require_once( 'JSON.php' );
	$jsonparser = new Services_JSON();

	return ( $jsonparser->encode( $variable ) );
}

function zopim_get_account_details( $salt ) {
	$salty = array( "salt" => get_option( 'zopimSalt' ) );

	return json_to_array( zopim_post_request( ZOPIM_GETACCOUNTDETAILS_URL, $salty ) );
}

function zopim_set_editor( $salt ) {
	$salty = array( "salt" => get_option( 'zopimSalt' ) );

	return json_to_array( zopim_post_request( ZOPIM_SETEDITOR_URL, $salty ) );
}

// Load plugin text domain
load_plugin_textdomain( 'zopim', false, basename( dirname( __FILE__ ) ) . '/languages' );

