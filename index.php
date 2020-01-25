<?php 			 

/**
 * @package email-artillery
 * @version 4.0
 */
/*
Plugin Name: Email Artillery
Plugin URI: http://xpertsol.org/email-artillery/
Description: Email Artillery is a plugin to Sync users with other wordpress sites and send emails to all of them automatically when new post is published.
Version: 4.0
Author: Xpert Solution
Author URI: http://xpertsol.org/
*/

//SET TIME LIMIT
set_time_limit(50000);

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
register_activation_hook( __FILE__, 'etmbu_install' );
register_activation_hook(__FILE__, 'etmbu_sendemail_activation');
register_deactivation_hook(__FILE__, 'etmbu_sendemail_deactivation');
add_action( 'plugins_loaded', 'etmbu_update_db_check' );
add_shortcode( 'unsub_ea' , 'etmbu_unsub_shortcode' );
add_action( 'publish_post', 'etmbu_publish_notify', 10, 2 );
add_action('sendingemails_event_fm', 'etmbu_send_emails_fivem');
add_filter('cron_schedules','etmbu_my_cron_schedules');
add_action('admin_menu', 'etmbu_email_artillery');





ignore_user_abort(true);
global $etmbu_db_version;
$etmbu_db_version = '1.0';

function etmbu_install() {
	global $wpdb;
	global $etmbu_db_version;

//Create table for db details
	
	$charset_collate = $wpdb->get_charset_collate();

	$table_name1 = $wpdb->prefix . 'db_details';

	$sql1 = "CREATE TABLE $table_name1 (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		site_name tinytext NOT NULL,
		site_url tinytext NOT NULL,
		db_host tinytext NOT NULL,
		db_name tinytext NOT NULL,
		db_user tinytext NOT NULL,
		db_password tinytext NOT NULL,
		db_prefix tinytext NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";


//Create table for sync emails

	$table_name2 = $wpdb->prefix . 'ea_emails';

	$sql2 = "CREATE TABLE $table_name2 (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name tinytext NOT NULL,
		email tinytext NOT NULL,
		site_id mediumint(9) NOT NULL,
		unsubscribe mediumint(9) NOT NULL,
		invalid_email mediumint(9) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";


//Create table for posts ids

	$table_name3 = $wpdb->prefix . 'ea_posts';

	$sql3 = "CREATE TABLE $table_name3 (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		post_id mediumint(9) NOT NULL,
		done mediumint(9) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";



//Create table for email sending and history

	$table_name4 = $wpdb->prefix . 'ea_emails_history';

	$sql4 = "CREATE TABLE $table_name4 (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		post_id mediumint(9) NOT NULL,
		email_id mediumint(9) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

//Create table for email sending and history

	$table_name5 = $wpdb->prefix . 'ea_schedule_trigger';

	$sql5 = "CREATE TABLE $table_name5 (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		nexttime tinytext NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";


//Create table for db details
	
	$table_name6 = $wpdb->prefix . 'ea_settings';

	$sql6 = "CREATE TABLE $table_name6 (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		admin_email tinytext NOT NULL,
		notify_switch mediumint(9) NOT NULL,
		email_count mediumint(9) NOT NULL,
		remaining_emails mediumint(9) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";


	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql1 );
	dbDelta( $sql2 );
	dbDelta( $sql3 );
	dbDelta( $sql4 );
	dbDelta( $sql5 );
	dbDelta( $sql6 );

	add_option( 'etmbu_db_version', $etmbu_db_version );
}

    $the_page_title = 'Unsubscribe Email Artillery';
    $the_page_name = 'unsubscribe-ea';

    $the_page = get_page_by_title( $the_page_title );

    if ( ! $the_page ) {

        // Create post object
        $_p = array();
        $_p['post_title'] = $the_page_title;
        $_p['post_content'] = "[unsub_ea]";
        $_p['post_status'] = 'publish';
        $_p['post_type'] = 'page';
        $_p['comment_status'] = 'closed';
        $_p['ping_status'] = 'closed';
        $_p['post_category'] = array(1); // the default 'Uncatrgorised'

        // Insert the post into the database
        $the_page_id = wp_insert_post( $_p );

    }
    else {
        // the plugin may have been previously active and the page may just be trashed...

        $the_page_id = $the_page->ID;

        //make sure the page is not trashed...
        $the_page->post_status = 'publish';
        $the_page_id = wp_update_post( $the_page );

    }

    delete_option( 'unsub_ea_page_id' );
    add_option( 'unsub_ea_page_id', $the_page_id );


function etmbu_update_db_check() {
    global $etmbu_db_version;
    if ( get_site_option( 'etmbu_db_version' ) != $etmbu_db_version ) {
        etmbu_install();
    }
}



if( empty( get_option( 'etmbu-my-acf-notice-dismissed' ) ) ) {
  add_action( 'admin_notices', 'etmbu_my_acf_admin_notice' );
}

function etmbu_my_acf_admin_notice() {
    ?>
    <div class="notice error etmbu-my-acf-notice is-dismissible" >
        <p><?php _e( '<b>Email Artillery</b> <br /> 1. <a href="'.admin_url().'admin.php?page=report-bugs-email-artillery">Click Here</a> to Let us know if you find any bugs. <br /> 2. <a target="new" href="https://wordpress.org/support/plugin/email-artillery/reviews/">Click Here</a> to Rate our Plugin. <br/>
		
		3. <b>Buy Developers a cup of coffee</b> <br />
<a href="https://www.2checkout.com/checkout/purchase?sid=102822373&quantity=1&product_id=5" target="new">Buy Now</a> ', 'email-artillery' ); ?></p>
    </div>


    <?php
}




function etmbu_email_artillery(){

	
	add_menu_page( 'Email Artillery', 'Email Artillery', 'manage_options', 'etmbu-email-artillery', 'etmbu_email_artillery_main');

	add_submenu_page ( 'etmbu-email-artillery', 'Sites Management', 'Site Management', 'manage_options', 'etmbu-all-sites-settings', 'etmbu_all_sites_settings' );

	add_submenu_page ( 'etmbu-email-artillery', 'Add Site', 'Add Site', 'manage_options', 'etmbu-add-new-site', 'etmbu_add_new_site' );

	add_submenu_page ( 'etmbu-email-artillery', 'All Posts', 'All Posts', 'manage_options', 'etmbu-all-posts', 'etmbu_all_posts' );


	add_submenu_page ( 'etmbu-email-artillery', 'Manual Trigger', 'Manual Trigger', 'manage_options', 'etmbu-testng-email', 'etmbu_testing' );


	add_submenu_page ( 'etmbu-email-artillery', 'All Emails', 'All Emails', 'manage_options', 'etmbu-all-emails', 'etmbu_all_emails' );


	add_submenu_page ( 'etmbu-email-artillery', 'Sync Emails', 'Sync Emails', 'manage_options', 'etmbu-manual-email-sync', 'etmbu_manual_sync' );

	add_submenu_page ( 'etmbu-email-artillery', 'Import Emails', 'Import Emails', 'manage_options', 'etmbu-import-email-manual', 'etmbu_manual_import' );
	
	
	add_submenu_page ( 'etmbu-email-artillery', 'Report Bugs', 'Report Bugs', 'manage_options', 'report-bugs-email-artillery', 'etmbu_bugs_email_artillery' );


	add_submenu_page ( 'etmbu-email-artillery', 'About', 'About', 'manage_options', 'about-email-artillery', 'etmbu_about_email_artillery' );

}


require('classes.php');







function etmbu_email_artillery_main()
{


		$easettings = new EmailArt();
		$easettings->includefiles();
		$easettings->ea_update_setting();
	
	

	
}


function etmbu_manual_import()
{
	
	$eaimport = new EmailArt();
	$eaimport->includefiles();
	$eaimport->import_emails_txt();

	
	
	
}


function etmbu_all_sites_settings(){


		$eagetallsites = new EmailArt();
		$eagetallsites->includefiles();
		$eagetallsites->getallsites();



	
}


function etmbu_add_new_site(){
	

		global $wpdb;	
		$eagetemails = new EmailArt();
		$eagetemails->includefiles();
		$db_id = sanitize_text_field($_GET['db_id']);
		
				if(!empty($_POST['submit_savedb']))
		{
			$host = sanitize_text_field($_POST['host']);
			$db = sanitize_text_field($_POST['db']);
			$user = sanitize_text_field($_POST['user']);
			$pass = sanitize_text_field($_POST['password']);
			$prefix = sanitize_text_field($_POST['prefix']);
			
	
		$eagetemails->save_db($host,$db,$user,$pass,$prefix, $db_id);
		}

								
		$check_db = $wpdb->get_row( 
		"SELECT * FROM ".$wpdb->prefix . 'db_details where id ='.$db_id
		);
		

		
	?>



    <h1> <?php if(empty($_GET['db_id'])){ echo 'Add'; }else{ echo 'Edit'; } ?> Site:</h1>
<hr />
    
    
    <?php echo $_SESSION['msg']; $_SESSION['msg'] = '';  ?>
    <form action="?page=etmbu-add-new-site&action=save_db<?php 		if(!empty($_GET['db_id'])){ echo '&db_id='.$db_id;}
 ?>" method="post">
    <table>
    <tr>
    <td>Host</td>
    <td><input type="text" name="host" value="<?php echo $check_db->db_host; ?>" ></td>
    </tr>
    <tr>
    <td>Database</td>
    <td><input type="text" name="db" value="<?php echo $check_db->db_name; ?>" ></td>
    </tr>
    <tr>
    <td>Username</td>
    <td><input type="text" name="user"  value="<?php echo $check_db->db_user ?>" ></td>
    </tr>
    <tr>
    <td>Password</td>
    <td><input type="text" name="password" value="<?php echo $check_db->db_password; ?>" ></td>
    </tr>
    <tr>
    <td>Database Prefix</td>
    <td><input type="text" name="prefix" value="<?php echo $check_db->db_prefix; ?>" ></td>
    </tr>
    <tr>
    <td><input type="hidden" value='1' name="submit_savedb" ></td>
    <td><input type="submit" value="Update Database" ></td>    
    </table>
    
    </form>
	<?php
	
}



function etmbu_publish_notify( $ID, $post ) {

	global $wpdb;
	$eatriggeremails = new EmailArt();
	$eatriggeremails->insert_post_id( $ID );

}




 
  // add once 5 minute interval to wp schedules
function etmbu_my_cron_schedules($schedules) {
 
    if(!isset($schedules["5min"])){
        $schedules["5min"] = array(
            'interval' => 5*60,
            'display' => __('Once every 5 minutes'));
    }
    if(!isset($schedules["30min"])){
        $schedules["30min"] = array(
            'interval' => 30*60,
            'display' => __('Once every 30 minutes'));
    } 
    return $schedules;
  }




//SET 5 MIN TASK IF NOT SET AUTOMATICALLY
if(!wp_get_schedule('sendingemails_event_fm')){
    add_action('init', 'etmbu_sendemail_activation',10);
} 

//CLEAR HOURLY TASK
if(wp_get_schedule('sendingemails_event')){
wp_clear_scheduled_hook( time() , 'hourly', 'sendingemails_event');
wp_clear_scheduled_hook( time() , '5min', 'sendingemails_event');
wp_clear_scheduled_hook( time() , '5mins', 'sendingemails_event');
wp_clear_scheduled_hook( time() , 'minute_5', 'sendingemails_event');

}

//RUNS AT PLUGIN ACTIVATION
function etmbu_sendemail_activation() {
	
    wp_schedule_event(time(), '5min', 'sendingemails_event_fm');
	
	
}

//RUNS AT PLUGIN DEACTIVATION
function etmbu_sendemail_deactivation() {
	wp_clear_scheduled_hook( time() , '5min', 'sendingemails_event_fm');

}


function etmbu_testing(){
	
		$eagetemails = new EmailArt();
		$eagetemails->includefiles();

	if($_REQUEST['manual'] == 'yes')
	{
	etmbu_send_emails_fivem();
	}
	
	?>
<h1>Manual Trigger</h1>
<hr />
<center>
<p align="center" class="page-numbers"> DO NOT INITIATE IF YOU DON'T KNOW ABOUT YOUR HOURLY EMAIL LIMITS </p>    
</center>    <table>
    <form action="" method="post">
    <tr>
    <td>Initiate Manual Email Sending
    <input type="hidden" name="manual" value="yes"  />
    </td>
    <td><input type="submit" value="Initiate"  /></td>
    </tr>
    </form>
    </table>
    
    <?php
	
	
	
	
	}


function etmbu_send_emails_fivem() {
	
	// Send Emails every 5 min
	
	global $wpdb;
	$email_send = new EmailArt;				
	$email_send->trigger_emails();
					
		



}



function etmbu_manual_sync(){
	
	
	
	
		$eamanualsync = new EmailArt();
		$eamanualsync->includefiles();
		$eamanualsync->manual_sync_emails();

	
	
	}

function etmbu_manual_sync_thisiste(){
	
	
	
	
		$eamanualsync = new EmailArt();
		$eamanualsync->includefiles();
		$eamanualsync->getemails_thissite();

	
	
}






function etmbu_unsub_shortcode()
{
	
global $wpdb;


$id = $_GET['id'];
$email = $_GET['email'];
$confirm = $_POST['confirm'];


if(!empty($id) && !empty($email) && $confirm == 1 )
{
		$check_db = $wpdb->get_row( 
		"SELECT * FROM ".$wpdb->prefix . "ea_emails where id=".$id.' and email = "'.$email.'" and unsubscribe =0'
		);


	
	if($wpdb->num_rows >0)
	{
		
			$wpdb->update( 
			$wpdb->prefix . 'ea_emails', 
				array( 
			
			'unsubscribe' => 1
			),
				array( 'id' => $id ) 
			);
?>

            <h2>Un-subscribed Successfully.</h2>

<?php
	}
	else
	{
			?>
            
            <h2>Something went wrong !</h2>
            <?php
	}

}

	?>
    
    <table>
    <form action="" method="post">
    <tr>
    <td>Email</td>
    <td><input type="email" name="email" value="<?php echo $email; ?>"  readonly="readonly" required="required"  />
    <input type="hidden" value="1" name="confirm"
    </td>
    </tr>
    <tr>
    <td></td>
    <td>
    <input type="submit" value="Unsubscribe"  />
    </td>
    
    </tr>
    </form>
    </table>
    
    <?php



	
}


function etmbu_all_posts(){
	
	
		$eagetallposts = new EmailArt();
		$eagetallposts->includefiles();
		$eagetallposts->getallposts();

	
	
}


function etmbu_all_emails(){
	
	
		$eagetallemails = new EmailArt();
		$eagetallemails->includefiles();
		$eagetallemails->getallemails();

	
	
}



function etmbu_bugs_email_artillery(){

		$name = $_POST['name'];
		$email = $_POST['email'];
		$message = $_POST['message'];
		$eareportbugs = new EmailArt();
		$eareportbugs->includefiles();
		
		if($_POST['bugs'] == '1')
		{
			$eareportbugs->send_bugs_reports($name , $email , $message);
			
		}

		
		if(empty($_POST['bugs']))
		{
		?>
    <h1>Report Bugs</h1>
    <hr />
    <table>
    <form action="" method="post">
    <tr>
    <td>Name</td>
    <td><input type="text" name="name" placeholder="Enter you name" required="required" /></td>
    </tr>
    <tr>
    <td>Email</td>
    <td><input type="email" name="email" required="required" placeholder="Enter your email"  />
    <input type="hidden" value="1" name="bugs"  />
    </td>
    </tr>
    <tr>
    <td>Bugs / Suggestions </td>
    <td>
    <textarea name="message" placeholder="Enter your message here.." cols="35" rows="10"></textarea>
    </td>
    </tr>
    <tr>
    <td></td>
    <td>
    <input type="submit" value="SEND"  />
    </td>
    
    </tr>
    </form>
    </table>
    
    <?php
		}

	
	
	
	
	
	}




function etmbu_about_email_artillery()
{
		$eaabout = new EmailArt();
		$eaabout->includefiles();

	?>
    <h1>About Email Artillery</h1>
    <hr />
    For any queries contact us @ support@xpertsol.org<br />
    Or just go to Report Bugs section.
 We would appreciate if you report any bugs or send us improvement suggestions.
<br />

	<h2>How to Use?</h2>
    <ul>
    <li>
    Set 'Admin Email' , 'Emails Count' (Every 5 Min Emails to be sent out), Select 'Notification' (as per your need) in General Settings of 'Email Artillery'
    </li>
    <li>
    Go to 'Add a Site' in 'Email Artillery' to add your other wordpress sites or the one you installed this plugin. (We do not currently import emails automatically) 
    </li>
    <li>
    Go to 'Sync Emails' in 'Email Artillery', Select site and click on 'Sync Emails' button
    </li>
    <li>
    Now, Post anything to your blog and Go to 'All Posts' in 'Email Artillery' and click on 'View Details' to see how many emails has been sent and to who. 
    </li>
    <li>
    Get along with all other features of our plugin and let us know if you have any trouble using it. 
    </li>
    </ul>
    

    <?php

	
}