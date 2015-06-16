<?php

class Ezfc_backend {
	function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->tables = array(
			"debug"          => "{$this->wpdb->prefix}ezfcf_debug",
			"elements"       => "{$this->wpdb->prefix}ezfcf_elements",
			"files"          => "{$this->wpdb->prefix}ezfcf_files",
			"forms"			 => "{$this->wpdb->prefix}ezfcf_forms",
			"forms_elements" => "{$this->wpdb->prefix}ezfcf_forms_elements",
			"forms_options"  => "{$this->wpdb->prefix}ezfcf_forms_options",
			"options"        => "{$this->wpdb->prefix}ezfcf_options",
			"submissions"    => "{$this->wpdb->prefix}ezfcf_submissions",
			"templates"      => "{$this->wpdb->prefix}ezfcf_templates",
			"themes"         => "{$this->wpdb->prefix}ezfcf_themes",
			"tmp"            => "{$this->wpdb->prefix}ezfcf_tmptable"
		);
	}

	function get_debug_log() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["debug"]} ORDER BY id DESC LIMIT 500");

		if (count($res) < 1) return "No debug logs found.";

		$logs = array();
		foreach ($res as $log) {
			$logs[] = "{$log->time}: {$log->msg}";
		}

		return implode("\n", $logs);
	}

	function clear_debug_log() {
		$this->wpdb->query("TRUNCATE TABLE `{$this->tables["debug"]}`");
	}

	function debug($msg) {
		if (get_option("ezfc_debug_mode", 0) == 0) return;

		$this->wpdb->insert(
			$this->tables["debug"],
			array("msg" => $msg),
			array("%s")
		);
	}

	function form_get_count() {
		$res = $this->wpdb->get_var("SELECT count(*) FROM {$this->tables["forms"]}");

		return $res;
	}

	/**
		forms
	**/
	function form_add($template_id=0, $import_data=false) {
		if ($this->form_get_count() >= 1) return $this->send_message("error", __("Only 1 form per site allowed in the free version", "ezfc"));

		$form_name = __("New form", "ezfc");

		if ($import_data) {
			if (!property_exists($import_data, "form")) return $this->send_message("error", __("Corrupt form data.", "ezfc"));

			$form_name = $import_data->form->name;
		}

		$res = $this->wpdb->insert(
			$this->tables["forms"],
			array("name" => $form_name),
			array("%s")
		);

		$insert_id = $this->wpdb->insert_id;

		// template / import
		$template_id = (int) $template_id;
		$template = null;

		// add by template
		if ($template_id != 0) {
			$template = $this->form_template_get($template_id);
			$template_elements = json_decode($template->data);
			$template_options  = json_decode($template->options);
		}
		// add by import data
		else if ($import_data && property_exists($import_data, "elements")) {
			$template = $import_data;
			$template_elements = $template->elements;
			$template_options  = $template->options;
		}

		// template data exists -> import them
		if (is_object($template)) {
			foreach ($template_elements as $element) {
				$el_insert_id = $this->form_element_add($insert_id, $element->e_id, $element->data, $element->position);

				if (!$el_insert_id) return $this->send_message("error", __("Could not insert element data from template.", "ezfc"));

				$element->id = $el_insert_id;
			}

			// replace calculate positions with target ids
			$template_elements_indexed = $this->array_index_key($template_elements, "position");

			foreach ($template_elements_indexed as $pos => $element) {
				if (!property_exists($element, "data")) continue;
				$element_data = json_decode($element->data);

				// calculate elements
				if (property_exists($element_data, "calculate") &&
					!empty($element_data->calculate) &&
					count($element_data->calculate) > 0) {

					foreach ($element_data->calculate as $calc_key => $calc_value) {
						if ($calc_value->target == 0) continue;

						if (!isset($template_elements_indexed[$calc_value->target])) continue;

						$target_element = $template_elements_indexed[$calc_value->target];
						$calc_id = $target_element->id;

						$element_data->calculate[$calc_key]->target = $calc_id;
						// unset id due to duplication
						unset($element_data->id);

						$element_data_sql = json_encode($element_data);

						$query = "UPDATE {$this->tables["forms_elements"]} SET data='{$element_data_sql}' WHERE id={$element->id}";

						$this->wpdb->query($query);
					}
				}

				// conditional elements
				if (property_exists($element_data, "conditional") &&
					!empty($element_data->conditional) &&
					count($element_data->conditional) > 0) {

					foreach ($element_data->conditional as $cond_key => $cond_value) {
						if ($cond_value->target == 0) continue;

						if (!isset($template_elements_indexed[$cond_value->target])) continue;

						$target_element = $template_elements_indexed[$cond_value->target];
						$cond_id = $target_element->id;

						$element_data->conditional[$cond_key]->target = $cond_id;
						// unset id due to duplication
						unset($element_data->id);

						$element_data_sql = json_encode($element_data);

						$query = "UPDATE {$this->tables["forms_elements"]} SET data='{$element_data_sql}' WHERE id={$element->id}";

						$this->wpdb->query($query);
					}
				}
			}
		}

		// add default options
		$default_options = $this->wpdb->query($this->wpdb->prepare("
			INSERT INTO {$this->tables["forms_options"]} (f_id, o_id, value)
			(
				SELECT %d, id, value FROM {$this->tables["options"]}
			)
		", $insert_id));

		// import options
		if (isset($template_options) && is_object($template_options)) {
			foreach ($template_options as $o) {
				$this->wpdb->query($this->wpdb->prepare("
					UPDATE {$this->tables["forms_options"]}
					SET value=%s
					WHERE f_id=%d AND o_id=%d
				", $o->value, $insert_id, $o->o_id));
			}
		}

		$this->add_missing_element_options($insert_id);

		return $insert_id;
	}

	function form_clear($id) {
		$id = (int) $id;

		$res = $this->wpdb->delete(
			$this->tables["forms_elements"],
			array("f_id" => $id),
			array("%d")
		);

		return $this->send_message("success", __("Form cleared.", "ezfc"));
	}

	function form_delete($id) {
		$id = (int) $id;

		$res = $this->wpdb->delete(
			$this->tables["forms"],
			array("id" => $id),
			array("%d")
		);

		if ($res === false) return $this->send_message("error", __("Could not delete form: {$wpdb->last_error}"));

		$res = $this->wpdb->delete(
			$this->tables["forms_elements"],
			array("f_id" => $id),
			array("%d")
		);

		if ($res === false) return $this->send_message("error", __("Could not delete form elements: {$wpdb->last_error}"));

		$res = $this->wpdb->delete(
			$this->tables["forms_options"],
			array("f_id" => $id),
			array("%d")
		);

		if ($res === false) return $this->send_message("error", __("Could not delete form options: {$wpdb->last_error}"));

		return $this->send_message("success", __("Form deleted.", "ezfc"));
	}

	function form_duplicate($id) {
		// get import data
		$form_data = $this->form_get_export_data(null, $id);
		// convert objects
		$form_object = json_decode(json_encode($form_data));
		// add form
		$new_form_id = $this->form_add(0, $form_object);
		
		// get form data
		$ret = array(
			"elements" => $this->form_elements_get($new_form_id),
			"form"     => $this->form_get($new_form_id),
			"options"  => $this->form_get_options($new_form_id)
		);

		return $ret;
	}

	function form_file_delete($id) {
		$id = (int) $id;

		$file = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["files"]} WHERE id=%d",
			$id
		));

		if (!unlink($file->file)) return $this->send_message("error", __("Could not delete file from server.", "ezfc"));

		$res = $this->wpdb->delete(
			$this->tables["files"],
			array("id" => $id),
			array("%d")
		);

		if ($res === false) return $this->send_message("error", __("Could not delete file entry: {$wpdb->last_error}", "ezfc"));

		return $this->send_message("success", __("File deleted.", "ezfc"));
	}

	function forms_get() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["forms"]}");

		return $res;
	}

	function form_get($id) {
		if (!$id) return $this->send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["forms"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	function form_get_options($id) {
		if (!$id) return $this->send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT fo.f_id, fo.o_id, fo.value, o.name, o.type
			FROM {$this->tables["forms_options"]} AS fo
			JOIN {$this->tables["options"]} AS o
				ON fo.o_id=o.id
			WHERE fo.f_id=%d;",
			$id
		));

		return $res;
	}

	/**
		$import_data = json_object
	**/
	function form_import($import_data) {
		$new_form_id = $this->form_add(null, $import_data);

		$this->add_missing_element_options($new_form_id);

		$ret = array(
			"elements" => $this->form_elements_get($new_form_id),
			"form"     => $this->form_get($new_form_id),
			"options"  => $this->form_get_options($new_form_id)
		);

		return $ret;
	}

	/**
		submissions
	**/
	function get_submission($id) {
		if (!$id) return $this->send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["submissions"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	function form_get_submissions($id) {
		if (!$id) return $this->send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["submissions"]} WHERE f_id=%d ORDER BY id DESC",
			$id
		));

		return $res;
	}


	function form_get_submissions_count($id) {
		if (!$id) return $this->send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_var($this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tables["submissions"]} WHERE f_id=%d",
			$id
		));

		return $res;
	}

	function form_get_submissions_files($id) {
		if (!$id) return $this->send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["files"]} WHERE f_id=%d ORDER BY id DESC",
			$id
		));

		$files = array();
		// index by ref_id
		foreach ($res as $file) {
			$files[$file->ref_id][] = $file;
		}

		return $files;
	}

	function form_submission_delete($id) {
		$id = (int) $id;

		$res = $this->wpdb->delete(
			$this->tables["submissions"],
			array("id" => $id),
			array("%d")
		);

		if ($res === false) return $this->send_message("error", __("Could not delete form: {$wpdb->last_error}"));

		return $this->send_message("success", __("Submission deleted.", "ezfc"));
	}

	function form_save_template($id) {
		$id = (int) $id;

		$form          = $this->form_get($id);
		$form_elements = $this->form_elements_get($id, true);
		$form_options  = $this->form_get_options($id);

		// replace calculate target ids with positions
		foreach ($form_elements as $pos => $element) {
			if (!property_exists($element, "data")) continue;
			$element_data = json_decode($element->data);

			if (!property_exists($element_data, "calculate")) continue;
			if (count($element_data->calculate) < 1) continue;
			
			foreach ($element_data->calculate as $calc_key => $calc_value) {
				if ($calc_value->target == 0) continue;

				if (!$form_elements[$calc_value->target]) continue;

				$target_element = $form_elements[$calc_value->target];
				$calc_position = $target_element->position;

				$element_data->calculate[$calc_key]->target = $calc_position;
				$element->data = json_encode($element_data);
			}
		}

		// replace conditional target ids with positions
		foreach ($form_elements as $pos => $element) {
			if (!property_exists($element, "data")) continue;
			$element_data = json_decode($element->data);

			if (!property_exists($element_data, "conditional")) continue;
			if (count($element_data->conditional) < 1) continue;
			
			foreach ($element_data->conditional as $cond_key => $cond_value) {
				if ($cond_value->target == 0) continue;

				if (!$form_elements[$cond_value->target]) continue;

				$target_element = $form_elements[$cond_value->target];
				$calc_position = $target_element->position;

				$element_data->conditional[$cond_key]->target = $calc_position;
				$element->data = json_encode($element_data);
			}
		}

		$form_elements_json = json_encode($form_elements);
		$form_options_json  = json_encode($form_options);

		$res = $this->wpdb->insert(
			$this->tables["templates"],
			array(
				"name"    => $form->name,
				"data"    => $form_elements_json,
				"options" => $form_options_json
			),
			array(
				"%s",
				"%s"
			)
		);

		if (!$res) return $this->send_message("error", __("Could not insert template", "ezfc") . ": " . $this->wpdb->last_error);

		return $this->wpdb->insert_id;
	}

	function form_template_delete($id) {
		$id = (int) $id;

		$res = $this->wpdb->delete(
			$this->tables["templates"],
			array("id" => $id),
			array("%d")
		);

		if ($res === false) return $this->send_message("error", __("Could not delete template: {$wpdb->last_error}"));

		return $this->send_message("success", __("Template deleted.", "ezfc"));
	}

	function form_update($id, $name) {
		$id = (int) $id;

		$res = $this->wpdb->update(
			$this->tables["forms"],
			array("name" => $name),
			array("id" => $id),
			array("%s"),
			array("%d")
		);

		return $res;
	}

	function form_update_options($id, $options) {	
		if (count($options) < 1) return $this->send_message("success", __("Settings updated", "ezfc"));

		foreach ($options as $o_id=>$value) {
			$res = $this->wpdb->replace(
				$this->tables["forms_options"],
				array(
					"f_id"  => $id,
					"o_id"  => $o_id,
					"value" => stripslashes($value)
				),
				array(
					"%d",
					"%d",
					"%s"
				)
			);

			if ($res === false) {
				return $this->send_message("error", $this->wpdb->last_error);
			}
		}

		return $this->send_message("success", __("Settings updated", "ezfc"));
	}

	/**
		elements
	**/
	function elements_get() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["elements"]} ORDER BY id ASC");

		return $res;
	}

	function element_get($id) {
		if (!$id) return $this->send_message("error", __("No ID.", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["elements"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	/**
		form elements
	**/
	function form_elements_get($id, $index=false, $key="id") {
		if (!$id) return $this->send_message("error", __("No ID given.", "ezfc"));

		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["forms_elements"]} WHERE f_id=%d ORDER BY position DESC",
			$id
		));

		if ($index) $res = $this->array_index_key($res, $key);

		return $res;
	}

	function form_element_get($id) {
		if (!$id) return $this->send_message("error", __("No ID given.", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["forms_elements"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	function form_element_add($f_id, $e_id, $data=null, $e_position=null) {
		$form_elements = $this->form_elements_get($f_id);
		if (count($form_elements) >= 5) return $this->send_message("error", __("Only 5 elements allowed in the free version."));

		// default data
		if (!$data) {
			$default_data = $this->wpdb->get_var($this->wpdb->prepare(
				"SELECT data FROM {$this->tables["elements"]} WHERE id=%d",
				$e_id
			));
		}
		// template data
		else {
			$default_data = $data;
		}

		// convert some element properties to htmlentities
		$default_data_object = json_decode($default_data);
		$default_data_object = $this->convert_html_fields($default_data_object, "decode");
		$default_data        = json_encode($default_data_object);

		// set position
		$position = $e_position ? $e_position : 0;

		$res = $this->wpdb->insert(
			$this->tables["forms_elements"],
			array(
				"f_id"     => $f_id,
				"e_id"     => $e_id,
				"data"     => $default_data,
				"position" => $position
			),
			array(
				"%d",
				"%d",
				"%s",
				"%d"
			)
		);

		if (!$res) return $this->send_message("error", __("Could not insert element to form", "ezfc") . ": " . $this->wpdb->last_error);

		return $this->wpdb->insert_id;
	}

	function form_element_delete($id) {
		$id = (int) $id;

		$res = $this->wpdb->delete(
			$this->tables["forms_elements"],
			array("id" => $id),
			array("%d")
		);

		return $this->send_message("success", __("Element deleted.", "ezfc"));
	}

	function form_element_duplicate($id) {
		$id = (int) $id;

		$element = $this->form_element_get($id);
		if (!$element) {
			return $this->send_message("error", __("Failed to duplicate element.", "ezfc"));
		}

		$new_element_id = $this->form_element_add($element->f_id, $element->e_id, $element->data);
		return $this->form_element_get($new_element_id);
	}

	function form_elements_save($id, $data) {
		if (!$id) return $this->send_message("error", __("No ID.", "ezfc"));

		// no elements present --> save complete
		if (count($data) < 1) return 1;

		$max = count($data);
		foreach ($data as $id => $element) {
			$element_data = $this->convert_html_fields($element, "encode");
			$element_data = json_encode($element);

			$res = $this->wpdb->update(
				$this->tables["forms_elements"],
				array(
					"data"     => $element_data,
					"position" => $max--
				),
				array("id" => $id),
				array(
					"%s",
					"%d"
				),
				array("%d")
			);

			if (!$res && $res!=0) return $this->send_message("error", __("Form elements update error: ", "ezfc") . $this->wpdb->last_error);
		}

		return 1;
	}


	/**
		form templates
	**/
	function form_templates_get() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["templates"]}");

		return $res;
	}

	function form_template_get($id) {
		if (!$id) return $this->send_message("error", __("No ID given.", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["templates"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	/**
		themes
	**/
	function get_themes() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["themes"]}");
		
		return $res;
	}

	/**
		settings
	**/
	function get_settings($order="id asc") {
		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["options"]} ORDER BY %s",
			$order
		));
		
		return $res;
	}
	

	function update_options($settings, $overwrite=0, $upgrade=0) {
		if (!$settings) $settings = array();
		
		foreach ($settings as $o_id=>$value) {
			$res = $this->wpdb->update(
				$this->tables["options"],
				array("value" => stripslashes($value)),
				array("id" => $o_id),
				array("%s"),
				array("%d")
			);

			if ($res === false) {
				return $this->send_message("error", $this->wpdb->last_error);
			}

			// overwrite settings, skip on name
			if ($overwrite == 0 || $name == "name") continue;

			$res = $this->wpdb->update(
				$this->tables["forms_options"],
				array("value" => stripslashes($value)),
				array("o_id" => $o_id),
				array("%s"),
				array("%d")
			);
		}

		if ($upgrade == 1) {
			$this->setup_db();
			$this->upgrade();
		}

		return $this->send_message("success", __("Settings updated", "ezfc"));
	}

	function add_missing_element_options($f_id=-1) {
		$elements = $this->array_index_key($this->elements_get(), "id");

		if ($f_id != -1) {
			$forms = array($this->form_get($f_id));
		}
		else {
			$forms = $this->forms_get();
		}

		foreach ($forms as $fi=>$form) {
			// insert missing options
			$query = "SELECT 
				o.id, o.value
			FROM
				{$this->tables["options"]} as o
				left join {$this->tables["forms_options"]} as fo on (o.id = fo.o_id AND fo.f_id = {$form->id})
			WHERE
				fo.o_id is NULL";

			$missing_options = $this->wpdb->get_results($query);

			foreach ($missing_options as $mo) {
				$mo_res = $this->wpdb->insert(
					$this->tables["forms_options"],
					array(
						"f_id"  => $form->id,
						"o_id"  => $mo->id,
						"value" => $mo->value
					),
					array(
						"%d",
						"%d",
						"%s"
					)
				);

				if ($mo_res === false) {
					return $this->send_message("error", $this->wpdb->last_error);
				}
			}

			// insert missing element options
			$form_elements_data = $this->wpdb->get_results($this->wpdb->prepare("SELECT id, e_id, data FROM {$this->tables["forms_elements"]} WHERE f_id=%d", $form->id));

			foreach ($form_elements_data as $element_data) {
				$data_array    = json_decode($elements[$element_data->e_id]->data, true);
				$data_el_array = json_decode($element_data->data, true);

				if (!is_array($data_array) || !is_array($data_el_array)) continue;
				
				// merge global element data with form element data
				$data_merged = Ez_Functions::array_merge_recursive_distinct($data_array, $data_el_array);
				$data_new    = json_encode($data_merged);

				$res = $this->wpdb->update(
					$this->tables["forms_elements"],
					array("data" => $data_new),
					array("id" => $element_data->id),
					array("%s"),
					array("%d")
				);

				if ($res === false) {
					return $this->send_message("error", $this->wpdb->last_error);
				}
			}
		}
	}

	function setup_db() {
		$query = file_get_contents(dirname(__FILE__) . "/db.sql");
		if (!$query) {
			die("Error opening file 'db.sql'");
		}

		$query_replaced = str_replace("__PREFIX__", $this->wpdb->prefix, $query);
		$this->execute_multiline_sql($query_replaced);
	}

	function upgrade() {
		$current_version = ezfc_get_version();
		$old_version     = get_option("ezfc_version", "1.0");

		$this->add_missing_element_options();

		// do not perform table changes when old version is current version
		if (version_compare($old_version, $current_version) == 0) return;

		// version specific upgrades
		$query_upgrade = array();

		// file upload reference
		if (version_compare($old_version, "1.1") < 0) {
			$query_upgrade[] = "ALTER IGNORE TABLE `{$this->tables["submissions"]}` ADD COLUMN `ref_id` VARCHAR(16) NOT NULL;";
		}

		// subtotal added
		if (version_compare($old_version, "1.4") < 0) {
			$query_upgrade[] = "INSERT IGNORE INTO `{$this->tables["elements"]}` (`id`, `name`, `description`, `type`, `data`, `icon`) VALUES (15, 'Subtotal', 'Subtotal', 'subtotal', '{\"name\": \"Subtotal\", \"label\": \"Subtotal\", \"calculate_enabled\": 1, \"calculate\": [], \"class\": \"\"}', 'fa-thumb-tack');";
		}

		// woocommerce support
		if (version_compare($old_version, "1.5") < 0) {
			$query_upgrade[] = "ALTER IGNORE TABLE `{$this->tables["submissions"]}` ADD COLUMN `total` DOUBLE NOT NULL";
		}

		if (version_compare($old_version, "1.7") < 0) {
			// changed calculation routine
			$forms = $this->forms_get();

			foreach ($forms as $f) {
				$form_elements = $this->form_elements_get($f->id);

				foreach ($form_elements as $fe) {
					$element_data = json_decode($fe->data);

					if (!property_exists($element_data, "calculate")) continue;

					$element_data->calculate = array($element_data->calculate);
					$element_data_sql        = json_encode($element_data);

					$query_upgrade[] = $this->wpdb->prepare(
						"UPDATE `{$this->tables["forms_elements"]}` SET data='%s' WHERE id=%d",
						$element_data_sql, $fe->id
					);
				}
			}

			$query_upgrade[] = "ALTER IGNORE TABLE `{$this->tables["templates"]}` ADD COLUMN `options` TEXT NOT NULL AFTER `data`;";
		}

		if (version_compare($old_version, "2.2") < 0) {
			$query_upgrade[] = "ALTER IGNORE TABLE `{$this->tables["submissions"]}` ADD COLUMN `payment_id` INT UNSIGNED NOT NULL DEFAULT '0';";
			$query_upgrade[] = "ALTER IGNORE TABLE `{$this->tables["submissions"]}` ADD COLUMN `transaction_id` VARCHAR(50) NOT NULL;";
			$query_upgrade[] = "ALTER IGNORE TABLE `{$this->tables["submissions"]}` ADD COLUMN `token` VARCHAR(20) NOT NULL;";
			$query_upgrade[] = "ALTER IGNORE TABLE `{$this->tables["submissions"]}` ADD COLUMN `user_mail` VARCHAR(100) NOT NULL;";
		}

		// do it already!
		foreach ($query_upgrade as $q) {
			$res = $this->wpdb->query($q);
		}

		// all upgrades finished, update version
		update_option("ezfc_version", $current_version);
	}

	/**
		get all export data
	**/
	function get_export_data() {
		$forms_export    = array();
		$settings_export = array();

		$forms = $this->forms_get();
		foreach ($forms as $f) {
			$forms_export[] = $this->form_get_export_data($f);
		}

		$settings_tmp = $this->get_settings();
		foreach ($settings_tmp as $s) {
			$settings_export[$s->id] = $s->value;
		}

		return array(
			"forms"    => $forms_export,
			"settings" => $settings_export
		);
	}

	/**
		get form export data
	**/
	function form_get_export_data($form=null, $f_id=null) {
		if (!$form && $f_id) $form = $this->form_get($f_id);

		// replace calculation targets with positions
		$elements = $this->form_elements_get($form->id);
		$elements_indexed = $this->array_index_key($elements, "id");

		foreach ($elements as $i => $e) {
			$data = json_decode($e->data);

			if (property_exists($data, "calculate")) {
				foreach ($data->calculate as $ci => $calc) {
					if ($calc->target != 0 && array_key_exists($calc->target, $elements_indexed)) {
						$data->calculate[$ci]->target = $elements_indexed[$calc->target]->position;
					}
				}
			}

			if (property_exists($data, "conditional")) {
				foreach ($data->conditional as $ci => $cond) {
					if ($cond->target != 0 && isset($elements_indexed[$cond->target])) {
						$data->conditional[$ci]->target = $elements_indexed[$cond->target]->position;
					}
				}
			}

			// convert some element properties to htmlentities
			$data = $this->convert_html_fields($data, "encode");

			$elements[$i]->data = json_encode($data);
		}

		return array(
			"form"     => $form,
			"elements" => $elements,
			"options"  => $this->form_get_options($form->id)
		);
	}

	public function convert_html_fields($object, $convert="encode") {
		$convert_html_keys = array("name", "label", "html", "options" => array("text"));
		if (!is_object($object)) return $object;

		foreach ($convert_html_keys as $key => $val) {
			if (is_array($val)) {
				foreach ($val as $array_val) {
					if (!property_exists($object, $array_val)) continue;

					foreach ($val as $array_val) {
						if (property_exists($object->$key, $array_val)) {
							$object->$key->$array_val = $convert=="encode" ? htmlentities($object->$key->$array_val) : html_entity_decode($object->$key->$array_val);
						}
					}
				}
			}
			else {
				if (!property_exists($object, $val)) continue;

				$object->$val = $convert=="encode" ? htmlentities($object->$val) : html_entity_decode($object->$val);
			}
		}

		return $object;
	}

	/**
		import all data
	**/
	function import_data($data) {
		$data_json = get_magic_quotes_gpc() ? json_decode(stripslashes($data)) : json_decode($data);

		// import settings
		$this->update_options($data_json->settings, 0, 0);

		// import forms
		if (property_exists($data_json, "forms") && count($data_json->forms) > 0) {
			foreach ($data_json->forms as $i => $f) {
				$this->form_import($f);
			}
		}

		return 1;
	}

	/**
		test mail
	**/
	public function send_test_mail($recipient) {
		$subject = __("ez Form Calculator Test Email", "ezfc");
		$text    = __("This is a test email sent by ez Form Calculator.", "ezfc");

		// use smtp
		if (get_option("ezfc_email_smtp_enabled") == 1) {
			require_once(plugin_dir_path(__FILE__) . "lib/PHPMailer/PHPMailerAutoload.php");

			$mail = new PHPMailer();
			$mail->isSMTP();
			$mail->SMTPAuth   = true;
			//$mail->SMTPDebug  = 3;
			$mail->Host       = get_option("ezfc_email_smtp_host");
			$mail->Username   = get_option("ezfc_email_smtp_user");
			$mail->Password   = get_option("ezfc_email_smtp_pass");
			$mail->Port       = get_option("ezfc_email_smtp_port");
			$mail->SMTPSecure = get_option("ezfc_email_smtp_secure");

			$mail->addAddress($recipient);
			$mail->Subject = $subject;
			$mail->Body    = $text;

			if ($mail->send()) {
				$res = __("Mail successfully sent.", "ezfc");
			}
			else {
				$res = __("Unable to send mails: ", "ezfc") . $mail->ErrorInfo;
			}

			return $res;
		}
		else {
			$res = wp_mail(
				$recipient,
				$subject,
				$text
			);

			return $res==1 ? __("Mail successfully sent.", "ezfc") : __("Unable to send mails.", "ezfc");
		}
	}

	/**
		ajax message
	**/
	function send_message($type, $msg, $id=0) {
		return array(
			$type 	=> $msg,
			"id"	=> $id
		);
	}

	private function array_index_key($array, $key) {
		$ret_array = array();

		foreach ($array as $v) {
			if (is_object($v)) {
				$ret_array[$v->$key] = $v;
			}
			if (is_array($v)) {
				$ret_array[$v[$key]] = $v;
			}
		}

		return $ret_array;
	}

	private function execute_multiline_sql($sql, $delim=";") {
	    global $wpdb;
	    
	    $sqlParts = $this->split_sql_file($sql, $delim);
	    foreach($sqlParts as $part) {
	        $res = $wpdb->query($part);

	        if ($res === false) {
	        	$wpdb->print_error();
	        	return false;
	        }
	    }

	    return true;
	}

	private function split_sql_file($sql, $delimiter) {
	   // Split up our string into "possible" SQL statements.
	   $tokens = explode($delimiter, $sql);

	   // try to save mem.
	   $sql = "";
	   $output = array();

	   // we don't actually care about the matches preg gives us.
	   $matches = array();

	   // this is faster than calling count($oktens) every time thru the loop.
	   $token_count = count($tokens);
	   for ($i = 0; $i < $token_count; $i++)
	   {
	      // Don't wanna add an empty string as the last thing in the array.
	      if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0)))
	      {
	         // This is the total number of single quotes in the token.
	         $total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
	         // Counts single quotes that are preceded by an odd number of backslashes,
	         // which means they're escaped quotes.
	         $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

	         $unescaped_quotes = $total_quotes - $escaped_quotes;

	         // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
	         if (($unescaped_quotes % 2) == 0)
	         {
	            // It's a complete sql statement.
	            $output[] = $tokens[$i];
	            // save memory.
	            $tokens[$i] = "";
	         }
	         else
	         {
	            // incomplete sql statement. keep adding tokens until we have a complete one.
	            // $temp will hold what we have so far.
	            $temp = $tokens[$i] . $delimiter;
	            // save memory..
	            $tokens[$i] = "";

	            // Do we have a complete statement yet?
	            $complete_stmt = false;

	            for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++)
	            {
	               // This is the total number of single quotes in the token.
	               $total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
	               // Counts single quotes that are preceded by an odd number of backslashes,
	               // which means they're escaped quotes.
	               $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

	               $unescaped_quotes = $total_quotes - $escaped_quotes;

	               if (($unescaped_quotes % 2) == 1)
	               {
	                  // odd number of unescaped quotes. In combination with the previous incomplete
	                  // statement(s), we now have a complete statement. (2 odds always make an even)
	                  $output[] = $temp . $tokens[$j];

	                  // save memory.
	                  $tokens[$j] = "";
	                  $temp = "";

	                  // exit the loop.
	                  $complete_stmt = true;
	                  // make sure the outer loop continues at the right point.
	                  $i = $j;
	               }
	               else
	               {
	                  // even number of unescaped quotes. We still don't have a complete statement.
	                  // (1 odd and 1 even always make an odd)
	                  $temp .= $tokens[$j] . $delimiter;
	                  // save memory.
	                  $tokens[$j] = "";
	               }

	            } // for..
	         } // else
	      }
	   }

	   return $output;
	}
}