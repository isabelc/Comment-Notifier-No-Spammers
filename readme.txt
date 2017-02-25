=== Lightweight Subscribe To Comments ===
Contributors: isabel104
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=me%40isabelcastillo%2ecom
Tags: comment, comments, subscribe to comments, follow comments, notifications, subscription
Requires at least: 3.7
Tested up to: 4.8-alpha-40080
Stable tag: 1.5.2
License: GNU Version 2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easiest and most lightweight plugin to let visitors subscribe to comments and get email notifications.

== Description ==

Very simply, this plugin adds a subscription checkbox to your WordPress comments form to let your visitors subscribe to comments. They will then be notified by email when others comment on the same post. Works automatically upon activation, no settings required.

This plugin focuses on a lightweight footprint. It simply just works upon activation. All settings are optional. For fastest pagespeed, no stylesheets or scripts are added.

You can unsubscribe people from the settings page, if you wish.

**Optional Settings** include: 


* You can customize the notification emails.
* Set a custom "Unsubscribe Page" URL or unsubscribe message.
* Send a "Thank You" message for first time commentators.
* Send a copy of EACH notification to emails of your choice.

= Easily Switch From Other Comments Subscription Plugins =

Your subscribers will be imported for an easy switch from the following listed plugins. This is done automatically upon activation. Your comment subscribers will be migrated from these plugins:



- "Subscribe To Comments" plugin
- "Subscribe To Comments Reloaded" plugin
- "Comment Notifier" plugin


= Special Condition For "Comment Notifier" Plugin Users: =

**"Lightweight Subscribe to Comments" fixes a major problem with the "Comment Notifier" plugin:**

In particular, "Lightweight Subscribe to Comments" fixes [this problem](https://wordpress.org/support/topic/remove-subscribed-emails-whose-comments-are-trashedmarked-as-spam).

Lightweight Subscribe to Comments will not subscribe spammers while their comment is pending moderation. Only approved comments will be subscribed. Comments authors in moderation will only be subscribed if, and when, you approve their comment. 

The problem is that "Comment Notifier" plugin subscribes the email address as soon as the submitted comment goes into moderation. This means that spammers get added to the list of subscribers, immediately. Later, when you delete the spam comments, the spammer's email remains in the list of subscribers. 

You could have hundreds, even thousands, of spammer email addresses in that list. Then, when you approve a legit comment, your server sends out emails to all of the post subscribers (including spammers), which can cause server overload, among other problems.

This plugin fixes that by ignoring comments in moderation until they are approved by you. This means less load on your server.

**Bonus Clean Up For "Comment Notifier" Plugin Users:**

This plugin removes spammers from your "Comment Notifier" list.

Upon activation, this plugin will clean up your "comment_notifier" list (database table) by removing all spammer emails (emails of people that do NOT have an approved comment). (This only applies to you if you were using the "Comment Notifier" plugin.) It will also empty your Comments "Trash" and "Spam". This clean up is only done once, upon plugin activation.

= Languages =

This plugin is translation-ready and includes a `.pot` file to make it easy to translate.

== Installation ==

= Step 1: Install The Plugin =

In your WordPress dashboard -> Plugins -> Add New, search for "Lightweight Subscribe To Comments". Click "Install Now", then click "Activate Plugin".

Upon activation, the plugin automatically works. There will be a checkbox underneath your comment form so that visitors can subscribe to comments as they make a comment.

To troubleshoot a problem, see the Troubleshooting section on the [full documentation page](https://isabelcastillo.com/free-plugins/lightweight-subscribe-comments).

= (Optional) Step 2: Maybe Add CSS Styles =

Most themes do not need this step. If your theme uses common WP standards to load its stylesheet, your checkbox style will be just fine. However, if you notice that your checkbox is not aligned properly underneath your comment reply form, then see [the documentation](https://isabelcastillo.com/free-plugins/lightweight-subscribe-comments#jl-install) for a quick way to add the proper CSS style to align your checkbox.


= (Optional) Step 3: Customize The Settings =

If you want to customize any settings, then go to "Settings --> Lightweight Subscribe To Comments". See the full documentation (link above).

= (Optional) Step 4 - Only For Those That Were Using "Comment Notifier" Plugin =

If you were using "Comment Notifier" plugin, then deactivate it right away to avoid having it add new spammers to your comment_notifier list. Only once, upon activation, this plugin will clean up your "comment_notifier" list (database table) by removing all spammer emails that were subscribed by the "Comment Notifier" plugin. It will also empty your Comments "Trash" and "Spam". This is done automatically upon activation. Your existing approved comments, and legit subscribers, will remain intact.

== Frequently Asked Questions ==

= Why are Test Emails not sending? =

For test emails to work, you must enter an email address in the "Email address where to send test emails:" option, under "Advanced Settings". 
**Tip:** Do not use the sender address for this; some mail servers do not accept "from" and "to" set to the same value.

== Screenshots ==

1. This is the subscription checkbox that is added beneath your comment form.

== Changelog ==

= 1.5.2 =
* New - Easily switch from the "Subscribe to Comments" plugin by Jaquith. Also, improved importing for subscribers from the "Subscribe to Comments Reloaded" plugin.
* New - Replace tags with sample text in preview. This means you get a better preview of the message body when you click "preview".
* Fix - Fixed a bug that would delete the plugin settings (not subscribers, just settings) upon plugin deactivation. This is fixed, and currently, settings are kept intact. Future updates will bring an option to erase all data.
* Tweak - Added the {comment_link} to the list of tags available for editing the message body.

= 1.5.1 =
* New - Updated the `.pot` translation file.
* Fix - Options were not migrated in version 1.5. This properly migrates the options to the new handle. If you are on version 1.5, and you don't update to 1.5.1, then you would have to go into the settings page and click "Save" to save the new options. Or simply update to version 1.5.1 which takes care of that.

= 1.5 =
* New - Simplified settings page. All settings are optional. The plugin now works upon activation without the need to configure any settings. HTML p tags have been removed from the message textareas to make it easier to customize the messages. There is now only 1 Save button on the settings page, rather than 3.
* New - Changed plugin name to Lightweight Subscribe To Comments.
* New - Upon activation, will migrate subscribers from the "Subscribe to Comments Reloaded" plugin for an easy switch from that plugin to this one.
* Fix - Update values on the settings page after saving and sending test email. Previously, the email to send test emails to was not refreshing on the page even though it was being saved.
* Tweak - textdomain in now loaded on init which helps ensure that translations work.

= 1.4 =
* Fix - Removed annoying admin notice. It will only show up once upon plugin activation, only if spam/pending commenter emails were removed from the database.
* Fix - Fixed incorrect textdomain. The correct textdomain is comment-notifier-no-spammers.

= 1.3 =
* Fix - If using paged comments, the comments link may have been wrong if it was a comment that appeared on a page other than the first comments page.
* Tweak - Change h2 heading tag on Settings page to h1.

= 1.2 =
* New - Removed inline CSS for subscription checkbox. Added new CSS selectors for easier styling: p.cnns-comment-subscription and label#cnns-label.

= 1.1 =
* New - If you were manually adding the subscription checkbox to your template files, you must update it since the input checkbox code has changed. Specifically, the name and id of the checkbox input has changed from 'subscribe' to 'cnns_subscribe'. See the settings page for the whole snippet.
* Fix - Every approved comment author was being subscribed, whether they checked the box to subscribe, or not.
* Tweak - Improved some option descriptions.
* Maintenance - Removed a PHP notice.
* Maintenance - Updated .pot translation file.

= 1.0 =
* Fix - Removed several PHP errors from the options page.

= 0.2 =
* Fix - Removed fatal error which occurred when trying to delete a subscription from the admin.
* Maintenance - Removed a couple of PHP notices.
* Maintenance - updated .pot translation file.

= 0.1.9 =
* Added plugin URI.

= 0.1.8 =
* First release.

== Upgrade Notice ==
= 1.5.2 =
Fixed a bug that would delete the plugin settings (not subscriber data) upon plugin deactivation.

= 1.5.1 =
Fix: Options were not migrated in v1.5. Please update.

= 1.5 =
HTML p tags have been removed from the message textareas to make it easier to customize the emails.

= 1.1 =
Fix - Every approved comment author was being subscribed even if they did not subscribe.

= 1.0 =
Removed several PHP errors from the options page.

= 0.2 =
Fixed fatal error which occurred when trying to delete a subscription from the admin.
