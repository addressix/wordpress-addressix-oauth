<?php
require('aixoauth2.php');

class Aixoauth 
{
  private static $initiated = false;
  private static $instance;
  
  public static function init() {
    if ( ! self::$initiated ) {
      self::$instance = new Aixoauth();
    }
  }

  /**
   * Initializes WordPress hooks
   */
  function __construct() {
    self::$initiated = true;
    
    add_filter('query_vars', array($this, 'qvar_triggers'));
    add_action('template_redirect', array($this, 'qvar_handler'));
  }
  
  function qvar_triggers($vars) {
    $vars[] = 'login';
    $vars[] = 'code';
    return $vars;
  }
  
  function getCredentials()
  {
    $options = get_option('addressixoauth');
    $this->clientid = $options['clientid'];
    $this->secret = $options['secret'];
    $this->redirecturl = get_bloginfo('url');  
  }
   
  function qvar_handler() {

    if (get_query_var('login')) {
      $this->getCredentials();
      $this->callLogin();
    }
    else if (get_query_var('code')) {
      $this->getCredentials();
      $this->callback();
    }
  }

  function getAccessToken()
  {
    $tokens = get_user_meta(get_current_user_id(),'addressix_access');
    return $tokens[0];
  }

  function callLogin() {
    global $wpdb;  

    $state = rand(1001,99999);
// TODO:    $_SESSION['aixauthstate'] = $state;
    if (wp_redirect('https://www.addressix.com/oauth2/v1/fdp.ch/authorize?response_type=code&redirect_uri='.urlencode($this->redirecturl).'&client_id='.$this->clientid.'&state=' . $state)) {
      exit;
    }
  }

  function getUserForAddressixId($addressixid)
  {
    global $wpdb;
    $usermeta_table = $wpdb->usermeta;
    $sql = "SELECT $usermeta_table.user_id FROM $usermeta_table WHERE $usermeta_table.meta_key='wpoa_identity' AND $usermeta_table.meta_value LIKE '%Addressix|" .$addressixid . "%'";
    $rs = $wpdb->get_var($sql);

    $user = get_user_by('id', $rs);

    return $user;
  }

  function linkAccount($user_id, $addressixid)
  {
    add_user_meta($user_id, 'wpoa_identity', 'Addressix|' . $addressixid . '|' . time());
  }

  function register($person) {
    $user_login = $person->username;
    $user_login = preg_replace('#[<>"\'%;()&\s\\\\]|\\.\\./#', "", $user_login);
    $user_login = trim (trim($user_login), '.');    

    $pw = wp_generate_password();

    $user_id = wp_insert_user(
      array(
	'user_login' => $person->username,
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
      return 0;
    }

    $this->linkAccount($user_id, $person->addressixid);
    $creds = array();
    $creds['user_login'] = $person->username;
    $creds['user_password'] = $pw;
    $creds['remember'] = true;

    $user = wp_signon($creds, false);

    return $user_id;
  }

  function callback() {

    $client = new OAuth2Client($this->clientid, $this->secret);
    $params = array('code' => $_GET['code'], 
		    'redirect_uri' => $this->redirecturl);
    $response = $client->getAccessToken('authorization_code', $params);

    if ($response->code==200) {	
      $access_token = $response->body->access_token;
      $client->setAccessToken($access_token);

//	error_log('access-code: ' . $access_token);
      $personresponse = $client->fetch('https://www.addressix.com/api/people/v1/me');
      if ($personresponse->code==200) {
	$person = $personresponse->body;
//	  error_log('aixid: ' . $person->addressixid);
	$matched_user = $this->getUserForAddressixId($person->addressixid);
	if ($matched_user)
	{
	  // a user matched, log him in
	  $user_id = $matched_user->ID;
	  $user_login = $matched_user->user_login;
	  wp_set_current_user($user_id, $user_login);
	  wp_set_auth_cookie($user_id);
	  do_action('wp_login', $user_login, $matched_user);
	  update_user_meta($user_id, 'addressix_access', $access_token);
	  wp_redirect($this->redirecturl);
	}
	else {

	  error_log('new user');
	  // user does not exist yet, create him
	  $user_id = $this->register($person);
	  add_user_meta($user_id, 'addressix_access', $access_token);
	}
      }
    }
    else {
      error_log('aixlogin-callback: error-response: ' .$response->code);
    }
  }  
}