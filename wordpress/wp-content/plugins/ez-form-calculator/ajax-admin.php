<?php

global $wpdb;

require_once(plugin_dir_path(__FILE__) . "class.ez_functions.php");
require_once(plugin_dir_path(__FILE__) . "class.ezfc_backend.php");
$ezfc = new Ezfc_backend();

parse_str($_REQUEST["data"], $data);

$action = $data["action"];
$id 	= isset($data["id"]) ? $data["id"] : 0;
$nonce  = $data["nonce"];

if (!wp_verify_nonce($nonce, "ezfc-nonce")) {
	send_ajax(array("error" => "Could not verify security nonce. Please refresh this page."));
	die();
}

/**
	actions
**/
switch ($action) {
	case "form_add":
		if ($ezfc->form_get_count() >= 1) {
			send_ajax(array("error", __("Only 1 form per site allowed in the free version", "ezfc")));
			die();
		}

		$new_form_id = $ezfc->form_add($id);

		$ret = array(
			"elements" => $ezfc->form_elements_get($new_form_id),
			"form"     => $ezfc->form_get($new_form_id),
			"options"  => $ezfc->form_get_options($new_form_id)
		);

		send_ajax($ret);
	break;

	case "form_clear":
		send_ajax($ezfc->form_clear($id));
	break;

	case "form_delete":
		send_ajax($ezfc->form_delete($id));
	break;

	case "form_file_delete":
		send_ajax($ezfc->form_file_delete($id));
	break;

	case "form_duplicate":
		// send from form class due to delayed insert
		send_ajax($ezfc->form_duplicate($id));
	break;

	case "form_get":
		$ret = array(
			"elements"          => $ezfc->form_elements_get($id),
			"form"              => $ezfc->form_get($id),
			"options"           => $ezfc->form_get_options($id),
			"submissions_count" => $ezfc->form_get_submissions_count($id)
		);

		send_ajax($ret);
	break;

	case "form_show_export":
		$ret = $ezfc->form_get_export_data(null, $id);

		send_ajax($ret);
	break;

	case "form_get_submissions":
		$ret = array(
			"files"       => $ezfc->form_get_submissions_files($id),
			"submissions" => $ezfc->form_get_submissions($id)
		);

		send_ajax($ret);
	break;

	case "form_import_data":
		$import_data_json = json_decode($data["import_data"]);
		// elements couldn't be parsed - let's try with stripslashes
		if (!$import_data_json) {
			$import_data_json = json_decode(stripslashes($data["import_data"]));

			// still no luck - tell the user to remove special characters
			if (!$import_data_json) {
				send_ajax(array("error" => "Unable to import elements."));
				die();
			}
		}

		send_ajax($ezfc->form_import($import_data_json));
	break;

	case "form_save_template":
		send_ajax($ezfc->form_save_template($id));
	break;

	case "form_save":
		// update form info
		$ezfc->form_update($id, $data["ezfc-form-name"]);

		$elements_save = array();
		$elements      = json_decode($data["elements"]);

		// empty form
		if (is_array($elements) && count($elements) < 1) {
			send_ajax(1);
			die();
		}

		// elements couldn't be parsed - let's try with stripslashes
		if (!$elements) {
			$elements = json_decode(stripslashes($data["elements"]));

			// still no luck - tell the user to remove special characters
			if (!$elements) {
				send_ajax(array("error" => "Unable to save elements, please remove any special characters before saving."));
				die();
			}
		}

		// empty form
		if (is_array($elements) && count($elements) < 1) {
			send_ajax(1);
			die();
		}

		foreach ($elements as $element) {
			$tmp_str = $element->name . "=" . urlencode($element->value);
			parse_str($tmp_str, $tmp_save);
			
			$elements_save = Ez_Functions::array_merge_recursive_distinct($elements_save, $tmp_save);
		}

		// update form elements
		$res = $ezfc->form_elements_save($id, $elements_save["elements"]);
		if ($res !== 1) {
			send_ajax($res);
			die();
		}

		send_ajax($res);
	break;

	case "form_submission_delete":
		send_ajax($ezfc->form_submission_delete($id));
	break;

	case "form_template_delete":
		send_ajax($ezfc->form_template_delete($id));
	break;

	case "form_update_options":
		send_ajax($ezfc->form_update_options($id, $data["opt"]));
	break;

	case "elements_get":
		$ret = array(
			"elements" => $ezfc->elements_get()
		);

		send_ajax($ret);
	break;

	case "element_get":
		$ret = array(
			"element" => $ezfc->element_get($id)
		);

		send_ajax($ret);
	break;

	case "form_element_add":
		$f_id = (int) $data["f_id"];
		$e_id = (int) $data["e_id"];

		$type = isset($data["type"]) ? $data["type"] : null;

		$new_element_id = $ezfc->form_element_add($f_id, $e_id, $type);
		send_ajax($ezfc->form_element_get($new_element_id));
	break;

	case "form_element_delete":
		send_ajax($ezfc->form_element_delete($id));
	break;

	case "form_element_duplicate":
		send_ajax($ezfc->form_element_duplicate($id));
	break;
}

die();

function send_ajax($msg) {
	// check for errors in array
	if (is_array($msg)) {
		foreach ($msg as $m) {
			if (is_array($m) && isset($m["error"])) {
				echo json_encode($m);

				return;
			}
		}
	}

	echo json_encode($msg);
}

?>