<?php

defined( 'ABSPATH' ) OR exit;

if (isset($_POST["ezfc-reset"])) {
	$keep_data_option = get_option("ezfc_uninstall_keep_data", 0);
	update_option("ezfc_uninstall_keep_data", 0);

	ezfc_uninstall();
	ezfc_register();

	update_option("ezfc_uninstall_keep_data", $keep_data_option);
	$_POST = array();
}

if (isset($_GET["woo_setup"]) || isset($_REQUEST["ezfc-force-woocommerce"])) {
	unset($_POST["alt_opt"]["ezfc_woocommerce_product_id"]);
	$woo_install_flag = isset($_REQUEST["ezfc-force-woocommerce"]) ? true : false;
	$woo_install_res  = ezfc_woo_setup($woo_install_flag);

	if (isset($woo_install_res["error"])) {
		echo "<p style='color: #f00;'>Error: {$res["error"]}";
	}
}

require_once(plugin_dir_path(__FILE__) . "class.ezfc_backend.php");
$ezfc = new Ezfc_backend();

if (isset($_POST["submit"])) {
	$_POST["ezfc-manual-update"] = isset($_POST["ezfc-manual-update"]) ? $_POST["ezfc-manual-update"] : null;

	$ezfc->update_options(false, false, $_POST["ezfc-manual-update"]);

	// additional options
	foreach ($_POST["alt_opt"] as $k=>$v) {
		update_option($k, stripslashes($v));
	}

	$updated = 1;
}

$themes = $ezfc->get_themes();

$currency_array = array(
	"Australian Dollar" => "AUD", "Brazilian Real" => "BRL", "Canadian Dollar" => "CAD", "Czech Koruna" => "CZK", "Danish Krone" => "DKK", "Euro" => "EUR", "Hong Kong Dollar" => "HKD", "Hungarian Forint" => "HUF", "Israeli New Sheqel" => "ILS", "Japanese Yen" => "JPY", "Malaysian Ringgit" => "MYR", "Mexican Peso" => "MXN", "Norwegian Krone" => "NOK", "New Zealand Dollar" => "NZD", "Philippine Peso" => "PHP", "Polish Zloty" => "PLN", "Pound Sterling" => "GBP", "Singapore Dollar" => "SGD", "Swedish Krona" => "SEK", "Swiss Franc" => "CHF", "Taiwan New Dollar" => "TWD", "Thai Baht" => "THB", "Turkish Lira" => "TRY", "U.S. Dollar" => "USD"
); 

// other settings
$settings_alt = array(
	"Customization" => array(
		"price_format"              => array("desc" => "Price format", "desc_add" => "See <a href='http://numeraljs.com/' target='_blank'>numeraljs.com</a> for syntax documentation", "type" => "input"),
		"jquery_ui"                 => array("desc" => "Add default jQuery UI stylesheet", "desc_add" => "If your theme looks differently after installing this plugin, set this option to 'No' and see again. It may break due to the default jQuery UI stylesheet.", "type" => "yesno"),
		"css_form_label_width"      => array("desc" => "CSS label width", "desc_add" => "Width of the labels. Default: 15em", "type" => "input"),
		"custom_css"                => array("desc" => "Custom CSS", "desc_add" => "Add your custom styles here.", "type" => "textarea"),
		"required_text"             => array("desc" => "Required text", "desc_add" => "Default: 'Required'", "type" => "input"),
		"datepicker_language"       => array("desc" => "Datepicker language", "desc_add" => "Datepicker language. Default: 'en'", "type" => "input"),
		"datepicker_load_languages" => array("desc" => "Load datepicker languages", "desc_add" => "Load additional datepicker languages. Only set this option to 'Yes' when using a different language than English since all languages will be loaded with an additional ~40kb file. If you know what you are doing, you can remove all unneccessary data from the file /ez-form-calculator-premium/assets/js/jquery.ui.u18n.all.min.js", "type" => "yesno")
	),

	"Captcha" => array(
		"captcha_public"  => array("desc" => "Recaptcha public key",  "desc_add" => "", "type" => "input"),
		"captcha_private" => array("desc" => "Recaptcha private key", "desc_add" => "", "type" => "input")
	),

	"Email" => array(
		"email_smtp_enabled" => array("desc" => "Enable SMTP",  "desc_add" => "", "type" => "yesno"),
		"email_smtp_host"    => array("desc" => "SMTP Host", "desc_add" => "", "type" => "input"),
		"email_smtp_user"    => array("desc" => "SMTP Username", "desc_add" => "", "type" => "input"),
		"email_smtp_pass"    => array("desc" => "SMTP Password", "desc_add" => "", "type" => "password"),
		"email_smtp_port"    => array("desc" => "SMTP Port", "desc_add" => "", "type" => "input"),
		"email_smtp_secure"  => array("desc" => "SMTP Encryption", "desc_add" => "", "type" => "dropdown", "values" => array(
			""    => "No encryption",
			"ssl" => "SSL",
			"tls" => "TLS"
		))
	),

	"PayPal" => array(
		"pp_api_username"         => array("desc" => "PayPal API username", "desc_add" => "See <a href='https://developer.paypal.com/docs/classic/api/apiCredentials/'>PayPal docs</a> to read how to get your API credentials.", "type" => "input"),
		"pp_api_password"         => array("desc" => "PayPal API password", "desc_add" => "", "type" => "password"),
		"pp_api_signature"        => array("desc" => "PayPal API signature", "desc_add" => "", "type" => "input"),
		"pp_return_url"           => array("desc" => "Return URL", "desc_add" => "The return URL is the location where buyers return to when a payment has been succesfully authorized. <br>You need to use this shortcode on the return page/post or else it will not work:<br>[ezfc_verify]", "type" => "input"),
		"pp_cancel_url"           => array("desc" => "Cancel URL", "desc_add" => "The cancelURL is the location buyers are sent to when they hit the cancel button during authorization of payment during the PayPal flow.", "type" => "input"),
		"pp_currency_code"        => array("desc" => "Currency code", "desc_add" => "", "type" => "currencycodes"),
		"pp_sandbox"              => array("desc" => "Use sandbox", "desc_add" => "Set to 'yes' for testing purposes.", "type" => "yesno")
	),

	"WooCommerce" => array(
		"woocommerce"            => array("desc" => "Integrate with WooCommerce", "desc_add" => "Integrate with WooCommerce", "type" => "yesno"),
		"woocommerce_product_id" => array("desc" => "WooCommerce product id", "desc_add" => "WooCommerce product id which is used as placeholder for orders. Should be filled in automatically after integration.", "type" => "input"),
		"woocommerce_text"       => array("desc" => "WooCommerce added to cart text", "desc_add" => "This text will be displayed after a submission was added to the cart.", "type" => "input")
	),

	"Other" => array(
		//"edit_role"              => array("desc" => "WP user edit role", "desc_add" => "Minimum WP user role to access the plugin.", "type" => "edit_roles"),
		"mailchimp_api_key"      => array("desc" => "Mailchimp API key", "desc_add" => "<a href='http://kb.mailchimp.com/accounts/management/about-api-keys'>How to find your API key</a> (mailchimp.com)", "type" => "input"),
		"debug_mode"             => array("desc" => "Enable debug mode", "desc_add" => "", "type" => "yesno"),
		"uninstall_keep_data"    => array("desc" => "Keep data after uninstall", "desc_add" => "The plugin will keep all plugin-related data in the databse when uninstalling. Only select 'Yes' if you want to upgrade the script.", "type" => "yesno")
	)
);

?>

<div class="ezfc wrap">
	<h2><?php echo __("Global settings", "ezfc"); ?> - ez Form Calculator v<?php echo ezfc_get_version(); ?></h2> 

	<?php
	if (isset($updated)) {
		?>

		<div id="message" class="updated"><?php echo __("Settings saved.", "ezfc"); ?></div>

		<?php
	}

	// check if woocommerce is installed
	if (ezfc_woo_check() && !ezfc_woo_product_exists()) {
		?>

		<div class='updated'>
			<p>WooCommerce is installed but ez Form Calculator is not integrated yet.</p>

			<p><a href='<?php echo admin_url(); ?>admin.php?page=ezfc-options&woo_setup=1' class='button button-primary'>Integrate with WooCommerce</a></p>
		</div>

		<?php
	}

	// woocommerce integration successful.
	if ((ezfc_woo_check() && ezfc_woo_product_exists() && isset($_GET["woo_setup"])) ||
		(isset($_REQUEST["ezfc-force-woocommerce"]) && !isset($woo_install_res["error"]))) {
		?>

		<div class='updated'>
			<p>Integration was successful! Form submissions can be added to the cart. Check the 'Global settings' page.</p>
		</div>

		<?php
	}
	?>

	<form method="POST" name="ezfc-form" class="ezfc-form" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<div id="tabs">
			<ul>
				<?php
				$tabs = array_keys($settings_alt);

				foreach ($tabs as $i => $cat) {
					echo "<li><a href='#tab-{$i}'>{$cat}</a></li>";
				}
				?>
			</ul>

		    <?php

		    $tab_i = 0;
		    foreach ($settings_alt as $cat => $s) {
		    	?>

		    	<div id="tab-<?php echo $tab_i; ?>">
			    	<table class="form-table">
						<tr>

							<?php
							$out = array();

			    			foreach ($s as $name => $s) {
						    	$tmp_opt = get_option("ezfc_{$name}");
						    	$tmp_out = "";

						    	$tmp_out .= "
							    	<th scope='row'>
							    		<label for='alt_opt-{$name}'>" . __($s["desc"], "ezfc") . "</label>
							    	</th>
							    	<td>";

							    switch ($s["type"]) {
							    	case "dropdown":
							    		$tmp_input  = "<select id='alt_opt-{$name}' name='alt_opt[ezfc_{$name}]'>";

							    		foreach ($s["values"] as $value => $description) {
							    			$selected = "";
							    			if ($tmp_opt == $value) $selected = "selected";
						    				
						    				$tmp_input .= "<option value='{$value}' {$selected}>" . __($description, "ezfc") . "</option>";
						    			}

						    			$tmp_input .= "</select>";
							    	break;

							    	case "input":
							    		$tmp_input = "<input type='text' class='regular-text' id='alt_opt-{$name}' name='alt_opt[ezfc_{$name}]' value='{$tmp_opt}' />";
							    	break;

							    	case "password":
							    		$tmp_input = "<input type='password' class='regular-text' id='alt_opt-{$name}' name='alt_opt[ezfc_{$name}]' value='{$tmp_opt}' />";
							    	break;

							    	case "yesno":
							    		$selected_no = $selected_yes = "";

					    				if ($tmp_opt == 0) $selected_no  = " selected='selected'";
					    				else               $selected_yes = " selected='selected'";

					    				$tmp_input  = "<select id='alt_opt-{$name}' name='alt_opt[ezfc_{$name}]'>";
					    				$tmp_input .= "    <option value='0' {$selected_no}>" . __("No", "ezfc") . "</option>";
					    				$tmp_input .= "    <option value='1' {$selected_yes}>" . __("Yes", "ezfc") . "</option>";
					    				$tmp_input .= "</select>";
							    	break;

							    	case "textarea":
					    				$tmp_input  = "<textarea class='ezfc-settings-type-textarea' id='alt_opt-{$name}' name='alt_opt[ezfc_{$name}]'>";
					    				$tmp_input .= $tmp_opt;
					    				$tmp_input .= "</textarea>";
							    	break;

							    	case "editor":
					    				ob_start();

					    				wp_editor($tmp_opt, "editor_{$i}", array(
					    					"textarea_name" => "alt_opt[ezfc_{$name}]",
					    					"textarea_rows" => 5,
					    					"teeny"         => true
					    				));
					    				$tmp_input = ob_get_contents();

					    				ob_end_clean();
					    			break;

					    			case "currencycodes":
										$tmp_input  = "<select id='alt_opt-{$name}' name='alt_opt[ezfc_{$name}]'>";
					    				foreach ($currency_array as $desc => $v) {
					    					$selected = "";
					    					if ($tmp_opt == $v) $selected = "selected='selected'";

					    					$tmp_input .= "<option value='{$v}' {$selected}>({$v}) {$desc}</option>";
					    				}

					    				$tmp_input .= "</select>";
									break;
							    }

							    $tmp_out .= $tmp_input;
							    $tmp_out .= "<p class='description'>" . __($s["desc_add"], "ezfc") . "</p>
							    	</td>";

							    $out[] = $tmp_out;
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
			<!-- force woocommerce install -->
			<tr>
				<th scope='row'>
					<label for="ezfc-force-woocommerce">Force WooCommerce product integration</label>
		    	</th>
		    	<td>
		    		<input type="checkbox" name="ezfc-force-woocommerce" id="ezfc-force-woocommerce" value="1" /><br>
		    		<p class="description">In case WooCommerce could not be detected by the plugin, check this option in order to integrate this plugin.</p>
		    	</td>
		    </tr>	

		    <!-- manual update -->
			<tr>
				<th scope='row'>
					<label for="ezfc-manual-update">Manual update</label>
		    	</th>
		    	<td>
		    		<input type="checkbox" name="ezfc-manual-update" id="ezfc-manual-update" value="1" /><br>
		    		<p class="description">Checking this option will perform certain database changes. <strong>Check this if you recently updated the script as this will perform necessary changes.</strong></p>
		    		<p class="description">This will overwrite all default settings but not form options.</p>
		    	</td>
		    </tr>

		    <!-- reset -->
			<tr>
				<th scope='row'>
					<label for="ezfc-manual-update">Reset</label>
		    	</th>
		    	<td>
		    		<input type="checkbox" name="ezfc-reset" id="ezfc-reset" value="1" /><br>
		    		<p class="description">Complete reset of this plugin. <strong>This will reset all existing data. Use with caution.</strong></p>
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