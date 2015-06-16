<?php

if (!function_exists("get_option")) die("Access denied.");

if (get_option("ezfc_debug_mode", 0) == 1) {
	@error_reporting(E_ALL);
	@ini_set("display_errors", "On");
}

require_once(plugin_dir_path(__FILE__) . "class.ezfc_frontend.php");
require_once(plugin_dir_path(__FILE__) . "lib/recaptcha-php-1.11/recaptchalib.php");

$privatekey = get_option("ezfc_captcha_private");
$ezfc = new Ezfc_frontend();

parse_str($_POST["data"], $data);
$id = (int) $data["id"];

$check_input_result = $ezfc->check_input($id, $data["ezfc_element"], $data["ref_id"]);

if (array_key_exists("error", $check_input_result)) {
	send_ajax($check_input_result);
	die();
}

// check if recaptcha is present in the current form
$elements      = $ezfc->elements_get();
$form_elements = $ezfc->form_elements_get($id);
$form_options  = $ezfc->array_index_key($ezfc->form_get_options($id), "name");

// prepare submission data
$ezfc->prepare_submission_data($id, $data);

// special checks
$has_recaptcha = false;
$has_upload    = false;

// user can choose to pay via paypal or other types
$force_paypal = false;

foreach ($form_elements as $k => $fe) {
	if ($elements[$fe->e_id]->type == "recaptcha") {
		$has_recaptcha = true;
	}

	if ($elements[$fe->e_id]->type == "payment" &&
		isset($data["ezfc_element"][$fe->id]) &&
		$ezfc->get_target_value_from_input($fe->id, $data["ezfc_element"][$fe->id]) == "paypal") {
		$force_paypal = true;
	}
}

// if the user chose paypal payment, set it to submission data
$ezfc->submission_data["force_paypal"] = $force_paypal;

// recaptcha in form?
if ($has_recaptcha) {
	$resp = recaptcha_check_answer($privatekey,
	                               $_SERVER["REMOTE_ADDR"],
	                               $data["recaptcha_challenge_field"],
	                               $data["recaptcha_response_field"]);

	if (!$resp->is_valid) {
		send_ajax($ezfc->send_message("error", __("Recaptcha failed, please try again.", "ezfc")));
		die();
	}
}

if (isset($form_options["min_submit_value"])) {
	$total = $ezfc->get_total($data["ezfc_element"]);

	if ($total < $form_options["min_submit_value"]->value) {
		$replaced_message = str_replace("%s", $form_options["min_submit_value"]->value, __($form_options["min_submit_value_text"]->value, "ezfc"));
		send_ajax($ezfc->send_message("error", $replaced_message));
		die();
	}
}

// insert into db
send_ajax($ezfc->insert($id, $data["ezfc_element"], $data["ref_id"]));

die();

function send_ajax($msg) {
	// check for errors in array
	if (is_array($msg)) {
		foreach ($msg as $m) {
			if (is_array($m) && $m["error"]) {
				echo json_encode($m);

				return;
			}
		}
	}

	echo json_encode($msg);
}

?>