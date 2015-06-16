<?php

defined( 'ABSPATH' ) OR exit;

global $wpdb;

if (isset($_POST["ezfc-reset"])) {
	$keep_data_option = get_option("ezfc_uninstall_keep_data", 0);
	update_option("ezfc_uninstall_keep_data", 0);

	ezfc_uninstall();
	ezfc_register();

	update_option("ezfc_uninstall_keep_data", $keep_data_option);
	$_POST = array();
}

require_once(plugin_dir_path(__FILE__) . "class.ezfc_backend.php");
$ezfc = new Ezfc_backend();

if (isset($_POST["submit"])) {
	$_POST["opt"] = isset($_POST["opt"]) ? $_POST["opt"] : null;
	$_POST["ezfc-overwrite"] = isset($_POST["ezfc-overwrite"]) ? $_POST["ezfc-overwrite"] : null;
	$_POST["ezfc-manual-update"] = isset($_POST["ezfc-manual-update"]) ? $_POST["ezfc-manual-update"] : null;

	$ezfc->update_options($_POST["opt"], $_POST["ezfc-overwrite"], $_POST["ezfc-manual-update"]);

	$updated = 1;
}

$settings = $ezfc->get_settings("cat ASC, name ASC");
$themes   = $ezfc->get_themes();

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

?>

<div class="ezfc wrap">
	<h2><?php echo __("Form settings", "ezfc"); ?> - ez Form Calculator v<?php echo ezfc_get_version(); ?></h2> 
	<p>These options can be changed individually in each form. Saving these options will be applied to new forms only (when you did not check to overwrite settings).</p>

	<?php
	if (isset($updated)) {
		?>

		<div id="message" class="updated"><?php echo __("Settings saved.", "ezfc"); ?></div>

		<?php
	}
	?>

	<form method="POST" name="ezfc-form" class="ezfc-form" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
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
						    	$tmp_input = "<input type='text' class='regular-text {$add_class}' id='opt-{$s->name}' name='opt[{$s->id}]' value='{$s->value}' />";

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

						    				wp_editor($s->value, "editor_{$i}", array(
						    					"textarea_name" => "opt[{$s->id}]",
						    					"textarea_rows" => 5,
						    					"teeny"         => true
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

		</div> <!-- tabs -->

		<table class="form-table" style="margin-top: 1em;">
			<!-- overwrite settings -->
			<tr>
				<th scope='row'>
					<label for="ezfc-overwrite">Overwrite settings</label>
		    	</th>
		    	<td>
		    		<input type="checkbox" name="ezfc-overwrite" id="ezfc-overwrite" value="1" /><br>
		    		<p class="description">Checking this option will overwrite <b>ALL</b> existing form settings!</p>
		    	</td>
		    </tr>
		</table>

		<!-- save -->
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __("Save", "ezfc"); ?>" /></p>
	</form>
</div>

<script>
jQuery(function($) {
	$(".ezfc-form").on("submit", function() {
		// confirmation
		if ($("#ezfc-overwrite").prop("checked") || $("#ezfc-reset").prop("checked")) {
			if (!confirm("Really overwrite all settings?")) return false;
		}
	});

	$("#tabs").tabs();
});
</script>