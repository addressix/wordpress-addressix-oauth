<?php
class AddressixOAuthSettings
{
  private static $instance = false;

  private $options;

  static public function init()
  {
    if (self::$instance===false) {
      self::$instance = new AddressixOAuthSettings();
    }
  }

  function __construct()
  {
    add_action('admin_menu', array($this, 'admin_add_page'));
    add_action('admin_init', array($this, 'register_settings'));
  }

  function admin_add_page()
  {
//    remove_menu_page( 'users.php' );

    add_menu_page('Benutzer', 'Benutzer', 'list_users', 'addressix_users', array($this, 'draw_plugin_admin'), 20);

    add_options_page(
      'Addressix OAuth2 Options',
      'Addressix OAuth2', 
      'manage_options', 
      'addressixoauth_admin', 
      array($this, 'create_admin_page')
      );

  }

  function create_admin_page() 
  {
    $this->options = get_option('addressixoauth');
    ?>
<div class="wrap">
   <h2>Addressix OAuth2</h2>
   <form method="post" action="options.php">
   <?php 
   settings_fields('addressixoauth_group');
do_settings_sections('addressixoauth_admin');
submit_button();
?>
</form>
</div><?php
  }

  function register_settings()
  {
    register_setting('addressixoauth_group', 'addressixoauth');

    add_settings_section(
      'addressixoauth_main',
      'Main Settings', 
      function() { 
	echo '<p>Client credentials for Addressix</p>';
      }, 
      'addressixoauth_admin');

    add_settings_field(
      'clientid',
      'Client ID', 
      array($this, 'clientid_form'),
      'addressixoauth_admin', 
      'addressixoauth_main'
      );

    add_settings_field(
      'secret',
      'Secret Key', 
      array($this, 'secret_form'), 
      'addressixoauth_admin', 
      'addressixoauth_main'
      );  

    add_action('admin_post_users_edit', array($this, 'edit_users'));
    add_action('admin_post_users_create', array($this, 'create_user'));

    // plugin_update
    require(plugin_dir_path( dirname( __FILE__ ) ) . 'addressixoauth/aixupdater.php');
    $this->updater = new AixUpdater();
    add_filter('pre_site_transient_update_plugins', 'display_transient_update_plugins');
  }

  function clientid_form()
  {
    printf('<input type="text" id="clientid" name="addressixoauth[clientid]" value="%s">',
	   isset($this->options['clientid']) ? esc_attr($this->options['clientid']) : '');
  }
  
  function secret_form()
  {
    printf('<input type="text" id="secret" name="addressixoauth[secret]" value="%s">',
	   isset($this->options['secret']) ? esc_attr($this->options['secret']) : '');
  } 


  // user admin pages
  function draw_plugin_admin()
  {

    if (isset($_GET['user_id']) && ((int)$_GET['user_id'])) {
	    
      $url = '/events/v1/events/' . (int)$_GET['user_id'];
      $response = $this->api->fetch($url);
      if ($response->code==200) {
	$this->event = $response->body;
	require(plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/edit-user.php');
      }
      else {
	error_log('could not open event ' . $_GET['event_id'] . ' Code(' . $response->code .')');
      }
      $this->event = $response->body;	    

    } else if (isset($_GET['new'])) {
      require(plugin_dir_path( dirname( __FILE__ ) ) . 'addressixoauth/partials/new-user.php');
    }
    else {

      // nothing other matched - draw user list overview
      require(plugin_dir_path( dirname( __FILE__ ) ) . 'addressixoauth/usertable.php');    

      require(plugin_dir_path( dirname( __FILE__ ) ) . 'addressixoauth/partials/users.php');

    }

  }
 
  function create_user() {


    // Check nonce field
    check_admin_referer('create-user', '_wpnonce_create-user');
    $m = 'nop';

    $this->api = AddressixAPI::init();	  

    // check if we find addressix user
    $url = '/contact/v1/people?email=' . $_POST['email'];
    $response = $this->api->fetch($url, $params, 'GET');
    if ($response->code==200) {
      $m='';
      $people = $response->body;
      if (!count($people)) {
	$m='notfound';
      } else {
	foreach($people as $person) {
	  // register user
	  $username = $person->primaryemail;
	  $user_login = $username;
	  $user_login = preg_replace('#[<>"\'%;()&\s\\\\]|\\.\\./#', "", $user_login);
	  $user_login = trim (trim($user_login), '.');    

	  $pw = wp_generate_password();

	  $user_id = wp_insert_user(
	    array(
	      'user_login' => $username,
	      'user_nicename' => trim($person->firstname . ' ' . $person->surname),
	      'user_email' => $person->primaryemail,
	      'display_name' => $person->firstname,
	      'nickname' => $person->firstname,
	      'first_name' => $person->firstname,
	      'last_name' => $person->surname,
	      'password' => $pw
	      )
	    );

	  if (is_wp_error($user_id)) {
	    error_log($user_id->get_error_message());
	    $m='create_failed';
	  }
	  add_user_meta($user_id, 'wpoa_identity', 'Addressix|' . $person->addressixid . '|' . time());
	}
      }
    }
    else {
      $m = $response->code;
      error_log('could not get person for email ' . $_POST['email'] . ' Code(' . $response->code .')');
    }
    
    if ($m=='') {
      wp_redirect(admin_url('admin.php?page=addressix_users'));
    } else {
      $email = $_POST['email'];
      wp_redirect(admin_url('admin.php?page=addressix_users&new=1&email'));
    }
  }

  function edit_users() {

    error_log('edit users called');

    // Check nonce field
    //    check_admin_referer('edit-users-verify');

    require(plugin_dir_path( dirname( __FILE__ ) ) . 'addressixoauth/usertable.php');    
    $wp_list_table = new AIXUser_List_Table();
    $redirect = admin_url('admin.php?page=addressix_users');

    switch($wp_list_table->current_action()) {
    case promote:

      error_log('promote user');

      if ( ! current_user_can( 'promote_users' ) )
	wp_die( __( 'You can&#8217;t edit that user.' ) );
      
      if ( empty($_REQUEST['users']) ) {
	wp_redirect($redirect);
	exit();
      }

      $editable_roles = get_editable_roles();
      $role = false;
      if ( ! empty( $_REQUEST['new_role2'] ) ) {
	$role = $_REQUEST['new_role2'];
      } elseif ( ! empty( $_REQUEST['new_role'] ) ) {
	$role = $_REQUEST['new_role'];
      }
      
      if ( ! $role || empty( $editable_roles[ $role ] ) ) {
	wp_die( __( 'You can&#8217;t give users that role.' ) );
      }
      
      $userids = $_REQUEST['users'];
      $update = 'promote';
      foreach ( $userids as $id ) {
	$id = (int) $id;
	
	if ( ! current_user_can('promote_user', $id) )
	  wp_die(__('You can&#8217;t edit that user.'));
	// The new role of the current user must also have the promote_users cap or be a multisite super admin
	if ( $id == $current_user->ID && ! $wp_roles->role_objects[ $role ]->has_cap('promote_users')
	     && ! ( is_multisite() && is_super_admin() ) ) {
	  $update = 'err_admin_role';
	  continue;
	}

	// If the user doesn't already belong to the blog, bail.
	if ( is_multisite() && !is_user_member_of_blog( $id ) ) {
	  wp_die(
	    '<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
	    '<p>' . __( 'One of the selected users is not a member of this site.' ) . '</p>',
	    403
	    );
	}
	
	$user = get_userdata( $id );
	$user->set_role( $role );
      }
      $m = 'rolechanged';
      break;
    default:
      $m = '';
    }

    wp_redirect(admin_url('admin.php?page=addressix_users&m='.$m));
    exit;    
  }
}
