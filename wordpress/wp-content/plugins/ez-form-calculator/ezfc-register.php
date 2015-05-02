<?php

defined( 'ABSPATH' ) OR exit;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
global $wpdb;

$query = file_get_contents(dirname(__FILE__) . "/db.sql");
if (!$query) {
	die("Error opening file.");
}

require_once(plugin_dir_path(__FILE__) . "class.ezfc_backend.php");
$ezfc_backend = new Ezfc_backend();
$ezfc_backend->setup_db();

$current_version = ezfc_get_version();
$old_version     = get_option("ezfc_version", -1);
// upgrade from older version;
if ($old_version != $current_version && $old_version != -1) {
	$ezfc_backend->upgrade();
}

// default options
$ezfc_options = array(
	"css_form_label_width"   => "",
    "captcha_public"         => "",
    "captcha_private"        => "",
    "datepicker_language"    => "en",
    "debug_mode"             => 0,
    "email_smtp_port"        => 25,
    "jquery_ui"              => 1,
    "mailchimp_api_key"      => "",
    "price_format"           => "0,0[.]00",
    "required_text"          => "Required",
    "woocommerce"            => 0,
    "woocommerce_text"       => "Added to cart.",
    "woocommerce_product_id" => 0,
	"uninstall_keep_data"    => 0
);

// set default option values
foreach ($ezfc_options as $option=>$value) {
	if (!get_option("ezfc_" . $option)) {
		update_option("ezfc_" . $option, $value);
	}
}