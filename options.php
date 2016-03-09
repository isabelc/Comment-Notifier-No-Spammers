<?php
function cmnt_nospammers_options_page() {
	$options = get_option('cmnt_nospammers');
	global $wpdb;
	// prevent warnings
	$unsubscribe_url = empty($options['unsubscribe_url']) ? '' : htmlspecialchars($options['unsubscribe_url']);
	$length = empty($options['length']) ? '' : htmlspecialchars($options['length']);
	$test = empty($options['test']) ? '' : htmlspecialchars($options['test']);
	$copy = empty($options['copy']) ? '' : htmlspecialchars($options['copy']);
	if(!empty($_POST['_wpnonce'])){

		if (wp_verify_nonce($_POST['_wpnonce'], 'update-comment-notifier-options')) {
			$options = stripslashes_deep($_POST['options']);
			update_option('cmnt_nospammers', $options);
			
			if (isset($_POST['savetest'])) {
				$cmnt_nospammers_data = new stdClass();
				$cmnt_nospammers_data->author = __('Author name', 'comment-notifier-no-spammers');
				$cmnt_nospammers_data->link = get_option('home');
				$cmnt_nospammers_data->comment_link = get_option('home');
				$cmnt_nospammers_data->title = __('A wonderful post title', 'comment-notifier-no-spammers');
				$cmnt_nospammers_data->content = __('This is a long comment. They say that love is more important than money, but have you ever tried to pay your bills with a hug?', 'comment-notifier-no-spammers');
				$message = cmnt_nospammers_replace($options['message'], $cmnt_nospammers_data);

				$message = str_replace('{name}', 'Subscriber name', $message);

				$message = str_replace('{unsubscribe}', get_option('home') . '/?cmnt_nospammers_id=0&cmnt_nospammers_t=fake', $message);


				$subject = $options['subject'];
				$subject = str_replace('{title}', $cmnt_nospammers_data->title, $subject);
				$subject = str_replace('{author}', $cmnt_nospammers_data->author, $subject);
				$subject = str_replace('{name}', 'Subscriber name', $subject);

				cmnt_nospammers_mail($test, $subject, $message);
			}

			if (isset($_POST['savethankyou'])) {
				$cmnt_nospammers_data = new stdClass();
				$cmnt_nospammers_data->author = __('Author', 'comment-notifier-no-spammers');
				$cmnt_nospammers_data->link = get_option('home');
				$cmnt_nospammers_data->comment_link = get_option('home');
				$cmnt_nospammers_data->title = __('The post title', 'comment-notifier-no-spammers');
				$cmnt_nospammers_data->content = __('This is a long comment. Be a yardstick of quality. Some people are not used to an environment where excellence is expected.', 'comment-notifier-no-spammers');
				$message = cmnt_nospammers_replace($options['ty_message'], $cmnt_nospammers_data);

				$subject = $options['ty_subject'];
				$subject = str_replace('{title}', $cmnt_nospammers_data->title, $subject);
				$subject = str_replace('{author}', $cmnt_nospammers_data->author, $subject);
				cmnt_nospammers_mail($test, $subject, $message, isset($options['ty_html']));
			}
		}
	}

	// Removes a single email for all subscriptions
	if (isset($_POST['remove_email'])) {
		if (!wp_verify_nonce($_POST['_wpnonce'], 'remove_email'))
			die(__('Security violated', 'comment-notifier-no-spammers'));
		$email = strtolower(trim($_POST['email']));
		$wpdb->query($wpdb->prepare("delete from " . $wpdb->prefix . "comment_notifier where email=%s", $email));
	}

	if (isset($_POST['remove'])) {
		if (!wp_verify_nonce($_POST['_wpnonce'], 'remove'))
			die(__('Security violated', 'comment-notifier-no-spammers'));
		$query = "delete from " . $wpdb->prefix . "comment_notifier where id in (" . implode(',', $_POST['s']) . ")";
		$wpdb->query($query);
	}
	?>

<script type="text/javascript">
    function cmnt_nospammers_preview()
    {
        var m = document.getElementById("message").value;
        m = m.replace("{content}", "I totally agree with your opinion about him, he's really...");
        var h = window.open("", "cmnt_nospammers","status=0,toolbar=0,height=400,width=550");
        var d = h.document;
        d.write('<html><head><title>Email preview</title>');
        d.write('</head><body>');
        d.write('<table width="100%" border="1" cellspacing="0" cellpadding="5">');
        d.write('<tr><td align="right"><b>Subject</b></td><td>' + document.getElementById("subject").value + '</td></tr>');
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
    <h2><?php _e('Comment Notifier No Spammers', 'comment-notifier-no-spammers'); ?></h2>
	<?php 
	$cleaned = get_option('cmnt_nospammers_cleanup');
	if ( $cleaned > 0 ) { ?>
		<div class="updated"><p><?php printf( _n( 'On activation, this plugin removed <strong>%s</strong> spammer email address from your database.', 'On activation, this plugin removed <strong>%s</strong> spammer email addresses from your database.', $cleaned, 'comment-notifier-no-spammers' ), $cleaned ); ?></p></div>
	<?php } ?>
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

    <form action="" method="post">
        <?php wp_nonce_field('update-comment-notifier-options') ?>
        <h3><?php _e('Subscription Checkbox Configuration', 'comment-notifier-no-spammers'); ?></h3>
        <table class="form-table">
            <tr>
                <th><?php _e('Add The Checkbox', 'comment-notifier-no-spammers'); ?></th>
                <td>
                    <input type="checkbox" name="options[checkbox]" value="1" <?php echo isset($options['checkbox']) ? 'checked' : ''; ?> />
                    <?php _e('Check this to add the "Notify me" checkbox to the comment form. (Not all themes support this function. Read below.)', 'comment-notifier-no-spammers'); ?>

                    <br /><br />
                    <?php
                    $commentsphp = @file_get_contents(get_template_directory() . '/comments.php');
                    if (strpos($commentsphp, 'comment_form') === false) {
                        echo '<strong>' .__('Your theme seems to NOT have the "comment_form" action call. Read below.', 'comment-notifier-no-spammers') . '</strong><br /><br />';
                    }

                    _e('Your theme needs to call the WordPress "comment_form" (usually in comments.php theme file). Not all themes have it. If you want to manually add the subscription checkbox, use this code for an unchecked checkbox:', 'comment-notifier-no-spammers'); ?><br /><br />
                    &nbsp;&nbsp;&nbsp;<code>&lt;input type="checkbox" value="1" name="cnns_subscribe" id="cnns_subscribe"/&gt;</code><br /><br />
                    <?php _e('or the one below for a checked checkbox:', 'comment-notifier-no-spammers'); ?><br /><br />
                    &nbsp;&nbsp;&nbsp;<code>&lt;input type="checkbox" value="1" name="cnns_subscribe" id="cnns_subscribe" checked="checked"/&gt;</code>
                </td>
            </tr>

            <tr>
                <th><?php _e('Subscription Checkbox Label', 'comment-notifier-no-spammers'); ?></th>
                <td>
                    <input name="options[label]" type="text" size="50" value="<?php echo htmlspecialchars($options['label']) ?>"/>
                    <br /><?php _e('Label to be displayed near the subscription checkbox', 'comment-notifier-no-spammers'); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Checkbox Default Status', 'comment-notifier-no-spammers'); ?></th>
                <td>
                    <input type="checkbox" name="options[checked]" value="1" <?php echo isset($options['checked']) ? 'checked' : ''; ?> />
                    <?php _e('Check here if you want the "Notify me" checkbox to be checked by default', 'comment-notifier-no-spammers'); ?>
                </td>
            </tr>
        </table>

        <h3><?php _e('Notification Email Settings', 'comment-notifier-no-spammers'); ?></h3>
        <table class="form-table">
            <tr>
                <th><?php _e('Notification Sender Name', 'comment-notifier-no-spammers'); ?></th>
                <td>
                    <input name="options[name]" id="from_name" type="text" size="50" value="<?php echo htmlspecialchars($options['name']) ?>"/>
                </td>
            </tr>

            <tr>
                <th><?php _e('Notification Sender Email', 'comment-notifier-no-spammers'); ?></th>
                <td>
                    <input name="options[from]" id="from_email" type="text" size="50" value="<?php echo htmlspecialchars($options['from']) ?>"/>
                </td>
            </tr>
            <tr>
                <th><?php _e('Notification Subject', 'comment-notifier-no-spammers'); ?></th>
                <td>
                    <input name="options[subject]" id="subject" type="text" size="70" value="<?php echo htmlspecialchars($options['subject']) ?>"/>
                    <br />
                    <?php printf(__('Tags: %1$s - the post title, %2$s - the subscriber name, %3$s - the commenter name', 'comment-notifier-no-spammers'), '{title}', '{name}', '{author}'); ?>
                </td>
            </tr>

            <tr>
                <th><?php _e('Notification Message Body', 'comment-notifier-no-spammers'); ?></th>
                <td>
                    <input type="checkbox" name="options[html]" value="1" <?php echo $options['html'] != null ? 'checked' : ''; ?> /> <?php _e(' send emails as html', 'comment-notifier-no-spammers'); ?> 
                    ( <a href="javascript:void(cmnt_nospammers_preview());"><?php _e('preview', 'comment-notifier-no-spammers'); ?></a>)
                    <br />
                    <textarea name="options[message]" id="message" wrap="off" rows="10" style="width: 100%"><?php echo htmlspecialchars($options['message']) ?></textarea>
                    <br />
                    <?php printf(__('Tags: %1$s - the subscriber name, %2$s - the unsubscribe link, %3$s - the post title, %4$s - the commenter name, %5$s - the post link, %6$s - the comment text (eventually truncated).'), '{name}', '{unsubscribe}', '{title}', '{author}', '{link}', '{content}'); ?><br /><br />
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

        <h3><?php _e('Unsubscribe Settings', 'comment-notifier-no-spammers'); ?></h3>
        <p>
            <?php _e('Here you can configure what to show to unsubscribing users. Set an "Unsubscribe page URL" to send the user to a specific page or configure a specific message.', 'comment-notifier-no-spammers'); ?>
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
                    <textarea name="options[thankyou]" wrap="off" rows="7" style="width: 500px"><?php echo htmlspecialchars($options['thankyou']) ?></textarea>
                    <br />
                    <?php _e('Example: You have unsubscribed successfully. Thank you. I will send you to the home page in 3 seconds.', 'comment-notifier-no-spammers'); ?><br />
                </td>
            </tr>
        </table>

        <p class="submit">
            <input class="button-primary" type="submit" name="save" value="<?php _e('Save', 'comment-notifier-no-spammers'); ?>"/>
            <input class="button-primary" type="submit" name="savetest" value="<?php _e('Save and send a test email', 'comment-notifier-no-spammers'); ?>"/>
        </p>


        <h3><?php _e('Thank You Message Settings', 'comment-notifier-no-spammers'); ?></h3>
        <p><?php _e('Configure a thank you message for <strong>first time commentators</strong>. Messages are sent when comments are approved.', 'comment-notifier-no-spammers'); ?></p>

        <table class="form-table">
            <tr>
				<th><?php _e('Enable Thank You Message', 'comment-notifier-no-spammers'); ?></th>
                <td>
                    <input type="checkbox" name="options[ty_enabled]" value="1" <?php echo isset($options['ty_enabled']) ? 'checked' : ''; ?> />
                    <?php _e('send a "Thank You" message sent to visitor on their first comment', 'comment-notifier-no-spammers'); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Subject', 'comment-notifier-no-spammers'); ?></th>
                <td>
                    <input name="options[ty_subject]" type="text" size="70" value="<?php echo htmlspecialchars($options['ty_subject']) ?>"/>
                    <br />
                    <?php printf(__('Tags: %1$s - the post title, %2$s - the commenter name', 'comment-notifier-no-spammers'), '{title}', '{author}'); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Notification message body', 'comment-notifier-no-spammers'); ?></th>
                <td>
                    <input type="checkbox" name="options[ty_html]" value="1" <?php echo isset($options['ty_html']) ? 'checked' : ''; ?> />  <?php _e(' send emails as html', 'comment-notifier-no-spammers'); ?>
                    <br />
                    <textarea name="options[ty_message]" wrap="off" rows="10" cols="70" style="width: 500px"><?php echo htmlspecialchars($options['ty_message']); ?></textarea>
                    <br />
                    <?php printf(__('Tags: %1$s - the post title, %2$s - the commenter name, %3$s - the post link, %4$s - the comment text.', 'comment-notifier-no-spammers'), '{title}', '{author}', '{link}', '{content}'); ?><br /><br />
                </td>
            </tr>
        </table>

        <p class="submit">
            <input class="button-primary" type="submit" name="save" value="<?php _e('Save', 'comment-notifier-no-spammers'); ?>"/>
            <input class="button-primary" type="submit" name="savethankyou" value="<?php _e('Save and send a thank you test email', 'comment-notifier-no-spammers'); ?>"/>
        </p>
        <h3><?php _e('Advanced Settings', 'comment-notifier-no-spammers'); ?></h3>
        <table class="form-table">
            <tr>
                <td>
                    <label><?php _e('Extra email address where to send a copy of EACH notification:', 'comment-notifier-no-spammers'); ?></label><br /><br />
                    <input name="options[copy]" type="text" size="50" value="<?php echo $copy; ?>"/>
                    <br />
                    <?php _e('Leave empty to disable. You still get notifications at the site\'s admin email (which is set in General Settings).', 'comment-notifier-no-spammers'); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label><?php _e('Email address where to send test emails:', 'comment-notifier-no-spammers'); ?></label><br /><br />
                    <input name="options[test]" type="text" size="50" value="<?php echo $test; ?>"/>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input class="button button-primary" type="submit" name="save" value="<?php _e('Save', 'comment-notifier-no-spammers'); ?>"/>
        </p>

    </form>
    <form action="" method="post">
        <?php wp_nonce_field('remove') ?>
        <h3><?php _e('Long List of Subscribers', 'comment-notifier-no-spammers'); ?></h3>
        <ul>
            <?php
            $list = $wpdb->get_results("select distinct post_id, count(post_id) as total from " . $wpdb->prefix . "comment_notifier group by post_id order by total desc");
            foreach ($list as $r) {
                $post = get_post($r->post_id);
                $link = get_permalink($r->post_id);
                echo '<li><a href="' . $link . '" target="_blank">' . $post->post_title . '</a> (id: ' . $r->post_id . __(', subscribers: ', 'comment-notifier-no-spammers') . $r->total . ')</li>';
                $list2 = $wpdb->get_results("select id,email,name from " . $wpdb->prefix . "comment_notifier where post_id=" . $r->post_id);
                echo '<ul>';
                foreach ($list2 as $r2) {
                    echo '<li><input type="checkbox" name="s[]" value="' . $r2->id . '"/> ' . $r2->email . '</li>';
                }
                echo '</ul>';
                echo '<input type="submit" name="remove" value="' . __('Remove', 'comment-notifier-no-spammers') . '"/>';
            }
            ?>
        </ul>
    </form>
</div>
<?php }