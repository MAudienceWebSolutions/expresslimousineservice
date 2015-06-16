<?php
// Settings page in the admin panel
function zopim_account_config() {
	?>
	<div class="wrap">
	<?php
	if ( isset( $_GET["action"] ) && $_GET["action"] == "deactivate" ) {
		update_option( 'zopimSalt', "" );
		update_option( 'zopimCode', "zopim" );
	}

	$message       = "";
	$authenticated = "";

	if ( isset( $_POST["action"] ) && $_POST["action"] == "login" ) {

		if ( $_POST["zopimUsername"] != "" && $_POST["zopimPassword"] != "" ) {
			$logindata   = array( "email" => $_POST["zopimUsername"], "password" => $_POST["zopimPassword"] );
			$loginresult = json_to_array( zopim_post_request( ZOPIM_LOGIN_URL, $logindata ) );

			if ( isset( $loginresult->error ) ) {
				$error["login"] = "<b>" . __( 'Could not log in to Zopim. Please check your login details.',
						'zopim' ) . "</b>";
				$gotologin      = 1;
				update_option( 'zopimSalt', "wronglogin" );
			} else if ( isset( $loginresult->salt ) ) {
				update_option( 'zopimUsername', $_POST["zopimUsername"] );
				update_option( 'zopimSalt', $loginresult->salt );
				$account = zopim_get_account_details( get_option( 'zopimSalt' ) );
				$editor  = zopim_set_editor( get_option( 'zopimSalt' ) );

				if ( isset( $account ) ) {
					update_option( 'zopimCode', $account->account_key );

					if ( get_option( 'zopimGreetings' ) == "" ) {
						$jsongreetings = to_json( $account->settings->greetings );
						update_option( 'zopimGreetings', $jsongreetings );
					}
				}
			} else if ( isset( $loginresult->wp_error ) ) {
				update_option( 'zopimSalt', "" );
				$error["login"] = "<b>" . __( "Could not log in to Zopim. We were unable to contact Zopim servers. Please check with your server administrator to ensure that <a href='http://www.php.net/manual/en/book.curl.php'>PHP Curl</a> is installed and permissions are set correctly",
						"zopim" ) . "</b>";
			}
		} else {
			update_option( 'zopimSalt', "wronglogin" );
			$gotologin      = 1;
			$error["login"] = "<b>" . __( 'Could not log in to Zopim. Please check your login details.',
					'zopim' ) . "</b>";
		}
	} else if ( isset( $_POST["action"] ) && $_POST["action"] == "signup" ) {
		$createdata = array(
			"email"                     => $_POST["zopimnewemail"],
			"first_name"                => $_POST["zopimfirstname"],
			"last_name"                 => $_POST["zopimlastname"],
			"display_name"              => $_POST["zopimfirstname"] . " " . $_POST["zopimlastname"],
			"eref"                      => $_POST["zopimeref"],
			"source"                    => "wordpress",
			"recaptcha_challenge_field" => $_POST["recaptcha_challenge_field"],
			"recaptcha_response_field"  => $_POST["recaptcha_response_field"]
		);

		$signupresult = json_to_array( zopim_post_request( ZOPIM_SIGNUP_URL, $createdata ) );
		if ( isset( $signupresult->error ) ) {
			$message = "<div style='color:#c33;'>";
			$message .= sprintf( __( 'Error during activation: <b>%s</b>. Please try again.</div>', 'zopim' ),
				$signupresult->error );
		} else if ( isset( $signupresult->account_key ) ) {
			$message   = "<b>" . __( 'Thank you for signing up. Please check your mail for your password to complete the process.',
					'zopim' ) . "</b>";
			$gotologin = 1;
		} else {
			$message = "<b>" . __( "Could not activate account. The wordpress installation was unable to contact Zopim servers. Please check with your server administrator to ensure that <a href='http://www.php.net/manual/en/book.curl.php'>PHP Curl</a> is installed and permissions are set correctly.",
					'zopim' ) . "</b>";
		}
	}

	if ( get_option( 'zopimCode' ) != "" && get_option( 'zopimCode' ) != "zopim" ) {
		$accountDetails = zopim_get_account_details( get_option( 'zopimSalt' ) );

		if ( ! isset( $accountDetails ) || isset( $accountDetails->error ) ) {
			$gotologin     = 1;
			$error["auth"] = '
				<div class="metabox-holder">
					<div class="postbox">
						<h3 class="hndle"><span>' . __( 'Account no longer linked!', 'zopim' ) . '</span></h3>
						<div style="padding:10px;line-height:17px;">'
			                 . __( 'We could not verify your Zopim account. Please check your password and try again.', 'zopim' )
			                 . '</div>
					</div>
				 </div>';
		} else {
			$authenticated = "ok";
		}
	}

	if ( $authenticated == "ok" ) {

		if ( $accountDetails->package_id == "trial" ) {
			$accountDetails->package_id = __( 'Free Lite Package + 14 Days Full-features', 'zopim' );
		} else {
			$accountDetails->package_id .= __( ' Package', 'zopim' );
		}

		?>
		<div id="icon-options-general" class="icon32"><br/></div>
		<h2><?php _e( 'Set up your Zopim Account', 'zopim' ); ?></h2>
		<br/>
		<div style="background:#FFFEEB;padding:25px;border:1px solid #eee;">
			<span style="float:right;">
				<a href="admin.php?page=zopim_account_config&action=deactivate"><?php _e( 'Deactivate',
						'zopim' ); ?></a>
			</span>
			<?php _e( 'Currently Activated Account', 'zopim' ); ?> &rarr;
			<b><?php echo get_option( 'zopimUsername' ); ?></b>

			<div style="display:inline-block;margin-left:5px;background:#444;color:#fff;font-size:10px;text-transform:uppercase;padding:3px 8px;-moz-border-radius:5px;-webkit-border-radius:5px;"><?php echo ucwords( $accountDetails->package_id ); ?></div>
			<!--<br><p><br>You can <a href="admin.php?page=zopim_customize_widget">customize</a> the chat widget, or <a href="admin.php?page=zopim_dashboard">launch the dashboard</a> for advanced features.-->
			<br><br><?php _e( 'To start using Zopim chat, launch our dashboard for access to all features, including widget customization!',
				'zopim' ); ?>
			<br><br><a href="<?php echo ZOPIM_DASHBOARD_LINK . "&username=" . get_option( 'zopimUsername' ); ?>"
			           style="text-decoration:none;" target="_blank" data-popup="true">
				<div class="zopim_btn_orange"><?php _e( 'Launch Dashboard', 'zopim' ); ?></div>
			</a>&nbsp;&nbsp;(<?php _e( 'This will open up a new browser tab', 'zopim' ); ?>)


			<form method="post" action="admin.php?page=zopim_account_config">
				<?php
				if ( isset( $_POST['widget-options'] ) ) {
					$opts = $_POST['widget-options'];
					update_option( 'zopimWidgetOptions', $opts );
					echo '<i>' . __( 'Widget options updated.', 'zopim' ) . '<br/></i>';
				}

				?>
				<p>
					<?php _e( 'Optional code for customization with Zopim API:', 'zopim' ); ?>
					<br/>
					<textarea name="widget-options"
					          style="width:680px; height: 200px;"><?php echo esc_textarea( zopim_get_widget_options() ); ?></textarea>
					<br/>
					<input class="button-primary" type="submit" value="Update widget options"/>
				</p>
			</form>

		</div>
	<?php } else { ?>
		<div id="icon-options-general" class="icon32"><br/></div><h2><?php _e( 'Set up your Zopim Account',
				'zopim' ); ?></h2>
		<?php if ( isset( $error["auth"] ) ) {
			echo $error["auth"];
		} else if ( $message == "" ) { ?>
			<?php _e( 'Congratulations on successfully installing the Zopim WordPress plugin!', 'zopim' ); ?><br>
			<br>
		<?php } else {
			echo $message;
		} ?>
		<div id="existingform">
			<div class="metabox-holder">
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Link up with your Zopim account', 'zopim' ); ?></span></h3>

					<div style="padding:10px;">
						<?php if ( isset( $error["login"] ) ) {
							echo '<span class="error">' . $error["login"] . '</span>';
						} ?>
						<form method="post" action="admin.php?page=zopim_account_config">
							<input type="hidden" name="action" value="login">
							<table class="form-table">

								<tr valign="top">
									<th scope="row"><?php _e( 'Zopim Username (E-mail)', 'zopim' ); ?></th>
									<td><input type="text" name="zopimUsername"
									           value="<?php echo get_option( 'zopimUsername' ); ?>"/></td>
								</tr>

								<tr valign="top">
									<th scope="row"><?php _e( 'Zopim Password', 'zopim' ); ?></th>
									<td><input type="password" name="zopimPassword" value=""/></td>
								</tr>

							</table>
							<br/>
							<?php _e( 'The Zopim chat widget will display on your blog after your account is linked up.', 'zopim' ); ?>
							<br/>

							<p class="submit">
								<input id="linkup" type="submit" onclick="animateButton()" class="button-primary"
								       value="<?php _e( 'Link Up', 'zopim' ) ?>"/>
								&nbsp;<?php _e( 'Don\'t have a Zopim account?', 'zopim' ); ?> <a
									href="<?php echo ZOPIM_SIGNUP_REDIRECT_URL; ?>" target="_blank"
									data-popup="true"><?php _e( 'Sign up now', 'zopim' ); ?></a>.
							</p>

						</form>

					</div>
				</div>
			</div>
		</div>

		</div>


	<?php }
} ?>
