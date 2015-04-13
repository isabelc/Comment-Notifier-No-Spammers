<?php
/*
Plugin Name: Comment Notifier No Spammers
Plugin URI: https://github.com/isabelc/Comment-Notifier-No-Spammers
Description: Subscribe to comments and notify only approved comment authors, not spammers.
Version: 1.2
Author: Isabel Castillo
Author URI: http://isabelcastillo.com
License: GPL2
Text Domain: comment-notifier-no-spammers
Domain Path: languages

Copyright 2014 - 2015 Isabel Castillo

This file is part of Comment Notifier No Spammers.

Comment Notifier No Spammers is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Comment Notifier No Spammers is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Comment Notifier No Spammers. If not, see <http://www.gnu.org/licenses/>.
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
function cmnt_nospammers_comment_post( $comment_id, $status ) {

		$comment = get_comment($comment_id);
		$name = $comment->comment_author;
		$email = strtolower( trim( $comment->comment_author_email ) );
		$post_id = $comment->comment_post_ID;
	
	// Only subscribe if comment is approved; skip those in moderation.

	// if comment author subscribed, and if comment is automatically approved, subscribe author
	if ( ( $status === 1 ) && isset( $_POST['cnns_subscribe'] ) ) {
		cmnt_nospammers_subscribe( $post_id, $email, $name );
	}

	// If comment is approved automatically, notify subscribers
	if ( $status == 1 ) {
		cmnt_nospammers_thankyou( $comment_id );
		cmnt_nospammers_notify( $comment_id );
	}

	// If comment goes to moderation, and if comment author subscribed,
	// add comment meta key for pending subscription.
	if ( ( $status === 0 ) && isset( $_POST['cnns_subscribe'] ) ) {
		add_comment_meta( $comment_id, 'cnns_subscribe', true, true );
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
function cmnt_nospammers_wp_set_comment_status( $comment_id, $status ) {

	// get original comment info
	$comment = get_comment( $comment_id );
	$post_id = $comment->comment_post_ID;
	$email = strtolower( trim( $comment->comment_author_email ) );
	$name = $comment->comment_author;

	// When a comment is approved later, notify the subscribers, and subscribe this comment author
	if ( $status === 'approve' ) {
		cmnt_nospammers_thankyou( $comment_id );
		cmnt_nospammers_notify( $comment_id );
		cmnt_nospammers_subscribe_later( $post_id, $email, $name, $comment_id );
	}
}

/**
 * Send thank you message to first timers after their 1st comment is approved,
 * regardless of whether they subscribe.
 */
function cmnt_nospammers_thankyou( $comment_id ) {
	global $wpdb;
	$options = get_option( 'cmnt_nospammers' );
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
	$data->comment_link = $data->link . '#comment-' . $comment_id;
	$data->author = $comment->comment_author;
	$data->content = $comment->comment_content;

	$message = $message = cmnt_nospammers_replace($options['ty_message'], $data);

	// Fill the message subject with same for all data.
	$subject = $options['ty_subject'];
	$subject = str_replace('{title}', $post->post_title, $subject);
	$subject = str_replace('{author}', $comment->comment_author, $subject);

	cmnt_nospammers_mail($comment->comment_author_email, $subject, $message, isset($options['ty_html']));
}

/**
 * Add a subscribe checkbox after the form content.
 */
function cmnt_nospammers_comment_form() {
	$options = get_option('cmnt_nospammers');
	if (isset($options['checkbox'])) {
		echo '<p class="cnns-comment-subscription"><input type="checkbox" value="1" name="cnns_subscribe" id="cnns_subscribe"';
		if (isset($options['checked'])) {
			echo ' checked="checked"';
		}
		echo '/>&nbsp;<label id="cnns-label" for="cnns_subscribe">' . $options['label'] . '</label></p>';
	}
}
/** Replace placeholders in body message with subscriber data and post/comment
 * data.
 * @param <type> $message
 * @param <type> $data
 * @return <type>
 */
function cmnt_nospammers_replace($message, $data) {
	$options = get_option('cmnt_nospammers');
	$message = str_replace('{title}', $data->title, $message);
	$message = str_replace('{link}', $data->link, $message);
	$message = str_replace('{comment_link}', $data->comment_link, $message);
	$message = str_replace('{author}', $data->author, $message);
	$temp = strip_tags($data->content);
	$length = empty($options['length']) ? 155 : htmlspecialchars($options['length']);
	
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
function cmnt_nospammers_notify($comment_id)
{
	global $wpdb;

	//@set_time_limit(0);

	$options = get_option('cmnt_nospammers');
	$comment = get_comment($comment_id);

	if ($comment->comment_type == 'trackback' || $comment->comment_type == 'pingback')
	{
		return;
	}

	$post_id = $comment->comment_post_ID;
	if (empty($post_id)) {
		return;
	}
	$email = strtolower(trim($comment->comment_author_email));

	$subscriptions = $wpdb->get_results(
		$wpdb->prepare("select * from " . $wpdb->prefix . "comment_notifier where post_id=%d and email<>%s",
		$post_id, $email) );

	if (!$subscriptions)
	{
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
	$data->comment_link = $data->link . '#comment-' . $comment_id;

	$comment = get_comment($comment_id);
	$data->author = $comment->comment_author;
	$data->content = $comment->comment_content;

	$message = cmnt_nospammers_replace($options['message'], $data);

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
	foreach ($subscriptions as $subscription)
	{
		$idx++;
		$m = $message;
		$m = str_replace('{name}', $subscription->name, $m);
		$m = str_replace('{unsubscribe}', $url . 'cmnt_nospammers_id=' . $subscription->id . '&cmnt_nospammers_t=' . $subscription->token, $m);

		$s = $subject;
		$s = str_replace('{name}', $subscription->name, $s);

		if (cmnt_nospammers_mail($subscription->email, $s, $m)) $ok++;
	}
}

/**
 * Subscribe a user to a post.
 * 
 * @param int $post_id on which to subscribe
 * @param string $email user's email
 * @param string $name user's name
 */
function cmnt_nospammers_subscribe( $post_id, $email, $name ) {
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
function cmnt_nospammers_subscribe_later( $post_id, $email, $name, $comment_id ) {
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
		if ( get_comment_meta( $comment_id, 'cnns_subscribe', true ) ) {

			// The random token for unsubscription
			$token = md5(rand());
			$res = $wpdb->insert($wpdb->prefix ."comment_notifier", array(
				'post_id' => $post_id,
				'email' => $email,
				'name' => $name,
				'token' => $token ));

			delete_comment_meta( $comment_id, 'cnns_subscribe' );
		}
	}

}

function cmnt_nospammers_init() {
	$options = get_option('cmnt_nospammers');

	if (is_admin())
	{
		add_action('admin_menu', 'cmnt_nospammers_admin_menu');
	}

	add_action('comment_form', 'cmnt_nospammers_comment_form', 99);
	add_action('wp_set_comment_status', 'cmnt_nospammers_wp_set_comment_status', 10, 2);
	add_action('comment_post', 'cmnt_nospammers_comment_post', 10, 2);

	if (empty($_GET['cmnt_nospammers_id'])) return;

	$token = $_GET['cmnt_nospammers_t'];
	$id = $_GET['cmnt_nospammers_id'];


	cmnt_nospammers_unsubscribe($id, $token);
	
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
add_action('init', 'cmnt_nospammers_init');

/**
 * Removes a subscription.
 */
function cmnt_nospammers_unsubscribe($id, $token) {
	global $wpdb;

	$wpdb->query($wpdb->prepare("delete from " . $wpdb->prefix . "comment_notifier where id=%d and token=%s", $id, $token));

}

function cmnt_nospammers_mail(&$to, &$subject, &$message, $html=null) {
	$options = get_option('cmnt_nospammers');

	if ($html == null) $html = isset($options['html']);

	if ($html)
		$headers = "Content-type: text/html; charset=UTF-8\n";
	else
		$headers = "Content-type: text/plain; charset=UTF-8\n";

	$headers .= 'From: "' . $options['name'] . '" <' . $options['from'] . ">\n";

	return wp_mail($to, $subject, $message, $headers);
}
/** 
* Load plugin textdomain
*/
 function cmnt_nospammers_load_textdomain() {
	load_plugin_textdomain( 'cmnt_nospammers_options_page', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'cmnt_nospammers_load_textdomain' );

/**
* Remove spammers that were previously subscribed. This only runs on activation.
*/
function cmnt_nospammers_cleanup_prior() {
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
	update_option( 'cmnt_nospammers_cleanup', $count );

}
/** Upon activation, create table unless it exists from Comment Notifier plugin, in which case existing spammer emails will be removed from table. Also set up default settings.
*/
function cmnt_nospammers_activate() {
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
'<p>' . sprintf(__( 'Hi %s', 'comment-notifier-no-spammers' ), '{name}') . '</p>
<p>' . sprintf( __( '%s has just written a new comment on "%s". Here is an excerpt:', 'comment-notifier-no-spammers' ), '{author}', '{title}') . '</p>
<p>{content}</p>
<p>' . sprintf(__('To read more, <a href="%s">click here</a>', 'comment-notifier-no-spammers'), '{comment_link}') . '</p>

<p>' . __('Bye', 'comment-notifier-no-spammers') . '</p>

<p>' . sprintf(__('To unsubscribe this notification service, <a href="%s">click here</a>', 'comment-notifier-no-spammers'), '{unsubscribe}') . '</p>';
$default_options['label'] = __( 'Notify me when new comments are added.', 'comment-notifier-no-spammers');
$default_options['subject'] = sprintf(__( 'A new comment from %s on "%s"', 'comment-notifier-no-spammers'), '{author}', '{title}');
$default_options['thankyou'] = __( 'Your subscription has been removed. You\'ll be redirect to the home page within few seconds.', 'comment-notifier-no-spammers');
$default_options['name'] = get_option('blogname');
$default_options['from'] = get_option('admin_email');
$default_options['checkbox'] = '1';
$default_options['checked'] = '1';
$default_options['ty_html'] = '1';
$default_options['html'] = '1';

$default_options['ty_subject'] = __('Thank you for your first comment', 'comment-notifier-no-spammers');
$default_options['ty_message'] = 
'<p>' . sprintf(__('Hi %s,', 'comment-notifier-no-spammers'), '{author}') . '</p>

<p>' . __('I received and published your first comment on my blog on the article:', 'comment-notifier-no-spammers'). '</p>
<p><a href="{link}">{title}</a></p>
<p>' . __('Have a nice day!', 'comment-notifier-no-spammers') . '</p>';

	$options = get_option('cmnt_nospammers', array());
	$options = array_merge($default_options, $options);
	update_option('cmnt_nospammers', $options);
	cmnt_nospammers_cleanup_prior();
}
register_activation_hook( __FILE__, 'cmnt_nospammers_activate' );

/* Upon deactivation, delete the option that holds the number of deleted spammers from this activation
*/
function cmnt_nospammers_deactivate() {
	delete_option( 'cmnt_nospammers_cleanup' );
}
register_deactivation_hook( __FILE__, 'cmnt_nospammers_deactivate' );

include_once plugin_dir_path(__FILE__) . '/options.php';

function cmnt_nospammers_admin_menu()
{
	add_options_page(__('Comment Notifier No Spammers', 'comment-notifier-no-spammers'), __('Comment Notifier No Spammers', 'comment-notifier-no-spammers'), 'manage_options', 'comment-notifier-no-spammers', 'cmnt_nospammers_options_page');
}
function cmnt_nospammers_settings_link($links) {
	$url = get_admin_url().'options-general.php?page=comment-notifier-no-spammers';
	$settings_link = '<a href="'.$url.'">' . __('Settings', 'comment-notifier-no-spammers') . '</a>';
	array_unshift($links, $settings_link);
	return $links;
}

function cmnt_nospammers_after_setup_theme() {
	 add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cmnt_nospammers_settings_link');
}
add_action ('after_setup_theme', 'cmnt_nospammers_after_setup_theme');