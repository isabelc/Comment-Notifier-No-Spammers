Comment Notifier No Spammers
============================


Subscribe to comments and notify only approved comment authors, not spammers.


* Let visitors subscribe to comments.

* Do NOT subscribe spammers.* Removes spammers from your "Comment Notifier" list.

* Seamlessly and easily switch from "Comment Notifier".

This is an alternative to [Comment Notifier](http://wordpress.org/plugins/comment-notifier/) by Stefano Lissa. His is a great plugin, and this maintains all the same features and options. 

The main difference is that this new plugin will not subscribe spammers while their comment is pending moderation. Only approved comments will be subscribed. Comments authors in moderation will only be subscribed if, and when, you approve their comment. 



In particular, this new plugin fixes [this problem](http://wordpress.org/support/topic/remove-subscribed-emails-whose-comments-are-trashedmarked-as-spam).


Easily Switch From Comment Notifier
-----------------------------------

If you were using Comment Notifier plugin, then it is easy to switch to this plugin. Simply install this one, and deactivate that one. Your existing approved comments, and legit subscribers, will not be lost. If you want to make sure you don't get any new spammers added to your comment_notifier list, then you should deactivate the old plugin immediately after activating this new one. The reason is because the new plugin will only clean up after the old plugin once, upon activation. If you leave the old plugin activated, you'll continue to get spammers added to your list. *(I'm considering adding a button in the options page to "clean up" again.)*

Bonus Clean Up
--------------


Upon activation, this plugin will clean up your "comment_notifier" list (database table) by removing all spammer emails. (This only applies to you if you were using the original Comment Notifier plugin.) It will also empty your Comments "Trash" and "Spam". This clean up is only done once, upon plugin activation.


Languages
---------
This plugin is translation-ready and includes a `.pot` file to make it easy to translate.


Why Did I Modify The Original Plugin?
--------------------------------------



The problem is that the original Comment Notifier plugin subscribes the email address as soon as the submitted comment goes into moderation. This means that spammers get added to the list of subscribers, immediately. Later, when you delete the spam comments, the spammer's email remains in the list of subscribers. 



You could have hundreds, even thousands, of spammer email addresses in that list. Then, when you approve a legit comment, your server sends out emails to all of the post subscribers (including spammers), which can cause server overload, among other problems.This plugin, **Comment Notifier No Spammers**, fixes that by ignoring comments in moderation until they are approved by you. This means less load on your server.

Installation
============

1. Put the plugin folder into `/wp-content/plugins/`

2. In your WordPress admin, go to "Plugins" and activate the plugin.
3. Optional: go to "Settings -> Comment Notifier No Spammers" and configure the settings. If you were using Comment Notifier plugin, please note that the "Checkbox default status" setting is reset to "unchecked". If want the "notify me" checkbox to be checked by default, you will have to check that setting again. The rest of your settings should be intact from before.

4. If you were using Comment Notifier plugin, then deactivate it right away to avoid having it add new spammers to your comment_notifier list.
