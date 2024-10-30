<?php
/*
 Plugin Name: Constant Contact Digest
 Description: Allows an administrator to quickly and easily send a digest via Constant Contact service
 Author URI: http://aimbox.com
 Author: Aimbox
 Version: 1.0
  */

require_once dirname(__FILE__) . '/ctct_php_library/ConstantContact.php';

class CC_Singleton
{
	private static $api_key;
	private static $username;
	private static $password;
	private static $constantContact;
	
	private function __construct() {}

	public static function &getInstance() 
	{
		if (is_object(self::$constantContact) == true) {
			return self::$constantContact;
		}
		
		if ( empty(self::$api_key) || empty(self::$username) || empty(self::$password) ) {
			$options = get_option( 'uvd_ccdp_options' );
			self::$api_key = $options['api_key'];
			self::$username = $options['username'];
			self::$password = base64_decode($options['password']);
		}
		
		if ( empty(self::$api_key) || empty(self::$username) || empty(self::$password) ) {
			throw new Exception('Empty authorization data');
		}

		self::$constantContact = new ConstantContact ( "basic", self::$api_key, self::$username, self::$password );
		return self::$constantContact;
    }
}

register_activation_hook(__FILE__, 'uvd_ccdp_install'); 
register_deactivation_hook( __FILE__, 'uvd_ccdp_remove' );

function uvd_ccdp_install()
{
	$options = array(
		'api_key'		=> '',
		'username'		=> '',
		'password'		=> '',
		'masthead'		=> '',
	);
	add_option( 'uvd_ccdp_options', $options );
	
	// give 'send_cc_digests' capability to administrator
	$role = get_role('administrator');
	if(!$role->has_cap('send_cc_digests')) {
		$role->add_cap('send_cc_digests');
	}
}

function uvd_ccdp_remove()
{
	delete_option( 'uvd_ccdp_options' );
	
	// take 'send_cc_digests' capability from administrator
	$role = get_role('administrator');
	if($role->has_cap('send_cc_digests')) {
		$role->remove_cap('send_cc_digests');
	}
}

add_action( 'admin_menu', 'uvd_ccdp_add_page' );

function uvd_ccdp_add_page() 
{
	add_submenu_page(  
		'options-general.php',
        'Constant Contact Digest Plugin',			
        'Constant Contact Digest',					
        'send_cc_digests',							
        'uvd_ccdp_options_menu',									
        'uvd_ccdp_options_page'						
    ); 
	
	add_submenu_page(  
		'tools.php',
        'Constant Contact Digest Plugin',			
        'Constant Contact Digest',					
        'send_cc_digests',							
        'uvd_ccdp_tools_menu',									
        'uvd_ccdp_tools_page'						
    );
}

function uvd_ccdp_options_page()
{
?> 
  <div class='wrap'> 
	<?php screen_icon(); ?> 
	<h2> Constant Contact Settings </h2> 
	
	<?php settings_errors(); ?>
	
	<form action="options.php" method="post" > 
		<?php settings_fields('uvd_ccdp_group'); ?> 
		<?php do_settings_sections('uvd_ccdp_options_menu'); ?> 
		<?php submit_button(); ?>
	</form>  
  </div> 
<?php
}

// Register and define the settings
add_action('admin_init', 'uvd_ccdp_admin_init');

function uvd_ccdp_admin_init()
{
    register_setting( 
		'uvd_ccdp_group', 
		'uvd_ccdp_options',
		'uvd_ccdp_sanitize'
	);
	
    add_settings_section( 
		'uvd_ccdp_settings_sections', 
		'User Credentials',
        'uvd_ccdp_section_text', 
		'uvd_ccdp_options_menu' 
	);
	
	add_settings_field( 
		'api_key', 
		'API Key:',
        'uvd_ccdp_api_key', 
		'uvd_ccdp_options_menu', 
		'uvd_ccdp_settings_sections' 
	);
	
    add_settings_field( 
		'username', 
		'User Name:',
        'uvd_ccdp_username', 
		'uvd_ccdp_options_menu', 
		'uvd_ccdp_settings_sections' 
	);
	
	add_settings_field(
		'password',
		'Password:',
        'uvd_ccdp_password',
		'uvd_ccdp_options_menu',
		'uvd_ccdp_settings_sections'
	);

	add_settings_field(
		'masthead',
		'Masthead:',
		'uvd_ccdp_masthead',
		'uvd_ccdp_options_menu',
		'uvd_ccdp_settings_sections'
	);
}

// Draw the section header
function uvd_ccdp_section_text() 
{
?>    
<div class="description" style="font-style:italic;">
	To get your API Key go to the <a href="http://community.constantcontact.com/t5/Documentation/API-Keys/ba-p/25015" target="blank">link</a>
</div>
<?php
}

function uvd_ccdp_api_key()
{
	// get option 'uvd_ccdp_options' value from the database
    $options = get_option( 'uvd_ccdp_options' );
    $api_key = $options['api_key'];
    // echo the field
    echo '<input id="api_key" name="uvd_ccdp_options[api_key]" value="'.$api_key.'" type="text" size="35">';
}

function uvd_ccdp_username()
{
	// get option 'uvd_ccdp_options' value from the database
    $options = get_option( 'uvd_ccdp_options' );
    $username = $options['username'];
    // echo the field
    echo '<input id="api_key" name="uvd_ccdp_options[username]" value="'.$username.'" type="text" size="35">';
}

function uvd_ccdp_password()
{
	// get option 'uvd_ccdp_options' value from the database
    $options = get_option( 'uvd_ccdp_options' );
    $password = base64_decode($options['password']);
    // echo the field
    echo '<input id="api_key" name="uvd_ccdp_options[password]" value="'.$password.'" type="password" size="35">';
}

function uvd_ccdp_masthead()
{
	// get option 'uvd_ccdp_options' value from the database
	$options = get_option( 'uvd_ccdp_options' );
	$masthead = $options['masthead'];
	// echo the field
	echo '<input id="masthead" name="uvd_ccdp_options[masthead]" value="'.$masthead.'" type="text" size="35">';
}

function uvd_ccdp_sanitize( $input )
{
	foreach ($input as $key => $value) {
		$input[$key] = trim($value);
	}
	$input['password'] = base64_encode($input['password']);
	return $input;
}

function uvd_ccdp_tools_page()
{
	if ( ! current_user_can( 'send_cc_digests' ) )
		wp_die( __( 'You do not have sufficient permissions to send Constant Contact Digest.' ) );
	
	$errors = array();
	
	try {
		$constantContact = CC_Singleton::getInstance();
	}
	catch (Exception $e) {
		$errors['exception'] = 'Your authorization data is empty. Go to the <a href="'. admin_url( 'options-general.php?page=uvd_ccdp_options_menu' ) .'">setting page</a> and fill in the form.';
	}
	
	// get lists
	if (is_object($constantContact) ) {
		try {
			$lists = @$constantContact->getLists();
		}
		catch (Exception $e) {
			$errors['exception'] = strip_tags($e->getMessage());
		}
	}
	
	// if request has been processed get the result
	if ( isset( $_REQUEST['digest-sent'] ) && $_REQUEST['digest-sent'] == 'false' ) {
		$error_code = get_transient('uvd_ccdp_error');
		if ( !isset($errors[$error_code]) ) {
			switch ($error_code) {
				case 'auth_failed':
					$errors['auth_failed'] = 'Your authorization data is empty. Go to the <a href="'. admin_url( 'options-general.php?page=uvd_ccdp_options_menu' ) .'">setting page</a> and fill in the form.';
					break;
				case 'no_posts':
					$errors['no_posts'] = 'There are no posts for that condition. Try to increase time interval.';
					break;
				case 'email_not_verified':
					$errors['email_not_verified'] = 'You should verify you email address.';
					break;
				case 'missing_field':
					$errors['missing_field'] = 'Missing required field(s).';
					break;
				case 'shedule_fail':
					$errors['shedule_fail'] = 'Failed to send the campaign.';
					break;
				default:
					$errors[$error_code] = $error_code;
					break;
			}
			
		}
	}
	
?>
  <div class='wrap'> 
	<?php screen_icon(); ?> 
	<h2> Constant Contact Digest Form </h2> 
	
	<?php if ( !empty($errors) ) : ?>
		<?php foreach ($errors as $error) : ?>
			<div id='settings-error-ccdp' class='error settings-error'>
				<p>
					<strong><?php echo $error; ?></strong>
				</p>
			</div>
		<?php endforeach; ?>
	<?php elseif ( isset( $_REQUEST['digest-sent'] ) && $_REQUEST['digest-sent'] == 'true' ) : ?>
		<div id='settings-error-ccdp' class='updated settings-error'>
			<p>
				<strong>Digest was sent successfully!</strong>
			</p>
		</div>
	<?php endif; ?>
	
	<form action="admin-post.php?action=uvd_ccdp_send_digest" method="post" > 
		<?php wp_nonce_field('uvd_ccdp_sending_digest', 'uvd_ccdp_nonce'); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Email Title:</th>
				<td>
					<input id="email_title" name="email_title" type="text" value="" size="35" >
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Email Subject</th>
				<td>
					<input id="email_subject" name="email_subject" type="text" value="" size="35">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">From Name:</th>
				<td>
					<input id="from_name" name="from_name" type="text" value="" size="35">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Contact Lists:</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><span>Contact Lists</span></legend>
						<?php if ( !empty($lists['lists']) ) : ?>
							<?php foreach ( $lists['lists'] as $list ) : ?>
								<label>
									<input id="list-<?php echo $list->name; ?>" name="lists[]" type="checkbox" value="<?php echo $list->id; ?>">
									<?php echo $list->name; ?>
								</label>
								<br>
							<?php endforeach; ?>
						<?php endif; ?>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Email Greeting Salutation:</th>
				<td>
					<input id="salutation" name="salutation" type="text" value="Dear" size="35">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Email Greeting Name:</th>
				<td>
					<select id="greeting_name" name="greeting_name">
						<option value="FirstName">FirstName</option>
						<option value="LastName">LastName</option>
						<option value="FirstAndLastName">FirstAndLastName</option>
						<option value="NONE">NONE</option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Email Greeting String:</th>
				<td>
					<input id="greeting_string" name="greeting_string" type="text" value="Greetings!" size="35">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">View As Webpage:</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><span>View As Webpage</span></legend>
						<label><input id="webpage-yes" type="radio" name="webpage" checked="checked" value="1">Yes</label>
						<label><input id="webpage-no" type="radio" name="webpage" value="0">No</label>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">View As Webpage Text:</th>
				<td>
					<input id="webpage_text" name="webpage_text" type="text" value="Having trouble viewing" size="35">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">View As Webpage Link Text:</th>
				<td>
					<input id="webpage_link_text" name="webpage_link_text" type="text" value="Click here" size="35">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Include Forward Email Link:</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><span>Include Forward Email Link</span></legend>
						<label><input id="forward_link-yes" type="radio" name="forward_link" checked="checked" value="1">Yes</label>
						<label><input id="forward_link-no" type="radio" name="forward_link" value="0">No</label>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Forward Email Link Text:</th>
				<td>
					<input id="forward_link_text" name="forward_link_text" type="text" value="Forward email" size="35">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Include Subscribe Email Link:</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><span>Include Subscribe Email Link</span></legend>
						<label><input id="subscribe_link-yes" type="radio" name="subscribe_link" value="1">Yes</label>
						<label><input id="subscribe_link-no" type="radio" name="subscribe_link" checked="checked" value="0">No</label>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Subscribe Email Link Text:</th>
				<td>
					<input id="subscribe_link_text" name="subscribe_link_text" type="text" value="Subscribe me!" size="35">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Hours Back:</th>
				<td>
					<input id="hours_back" name="hours_back" type="text" value="" size="35">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Greeting Title</th>
				<td>
					<input id="greeting_title" name="greeting_title" type="text" value="" size="35">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Greeting Body</th>
				<td>
					<textarea id="greeting_body" name="greeting_body" cols="35"></textarea>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Signature Text</th>
				<td>
					<textarea id="signature_text" name="signature_text" cols="35"></textarea>
				</td>
			</tr>
		</table>
		<?php submit_button('Send Digest'); ?>
	</form>  
  </div> 
<?php	
}

add_action('admin_post_uvd_ccdp_send_digest', 'uvd_ccdp_send_digest_action');

function uvd_ccdp_filter_where( $where = '' ) {
	global $wpdb, $hoursBack;
	
	$tz = get_option('timezone_string');
	date_default_timezone_set( $tz );
   	$where .= " AND ".$wpdb->prefix."posts.post_date > '" . date('Y-m-d H:i:s', strtotime('-'.$hoursBack.' hours')) . "'";
	return $where;
}

function uvd_ccdp_go_back( $error_code = '' )
{
	if ( !empty($error_code) ) {
		set_transient('uvd_ccdp_error', $error_code, 30);
		$goback = add_query_arg( 'digest-sent', 'false',  wp_get_referer() );
	}
	else {
		$goback = add_query_arg( 'digest-sent', 'true',  wp_get_referer() );
	}
	wp_redirect( $goback );
	exit;
}

function uvd_ccdp_send_digest_action()
{
	global $hoursBack;
	$options = get_option( 'uvd_ccdp_options' );
	
	if ( empty($_POST) || !wp_verify_nonce($_POST['uvd_ccdp_nonce'], 'uvd_ccdp_sending_digest') )	{
		uvd_ccdp_go_back('Sorry, your nonce did not verify.');
	}
	
	try {
		$constantContact = CC_Singleton::getInstance();
	}
	catch (Exception $e) {
		$error_code = strip_tags($e->getMessage());
		uvd_ccdp_go_back($error_code);
	}
	
	$required = array('email_subject', 'from_name', 'salutation', 'greeting_name', 'greeting_string', 'lists', 'hours_back');
	foreach ($required as $post_array_key) {
		if ( empty($_POST[$post_array_key]) ) {
			uvd_ccdp_go_back('missing_field');
		}
	}
	
	$hoursBack = intval($_POST['hours_back']);
	$args = array(
		'numberposts'		=> -1,
		'suppress_filters'	=> 0,
		'post_type'			=> 'post',
		'post_status'		=> 'publish',
	);
	add_filter( 'posts_where', 'uvd_ccdp_filter_where' );
	$posts = get_posts($args);
	remove_filter( 'posts_where', 'uvd_ccdp_filter_where' );
	
	if ( empty($posts) ) {
		uvd_ccdp_go_back('no_posts');
	}
	
	$email_content = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';
	$email_content .= (!empty($options['masthead'])) ? '<p><img src="'.$options['masthead'].'"></p>' : '';
	$email_content .= $_POST['greeting_title'].'<br><br><greeting/><br><br>'.$_POST['greeting_body'] . '<br><br>';
	foreach ( $posts as $post ) {
		$email_content .= '<a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a><br>';
	}
	$email_content .= '<br><br>'.$_POST['signature_text'];
	$email_content .= '</body></html>';
	
	$params = array(
		'name'					=> 'Campaign '. time(),
		'subject'				=> $_POST['email_subject'],
		'fromName'				=> $_POST['from_name'],
		'vawp'					=> $_POST['webpage'] == 1 ? 'YES' : 'NO',
		'vawpLinkText'			=> $_POST['webpage_link_text'],
		'vawpText'				=> $_POST['webpage_text'],
		'greetingSalutation'	=> $_POST['salutation'],
		'greetingName'			=> $_POST['greeting_name'],
		'greetingString'		=> $_POST['greeting_string'],
		'incForwardEmail'		=> $_POST['forward_link'] == 1 ? 'YES' : 'NO',
		'forwardEmailLinkText'	=> $_POST['forward_link_text'],
		'incSubscribeLink'		=> $_POST['subscribe_link'] == 1 ? 'YES' : 'NO',
		'subscribeLinkText'		=> $_POST['subscribe_link_text'],
		'emailContent'			=> esc_html($email_content),
		'textVersionContent'	=> strip_tags($email_content),
		'lists'					=> $_POST['lists'],
	);
	$verifiedAddresses = $constantContact->getVerifiedAddresses();
	$i = 0;
	$fromEmail = null;
	$admin_email = strtolower(get_option('admin_email'));
	$email_pool = array();
	do {
		if ( $verifiedAddresses['addresses'][$i]->email == $admin_email && 
			$verifiedAddresses['addresses'][$i]->status == 'Verified' ) {
			$fromEmail = $verifiedAddresses['addresses'][$i];
		}
		elseif ( $verifiedAddresses['addresses'][$i]->status == 'Verified' ) {
			$email_pool[] = $verifiedAddresses['addresses'][$i];
		}
		$i++;
	}
	while ( !$fromEmail && $i < sizeof($verifiedAddresses['addresses']) );
	
	if ( empty($fromEmail) && !empty($email_pool) ) {
		$fromEmail = $email_pool[0];
	}
	
	if ( empty($fromEmail) ) {
		uvd_ccdp_go_back('email_not_verified');
	}
	
	$result = false;
	try {
		$campaign = new Campaign($params);
		$draftCampaign = @$constantContact->addCampaign($campaign, $fromEmail);
		/* need special access to that part of api */
		$draftCampaign->link = str_replace('http://api.constantcontact.com', '', $draftCampaign->id);
		date_default_timezone_set( 'UTC' );
		$result = @$constantContact->scheduleCampaign($draftCampaign, date('Y-m-d\TH:i:s', strtotime('+1 minute')).'.000Z');
	}
	catch (Exception $e) {
		$error = strip_tags($e->getMessage());
		uvd_ccdp_go_back($error);
	}
	
	/**
	 * Redirect back to the settings page that was submitted
	 */
	$error_code = $result == false ? 'shedule_fail' : ''; 
	uvd_ccdp_go_back($error_code);
}
