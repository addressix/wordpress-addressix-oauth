<?php
if ( is_multisite() ) {
	if ( ! current_user_can( 'create_users' ) && ! current_user_can( 'promote_users' ) ) {
		wp_die(
			'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
			'<p>' . __( 'You do not have sufficient permissions to add users to this network.' ) . '</p>',
			403
		);
	}
} elseif ( ! current_user_can( 'create_users' ) ) {
	wp_die(
		'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
		'<p>' . __( 'You are not allowed to create users.' ) . '</p>',
		403
	);
}

if ( is_multisite() ) {
	add_filter( 'wpmu_signup_user_notification_email', 'admin_created_user_email' );
}

if ( isset($_REQUEST['action']) && 'adduser' == $_REQUEST['action'] ) {
	check_admin_referer( 'add-user', '_wpnonce_add-user' );
} elseif ( isset($_REQUEST['action']) && 'createuser' == $_REQUEST['action'] ) {
	check_admin_referer( 'create-user', '_wpnonce_create-user' );

	if ( ! current_user_can( 'create_users' ) ) {
		wp_die(
			'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
			'<p>' . __( 'You are not allowed to create users.' ) . '</p>',
			403
		);
	}

	if ( ! is_multisite() ) {
		$user_id = edit_user();

		if ( is_wp_error( $user_id ) ) {
			$add_user_errors = $user_id;
		} else {
			if ( current_user_can( 'list_users' ) )
				$redirect = 'users.php?update=add&id=' . $user_id;
			else
				$redirect = add_query_arg( 'update', 'add', 'user-new.php' );
			wp_redirect( $redirect );
			die();
		}
	} else {
// TODO
	}
  }

$title = __('Add New User');
$parent_file = 'users.php';

$do_both = false;
if ( is_multisite() && current_user_can('promote_users') && current_user_can('create_users') )
	$do_both = true;

$help = '<p>' . __('To add a new user to your site, fill in the form on this screen and click the Add New User button at the bottom.') . '</p>';

if ( is_multisite() ) {
  $help .= '<p>' . __('Because this is a multisite installation, you may add accounts that already exist on the Network by specifying a username or email, and defining a role. For more options, such as specifying a password, you have to be a Network Administrator and use the hover link under an existing user&#8217;s name to Edit the user profile under Network Admin > All Users.') . '</p>' ;
} else {
  $help .= '<p>' . __('You must assign a password to the new user, which they can change after logging in. The username, however, cannot be changed.') . '</p>' ;
}

$help .= '<p>' . __('Remember to click the Add New User button at the bottom of this screen when you are finished.') . '</p>';

get_current_screen()->add_help_tab( array(
	'id'      => 'overview',
	'title'   => __('Overview'),
	'content' => $help,
) );

get_current_screen()->add_help_tab( array(
'id'      => 'user-roles',
'title'   => __('User Roles'),
'content' => '<p>' . __('Here is a basic overview of the different user roles and the permissions associated with each one:') . '</p>' .
				'<ul>' .
				'<li>' . __('Subscribers can read comments/comment/receive newsletters, etc. but cannot create regular site content.') . '</li>' .
				'<li>' . __('Contributors can write and manage their posts but not publish posts or upload media files.') . '</li>' .
				'<li>' . __('Authors can publish and manage their own posts, and are able to upload files.') . '</li>' .
				'<li>' . __('Editors can publish posts, manage posts as well as manage other people&#8217;s posts, etc.') . '</li>' .
				'<li>' . __('Administrators have access to all the administration features.') . '</li>' .
				'</ul>'
) );

get_current_screen()->set_help_sidebar(
    '<p><strong>' . __('For more information:') . '</strong></p>' .
    '<p>' . __('<a href="https://codex.wordpress.org/Users_Add_New_Screen" target="_blank">Documentation on Adding New Users</a>') . '</p>' .
    '<p>' . __('<a href="https://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>'
);

wp_enqueue_script('wp-ajax-response');
wp_enqueue_script( 'user-profile' );

/**
 * Filter whether to enable user auto-complete for non-super admins in Multisite.
 *
 * @since 3.4.0
 *
 * @param bool $enable Whether to enable auto-complete for non-super admins. Default false.
 */
if ( is_multisite() && current_user_can( 'promote_users' ) && ! wp_is_large_network( 'users' )
	&& ( is_super_admin() || apply_filters( 'autocomplete_users_for_site_admins', false ) )
) {
	wp_enqueue_script( 'user-suggest' );
}

if ( isset($_GET['update']) ) {
	$messages = array();
	if ( is_multisite() ) {
		$edit_link = '';
		if ( ( isset( $_GET['user_id'] ) ) ) {
			$user_id_new = absint( $_GET['user_id'] );
			if ( $user_id_new ) {
				$edit_link = esc_url( add_query_arg( 'wp_http_referer', urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ), get_edit_user_link( $user_id_new ) ) );
			}
		}

		switch ( $_GET['update'] ) {
			case "newuserconfirmation":
				$messages[] = __('Invitation email sent to new user. A confirmation link must be clicked before their account is created.');
				break;
			case "add":
				$messages[] = __('Invitation email sent to user. A confirmation link must be clicked for them to be added to your site.');
				break;
			case "addnoconfirmation":
				if ( empty( $edit_link ) ) {
					$messages[] = __( 'User has been added to your site.' );
				} else {
					/* translators: %s: edit page url */
					$messages[] = sprintf( __( 'User has been added to your site. <a href="%s">Edit user</a>' ), $edit_link );
				}
				break;
			case "addexisting":
				$messages[] = __('That user is already a member of this site.');
				break;
			case "does_not_exist":
				$messages[] = __('The requested user does not exist.');
				break;
			case "enter_email":
				$messages[] = __('Please enter a valid email address.');
				break;
		}
	} else {
		if ( 'add' == $_GET['update'] )
			$messages[] = __('User added.');
	}
}
?>
<div class="wrap">
<h1 id="add-new-user"><?php
if ( current_user_can( 'create_users' ) ) {
	echo _x( 'Add New User', 'user' );
} elseif ( current_user_can( 'promote_users' ) ) {
	echo _x( 'Add Existing User', 'user' );
} ?>
</h1>

<?php if ( isset($_GET['m']) && ($_GET['m']!='') ) : ?>
	<div class="error">
		<ul>
		<?php
	   switch($_GET['m']) {
	   case 'notfound':
	   echo '<li>' . __('Benutzer nicht gefunden');
	   break;
	   }
		?>
		</ul>
	</div>
<?php endif;

if ( ! empty( $messages ) ) {
	foreach ( $messages as $msg )
		echo '<div id="message" class="updated notice is-dismissible"><p>' . $msg . '</p></div>';
} ?>

<?php if ( isset($add_user_errors) && is_wp_error( $add_user_errors ) ) : ?>
	<div class="error">
		<?php
			foreach ( $add_user_errors->get_error_messages() as $message )
				echo "<p>$message</p>";
		?>
	</div>
<?php endif; ?>
<div id="ajax-response"></div>

<?php
if ( is_multisite() ) {
	if ( $do_both )
		echo '<h2 id="add-existing-user">' . __( 'Add Existing User' ) . '</h2>';
	if ( !is_super_admin() ) {
		echo '<p>' . __( 'Enter the email address of an existing user on this network to invite them to this site. That person will be sent an email asking them to confirm the invite.' ) . '</p>';
		$label = __('Email');
		$type  = 'email';
	} else {
		echo '<p>' . __( 'Enter the email address or username of an existing user on this network to invite them to this site. That person will be sent an email asking them to confirm the invite.' ) . '</p>';
		$label = __('Email or Username');
		$type  = 'text';
	}
?>
<form method="post" name="adduser" id="adduser" class="validate" novalidate="novalidate"<?php
	/**
	 * Fires inside the adduser form tag.
	 *
	 * @since 3.0.0
	 */
	do_action( 'user_new_form_tag' );
?>>
<input name="action" type="hidden" value="user_add" />
<?php wp_nonce_field( 'add-user', '_wpnonce_add-user' ) ?>

<table class="form-table">
	<tr class="form-field form-required">
		<th scope="row"><label for="adduser-email"><?php echo $label; ?></label></th>
		<td><input name="email" type="<?php echo $type; ?>" id="adduser-email" class="wp-suggest-user" value="" /></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="adduser-role"><?php _e('Role'); ?></label></th>
		<td><select name="role" id="adduser-role">
			<?php wp_dropdown_roles( get_option('default_role') ); ?>
			</select>
		</td>
	</tr>
<?php if ( current_user_can( 'manage_network_users' ) ) { ?>
	<tr>
		<th scope="row"><label for="adduser-noconfirmation"><?php _e('Skip Confirmation Email') ?></label></th>
		<td><label for="adduser-noconfirmation"><input type="checkbox" name="noconfirmation" id="adduser-noconfirmation" value="1" /> <?php _e( 'Add the user without sending an email that requires their confirmation.' ); ?></label></td>
	</tr>
<?php } ?>
</table>
<?php
/**
 * Fires at the end of the new user form.
 *
 * Passes a contextual string to make both types of new user forms
 * uniquely targetable. Contexts are 'add-existing-user' (Multisite),
 * and 'add-new-user' (single site and network admin).
 *
 * @since 3.7.0
 *
 * @param string $type A contextual string specifying which type of new user form the hook follows.
 */
do_action( 'user_new_form', 'add-existing-user' );
?>
<?php submit_button( __( 'Add Existing User' ), 'primary', 'adduser', true, array( 'id' => 'addusersub' ) ); ?>
</form>
<?php
} // is_multisite()

if ( current_user_can( 'create_users') ) {
	if ( $do_both )
		echo '<h2 id="create-new-user">' . __( 'Add New User' ) . '</h2>';
?>
<p><?php _e('Create a brand new user and add them to this site.'); ?></p>
<form method="post" name="createuser" id="createuser" class="validate" novalidate="novalidate"<?php
	/** This action is documented in wp-admin/user-new.php */
	do_action( 'user_new_form_tag' );
?> action="admin-post.php">
<input name="action" type="hidden" value="users_create" />
<?php wp_nonce_field( 'create-user', '_wpnonce_create-user' ); ?>
<?php
// Load up the passed data, else set to a default.
$creating = isset( $_POST['createuser'] );

//$new_user_name = $creating && isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '';
$new_user_email = $creating && isset( $_REQUEST['email'] ) ? wp_unslash( $_REQUEST['email'] ) : '';
$new_user_role = $creating && isset( $_POST['role'] ) ? wp_unslash( $_POST['role'] ) : '';
$new_user_send_notification = $creating && ! isset( $_POST['send_user_notification'] ) ? false : true;

?>
<table class="form-table">
	<tr class="form-field form-required">
		<th scope="row"><label for="email"><?php _e('Email'); ?></label></th>
		<td><input name="email" type="email" id="email" value="<?php echo esc_attr( $new_user_email ); ?>" /></td>
	</tr>
<?php if ( 0 && !is_multisite() ) { ?>
	<tr>
		<th scope="row"><?php _e( 'Send User Notification' ) ?></th>
		<td><label for="send_user_notification"><input type="checkbox" name="send_user_notification" id="send_user_notification" value="1" <?php checked( $new_user_send_notification ); ?> /> <?php _e( 'Send the new user an email about their account.' ); ?></label></td>
	</tr>
<?php } // !is_multisite ?>
	<tr class="form-field">
		<th scope="row"><label for="role"><?php _e('Role'); ?></label></th>
		<td><select name="role" id="role">
			<?php
			if ( !$new_user_role )
				$new_user_role = !empty($current_role) ? $current_role : get_option('default_role');
			wp_dropdown_roles($new_user_role);
			?>
			</select>
		</td>
	</tr>
	<?php if ( is_multisite() && current_user_can( 'manage_network_users' ) ) { ?>
	<tr>
		<th scope="row"><label for="noconfirmation"><?php _e('Skip Confirmation Email') ?></label></th>
		<td><label for="noconfirmation"><input type="checkbox" name="noconfirmation" id="noconfirmation" value="1" <?php checked( $new_user_ignore_pass ); ?> /> <?php _e( 'Add the user without sending an email that requires their confirmation.' ); ?></label></td>
	</tr>
	<?php } ?>
</table>

<?php
/** This action is documented in wp-admin/user-new.php */
do_action( 'user_new_form', 'add-new-user' );
?>

<?php submit_button( __( 'Add New User' ), 'primary', 'createuser', true, array( 'id' => 'createusersub' ) ); ?>

</form>
<?php } // current_user_can('create_users') ?>
</div>
<?php
