<?php

defined( 'ABSPATH' ) OR exit;

require_once(plugin_dir_path(__FILE__) . "class.ezfc_backend.php");
$ezfc = new Ezfc_backend();
$message = "";

// clear logs
if (isset($_REQUEST["clear_logs"]) && $_REQUEST["clear_logs"] == 1) {
	$ezfc->clear_debug_log();
	$message = "Logs cleared.";
}
// send test mail
if (isset($_REQUEST["send_test_mail"]) && $_REQUEST["send_test_mail"] == 1 &&
	isset($_REQUEST["send_test_mail_recipient"]) && !empty($_REQUEST["send_test_mail_recipient"])) {
	$message = $ezfc->send_test_mail($_REQUEST["send_test_mail_recipient"]);
}

$debug_active = get_option("ezfc_debug_mode", 0)==1 ? true : false;
$debug_log    = $ezfc->get_debug_log();

$debug_vars = array(
	"php_version"  => phpversion(),
	"wp_version"   => get_bloginfo("version"),
	"magic_quotes" => get_magic_quotes_gpc()==0 ? "Off" : "On"
);

?>

<div class="ezfc wrap">
	<div class="container-fluid">
		<div class="col-lg-12">
			<h2><?php echo __("Help / debug", "ezfc"); ?> - ez Form Calculator v<?php echo ezfc_get_version(); ?></h2> 
			<p>
				<a class="button button-primary" href="http://www.mials.de/mials/ezfc/documentation/" target="_blank">Open documentation site</a>
			</p>

			<p>
				If you have found any bugs, please report them to <a href="mailto:support@mials.de">support@mials.de</a>. Thank you!
			</p>
		</div>

		<?php if (!empty($message)) { ?>
			<div class="col-lg-12">
				<div id="message" class="updated"><?php echo $message; ?></div>
			</div>
		<?php } ?>

		<div class="col-lg-10">
			<h3>Debug log</h3>

			<p>
				Debug mode is <strong><?php echo $debug_active ? "active" : "inactive"; ?></strong>.
			</p>
			<textarea class="ezfc-settings-type-textarea" style="height: 400px;"><?php echo $debug_log; ?></textarea>

			<form action="" method="POST">
				<input type="hidden" value="1" name="clear_logs" />
				<input type="submit" value="Clear logs" class="button button-primary" />
			</form>
		</div>

		<div class="col-lg-2">
			<h3>Environment Vars</h3>
			<?php
			$out = array();
			$out[] = "<table>";
			foreach ($debug_vars as $key => $var) {
				$out[] = "<tr>";
				$out[] = "	<td>";
				$out[] = 		$key;
				$out[] = "	</td><td>";
				$out[] = 		$var;
				$out[] = "	</td>";
				$out[] = "</tr>";
			}
			$out[] = "</table>";

			echo implode("", $out);
			?>

			<h3>Tests</h3>
			<form action="" method="POST">
				<input type="hidden" value="1" name="send_test_mail" />
				<input type="text" value="" name="send_test_mail_recipient" placeholder="your@email.com" />
				<input type="submit" value="Send test mail" class="button" />
			</form>
		</div>
	</div>
</div>
