<?php
/*
Plugin Name: ez Form Calculator Free
Plugin URI: http://www.mials.de/mials/ezfc/
Description: With ez Form Calculator, you can simply create a form calculator for both yourself and your customers. Easily add basic form elements like checkboxes, dropdown menus, radio buttons etc. with only a few clicks. Each form element can be assigned a value which will automatically be calculated. Get the premium version at <a href="http://codecanyon.net/item/ez-form-calculator-wordpress-plugin/7595334?ref=keksdieb">
Version: 2.0
Author: Michael Schuppenies
Author URI: http://www.mials.de/
*/

defined( 'ABSPATH' ) OR exit;

// actions
add_action("admin_menu", "ezfc_setup", 999);
add_action("init", "ezfc_load_language");
add_action("wp_head", "ezfc_wp_head");
add_action("wp_ajax_ezfc_backend", "ezfc_ajax");
add_action("wp_ajax_ezfc_frontend", "ezfc_ajax_frontend");
add_action("wp_ajax_nopriv_ezfc_frontend", "ezfc_ajax_frontend");

// tinymce
add_action("admin_head", "ezfc_tinymce");
add_action("admin_print_scripts", "ezfc_tinymce_script");

if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}
// woocommerce check
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ||
	is_plugin_active_for_network('woocommerce/woocommerce.php')) {
    add_action('woocommerce_before_calculate_totals', 'ezfc_add_custom_price' );
}

// functions
require_once(plugin_dir_path(__FILE__) . "class.ez_functions.php");

// hooks
register_activation_hook(__FILE__, "ezfc_register");
register_uninstall_hook(__FILE__, "ezfc_uninstall");

/**
	setup
**/
function ezfc_get_version() {
	return "2.0";
}

/**
	install
**/
function ezfc_register() {
	require_once(plugin_dir_path(__FILE__) . "ezfc-register.php");
}

/**
	uninstall
**/
function ezfc_uninstall() {
	require_once(plugin_dir_path(__FILE__) . "ezfc-uninstall.php");
}

/**
	language domain
**/
function ezfc_load_language() {
	load_plugin_textdomain('ezfc', false, dirname(plugin_basename(__FILE__)) . '/');
}

/**
	admin pages
**/
function ezfc_setup() {
	$role = "edit_posts";

	add_menu_page("ezfc", __("ez Form Calculator", "ezfc"), $role, "ezfc", "ezfc_page_main");
	add_submenu_page("ezfc", __("Form settings", "ezfc"), __("Form settings", "ezfc"), $role, "ezfc-settings-form", "ezfc_page_settings_form");
	add_submenu_page("ezfc", __("Global settings", "ezfc"), __("Global settings", "ezfc"), $role, "ezfc-options", "ezfc_page_settings");
	add_submenu_page("ezfc", __("Import / export", "ezfc"), __("Import / Export", "ezfc"), $role, "ezfc-importexport", "ezfc_page_importexport");
	add_submenu_page("ezfc", __("Help / debug", "ezfc"), __("Help / debug", "ezfc"), $role, "ezfc-help", "ezfc_page_help");
	add_submenu_page("ezfc", __("Premium version", "ezfc"), __("Premium version", "ezfc"), $role, "ezfc-premium", "ezfc_page_premium");
}

function ezfc_page_main() {
	ezfc_load_scripts("backend");
	require_once(plugin_dir_path(__FILE__) . "ezfc-page-main.php");
}

function ezfc_page_settings_form() {
	ezfc_load_scripts("backend");
	require_once(plugin_dir_path(__FILE__) . "ezfc-page-settings-form.php");
}

function ezfc_page_settings() {
	ezfc_load_scripts("backend");
	require_once(plugin_dir_path(__FILE__) . "ezfc-page-settings.php");
}

function ezfc_page_importexport() {
	ezfc_load_scripts("backend");
	require_once(plugin_dir_path(__FILE__) . "ezfc-page-importexport.php");
}

function ezfc_page_templates() {
	ezfc_load_scripts("backend");
	require_once(plugin_dir_path(__FILE__) . "ezfc-page-templates.php");
}

function ezfc_page_help() {
	ezfc_load_scripts("backend");
	require_once(plugin_dir_path(__FILE__) . "ezfc-page-help.php");
}

function ezfc_page_premium() {
	ezfc_load_scripts("backend");
	require_once(plugin_dir_path(__FILE__) . "ezfc-page-premium.php");
}

/**
	tinymce button
**/
function ezfc_tinymce() {
	global $typenow;

    if( ! in_array( $typenow, array( 'post', 'page' ) ) )
        return;

    add_filter( 'mce_external_plugins', 'ezfc_add_tinymce_plugin' );
    add_filter( 'mce_buttons', 'ezfc_add_tinymce_button' );
}

function ezfc_tinymce_script() {
	global $typenow;

    if( ! in_array( $typenow, array( 'post', 'page' ) ) )
        return;

	require_once(plugin_dir_path(__FILE__) . "class.ezfc_backend.php");
    $ezfc_backend = new Ezfc_backend();

    echo "<script>ezfc_forms = " . json_encode($ezfc_backend->forms_get()) . ";</script>";
}

function ezfc_add_tinymce_plugin( $plugin_array ) {
    $plugin_array['ezfc_tinymce'] = plugins_url('/ezfc_tinymce.js', __FILE__ );

    return $plugin_array;
}

function ezfc_add_tinymce_button( $buttons ) {
    array_push( $buttons, 'ezfc_tinymce_button' );

    return $buttons;
}

/**
	scripts
**/
function ezfc_load_scripts($end="frontend") {
	wp_enqueue_script("jquery");

	if ($end == "backend") {
		wp_enqueue_media();
		
		wp_enqueue_style("bootstrap-grid", plugins_url("assets/css/bootstrap-grid.min.css", __FILE__));
		wp_enqueue_style("jquery-ui", plugins_url("assets/css/jquery-ui.min.css", __FILE__));
		wp_enqueue_style("jquery-ui-theme", plugins_url("assets/css/jquery-ui.theme.min.css", __FILE__));
		wp_enqueue_style("jquerytimepicker-css", plugins_url("assets/css/jquery.timepicker.css", __FILE__));
		wp_enqueue_style("opentip", plugins_url("assets/css/opentip.css", __FILE__));
        wp_enqueue_style("thickbox");
		wp_enqueue_style("ezfc-css-frontend", plugins_url("style-frontend.css?1", __FILE__));
		wp_enqueue_style("ezfc-css-backend", plugins_url("style-backend.css?1", __FILE__));
		wp_enqueue_style("ezfc-font-awesome", plugins_url("assets/css/font-awesome.min.css", __FILE__));

		wp_enqueue_script("jquery-ui-core");
		wp_enqueue_script("jquery-ui-mouse");
		wp_enqueue_script("jquery-ui-widget");
		wp_enqueue_script("jquery-ui-dialog");
		wp_enqueue_script("jquery-ui-draggable");
		wp_enqueue_script("jquery-ui-droppable");
		wp_enqueue_script("jquery-ui-selectable");
		wp_enqueue_script("jquery-ui-sortable");
		wp_enqueue_script("jquery-ui-spinner");
		wp_enqueue_script("jquery-ui-tabs");
		wp_enqueue_script("jquery-opentip", plugins_url("assets/js/opentip-jquery.min.js", __FILE__));
		wp_enqueue_script("jquerytimepicker", plugins_url("assets/js/jquery.timepicker.min.js", __FILE__));
		wp_enqueue_script("jquery-nestedsortable", plugins_url("assets/js/jquery.mjs.nestedSortable.js", __FILE__));
		wp_enqueue_script("thickbox");
		wp_enqueue_script("ezfc-backend", plugins_url("backend.js", __FILE__), array(), ezfc_get_version());

		wp_localize_script("ezfc-backend", "ezfc_vars", array(
			"delete" => __("Delete", "ezfc"),
			"delete_form" => __("Really delete the selected form?", "ezfc"),
			"delete_element" => __("Really delete the selected element?", "ezfc"),
			"form_changed" => __("You have changed the form without having saved. Really leave the current form unsaved?"),
			"yes_no" => array(
				"yes" => __("Yes", "ezfc"),
				"no"  => __("No", "ezfc")
			)
		));
	}

	elseif ($end == "frontend") {
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_excerpt', 'wpautop' );
		
		if (get_option("ezfc_jquery_ui") == 1) {
			wp_enqueue_style("jquery-ui", plugins_url("assets/css/jquery-ui.min.css", __FILE__));
			wp_enqueue_style("jquery-ui", plugins_url("assets/css/jquery-ui.theme.min.css", __FILE__));
		}
		wp_enqueue_style("opentip", plugins_url("assets/css/opentip.css", __FILE__));
		wp_enqueue_style("jquery-timepicker", plugins_url("assets/css/jquery.timepicker.css", __FILE__));
		wp_enqueue_style("ezfc-css-frontend", plugins_url("style-frontend.css?1", __FILE__));

		// datepicker language
		if (get_option("ezfc_datepicker_load_languages", 0) == 1) {
			wp_enqueue_script("jquery-languages", plugins_url("assets/js/jquery.ui.i18n.all.min.js", __FILE__));
		}

		wp_enqueue_script("jquery");
		wp_enqueue_script("jquery-ui-core");
		wp_enqueue_script("jquery-ui-datepicker");
		wp_enqueue_script("jquery-ui-dialog");
		wp_enqueue_script("jquery-ui-progressbar");
		wp_enqueue_script("jquery-ui-slider");
		wp_enqueue_script("jquery-ui-spinner");
		wp_enqueue_script("jquery-ui-widget");
		wp_enqueue_script("jquery-touch-punch", plugins_url("assets/js/jquery.ui.touch-punch.min.js", __FILE__));
		wp_enqueue_script("jquery-opentip", plugins_url("assets/js/opentip-jquery.min.js", __FILE__));
		wp_enqueue_script("numeraljs", plugins_url("assets/js/numeral.min.js", __FILE__));
		wp_enqueue_script("jquery-file-upload", plugins_url("assets/js/jquery.fileupload.min.js", __FILE__));
		wp_enqueue_script("jquery-iframe-transport", plugins_url("assets/js/jquery.iframe-transport.min.js", __FILE__));
		wp_enqueue_script("jquery-timepicker", plugins_url("assets/js/jquery.timepicker.min.js", __FILE__));
		wp_enqueue_script("ezfc-frontend", plugins_url("frontend.min.js", __FILE__), array(), ezfc_get_version());	

		// general options
		wp_localize_script("ezfc-frontend", "ezfc_vars", array(
			"datepicker_language" => get_option("ezfc_datepicker_language", "en"),
			"debug_mode"          => get_option("ezfc_debug_mode", 0),
			"noid"                => __("No form with the requested ID found.", "ezfc"),
			"price_format"        => get_option("ezfc_price_format"),
			"uploading"           => __("Uploading...", "ezfc"),
			"upload_success"      => __("File upload successful.", "ezfc"),
			"yes_no" => array(
				"yes" => __("Yes", "ezfc"),
				"no"  => __("No", "ezfc")
			)
		));
	}
}

/**
	ajax
**/
function ezfc_ajax_frontend() {
	require_once(plugin_dir_path(__FILE__) . "ajax.php");
}

// backend
function ezfc_ajax() {
	require_once(plugin_dir_path(__FILE__) . "ajax-admin.php");
}

function ezfc_wp_head() {
	?>
		<script type="text/javascript">
		ezfc_ajaxurl = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
		ezfc_form_vars = [];
		</script>
	<?php
}


/**
	shortcodes
**/
class Ezfc_shortcode {
	static $add_script;

	static function init() {
		add_shortcode('ezfc', array(__CLASS__, 'handle_shortcode'));

		add_action('init', array(__CLASS__, 'register_script'));
		add_action('wp_footer', array(__CLASS__, 'print_script'));
	}

	static function handle_shortcode($atts) {
		self::$add_script = true;

		extract(shortcode_atts(array(
			"id"   => null,
			"name" => null
		), $atts));

		$id = (int) $id;

		require_once(plugin_dir_path(__FILE__)."class.ezfc_frontend.php");
		$ezfc = new Ezfc_frontend();

		return $ezfc->get_output($id, $name);
	}

	static function register_script() {
		wp_register_style('ezfc-css-frontend', plugins_url("style-frontend.css", __FILE__));
	}

	static function print_script() {
		if ( ! self::$add_script )
			return;

		ezfc_load_scripts("frontend");
	}
}
Ezfc_shortcode::init();

// paypal verify shortcode
function ezfc_shortcode_paypal_verify($atts, $content = null) {
	session_start();

	require_once(plugin_dir_path(__FILE__)."class.ezfc_frontend.php");
	require_once(plugin_dir_path(__FILE__)."lib/paypal/expresscheckout.php");

	if (!isset($_GET["PayerID"])) return __("No PayerID.", "ezfc");

	// verify paypal payment
	$_SESSION["payer_id"] = $_REQUEST["PayerID"];
	$confirmation = Ezfc_paypal::confirm();

	// no payment
	if (!$confirmation || isset($confirmation["error"])) {
		return __("Payment could not be verified. :(", "ezfc");
	}

	// user paid $$$ --> update submission
	$ezfc    = new Ezfc_frontend();
	$update  = $ezfc->update_submission_paypal($_REQUEST["token"], $confirmation["transaction_id"]);

	if (!$update || isset($update["error"])) return __($update["error"], "ezfc");

	// get form options
	$options = $ezfc->array_index_key($ezfc->form_get_options($update["submission"]->f_id), "name");

	// prepare submission data for mails
	$ezfc->prepare_submission_data($update["submission"]->f_id, json_decode($update["submission"]->data));
	// send mails
	$ezfc->send_mails($update["submission"]->id);

	return __($options["pp_paid_text"]->value, "ezfc");
}
add_shortcode("ezfc_verify", "ezfc_shortcode_paypal_verify");


/**
	woocommerce
**/
function ezfc_woo_check() {
	// woo multisite check
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	}

	if ( is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
	    return true;
	}
	else {
		return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' )));
	}
}
function ezfc_woo_setup($force=false) {
	if (!ezfc_woo_check() && !$force) return array("error" => "Cannot locate WooCommerce plugin.");
	
	if (ezfc_woo_product_exists() && !$force) return array("error" => "Product already exists.");

    // add placeholder post
    $post = array(
        'post_content'   => "",
        'post_name'      => "ezfc-product-placeholder",
        'post_title'     => "ez Form Calculator Product Placeholder",
        'post_status'    => "publish",
        'post_type'      => "product"
    );

    $product_id = wp_insert_post($post);

    update_option("ezfc_woocommerce_product_id", $product_id);

    update_post_meta($product_id, '_visibility', 'hidden' );
    update_post_meta($product_id, '_stock_status', 'instock');
    update_post_meta($product_id, 'total_sales', '0');
    update_post_meta($product_id, '_downloadable', 'no');
    update_post_meta($product_id, '_virtual', 'yes');
    update_post_meta($product_id, '_regular_price', "0" );
    update_post_meta($product_id, '_sale_price', "" );
    update_post_meta($product_id, '_purchase_note', "" );
    update_post_meta($product_id, '_featured', "no" );
    update_post_meta($product_id, '_weight', "" );
    update_post_meta($product_id, '_length', "" );
    update_post_meta($product_id, '_width', "" );
    update_post_meta($product_id, '_height', "" );
    update_post_meta($product_id, '_sku', "");
    update_post_meta($product_id, '_product_attributes', array());
    update_post_meta($product_id, '_sale_price_dates_from', "" );
    update_post_meta($product_id, '_sale_price_dates_to', "" );
    update_post_meta($product_id, '_price', "0" );
    update_post_meta($product_id, '_sold_individually', "" );
    update_post_meta($product_id, '_manage_stock', "no" );
    update_post_meta($product_id, '_backorders', "no" );
    update_post_meta($product_id, '_stock', "" );

    return array("success" => true);
}

function ezfc_woo_product_exists() {
	$product_exists = get_post(get_option("ezfc_woocommerce_product_id"));

    return $product_exists ? true : false;
}

function ezfc_add_custom_price( $cart_object ) {
	global $woocommerce;

    require_once(plugin_dir_path(__FILE__) . "class.ezfc_backend.php");
    $ezfc = new Ezfc_backend();

    $target_product_id = get_option("ezfc_woocommerce_product_id");

    foreach ( $cart_object->cart_contents as $key => $value ) {
        if ($value['product_id'] == $target_product_id) {
        	// do not mess with other products
        	if (!isset($value["variation"]["ezfc_id"])) return;

            $ezfc_id = $value["variation"]["ezfc_id"];
            $ezfc_submission = $ezfc->get_submission($ezfc_id);
			
			// change price
			$value["data"]->set_price($ezfc_submission->total);

            // change title
            //$ezfc_form = $ezfc->form_get($ezfc_submission->f_id);
            //$value["data"]->post->post_title = $ezfc_form->name;
        }
    }
}