=== Zopim Live Chat ===
Contributors: zendesk_official
Tags: chat, chat online, contact plugin, contact us, customer support, free chat, chat software, IM chat, live chat, live chat inc, live chat services, live chat software, live chatting, live help, live support, live web chat, livechat, live help, live support, olark, online chat, online support, php live chat, snapengage, support software, website chat, wordpress chat, wordpress live chat, wordpress live chat plugin, Zopim, zendesk, Zopim live chat, banckle, clickdesk, click desk
Requires at least: 3.1
Tested up to: 4.1.1
Stable tag: 1.3.8

Zopim lets you monitor and chat with visitors surfing your store in real-time. Impress them personally and ease them into their purchase.

== Description ==

Did you know that 83% of consumers need some kind of customer support when making an online purchase? And 45% of customers abandon an online transaction if their questions or concerns are not addressed quickly.

Businesses that interact with potential customers online are better placed to build a connection and increase their revenues. The Zopim live chat app will let you answer your customer’s questions in real time and ease them into a purchase.

[youtube https://www.youtube.com/watch?v=tSRSn9hJU1c]

With Zopim Live Chat, visitors to your website will be able to chat directly with you through the widget and you can manage multiple conversations through the online Dashboard.

**Key Features**

* **Mobile Optimized:** Your customers can chat with you from any device using our mobile optimized chat widget
* **Proactive Chat:** Rather than chatting with every single person yourself, you can increase the chances of high value engagement (and more sales) by using Triggers to automatically reach every visitor
* **Advanced Analytics:** Our analytics dashboard lets you monitor visitor flow, usage patterns, and lets you jump in whenever a customer might need help

**What makes Zopim the best choice for live chat?**

* We’re the most popular live chat provider in the world - loved by over 150,000 businesses
* A simple and highly customizable chat widget to complement your website and taste
* User friendly dashboard lets you monitor visitor activity and manage chats
* 24 hour live chat support from our trained experts on any weekday (visit Zopim.com)
* Available in over 40 languages

**Some Geeky Facts**

* Work across major browsers (Internet Explorer 6+, Firefox, Google Chrome, Opera, Safari).
* Average uptime is 99.8%.
* HTML5 dashboard
* iPhone, Android, and BlackBerry apps
* Integrates seamlessly with UserVoice, Salesforce, Highrise, Batchbook, Zendesk, vTiger and many more.

Should you need any assistance, feel free to chat with our customer advocates on www.zopim.com or email us at support@zopim.com

What are you waiting for? Download Zopim Live Chat plugin now and <a href="https://account.zopim.com/?aref=MjUxMjY4:1TeORR:9SP1e-iPTuAVXROJA6UU5seC8x4&visit_id=6ffe00ec3cfc11e2b5ab22000a1db8fa&utm_source=wpdirectory&utm_medium=link&utm_campaign=wp%2Bsignup#signup">sign up here</a> for a free account!

**See languages available, lovingly translated by Zopim users (in alphabetical order)**

* Arabic | Bulgarian | Chinese | Croatian | Czech | Danish | Dutch; Flemish | Estonian | Faroese | Finnish | French | Georgian | German | Greek | Hebrew | Hungarian | Icelandic | Indonesian | Italian | Japanese | Korean | Kurdish | Latvian | Lithuanian | Macedonian | Malay | Norwegian Bokmal | Persian | Polish | Portuguese | Romanian | Russian | Serbian | Slovak | Slovenian | Spanish; Castilian | Swedish | Thai | Turkish | Ukranian | Urdu | Vietnamese

== Changelog ==
= 1.3.8 =
* Fix throwing of wordpress errors
* Fix PHP notice ('Notice: Undefined variable: error') on the plugin dashboard
* Remove unused globals
* Standardize function names
* Use _() and _e() for translations
* Add english language file
* Added icon assets

= 1.3.7 =
* Add documentation for releasing new versions of the plugin

= 1.3.6 =
* Fix 'Cannot modify header information' PHP error by registering and enequeing script
* Include js file

= 1.3.5 =
* Update supported version to 4.1.1
* Include Plugin's version number to footer (if Account is linked up)
* Always use SSL to link account
* Improved UI on login page to reduce confusion with SSL option and Password field
* Update Signup Link

= 1.3.4 =
* Fix menu icon image

= 1.3.3 = 
* Fix bug where account deactivates linking but widget still shows up

= 1.3.2 = 
* Refactored css loading to accommodate older PHP versions
* Included css file

= 1.3.1 =
* Added admin_print_styles to custom css
* Changed button css name to a non-generic name

= 1.3.0 =
* Removed iframe Capability from plugin
* Removed Account Configuration, Customize and Dashboard sub-menu pages
* Added Launch Dashboard link to open new browser tab to access Dashboard
* Minor Code Fixes

= 1.2.9 =
* Revert back to iframe Customize and Dashboard instead of opening new window
* In PHP 5.3, ereg is deprecated, updated to use preg_match

= 1.2.8 =
* Due to breaking changes in many consumer IM clients, we will no longer be supporting IM Chat Bots (AIM, Google Talk, Skype, Yahoo Messenger or MSN)
* Customize and Dashboard will now open in a new window instead of iframe

= 1.2.7 =
* Plugin will now embed v2 Widget
* Improved Login Response
* Classic Theme Editor removed to use Dashboard Widget Customization 
Click on Appearance to make changes to your widget
* Old Javascript API from v1 Widget may not work with v2 Widget
Visit http://api.zopim.com/files/meshim/widget/controllers/LiveChatAPI-js.html for more details

= 1.2.6 =
* Addresses XSS vulnerabilities concerns by removing ZeroClipboard

= 1.2.5 =
* Enhancement to Theme Editor - now autoloads blog's url automatically

= 1.2.4 =
* Adds an optional widget settings box
* Widget settings saved from old wordpress plugin will be migrated to the optional settings box
* Visitor info uses new wp api and now enabled by default
* Required Wordpress version bumped to 3.1

= 1.2.3 =
* Replaces old customize widget page with new theme editor
* Remove customizewidget.php

= 1.2.2 =
* adds User Capability levels, allowing non-admins to use the plugin admin interface
* Admins can use add the following code
	$role = get_role( 'editor' ); $role->add_cap( 'access_zopim' );
to their themes, or use the Members plugin (http://wordpress.org/extend/plugins/members/)
to give roles the 'access_zopim' capability.

= 1.2.1 =
* Uses wordpress http api for better linkup support with multiple transports
* Reduce name collisions in functions
* Adds compatibility to premiumpress theme

= 1.2.0 =
* Signup process is now linked to zopim.com as per Wordpress guidelines.

= 1.1.3 =
* Update Zopim embed script
* Add option to override dashboard settings for chat bubble text

= 1.1.2 =
* Maintenance update: Compatibility update for Wordpress 3.3.1

= 1.1.1 =
* Maintenance update: Fix invalid plugin header (zopim.php) for new installation

= 1.1.0 =
* Maintenance update: Greeting messages are now saved properly.
* Add more language options in customization page.
* Using new Zopim async embed script: improve page's load time and do not block page's rendering anymore

= 1.0.7 =
* Maintenance update: Remove phased out "middle left", "middle right" positions
* Add options to let user decides hiding of chat bubble, no longer force show/hide
* Enable option for not using plugins' greeting messages

= 1.0.6 =
* Maintenance update: Class' redeclaration conflict fix.

= 1.0.5 =
* Maintenance update: fix minor bug on widget customization page - online msg input

= 1.0.4 =
* Robustness update: Make sure widget won't appear more than once.

= 1.0.3 =
* Maintenance update: New line bugfixes.

= 1.0.2 =
* Maintenance update: More curl robustness enhancements.

= 1.0.1 =
* Maintenance update: More robust connectivity, CURL errors caught
* Note: PHP Curl is required (and has always been).

= 1.0 =
* Stability update: Official Plugin Launched

= 0.6.1 =
* Update: Launched with improved signup process and minor cosmetic fixes.

= 0.6.0 =
* UI fix: Improved account management page.

= 0.5.0 =

* Feature: Push surveys and questions to the visitor.
* Bugfix: In-plugin dashboard will be hidden in full screen mode.

= 0.4.0 =

* Feature: Provide customer service through your favourite IM client (MSN, GTalk, Yahoo, AIM)
* Feature: You can access the dashboard in full screen!

= 0.3.0 =

* Feature: Connect to Zopim servers using 256-bit industry standard SSL for increased security!
* Feature: You can experiment with customizing the widget even without an account.
* Feature: Change the automatic messages displayed on the widget when it first loads, and depending on your online status.
* Feature: Customize the language of the widget.
* Bugfix: Positioning customization now works properly.

= 0.2.0 =

* Feature: Live Visitor Analytics (page your users are on, webpaths, length of stay, repeat visits and much more!)
* Feature: Create an account directly in the plugin.
* Feature: Rank Visitors by priority of importance.

= 0.1.0 =

* Feature: Add the Zopim live chat widget to your site!
* Feature: Customize look and feel of the widget, including themes and color!
* Feature: Use wordpress user information to populate visitor data in the plugin.

== Screenshots ==

1. Chat window on your website - active chat
2. Account Configuration - Link Up
3. Account Configuration - Linked Up with Launch Dashboard
4. Dashboard View - Open new browser tab
5. Widget Customization from Dashboard 

== Frequently Asked Questions ==

= Do I have to install any software on my server to get this working? =

Not at all! Zopim is a hosted livechat service. Simply configure the plugin and you're done!

= Which web browsers work best with this plugin =

Though designed to work on most browsers, Zopim works best in the following environment: IE 6 or later (PC), Firefox 2 or later (Mac, PC, or Linux), Safari 2 or later (Mac), Google Chrome (PC, Mac).

= I managed to install the plugin but cannot link up to Zopim. Why? =
The plugin tries to link up with zopim by connecting using a few approaches including PHP Curl. If the outgoing connections are blocked (eg. by a firewall), please request your server administrator.

Alternatively, you can also manually include the script in the footer file ("wp-content/themes/default/footer.php" in your wordpress installation).

= Is it free to use? =

The plugin comes with a free plan specially tailored for Wordpress users. Power users can purchase upgrade options anytime.

= How can I chat with more visitors at the same time? =

You can easily do so by upgrading to a better plan. To find out more about the plans and features we offer, please visit https://www.zopim.com/pricing

= Its just not working for me! HELP! =

Dont worry!! We are happy to assist! Just come on down to our site at http://www.zopim.com or leave an email for us at support@zopim.com and we will help you with installation.

== Usage ==

After enabling the plug in, head on to the widget customization page to change settings and integrate its look and feel to match your site. When done, enable it by visiting the account configuration page and completing the instant signup process.

== Installation ==

*Server Requirements:* PHP4 or PHP5.

*Wordpress versions:* Wordpress 2.7 and up.

Step-by-step Guide:

* Install plugin from WordPress directory and activate it.
* Under Zopim Chat section, click on Account Setup to link up your Zopim account.
* Customize the chat widget to your preference.
* Finally, make full use of our intuitive Dashboard to manage your chat widget.
