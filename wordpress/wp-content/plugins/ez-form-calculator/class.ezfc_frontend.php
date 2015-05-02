<?php

class Ezfc_frontend {
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
			"themes"         => "{$this->wpdb->prefix}ezfcf_themes"
		);
	}

	function debug($msg) {
		if (get_option("ezfc_debug_mode", 0) == 0) return;

		$this->wpdb->insert(
			$this->tables["debug"],
			array("msg" => $msg),
			array("%s")
		);
	}

	function form_get($id, $name=null) {
		if (!$id && !$name) return $this->send_message("error", __("No id or name found.", "ezfc"));

		if ($id) {
			$res = $this->wpdb->get_row($this->wpdb->prepare(
				"SELECT * FROM {$this->tables["forms"]} WHERE id=%d",
				$id
			));
		}

		if ($name) {
			$res = $this->wpdb->get_row($this->wpdb->prepare(
				"SELECT * FROM {$this->tables["forms"]} WHERE name=%s",
				$name
			));
		}

		return $res;
	}

	function form_get_count() {
		$res = $this->wpdb->get_var("SELECT count(*) FROM {$this->tables["forms"]}");

		return $res;
	}

	function form_get_options($id) {
		if (!$id) return $this->send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT o.name, fo.value
			FROM {$this->tables["forms_options"]} AS fo
			JOIN {$this->tables["options"]} AS o
				ON fo.o_id=o.id
			WHERE fo.f_id=%d;",
			$id
		));

		return $res;
	}

	function form_get_submission_files($ref_id) {
		if (!$ref_id) return $this->send_message("error", __("No ref_id", "ezfc"));

		$files = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["files"]} WHERE ref_id=%s",
			$ref_id
		));

		return $files;
	}

	/**
		elements
	**/
	function elements_get() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["elements"]} ORDER BY id ASC");

		$elements_indexed = array();
		foreach ($res as $element) {
			$elements_indexed[$element->id] = $element;
		}

		return $elements_indexed;
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
	function form_elements_get($id) {
		if (!$id) return $this->send_message("error", __("No ID given.", "ezfc"));

		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["forms_elements"]} WHERE f_id=%d ORDER BY position DESC",
			$id
		));

		return $res;
	}

	/**
		get submission entry
	**/
	function submission_get($id) {
		if (!$id) return $this->send_message("error", __("No ID.", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["submissions"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	/**
		misc functions
	**/
	function check_input($id, $data, $ref_id) {
		if (!$id || !$data) return $this->send_message("error", __("No ID or no data.", "ezfc"));

		$elements      = $this->array_index_key($this->elements_get(), "id");
		$form_elements = $this->array_index_key($this->form_elements_get($id), "id");
		$options       = $this->array_index_key($this->form_get_options($id), "name");

		if ($options["submission_enabled"]->value == 0) {
			return $this->send_message("error", __("Submission not allowed.", "ezfc"));
		}

		foreach ($form_elements as $fe_id => $form_element) {
			// special field - email double check
			if (strpos($fe_id, "email_check") !== false) continue;

			$el_reference = $elements[$form_element->e_id];

			// skip non-input fields (and recaptcha since it is verified in ajax.php)
			$skip_check_elements = "image,hr,html,recaptcha,stepstart,stepend";
			$skip_check_array    = explode(",", $skip_check_elements);
			if (in_array($el_reference->type, $skip_check_array)) continue;

			// skip if the field was hidden by conditional logic
			if (isset($data[$fe_id]) && !is_array($data[$fe_id]) && strpos($data[$fe_id], "__HIDDEN__") !== false) continue;

			// checkbox (shouldn't be required, really)
			if (isset($data[$fe_id]) && is_array($data[$fe_id]) && $el_reference->type == "checkbox") {
				if (Ez_Functions::array_empty($data[$fe_id])) {
					return $this->send_message("error", __("This field is required.", "ezfc"), $fe_id);
				}
			}
			else {
				$empty = false;

				if (isset($data[$fe_id])) {
					$input_value = $data[$fe_id];

					if (is_array($data[$fe_id])) {
						$empty = Ez_Functions::array_empty($data[$fe_id]);
					}
					else {
						$input_value = trim($data[$fe_id]);

						// check if submitted data string is empty
						$empty = ((!is_string($input_value) || $input_value == "") && $el_reference->type != "fileupload") ? true : false;
					}
				}
				// no submit data for this element exists -> empty
				else {
					$empty = true;
				}

				$element_data = json_decode($form_element->data);

				// check if element is required and no value was submitted
				if (property_exists($element_data, "required") && (int) $element_data->required == 1 && $empty) {
					return $this->send_message("error", __("This field is required.", "ezfc"), $fe_id);
				}

				// run filters
				if (!$empty) {
					switch ($el_reference->type) {
						case "input":
							if (property_exists($element_data, "custom_regex") && !empty($element_data->custom_regex)) {
								if (!preg_match($element_data->custom_regex, $input_value)) {
									return $this->send_message("error", __($element_data->custom_error_message, "ezfc"), $fe_id);
								}
							}
						break;

						case "daterange":
							if (!is_array($input_value)	||
								!isset($input_value[0]) || !isset($input_value[1]) ||
								!Ez_Functions::check_valid_date($options["datepicker_format"]->value, $input_value[0], true) ||
								!Ez_Functions::check_valid_date($options["datepicker_format"]->value, $input_value[1], true)) {
								return $this->send_message("error", __("Please enter a valid date range.", "ezfc"), $fe_id);
							}
						break;

						case "email":
							if (!filter_var($input_value, FILTER_VALIDATE_EMAIL)) {
								return $this->send_message("error", __("Please enter a valid email address.", "ezfc"), $fe_id);
							}

							// double check email address
							if (property_exists($element_data, "double_check") && $element_data->double_check == 1) {
								$email_check_name = "{$fe_id}_email_check";

								if (!$data[$email_check_name] ||
									$data[$email_check_name] !== $input_value) {
									return $this->send_message("error", __("Please check your email address.", "ezfc"), $fe_id);
								}
							}
						break;

						case "fileupload":
							// yeah i know, this sucks :/
							if ($element_data->required == 1) {
								$checkfile = $this->wpdb->get_row($this->wpdb->prepare(
									"SELECT id FROM {$this->tables["files"]} WHERE ref_id=%s",
									$ref_id
								));

								if (!$checkfile) return $this->send_message("error", __("No file was uploaded yet.", "ezfc"), $fe_id);
							}
						break;

						case "numbers":
							if (!is_numeric($input_value)) {
								return $this->send_message("error", __("Please enter a valid number.", "ezfc"), $fe_id);
							}

							// min / max values
							if (!empty($element_data->min)) {
								if ($input_value < $element_data->min) return $this->send_message("error", __("Minimum value: ", "ezfc") . $element_data->min , $fe_id);
							}
							if (!empty($element_data->max)) {
								if ($input_value > $element_data->max) return $this->send_message("error", __("Maximum value: ", "ezfc") . $element_data->max , $fe_id);
							}
						break;
					}
				}
			}
		}

		// no errors found
		return $this->send_message("success", "");
	}

	/**
		prepare submission data
	**/
	function prepare_submission_data($id, $data, $force_paypal=false) {
		$raw_values = array();
		foreach ($data["ezfc_element"] as $fe_id => $value) {
			$raw_values[$fe_id] = $value;
		}

		$this->submission_data = array(
			"elements"      => $this->array_index_key($this->elements_get(), "id"),
			"form_elements" => $this->array_index_key($this->form_elements_get($id), "id"),
			"options"       => $this->array_index_key($this->form_get_options($id), "name"),
			"raw_values"    => $raw_values,
			"ref_id"        => $data["ref_id"],
			"force_paypal"  => $force_paypal
		);
	}

	/**
		insert submission
	**/
	function insert($id, $data, $ref_id, $send_mail=true, $payment=array()) {
		if (!$id || !$data || !$this->submission_data) return $this->send_message("error", __("No ID or no data.", "ezfc"));
		if ($this->form_get_count() > 1) return $this->send_message("error", __("Maximum forms exceeded."));

		if (count($payment) < 1) {
			$payment = array(
				"id"             => 0,
				"token"          => "",
				"transaction_id" => 0
			);
		}

		// spam protection
		$spam_time = $this->submission_data["options"]["spam_time"]->value;
		$spam = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT 1 FROM {$this->tables["submissions"]} WHERE ip='%s' AND date>=DATE_ADD(NOW(), INTERVAL -{$spam_time} SECOND)",
			$_SERVER["REMOTE_ADDR"]
		));

		if ($spam) {
			return $this->send_message("error", __("Spam protection: you need to wait {$spam_time} seconds before you can submit anything.", "ezfc"));
		}

		$this->debug("Add submission to database start: id={$id}, send_mail={$send_mail}");

		// check for user mail address
		$user_mail = "";
		foreach ($data as $fe_id => $value) {
			// email check
			if (strpos($fe_id, "email_check") !== false) continue;

			// element could not be found
			if (!isset($this->submission_data["form_elements"][$fe_id])) {
				return $this->send_message("error", __("Element could not be found.", "ezfc"));
			}

			$element      = $this->submission_data["form_elements"][$fe_id];
			$element_data = json_decode($element->data);

			if (property_exists($element_data, "use_address") && $element_data->use_address == 1) {
				$user_mail = $this->submission_data["raw_values"][$fe_id];
				$this->debug("User email address found: {$user_mail}");
			}
		}

		// mail output
		$output_data = $this->get_mail_output($this->submission_data);

		// check minimum value
		if (isset($this->submission_data["options"]["min_submit_value"]) && (float) $output_data["total"] < (float) $this->submission_data["options"]["min_submit_value"]->value) {
			$min_submit_value_text = sprintf($this->submission_data["options"]["min_submit_value_text"]->value, $this->submission_data["options"]["min_submit_value"]->value);

			return $this->send_message("error", __($min_submit_value_text, "ezfc"));
		}

		/**
			* hook: before submission
			* @param int $form_id ID of this form
		**/
		do_action("ezfc_before_submission", $id);

		// insert into db
		$res = $this->wpdb->insert(
			$this->tables["submissions"],
			array(
				"f_id"           => $id,
				"data"           => json_encode($data),
				"content"        => $output_data["result"],
				"ip"             => $_SERVER["REMOTE_ADDR"],
				"ref_id"         => $ref_id,
				"total"          => $output_data["total"],
				"payment_id"     => $payment["id"],
				"transaction_id" => $payment["transaction_id"],
				"token"          => $payment["token"],
				"user_mail"      => $user_mail
			),
			array(
				"%d",
				"%s",
				"%s",
				"%s",
				"%s",
				"%f",
				"%d",
				"%s",
				"%s",
				"%s"
			)
		);

		$insert_id = $this->wpdb->insert_id;

		if (!$res) return $this->send_message("error", __("Submission failed.", "ezfc"));
		$this->debug("Successfully added submission to db: id={$insert_id}");

		// mailchimp integration
		if ($this->submission_data["options"]["mailchimp_add"]->value == 1) {
			// load mailchimp api wrapper
			require_once(dirname( __FILE__ ) . "/lib/mailchimp/MailChimp.php");
			$mailchimp_api_key = get_option("ezfc_mailchimp_api_key", -1);

			if (!empty($mailchimp_api_key) && $mailchimp_api_key != -1) {
				$mailchimp = new Drewm_MailChimp($mailchimp_api_key);
				$mres = $mailchimp->call("lists/subscribe", array(
					"id"    => $this->submission_data["options"]["mailchimp_list"]->value,
					"email" => array("email" => $user_mail)
				));

				if (!$mres) {
					$this->debug("Unable to add email address to MailChimp list.");
				}
				else {
					$this->debug("Email address added to MailChimp list id={$this->submission_data["options"]["mailchimp_list"]->value}");
				}
			}
		}

		// add to cart
		if (get_option("ezfc_woocommerce", 0) != 0 && get_option("ezfc_woocommerce_product_id", 0) != 0) {
			$this->debug("Adding submission to WooCommerce cart...");

			// this is required as anonymous users cannot add an ezfc product to the cart
			if (!WC()->session->has_session()) {
				WC()->session->set_customer_session_cookie(true);
				$this->debug("WC session could not be found -> set customer session cookie");
			}

			$product_id = get_option("ezfc_woocommerce_product_id");

			$cart_item_key = WC()->instance()->cart->add_to_cart($product_id, 1, 0, array(
				"ezfc_id"      => $insert_id,
				"ezfc_form"    => $id,
				"ezfc_content" => $output_data["result"]
			));

			if (!$cart_item_key) {
				return $this->send_message("error", __("Unable to add the product to the cart.", "ezfc"));
			}
			
			$this->debug("Submission added to the cart successfully: cart_item_key={$cart_item_key}");
			return $this->send_message("success", __(get_option("ezfc_woocommerce_text"), "ezfc"));
		}

		if ($send_mail) {
			$this->send_mails(false, $output_data, $user_mail);
		}

		/**
			* @hook submission successful
			* @param int $submission_id The ID of this submission
			* @param float $total The total
			* @param string $user_email User email address (if any)
			* @param int $form_id The ID of this form
		**/
		do_action("ezfc_after_submission", $this->wpdb->insert_id, $output_data["total"], $user_mail, $id);

		return $this->send_message("success", __($this->submission_data["options"]["success_text"]->value, "ezfc"));
	}

	/**
		get email output
	**/
	function get_mail_output($submission_data) {
		$currency = $submission_data["options"]["currency"]->value;
		$total    = 0;
		$out      = array();

		// output prefix
		$out_pre = "
		<html>
		<head><meta charset='utf-8' /></head>
		<body>";

		// output suffix
		$out_suf = "
		</body>
		</html>";

		// result output
		$out[] = "<table>";

		$i     = 0;
		$total = 0;
		foreach ($submission_data["raw_values"] as $fe_id => $value) {
			$tmp_out = $this->get_element_output($fe_id, $value, $i);

			$out[]  = $tmp_out["output"];
			
			if ($tmp_out["override"]) $total  = $tmp_out["total"];
			else                      $total += $tmp_out["total"];

			$i++;
		}

		// show total price in email or not
		if ($submission_data["options"]["email_show_total_price"]->value == 1) {
			$out[] = "<tr style='margin: 5px 0; background-color: #eee; border-top: #aaa 1px solid; font-weight: bold;'>";
			$out[] = "	<td colspan='2'>" . __("Total", "ezfc") . "</td>";
			$out[] = "	<td style='text-align: right;'>" .  $this->number_format($total) . "</td>";
			$out[] = "</tr>";
		}

		$out[] = "<tr><td colspan='3'>Powered by <a href='http://codecanyon.net/item/ez-form-calculator-wordpress-plugin/7595334?ref=keksdieb'>ez Form Calculator</a></td></tr>";

		$out[] = "</table>";

		// implode content
		$result_content = implode("", $out);

		// put email text into vars
		$mail_content_replace = $submission_data["options"]["email_text"]->value;
		if ($submission_data["options"]["pp_enabled"]->value == 1 || $submission_data["force_paypal"]) {
			$mail_content_replace = $submission_data["options"]["email_text_pp"]->value;
		}

		$mail_admin_content_replace = $submission_data["options"]["email_admin_text"]->value;

		// get uploaded files
		$files = $this->form_get_submission_files($submission_data["ref_id"]);
		$files_output = "<p>Files</p>";

		if (count($files) > 0) {
			foreach ($files as $file) {
				$filename = basename($file->file);
				$files_output .= "<p><a href='{$file->url}' target='_blank'>{$filename}</a></p>";
			}
		}

		// replace placeholders with form values
		foreach ($submission_data["form_elements"] as $fe_id => $fe) {
			$fe_data = json_decode($submission_data["form_elements"][$fe_id]->data);

			if (!isset($submission_data["raw_values"][$fe_id])) continue;

			$value_to_replace = $this->get_target_value_from_input($fe_id, $submission_data["raw_values"][$fe_id]);
			if (is_array($submission_data["raw_values"][$fe_id])) {
				$value_to_replace = implode(", ", $submission_data["raw_values"][$fe_id]);
			}

			$mail_content_replace       = str_ireplace("{{" . $fe_data->name . "}}", $value_to_replace, $mail_content_replace);
			$mail_admin_content_replace = str_ireplace("{{" . $fe_data->name . "}}", $value_to_replace, $mail_admin_content_replace);
		}

		// replace other values
		$replaces = array(
			"files"  => $files_output,
			"result" => $result_content,
			"total"  => $this->number_format($total)
		);

		foreach ($replaces as $replace => $replace_value) {
			$mail_content_replace       = str_ireplace("{{" . $replace . "}}", $replace_value, $mail_content_replace);
			$mail_admin_content_replace = str_ireplace("{{" . $replace . "}}", $replace_value, $mail_admin_content_replace);
		}

		// put together email contents for user
		$mail_content  = $out_pre;
		$mail_content .= $mail_content_replace;
		$mail_content .= $out_suf;

		// put together email contents for admin
		$mail_admin_content  = $out_pre;
		$mail_admin_content .= $mail_admin_content_replace;
		$mail_admin_content .= $out_suf;

		return array(
			"user"   => $mail_content,
			"admin"  => $mail_admin_content,
			"result" => $result_content,
			"total"  => $total
		);
	}

	/**
		get output from form elements
	**/
	function get_element_output($fe_id, $value, $even=1) {
		if (!is_array($value)) {
			// skip email double check
			if (strpos($fe_id, "email_check") !== false) return array("output" => "", "total" => 0, "override" => false);

			// skip hidden field by conditional logic
			if (strpos($value, "__HIDDEN__") !== false) return array("output" => "", "total" => 0, "override" => false);
		}

		$currency       = $this->submission_data["options"]["currency"]->value;
		$discount_total = 0;
		$el_out         = array();
		$price_override = false;
		$total          = 0;
		$total_out      = array();
		$value_valid    = true;
		$value_out      = array();

		$element        = json_decode($this->submission_data["form_elements"][$fe_id]->data);
		$element_type   = $this->submission_data["elements"][$element->e_id]->type;
		$element_values = property_exists($element, "value") ? $element->value : "";

		switch ($element_type) {
			case "numbers":
				$value_out[] = $this->number_format($this->submission_data["raw_values"][$fe_id], $element);
			break;

			case "checkbox":
				//$element_values = $this->array_index_key($element->options, "value");
				$element_values = $element->options;

				foreach ($value as $chk_i=>$chk_value) {
					// value was not found -> user probably changed it
					if (!isset($element_values[$chk_value])) {
						$value_out[] = $chk_value . "<br>" . __("Value '{$chk_value}' was not found. Either the user changed it manually or the value was changed otherwise in the meantime.", "ezfc");
						$value_valid = false;
					}
					// value found! we are happy!
					else {
						$value_out[] = $element_values[$chk_value]->text;
					}
				}
			break;

			case "dropdown":
			case "radio":
			case "payment":
				//$element_values = $this->array_index_key($element->options, "value");
				$element_values = $element->options;

				// value was not found -> user probably changed it
				if (!isset($element_values[$value])) {
					$value_out[] = $value . "<br>" . __("Value '{$value}' was not found. Either the user changed it manually or the value was changed otherwise in the meantime.", "ezfc");
					$value_valid = false;
				}
				// value found! we are happy!
				else {
					$value_out[] = $element_values[$value]->text;
				}
			break;

			case "daterange":
				$value_out[] = __("From", "ezfc") . ":<br>" . $this->submission_data["raw_values"][$fe_id][0];
				$value_out[] = __("To", "ezfc") . ":<br>" . $this->submission_data["raw_values"][$fe_id][1];
			break;

			case "email":
				$value_out[] = "<a href='mailto:{$value}'>{$value}</a>";
			break;

			// no action
			case "hr":
			case "html":
			case "recaptcha":
			case "stepstart":
			case "stepend":
			break;

			default:
				$value_out[] = $this->submission_data["raw_values"][$fe_id];
			break;
		}

		if (property_exists($element, "calculate_enabled") && $element->calculate_enabled == 1) {
			// support for older versions
			if (!is_array($value)) $value_array = array($value);
			else                   $value_array = $value;

			// counter value for dateranges so the calculating info will not be displayed twice
			$daterange_counter = 0;

			foreach ($value_array as $input_value) {
				// skip second daterange input value
				if ($element_type == "daterange" && $daterange_counter%2==1) continue;

				// total price
				$tmp_total = (double) $this->get_target_value_from_input($fe_id, $input_value);
				// price details output
				$tmp_total_out = array();
				// show price for current element
				$show_price = true;

				// calculate value * factor
				if (property_exists($element, "factor") && $value) {
					if (empty($element->factor) || !is_numeric($element->factor)) $element->factor = 1;

					// special calculation for daterange element
					if ($element_type == "daterange") {
						$datepicker_format = $this->submission_data["options"]["datepicker_format"]->value;
						$days = Ez_Functions::count_days_format($datepicker_format, $value[0], $value[1]);

						$tmp_total = (double) $days * $element->factor;
						$tmp_total_out[] = "= {$days} " . __("day(s)", "ezfc");

						$show_price = false;
						$daterange_counter++;
					}
					else {
						$tmp_total = (double) $value * $element->factor;
						$tmp_total_out[] = "{$value} * {$element->factor}";
					}
				}

				// custom calculations
				if (count($element->calculate) > 0) {
					foreach ($element->calculate as $calc_index => $calc_array) {
						if ($calc_array->operator == "0" || ($calc_array->target == "0" && empty($calc_array->value))) continue;

						$use_target_value = $calc_array->target!="0";
						$use_custom_value = (!$use_target_value && !empty($calc_array->value));

						// check if target element exists (only when a target was selected)
						if ($use_target_value && !$use_custom_value && !isset($this->submission_data["form_elements"][$calc_array->target])) {
							$tmp_total = 0;
							$tmp_total_out[] = __("No target found: ", "ezfc") . $calc_array->target;
						}

						if ($use_target_value || $use_custom_value) {
							// use value from target element
							if ($use_target_value) {
								if (isset($this->submission_data["raw_values"][$calc_array->target])) {
									$target_value = $this->get_target_value_from_input($calc_array->target, $this->submission_data["raw_values"][$calc_array->target]);
								}
								else {
									$target_value = 0;
								}
							}
							// use custom value
							else {
								$target_value = $calc_array->value;
							}

							switch ($calc_array->operator) {
								case "add":
									$tmp_total_out[] = "+ {$target_value}";
									$tmp_total       = (double) $tmp_total + $target_value;
								break;

								case "subtract":
									$tmp_total_out[] = "- {$target_value}";
									$tmp_total       = (double) $tmp_total - $target_value;
								break;

								case "multiply":
									$tmp_total_out[] = "* {$target_value}";
									$tmp_total       = (double) $tmp_total * $target_value;
								break;

								case "divide":
									if ($target_value == 0) {
										$tmp_total = 0;
										$tmp_total_out[] = __("Cannot divide by target factor 0", "ezfc");
									}
									else {
										if (property_exists($element, "calculate_before") && $element->calculate_before == "1") {
											$tmp_total_out[] = "{$target_value} / {$tmp_total}";
											$tmp_total       = round($target_value / (double) $tmp_total, 2);
										}
										else {
											$tmp_total_out[] = "{$tmp_total} / {$target_value}";
											$tmp_total       = round((double) $tmp_total / $target_value, 2);
										}
									}
								break;

								case "equals":
									if (property_exists($this->submission_data["form_elements"][$calc_array->target], "factor")) {
										$target_factor   = $this->submission_data["form_elements"][$calc_array->target]->factor;
										$tmp_total_out[] = "= {$target_factor} * {$currency} {$target_value}";
										$tmp_total       = (double) $target_factor * $target_value;
									}
									else {
										$tmp_total_out[] = "= {$target_value}";	
										$tmp_total       = (double) $target_value;
									}
								break;

								case "power":
									$tmp_total_out[] = "{$tmp_total} ^ {$target_value}";
									$tmp_total       = round(pow((double) $tmp_total, $target_value), 2);
								break;
							}
						}
					}
				}

				// add element value to total value
				if (property_exists($element, "overwrite_price") && (int) $element->overwrite_price == 1) {
					$price_override = true;
					$total          = $tmp_total;

					$tmp_total_out[] = "<strong>" . __("Price override", "ezfc") . "</strong>";
				}
				else {
					if ($element_type != "subtotal") {
						$total += $tmp_total;
					}
				}

				// discount
				if (property_exists($element, "discount") && count($element->discount) > 0) {
					foreach ($element->discount as $discount) {
						// if fields are left blank, set min/max to infinity
						if (!$discount->range_min && $discount->range_min !== 0) $discount->range_min = -INF;
						if (!$discount->range_max && $discount->range_max !== 0) $discount->range_max = INF;

						if ($tmp_total >= $discount->range_min && $tmp_total <= $discount->range_max) {
							$discount->value = (float) $discount->discount_value;
							$tmp_total_out[] = "{$currency} {$tmp_total}";

							switch ($discount->operator) {
								case "add":
									$tmp_total_out[] = __("Discount:") . " + " . $this->number_format($discount->discount_value, $element);
									$discount_total  = $discount->discount_value;
								break;

								case "subtract":
									$tmp_total_out[] = __("Discount:") . " - " . $this->number_format($discount->discount_value, $element);
									$discount_total  = -$discount->discount_value;
								break;

								case "percent_add":
									$tmp_total_out[] = __("Discount:") . " +{$discount->discount_value}%";
									$discount_total  = round($tmp_total * ($discount->discount_value / 100), 2);
								break;

								case "percent_sub":
									$tmp_total_out[] = __("Discount:") . " -{$discount->discount_value}%";
									$discount_total  = round(-($tmp_total * ($discount->discount_value / 100)), 2);
								break;

								case "equals":
									$tmp_total_out[] = __("Discount:") . " = " . $this->number_format($discount->discount_value);
									$discount_total  = 0;
									// overwrite temporary price here
									$tmp_total       = $discount->discount_value;
								break;
							}

							$tmp_total += $discount_total;
							$total     += $discount_total;
						}
					}
				}

				// build string for output
				if ($show_price) {
					$value_out_str = !$tmp_total ? "-" : $this->number_format($tmp_total);

					if ($tmp_total_out) {
						$value_out_str = implode("<br>", $tmp_total_out) . "<br>= {$value_out_str}";
					}
				}
				else {
					$value_out_str = implode("<br>", $tmp_total_out);
				}

				$total_out[] = $value_out_str;
			}
		}

		$tr_bg = $even%2==1 ? "#fff" : "#efefef";

		$el_out[] = "<tr style='margin: 5px 0; background-color: {$tr_bg};'>";
		$el_out[] = "	<td style='vertical-align: top;'>{$element->name}</td>";
		$el_out[] = "	<td style='vertical-align: top;'>" . implode("<hr style='border: 0; border-bottom: #ccc 1px solid;' />", $value_out) . "</td>";
		$el_out[] = "	<td style='vertical-align: top; text-align: right;'>" . implode("<hr style='border: 0; border-bottom: #ccc 1px solid;' />", $total_out) . "</td>";
		$el_out[] = "</tr>";

		return array(
			"output"   => implode("", $el_out),
			"total"    => $total,
			"override" => $price_override
		);
	}

	function number_format($number, $element_data = null) {
		if (empty($number)) return;
		
		// check if element value should not formatted as currency
		if ($element_data) {
			if (!property_exists($element_data, "is_currency") ||
				(property_exists($element_data, "is_currency") && $element_data->is_currency == 0)) {
				return $number;
			}
		}

		$currency = $this->submission_data["options"]["currency"]->value;
		$currency_position = $this->submission_data["options"]["currency_position"]->value;

		// todo
		$number_formatted = number_format($number, 2);

		if ($currency_position == 0) {
			return "{$currency} {$number_formatted}";
		}
		else {
			return "{$number_formatted} {$currency}";
		}
	}

	function get_target_value_from_input($target_id, $input_value) {
		if (!isset($this->submission_data["form_elements"][$target_id])) return false;

		$target  = $this->submission_data["form_elements"][$target_id];
		$value   = 0;

		$data    = json_decode($target->data);
		$element = $this->submission_data["elements"][$target->e_id]->type;

		if (property_exists($data, "options") && is_array($data->options)) {
			// checkboxes
			if (is_array($input_value)) {
				if (count($input_value) < 1) return 0;

				// iterate through all checkboxes
				$checkbox_total = 0;
				foreach ($input_value as $i => $checkbox_value) {
					// checkbox index was not found
					if (!array_key_exists($i, $data->options)) return 0;

					$checkbox_total += (float) $data->options[$i]->value;
				}

				return $checkbox_total;
			}
			
			if (!array_key_exists($input_value, $data->options)) return false;
			if (!property_exists($data->options[$input_value], "value")) return false;

			$value = $data->options[$input_value]->value;
		}
		else if ($element == "daterange") {
			$datepicker_format = $this->submission_data["options"]["datepicker_format"]->value;

			if (isset($input_value[0]) && isset($input_value[1])) {
				$days = Ez_Functions::count_days_format($datepicker_format, $input_value[0], $input_value[1]);
				$value = $days;
			}
			else {
				$value = 0;
			}
		}
		else {
			$value = $this->submission_data["raw_values"][$target_id];

			if (property_exists($data, "factor") && $data->factor && $data->factor !== 0) {
				$value *= $data->factor;
			}
		}

		return $value;
	}

	/**
		calculates total value from submitted data
	**/
	function get_total($data) {
		$total = 0;

		foreach ($data as $fe_id => $value) {
			$tmp_out = $this->get_element_output($fe_id, $value);
			$total  += $tmp_out["total"];

			if ($tmp_out["override"]) $total = $tmp_out["total"];
		}

		return $total;
	}

	/**
		send mails (obviously)
	**/
	function send_mails($submission_id, $custom_mail_output=false, $user_mail=false) {
		$this->debug("Preparing to send mail(s)...");

		// generate email content from submission
		// use $this->prepare_submission_data() first!
		if ($submission_id != false) {
			$submission = $this->submission_get($submission_id);
			$user_mail  = $submission->user_mail;

			$mail_output = $this->get_mail_output($this->submission_data);
		}
		// send emails later
		else if ($custom_mail_output != false) {
			$mail_output = $custom_mail_output;
		}

		$this->debug("Target email: $user_mail");

		// use smtp
		$use_smtp = get_option("ezfc_email_smtp_enabled")==1 ? true : false;
		if ($use_smtp) {
			$this->smtp_setup();
		}

		// admin mail
		if (!empty($this->submission_data["options"]["email_recipient"]->value)) {
			$mail_admin_headers   = array();
			$mail_admin_headers[] = "Content-type: text/html";

			if ($user_mail && !empty($user_mail)) {
				$mail_admin_headers[] = "Reply-to: \"{$user_mail}\"";

				if ($use_smtp) $this->smtp->addReplyTo($user_mail);
			}

			// smtp
			if ($use_smtp) {
				$this->smtp->addAddress($this->submission_data["options"]["email_recipient"]->value);
				$this->smtp->Subject = __($this->submission_data["options"]["email_admin_subject"]->value, "ezfc");
				$this->smtp->Body    = nl2br($mail_output["admin"]);
				$res = $this->smtp->send() ? 1 : 0;
			}
			else {
				$res = wp_mail(
					$this->submission_data["options"]["email_recipient"]->value,
					__($this->submission_data["options"]["email_admin_subject"]->value, "ezfc"),
					nl2br($mail_output["admin"]),
					$mail_admin_headers
				);
			}

			$this->debug("Email delivery to admin: $res");
			$this->debug(var_export($mail_admin_headers, true));
		}
		else {
			$this->debug("No admin email recipient found.");
		}

		// user mail
		if ($user_mail && !empty($user_mail)) {
			$mail_subject = ($this->submission_data["options"]["pp_enabled"]->value==1 || $this->submission_data["force_paypal"]) ? $this->submission_data["options"]["email_subject_pp"]->value : $this->submission_data["options"]["email_subject"]->value;
			$mail_from = !empty($this->submission_data["options"]["email_admin_sender"]->value) ? $this->submission_data["options"]["email_admin_sender"]->value : get_bloginfo("name");

			// headers
			$mail_headers   = array();
			$mail_headers[] = "Content-type: text/html";
			$mail_headers[] = "From: {$mail_from}";

			// smtp
			if ($use_smtp) {
				// Convert syntax: Name <hello@ezplugins.de>
				$mail_from_array = explode("<", $mail_from);
				if (count($mail_from_array) > 1) {
					$mail_from_address    = str_replace(">", "", $mail_from_array[1]);
					$this->smtp->From     = $mail_from_address;
					$this->smtp->FromName = trim($mail_from_array[0]);
				}

				$this->smtp->addAddress($user_mail);
				$this->smtp->Subject = $mail_subject;
				$this->smtp->Body    = nl2br($mail_output["user"]);

				$res = $this->smtp->send() ? 1 : 0;
			}
			else {
				$res = wp_mail(
					$user_mail,
					__($mail_subject, "ezfc"),
					nl2br($mail_output["user"]),
					$mail_headers
				);
			}

			$this->debug("Email delivery to user: $res");
			$this->debug(var_export($mail_headers, true));
		}
		else {
			$this->debug("No user email found.");
		}
	}

	/**
		output
	**/
	function get_output($id=null, $name=null, $raw=false) {
		if (!$id && !$name) return __("No id or name found. Correct syntax: [ezfc id='1' /] or [ezfc name='form-name' /].");

		if ($id) {
			$form = $this->form_get($id);
			if (!$form) return __("No form found (ID: {$id}).", "ezfc");
		}

		if ($name) {
			$form = $this->form_get(null, $name);
			if (!$form) return __("No form found (Name: {$name}).", "ezfc");
		}

		$elements = $this->elements_get();
		$options  = $this->array_index_key($this->form_get_options($form->id), "name");
		$theme    = $this->get_theme($options["theme"]->value);

		require_once(plugin_dir_path(__FILE__) . "lib/recaptcha-php-1.11/recaptchalib.php");
		$publickey = get_option("ezfc_captcha_public");

		// frontend output
		if (!$raw) {
			$form_elements = $this->form_elements_get($form->id);
		}
		// backend preview
		else {
			$form_elements = $raw;
		}

		// reference id for file uploads
		$ref_id = uniqid();

		// begin output
		$html = "";

		// count all elements beforehand
		$elements_count = count($form_elements);
		// step counter
		$current_step = 0;
		// get amount of steps
		$step_count = 0;
		foreach ($form_elements as $i => $element) {
			if (!$raw) {
				$data    = json_decode($element->data);
			}
			else {
				$element = json_decode(json_encode($element), FALSE);
				$data    = $element;
			}

			if ($elements[$element->e_id]->type == "stepstart") $step_count++;
		}
		
		// additional styles
		$css_label_width = get_option("ezfc_css_form_label_width");
		$css_label_width = empty($css_label_width) ? "" : "style='width: {$css_label_width}'";
		$form_class      = isset($options["form_class"]) ? $options["form_class"]->value : "";

		$html .= $this->remove_nl("<style type='text/css'>{$theme}</style>");

		// custom css
		$custom_css = get_option("ezfc_custom_css");
		if (!empty($custom_css)) {
			$html .= $this->remove_nl("<style type='text/css'>{$custom_css}</style>");
		}

		$html .= "<div class='ezfc-wrapper'>";
		// adding "novalidate" is essential since required fields can be hidden due to conditional logic
		$html .= "<form class='ezfc-form {$form_class}' name='ezfc-form[{$form->id}]' action='' data-id='{$form->id}' data-currency='{$options["currency"]->value}' novalidate>";

		// reference
		$html .= "<input type='hidden' name='id' value='{$form->id}'>";
		$html .= "<input type='hidden' name='ref_id' value='{$ref_id}'>";

		// price
		if ($options["show_price_position"]->value == 2 ||
			$options["show_price_position"]->value == 3) {
			$html .= "<div class='ezfc-element'>";
			$html .= "	<label {$css_label_width}>" . __($options["price_label"]->value, "ezfc") . "</label>";
			$html .= "	<div class='ezfc-price-wrapper'>";
			$html .= "		<span class='ezfc-price'>-</span>";
			$html .= "	</div>";
			$html .= "</div>";
		}

		foreach ($form_elements as $i => $element) {
			$element_css = "ezfc-element ezfc-custom-element";

			if (!$raw) {
				$data    = json_decode($element->data);
				$el_id   = "ezfc_element-{$element->id}";
				$el_name = "ezfc_element[{$element->id}]";
			}
			else {
				$element = json_decode(json_encode($element), FALSE);
				$data    = $element;
				$el_id   = "ezfc_element-{$i}";
				$el_name = "ezfc_element[{$i}]";
			}

			$el_label      = "";
			$el_data_label = "";
			$el_text       = "";
			$required      = "";
			$required_char = "";
			$step          = false;
			if (property_exists($data, "required") && $data->required == 1) {
				$required = "required";

				if ($options["show_required_char"]->value != 0) {
					$required_char = " <span class='ezfc-required-char'>*</span>";
				}
			}

			// element description
			if (property_exists($data, "description") && !empty($data->description)) {
				$el_data_label .= "<span class='ezfc-icon-description' data-ot='{$data->description}'></span> ";
			}

			// trim labels
			if (property_exists($data, "label")) {
				$el_data_label .= trim($data->label);
			}

			// calculate values
			$calc_enabled = 0;
			if (property_exists($data, "calculate_enabled")) {
				$calc_enabled = $data->calculate_enabled ? 1 : 0;
			}

			$calc_before = 0;
			if (property_exists($data, "calculate_before")) {
				$calc_before  = $data->calculate_before ? 1 : 0;
			}

			$data_calculate_output = array(
				"operators" => array(),
				"targets"   => array(),
				"values"    => array()
			);
			$data_calculate = "data-calculate_enabled='{$calc_enabled}' ";

			if (property_exists($data, "calculate") && count($data->calculate) > 0) {
				foreach ($data->calculate as $calculate) {
					$data_calculate_output["operators"][] = property_exists($calculate, "operator") ? $calculate->operator : "";
					$data_calculate_output["targets"][]   = property_exists($calculate, "target") ? $calculate->target : "";
					$data_calculate_output["values"][]    = property_exists($calculate, "value") ? $calculate->value : "";
				}

				$data_calculate .= "
					data-calculate_operator='" . implode(",", $data_calculate_output["operators"]) . "'
					data-calculate_target='" . implode(",", $data_calculate_output["targets"]) . "'
					data-calculate_values='" . implode(",", $data_calculate_output["values"]) . "'
					data-overwrite_price='{$data->overwrite_price}'
					data-calculate_before='{$calc_before}'
				";
			}

			// conditional values
			$data_conditional_output = array(
				"actions"   => array(),
				"operators" => array(),
				"targets"   => array(),
				"values"    => array()
			);

			if (property_exists($data, "conditional") && count($data->conditional) > 0) {
				foreach ($data->conditional as $conditional) {
					$data_conditional_output["actions"][]   = $conditional->action;
					$data_conditional_output["operators"][] = $conditional->operator;
					$data_conditional_output["targets"][]   = $conditional->target;
					$data_conditional_output["values"][]    = $conditional->value;
					$data_conditional_output["notoggle"][]  = property_exists($conditional, "notoggle") ? $conditional->notoggle : "0";
				}

				$data_conditional = "
					data-conditional_action='"   . implode(",", $data_conditional_output["actions"]) . "'
					data-conditional_operator='" . implode(",", $data_conditional_output["operators"]) . "'
					data-conditional_target='"   . implode(",", $data_conditional_output["targets"]) . "'
					data-conditional_values='"   . implode(",", $data_conditional_output["values"]) . "'
					data-conditional_notoggle='" . implode(",", $data_conditional_output["notoggle"]) . "'
				";

				$data_calculate .= $data_conditional;
			}

			// discount
			$data_discount_output = array(
				"range_min" => array(),
				"range_max" => array(),
				"operator"  => array(),
				"values"    => array()
			);
			$data_discount = "";

			if (property_exists($data, "discount") && count($data->discount) > 0) {
				foreach ($data->discount as $discount) {
					$data_discount_output["range_min"][]       = $discount->range_min;
					$data_discount_output["range_max"][]       = $discount->range_max;
					$data_discount_output["operator"][]        = $discount->operator;
					$data_discount_output["discount_values"][] = $discount->discount_value;
				}

				$data_discount .= "
					data-discount_range_min='" . implode(",", $data_discount_output["range_min"]) . "'
					data-discount_range_max='" . implode(",", $data_discount_output["range_max"]) . "'
					data-discount_operator='" . implode(",", $data_discount_output["operator"]) . "'
					data-discount_values='" . implode(",", $data_discount_output["discount_values"]) . "'
				";

				$data_calculate .= $data_discount;
			}

			// remove all line breaks (since WP adds these here)
			$data_calculate = $this->remove_nl($data_calculate);

			// element price
			$show_price = "";

			// hidden?
			if (property_exists($data, "hidden")) {
				if ($data->hidden == 1) $element_css .= " ezfc-hidden";
				elseif ($data->hidden == 2) $element_css .= " ezfc-hidden ezfc-custom-hidden";
			}

			// factor
			$data_factor = "";
			if (property_exists($data, "factor")) $data_factor = "data-factor='{$data->factor}'";

			// external value
			$data_value_external = "";
			if (property_exists($data, "value_external")) $data_value_external = "data-value_external='{$data->value_external}'";

			switch ($elements[$element->e_id]->type) {
				case "input":
				case "email":
					if ($options["show_element_price"]->value == 1 && property_exists($data, "factor")) {
						$show_price = " <span class='ezfc-element-price'>({$options["currency"]->value}{$data->factor})</span>";
					}

					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($el_data_label, "ezfc") . "{$required_char}</label>";
					$el_text .= "<input	class='{$data->class} ezfc-element ezfc-element-input' {$data_factor} name='{$el_name}' placeholder='{$data->placeholder}' type='text' value='{$data->value}' {$required} />{$show_price}";

					// email double-check
					if (property_exists($data, "double_check") && $data->double_check == 1) {
						$el_email_check_name = "ezfc_element[{$element->id}_email_check]";
						$el_text .= "<br><input class='{$data->class} ezfc-element ezfc-element-input' name='{$el_email_check_name}' type='text' value='{$data->value}' placeholder='{$data->placeholder}' {$required} /> <small>" . __("Confirmation", "ezfc") . "</small>";
					}
				break;

				case "numbers":
					if ($options["show_element_price"]->value == 1 && property_exists($data, "factor")) {
						$show_price = " <span class='ezfc-element-price'>({$options["currency"]->value}{$data->factor})</span>";
					}

					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($el_data_label, "ezfc") . "{$required_char}</label>";

					$add_attr = "data-min='{$data->min}' data-max='{$data->max}'";
					// use slider
					if (property_exists($data, "slider") && $data->slider == 1) {
						$steps_slider = 1;
						if (property_exists($data, "steps_slider")) $steps_slider = $data->steps_slider;

						$add_attr    .= " data-stepsslider='{$steps_slider}'";
						$data->class .= " ezfc-slider";
						$el_text     .= "<div class='ezfc-slider-element' data-target='{$el_id}'></div>";
					}
					// use spinner
					if (property_exists($data, "spinner") && $data->spinner == 1) {
						$steps_spinner = 1;
						if (property_exists($data, "steps_spinner")) $steps_spinner = $data->steps_spinner;

						$add_attr    .= " data-stepsspinner='{$steps_spinner}'";
						$data->class .= " ezfc-spinner";
					}

					$el_text .= "<input	class='{$data->class} ezfc-element ezfc-element-numbers' {$data_factor} name='{$el_name}' placeholder='{$data->placeholder}' type='text' value='{$data->value}' {$required} {$add_attr} />{$show_price}";
				break;

				case "hidden":
					$el_text .= "<input class='{$data->class} ezfc-element ezfc-element-input-hidden' {$data_factor} name='{$el_name}' type='hidden' value='{$data->value}' />";
				break;

				case "dropdown":
					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($el_data_label, "ezfc") . "{$required_char}</label>";
					$el_text .= "<select class='{$data->class} ezfc-element ezfc-element-select' name='{$el_name}' {$required}>";

					foreach ($data->options as $n => $option) {
						$add_data = "";
						if (property_exists($data, "preselect")) {
							$add_data = $data->preselect==$n ? "selected='selected'" : "";
						}

						if (property_exists($option, "disabled")) {
							$add_data .= " disabled";
						}

						if ($options["show_element_price"]->value == 1) {
							$show_price = " ({$options["currency"]->value}{$option->value})";
						}

						$el_text .= "<option value='{$n}' data-value='{$option->value}' data-initvalue='{$n}' data-factor='{$option->value}' {$add_data}>{$option->text}{$show_price}</option>";
					}

					$el_text .= "</select>";
				break;

				case "radio":
				case "payment":
					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($el_data_label, "ezfc") . "{$required_char}</label>";
					$el_text .= "<div class='ezfc-element ezfc-element-radio-container'>";

					foreach ($data->options as $n=>$radio) {
						$add_data = "";
						if (property_exists($data, "preselect")) {
							$add_data = $data->preselect==$n ? "checked='checked'" : "";
						}

						if (property_exists($radio, "disabled")) {
							$add_data .= " disabled";
						}

						if ($options["show_element_price"]->value == 1) {
							$show_price = " <span class='ezfc-element-price'>({$options["currency"]->value}{$radio->value})</span>";
						}

						$el_text .= "<div class='ezfc-element-radio'>";
						$el_text .= "	<input class='{$data->class} ezfc-element-radio-input' type='radio' name='{$el_name}' value='{$n}' data-value='{$radio->value}' data-initvalue='{$n}' data-factor='{$radio->value}' {$add_data}>{$radio->text}{$show_price}";
						$el_text .= "</div>";
					}

					$el_text .= "</div>";
				break;

				case "checkbox":
					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($el_data_label, "ezfc") . "{$required_char}</label>";
					$el_text .= "<div class='ezfc-element-checkbox-container'>";
					
					$preselect_values = array();
					if (property_exists($data, "preselect")) {
						$preselect_values = explode(",", $data->preselect);
					}

					foreach ($data->options as $n=>$checkbox) {
						// use different names due to multiple choices
						$el_name = "ezfc_element[{$element->id}][$n]";

						$add_data = "";
						if (in_array((string) $n, $preselect_values)) {
							$add_data = "checked='checked'";
						}

						if (property_exists($checkbox, "disabled")) {
							$add_data .= " disabled";
						}

						if ($options["show_element_price"]->value == 1) {
							$show_price = " <span class='ezfc-element-price'>({$options["currency"]->value}{$checkbox->value})</span>";
						}

						$el_text .= "<div class='ezfc-element-checkbox'>";
						$el_text .= "	<input class='{$data->class} ezfc-element-checkbox-input' type='checkbox' name='{$el_name}' value='{$n}' data-value='{$checkbox->value}' data-initvalue='{$n}' data-factor='{$checkbox->value}' {$add_data}>{$checkbox->text}{$show_price}";
						$el_text .= "</div>";
					}

					$el_text .= "</div>";
				break;

				case "datepicker":
					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($el_data_label, "ezfc") . "{$required_char}</label>";
					$el_text .= "<input class='{$data->class} ezfc-element ezfc-element-input ezfc-element-datepicker' type='text' name='{$el_name}' {$data_value_external} value='{$data->value}' placeholder='{$data->placeholder}' {$required} />";
				break;

				case "daterange":
					$placeholder = explode(";;", $data->placeholder);
					$placeholder_values = array(
						isset($placeholder[0]) ? $placeholder[0] : "",
						isset($placeholder[1]) ? $placeholder[1] : ""
					);

					// mindate
					$minDate = explode(";;", $data->minDate);
					$minDate_values = array(
						isset($minDate[0]) ? $minDate[0] : "",
						isset($minDate[1]) ? $minDate[1] : ""
					);
					// maxdate
					$maxDate = explode(";;", $data->maxDate);
					$maxDate_values = array(
						isset($maxDate[0]) ? $maxDate[0] : "",
						isset($maxDate[1]) ? $maxDate[1] : ""
					);

					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($el_data_label, "ezfc") . "{$required_char}</label>";

					// element needs an additional container class
					$el_text .= "<div class='ezfc-element-daterange-container' data-factor='{$data->factor}'>";

					// from
					$el_text .= "<input class='{$data->class} ezfc-element ezfc-element-input ezfc-element-daterange ezfc-element-daterange-from' type='text' {$data_factor} name='{$el_name}[0]' {$data_value_external} data-mindays='{$data->minDays}' placeholder='{$placeholder_values[0]}' data-mindate='{$minDate_values[0]}' data-maxdate='{$maxDate_values[0]}' {$required} />";
					// to
					$el_text .= "<input class='{$data->class} ezfc-element ezfc-element-input ezfc-element-daterange ezfc-element-daterange-to' type='text' {$data_factor} name='{$el_name}[1]' {$data_value_external} data-mindays='{$data->minDays}' placeholder='{$placeholder_values[1]}' data-mindate='{$minDate_values[1]}' data-maxdate='{$maxDate_values[1]}' {$required} />";

					
					$el_text .= "</div>";
				break;

				case "timepicker":
					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($el_data_label, "ezfc") . "{$required_char}</label>";
					$el_text .= "<input class='{$data->class} ezfc-element ezfc-element-input ezfc-element-timepicker' type='text' name='{$el_name}' {$data_value_external} value='{$data->value}' placeholder='{$data->placeholder}' {$required} />";
				break;

				case "textfield":
					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($el_data_label, "ezfc") . "{$required_char}</label>";
					$el_text .= "<textarea class='{$data->class} ezfc-element ezfc-element-textarea' name='{$el_name}' placeholder='{$data->placeholder}' {$required}>{$data->value}</textarea>";
				break;

				case "recaptcha":
					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($el_data_label, "ezfc") . "{$required_char}</label>";
					$el_text .= recaptcha_get_html($publickey, null);
				break;

				case "html":
					$el_text .= "<div>";
					$el_text .= stripslashes($data->html);
					$el_text .= "</div>";
				break;

				case "hr":
					$el_text .= "<hr class='{$data->class}'>";
				break;

				case "image":
					$el_text .= "<img src='{$data->image}' alt='{$data->alt}' />";
				break;

				case "fileupload":
					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($el_data_label, "ezfc") . "{$required_char}</label>";

					$multiple = $data->multiple==1 ? "multiple" : "";
					// file upload input
					$el_text .= "<input type='file' name='ezfc-files' class='{$data->class} ezfc-element-fileupload' placeholder='{$data->placeholder}' {$multiple} />";

					// upload button
					$el_text .= '<button class="btn ezfc-upload-button">' . __("Upload", "ezfc") . '</button>';

					$el_text .= "<p class='ezfc-fileupload-message'></p>";

					// progressbar
					$el_text .= '<div class="ezfc-progress ezfc-progress-striped active">
  									<div class="ezfc-bar ezfc-progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0">
    									<span class="sr-only">0% Complete</span>
							  		</div>
								</div>';
				break;

				case "subtotal":
					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($el_data_label, "ezfc") . "{$required_char}</label>";
					
					$el_type = "text";
					if (property_exists($data, "text_only") && $data->text_only == 1) {
						$el_type  = "hidden";
						$el_value = isset($value) ? $value : "";

						$el_text .= "<span class='ezfc-text'>{$el_value}</span>";
					}

					$el_text .= "<input class='{$data->class} ezfc-element ezfc-element-input ezfc-element-subtotal' type='{$el_type}' name='{$el_name}' value='' />";
				break;

				case "stepstart":
					$step_class = "ezfc-step ezfc-step-start {$data->class}";
					if ($current_step == 0) $step_class .= " ezfc-step-active";

					$el_text = "<div class='{$step_class}' data-step='{$current_step}'>";

					$step = true;
				break;

				case "stepend":
					$el_text = "";

					if (property_exists($data, "add_line") && $data->add_line == 1) {
						$el_text .= "<hr class='ezfc-step-line' />";
					}

					// previous button
					if ($current_step > 0) {
						$el_text .= "	<button class='ezfc-step-button ezfc-step-previous'>{$data->previous_step}</button>";
					}
					// next button
					if ($current_step < $step_count - 1) {
						$el_text .= "	<button class='ezfc-step-button ezfc-step-next'>{$data->next_step}</button>";
					}
					
					$el_text .= "</div>";

					$step = true;
					$current_step++;
				break;

				default: $el_text = $element->id; break;
			}

			if (!empty($data->label)) {
				$el_text = $el_label . $el_text;
			}

			if (property_exists($data, "columns")) $element_css .= " ezfc-column ezfc-col-{$data->columns}";

			if (!$step) {
				$element_css .= " ezfc-element-wrapper-{$elements[$element->e_id]->type}";
				
				$html .= "<div class='{$element_css}' id='{$el_id}' data-element='{$elements[$element->e_id]->type}' {$data_calculate} {$data_value_external}>{$el_text}</div>";
			}
			else {
				$html .= $el_text;
			}
		}

		// price
		if ($options["show_price_position"]->value == 1 ||
			$options["show_price_position"]->value == 3) {
			$html .= "<div class='ezfc-element'>";
			$html .= "	<label {$css_label_width}>" . __($options["price_label"]->value, "ezfc") . "</label>";
			$html .= "	<div class='ezfc-price-wrapper'>";
			$html .= "		<span class='ezfc-price'>-</span>";
			$html .= "	</div>";
			$html .= "</div>";
		}
		// fixed price position
		if ($options["show_price_position"]->value == 4 ||
			$options["show_price_position"]->value == 5) {
			$fixed_price_position = $options["show_price_position"]->value==4 ? "left" : "right";

			$html .= "<div class='ezfc-fixed-price ezfc-fixed-price-{$fixed_price_position}' data-id='{$form->id}'>";
			$html .= "	<label {$css_label_width}>" . __($options["price_label"]->value, "ezfc") . "</label>";
			$html .= "	<div class='ezfc-price-wrapper'>";
			$html .= "		<span class='ezfc-price'>-</span>";
			$html .= "	</div>";
			$html .= "</div>";
		}

		// submit
		if ($options["submission_enabled"]->value == 1) {
			// submission / woocommerce
			if (get_option("ezfc_woocommerce", 0) == 1) $submit_text = __($options["submit_text_woo"]->value, "ezfc");
			else if ($options["pp_enabled"]->value == 1) $submit_text = __($options["pp_submittext"]->value, "ezfc");
			else $submit_text = __($options["submit_text"]->value, "ezfc");

			$html .= "<div class='ezfc-element ezfc-submit-wrapper'>";
			$html .= "	<label {$css_label_width}></label>";
			$html .= "	<input class='btn ezfc-element ezfc-element-submit ezfc-submit {$options["submit_button_class"]->value}' id='ezfc-submit-{$form->id}' type='submit' value='{$submit_text}' />";
			$html .= "	<div class='ezfc-submit-icon'></div>";
			$html .= "</div>";
		}

		$html .= "</form>";

		// error messages
		$html .= "<div class='ezfc-message'></div>";

		// required char
		$required_text = get_option("ezfc_required_text");
		if ($options["show_required_char"]->value != 0 && !empty($required_text)) {
			$html .= "<div class='ezfc-required-notification'><span class='ezfc-required-char'>*</span> " . __($required_text, "ezfc") . "</div>";
		}

		// success message
		$success_text = get_option("ezfc_woocommerce")==1 ? __(get_option("ezfc_woocommerce_text"), "ezfc") : __($options["success_text"]->value, "ezfc");
		$html .= "<div class='ezfc-success-text' data-id='{$form->id}'>" . $success_text . "</div>";

		// wrapper
		$html .= "</div>";

		// overview
		$html .= "<div class='ezfc-overview' data-id='{$form->id}'></div>";

		// js output
		$form_options_js = json_encode(array(
			"currency_position" => $options["currency_position"]->value,
			"datepicker_format" => $options["datepicker_format"]->value,
			"hide_all_forms"    => !empty($options["hide_all_forms"]) ? $options["hide_all_forms"]->value : 0,
			"price_format"      => !empty($options["price_format"])   ? $options["price_format"]->value   : get_option("ezfc_price_format"),
			"redirect_url"      => trim($options["redirect_url"]->value),
			"timepicker_format" => $options["timepicker_format"]->value
		));

		$html .= "<script>ezfc_form_vars[{$form->id}] = {$form_options_js};</script>";

		return $html;
	}

	function insert_file($f_id, $ref_id, $file) {
		// insert into db
		$res = $this->wpdb->insert(
			$this->tables["files"],
			array(
				"f_id"   => $f_id,
				"ref_id" => $ref_id,
				"url"    => $file["url"],
				"file"   => $file["file"]
			),
			array(
				"%d",
				"%s",
				"%s",
				"%s"
			)
		);

		if (!$res) return $this->send_message("error", __("File entry failed.", "ezfc"));

		return $this->send_message("success", __("File entry saved.", "ezfc"));
	}

	function get_theme($id) {
		if (!$id) return $this->send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT data FROM {$this->tables["themes"]} WHERE id=%d",
			$id
		));

		return $res->data;
	}

	function update_submission_paypal($token, $transaction_id) {
		if (!$token) return $this->send_message("error", __("No token.", "ezfc"));

		$submission = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT id, f_id, data FROM {$this->tables["submissions"]} WHERE token=%s",
			$token
		));

		// no submission with $token found
		if (!$submission || count($submission) < 1) return $this->send_message("error", __("Could not find submission.", "ezfc"));

		$res = $this->wpdb->update(
			$this->tables["submissions"],
			array("transaction_id" => $transaction_id),
			array("id" => $submission->id),
			array("%s"),
			array("%d")
		);

		// reset some session data
		$_SESSION["Payment_Amount"] = null;

		return array("submission" => $submission);
	}

	function remove_nl($content) {
		return trim(preg_replace('/\s\s+/', ' ', $content));
	}

	function array_index_key($array, $key) {
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

	function send_message($type, $msg, $id=0) {
		return array(
			$type => $msg,
			"id"  => $id
		);
	}

	function smtp_setup() {
		require_once(plugin_dir_path(__FILE__) . "lib/PHPMailer/PHPMailerAutoload.php");

		$this->smtp = new PHPMailer();
		$this->smtp->isSMTP();
		$this->smtp->SMTPAuth   = true;
		$this->smtp->Host       = get_option("ezfc_email_smtp_host");
		$this->smtp->Username   = get_option("ezfc_email_smtp_user");
		$this->smtp->Password   = get_option("ezfc_email_smtp_pass");
		$this->smtp->Port       = get_option("ezfc_email_smtp_port");
		$this->smtp->SMTPSecure = get_option("ezfc_email_smtp_secure");
		$this->smtp->isHTML(true);
	}
}