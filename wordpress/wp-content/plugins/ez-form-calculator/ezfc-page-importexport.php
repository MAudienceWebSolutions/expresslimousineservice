<?php

defined( 'ABSPATH' ) OR exit;

global $wpdb;

require_once(plugin_dir_path(__FILE__) . "class.ezfc_backend.php");
$ezfc = new Ezfc_backend();

if (isset($_POST["submit"])) {
	$ezfc->import_data($_POST["import_data"]);

	$updated = 1;
}

$export_data = $ezfc->get_export_data();

?>

<div class="ezfc wrap">
	<h2><?php echo __("Import / Export data", "ezfc"); ?></h2> 

	<?php
	if (isset($updated)) {
		?>

		<div id="message" class="updated"><?php echo __("Data imported.", "ezfc"); ?></div>

		<?php
	}
	?>

	<h3>Export data</h3>
	<textarea class="ezfc-settings-type-textarea"><?php echo json_encode($export_data); ?></textarea>

	<h3>Import data</h3>
	<form method="POST" name="ezfc-form" class="ezfc-form" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<textarea class="ezfc-settings-type-textarea" name="import_data"></textarea>

		<!-- save -->
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __("Import", "ezfc"); ?>" /></p>
	</form>
</div>

<script>
jQuery(function($) {
	$(".ezfc-form").on("submit", function() {
		// confirmation
		if (!confirm("Importing will overwrite all existing data. Continue?")) return false;
	});
});
</script>