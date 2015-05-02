<?php

defined( 'ABSPATH' ) OR exit;

global $wpdb;

require_once(plugin_dir_path(__FILE__) . "class.ezfc_backend.php");
$ezfc = new Ezfc_backend();

$elements     = $ezfc->elements_get();
$forms        = $ezfc->forms_get();
$settings     = $ezfc->get_settings("section ASC, id ASC");
$templates    = $ezfc->form_templates_get();
$themes       = $ezfc->get_themes();

// elements -> js var
$elements_js = array();
foreach ($elements as $e) {
	$elements_js[$e->id] = $e;
}

wp_localize_script("ezfc-backend", "ezfc", array(
	"elements" => $elements_js
));

// categorize elements for improved overview
$elements_cat = array();
foreach ($elements as $e) {
	$elements_cat[$e->category][] = $e;
}

function list_elements($elements) {
	foreach ($elements as $e) {
		echo "<li class='button ezfc-element' data-action='form_element_add' data-id='{$e->id}' data-ot='{$e->description}'><i class='fa fa-fw {$e->icon}'></i> {$e->name}</li>
		";
	}
}

// load mailchimp api wrapper
require_once(plugin_dir_path(__FILE__) . "lib/mailchimp/MailChimp.php");
$mailchimp_api_key = get_option("ezfc_mailchimp_api_key", -1);
$mailchimp_lists   = array();
if (!empty($mailchimp_api_key) && $mailchimp_api_key != -1) {
	$mailchimp = new Drewm_MailChimp($mailchimp_api_key);
	$mailchimp_lists = $mailchimp->call("lists/list");
}

// categorize settings
$settings_cat = array();
foreach ($settings as $s) {
	$settings_cat[$s->cat][] = $s;
}

$nonce = wp_create_nonce("ezfc-nonce");

?>

<div class="ezfc wrap ezfc-wrapper">
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<?php echo "<h2>" . __("Overview", "ezfc") . " <span class='spinner'></span></h2>"; ?>

				<div class="ezfc-error"></div>
				<div class="ezfc-message"></div>
			</div>
		</div>

		<div class="row">
			<div class="col-lg-2 col-md-2 col-sm-4 col-xs-12">
				<h3><?php echo __("Add form", "ezfc"); ?></h3>

				Template:
				<ul class="ezfc-template-list">
					<li>
						<select id="ezfc-form-template-id" name="ezfc-form-template-id">
							<option value="0"><?php echo __("Blank", "ezfc"); ?></option>

							<?php
							$out = "";
							foreach ($templates as $t) {
								$out .= "<option value='{$t->id}'>{$t->name}</option>";
							}
							echo $out;
							?>
						</select>
					</li>
					<li class="button" data-action="form_add" data-ot="<?php echo __("Add by template", "ezfc"); ?>"><i class='fa fa-fw fa-plus-square-o'></i></li>
					<li class="button" data-action="form_template_delete" data-ot="<?php echo __("Delete template", "ezfc"); ?>"><i class='fa fa-fw fa-times'></i></li>
					<li class="button" data-action="form_show_import" id="ezfc-form-import" data-ot="<?php echo __("Import form", "ezfc"); ?>"><i class='fa fa-fw fa-upload'></i></li>
				</ul>
			</div>

			<div class="ezfc-hidden col-lg-10 col-md-10 col-sm-8 col-xs-12 ezfc-inline-list ezfc-form-elements-actions">
				<h3><?php echo __("Actions", "ezfc"); ?></h3>

				<ul>
					<li id="ezfc-form-save" class="button button-primary" data-action="form_save"><i class='fa fa-fw fa-floppy-o'></i> <?php echo __("Update form", "ezfc"); ?></li>
					<li id="ezfc-form-show-options" class="button" data-action="form_show_options"><i class='fa fa-fw fa-cogs'></i> <?php echo __("Options", "ezfc"); ?></li>
					<li class="ezfc-separator"></li>

					<li id="ezfc-form-show" class="button" data-action="form_show"><i class='fa fa-fw fa-list-alt' data-ot="<?php echo __("Show form", "ezfc"); ?>"></i></li>
					<li id="ezfc-form-show-submissions" class="button" data-action="form_get_submissions" data-ot="<?php echo __("Show submissions", "ezfc"); ?>"><i class='fa fa-fw fa-envelope'></i> (<span id="ezfc-form-submissions-count">0</span>)</li>
					<li class="ezfc-separator"></li>

					<li id="ezfc-form-duplicate" class="button" data-action="form_duplicate" data-ot="<?php echo __("Duplicate form", "ezfc"); ?>"><i class='fa fa-fw fa-files-o'></i></li>
					<li id="ezfc-form-save-template" class="button" data-action="form_save_template" data-ot="<?php echo __("Save current form as template", "ezfc"); ?>"><i class='fa fa-fw fa-star'></i></li>
					<li id="ezfc-form-import" class="button" data-action="form_show_export" data-ot="<?php echo __("Export form", "ezfc"); ?>"><i class='fa fa-fw fa-download'></i></li>
					<li class="ezfc-separator"></li>

					<li id="ezfc-form-clear" class="button" data-action="form_clear" data-ot="<?php echo __("Clear form (delete all elements)", "ezfc"); ?>"><i class='fa fa-fw fa-eraser'></i></li>
					<li id="ezfc-form-delete" class="button" data-action="form_delete" data-ot="<?php echo __("Delete form", "ezfc"); ?>"><i class='fa fa-fw fa-times'></i></li>
				</ul>
			</div>

			<div class="clear"></div>

			<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 ezfc-forms">
				<h3><?php echo __("Forms", "ezfc"); ?></h3>

				<ul class="ezfc-forms-list">
					<li class="button clone" data-action="form_get" data-selectgroup="forms"></li>

					<?php
					foreach ($forms as $f) {
						echo "
							<li class='button ezfc-form' data-id='{$f->id}' data-action='form_get' data-selectgroup='forms'>
								<i class='fa fa-fw fa-list-alt'></i> {$f->id} - <span class='ezfc-form-name'>{$f->name}</span>
							</li>
						";
					}
					?>
				</ul>
			</div>

			<div class="ezfc-hidden col-lg-6 col-md-6 col-sm-12 col-xs-12 ezfc-form-elements-container">
				<div class="ezfc-elements-show">
					<h3><?php echo __("Form elements", "ezfc"); ?></h3>

					<form id="form-elements" name="ezfc-form-elements" action="">
						<ul class="ezfc-form-elements">
						</ul>
					</form>
				</div>
			</div>

			<div class="ezfc-hidden col-lg-4 col-md-4 col-sm-12 col-xs-12 ezfc-form-options-wrapper">
				<h3><label for="ezfc-form-name"><?php echo __("Name", "ezfc"); ?></label></h3>
				<input type="text" id="ezfc-form-name" name="ezfc-form-name" value="" />

				<div class="ezfc-elements-add">
					<h3><?php echo __("Add elements"); ?></h3>

					<h4><?php echo __("Basic", "ezfc"); ?></h4>
					<ul class="ezfc-elements">
						<?php list_elements($elements_cat["basic"]) ?>
					</ul>

					<h4><?php echo __("Calculation", "ezfc"); ?></h4>
					<ul class="ezfc-elements">
						<?php list_elements($elements_cat["calc"]) ?>
					</ul>
				</div>

				<h3><?php echo __("Shortcode", "ezfc"); ?></h3>
				<p id="ezfc-shortcode">-</p>
			</div>

			<!-- submissions -->
			<div class="ezfc-hidden col-lg-8 col-md-8 col-sm-8 col-xs-8 ezfc-form-submissions">
			</div>

			<div class="clear"></div>

			<!-- todo: preview -->
			<div class="ezfc-hidden col-lg-12 col-md-12 col-sm-12 col-xs-12 ezfc-form-preview-container">
				<h3><?php echo __("Preview", "ezfc"); ?></h3>
				<div class="ezfc-form-preview"></div>
			</div>
		</div>

		<!-- options modal dialog -->
		<div class="ezfc-options-dialog ezfc-dialog" title="Form options">
			<form id="form-options" name="ezfc-form-options" action="">
				<div id="ezfc-form-options">
					<div id="tabs">
						<ul>
							<?php
							$tabs = array_keys($settings_cat);

							foreach ($tabs as $i => $cat) {
								echo "<li><a href='#tab-{$i}'>{$cat}</a></li>";
							}
							?>
						</ul>

					    <?php

					    $tab_i = 0;
					    foreach ($settings_cat as $cat_name => $cat) {
					    	?>

							<div id="tab-<?php echo $tab_i; ?>">
								<table class="form-table">
									<tr>

										<?php
										$out = array();

						    			foreach ($cat as $i => $s) {
									    	$add_class = empty($s->type) ? "" : "ezfc-settings-type-{$s->type}";
									    	$tmp_input = "<input type='text' class='regular-text {$add_class}' id='opt-{$s->id}' name='opt[{$s->id}]' value='{$s->value}' />";

									    	if (!empty($s->type)) {
									    		$type_array = explode(",", $s->type);

									    		switch ($type_array[0]) {
									    			case "yesno":
									    				$selected_no = $selected_yes = "";

									    				if ($s->value == 0) $selected_no = " selected='selected'";
									    				else                $selected_yes = " selected='selected'";

									    				$tmp_input  = "<select class='{$add_class}' id='opt-{$s->id}' name='opt[{$s->id}]'>";
									    				$tmp_input .= "    <option value='0' {$selected_no}>" . __("No", "ezfc") . "</option>";
									    				$tmp_input .= "    <option value='1' {$selected_yes}>" . __("Yes", "ezfc") . "</option>";
									    				$tmp_input .= "</select>";
									    			break;

									    			case "select":
									    				$options = explode("|", $type_array[1]);

									    				$tmp_input  = "<select class='{$add_class}' id='opt-{$s->id}' name='opt[{$s->id}]'>";
									    				foreach ($options as $v => $desc) {
									    					$selected = "";
									    					if ($s->value == $v) $selected = "selected='selected'";

									    					$tmp_input .= "<option value='{$v}' {$selected}>" . $desc . "</option>";
									    				}

									    				$tmp_input .= "</select>";
									    			break;

									    			case "textarea":
									    				$tmp_input  = "<textarea class='{$add_class}' id='opt-{$s->id}' name='opt[{$s->id}]'>";
									    				$tmp_input .= $s->value;
									    				$tmp_input .= "</textarea>";
									    			break;

									    			case "editor":
									    				ob_start();

									    				wp_editor($s->value, "editor_{$s->id}", array(
									    					"teeny"         => true,
									    					"textarea_name" => "opt[{$s->id}]",
									    					"textarea_rows" => 5
									    				));
									    				$tmp_input = ob_get_contents();

									    				ob_end_clean();
									    			break;

									    			case "numbers":
									    				$type_numbers = explode("-", $type_array[1]);

									    				$tmp_input = "<select class='{$add_class}' id='opt-{$s->id}' name='opt[{$s->id}]'>";
									    				for ($ti = $type_numbers[0]; $ti <= $type_numbers[1]; $ti++) {
									    					$selected = $s->value==$ti ? "selected='selected'" : "";

									    					$tmp_input .= "<option value='{$ti}' {$selected}>{$ti}</option>";
									    				}
									    				$tmp_input .= "</select>";
									    			break;

									    			case "themes":
									    				$tmp_input = "<select class='{$add_class}' id='opt-{$s->id}' name='opt[{$s->id}]'>";
									    				foreach ($themes as $theme) {
									    					$selected = $s->value==$theme->id ? "selected='selected'" : "";

									    					$tmp_input .= "<option value='{$theme->id}' {$selected}>{$theme->description}</option>";
									    				}
									    				$tmp_input .= "</select>";
									    			break;

									    			case "mailchimp_list":
									    				$tmp_input = "<select class='{$add_class}' id='opt-{$s->id}' name='opt[{$s->id}]'>";

									    				if (isset($mailchimp_lists["total"]) && $mailchimp_lists["total"] > 0) {
										    				foreach ($mailchimp_lists["data"] as $list) {
										    					$selected = $s->value==$list["id"] ? "selected='selected'" : "";

										    					$tmp_input .= "<option value='{$list["id"]}' {$selected}>{$list["name"]}</option>";
										    				}
										    			}
										    			// no lists
										    			else {
										    				$tmp_input .= "<option value='-1'>No MailChimp lists found or wrong API key</option>";
										    			}

									    				$tmp_input .= "</select>";
									    			break;
									    		}
								    		}

								    		$out[] = "
										    	<th scope='row'>
										    		<label for='opt-{$s->name}'>" . __($s->description, "ezfc") . "</label>
										    	</th>
										    	<td>
										    		{$tmp_input}
										    		<p class='description'>" . __($s->description_long, "ezfc") . "</p>
										    	</td>
									    	";
								    	}

								    	echo implode("</tr><tr>", $out);
								    	?>

								    </tr>
								</table>
							</div>

							<?php

							$tab_i++;
						}
						?>
					</div>
				</div>

				<!-- placeholder for modal buttons -->
				<button class="button button-primary ezfc-option-save hidden" data-action="form_update_options" data-id=""><?php echo __("Update options", "ezfc"); ?></button>
			</form>
		</div>

		<!-- form import modal dialog -->
		<div class="ezfc-import-dialog ezfc-dialog" title="Import form">
			<form name="ezfc-form-import" action="">
				<p><?php echo __("Import data", "ezfc"); ?></p>
				<textarea name="import_data" id="form-import-data"></textarea>

				<!-- placeholder for modal buttons -->
				<button class="button button-primary ezfc-import-data hidden" data-action="form_import_data" data-id=""><?php echo __("Import form", "ezfc"); ?></button>
			</form>
		</div>

		<!-- form export modal dialog -->
		<div class="ezfc-export-dialog ezfc-dialog" title="Export form">
			<p><?php echo __("Export data", "ezfc"); ?></p>
			<textarea name="export_data" id="form-export-data"></textarea>
		</div>
	</div>
</div>

<script>ezfc_nonce = "<?php echo $nonce; ?>";</script>