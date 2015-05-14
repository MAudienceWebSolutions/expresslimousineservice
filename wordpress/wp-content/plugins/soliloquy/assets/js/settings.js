/* ==========================================================
 * settings.js
 * http://soliloquywp.com/
 * ==========================================================
 * Copyright 2014 Thomas Griffin.
 *
 * Licensed under the GPL License, Version 2.0 or later (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================== */
;(function($){
    $(function(){
        // Initialize the slider tabs.
        var soliloquy_tabs           = $('#soliloquy-tabs'),
            soliloquy_tabs_nav       = $('#soliloquy-tabs-nav'),
            soliloquy_tabs_hash      = window.location.hash,
            soliloquy_tabs_hash_sani = window.location.hash.replace('!', '');

        // If we have a hash and it begins with "soliloquy-tab", set the proper tab to be opened.
        if ( soliloquy_tabs_hash && soliloquy_tabs_hash.indexOf('soliloquy-tab-') >= 0 ) {
            $('.soliloquy-active').removeClass('soliloquy-active nav-tab-active');
            soliloquy_tabs_nav.find('a[href="' + soliloquy_tabs_hash_sani + '"]').addClass('soliloquy-active nav-tab-active');
            soliloquy_tabs.find(soliloquy_tabs_hash_sani).addClass('soliloquy-active').show();
        }

        // Change tabs on click.
        $('#soliloquy-tabs-nav a').on('click', function(e){
            e.preventDefault();
            var $this = $(this);
            if ( $this.hasClass('soliloquy-active') ) {
                return;
            } else {
                window.location.hash = soliloquy_tabs_hash = this.hash.split('#').join('#!');
                var current = soliloquy_tabs_nav.find('.soliloquy-active').removeClass('soliloquy-active nav-tab-active').attr('href');
                $this.addClass('soliloquy-active nav-tab-active');
                soliloquy_tabs.find(current).removeClass('soliloquy-active').hide();
                soliloquy_tabs.find($this.attr('href')).addClass('soliloquy-active').show();
            }
        });

        // Start the upgrade process.
        $('.soliloquy-start-upgrade').on('click', function(e){
            e.preventDefault();
            var $this = $(this);

            // Show the spinner.
            $('.soliloquy-spinner').css({ 'display' : 'inline-block', 'float' : 'none', 'vertical-align' : 'text-bottom' });

            // Prepare our data to be sent via Ajax.
            var upgrade = {
                action:  'soliloquy_upgrade_sliders',
                nonce:   soliloquy_settings.upgrade_nonce
            };

            // Process the Ajax response and output all the necessary data.
            $.post(
                soliloquy_settings.ajax,
                upgrade,
                function(response) {
                    // Hide the spinner.
                    $('.soliloquy-spinner').hide();

                    // Redirect back to Soliloquy screen.
                    window.location.replace(soliloquy_settings.redirect);
                },
                'json'
            );
        });

        // Re-enable install button if user clicks on it, needs creds but tries to install another addon instead.
        $('#soliloquy-addons-area').on('click.refreshInstallAddon', '.soliloquy-addon-action-button', function(e) {
            var el      = $(this);
            var buttons = $('#soliloquy-addons-area').find('.soliloquy-addon-action-button');
            $.each(buttons, function(i, element) {
                if ( el == element )
                    return true;

                soliloquyAddonRefresh(element);
            });
        });

        // Process Addon activations for those currently installed but not yet active.
        $('#soliloquy-addons-area').on('click.activateAddon', '.soliloquy-activate-addon', function(e) {
            e.preventDefault();
            var $this = $(this);

            // Remove any leftover error messages, output an icon and get the plugin basename that needs to be activated.
            $('.soliloquy-addon-error').remove();
            $(this).text(soliloquy_settings.activating);
            $(this).next().css({'display' : 'inline-block', 'margin-top' : '0px'});
            var button  = $(this);
            var plugin  = $(this).attr('rel');
            var el      = $(this).parent().parent();
            var message = $(this).parent().parent().find('.addon-status');

            // Process the Ajax to perform the activation.
            var opts = {
                url:      ajaxurl,
                type:     'post',
                async:    true,
                cache:    false,
                dataType: 'json',
                data: {
                    action: 'soliloquy_activate_addon',
                    nonce:  soliloquy_settings.activate_nonce,
                    plugin: plugin
                },
                success: function(response) {
                    // If there is a WP Error instance, output it here and quit the script.
                    if ( response && true !== response ) {
                        $(el).slideDown('normal', function() {
                            $(this).after('<div class="soliloquy-addon-error"><strong>' + response.error + '</strong></div>');
                            $this.next().hide();
                            $('.soliloquy-addon-error').delay(3000).slideUp();
                        });
                        return;
                    }

                    // The Ajax request was successful, so let's update the output.
                    $(button).text(soliloquy_settings.deactivate).removeClass('soliloquy-activate-addon').addClass('soliloquy-deactivate-addon');
                    $(message).text(soliloquy_settings.active);
                    $(el).removeClass('soliloquy-addon-inactive').addClass('soliloquy-addon-active');
                    $this.next().hide();
                },
                error: function(xhr, textStatus ,e) {
                    $this.next().hide();
                    return;
                }
            }
            $.ajax(opts);
        });

        // Process Addon deactivations for those currently active.
        $('#soliloquy-addons-area').on('click.deactivateAddon', '.soliloquy-deactivate-addon', function(e) {
            e.preventDefault();
            var $this = $(this);

            // Remove any leftover error messages, output an icon and get the plugin basename that needs to be activated.
            $('.soliloquy-addon-error').remove();
            $(this).text(soliloquy_settings.deactivating);
            $(this).next().css({'display' : 'inline-block', 'margin-top' : '0px'});
            var button  = $(this);
            var plugin  = $(this).attr('rel');
            var el      = $(this).parent().parent();
            var message = $(this).parent().parent().find('.addon-status');

            // Process the Ajax to perform the activation.
            var opts = {
                url:      ajaxurl,
                type:     'post',
                async:    true,
                cache:    false,
                dataType: 'json',
                data: {
                    action: 'soliloquy_deactivate_addon',
                    nonce:  soliloquy_settings.deactivate_nonce,
                    plugin: plugin
                },
                success: function(response) {
                    // If there is a WP Error instance, output it here and quit the script.
                    if ( response && true !== response ) {
                        $(el).slideDown('normal', function() {
                            $(this).after('<div class="soliloquy-addon-error"><strong>' + response.error + '</strong></div>');
                            $this.next().hide();
                            $('.soliloquy-addon-error').delay(3000).slideUp();
                        });
                        return;
                    }

                    // The Ajax request was successful, so let's update the output.
                    $(button).text(soliloquy_settings.activate).removeClass('soliloquy-deactivate-addon').addClass('soliloquy-activate-addon');
                    $(message).text(soliloquy_settings.inactive);
                    $(el).removeClass('soliloquy-addon-active').addClass('soliloquy-addon-inactive');
                    $this.next().hide();
                },
                error: function(xhr, textStatus ,e) {
                    $this.next().hide();
                    return;
                }
            }
            $.ajax(opts);
        });

        // Process Addon installations.
        $('#soliloquy-addons-area').on('click.installAddon', '.soliloquy-install-addon', function(e) {
            e.preventDefault();
            var $this = $(this);

            // Remove any leftover error messages, output an icon and get the plugin basename that needs to be activated.
            $('.soliloquy-addon-error').remove();
            $(this).text(soliloquy_settings.installing);
            $(this).next().css({'display' : 'inline-block', 'margin-top' : '0px'});
            var button  = $(this);
            var plugin  = $(this).attr('rel');
            var el      = $(this).parent().parent();
            var message = $(this).parent().parent().find('.addon-status');

            // Process the Ajax to perform the activation.
            var opts = {
                url:      ajaxurl,
                type:     'post',
                async:    true,
                cache:    false,
                dataType: 'json',
                data: {
                    action: 'soliloquy_install_addon',
                    nonce:  soliloquy_settings.install_nonce,
                    plugin: plugin
                },
                success: function(response) {
                    // If there is a WP Error instance, output it here and quit the script.
                    if ( response.error ) {
                        $(el).slideDown('normal', function() {
                            $(button).parent().parent().after('<div class="soliloquy-addon-error"><strong>' + response.error + '</strong></div>');
                            $(button).text(soliloquy_settings.install);
                            $this.next().hide();
                            $('.soliloquy-addon-error').delay(4000).slideUp();
                        });
                        return;
                    }

                    // If we need more credentials, output the form sent back to us.
                    if ( response.form ) {
                        // Display the form to gather the users credentials.
                        $(el).slideDown('normal', function() {
                            $(this).after('<div class="soliloquy-addon-error">' + response.form + '</div>');
                            $this.next().hide();
                        });

                        // Add a disabled attribute the install button if the creds are needed.
                        $(button).attr('disabled', true);

                        $('#soliloquy-addons-area').on('click.installCredsAddon', '#upgrade', function(e) {
                            // Prevent the default action, let the user know we are attempting to install again and go with it.
                            e.preventDefault();
                            $this.next().hide();
                            $(this).val(soliloquy_settings.installing);
                            $(this).next().css({'display' : 'inline-block', 'margin-top' : '0px'});

                            // Now let's make another Ajax request once the user has submitted their credentials.
                            var hostname  = $(this).parent().parent().find('#hostname').val();
                            var username  = $(this).parent().parent().find('#username').val();
                            var password  = $(this).parent().parent().find('#password').val();
                            var proceed   = $(this);
                            var connect   = $(this).parent().parent().parent().parent();
                            var cred_opts = {
                                url:      ajaxurl,
                                type:     'post',
                                async:    true,
                                cache:    false,
                                dataType: 'json',
                                data: {
                                    action:   'soliloquy_install_addon',
                                    nonce:    soliloquy_settings.install_nonce,
                                    plugin:   plugin,
                                    hostname: hostname,
                                    username: username,
                                    password: password
                                },
                                success: function(response) {
                                    // If there is a WP Error instance, output it here and quit the script.
                                    if ( response.error ) {
                                        $(el).slideDown('normal', function() {
                                            $(button).parent().parent().after('<div class="soliloquy-addon-error"><strong>' + response.error + '</strong></div>');
                                            $(button).text(soliloquy_settings.install);
                                            $this.next().hide();
                                            $('.soliloquy-addon-error').delay(4000).slideUp();
                                        });
                                        return;
                                    }

                                    if ( response.form ) {
                                        $this.next().hide();
                                        $('.soliloquy-inline-error').remove();
                                        $(proceed).val(soliloquy_settings.proceed);
                                        $(proceed).after('<span class="soliloquy-inline-error">' + soliloquy_settings.connect_error + '</span>');
                                        return;
                                    }

                                    // The Ajax request was successful, so let's update the output.
                                    $(connect).remove();
                                    $(button).show();
                                    $(button).text(soliloquy_settings.activate).removeClass('soliloquy-install-addon').addClass('soliloquy-activate-addon');
                                    $(button).attr('rel', response.plugin);
                                    $(button).removeAttr('disabled');
                                    $(message).text(soliloquy_settings.inactive);
                                    $(el).removeClass('soliloquy-addon-not-installed').addClass('soliloquy-addon-inactive');
                                    $this.next().hide();
                                },
                                error: function(xhr, textStatus ,e) {
                                    $this.next().hide();
                                    return;
                                }
                            }
                            $.ajax(cred_opts);
                        });

                        // No need to move further if we need to enter our creds.
                        return;
                    }

                    // The Ajax request was successful, so let's update the output.
                    $(button).text(soliloquy_settings.activate).removeClass('soliloquy-install-addon').addClass('soliloquy-activate-addon');
                    $(button).attr('rel', response.plugin);
                    $(message).text(soliloquy_settings.inactive);
                    $(el).removeClass('soliloquy-addon-not-installed').addClass('soliloquy-addon-inactive');
                    $this.next().hide();
                },
                error: function(xhr, textStatus ,e) {
                    $this.next().hide();
                    return;
                }
            }
            $.ajax(opts);
        });

        // Function to clear any disabled buttons and extra text if the user needs to add creds but instead tries to install a different addon.
        function soliloquyAddonRefresh(element) {
            if ( $(element).attr('disabled') )
                $(element).removeAttr('disabled');

            if ( $(element).parent().parent().hasClass('soliloquy-addon-not-installed') )
                $(element).text(soliloquy_settings.install);
        }
    });
}(jQuery));