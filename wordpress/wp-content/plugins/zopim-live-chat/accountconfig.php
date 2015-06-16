<?php
// Settings page in the admin panel
function zopim_account_config() {
	global $usernameToCodeURL, $languagesURL, $current_user;

?>
<div class="wrap">
<?php
	if (isset($_GET["action"]) && $_GET["action"]=="deactivate") {
		update_option('zopimSalt', "");
		update_option('zopimCode', "zopim");
	}

	$message = "";
	$authenticated = "";

	if (isset($_POST["action"]) && $_POST["action"]=="login") {

		if ($_POST["zopimUsername"] != "" && $_POST["zopimPassword"] != "") {
			$logindata = array("email" => $_POST["zopimUsername"], "password" => $_POST["zopimPassword"]);			
			$loginresult = json_to_array(zopim_post_request(ZOPIM_LOGIN_URL, $logindata));

			if (isset($loginresult->error)) {
				$error["login"] = "<b>Could not log in to Zopim. Please check your login details.</b>";
				$gotologin = 1;
				update_option('zopimSalt', "wronglogin");
			} else if (isset($loginresult->salt)) {
				update_option('zopimUsername', $_POST["zopimUsername"]);
				update_option('zopimSalt', $loginresult->salt);
				$account = getAccountDetails(get_option('zopimSalt'));
				$editor = setEditor(get_option('zopimSalt'));				

				if (isset($account)) {
					update_option('zopimCode', $account->account_key);

					if (get_option('zopimGreetings') == "") {
						$jsongreetings = to_json($account->settings->greetings);
						update_option('zopimGreetings', $jsongreetings);
					}
				}
			} else {
				update_option('zopimSalt', "");
				$error["login"] = "<b>Could not log in to Zopim. We were unable to contact Zopim servers. Please check with your server administrator to ensure that <a href='http://www.php.net/manual/en/book.curl.php'>PHP Curl</a> is installed and permissions are set correctly.</b>";
			}
		}
		else {
			update_option('zopimSalt', "wronglogin");
			$gotologin = 1;
			$error["login"] = "<b>Could not log in to Zopim. Please check your login details.</b>";
		}
	} else if (isset($_POST["action"]) && $_POST["action"]=="signup") {
		$createdata = array(
			"email" => $_POST["zopimnewemail"],
			"first_name" => $_POST["zopimfirstname"],
			"last_name" => $_POST["zopimlastname"],
			"display_name" => $_POST["zopimfirstname"]." ".$_POST["zopimlastname"],
			"eref" => $_POST["zopimeref"],
			"source" => "wordpress",
			"recaptcha_challenge_field" => $_POST["recaptcha_challenge_field"],
			"recaptcha_response_field" => $_POST["recaptcha_response_field"]
		);

		$signupresult = json_to_array(zopim_post_request(ZOPIM_SIGNUP_URL, $createdata));
		if (isset($signupresult->error)) {
			$message = "<div style='color:#c33;'>Error during activation: <b>".$signupresult->error."</b>. Please try again.</div>";
		} else if (isset($signupresult->account_key)) {
			$message = "<b>Thank you for signing up. Please check your mail for your password to complete the process. </b>";
			$gotologin = 1;
		} else {
			$message = "<b>Could not activate account. The wordpress installation was unable to contact Zopim servers. Please check with your server administrator to ensure that <a href='http://www.php.net/manual/en/book.curl.php'>PHP Curl</a> is installed and permissions are set correctly.</b>";
		}
	}

	if (get_option('zopimCode') != "" && get_option('zopimCode') != "zopim") {
		$accountDetails = getAccountDetails(get_option('zopimSalt'));

		if (!isset($accountDetails) || isset($accountDetails->error)) {
			$gotologin = 1;
			$error["auth"] = '
	 <div class="metabox-holder">
	<div class="postbox">
		<h3 class="hndle"><span>Account no longer linked!</span></h3>
		<div style="padding:10px;line-height:17px;">
		We could not verify your Zopim account. Please check your password and try again.
		</div>
	</div>
	 </div>'
;
		} else {
			$authenticated = "ok";
		}
	}

	if ($authenticated == "ok") {

		if ($accountDetails->package_id=="trial") {
			$accountDetails->package_id = "Free Lite Package + 14 Days Full-features";
		} else {
			$accountDetails->package_id .= " Package";
		}

?>
<div id="icon-options-general" class="icon32"><br/></div><h2>Set up your Zopim Account</h2>
<br/>
<div style="background:#FFFEEB;padding:25px;border:1px solid #eee;">
<span style="float:right;"><a href="admin.php?page=zopim_account_config&action=deactivate">Deactivate</a></span>
Currently Activated Account &rarr; <b><?php echo get_option('zopimUsername'); ?></b> <div style="display:inline-block;margin-left:5px;background:#444;color:#fff;font-size:10px;text-transform:uppercase;padding:3px 8px;-moz-border-radius:5px;-webkit-border-radius:5px;"><?php echo ucwords($accountDetails->package_id); ?></div>
<!--<br><p><br>You can <a href="admin.php?page=zopim_customize_widget">customize</a> the chat widget, or <a href="admin.php?page=zopim_dashboard">launch the dashboard</a> for advanced features.-->
<br><br>To start using Zopim chat, launch our dashboard for access to all features, including widget customization!
<br><br><a href="<?php echo ZOPIM_DASHBOARD_LINK."&username=".get_option('zopimUsername'); ?>" style="text-decoration:none;" target="_blank" data-popup="true"><div class="zopim_btn_orange">Launch Dashboard</div></a>&nbsp;&nbsp;(This will open up a new browser tab)


<form method="post" action="admin.php?page=zopim_account_config">
	<?php
	if (isset($_POST['widget-options'])) {
		$opts = $_POST['widget-options'];
		update_option('zopimWidgetOptions', $opts);
		echo '<i>Widget options updated.<br/></i>';
	}

	?>
	<p>
	Optional code for customization with Zopim API:
	<br/>
	<textarea name="widget-options" style="width:680px; height: 200px;"><?php echo esc_textarea(zopim_get_widget_options()); ?></textarea>
	<br/>
	<input class="button-primary" type="submit" value="Update widget options" />
	</p>
</form>

</div>
<?php } else { ?>
<div id="icon-options-general" class="icon32"><br/></div><h2>Set up your Zopim Account</h2>
<?php if (isset($error["auth"])) {
	echo $error["auth"];
	} else if ($message == "") { ?>
Congratulations on successfully installing the Zopim WordPress plugin!<br>
<br>
<?php } else { echo $message;} ?>
<div id="existingform">
	<div class="metabox-holder">
		<div class="postbox">
			<h3 class="hndle"><span>Link up with your Zopim account</span></h3>
			<div style="padding:10px;">
<?php if (isset($error["login"])) { echo '<span class="error">'.$error["login"].'</span>'; } ?>
<form method="post" action="admin.php?page=zopim_account_config">
	<input type="hidden" name="action" value="login">
	<table class="form-table">

			<tr valign="top">
			<th scope="row">Zopim Username (E-mail)</th>
			<td><input type="text" name="zopimUsername" value="<?php echo get_option('zopimUsername'); ?>" /></td>
			</tr>

			<tr valign="top">
			<th scope="row">Zopim Password</th>
			<td><input type="password" name="zopimPassword" value="" /></td>
			</tr>

			<!--<tr valign="center">
			<th scope="row">Use SSL</th>
			<td><input type="checkbox" name="zopimUseSSL" value="zopimUseSSL" <?php if (get_option('zopimUseSSL') == "zopimUseSSL") { echo "checked='checked'"; } ?> /> uncheck this if you are unable to login</td>
			</tr>-->
	 </table>
		<br/>
		The Zopim chat widget will display on your blog after your account is linked up.
		<br/>
		<p class="submit">
		<input id="linkup" type="submit" onclick="animateButton()" class="button-primary" value="<?php _e('Link Up') ?>" />
		&nbsp;Don't have a Zopim account? <a href="<?php echo ZOPIM_SIGNUP_REDIRECT_URL; ?>" target="_blank" data-popup="true">Sign up now</a>.
		</p>

</form>

			</div>
		</div>
	</div>
</div>

</div>


<?php } } ?>