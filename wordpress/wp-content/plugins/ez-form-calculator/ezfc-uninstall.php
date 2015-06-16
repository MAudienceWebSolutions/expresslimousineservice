<?php

defined( 'ABSPATH' ) OR exit;
if (!current_user_can('activate_plugins')) return;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

global $wpdb;

$tables = array(
	"{$wpdb->prefix}ezfc_elements",
	"{$wpdb->prefix}ezfc_files",
	"{$wpdb->prefix}ezfc_forms",
	"{$wpdb->prefix}ezfc_forms_elements",
	"{$wpdb->prefix}ezfc_forms_options",
	"{$wpdb->prefix}ezfc_options",
	"{$wpdb->prefix}ezfc_submissions",
	"{$wpdb->prefix}ezfc_templates",
	"{$wpdb->prefix}ezfc_themes"
);

$options = array(
	"css_form_label_width",
	"captcha_public",
	"captcha_private",
	"debug_mode",
	"edit_role",
	"jquery_ui",
	"mailchimp_api_key",
	"price_format",
	"required_text",
	"woocommerce",
	"woocommerce_text",
	"woocommerce_product_id",
	"uninstall_keep_data",
	"version"
);

// do not delete data
if (get_option("ezfc_uninstall_keep_data") == 1) return;

foreach ($tables as $table) {
	$wpdb->query("DROP TABLE `{$table}`");
}

foreach ($options as $o) {
	delete_option("ezfc_{$o}");
}