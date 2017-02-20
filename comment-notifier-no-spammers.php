<?php
/*
Plugin Name: Lightweight Subscribe To Comments
Plugin URI: https://isabelcastillo.com/free-plugins/lightweight-subscribe-comments
Description: Easiest and most lightweight plugin to let visitors subscribe to comments and get email notifications.
Version: 1.5.1.alpha-1
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
 * @param int $comment_id the database id of the comment
 * @param mixed $status, 0, 1 or spam
 */
function lstc_comment_post( $comment_id, $status ) {

		$comment = get_comment($comment_id);
		$name = $comment->comment_author;
		$email = strtolower( trim( $comment->comment_author_email ) );
		$post_id = $comment->comment_post_ID;
	
	// Only subscribe if comment is approved; skip those in moderation.

	// if comment author subscribed, and if comment is automatically approved, subscribe author
	if ( ( $status === 1 ) && isset( $_POST['lstc_subscribe'] ) ) {
		lstc_subscribe( $post_id, $email, $name );
	}

	// If comment is approved automatically, notify subscribers
	if ( $status == 1 ) {
		lstc_thankyou( $comment_id );
		lstc_notify( $comment_id );
	}

	// If comment goes to moderation, and if comment author subscribed,
	// add comment meta key for pending subscription.
	if ( ( $status === 0 ) && isset( $_POST['lstc_subscribe'] ) ) {
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
 * @param string $status either 'hold', 'approve', 'spam', or 'delete'.
 */
function lstc_wp_set_comment_status( $comment_id, $status ) {

	// get original comment info
	$comment = get_comment( $comment_id );
	$post_id = $comment->comment_post_ID;
	$email = strtolower( trim( $comment->comment_author_email ) );
	$name = $comment->comment_author;

	// When a comment is approved later, notify the subscribers, and subscribe this comment author
	if ( $status === 'approve' ) {
		lstc_thankyou( $comment_id );
		lstc_notify( $comment_id );
		lstc_subscribe_later( $post_id, $email, $name, $comment_id );
	}
}

/**
 * Send thank you message to first timers after their 1st comment is approved,
 * regardless of whether they subscribe.
 */
function lstc_thankyou( $comment_id ) {
	global $wpdb;
	$options = get_option( 'lstc' );
	if (!isset($options['ty_enabled'])){
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
 * Add a subscribe checkbox after the form content.
 */
function lstc_comment_form() {
	$options = get_option( 'lstc' );
	if ( isset( $options['checkbox'] ) ) {
		echo '<p id="lstc-comment-subscription" class="cnns-comment-subscription"><input type="checkbox" value="1" name="lstc_subscribe" id="lstc_subscribe"';
		if ( isset( $options['checked'] ) ) {
			echo ' checked="checked"';
		}
		echo '/>&nbsp;<label id="cnns-label" class="lstc-label" for="lstc_subscribe">' . $options['label'] . '</label></p>';
	}
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

	//@set_time_limit(0);

	$options = get_option('lstc');
	$comment = get_comment($comment_id);

	if ($comment->comment_type == 'trackback' || $comment->comment_type == 'pingback')
	{
		return;
	}

	$post_id = $comment->comment_post_ID;
	if ( empty( $post_id ) ) {
		return;
	}
	$email = strtolower(trim($comment->comment_author_email));

	$subscriptions = $wpdb->get_results(
		$wpdb->prepare("select * from " . $wpdb->prefix . "comment_notifier where post_id=%d and email<>%s",
		$post_id, $email) );

	if ( ! $subscriptions ) {
		return;
	}


	// Fill the message body with same for all data.
	$post = get_post($post_id);
	if (empty($post)) {
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
		$wpdb->prepare("select count(*) from " . $wpdb->prefix . "comment_notifier where post_id=%d and email=%s",
		$post_id, $email));

	if ($subscribed > 0) {
		 return;
	}

	// The random token for unsubscription
	$token = md5(rand());
	$res = $wpdb->insert($wpdb->prefix ."comment_notifier", array(
		'post_id' => $post_id,
		'email' => $email,
		'name' => $name,
		'token' => $token ));
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

	if (is_admin()) {
		add_action( 'admin_menu', 'lstc_admin_menu' );
	}

	add_action('comment_form', 'lstc_comment_form', 99);
	add_action('wp_set_comment_status', 'lstc_wp_set_comment_status', 10, 2);
	add_action('comment_post', 'lstc_comment_post', 10, 2);

	if (empty($_GET['lstc_id'])) return;

	$token = $_GET['lstc_t'];
	$id = $_GET['lstc_id'];


	lstc_unsubscribe($id, $token);
	
	$unsubscribe_url = empty($options['unsubscribe_url']) ? '' : $options['unsubscribe_url'];

	if ( $unsubscribe_url ) {
		header('Location: ' . $unsubscribe_url);
	} else {
		echo '<html><head>';
		echo '<meta http-equiv="refresh" content="3;url=' . get_option('home') . '"/>';
		echo '</head><body>';
		echo $options['thankyou'];
		echo '</body></html>';
	}

	flush();

	die();
}
add_action('init', 'lstc_init');

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
 * Align the subscription checkbox without adding a stylesheet, piggyback on current theme's stylesheet.
 * @since 1.5
 */
function lstc_inline_style() {
	if ( ! is_singular() ) {
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
 * Migrate subscribers from Subscribe to Comments Reloaded. This only runs on activation.
 */
function lstc_migrate_subscribers_from_stcr() {
	global $wpdb;
	$stcr_table = $wpdb->prefix . 'subscribe_reloaded_subscribers';

	// Check if the Subscribe to Comments Reloaded table exists

	if ( $wpdb->get_var( "SHOW TABLES LIKE '$stcr_table'" ) != $stcr_table ) {
		return;
	}
	
	$join = 'SELECT t1.* FROM ' . $wpdb->prefix . 'comments t1 WHERE EXISTS (SELECT subscriber_email FROM ' . $stcr_table . ' t2 WHERE t2.subscriber_email = t1.comment_author_email)';

	$result = $wpdb->get_results( $join );

	if ( empty( $result ) ) {
		return;
	}

	foreach ( $result as $row ) {

	    $token = md5(rand());

		$wpdb->query( $wpdb->prepare(
			'INSERT IGNORE INTO ' . $wpdb->prefix . 'comment_notifier 
				( post_id, name, email, token )
				VALUES ( %d, %s, %s, %s )
			', 
			array(
				$row->comment_post_ID, 
				$row->comment_author,
				$row->comment_author_email,
				$token
			) 
		) );

	}
	  
}

/**
* Remove spammers that were previously subscribed. This only runs on activation.
*/
function lstc_cleanup_prior() {
	global $wpdb;
	// get table name
	$pre = $wpdb->base_prefix;
	if ( is_multisite() ) { 
		global $blog_id;
		$comment_notifier_table = $pre . get_current_blog_id() . '_comment_notifier';
	} else {
		// not Multisite
		$comment_notifier_table = $pre . 'comment_notifier';
	}

	// Empty the trash and spam
	$wpdb->delete( $wpdb->comments, array( 'comment_approved' => 'trash' ) );
	$wpdb->delete( $wpdb->comments, array( 'comment_approved' => 'spam' ) );

	// delete every email in the comment_notifier table that doesn’t have a corresponding (pending or approved) comment.
	$count = $wpdb->query("DELETE FROM " . $comment_notifier_table . " WHERE email NOT IN ( SELECT comment_author_email FROM " . $wpdb->comments . " )");
}
/** Upon activation, create table unless it exists from Comment Notifier plugin, in which case existing spammer emails will be removed from table. Also set up default settings.
*/
function lstc_activate() {
	global $wpdb;
	//   $wpdb->query("RENAME TABLE " . $wpdb->prefix . "subscriptions TO " . $wpdb->prefix . "comment_notifier");

	// SQL to create the table
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
sprintf(__( 'Hi %s', 'comment-notifier-no-spammers' ), '{name}') .
"\n\n" .
sprintf( __( '%s has just written a new comment on "%s". Here is an excerpt:', 'comment-notifier-no-spammers' ), '{author}', '{title}') .
"\n\n" .
 '{content}' .
 "\n\n" .
 sprintf(__('To read more, <a href="%s">click here</a>.', 'comment-notifier-no-spammers'), '{comment_link}') .
 "\n\n" .
 __('Bye', 'comment-notifier-no-spammers') .
 "\n\n" .
sprintf(__('To unsubscribe this notification service, <a href="%s">click here</a>.', 'comment-notifier-no-spammers'), '{unsubscribe}');

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

	$options = get_option( 'lstc', array() );
	$options = array_merge( $default_options, $options );
	update_option( 'lstc', $options );
	// Remove spammers that were previously subscribed by Comment Notifier plugin.
	lstc_cleanup_prior();
	// Migrate subscribers from Subscribe to Comments Reloaded
 	lstc_migrate_subscribers_from_stcr();

}
register_activation_hook( __FILE__, 'lstc_activate' );

include_once plugin_dir_path(__FILE__) . '/options.php';

function lstc_admin_menu() {
	add_options_page(__('Lightweight Subscribe To Comments', 'comment-notifier-no-spammers'), __('Lightweight Subscribe To Comments', 'comment-notifier-no-spammers'), 'manage_options', 'lightweight-subscribe-comments', 'lstc_options_page');
}

/**
 * Migrate options to new handle 
 * @since 1.5.1
 * @todo remove this function in version 2.0, and del lstc_migrate_options_complete on uninstall
 */
function lstc_migrate_options() {
	// Run this update only once
	if ( get_option( 'lstc_migrate_options_complete' ) != 'completed' ) {
		$old_options = get_option( 'cmnt_nospammers' );
		update_option( 'lstc', $old_options );
		update_option( 'lstc_migrate_options_complete', 'completed' );
	}
}
add_action( 'init', 'lstc_migrate_options' );
