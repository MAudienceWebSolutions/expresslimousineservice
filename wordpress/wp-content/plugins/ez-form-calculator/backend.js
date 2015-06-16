jQuery(document).ready(function($) {
	/**
		ui
	**/
	ezfc_z_index = 10;
	form_changed = false;

	/**
		operator lists
	**/
	var ezfc_operators = [
		{ value: "0", text: " " },
		{ value: "add", text: "+" },
		{ value: "subtract", text: "-" },
		{ value: "multiply", text: "*" },
		{ value: "divide", text: "/" },
		{ value: "equals", text: "=" },
		{ value: "power", text: "^" }
	];

	var ezfc_operators_discount = [
		{ value: "0", text: " " },
		{ value: "add", text: "+" },
		{ value: "subtract", text: "-" },
		{ value: "percent_add", text: "%+" },
		{ value: "percent_sub", text: "%-" },
		{ value: "equals", text: "=" }
	];

	var ezfc_cond_operators = [
		{ value: "0", text: " " },
		{ value: "gr", text: ">" },
		{ value: "gre", text: ">=" },
		{ value: "less", text: "<" },
		{ value: "lesse", text: "<=" },
		{ value: "equals", text: "=" },
		{ value: "between", text: "between" }
	];

	var ezfc_cond_actions = [
		{ value: "0", text: " " },
		{ value: "show", text: "Show" },
		{ value: "hide", text: "Hide" }
	];

	// tabs
	$("#tabs").tabs();

	// dialogs
	var dialog_default_attr = {
		autoOpen: false,
		height: Math.min(800, $(window).height() - 200),
		width: Math.min(1200, $(window).width() - 200),
		modal: true
	};

	var dialog_options_buttons = {
		buttons: {
			"Update options": function() {
				$(".ezfc-option-save").click();
			},
			"Cancel": function() {
				$(this).dialog("close");
			}
		}
	};

	var dialog_import_buttons = {
		buttons: {
			"Import data": function() {
				$("[data-action='form_import_data']").click();
			},
			"Cancel": function() {
				$(this).dialog("close");
			}
		}
	};

	var dialog_export_buttons = {
		buttons: {
			"Close": function() {
				$(this).dialog("close");
			}
		}
	}

	$(".ezfc-options-dialog").dialog($.extend(dialog_default_attr, dialog_options_buttons));
	$(".ezfc-import-dialog").dialog($.extend(dialog_default_attr, dialog_import_buttons));
	$(".ezfc-export-dialog").dialog($.extend(dialog_default_attr, dialog_export_buttons));

	// ajax actions
	$("body").on("click", "[data-action]", function() {
		if ($(this).data("action") == "form_get" && form_changed) {
			if (!confirm(ezfc_vars.form_changed)) {
				$(".spinner").hide();
				return false;
			}
		}

		do_action($(this));

		var selectgroup = $(this).data("selectgroup");
		if (selectgroup) {
			$(".button-primary[data-selectgroup='" + selectgroup + "']").removeClass("button-primary");
			$(this).addClass("button-primary");
		}

		return false;
	});

	// toggle form element data
	$("body").on("click", ".ezfc-form-element-name", function() {
		$(this).parent().find(".ezfc-form-element-data").toggle().css("z-index", ++ezfc_z_index);
	});
	// toggle submission data
	$("body").on("click", ".ezfc-form-submission-name", function() {
		$(this).parent().find(".ezfc-form-submission-data").toggle();
	});

	// image upload
	$("body").on("click", ".ezfc-image-upload", function(event) {
		event.preventDefault();

		var file_frame;
		var _this = this;

	    file_frame = wp.media.frames.file_frame = wp.media({
	      title: jQuery( this ).data( 'uploader_title' ),
	      button: {
	        text: jQuery( this ).data( 'uploader_button_text' ),
	      },
	      multiple: false
	    });
	 
	    file_frame.on( 'select', function() {
	    	var attachment = file_frame.state().get('selection').first().toJSON();
	    	$(_this).parents(".ezfc-form-element-data").find("[data-element-name='image']").val(attachment.url);
	    	$(_this).parents(".ezfc-form-element-data").find(".ezfc-image-preview").attr("src", attachment.url);
	    });
	 
	    file_frame.open();
    });

	// hide/show element data
	$(".ezfc-toggle-show").on("click", function() {
		$(".ezfc-form-element-data, .ezfc-form-submission-data").show();
	});
	$(".ezfc-toggle-hide").on("click", function() {
		$(".ezfc-form-element-data, .ezfc-form-submission-data").hide();
	});
	// toggle expand view
	$(".ezfc-toggle-expand").on("click", function() {
		$(".ezfc-form-elements-container").toggleClass("full-width");
	});

	// add option
	$("body").on("click", ".ezfc-form-element-option-add", function() {
		form_changed = true;

		var target_element_id = $(this).data("element_id");
		var target            = $($(this).data("target") + "[data-element_id='" + target_element_id + "']:last");
		var target_class_row  = $(target).data("row");
		var target_row_new    = parseInt(target_class_row) + 1;

		$(target).clone()
			.attr("data-row", target_row_new) // .data("row", 1) does not work here :/
			.find(".ezfc-form-element-option-value").attr("name", "elements[" + target_element_id + "][options][" + target_row_new + "][value]").end()
			.find(".ezfc-form-element-option-text").attr("name", "elements[" + target_element_id + "][options][" + target_row_new + "][text]").end()
			.insertAfter(target);

		return false;
	});
	// delete option
	$("body").on("click", ".ezfc-form-element-option-delete", function() {
		var target            = $(this).parents($(this).data("target"));
		var target_element_id = $(this).data("element_id");

		var target_length = $($(this).data("target") + "[data-element_id='" + target_element_id + "']").length;
		if (target_length <= 1) return false;

		$(target).remove();

		return false;
	});

	// add calculation field
	$("body").on("click", ".ezfc-form-element-calculate-add", function() {
		form_changed = true;

		var target_element_id = $(this).data("element_id");
		var target            = $($(this).data("target") + "[data-element_id='" + target_element_id + "']:last");
		var target_class_row  = $(target).data("row");
		var target_row_new    = parseInt(target_class_row) + 1;

		$(target).clone()
			.attr("data-row", target_row_new) // .data("row", 1) does not work here :/
			.find(".ezfc-form-element-calculate-operator").attr("name", "elements[" + target_element_id + "][calculate][" + target_row_new + "][operator]").end()
			.find(".ezfc-form-element-calculate-target").attr("name", "elements[" + target_element_id + "][calculate][" + target_row_new + "][target]").end()
			.find(".ezfc-form-element-calculate-value").attr("name", "elements[" + target_element_id + "][calculate][" + target_row_new + "][value]").end()
			.insertAfter(target);

		custom_trigger_change();

		return false;
	});
	// delete calculation field
	$("body").on("click", ".ezfc-form-element-calculate-delete", function() {
		var target            = $(this).parents($(this).data("target"));
		var target_element_id = $(this).data("element_id");

		var target_length = $($(this).data("target") + "[data-element_id='" + target_element_id + "']").length;
		if (target_length <= 1) {
			clear_option_row(target);
			return false;
		}

		$(target).remove();

		return false;
	});

	// restrict calculation target / value field
	$("body").on("change custom-trigger-change", ".ezfc-form-element-calculate-target", function() {
		var $value_element = $(this).parents(".ezfc-form-element-calculate-wrapper").find(".ezfc-form-element-calculate-value");
		if ($(this).val() == 0) {
			$value_element.removeAttr("disabled");
		}
		else {
			$value_element.attr("disabled", "disabled");
		}
	});

	// add conditional field
	$("body").on("click", ".ezfc-form-element-conditional-add", function() {
		form_changed = true;

		var target_element_id = $(this).data("element_id");
		var target            = $($(this).data("target") + "[data-element_id='" + target_element_id + "']:last");
		var target_class_row  = $(target).data("row");
		var target_row_new    = parseInt(target_class_row) + 1;

		$(target).clone()
			.attr("data-row", target_row_new) // .data("row", 1) does not work here :/
			.find(".ezfc-form-element-conditional-action").attr("name", "elements[" + target_element_id + "][conditional][" + target_row_new + "][action]").end()
			.find(".ezfc-form-element-conditional-target").attr("name", "elements[" + target_element_id + "][conditional][" + target_row_new + "][target]").end()
			.find(".ezfc-form-element-conditional-operator").attr("name", "elements[" + target_element_id + "][conditional][" + target_row_new + "][operator]").end()
			.find(".ezfc-form-element-conditional-value").attr("name", "elements[" + target_element_id + "][conditional][" + target_row_new + "][value]").end()
			.insertAfter(target);

		return false;
	});
	// delete conditional field
	$("body").on("click", ".ezfc-form-element-conditional-delete", function() {
		var target            = $(this).parents($(this).data("target"));
		var target_element_id = $(this).data("element_id");

		var target_length = $($(this).data("target") + "[data-element_id='" + target_element_id + "']").length;
		if (target_length <= 1) {
			clear_option_row(target);
			return false;
		}

		$(target).remove();

		return false;
	});

	// add discount field
	$("body").on("click", ".ezfc-form-element-discount-add", function() {
		form_changed = true;

		var target_element_id = $(this).data("element_id");
		var target            = $($(this).data("target") + "[data-element_id='" + target_element_id + "']:last");
		var target_class_row  = $(target).data("row");
		var target_row_new    = parseInt(target_class_row) + 1;

		$(target).clone()
			.attr("data-row", target_row_new)
			.find(".ezfc-form-element-discount-range_min").attr("name", "elements[" + target_element_id + "][discount][" + target_row_new + "][range_min]").end()
			.find(".ezfc-form-element-discount-range_max").attr("name", "elements[" + target_element_id + "][discount][" + target_row_new + "][range_max]").end()
			.find(".ezfc-form-element-discount-operator").attr("name", "elements[" + target_element_id + "][discount][" + target_row_new + "][operator]").end()
			.find(".ezfc-form-element-discount-discount_value").attr("name", "elements[" + target_element_id + "][discount][" + target_row_new + "][discount_value]").end()
			.insertAfter(target);

		return false;
	});
	// delete discount field
	$("body").on("click", ".ezfc-form-element-discount-delete", function() {
		var target            = $(this).parents($(this).data("target"));
		var target_element_id = $(this).data("element_id");

		var target_length = $($(this).data("target") + "[data-element_id='" + target_element_id + "']").length;
		if (target_length <= 1) {
			clear_option_row(target);
			return false;
		}

		$(target).remove();

		return false;
	});

	// refresh calculation fields
	$("body").on("click", ".ezfc-form-calculate-refresh", function() {
		fill_calculate_fields();

		return false;
	});

	// label name keyboard input
	$("body").on("keyup", ".ezfc-form-element-data input[data-element-name='label']", function() {
		var text = $(this).val();
		$(this).parents(".ezfc-form-element").find(".element-label").text(text);
	});

	// add changed class upon change
	$("body").on("keyup change", ".ezfc-form-element-data input, .ezfc-form-element-data select", function() {
		ezfc_form_has_changed(this);
	});
	// add changed class when options were added / removed
	$("body").on("click", ".ezfc-form-element-data button", function() {
		ezfc_form_has_changed(this);
	});

	// required toggle char
	$("body").on("click", ".ezfc-form-element-required-toggle", function() {
		form_changed = true;

		var req_char = $(this).val()==1 ? "*" : "";
		$(this).parents(".ezfc-form-element").find(".ezfc-form-element-required-char").text(req_char);
	});

	// preview suppress submit
	$("body").on("click", "form .ezfc-element-submit", function() {
		return false;
	});

	// column change
	$("body").on("click", ".ezfc-form-element-column-left", function() {
		change_columns($(this), -1);
		return false;
	});
	$("body").on("click", ".ezfc-form-element-column-right", function() {
		change_columns($(this), 1);
		return false;
	});

	/**
		ui functions
	**/
	// init
	function init_ui() {
		/* next version!
		// elements
		$(".ezfc-form-elements").nestedSortable({
			handle: ".ezfc-form-element-name",
			listType: "ul"
		});
		*/

		$(".ezfc-form-elements").sortable({
			distance: 5,
			forcePlaceholderSize: true,
			handle: ".ezfc-form-element-name",
			placeholder: "ui-state-highlight",
			revert: true
		});

		// tooltips
		$(".ezfc-elements-show [data-ot]").each(function(i, el) {
			$(el).opentip($(el).data("ot"), {
				fixed: true,
				tipJoint: "bottom"
			});
		});

		// spinner
		$(".ezfc-spinner").spinner();

		custom_trigger_change();
	}

	// restrict calculation target / value field
	function custom_trigger_change() {
		$(".ezfc-form-element-calculate-target").trigger("custom-trigger-change");
	}

	/**
		forms
	**/
	// add form
	function form_add(data) {
		var html = "";
		html += "<li class='button ezfc-form' data-id='" + data.form.id + "' data-action='form_get' data-selectgroup='forms'>";
		html += "	<i class='fa fa-fw fa-list-alt'></i> ";
		html += 	data.form.id + " - ";
		html += "	<span class='ezfc-form-name'>" + data.form.name + "</span>";
		html += "</li>";

		$(".ezfc-forms-list").append(html);

		$(".ezfc-form.button-primary").removeClass("button-primary");
		$(".ezfc-form[data-id='" + data.form.id + "']").addClass("button-primary");

		form_show(data);
	}

	function form_clear(id) {
		$(".ezfc-form-element").remove();
	}

	function form_delete(id) {
		$(".ezfc-form-elements-actions, .ezfc-form-elements-container, .ezfc-form-options-wrapper").addClass("ezfc-hidden");
		$(".ezfc-form[data-id='" + id + "']").remove();
	}

	function form_file_delete(id) {
		$(".ezfc-form-file[data-id='" + id + "']").remove();
	}

	function form_preview(data) {
		$(".ezfc-form-preview").html(data);

		if (data.length < 1) return false;

		$("html, body").animate({
			scrollTop: $(".ezfc-form-preview").offset().top-50
		}, 1337);
	}

	// show single form
	function form_show(data) {
		form_changed = false;
		var out = "";

		if (data) {
			$.each(data.elements, function(i, element) {
				out += element_add(element);
			});

			$(".ezfc-form-elements").html(out);

			$("#ezfc-form-save, #ezfc-form-delete, #ezfc-form-clear").data("id", data.form.id);
			$("#ezfc-shortcode").text("[ezfc id='" + data.form.id + "' /]");
			$("#ezfc-form-name").val(ezfc_stripslashes(data.form.name).replace(/&apos;/g, "'"));

			// calculate fields
			fill_calculate_fields();

			// populate form option fields
			form_show_options(data.options);

			// set submission entries
			$("#ezfc-form-submissions-count").text(data.submissions_count);
		}

		$(".ezfc-form-submissions").addClass("ezfc-hidden");
		$(".ezfc-form-elements-actions, .ezfc-form-elements-container, .ezfc-form-options-wrapper").removeClass("ezfc-hidden");

		// scroll to form
		$(".ezfc-forms-list").animate({ scrollTop: $(".ezfc-forms-list")[0].scrollHeight }, "slow");

		// tooltips
		init_ui();
		change_columns();
	}

	/**
		show form options
	**/
	function form_show_options(options) {
		$.each(options, function(i, v) {
			var target = "#opt-" + v.o_id;

			switch (v.type) {
    			case "yesno":
    			case "lang":
    				$(target + " option").removeAttr("selected");
    				$(target + " option[value='" + v.value + "']").attr("selected", "selected");
    			break;

    			case "editor":
    				// visual editor
    				$("#editor_" + v.o_id + "_ifr").contents().find("body").html(nl2br(v.value));
    				// textarea
    				$("#editor_" + v.o_id).val(v.value);
    			break;

    			default:
    				$(target).val(v.value);
    			break;
    		}
		});
	}

	// show form submissions
	function form_show_submissions(submissions) {
		form_changed = false;
		var out = "<ul>";

		$.each(submissions.submissions, function(i, submission) {
			var date    = new Date(Date.parse(submission.date));
			var addIcon = "";

			if (submission.payment_id == 1) {
				addIcon += " <i class='fa fa-fw fa-paypal' data-ot='PayPal used'></i>";

				if (submission.transaction_id.length>0) addIcon += " <i class='fa fa-fw fa-check' data-ot='Payment verified.'></i>";
				else addIcon += " <i class='fa fa-fw fa-times' data-ot='Payment denied or cancelled'></i>";
			}

			out += "<li class='ezfc-form-submission' data-id='" + submission.id + "'>";
			out += "	<div class='ezfc-form-submission-name'>";
			out += "		<i class='fa fa-fw fa-envelope'></i>" + addIcon + " ID: " + submission.id + " - " + date.toUTCString();
			out += "		<button class='ezfc-form-submission-delete button' data-action='form_submission_delete' data-id='" + submission.id + "'><i class='fa fa-times'></i></button>";
			out += "	</div>";

			// additional data (toggle)
			out += "	<div class='ezfc-form-submission-data hidden'>";

			// paypal info
			if (submission.payment_id == 1) {
				out += "<div>";
				out += "	<p><strong>Paid with Paypal</strong></p>";
				out += "	<p>Transaction-ID: " + submission.transaction_id;
				out += "</div>";
			}

			out += submission.content;

			// files
			if (submissions.files[submission.ref_id]) {
				out += "	<div class='ezfc-form-files'>";
				out += "		<p>Files</p>";

				$.each(submissions.files[submission.ref_id], function(fi, file) {
					var filename = file.url.split("/").slice(-1);

					out += "	<ul>";
					out += "		<li class='ezfc-form-file' data-id='" + file.id + "'>";
					out += "			<a href='" + file.url + "'>" + filename + "</a>";
					out += "			<button class='ezfc-form-file-delete button' data-action='form_file_delete' data-id='" + file.id + "'><i class='fa fa-times'></i></button>";
					out += "	</li>";
					out += "	</ul>";
				});

				out += "	</div>";
			}

			out += "</li>";
		});

		out += "</ul>";

		$(".ezfc-form-submissions").removeClass("ezfc-hidden").html(out);
		$(".ezfc-form-elements-container, .ezfc-form-options-wrapper").addClass("ezfc-hidden");

		// meh
		$(".ezfc-form-submission-data h2").remove();
	}

	// add element
	function element_add(element) {
		var data_el = $.parseJSON(element.data);

		var columns             = 6;
		var element_name_header = "";
		var req_char            = "";

		var html = "";
		html += "<input type='hidden' class='ezfc-form-element-e_id' value='" + element.e_id + "' name='elements[" + element.id + "][e_id]' />";

		var element_prefix = "";
		var element_suffix = "";
		if (ezfc.elements[element.e_id].type == "group") {
			element_prefix  = "<li class='ezfc-form-element ezfc-form-element-group'>";
			element_prefix += "    <div class='ezfc-form-element-name'>Name</div>";
			element_prefix += "    <ul class='ezfc-group'>";
			element_prefix += "        " + html;

			element_suffix += "    </ul>";
			element_suffix += "    <div class='clearfix'></div>";
			element_suffix += "</li>";

			return element_prefix + element_suffix;
		}

		$.each(data_el, function(name, value) {
			// skip id
			if (name == "e_id" || name == "preselect") return;

			var input_id   = "elements-" + name + "-" + element.id;
			var input_raw  = "elements[" + element.id + "]";
			var input_name = "elements[" + element.id + "][" + name + "]";

			var input = "";
			input += "<input type='text' value='" + value + "' name='" + input_name + "' data-element-name='" + name + "' id='" + input_id + "' />";

			var el_description = "";

			switch (name) {
				case "columns":
					columns = value;
					html += "<input name='" + input_name + "' id='" + input_id + "' data-element-name='" + name + "' type='hidden' value='" + value + "' />";
					// skip because we don't want this field to be displayed
					return;
				break;

				// there are better ways, i know, but due to limited time and lack of beer, i will do it this (crappy) way (for now).
				case "name":
					el_description = "Internal name. This value is displayed in submissions/emails only.";
				break;

				case "label":
					el_description = "This text will be displayed in the frontend.";
					element_name_header = value;
				break;

				case "description":
					el_description = "Users will see the description in a tooltip.";
				break;

				case "html":
					input = "<textarea name='" + input_name + "' id='" + input_id + "'>" + ezfc_stripslashes(value) + "</textarea>";
				break;

				case "required":
					el_description = "Whether this is a required field or not.";
					req_char = value==1 ? "*" : "";

					input = "<select class='ezfc-form-element-required-toggle' name='" + input_name + "' id='" + input_id + "' data-element-name='" + name + "'>";
					input += "	<option value='0'>" + ezfc_vars.yes_no.no + "</option>";
					input += "	<option value='1'" + (value==1 ? "selected" : "") + ">" + ezfc_vars.yes_no.yes + "</option>";
					input += "</select>";
				break;

				case "add_line":
				case "calculate_enabled":
				case "calculate_before":
				case "double_check":
				case "is_currency":
				case "overwrite_price":
				case "slider":
				case "spinner":
				case "use_address":
				case "text_only":
					switch (name) {
						case "add_line":          el_description = "Add a line above step buttons.";
						case "calculate_enabled": el_description = "When checked, this field will be taken into calculations."; break;
						case "calculate_before":  el_description = "When checked, this field will be calculated first. <br><br><strong>Checked</strong>: this_field / target_calculation_field. <br><br><strong>Unchecked</strong>: target_calculation_field / this_field."; break;
						case "double_check":      el_description = "Double check email-address"; break;
						case "is_currency":       el_description = "Format this field as currency value in submissions."; break;
						case "overwrite_price":   el_description = "When checked, this field will override the calculations above. Useful with division operator. <br><br><strong>Checked</strong>: result = target_calculation_field / this_field. <br><br><strong>Unchecked</strong>: result = current_value + target_calculation_field / this_field."; break;
						case "hidden":            el_description = "Pretty self-explanatory, I think. ;)"; break;
						case "slider":            el_description = "Display a slider instead of a textfield. Needs minimum and maximum fields defined."; break;
						case "spinner":           el_description = "Display a spinner instead of a textfield."; break;
						case "use_address":       el_description = "Emails will be sent to this address."; break;
						case "text_only":         el_description = "Display text only instead of an input field"; break;
					}

					input = "<select class='ezfc-form-element-" + name + "' name='" + input_name + "' id='" + input_id + "' data-element-name='" + name + "'>";
					input += "	<option value='0'>" + ezfc_vars.yes_no.no + "</option>";
					input += "	<option value='1'" + (value==1 ? "selected" : "") + ">" + ezfc_vars.yes_no.yes + "</option>";
					input += "</select>";
				break;

				case "hidden":
					el_description = "Hidden field. If this field is taken into conditional calculations, you need to set this option to Conditional hidden.";

					input = "<select class='ezfc-form-element-" + name + "' name='" + input_name + "' id='" + input_id + "' data-element-name='" + name + "'>";
					input += "	<option value='0'>" + ezfc_vars.yes_no.no + "</option>";
					input += "	<option value='1'" + (value==1 ? "selected" : "") + ">Yes</option>";
					input += "	<option value='2'" + (value==2 ? "selected" : "") + ">Conditional hidden</option>";
					input += "</select>";
				break;

				case "steps_slider":
				case "steps_spinner":
					el_description = "Incremental steps";

					input = "<input class='ezfc-spinner' type='text' value='" + value + "' name='" + input_name + "' data-element-name='" + name + "' id='" + input_id + "' />";
				break;

				case "factor":
					el_description = "Field value will be automatically multiplied by this factor. Default factor: 1";
				break;

				case "value":
					el_description = "Predefined value.";
				break;
				case "value_external":
					el_description = "DOM-selector to get the value from (e.g. #myinputfield).";
				break;

				case "min":
					el_description = "Minimum value.";
				break;
				case "max":
					el_description = "Maximum value.";
				break;

				case "placeholder":
					el_description = "Placeholder only (slight background text when no value is present).";
				break;

				case "multiple":
					el_description = "When checked, multiple files can be uploaded.";

					input = "<select class='ezfc-form-element-multiple' name='" + input_name + "' id='" + input_id + "' data-element-name='" + name + "'>";
					input += "	<option value='0'>" + ezfc_vars.yes_no.no + "</option>";
					input += "	<option value='1'" + (value==1 ? "selected" : "") + ">" + ezfc_vars.yes_no.yes + "</option>";
					input += "</select>";
				break;

				// used for radio-buttons, checkboxes
				case "options":
					input = "<button class='button ezfc-form-element-option-add' data-target='.ezfc-form-element-option' data-element_id='" + element.id + "'>Add option</button></div>";
					
					input += "<div class='col-lg-4 col-md-4 col-sm-4 col-xs-4'>Value</div>";
					input += "<div class='col-lg-6 col-md-6 col-sm-6 col-xs-6'>Text</div>";
					input += "<div class='col-lg-2 col-md-2 col-sm-2 col-xs-2'>";
					input += "	<abbr title='Preselect values'>Sel</abbr>&nbsp;";
					input += "	<abbr title='Disabled'>Dis</abbr>&nbsp;";
					input += "	<abbr title='Remove item'>Rem</abbr>";
					input += "</div>";

					var n              = 0,
						preselect      = data_el.preselect ? data_el.preselect : "",
						preselect_html = "",
						preselect_type = "",
						preselect_val  = [];

					var disabled_val = data_el.disabled ? data_el.disabled : "";
						disabled_arr = $.map(disabled_val.split(","), function(v) {
							return parseInt(v);
						});

					switch (ezfc.elements[element.e_id].type) {
						case "checkbox":
							// use dummy name for checkboxes since these values will be concatenated on saving
							preselect_name = "preselect-dummy";
							preselect_type = "checkbox";
							preselect_val  = $.map(preselect.split(","), function(v) {
								return parseInt(v);
							});
						break;

						default:
							preselect_name = input_raw + "[preselect]";
							preselect_type = "radio";
						break;
					}

					$.each(value, function(opt_val, opt_text) {
						input += "<div class='ezfc-form-element-option' data-element_id='" + element.id + "' data-row='" + n + "'>";
						// text
						input += "	<div class='col-lg-4 col-md-4 col-sm-4 col-xs-4'><input class='ezfc-form-element-option-value small' name='" + input_name + "[" + n + "][value]' value='" + opt_text.value + "' type='text' /></div>";
						// value
						input += "	<div class='col-lg-6 col-md-6 col-sm-6 col-xs-6'><input class='ezfc-form-element-option-text' name='" + input_name + "[" + n + "][text]' type='text' value='" + opt_text.text + "' /></div>";

						// preselect
						if (preselect_val.length > 0) {
							preselect_html = $.inArray(n, preselect_val)!=-1 ? "checked='checked'" : "";
						}
						// radio buttons
						else {
							preselect_html = data_el.preselect == n ? "checked='checked'" : "";
						}
						// preselect
						if (disabled_arr.length > 0) {
							disabled_html = $.inArray(n, disabled_arr)!=-1 ? "checked='checked'" : "";
						}
						// radio buttons
						else {
							disabled_html = data_el.disabled == n ? "checked='checked'" : "";
						}

						input += "	<div class='col-lg-2 col-md-2 col-sm-2 col-xs-2'>";
						// preselect
						input += "		<input class='ezfc-form-element-option-" + preselect_type + "' name='" + input_raw + "[preselect]' type='" + preselect_type + "' data-element_id='" + element.id + "' value='" + n + "' " + preselect_html + " />&nbsp;";
						// disabled
						input += "		<input class='ezfc-form-element-option-disabled' name='" + input_name + "[" + n + "][disabled]' type='checkbox' data-element_id='" + element.id + "' value='1' " + disabled_html + " />&nbsp;";
						// remove
						input += "		<button class='button ezfc-form-element-option-delete' data-target='.ezfc-form-element-option' data-element_id='" + element.id + "' data-target_row='" + n + "'><i class='fa fa-times'></i></button>";
						input += "	</div>";
						input += "</div>";
						
						n++;
					});

					if (preselect_type == "checkbox") {
						input += "<input class='ezfc-form-option-preselect' type='hidden' name='" + input_raw + "[preselect]' data-element_id='" + element.id + "' value='" + preselect + "' />";
					}

					else if (preselect_type == "radio") {
						preselect_html = preselect==-1 ? "checked='checked'" : "";

						input += "<div class='col-lg-10 col-md-10 col-sm-10 col-xs-10'>No preselected value</div>";
						input += "<div class='col-lg-2 col-md-2 col-sm-2 col-xs-2'><input class='ezfc-form-element-option-radio' name='" + input_raw + "[preselect]' type='radio' data-element_id='" + element.id + "' value='-1' " + preselect_html + " /></div>";
					}

					input += "<div>";
				break;

				// calculate
				case "calculate":
					el_description = "Choose the operator and target element to calculate with. <br><br>Example: [ * ] [ field_1 ]<br>Result = current_value + field_1 * this_field.";

					input = "<button class='button ezfc-form-element-calculate-add' data-target='.ezfc-form-element-calculate-wrapper' data-element_id='" + element.id + "'>Add calculation field</button>&nbsp;";
					input += "<button class='ezfc-form-calculate-refresh button' data-ot='Refresh fields'><span class='fa fa-refresh'></span></button>";
					input += "</div>";

					input += "<div class='col-lg-1 col-md-1 col-sm-1 col-xs-1'><span class='fa fa-question-circle' data-ot='Operator'></span></div>";
					input += "<div class='col-lg-5 col-md-5 col-sm-5 col-xs-5'>Field</div>";
					input += "<div class='col-lg-5 col-md-5 col-sm-5 col-xs-5'><span class='fa fa-question-circle' data-ot='Only relevant when no field is selected.'></span> Value</div>";
					input += "<div class='col-lg-1 col-md-1 col-sm-1 col-xs-1'>Remove</div>";
					input += "<div class='clearfix'></div>";

					// calculation fields
					var n = 0;
					$.each(value, function(calc_key, calc_text) {
						input += "<div class='ezfc-form-element-calculate-wrapper' data-element_id='" + element.id + "' data-row='" + n + "'>";
						// operator
						input += "	<div class='col-lg-1 col-md-1 col-sm-1 col-xs-1'>";
						input += "		<select class='ezfc-form-element-calculate-operator' name='" + input_name + "[" + n + "][operator]' data-element-name='" + name + "'>";

						// iterate through operators
						$.each(ezfc_operators, function(nn, operator) {
							var selected = "";
							if (calc_text.operator == operator.value) selected = "selected='selected'";

							input += "<option value='" + operator.value + "' " + selected + ">" + operator.text + "</option>";
						});

						input += "		</select>";
						input += "	</div>";

						// other elements (will be filled in from function fill_calculate_fields())
						input += "	<div class='col-lg-5 col-md-5 col-sm-5 col-xs-5'>";
						input += "		<select class='ezfc-form-element-calculate-target' name='" + input_name + "[" + n + "][target]' id='" + input_id + "-target' data-element-name='" + name + "' data-calculate_operator='" + calc_text.operator + "' data-calculate_target='" + calc_text.target + "'>";
						input += "		</select>";
						input += "	</div>";

						// value when no element was selected
						if (!calc_text.value) calc_text.value = "";
						input += "	<div class='col-lg-5 col-md-5 col-sm-5 col-xs-5'>";
						input += "		<input class='ezfc-form-element-calculate-value' name='" + input_name + "[" + n + "][value]' id='" + input_id + "-value' data-element-name='" + name + "' value='" + calc_text.value + "' type='text' />";
						input += "	</div>";

						// remove
						input += "	<div class='col-lg-1 col-md-1 col-sm-1 col-xs-1'>";
						input += "		<button class='button ezfc-form-element-calculate-delete' data-target='.ezfc-form-element-calculate-wrapper' data-element_id='" + element.id + "'><i class='fa fa-times'></i></button>";
						input += "	</div>";

						input += "	<div class='clearfix'></div>";
						input += "</div>";
						n++;
					});

					input += "<div>";
				break;

				// conditional fields
				case "conditional":
					el_description = "Conditional fields can show or hide elements. Check out the conditional example from the templates or visit the documentation site for more information.";

					input = "<button class='button ezfc-form-element-conditional-add' data-target='.ezfc-form-element-conditional-wrapper' data-element_id='" + element.id + "'>Add conditional field</button>&nbsp;";
					input += "<button class='ezfc-form-calculate-refresh button' data-ot='Refresh fields'><span class='fa fa-refresh'></span></button>";
					input += "</div>";

					input += "<div class='col-lg-2 col-md-2 col-sm-2 col-xs-2'>" + get_tip("Action") + " Action</div>";
					input += "<div class='col-lg-4 col-md-4 col-sm-4 col-xs-4'>" + get_tip("Target element to show/hide") + " Target element</div>";
					input += "<div class='col-lg-2 col-md-2 col-sm-2 col-xs-2'>" + get_tip("Conditional operator: when the value of this element equals/is less than/is greater/in between than the conditional value. When using \"in between\", use a colon (:) as separator.") + " CO</div>";
					input += "<div class='col-lg-2 col-md-2 col-sm-2 col-xs-2'>" + get_tip("Conditional value") + " Value</div>";
					input += "<div class='col-lg-1 col-md-1 col-sm-1 col-xs-1'>" + get_tip("Conditional toggle: when this field is checked, the opposite action will not be executed when this condition is triggered") + " CT</div>";
					input += "<div class='col-lg-1 col-md-1 col-sm-1 col-xs-1'>&nbsp;</div>";
					input += "<div class='clearfix'></div>";

					var n = 0;
					$.each(value, function(calc_key, calc_text) {
						input += "<div class='ezfc-form-element-conditional-wrapper' data-element_id='" + element.id + "' data-row='" + n + "'>";

						// show or hide
						input += "	<div class='col-lg-2 col-md-2 col-sm-2 col-xs-2'>";
						input += "		<select class='ezfc-form-element-conditional-action' name='" + input_name + "[" + n + "][action]' id='" + input_id + "-action' data-element-name='" + name + "'>";

						// iterate through conditional operators
						$.each(ezfc_cond_actions, function(nn, operator) {
							var selected = "";
							if (calc_text.action == operator.value) selected = "selected='selected'";

							input += "<option value='" + operator.value + "' " + selected + ">" + operator.text + "</option>";
						});

						input += "		</select>";
						input += "	</div>";

						// field to show
						input += "	<div class='col-lg-4 col-md-4 col-sm-4 col-xs-4'>";
						input += "		<select class='ezfc-form-element-conditional-target' name='" + input_name + "[" + n + "][target]' data-element-name='" + name + "' data-calculate_target='" + calc_text.target + "' data-show_all='true'>";
						input += "		</select>";
						input += "	</div>";

						// conditional operator
						input += "	<div class='col-lg-2 col-md-2 col-sm-2 col-xs-2'>";
						input += "		<select class='ezfc-form-element-conditional-operator' name='" + input_name + "[" + n + "][operator]' id='" + input_id + "-target' data-element-name='" + name + "'>";

						// iterate through conditional operators
						$.each(ezfc_cond_operators, function(nn, operator) {
							var selected = "";
							if (calc_text.operator == operator.value) selected = "selected='selected'";

							input += "<option value='" + operator.value + "' " + selected + ">" + operator.text + "</option>";
						});

						input += "		</select>";
						input += "	</div>";

						// conditional value
						if (!calc_text.value) calc_text.value = "";
						input += "	<div class='col-lg-2 col-md-2 col-sm-2 col-xs-2'>";
						input += "		<input class='ezfc-form-element-conditional-value' name='" + input_name + "[" + n + "][value]' id='" + input_id + "-value' data-element-name='" + name + "' value='" + calc_text.value + "' type='text' />";
						input += "	</div>";

						// conditional toggle
						var cond_toggle = (calc_text.notoggle && calc_text.notoggle=="1") ? "checked='checked'" : "";
						input += "	<div class='col-lg-1 col-md-1 col-sm-1 col-xs-1'>";
						input += "		<input class='ezfc-form-element-conditional-notoggle' name='" + input_name + "[" + n + "][notoggle]' id='" + input_id + "-notoggle' data-element-name='" + name + "' value='1' type='checkbox' " + cond_toggle + " />";
						input += "	</div>";

						// remove
						input += "	<div class='col-lg-1 col-md-1 col-sm-1 col-xs-1'>";
						input += "		<button class='button ezfc-form-element-conditional-delete' data-target='.ezfc-form-element-conditional-wrapper' data-element_id='" + element.id + "'><i class='fa fa-times'></i></button>";
						input += "	</div>";

						input += "	<div class='clearfix'></div>";
						input += "</div>";

						n++;
					});

					input += "<div>";
				break;

				// calculate
				case "discount":
					el_description = "Discount values";

					input = "<button class='button ezfc-form-element-discount-add' data-target='.ezfc-form-element-discount-wrapper' data-element_id='" + element.id + "'>Add discount field</button>&nbsp;";
					input += "</div>";

					input += "<div class='col-lg-3 col-md-3 col-sm-3 col-xs-3'>Range min</div>";
					input += "<div class='col-lg-3 col-md-3 col-sm-3 col-xs-3'>Range max</div>";
					input += "<div class='col-lg-2 col-md-2 col-sm-2 col-xs-2'>Op</div>";
					input += "<div class='col-lg-3 col-md-3 col-sm-3 col-xs-3'>Discount value</div>";
					input += "<div class='col-lg-1 col-md-1 col-sm-1 col-xs-1'>Remove</div>";
					input += "<div class='clearfix'></div>";

					// discount fields
					var n = 0;
					$.each(value, function(discount_key, discount_text) {
						input += "<div class='ezfc-form-element-discount-wrapper' data-element_id='" + element.id + "' data-row='" + n + "'>";

						// range_min
						input += "	<div class='col-lg-3 col-md-3 col-sm-3 col-xs-3'>";
						input += "		<input class='ezfc-form-element-discount-range_min' name='" + input_name + "[" + n + "][range_min]' id='" + input_id + "-value' data-element-name='" + name + "' value='" + discount_text.range_min + "' type='text' />";
						input += "	</div>";

						// range_max
						input += "	<div class='col-lg-3 col-md-3 col-sm-3 col-xs-3'>";
						input += "		<input class='ezfc-form-element-discount-range_max' name='" + input_name + "[" + n + "][range_max]' id='" + input_id + "-value' data-element-name='" + name + "' value='" + discount_text.range_max + "' type='text' />";
						input += "	</div>";

						// operator
						input += "	<div class='col-lg-2 col-md-2 col-sm-2 col-xs-2'>";
						input += "		<select class='ezfc-form-element-discount-operator' name='" + input_name + "[" + n + "][operator]' data-element-name='" + name + "'>";

						// iterate through operators
						$.each(ezfc_operators_discount, function(nn, operator) {
							var selected = "";
							if (discount_text.operator == operator.value) selected = "selected='selected'";

							input += "<option value='" + operator.value + "' " + selected + ">" + operator.text + "</option>";
						});

						input += "		</select>";
						input += "	</div>";

						// other elements (will be filled in from function fill_calculate_fields())
						input += "	<div class='col-lg-3 col-md-3 col-sm-3 col-xs-3'>";
						input += "		<input class='ezfc-form-element-discount-discount_value' name='" + input_name + "[" + n + "][discount_value]' id='" + input_id + "-value' data-element-name='" + name + "' value='" + discount_text.discount_value + "' type='text' />";
						input += "	</div>";

						// remove
						input += "	<div class='col-lg-1 col-md-1 col-sm-1 col-xs-1'>";
						input += "		<button class='button ezfc-form-element-discount-delete' data-target='.ezfc-form-element-discount-wrapper' data-element_id='" + element.id + "'><i class='fa fa-times'></i></button>";
						input += "	</div>";

						input += "	<div class='clearfix'></div>";
						input += "</div>";
						n++;
					});

					input += "<div>";
				break;

				// image
				case "image":
					input += "<button class='button ezfc-image-upload'>Choose image</button>";
					input += "<br><img src='" + value + "' class='ezfc-image-preview' />";
				break;

				case "class":
					el_description = "Additional CSS class for this element.";
				break;

				case "GET":
					el_description = "This field will be filled from a GET-parameter. Example: <br><br><strong>URL</strong>: http://www.test.com/?test_value=1 <br><strong>GET</strong>: test_value <br><strong>Field value</strong>: 1.";
				break;
				
				case "slidersteps":
					el_description = "Slider step value";

					input = "<input class='ezfc-spinner' name='" + input_name + "' value='" + value + "' />";
				break;

				case "minDate":
					el_description = "Minimum date of both dates. Example: +1d;;+2d - the first datepicker (from) will only have selectable dates 1 day in the future, the second datepicker (to) will only have selectable dates 2 days in the future";
				break;
				case "maxDate":
					el_description = "The opposite of minDate.";
				break;
				case "minDays":
					el_description = "The amount of minimum days to select.";
				break;

				case "custom_regex":
					el_description = "Custom regular expression. Only numbers allowed example: /[0-9]/i";
				break;
				case "custom_error_message":
					el_description = "Error message when element value does not validate regular expression from custom_regex";
				break;
			}

			html += "<div class='row'>";
			html += "	<div class='col-lg-4 col-md-4 col-sm-4 col-xs-4'>";

			if (el_description.length > 0) {
				html += "		<span class='fa fa-question-circle' data-ot='" + el_description + "'></span> &nbsp;"
			}

			html += "		<label for='" + input_name + "'>" + name.capitalize() + "</label>";
			html += "	</div>";
			html += "	<div class='col-lg-8 col-md-8 col-sm-8 col-xs-8'>";
			html += input;
			html += "	</div>";
			html += "</div>";

			html += "<div class='clearfix'></div>";
		});

		var element_label = element_name_header.length > 0 ? " - <span class='element-label'>" + element_name_header + "</span>" : "";
		var out = element_prefix;

		out += "<li class='ezfc-form-element ezfc-form-element-" + ezfc.elements[element.e_id].type + " ezfc-col-" + columns + "' data-columns='" + columns + "' data-id='" + element.id + "'>";
		out += "	<div class='ezfc-form-element-name'>";
		// column buttons
		out += "		<button class='ezfc-form-element-column ezfc-form-element-column-left button'><i class='fa fa-toggle-left'></i></button>";
		out += "		<button class='ezfc-form-element-column ezfc-form-element-column-right button'><i class='fa fa-toggle-right'></i></button>";

		out += "		<span class='fa fa-fw " + ezfc.elements[element.e_id].icon + "'></span>";
		out += "		<span class='ezfc-form-element-required-char'>" + req_char + "</span> ";
		out += "		<span class='ezfc-form-element-type'>" + ezfc.elements[element.e_id].name + "</span> " + element_label;
		// duplicate element button
		out += "		<button class='ezfc-form-element-duplicate button' data-action='form_element_duplicate' data-id='" + element.id + "'><i class='fa fa-files-o' data-ot='Duplicate element'></i></button>";
		// delete element button
		out += "		<button class='ezfc-form-element-delete button' data-action='form_element_delete' data-id='" + element.id + "'><i class='fa fa-times'></i></button>";
		out += "		</div>";
		out += "	<div class='container-fluid ezfc-form-element-data ezfc-form-element-" + ezfc.elements[element.e_id].name.replace(" ", "-").toLowerCase() + " hidden'>" + html + "</div>";
		out += "</li>";

		out += element_suffix;

		return out;
	}

	function fill_calculate_fields(show_all) {
		var calculate_out;
		var elements = $(".ezfc-form-element-data");
		var elements_filtered = elements.not(".ezfc-form-element-email, .ezfc-form-element-date, .ezfc-form-element-image, .ezfc-form-element-line, .ezfc-form-element-date, .ezfc-form-element-html, .ezfc-form-element-recaptcha, .ezfc-form-element-file-upload");

		var elements_html = "<option value='0'> </option>";
		$(elements).each(function(ie, element) {
			var el_parent = $(element).parents(".ezfc-form-element");
			var el_id     = $(el_parent).data("id");
			var name      = $(el_parent).find("input[data-element-name='name']").val();
			var type      = $(el_parent).find(".ezfc-form-element-type").text();

			elements_html += "<option value='" + el_id + "'>" + name + " (" + type + ")</option>";
		});

		var elements_filtered_html = "<option value='0'> </option>";
		$(elements_filtered).each(function(ie, element) {
			var el_parent = $(element).parents(".ezfc-form-element");
			var el_id     = $(el_parent).data("id");
			var name      = $(el_parent).find("input[data-element-name='name']").val();
			var type      = $(el_parent).find(".ezfc-form-element-type").text();

			elements_filtered_html += "<option value='" + el_id + "'>" + name + " (" + type + ")</option>";
		});

		$(".ezfc-form-element-calculate-target, .ezfc-form-element-conditional-target").each(function(i, calculate_element) {
			var calculate_out = "<option value='0'> </option>";
			var operator      = $(this).data("calculate_operator");
			var selected      = $(this).find(":selected").val() || $(this).data("calculate_target");
			var show_all      = $(this).data("show_all") ? true : false;

			// skip elements which cannot be calculated with
			var elements_to_insert = show_all ? elements_html : elements_filtered_html;

			$(calculate_element).html(elements_to_insert);

			var calc_el_parent = $(calculate_element).parent();
			calc_el_parent.parent().find(".ezfc-form-element-calculate-operator option[value='" + operator + "']").prop("selected", "selected");
			calc_el_parent.parent().find(".ezfc-form-element-calculate-target option[value='" + selected + "']").prop("selected", "selected");

			if (show_all) {
				calc_el_parent.find(".ezfc-form-element-conditional-action option[value='" + selected + "']").prop("selected", "selected");
				calc_el_parent.find(".ezfc-form-element-conditional-target option[value='" + selected + "']").prop("selected", "selected");
			}
		});
	}

	/**
		ajax
	**/
	function do_action(el) {
		$(".spinner").fadeIn("fast");

		var action = $(el).data("action");
		var f_id   = $(".ezfc-forms-list .button-primary").data("id");
		var id     = $(el).data("id");
		var data   = "action="+action;

		switch (action) {
			case "form_add":
			case "form_template_delete":
				id = $("#ezfc-form-template-id option:selected").val();
			break;

			case "form_duplicate":
			case "form_preview":
			case "form_get_submissions":
				id = $(".ezfc-forms-list .button-primary").data("id");
			break;

			case "form_element_add":
				if (!f_id) return false;
				var e_id = id;
				
				data += "&e_id=" + e_id + "&f_id=" + f_id;
			break;

			case "form_clear":
			case "form_element_delete":
			case "form_delete":
			case "form_submission_delete":
			case "form_template_delete":
			case "form_file_delete":
				if (action == "form_template_delete" && id == 0) {
					$(".spinner").hide();
					return false;
				} 

				if (!confirm(ezfc_vars.delete_element)) {
					$(".spinner").hide();
					return false;
				}
			break;

			case "form_show":
				$(".spinner").hide();
				form_show(null);
				return false;
			break;

			// import dialog
			case "form_show_import":
				$(".spinner").hide();
				$(".ezfc-import-dialog").dialog("open");
				$("#form-import-data").val("");
				return false;
			break;

			// import form data
			case "form_import_data":
				id = f_id;
				data += "&import_data=" + encodeURIComponent($("#form-import-data").val().replace(/'/g, "&apos;"));
			break;

			// export
			case "form_show_export":
				id = f_id;
			break;

			case "form_save":
			case "form_preview":
				tinyMCE.triggerSave();

				// concatenate preselect checkboxes
				$(".ezfc-form-element-checkbox").each(function() {
					var preselect = [];
					$(this).find(".ezfc-form-element-option-checkbox").each(function(i, checkbox) {
						if ($(checkbox).is(":checked")) {
							preselect.push($(checkbox).val());
						}
					});

					$(this).find(".ezfc-form-option-preselect").val(preselect.join(","));
				});

				var data_elements = encodeURIComponent(JSON.stringify($("#form-elements").serializeArray()).replace(/'/g, "&apos;"));
				var data_options  = encodeURIComponent(JSON.stringify($("#form-options").serializeArray()).replace(/'/g, "&apos;"));

				var form_name = encodeURIComponent($("#ezfc-form-name").val());
				data += "&elements=" + data_elements + "&options=" + data_options + "&ezfc-form-name=" + form_name;
			break;

			case "form_save_template":
				id = f_id;
			break;

			case "form_show_options":
				$(".spinner").hide();
				$(".ezfc-options-dialog").dialog("open");
				return false;
			break;

			case "form_update_options":
				tinyMCE.triggerSave();
				
				var save_data = $("#form-options").serialize();
				data += "&" + save_data;
				id    = f_id;
			break;

			case "form_element_duplicate":
				// check if element was changed before duplicating
				if ($(el).parents(".ezfc-form-element-name").hasClass("ezfc-changed")) {
					ezfc_message("Please update the form before duplicating elements.");
					$(".spinner").hide();

					return false;
				}
			break;
		}

		// auto append id
		if (id) {
			data += "&id=" + id;
		}

		// nonce
		data += "&nonce=" + ezfc_nonce;

		$.ajax({
			type: "post",
			url: ajaxurl,
			data: {
				action: "ezfc_backend",
				data: data
			},
			success: function(response) {
				$(".spinner").fadeOut("fast");

				response_json = $.parseJSON(response);
				if (!response_json) {
					$(".ezfc-error").text("Only available in the premium version.");
						
					return false;
				}

				if (response_json.error) {
					$(".ezfc-error").text(response_json.error);

					return false;
				}
				$(".ezfc-error").text("");

				if (response_json.message) {
					ezfc_message(response_json.message);
				} 

				/**
					call functions after ajax request
				**/
				switch (action) {
					case "element_get":
						element_show(response_json.element[0]);
					break;

					case "form_add":
					case "form_duplicate":
						form_add(response_json);
					break;

					case "form_get":
						form_show(response_json);
					break;

					case "form_get_submissions":
						form_show_submissions(response_json);
					break;

					case "form_clear":
						form_clear();
					break;

					case "form_delete":
						form_delete(id);
					break;

					case "form_file_delete":
						form_file_delete(id);
					break;

					case "form_save":
						form_changed = false;
						$(".ezfc-changed").removeClass("ezfc-changed");

						// update name in forms list
						$(".ezfc-form[data-id='" + id + "'] .ezfc-form-name").text($("#ezfc-form-name").val());
					break;

					case "form_save_template":
						var template_name = $("#ezfc-form-name").val();
						$("#ezfc-form-template-id").append("<option value='" + response_json + "'>" + template_name + "</option>");
					break;

					case "form_template_delete":
						$("#ezfc-form-template-id option[value='" + id + "']").remove();
					break;

					case "form_element_delete":
						$(el).parents(".ezfc-form-element").remove();
						fill_calculate_fields();
					break;

					case "form_element_add":
					case "form_element_duplicate":
						$(".ezfc-form-elements").append(element_add(response_json));
						fill_calculate_fields();
						
						init_ui();
					break;

					case "form_submission_delete":
						$(el).parents(".ezfc-form-submission").remove();
					break;

					case "form_update_options":
						$(".ezfc-forms-list li[data-id='" + id + "'] .ezfc-form-name").text($("#opt-name").val());
						$(".ezfc-dialog").dialog("close");
					break;

					case "form_import_data":
						form_add(response_json);
						form_show(response_json);
						$(".ezfc-dialog").dialog("close");
					break;

					case "form_show_export":
						$("#form-export-data").val(JSON.stringify(response_json));
						$(".ezfc-export-dialog").dialog("open");
					break;
				}
			}
		});

		return false;
	}

	function ezfc_message(message) {
		$(".ezfc-message").text(message).slideDown();

		setTimeout(function() {
			$(".ezfc-message").slideUp();
		}, 7500);
	}

	function ezfc_form_has_changed(trigger_el) {
		form_changed = true;

		$(trigger_el).parents(".ezfc-form-element").find(".ezfc-form-element-name").addClass("ezfc-changed");
	}

	function ezfc_stripslashes(str) {
		return (str + '')
		.replace(/\\(.?)/g, function (s, n1) {
		  switch (n1) {
		  case '\\':
		    return '\\';
		  case '0':
		    return '\u0000';
		  case '':
		    return '';
		  default:
		    return n1;
		  }
		});
	}

	// change form element columns
	function change_columns(el, inc) {
		var element_wrapper = $(el).parents(".ezfc-form-element");
		var columns = element_wrapper.data("columns");
		var columns_new = Math.min(6, Math.max(1, columns + inc));

		element_wrapper
			.removeClass("ezfc-col-" + columns)
			.addClass("ezfc-col-" + columns_new)
			.data("columns", columns_new)
			.find("[data-element-name='columns']")
				.val(columns_new);
	}

	function nl2br (str, is_xhtml) {
	    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
	    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
	}

	String.prototype.capitalize = function() {
	    return this.charAt(0).toUpperCase() + this.slice(1);
	}

	function get_tip(text) {
		return "<span class='fa fa-question-circle' data-ot='" + text + "'></span>";
	}

	function clear_option_row(row) {
		$(row).find("input").val("");
		$(row).find("select").val("0");
	}
});