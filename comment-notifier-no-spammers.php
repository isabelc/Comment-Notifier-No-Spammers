<?php
/*
Plugin Name: Lightweight Subscribe To Comments
Plugin URI: https://isabelcastillo.com/free-plugins/lightweight-subscribe-comments
Description: Easiest and most lightweight plugin to let visitors subscribe to comments and get email notifications.
Version: 1.5.7
Author: Isabel Castillo
Author URI: https://isabelcastillo.com
License: GPL2
Text Domain: comment-notifier-no-spammers
Domain Path: languages

Copyright 2014 - 2017 Isabel Castillo

This file is part of Lightweight Subscribe To Comments.

Lightweight Subscribe To Comments is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Lightweight Subscribe To Comments is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Lightweight Subscribe To Comments. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Subscribe comment author and notify subscribers
 * when comment posts with approved status.
 * If comment goes to moderation, add comment meta if user subscribed.
 *
 * Called when a comment is added to a post with its status:
 * '0' - in moderation,
 * '1' - approved,
 * 'spam' if it is spam.
 * @param int $comment_id the database id of the comment.
 * @param int|string $status Whether the comment is approved, 0, 1 or spam.
 */
function lstc_comment_post( $comment_id, $status ) {
	$comment = get_comment( $comment_id );
	$name = $comment->comment_author;
	$email = strtolower( trim( $comment->comment_author_email ) );
	$post_id = $comment->comment_post_ID;
	
	// Only subscribe if comment is approved; skip those in moderation.

	// If comment is approved automatically, notify subscribers
	if ( 1 === $status ) {
		lstc_thankyou( $comment_id );
		lstc_notify( $comment_id );

		// If comment author subscribed, subscribe author since the comment is automatically approved.
		if ( isset( $_POST['lstc_subscribe'] ) ) {
			lstc_subscribe( $post_id, $email, $name );
		}
	}

	// If comment goes to moderation, and if comment author subscribed,
	// add comment meta key for pending subscription.
	if ( ( 0 === $status ) && isset( $_POST['lstc_subscribe'] ) ) {
		add_comment_meta( $comment_id, 'lstc_subscribe', true, true );
	}
	
}

/**
 * Subscribe and notify after moderation.
 *
 * Subscribe user when their comment is finally approved
 * after being held in moderation. Notify other subscribers
 * of this comment.
 * Called when a comment is changed of status, as when approving
 * a comment that has been held in moderation.
 * 
 * @param int $comment_id the comment id
 * @param string $status New comment status, either 'hold', 'approve', 'spam', or 'trash'.
 */
function lstc_wp_set_comment_status( $comment_id, $status ) {
	// get original comment info
	$comment = get_comment( $comment_id );
	$post_id = $comment->comment_post_ID;
	$email = strtolower( trim( $comment->comment_author_email ) );
	$name = $comment->comment_author;

	// When a comment is approved later, notify the subscribers, and subscribe this comment author
	if ( 'approve' === $status ) {
		lstc_thankyou( $comment_id );
		lstc_notify( $comment_id );
		lstc_subscribe_later( $post_id, $email, $name, $comment_id );
	}
}

/**
 * Send thank you message to first timers after their 1st comment is approved,
 * regardless of whether they subscribe, if enabled.
 */
function lstc_thankyou( $comment_id ) {
	global $wpdb;
	$options = get_option( 'lstc' );
	if ( ! isset( $options['ty_enabled'] ) ) {
		return;
	}
	if ( empty( $options['ty_message'] ) ) {
		return;
	}

	$comment = get_comment( $comment_id ); 

	// is this the 1st comment?
	$query = $wpdb->prepare("select count(*) from " . $wpdb->comments . " where comment_approved='1' and lower(comment_author_email)=%s", strtolower($comment->comment_author_email));
	$count = $wpdb->get_var($query);
	if ($count != 1) {
		return;
	}
	$post = get_post($comment->comment_post_ID);
	if ( ! isset( $data ) ){
		$data = new stdClass();
	}
	$data->post_id = $comment->comment_post_ID;
	$data->title = $post->post_title;
	$data->link = get_permalink( $comment->comment_post_ID );
	$data->comment_link = get_comment_link( $comment_id );
	$data->author = $comment->comment_author;
	$data->content = $comment->comment_content;

	$message = lstc_replace( $options['ty_message'], $data );

	// Fill the message subject with same for all data.
	$subject = $options['ty_subject'];
	$subject = str_replace( '{title}', $post->post_title, $subject );
	$subject = str_replace( '{author}', $comment->comment_author, $subject );

	lstc_mail( $comment->comment_author_email, $subject, $message );
}

/**
 * Returns the HTML for the checkbox as a string, if the checkbox is enabled.
 */
function lstc_checkbox_html() {
	$html = '';
	$options = get_option( 'lstc' );
	if ( ! empty( $options['checkbox'] ) ) {
		$html .= '<p id="lstc-comment-subscription" class="cnns-comment-subscription"><input type="checkbox" value="1" name="lstc_subscribe" id="lstc_subscribe"';
		if ( ! empty( $options['checked'] ) ) {
			$html .= ' checked="checked"';
		}
		$html .= '/>&nbsp;<label id="cnns-label" class="lstc-label" for="lstc_subscribe">' . esc_html( $options['label'] ) . '</label></p>';
	}
	return $html;
}

/**
 * Add a subscribe checkbox below the comment form submit button.
 */
function lstc_comment_form() {
	echo lstc_checkbox_html();
}

/**
 * Add a subscribe checkbox above the submit button.
 */
function lstc_comment_form_submit_field( $submit_field ) {
	$checkbox = lstc_checkbox_html();
	return $checkbox . $submit_field;
}

/** Replace placeholders in body message with subscriber data and post/comment
 * data.
 * @param <type> $message
 * @param <type> $data
 * @return <type>
 */
function lstc_replace( $message, $data ) {
	$options = get_option('lstc');
	$message = str_replace('{title}', $data->title, $message);
	$message = str_replace('{link}', $data->link, $message);
	$message = str_replace('{comment_link}', $data->comment_link, $message);
	$message = str_replace('{author}', $data->author, $message);
	$temp = strip_tags($data->content);
	$length = empty( $options['length'] ) ? 155 : htmlspecialchars( $options['length'] );
	
	if ( ! is_numeric($length) ) {
		$length = 155;
	}
	
	if ( $length ) {
		if ( strlen($temp) > $length ) {
			$x = strpos($temp, ' ', $length);
			if ($x !== false) {
				$temp = substr($temp, 0, $x) . '...';
			}
		}
	}
	$message = str_replace('{content}', $temp, $message);
	return $message;
}

/**
 * Sends out the notification of a new comment for subscribers. This is the core function
 * of this plugin. The notification is not sent to the email address of the author
 * of the comment.
 */
function lstc_notify( $comment_id ) {
	global $wpdb;
	$options = get_option( 'lstc' );
	$comment = get_comment( $comment_id );

	if ( 'trackback' == $comment->comment_type || 'pingback' == $comment->comment_type ) {
		return;
	}

	$post_id = $comment->comment_post_ID;
	if ( empty( $post_id ) ) {
		return;
	}
	$email = strtolower( trim( $comment->comment_author_email ) );

	$subscriptions = $wpdb->get_results(
		$wpdb->prepare("select * from " . $wpdb->prefix . "comment_notifier where post_id=%d and email<>%s",
		$post_id, $email) );

	if ( ! $subscriptions ) {
		return;
	}


	// Fill the message body with same for all data.
	$post = get_post( $post_id );
	if ( empty( $post ) ) {
		 return;
	}
	
	$data = new stdClass();
	$data->post_id = $post_id;
	$data->title = $post->post_title;
	$data->link = get_permalink($post_id);
	$data->comment_link = get_comment_link( $comment_id );
	$comment = get_comment($comment_id);
	$data->author = $comment->comment_author;
	$data->content = $comment->comment_content;
	$message = lstc_replace($options['message'], $data);

	// Fill the message subject with same for all data.
	$subject = $options['subject'];
	$subject = str_replace('{title}', $post->post_title, $subject);
	$subject = str_replace('{author}', $comment->comment_author, $subject);

	$url = get_option('home') . '/?';

	if (!empty($options['copy'])) {
		$fake->token = 'fake';
		$fake->id = 0;
		$fake->email = $options['copy'];
		$fake->name = 'Test subscriber';
		$subscriptions[] = $fake;
	}

	$idx = 0;
	$ok = 0;
	foreach ( $subscriptions as $subscription ) {
		$idx++;
		$m = $message;
		$m = str_replace('{name}', $subscription->name, $m);
		$m = str_replace('{unsubscribe}', $url . 'lstc_id=' . $subscription->id . '&lstc_t=' . $subscription->token, $m);
		$s = $subject;
		$s = str_replace('{name}', $subscription->name, $s);
		if (lstc_mail($subscription->email, $s, $m)) $ok++;
	}
}

/**
 * Subscribe a user to a post.
 * 
 * @param int $post_id on which to subscribe
 * @param string $email user's email
 * @param string $name user's name
 */
function lstc_subscribe( $post_id, $email, $name ) {
	global $wpdb;

	// Check if user is already subscribed to this post
	$subscribed = $wpdb->get_var(
		$wpdb->prepare( "select count(*) from " . $wpdb->prefix . "comment_notifier where post_id=%d and email=%s",
		$post_id, $email ) );

	if ( $subscribed > 0 ) {
		return;
	}

	$token = md5( rand() );// The random token for unsubscription
	$res = $wpdb->insert( $wpdb->prefix ."comment_notifier", array(
		'post_id'	=> $post_id,
		'email'		=> $email,
		'name'		=> $name,
		'token'		=> $token ) );
}

/**
 * Subscribe a comment author to a post after his comment has
 * been held in moderation and is finally approved.
 * 
 * @param int $post_id on which comment was made
 * @param string $email comment author's email
 * @param string $name comment author's name
 * @param int $comment_id comment id
 */
function lstc_subscribe_later( $post_id, $email, $name, $comment_id ) {
	global $wpdb;

	// Check if user is already subscribed to this post
	$subscribed = $wpdb->get_var(
		$wpdb->prepare("select count(*) from " . $wpdb->prefix . "comment_notifier where post_id=%d and email=%s",
		$post_id, $email));

	if ($subscribed > 0) {
		 return;
	}

	// Did the comment author check the box to subscribe?
	if ( $comment_id ) {
		if ( get_comment_meta( $comment_id, 'lstc_subscribe', true ) ) {

			// The random token for unsubscription
			$token = md5(rand());
			$res = $wpdb->insert($wpdb->prefix ."comment_notifier", array(
				'post_id' => $post_id,
				'email' => $email,
				'name' => $name,
				'token' => $token ));

			delete_comment_meta( $comment_id, 'lstc_subscribe' );
		}
	}

}

function lstc_init() {
	$options = get_option('lstc');
	if ( is_admin() ) {
		add_action( 'admin_menu', 'lstc_admin_menu' );
	}
	// If theme_compat is enabled, use the old filter to add checkbox after the submit button
	// otherwise use our standard filter
	if ( empty( $options['theme_compat'] ) ) {
		add_filter( 'comment_form_submit_field', 'lstc_comment_form_submit_field', 9999 );
	} else {
		add_action( 'comment_form', 'lstc_comment_form', 99 );
	}

	add_action( 'wp_set_comment_status', 'lstc_wp_set_comment_status', 10, 2 );
	add_action( 'comment_post', 'lstc_comment_post', 10, 2 );

	if ( empty( $_GET['lstc_id'] ) ) {
		return;
	}

	$token = $_GET['lstc_t'];
	$id = $_GET['lstc_id'];

	lstc_unsubscribe( $id, $token );
	
	$unsubscribe_url = empty( $options['unsubscribe_url'] ) ? '' : esc_url_raw( $options['unsubscribe_url'] );

	if ( $unsubscribe_url ) {
		header( 'Location: ' . $unsubscribe_url );
	} else {
		$thankyou = empty( $options['thankyou'] ) ?
		__( 'Your subscription has been removed. You\'ll be redirect to the home page within few seconds.', 'comment-notifier-no-spammers') :
		htmlspecialchars( $options['thankyou'] );

		echo '<html><head>';
		echo '<meta http-equiv="refresh" content="3;url=' . esc_url( get_option( 'home' ) ) . '"/>';
		echo '</head><body>';
		echo $thankyou;
		echo '</body></html>';
	}

	flush();

	die();
}
add_action( 'init', 'lstc_init' );

/**
 * Removes a subscription.
 */
function lstc_unsubscribe($id, $token) {
	global $wpdb;

	$wpdb->query($wpdb->prepare("delete from " . $wpdb->prefix . "comment_notifier where id=%d and token=%s", $id, $token));

}
/**
 * Send an email
 */
function lstc_mail( $to, $subject, $message ) {
	$options = get_option( 'lstc' );
	$headers = "Content-type: text/html; charset=UTF-8\n";
	if ( ! empty( $options['name'] ) && ! empty( $options['from'] ) ) {
		$headers .= 'From: "' . $options['name'] . '" <' . $options['from'] . ">\n";
	}
	$message = wpautop( $message );
	return wp_mail( $to, $subject, $message, $headers );
}

/** 
* Load plugin textdomain
*/
 function lstc_load_textdomain() {
	load_plugin_textdomain( 'comment-notifier-no-spammers', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'lstc_load_textdomain' );

/**
 * Align the subscription checkbox by piggybacking on current theme's stylesheet.
 * @since 1.5
 */
function lstc_inline_style() {
	if ( ! is_singular() ) {
		return;
	}
	$options = get_option( 'lstc' );
	if ( isset( $options['disable_css'] ) ) {
		return;
	}
	global $wp_styles;
	$stylesheets = $wp_styles->queue;
	foreach( $stylesheets as $sheet ) {
		$sheetlen = strlen( $sheet );
		$testlen = 6;
		if ( $testlen > $sheetlen ) {
			return false;
		}
		if ( substr_compare( $sheet, '-style', $sheetlen - $testlen, $testlen ) === 0 ) {
			$handle = $sheet;
			break;
		}
	}

	if ( ! empty( $handle ) ) {

		$custom_css = "#lstc-comment-subscription label.lstc-label {
						display: inline-block;
						vertical-align: middle;
					}
					#lstc-comment-subscription {
						margin-top: 1em;
					}
					#lstc-comment-subscription input#lstc_subscribe {
						margin-right: 0.5em;
					}";
		wp_add_inline_style( $handle, $custom_css );
	}
}
add_action( 'wp_enqueue_scripts', 'lstc_inline_style', 999 );

/**
 * Check that an email is a valid email structure and if so, sanitize it.
 * @return string|bool A sanitized email if a valid email was passed, otherwise false.
 * @since 1.5.3
 */
function lstc_valid_email( $email ) {
	return is_email( $email ) ? sanitize_email( $email ) : false;
}

/**
 * Process subcriber data, then import subcribers into our table
 */
function lstc_process_import_subscribers( $subscriber_data ) {
	global $wpdb;
	
	foreach ( $subscriber_data as $key => $data ) {
		$valid = lstc_valid_email( $data->email );
		if ( $valid ) {
			$data->email = $valid;// sanitize emails to be inserted
		} else {
			unset( $subscriber_data[ $key ] );// remove invalid subscribers
			continue;
		}
		
		// Get comment author name, which is missing from STC and STCR postmeta
		$comment_author = $wpdb->get_var( $wpdb->prepare( 
			"SELECT comment_author FROM {$wpdb->prefix}comments WHERE comment_author_email = %s",
			$data->email
		) );
		// Add the name to the array of subscriber data.
		$subscriber_data[ $key ]->name =  empty( $comment_author ) ? __( 'Subscriber', 'comment-notifier-no-spammers ' ) : $comment_author;
	}
	// Insert subscribers into our table
	foreach ( $subscriber_data as $data ) {
		// Skip if something is missing
		if ( empty( $data->post_id ) || empty( $data->name ) || empty( $data->email ) ) {
			continue;
		}
	    $token = md5(rand());
		$wpdb->query( $wpdb->prepare(
			'INSERT IGNORE INTO ' . $wpdb->prefix . 'comment_notifier 
				( post_id, name, email, token )
				VALUES ( %d, %s, %s, %s )
			', 
			array(
				$data->post_id,
				$data->name,
				$data->email,
				$token
			) 
		) );
	}
}

/**
* Remove spammers that were previously subscribed with Comment Notifier plugin.
* This only runs on activation.
*/
function lstc_cleanup_prior() {
	global $wpdb;
	$comment_notifier_table = $wpdb->prefix . 'comment_notifier';

	// Empty the trash and spam
	$wpdb->delete( $wpdb->comments, array( 'comment_approved' => 'trash' ) );
	$wpdb->delete( $wpdb->comments, array( 'comment_approved' => 'spam' ) );

	// delete every email in the comment_notifier table that doesn’t have a corresponding comment.
	$count = $wpdb->query("DELETE FROM " . $comment_notifier_table . " WHERE email NOT IN ( SELECT comment_author_email FROM " . $wpdb->comments . " )");

	// Delete every email in the comment_notifier table isn't valid
	$lstc_subscribers = $wpdb->get_col( "SELECT email FROM " . $comment_notifier_table );
	foreach ( $lstc_subscribers as $email ) {
		if ( ! lstc_valid_email( $email ) ) {
			$wpdb->query( 
				$wpdb->prepare( "DELETE FROM " . $comment_notifier_table . " WHERE email = %s", $email )
			);			
		}
	}	
}
/**
 * Upon activation, setup the database table, default settings, and migrate
 * subscribers from other comment subscriber plugins.
 */
function lstc_activate() {
	global $wpdb;
	//Create table unless it exists from Comment Notifier plugin
	$sql = 'create table if not exists ' . $wpdb->prefix . 'comment_notifier (
		`id` int unsigned not null AUTO_INCREMENT,
		`post_id` int unsigned not null default 0,
		`name` varchar (100) not null default \'\',
		`email` varchar (100) not null default \'\',
		`token` varchar (50) not null default \'\',
		primary key (`id`),
		unique key `post_id_email` (`post_id`,`email`),
		key `token` (`token`)
		)';

	@$wpdb->query($sql);

$default_options['message'] = 
sprintf(__( 'Hi %s,', 'comment-notifier-no-spammers' ), '{name}') .
"\n\n" .
sprintf( __( '%s has just written a new comment on "%s". Here is an excerpt:', 'comment-notifier-no-spammers' ), '{author}', '{title}') .
"\n\n" .
 '{content}' .
 "\n\n" .
 sprintf(__('To read more, <a href="%s">click here</a>.', 'comment-notifier-no-spammers'), '{comment_link}') .
 "\n\n" .
 __('Bye', 'comment-notifier-no-spammers') .
 "\n\n" .
sprintf(__('To unsubscribe from this notification service, <a href="%s">click here</a>.', 'comment-notifier-no-spammers'), '{unsubscribe}');

$default_options['label'] = __( 'Notify me when new comments are added.', 'comment-notifier-no-spammers');
$default_options['subject'] = sprintf(__( 'A new comment from %s on "%s"', 'comment-notifier-no-spammers'), '{author}', '{title}');
$default_options['thankyou'] = __( 'Your subscription has been removed. You\'ll be redirect to the home page within few seconds.', 'comment-notifier-no-spammers');
$default_options['name'] = get_option('blogname');
$default_options['from'] = get_option('admin_email');
$default_options['checkbox'] = '1';
$default_options['checked'] = '1';
$default_options['ty_subject'] = __('Thank you for your first comment', 'comment-notifier-no-spammers');
$default_options['ty_message'] = 
sprintf(__('Hi %s,', 'comment-notifier-no-spammers'), '{author}') .
"\n\n" .
__('I received and published your first comment on my blog on the article:', 'comment-notifier-no-spammers') .
"\n\n" .
'<a href="{link}">{title}</a>' .
"\n\n" .
__('Have a nice day!', 'comment-notifier-no-spammers');

	$options = get_option( 'lstc' );
	if ( ! $options ) {
		$options =  array();// setting the get_option default to empty array did not work as expected.
	}
	$options = array_merge( $default_options, $options );
	update_option( 'lstc', $options );
	// Remove spammers that were previously subscribed by Comment Notifier plugin.
	lstc_cleanup_prior();
	// Import subscribers from Subscribe to Comments plugin, if any exist
	$stc_subscribers = $wpdb->get_results( "SELECT LCASE(meta_value) as email, post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sg_subscribe-to-comments'" );
	if ( $stc_subscribers ) {
		lstc_process_import_subscribers( $stc_subscribers );
	}
	// Import subscribers from Subscribe to Comments Reloaded, if any active subscribers exist
	$stcr_subscribers = $wpdb->get_results( "SELECT REPLACE(meta_key, '_stcr@_', '') AS email, post_id FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '\_stcr@\_%' AND meta_value LIKE '%|Y'" );
	if ( $stcr_subscribers ) {
		lstc_process_import_subscribers( $stcr_subscribers );
	}

}
register_activation_hook( __FILE__, 'lstc_activate' );

include_once plugin_dir_path(__FILE__) . 'options.php';

function lstc_admin_menu() {
	add_options_page(__('Lightweight Subscribe To Comments', 'comment-notifier-no-spammers'), __('Lightweight Subscribe To Comments', 'comment-notifier-no-spammers'), 'manage_options', 'lightweight-subscribe-comments', 'lstc_options_page');
}
/**
 * Sanitize settings before saving
 * @param array $options The array of options to be saved
 */
function lstc_sanitize_settings( $options ) {
	// integers
	$int_keys = array( 'checkbox',
		'checked',
		'ty_enabled',
		'disable_css',
		'delete_data',
		'theme_compat'
	);
	foreach ( $int_keys as $int_key ) {
		if ( isset( $options[ $int_key ] ) ) {
			$options[ $int_key ] = (int) $options[ $int_key ];
		}
	}
	// text
	$text_keys = array( 'label',
		'name',
		'subject',
		'unsubscribe_url',
		'ty_subject',
		'copy'
	);
	foreach ( $text_keys as $text_key ) {
		if ( isset( $options[ $text_key ] ) ) {
			$options[ $text_key ] = sanitize_text_field( $options[ $text_key ] );
		}
	}
	// some html
	$richtext_keys = array( 'message',
		'thankyou',
		'ty_message'
	);
	foreach ( $richtext_keys as $richtext_key ) {
		if ( isset( $options[ $richtext_key ] ) ) {
			$options[ $richtext_key ] = wp_kses_post( $options[ $richtext_key ] );
		}
	}
	// emails
	if ( isset( $options['from'] ) ) {
		$options['from'] = sanitize_email( $options['from'] );
	}

	if ( isset( $options['test'] ) ) {
		$options['test'] = sanitize_email( $options['test'] );
	}

	return $options;
}

/**
 * Migrate options to new handle 
 * @since 1.5.1
 * @todo remove this at some future point, and delete the option lstc_migrate_options_complete upon deactivation.
 */
function lstc_migrate_options() {
	// Run this update only once
	if ( get_option( 'lstc_migrate_options_complete' ) != 'completed' ) {
		$old_options = get_option( 'cmnt_nospammers' );
		if ( ! empty( $old_options ) ) {
			update_option( 'lstc', $old_options );
			delete_option( 'cmnt_nospammers' );
		}
		update_option( 'lstc_migrate_options_complete', 'completed' );
	}

	/* Remove invalid subscribers that were imported from other plugins.
	 * Run this cleanup only once
	 * @since 1.5.3 
	 * @todo remove this at some future point, and delete the option lstc_cleanup_emails_done upon deactivation.
	 */
	if ( get_option( 'lstc_cleanup_emails_done' ) != 'completed' ) {
		global $wpdb;
		$comment_notifier_table = $wpdb->prefix . 'comment_notifier';

		// Get email list
		$lstc_subscribers = $wpdb->get_col("SELECT email FROM " . $comment_notifier_table);
		// delete every email in the comment_notifier table isn't valid
		foreach ( $lstc_subscribers as $email ) {
			if ( ! lstc_valid_email( $email ) ) {
				$wpdb->query( 
					$wpdb->prepare( "DELETE FROM " . $comment_notifier_table . " WHERE email = %s", $email )
				);			
			}
		}
		update_option( 'lstc_cleanup_emails_done', 'completed' );
	}
}
add_action( 'init', 'lstc_migrate_options' );

/**
 * Convert our comment_notifier table to use utf8 instead of latin character set.
 * @since 1.5.7
 * @todo remove this at some future point, and delete the option lstc_update_table_utf8_complete upon deactivation.
 */
function lstc_update_table_utf8() {
	// Run this update only once
	if ( get_option( 'lstc_update_table_utf8_complete' ) != 'completed' ) {

		global $wpdb;
		$table = $wpdb->prefix . 'comment_notifier';

		// First, set our table to utf8
		$wpdb->query("ALTER TABLE $table CHARACTER SET utf8");

		// Then set the column in a 3-step process

		// First re-cast the data as latin1 
		$wpdb->query("ALTER TABLE $table change name name VARCHAR(100) CHARACTER SET latin1");
		// Then convert the VARCHAR column to its blob-type counterpart: VARBINARY. 
		$wpdb->query("ALTER TABLE $table change name name VARBINARY(100)");
		// Then convert it back to VARCHAR, but with our desired character set of utf8;. 
		$wpdb->query("ALTER TABLE $table change name name VARCHAR(100) CHARACTER SET utf8");

		update_option( 'lstc_update_table_utf8_complete', 'completed' );
	}
}
add_action( 'init', 'lstc_update_table_utf8' );
