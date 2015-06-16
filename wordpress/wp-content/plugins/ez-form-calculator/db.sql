CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_debug` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `msg` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `__PREFIX__ezfcf_elements`;
CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_elements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` tinytext NOT NULL,
  `type` varchar(50) NOT NULL,
  `data` text NOT NULL,
  `icon` varchar(50) NOT NULL,
  `category` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT COLLATE="utf8_general_ci";

INSERT INTO `__PREFIX__ezfcf_elements` (`id`, `name`, `description`, `type`, `data`, `icon`, `category`) VALUES
  (1, 'Input', 'Basic input field with no restrictions', 'input', '{\r\n  "name": "Input",\r\n  "label": "Text",\r\n  "required": 0,\r\n  "value": "",\r\n  "value_external": "",\r\n  "custom_regex": "",\r\n  "custom_error_message": "",\r\n  "placeholder": "",\r\n  "class": "",\r\n  "hidden": 0,\r\n  "columns": 6\r\n}', 'fa-pencil-square-o', 'basic'),
  (2, 'Email', 'Email input field', 'email', '{\r\n "name": "Email",\r\n  "label": "Email",\r\n  "required": 0,\r\n  "use_address": 1,\r\n  "double_check": 0,\r\n  "value": "",\r\n  "value_external": "",\r\n  "placeholder": "",\r\n  "class": "",\r\n  "hidden": 0,\r\n  "columns": 6\r\n}', 'fa-envelope-o', 'basic'),
  (3, 'Textfield', 'Large text field', 'textfield', '{\r\n  "name": "Textfield",\r\n  "label": "Textfield",\r\n "required": 0,\r\n  "value": "",\r\n  "value_external": "",\r\n  "placeholder": "",\r\n  "class": "",\r\n  "hidden": 0,\r\n  "columns": 6\r\n}', 'fa-align-justify', 'basic'),
  (7, 'Numbers', 'Numbers only', 'numbers', '{\r\n  "name": "Numbers",\r\n  "label": "Numbers",\r\n  "required": 0,\r\n  "calculate_enabled": 1,\r\n  "is_currency": 1,\r\n  "factor": "",\r\n  "value": "",\r\n  "value_external": "",\r\n  "min": "0",\r\n  "max": "100",\r\n  "slider": 0,\r\n  "steps_slider": 1,\r\n  "spinner": 0,\r\n  "steps_spinner": 1,\r\n  "calculate": [{"operator":"","target":0,"value":""}],\r\n  "overwrite_price": 0,\r\n  "calculate_before": 0,\r\n  "conditional":[{"action":"","target":0,"operator":"","value":""}],\r\n  "discount":[{"range_min":"","range_max":"","operator":"","discount_value":""}],\r\n  "placeholder": "",\r\n  "class": "",\r\n  "hidden": 0,\r\n  "columns": 6\r\n}', 'fa-html5', 'calc');

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_forms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT COLLATE="utf8_general_ci";

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_forms_elements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `f_id` int(10) unsigned NOT NULL,
  `e_id` int(10) unsigned NOT NULL,
  `data` text NOT NULL,
  `position` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `f_id` (`f_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT COLLATE="utf8_general_ci";

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_forms_options` (
  `f_id` int(10) unsigned NOT NULL,
  `o_id` int(10) unsigned NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`f_id`,`o_id`)
) ENGINE=InnoDB DEFAULT COLLATE="utf8_general_ci";

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `value` text NOT NULL,
  `description` text NOT NULL,
  `description_long` text NOT NULL,
  `type` text NOT NULL,
  `cat` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT COLLATE="utf8_general_ci";

INSERT IGNORE INTO `__PREFIX__ezfcf_options` (`id`, `name`, `value`, `description`, `description_long`, `type`, `cat`) VALUES
  (1, 'email_recipient', '', 'Email recipient', 'Notifications will be sent to this email. Leave blank for no notifications.', '', 'Email'),
  (2, 'success_text', 'Thank you for your submission!', 'Submission message', 'Frontend message after successful submission.', '', 'Form'),
  (3, 'spam_time', '60', 'Spam protection in seconds', 'Every x seconds, a user (identified by IP address) can add an entry. Default: 60.', '', 'Form'),
  (4, 'show_required_char', '1', 'Show required char', '', 'yesno', 'Layout'),
  (5, 'currency', '$', 'Currency', '', '', 'Layout'),
  (6, 'price_label', 'Price', 'Price label', 'Calculated field label (default: Price)', '', 'Layout'),
  (7, 'show_element_price', '0', 'Show element prices', '', 'yesno', 'Layout'),
  (8, 'submission_enabled', '1', 'Submission enabled', '', 'yesno', 'Form'),
  (9, 'show_price_position', '1', 'Total price position', 'Price can be displayed above or below the form (or both) as well as fixed (scrolls with window)', 'select,Hidden|Below|Above|Below and above|Fixed left|Fixed right', 'Layout'),
  (10, 'email_admin_sender', '', 'Sender name', 'Sender name in emails. Use this syntax: Sendername &lt;sender@mail.com&gt;', '', 'Email'),
  (11, 'email_subject', 'Your submission', 'Email subject', '', '', 'Email'),
  (12, 'email_text', 'Thank you for your submission, we will contact you soon!\r\n\r\n{{result}}', 'Email text', 'Email text sent to the user. Use {{result}} for submission details. Use {{Elementname}} for single element values (where Elementname is the internal name of the element). Use {{files}} for attached files (you should not send these to the customer for security reasons)', 'editor', 'Email'),
  (13, 'email_admin_subject', 'New submission', 'Admin email subject', '', '', 'Email'),
  (14, 'email_admin_text', 'You have received a new submission:\r\n\r\n{{result}}', 'Admin email text', 'Email text sent to the admin. Use {{result}} for submission details. Use {{Elementname}} for single element values (where Elementname is the internal name of the element). Use {{files}} for attached files (you should not send these to the customer for security reasons)', 'editor', 'Email'),
  (15, 'submit_text', 'Submit', 'Submit text', 'Text in submit buttons', '', 'Layout'),
  (16, 'submit_text_woo', 'Add to cart', 'Submit text WooCommerce', 'Text used for WooCommerce submissions', '', 'Layout'),
  (17, 'submit_button_class', '', 'Submit button CSS class', '', '', 'Layout'),
  (18, 'email_show_total_price', '1', 'Show total price in email', 'Whether the total price of a submission should be shown or not. (Disable this option when you don\'t have a calculation form)', 'yesno', 'Email'),
  (19, 'theme', '1', 'Form theme', '', 'themes', 'Layout'),
  (20, 'currency_position', '0', 'Currency position', '', 'select,Before|After', 'Layout'),
  (21, 'datepicker_format', 'mm/dd/yy', 'Datepicker format', 'See <a href="http://jqueryui.com/datepicker/#date-formats" target="_blank">jqueryui.com</a> for date formats.', '', 'Layout'),
  (22, 'pp_enabled', '0', 'Force PayPal payment', 'Enabling this option will force the user to use PayPal. If you want to let the user choose how to pay, disable this option and add the Payment element (do not change the paypal value).', 'yesno', 'PayPal'),
  (23, 'pp_submittext', 'Check out with PayPal', 'Submit text PayPal', 'Text used for PayPal checkouts', '', 'PayPal'),
  (24, 'email_subject_pp', 'Your submission', 'Email Paypal subject', '', '', 'Email'),
  (25, 'email_text_pp', 'Thank you for your submission,\r\n\r\nwe have received your payment via PayPal.', 'Email Paypal text', 'Email text sent to the user when paid with PayPal.', 'editor', 'Email'),
  (26, 'pp_paid_text', 'We have received your payment, thank you!', 'PayPal payment success text', 'This text will be displayed when the user has successfully paid and returns to the site.', '', 'PayPal'),
  (27, 'redirect_url', '', 'Redirect URL', 'Redirect users to this URL upon form submission. Note: URL must start with http://', '', 'Form'),
  (28, 'min_submit_value', '0', 'Minimum submission value', '', '', 'Form'),
  (29, 'min_submit_value_text', 'Minimum submission value is %s', 'Minimum submission value text', 'This text will be displayed when the user\'s total value is less than the minimum value.', '', 'Form'),
  (30, 'mailchimp_add', '1', 'Enable MailChimp', 'Enable MailChimp integration', 'yesno', 'Email'),
  (31, 'mailchimp_list', '', 'Mailchimp list', 'Email addresses will be added to this list upon form submission.', 'mailchimp_list', 'Email'),
  (32, 'hide_all_forms', '0', 'Hide all forms on submission', 'If this option is set to "yes", all forms on the relevant page will be hidden upon submission (useful for product comparisons).', 'yesno', 'Form'),
  (33, 'timepicker_format', 'H:i', 'See <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net</a> for time formats', '', '', 'Layout'),
  (34, 'price_format', '', 'Price format', 'If left blank, the global price format will be used. See <a href="http://numeraljs.com/" target="_blank">numeraljs.com</a> for syntax documentation', '', 'Form');


CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_submissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `f_id` int(10) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  `content` mediumtext NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(50) NOT NULL,
  `ref_id` VARCHAR(16) NOT NULL,
  `total` DOUBLE NOT NULL,
  `payment_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `transaction_id` VARCHAR(50) NOT NULL,
  `token` VARCHAR(20) NOT NULL,
  `user_mail` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `f_id` (`f_id`)
) ENGINE=InnoDB DEFAULT COLLATE="utf8_general_ci";

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_templates` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `data` text NOT NULL,
  `options` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT COLLATE="utf8_general_ci";

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_files` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `f_id` INT(10) UNSIGNED NOT NULL,
  `ref_id` VARCHAR(16) NOT NULL,
  `url` VARCHAR(2048) NOT NULL,
  `file` VARCHAR(2048) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT COLLATE="utf8_general_ci";

CREATE TABLE IF NOT EXISTS `__PREFIX__ezfcf_themes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

INSERT IGNORE INTO `__PREFIX__ezfcf_themes` (`id`, `name`, `description`, `data`) VALUES
  (1, 'default', 'Default theme', '.ezfc-form label {\r\n display: block;\r\n margin-bottom: 0.15em;\r\n  text-align: left;\r\n}\r\n\r\n.ezfc-element {\r\n padding-top: 0em;\r\n padding-bottom: 0.75em;\r\n}\r\n.ezfc-element-input,\r\n.ezfc-element-numbers {\r\n display: inline-block;\r\n  padding: 0.2em 0;\r\n width: 100%;\r\n}\r\n.ezfc-element-select { padding: 0.3em; }\r\n.ezfc-element-submit { padding: 0.5em; }\r\n.ezfc-element-textarea { width: 100%; }\r\n\r\ninput.ezfc-element-radio-input, input.ezfc-element-checkbox-input { margin: 0 5px 6px 0; }\r\ninput.ezfc-element-fileupload { display: inline-block; }\r\n\r\n.ezfc-price-wrapper {\r\n display: inline-block;\r\n  font-size: 1.5em;\r\n}\r\n\r\n.ezfc-success-text { display: none; }\r\n.ezfc-required-char { color: #f00; }\r\n.ezfc-element-price { font-style: italic; }\r\n\r\np.ezfc-fileupload-message { margin: 6px 0 0 150px; }\r\n.ezfc-upload-button {\r\n margin-left: 150px;\r\n padding: 10px;\r\n  background: #efefef;\r\n  color: #111;\r\n}'),
  (2, 'default_alternative', 'Alternative default theme', '.ezfc-element {\r\n  padding-top: 0em;\r\n padding-bottom: 0.75em;\r\n}\r\n\r\n.ezfc-form label {\r\n  display: inline-block;\r\n  padding: 0 1em 0.15em 0;\r\n  text-align: right;\r\n  vertical-align: top;\r\n  width: 10em;\r\n}\r\n\r\n.ezfc-element-input,\r\n.ezfc-element-numbers {\r\n  display: inline-block;\r\n  padding: 0.2em 0;\r\n}\r\n.ezfc-element-select { padding: 0.3em; }\r\n.ezfc-element-submit { padding: 0.5em; }\r\n\r\n.ezfc-element-radio-container,\r\n.ezfc-element-checkbox-container,\r\n.ezfc-element-textarea {\r\n display: inline-block;\r\n}\r\n\r\n.ezfc-element-textarea { width: 30em; }\r\n\r\ninput.ezfc-element-radio-input,\r\ninput.ezfc-element-checkbox-input {\r\n  margin: 0 5px 6px 0;\r\n}\r\ninput.ezfc-element-fileupload {\r\n  display: inline-block;\r\n}\r\n.ezfc-upload-button {\r\n  margin-left: 150px;\r\n padding: 10px;\r\n  background: #efefef;\r\n  color: #111;\r\n}\r\n\r\n.ezfc-price-wrapper {\r\n  display: inline-block;\r\n  font-size: 1.5em;\r\n}\r\n\r\n.ezfc-success-text {\r\n  display: none;\r\n}\r\n\r\n.ezfc-required-char {\r\n  color: #f00;\r\n}\r\n\r\n.ezfc-element-price {\r\n  font-style: italic;\r\n}\r\n\r\np.ezfc-fileupload-message {\r\n margin: 6px 0 0 150px;\r\n}');