=== Comment Notifier No Spammers ===
Contributors: isabel104
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=me%40isabelcastillo%2ecom
Tags: comments, comments reply, comments subscribe, notifications, notify, notifier, subscribe, subscriptions
Requires at least: 3.7
Tested up to: 4.1.1
Stable tag: 1.2
License: GNU Version 2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Subscribe to comments and notify only approved comment authors, not spammers.

== Description ==

* Let visitors subscribe to comments.
* Do NOT subscribe spammers.
* Removes spammers from your "Comment Notifier" list.
* Seamlessly and easily switch from "Comment Notifier".

This is an alternative to [Comment Notifier](http://wordpress.org/plugins/comment-notifier/) by Stefano Lissa. His is a great plugin, and this maintains all the same features and options. 

The main difference is that this new plugin will not subscribe spammers while their comment is pending moderation. Only approved comments will be subscribed. Comments authors in moderation will only be subscribed if, and when, you approve their comment. 

In particular, this new plugin fixes [this problem](http://wordpress.org/support/topic/remove-subscribed-emails-whose-comments-are-trashedmarked-as-spam).

**Easily Switch From Comment Notifier**
If you were using Comment Notifier plugin, then it is easy to switch to this plugin. Simply install this one, and deactivate that one. Your existing approved comments, and legit subscribers, will not be lost. If you want to make sure you don't get any new spammers added to your comment_notifier list, then you should deactivate the old plugin immediately after activating this new one. The reason is because the new plugin will only clean up after the old plugin once, upon activation. If you leave the old plugin activated, you'll continue to get spammers added to your list. *(I'm considering adding a button in the options page to "clean up" again.)*

**Bonus Clean Up**
Upon activation, this plugin will clean up your "comment_notifier" list (database table) by removing all spammer emails. (This only applies to you if you were using the original Comment Notifier plugin.) It will also empty your Comments "Trash" and "Spam". This clean up is only done once, upon plugin activation.

**Languages**
This plugin is translation-ready and includes a `.pot` file to make it easy to translate.

**Why Did I Modify The Original Plugin?**

The problem is that the original Comment Notifier plugin subscribes the email address as soon as the submitted comment goes into moderation. This means that spammers get added to the list of subscribers, immediately. Later, when you delete the spam comments, the spammer's email remains in the list of subscribers. 

You could have hundreds, even thousands, of spammer email addresses in that list. Then, when you approve a legit comment, your server sends out emails to all of the post subscribers (including spammers), which can cause server overload, among other problems.

This plugin, **Comment Notifier No Spammers**, fixes that by ignoring comments in moderation until they are approved by you. This means less load on your server.

Fork it on [GitHub](https://github.com/isabelc/Comment-Notifier-No-Spammers).

== Installation ==

**Step 1 - Activate The Plugin**

In your WordPress dashboard -> Plugins -> Add New, search for "Comment Notifier No Spammers". Click "Install Now", then click "Activate Plugin".

**Step 2 - Set a "Notification Sender Email"**

Go to "Settings --> Comment Notifier No Spammers". You must enter a "**Notification Sender Email**".

Some web hosts require that this be an email at your actual website. For example, if your website is `www.mysite.com`, then your sender email must be `something@mysite.com`, in which the first part "something" can be anything as long as it ends with **"@mysite.com"**. Bluehost is one host that requires this.

However, GoDaddy hosting does not require this. GoDaddy will allow you to use any email address as the sender email (for example, a Gmail or Yahoo email). If you are unsure whether your web host allows this, then stick with an email at your own site to ensure that your notification emails will be sent.

In addition to the requirement described above, some web hosts require that the email address be an actual existing address. For example, if your site is `www.mysite.com`, and you want to use "`wordpress@mysite.com`" as your "Notification Sender Email", but that email does not actually exist, then your notification emails will not send. In this case, you would have to create the email address "`wordpress@mysite.com`" on your hosting server. Please note that "Forwarding Email Addresses" will not work for this since they are not actual email boxes, but rather they are just aliases.

**Step 3 (Optional) Customize the Settings**

Optionally, you can customize the rest of the settings on this page.

**Step 4 - For those that were using Comment Notifier Plugin**

If you were using Comment Notifier plugin, then deactivate it right away to avoid having it add new spammers to your comment_notifier list.

Only once, upon activation, this plugin will clean up your "comment_notifier" list (database table) by removing all spammer emails that were subscribed by the Comment Notifier Plugin. It will also empty your Comments "Trash" and "Spam". This is done automatically upon activation.

Your existing approved comments, and legit subscribers, will not be lost.

== Frequently Asked Questions ==

= Why are Test Emails not sending? =

For test emails to work, you must enter an email address in the "Email address where to send test emails:" option, under "Advanced Settings". 
**Tip:** Do not use the sender address for this; some mail servers do not accept "from" and "to" set to the same value.

== Changelog ==
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

= 1.1 =
Fix - Every approved comment author was being subscribed even if they did not subcribe.

= 1.0 =
Removed several PHP errors from the options page.

= 0.2 =
Fixed fatal error which occurred when trying to delete a subscription from the admin.
