<?php
function lstc_options_page() {
	$options = get_option( 'lstc' );
	global $wpdb;
	$test = empty( $options['test'] ) ? '' : htmlspecialchars( $options['test'] );

	// save the options
	if ( ! empty( $_POST['_wpnonce'] ) ) {
		if ( wp_verify_nonce($_POST['_wpnonce'], 'update-lstc-options' ) ) {
			$options = stripslashes_deep( $_POST['options'] );
			$sane_options = lstc_sanitize_settings( $options );
			update_option( 'lstc', $sane_options );
			
			// Maybe send a test message, if requested
			if ( isset( $_POST['savethankyou'] ) ) {
				if ( ! empty( $_POST['options']['test'] ) ) {
					$test = sanitize_email( $_POST['options']['test'] );
				}
				$ty_message = empty( $options['ty_message'] ) ? '' : $options['ty_message'];
				$lstc_data = new stdClass();
				$lstc_data->author = __('Author', 'comment-notifier-no-spammers');
				$lstc_data->link = get_option('home');
				$lstc_data->comment_link = get_option('home');
				$lstc_data->title = __('The post title', 'comment-notifier-no-spammers');
				$lstc_data->content = __('This is a long comment. Be a yardstick of quality. Some people are not used to an environment where excellence is expected.', 'comment-notifier-no-spammers');
				$message = lstc_replace( $ty_message, $lstc_data );
				$subject = $options['ty_subject'];
				$subject = str_replace('{title}', $lstc_data->title, $subject);
				$subject = str_replace('{author}', $lstc_data->author, $subject);
				lstc_mail( $test, $subject, $message );
			}
		}
	}

	// Grab new values after "save and send test email"
	$options = get_option( 'lstc' );
	$unsubscribe_url = empty( $options['unsubscribe_url'] ) ? '' : htmlspecialchars( $options['unsubscribe_url'] );
	$length = empty( $options['length'] ) ? '' : htmlspecialchars( $options['length'] );
	$test = empty( $options['test'] ) ? '' : htmlspecialchars( $options['test'] );
	$copy = empty( $options['copy'] ) ? '' : htmlspecialchars( $options['copy'] );
	$label = empty( $options['label'] ) ? '' : htmlspecialchars( $options['label'] );
	$ty_message = empty( $options['ty_message'] ) ? '' : htmlspecialchars( $options['ty_message'] );
	$ty_subject = empty( $options['ty_subject'] ) ? '' : htmlspecialchars( $options['ty_subject'] );
	$thankyou = empty( $options['thankyou'] ) ? '' : htmlspecialchars( $options['thankyou'] );

	// Removes a single email for all subscriptions
	if ( isset( $_POST['remove_email'] ) ) {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'remove_email' ) )
			die( __( 'Security violated', 'comment-notifier-no-spammers' ) );
		$email = strtolower( sanitize_email( $_POST['email'] ) );
		$wpdb->query( $wpdb->prepare( "delete from " . $wpdb->prefix . "comment_notifier where email=%s", $email ) );
	}

	if (isset($_POST['remove'])) {
		if (!wp_verify_nonce($_POST['_wpnonce'], 'remove'))
			die(__('Security violated', 'comment-notifier-no-spammers'));
		$query = "delete from " . $wpdb->prefix . "comment_notifier where id in (" . implode(',', $_POST['s']) . ")";
		$wpdb->query($query);
	}
	?>

<script type="text/javascript">
// Replace all tags, not just the first instance of a tag
function lstcReplaceTags( tag, replacement, text ) {
	var intIndexOfMatch = text.indexOf( tag );
	while (intIndexOfMatch != -1) {
	    text = text.replace( tag, replacement );
	    intIndexOfMatch = text.indexOf( tag );
	}
	return text;
}
function lstcPreview() {
	var s = document.getElementById("subject").value;
	var m = document.getElementById("message").value;
	// Replace tags in Subject for preview
	s = lstcReplaceTags( '{title}', 'Sample Title', s );
	s = lstcReplaceTags( '{name}', 'You', s );
	s = lstcReplaceTags( '{author}', 'Bob', s );
	// Replace tags in Message Body for preview
	m = lstcReplaceTags( '{title}', 'Sample Title', m );
	m = lstcReplaceTags( '{name}', 'You', m );
	m = lstcReplaceTags( '{author}', 'Bob' , m );
	m = lstcReplaceTags( "{content}", "I totally agree with your opinion about him, he's really...", m );
	m = lstcReplaceTags( '{link}', '#', m );
	m = lstcReplaceTags( '{comment_link}', '#', m );
	m = lstcReplaceTags( '{unsubscribe}', '#', m );
	m = m.replace(/\n/g, "<br />");
	var h = window.open("", "lstc","status=0,toolbar=0,scrollbars=1,height=400,width=550");
	var d = h.document;
	d.write('<html><head><title>Email preview</title>');
	d.write('</head><body>');
	d.write('<table width="100%" border="1" cellspacing="0" cellpadding="5">');
	d.write('<tr><td align="right"><b>Subject</b></td><td>' + s + '</td></tr>');
	d.write('<tr><td align="right"><b>From</b></td><td>' + document.getElementById("from_name").value + ' &lt;' + document.getElementById("from_email").value + '&gt;</td></tr>');
	d.write('<tr><td align="right"><b>To</b></td><td>User name &lt;user@email&gt;</td></tr>');
	d.write('<tr><td align="left" colspan="2">' + m + '</td></tr>');
	d.write('</table>');
	d.write('</body></html>');
	d.close();
	return false;
}
</script>
<div class="wrap">
	<h1><?php _e( 'Lightweight Subscribe To Comments', 'comment-notifier-no-spammers' ); ?></h1>
	<form action="" method="post">
		<?php wp_nonce_field('remove_email') ?>
		<h3><?php _e('Email Management', 'comment-notifier-no-spammers'); ?></h3>
		<table class="form-table">
			<tr>
				<th></th>
				<td><?php _e('Remove this email: ', 'comment-notifier-no-spammers'); ?><input type="text" name="email" size="30"/>
					<input type="submit" name="remove_email" class="button-secondary" value="<?php _e('Remove', 'comment-notifier-no-spammers'); ?>"/>
				</td>
			</tr>
		</table>
	</form>
	<hr />
	<form action="" method="post">
		<h3><?php _e('Subscription Checkbox Configuration', 'comment-notifier-no-spammers'); ?></h3>
		<table class="form-table">
			<tr>
				<th><?php _e('Enable The Checkbox', 'comment-notifier-no-spammers'); ?></th>
				<td>
					<input type="checkbox" name="options[checkbox]" value="1" <?php echo empty( $options['checkbox'] ) ? '' : 'checked'; ?> />
					<?php _e('Check this to add the "Notify me" subscription checkbox to the comment form.', 'comment-notifier-no-spammers'); ?>
				</td>
			</tr>

			<tr>
				<th><?php _e('Checkbox Label', 'comment-notifier-no-spammers'); ?></th>
				<td>
					<input name="options[label]" type="text" size="50"
					 value="<?php echo $label; ?>"/>
					<br /><?php _e('Label to be displayed near the subscription checkbox', 'comment-notifier-no-spammers'); ?>
				</td>
			</tr>
			<tr>
				<th><?php _e('Checkbox Default Status', 'comment-notifier-no-spammers'); ?></th>
				<td>
					<input type="checkbox" name="options[checked]" value="1" <?php echo isset( $options['checked'] ) ? 'checked' : ''; ?> />
					<?php _e('Check here if you want the "Notify me" subscription checkbox to be checked by default', 'comment-notifier-no-spammers'); ?>
				</td>
			</tr>
		</table>
		<hr />
		<h3><?php _e('Notification Email Settings', 'comment-notifier-no-spammers'); ?></h3>
		<p><?php _e('Here you can configure the message which is sent to subscribers to notify them that a new comment was posted.', 'comment-notifier-no-spammers'); ?></p>

		<table class="form-table">
			<tr>
				<th><?php _e('From Name', 'comment-notifier-no-spammers'); ?></th>
				<td>
					<input name="options[name]" id="from_name" type="text" size="50" value="<?php echo htmlspecialchars($options['name']) ?>"/>
				</td>
			</tr>

			<tr>
				<th><?php _e('From Email', 'comment-notifier-no-spammers'); ?></th>
				<td>
					<input name="options[from]" id="from_email" type="text" size="50" value="<?php echo htmlspecialchars($options['from']) ?>"/>
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Subject', 'comment-notifier-no-spammers' ); ?></th>
				<td>
					<input name="options[subject]" id="subject" type="text" size="70" value="<?php echo htmlspecialchars($options['subject']) ?>"/>
					<br />
					<?php printf(__( 'Tags: %4$s %1$s - the post title %4$s %2$s - the subscriber name %4$s %3$s - the commenter name', 'comment-notifier-no-spammers'), '{title}', '{name}', '{author}', '<br />' ); ?>
				</td>
			</tr>

			<tr>
				<th><?php _e('Message Body', 'comment-notifier-no-spammers'); ?></th>
				<td>
					(<a href="javascript:void(lstcPreview());"><?php _e('preview', 'comment-notifier-no-spammers'); ?></a>)
					<br />
					<textarea name="options[message]" id="message" wrap="off" rows="10" style="width: 100%"><?php echo htmlspecialchars( $options['message'] ) ?></textarea>
					<br />
					<?php printf( __( 'Tags: %8$s %1$s - the subscriber name %8$s %2$s - the commenter name %8$s %3$s - the post title %8$s %4$s - the comment text (eventually truncated) %8$s %5$s - link to the comment %8$s %6$s - link to the post/page %8$s %7$s - the unsubscribe link' ), '{name}', '{author}', '{title}', '{content}', '{comment_link}', '{link}', '{unsubscribe}', '<br />' ); ?><br /><br />
				</td>
			</tr>
			<tr>
				<th><?php _e('Comment Excerpt Length', 'comment-notifier-no-spammers'); ?></th>
				<td>
					<input name="options[length]" 
					type="text" size="5" value="<?php echo $length; ?>"/> <?php _e(' characters', 'comment-notifier-no-spammers'); ?>
					<br />
					<?php _e('The length of the comment excerpt to be inserted in the email notification. If blank, the default is 155 characters.', 'comment-notifier-no-spammers'); ?>
				</td>
			</tr>
		</table>
		<hr />
		<h3><?php _e('Unsubscribe Settings', 'comment-notifier-no-spammers'); ?></h3>
		<p>
			<?php _e('Here you can configure what to show to unsubscribing users. You may set an "Unsubscribe page URL" to send the user to a specific page, or configure a specific message.', 'comment-notifier-no-spammers'); ?>
		</p>

		<table class="form-table">
			<tr>
				<td>
					<label><?php _e('Unsubscribe Page URL', 'comment-notifier-no-spammers'); ?></label><br />
					<input name="options[unsubscribe_url]" type="text" size="50" value="<?php echo $unsubscribe_url; ?>"/>
					<br />
					<?php _e('If you want to create a page with your content to say "ok, you are unsubscribed", enter the URL here. Otherwise, leave this field blank and the following message will be used.', 'comment-notifier-no-spammers'); ?>
				</td>
			</tr>
			<tr>
				<td>
					<label><?php _e('Unsubscribe Message', 'comment-notifier-no-spammers'); ?></label><br />
					<textarea name="options[thankyou]" wrap="off" rows="7" style="width: 500px"><?php echo $thankyou; ?></textarea>
					<br />
					<?php _e('Example: You have unsubscribed successfully. Thank you. I will send you to the home page in 3 seconds.', 'comment-notifier-no-spammers'); ?><br />
				</td>
			</tr>
		</table>
		<hr />
		<h3><?php _e('Thank You Message Settings', 'comment-notifier-no-spammers'); ?></h3>
		<p><?php _e('Configure a thank you message for <strong>first time commentators</strong>. Messages are sent when comments are approved.', 'comment-notifier-no-spammers'); ?></p>

		<table class="form-table">
			<tr>
				<th><?php _e('Enable Thank You Message', 'comment-notifier-no-spammers'); ?></th>
				<td>
					<input type="checkbox" name="options[ty_enabled]" value="1" <?php echo isset( $options['ty_enabled'] ) ? 'checked' : ''; ?> />
					<?php _e('send a "Thank You" message sent to visitor on their first comment', 'comment-notifier-no-spammers'); ?>
				</td>
			</tr>
			<tr>
				<th><?php _e('Subject', 'comment-notifier-no-spammers'); ?></th>
				<td>
					<input name="options[ty_subject]" type="text"
					 size="70" value="<?php echo $ty_subject; ?>"/>
					<br />
					<?php printf(__('Tags: %3$s %1$s - the post title %3$s %2$s - the commenter name', 'comment-notifier-no-spammers'), '{title}', '{author}', '<br />'); ?>
				</td>
			</tr>
			<tr>
				<th><?php _e('Message Body', 'comment-notifier-no-spammers'); ?></th>
				<td>
					<textarea name="options[ty_message]" wrap="off"
					 rows="10" cols="70" style="width: 500px"><?php echo $ty_message; ?></textarea>
					<br />
					<?php printf(__('Tags: %5$s %1$s - the post title %5$s %2$s - the commenter name %5$s %3$s - link to the post/page %5$s %6$s - link to the comment %5$s %4$s - the comment text', 'comment-notifier-no-spammers'), '{title}', '{author}', '{link}', '{content}', '<br />', '{comment_link}' ); ?><br /><br />
				</td>
			</tr>
		</table>

		<hr />
		<h3><?php _e( 'Theme Compatibility', 'comment-notifier-no-spammers' ); ?></h3>
		<table class="form-table">
			<tr>
				<th><label><?php _e( 'Show Checkbox After The Comment Form', 'comment-notifier-no-spammers' ); ?></label></th>
				<td>
					<input type="checkbox" name="options[theme_compat]" value="1" <?php echo isset( $options['theme_compat'] ) ? 'checked' : ''; ?> />
					<?php _e( 'If the checkbox is not appearing on your comment form, enable this option. Enabling this option will make the checkbox work on a larger variety of independent themes (themes that do not use standard WordPress comment form filters). This will add the checkbox <strong>below</strong> the comment form submit button.', 'comment-notifier-no-spammers' ); ?>
				</td>
			</tr>
		</table>

		<hr />
		<h3><?php _e( 'Advanced Settings', 'comment-notifier-no-spammers' ); ?></h3>
		<table class="form-table">
			<tr>
				<th><label><?php _e( 'Extra email address where to send a copy of EACH notification:', 'comment-notifier-no-spammers' ); ?></label><br /><br /></th>
				<td>
					<input name="options[copy]" type="text" size="50" value="<?php echo $copy; ?>"/>
					<br />
					<?php _e( 'Leave empty to disable.', 'comment-notifier-no-spammers' ); ?>
				</td>
			</tr>

			<tr>
				<th><label><?php _e( 'Email address where to send test emails:', 'comment-notifier-no-spammers' ); ?></label><br /><br /></th>
				<td>
					<input name="options[test]" type="text" size="50" value="<?php echo $test; ?>"/>
					<br />
				</td>
			</tr>

			<tr>
				<th><label><?php _e('Disable CSS Styles', 'comment-notifier-no-spammers'); ?></label></th>
				<td>
					<input type="checkbox" name="options[disable_css]" value="1" <?php echo isset($options['disable_css']) ? 'checked' : ''; ?> />
					<?php _e('Check this to stop the CSS styles from being added to the checkbox.', 'comment-notifier-no-spammers'); ?>
				</td>
			</tr>

			<tr>
				<th><label><?php _e( 'Delete Data on Uninstall', 'comment-notifier-no-spammers'); ?></label></th>
				<td>
					<input type="checkbox" name="options[delete_data]" value="1" <?php echo isset( $options['delete_data'] ) ? 'checked' : ''; ?> />
					<?php _e( 'Check this box if you would like this plugin to <strong>delete all</strong> of its data when the plugin is deleted. This would delete the entire list of subscribers and their subscriptions. This does NOT delete the actual comments.', 'comment-notifier-no-spammers' ); ?>
				</td>
			</tr>

		</table>
		<p class="submit">
			<?php wp_nonce_field( 'update-lstc-options' ) ?>
			<input class="button-primary" type="submit" name="save" value="<?php _e('Save', 'comment-notifier-no-spammers'); ?>"/>
			   
			<input class="button-secondary" type="submit" name="savethankyou" value="<?php _e('Save and send a Thank You test email', 'comment-notifier-no-spammers'); ?>"/>

		</p>

	</form><hr />
	<form action="" method="post">
		<?php wp_nonce_field( 'remove' ) ?>
		<h3><?php _e( 'Long List of Subscribers', 'comment-notifier-no-spammers' ); ?></h3>
		<ul>
			<?php
			$list = $wpdb->get_results( "select distinct post_id, count(post_id) as total from " . $wpdb->prefix . "comment_notifier where post_id != 0 group by post_id order by total desc" );
			foreach ( $list as $r ) {
				$post_id = (int) $r->post_id;
				$total = (int) $r->total;
				$post = get_post( $post_id );
				echo '<li><a href="' . esc_url( get_permalink( $post_id ) ) . '" target="_blank">' .
				esc_html( $post->post_title ) . '</a> (id: ' . $post_id .
				__(', subscribers: ', 'comment-notifier-no-spammers') . $total . ')</li>';
				$list2 = $wpdb->get_results( "select id,email,name from " . $wpdb->prefix . "comment_notifier where post_id=" . $post_id );
				echo '<ul>';
				foreach ( $list2 as $r2 ) {
					echo '<li><input type="checkbox" name="s[]" value="' . esc_attr( $r2->id ) . '"/> ' . esc_html( $r2->email ) . '</li>';
				}
				echo '</ul>';
				echo '<input type="submit" name="remove" value="' . __('Remove', 'comment-notifier-no-spammers') . '"/>';
			}
			?>
		</ul>
	</form>
</div>
<?php }