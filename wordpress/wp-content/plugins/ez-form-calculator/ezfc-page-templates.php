<?php

defined( 'ABSPATH' ) OR exit;

global $wpdb;

$nonce = wp_create_nonce("ezfc-nonce");

if (!function_exists("curl_init")) {
	echo "cURL is not installed. Therefore, you cannot use the template browser. :(";
	return;
}

$error = "";

// todo: install template
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "install") {
	// install
	$curl_url = "http://localhost/ezPlugins/ezfc-template-server/get-template.php?id={$_REQUEST["id"]}";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $curl_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	
	$res = curl_exec($ch);
	curl_close($ch);

	$template_json = json_decode($res);
	if (!property_exists($template_json, "data")) {
		$error = "Could not install template.";
	}
	else {
		require_once(plugin_dir_path(__FILE__) . "class.ezfc_backend.php");
		$ezfc_backend = new Ezfc_backend();

		$template_data_json = json_decode($template_json->data);

		$return = $ezfc_backend->form_add(0, $template_data_json);
		if (is_array($return) && isset($return["error"])) {
			$error = $return["error"];
		}
		else {
			echo "Template installed successfully. ID: {$return}";
		}
	}
}

// get templates
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/ezPlugins/ezfc-template-server/list.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	
$res = curl_exec($ch);
curl_close($ch);

$templates = json_decode($res);

?>

<div class="ezfc wrap ezfc-wrapper">
	<div class="ezfc-error"></div>
	<div class="ezfc-message"></div>

	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<?php echo "<h2>" . __("Template Browser", "ezfc") . " <span class='spinner'></span></h2>"; ?>
			</div>

			<?php if (!empty($error)) { ?>
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<?php echo $error; ?>
				</div>
			<?php } ?>
		</div>

		<div class="row">
			<?php
			if (count($templates) > 0) {
				foreach ($templates as $template) {
					$install_link = admin_url("admin.php") . "?page=ezfc-templates&amp;action=install&amp;id={$template->id}";
					?>

					<div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
						<h4><?php echo $template->name; ?></h4>
						<p class="ezfc-template-desc">
							<?php echo $template->description; ?>
						</p>
						<p class="ezfc-template-install">
							<a href="<?php echo $install_link; ?>" class="button button-primary"><?php _e("Install"); ?></a>
						</p>
					</div>

					<?php
				}
			}
			?>
		</div>
	</div>
</div>

<script>ezfc_nonce = "<?php echo $nonce; ?>";</script>