<?php


class EmailArt{
	
	
	public function getemails($id)
			{
				global $wpdb;	
						$checkdb = $wpdb->get_row( 
		"SELECT * FROM ".$wpdb->prefix . 'db_details where id ='.$id
		);
			$host = $checkdb->db_host;
			$database = $checkdb->db_name;
			$user = $checkdb->db_user;
			$password = $checkdb->db_password;
			$prefix = $checkdb->db_prefix;

		
				$connect = mysql_connect($host, $user , $password );
				if(!$connect)
				{
				echo '<span class="unsuccess">Conection Error ! Please check Server Host and Credentials.</span>';
				die();	
				}
								
				$db = mysql_select_db($database);
				if(!$db)
				{				echo '<span class="unsuccess">Cannot access Database, Please check database name or authorized user for this database.</span>';
				die();
					
				}
				$query = mysql_query('select user_login, user_email from '.$prefix.'users');

				while($fetch = mysql_fetch_row($query))
				{
					
					
					$email = $fetch[1];
					$name = $fetch[0];
					$db_id = $id;
					
						$check = $wpdb->get_results( 
							"SELECT * FROM ".$wpdb->prefix . 'ea_emails where email="'.$email.'"'
						);
						
									

						
					if($wpdb->num_rows < 1)
					{
							echo '<span class="success"><b> '.$email.'</b> has been added.</span>';
							echo '<br>';
							$wpdb->insert( 
								$wpdb->prefix . 'ea_emails', 
								array( 
								'name' => $name, 
								'email' => $email,
								'site_id' => $db_id
								)
						);
					}
					else
					{
						
							echo '<span class="unsuccess"><b> '.$email.'</b> has been skipped. <br> Reason: Already in Database </span>';
							echo '<br>';
					}
					
				}
				
				
			}





public function save_db($host,$db,$user,$pass,$prefix,$db_id){

		global $wpdb;							

				$connect = mysql_connect($host, $user , $pass );
				if(!$connect)
				{
				echo '<span class="unsuccess">Conection Error ! Please check Server Host and Credentials.</span>';
				die();	
				}
								
				$database = mysql_select_db($db);
				if(!$database)
				{				echo '<span class="unsuccess">Cannot access Database, Please check database name or authorized user for this database.</span>';
				die();
					
				}
				$database_query = mysql_query('SELECT option_value
				FROM '.$prefix.'options
				WHERE option_name IN ("siteurl","blogname")
				');
				
				$i = 0;
				
				while($fetch_db = mysql_fetch_row($database_query))
				{
					$i = $i +1 ;
					if($i == 1 )
					{
					$db_blogname = $fetch_db[0];
					}
					
					if($i == 2 )
					{
					$db_siteurl = $fetch_db[0];
					}

				}

		$check_db = $wpdb->get_row( 
		"SELECT * FROM ".$wpdb->prefix . "db_details where id=".$db_id
		);


	
	if($wpdb->num_rows >0)
	{
		
		if($_GET['action'] == 'save_db')
		{
			$wpdb->update( 
			$wpdb->prefix . 'db_details', 
				array( 
			
			'site_name' => $db_blogname, 
			'site_url' => $db_siteurl, 
			'db_host' => $host, 
			'db_name' => $db, 
			'db_user' => $user, 
			'db_password' => $pass, 
			'db_prefix' => $prefix 
			),
				array( 'id' => $_GET['db_id'] ) 
			);

		}
	}
	else
	{
		
		if($_GET['action'] == 'save_db')
		{
			
	
			$wpdb->insert( 
		$wpdb->prefix . 'db_details', 
		array( 
			'site_name' => $db_blogname, 
			'site_url' => $db_siteurl, 
			'db_host' => $host, 
			'db_name' => $db, 
			'db_user' => $user, 
			'db_password' => $pass, 
			'db_prefix' => $prefix 
			)
						);
			
		}
	}


		$_SESSION['msg'] = '<span class="success">Database credentials updated successfully.</span>';

	}
	
	

public function trigger_emails(){


	global $wpdb;

	//GET CURRENT DATE AND TIME
	$current_time = date("Y-m-d H:i:s");
	
	//GET AMOUNTS OF EMAIL SENT IN LAST ACTIVITY, CHECK IF YOU HAVEN'T USED IT ALL
	$prev_count = $this->getsetting('remain');
	
	//GET TIME OF NEXT ACTIVITY WHICH IS BEEN SET IN 2ND LAST ACTIVITY AND ADD AN HOUR IN IT
	$prev_time = date( "Y-M-d H:i:s", strtotime( $check_schedule->nexttime ) + (60*5) );			
	
	//IF LAST HOUR IS GONE RESET THE LIMIT TO ZERO
	if ($current_time > $prev_time)
	{
	
	//RESET THE HOURLY LIMIT
	$prev_count = 0;
	$this->handleremain_email($prev_count);
	
	}


	//CHECK IF THERE IS ANY PENDING POST
	$get_p_post = $wpdb->get_row( 
		"SELECT * FROM ".$wpdb->prefix . "ea_posts where done=0 ORDER BY id asc LIMIT 1" 
	);
	
		
	
	if($wpdb->num_rows > 0)
	{

	//GET POST ID AND WP POST
	$post_id = $get_p_post->post_id;
	$id_post = $get_p_post->id;
	$post = get_post( $post_id );

	//CHECK IF USER WANTS A NOTIFICATION THEN SEND EMAIL TO HIM THAT TASK HAS INITIATED
	$notify_switch = $this->getsetting('notify');
	
	//CHECK IF ADMIN WANTS A NOTIFICATION
	if($notify_switch == 1)
	{
	
	//SEND EMAIL TO ADMIN	
	$this->send_notifyadmin('initiate',$post_id,'','');
	
	echo 'Email Sent - INITIATE';
	
	}


	//GET BIGGEST EMAIL ID INSTEAD OF CHECKING ALL ONE BY ONE
	
	$max_email = $wpdb->get_var("SELECT max(email_id) as max_email FROM ".$wpdb->prefix . "ea_emails_history where post_id=".$id_post );
		
	// MOVE FORWARD IF ANY POST IS PENDING TO SEND OUT
			
		if(!empty($max_email)){ 
		
				$get_emails = $wpdb->get_results( "SELECT id,email,name from ". $wpdb->prefix. "ea_emails where unsubscribe=0 and invalid_email=0 and id > ".$max_email );				

		
		}else
		{

				$get_emails = $wpdb->get_results( "SELECT id,email,name from ". $wpdb->prefix. "ea_emails where unsubscribe=0 and invalid_email=0 ");				

			
		}
	
		$email_count = $prev_count;

		foreach($get_emails as $get_email)
		{
	//FOR EACH LOOP STARTS TO SEND EMAIL ONE BY ONE
	
	echo 'Entered FOR LOOP';
			
			$email_id = $get_email->id;
			$email_name = $get_email->name;
			$email = $get_email->email;
			$allowedmails = $this->getsetting('emailcount');

			
	//CHECK IF YOU HAVE EMAILS LEFT IN YOUR HOURLY LIMIT YOU SET IN SETTINGS
			if($email_count <= $allowedmails )
			{
				
						// GET Post author ID					
							$author = $post->post_author; 
						// GET AUTHOR NAME 	
							$name = get_the_author_meta( 'display_name', $author );
						// GET POST TITLE 	 
							$title = $post->post_title; 
						// GET POST PERMALINK	
							$permalink = get_permalink( $post->ID ); 
	//SET SUBJECT FOR EMAIL
		
	$subject = $email_name.' CHECK OUT NEW POST : "'.$title.'"';
	
	//SET EMAIL BODY
	
	$message = '
	<h1 align="center">
	'.get_bloginfo( 'name' ).'
	</h1>
	
	<h2>New Post Notification</h2>
	
	<p style="font-size:14px; ">
	Hey '.$email_name.',
	<br>
	I have posted a new article "'.$title.'" on my site <a href="'.get_bloginfo( 'siteurl' ).'">'.get_bloginfo( 'name' ).' </a></p>
	<br>
	<b>Check it out:</b>
	<br>
	<h2 align="center">
	<a href="'.$permalink.'" style=" background:red; color:#FFF; border:1px solid #000; padding:"10px;> '.$title.'</a>
	</h2>
	<br>
	<p align="center" style="font-size:12px;">Direct Link: '.$permalink.'</p>
	<br>
	<p style="font-size:14px;">For more information visit us at '.get_bloginfo( 'siteurl' ).' </p>
	<br>
	<br>
	<p style="font-size:14px;">
	-
	<br>
	Regards,
	<br>
	'.$name.'
	</p>
	<br>
	<p align="center" style="font-size:10px;">
	<a href="'.get_bloginfo( 'siteurl' ).'/unsubscribe-email-artillery?id='.$email_id.'&email='.$email.'"> Unsubscribe</a> from future Notifications</p> ' ;
	
	//SET EMAIL HEADERS
	
	$headers = "MIME-Version: 1.0\r\n 
	Content-Type: text/html; charset=ISO-8859-1\r\n";
					
							
	//ADD EMAIL TO SEND EMAIL TO						
							$to = $email_name.' <'.$email.'>';
							$email_count = $email_count + 1;
							echo $email_count;

	//SEND EMAIL NOW						
							wp_mail( $to, $subject, $message, $headers );
							
	//UPDATE EMAIL HISTORY						
							$this->update_email_history($id_post , $email_id);
	

			}//CHECK IF EMAIL LIMITS STILL LEFT : IF END
			
			//BREAK THE FOREACH LOOP IF EMAIL LIMIT REACHED						
			if($email_count == $allowedmails )	
			{
			break;	
			}
			
		}//FOR EACH END
		
		
	//CHECK IF THIS POST IS DONE SENDING BUT STILL HAVE REMAINING HOURLY LIMIT
		if($email_count < $allowedmails )
		{
	//MARK THIS POST DONE
			$this->mark_post_done($id_post);
			
	//SET HOURLY LIMIT FOR NEXT SESSION IN SAME HOUR		
			$this->handleremain_email($email_count);
			
				
	//CHECK IF NOTIFICATION IS SWITCHED ON IN SETTINGS
			$notify_switch = $this->getsetting('notify');
					
			if($notify_switch == 1)
			{
				
	// SEND EMAIL 
					
				$this->send_notifyadmin('markdone',$post_id,'',$email_count);
				
			}
	// RUN THE HOURLY ACTIVITY AGAIN AS WE STILL HAS THE LIMIT 			
		//	etmbu_send_emails_hourly();
		
		}
	
	//CHECK IF THE HOURLY LIMIT IS CONSUMED 
		if($email_count == $allowedmails )
		{
	//SET THE NEXT ACTIVITY TIME
			$nexttime = date("Y-m-d H:i:s", strtotime("+5 minutes"));
			$this->update_email_schedule($nexttime);
	//CHECK IF ADMIN NOTIFICATIONS ARE ON IN SETTINGS
			$notify_switch = $this->getsetting('notify');
	
			
			if($notify_switch == 1)
			{
				
	//SEND EMAIL				
				
				$this->send_notifyadmin('nexttime',$post_id,$nexttime,$email_count);
				
			}
			
			
			$this->handleremain_email('0');
		
		}

		
		
		
		
	
	}//END PENDING POST EXIST IF
	else
	{
		//NO PENIDING POSTS TO SEND OUT
		
		//CHECK IF NOTIFICATIONS ARE ON IN SETTINGS 
		$notify_switch = $this->getsetting('notify');
		
		//CHECK IF ADMIN WANTS A NOTIFICATION
		if($notify_switch == 1)
		{
			//SEND EMAIL
			
			$this->send_notifyadmin('nopending','','','');
			
		}

		
	}//END IF : NO PENDING POST EXIST
	
	
}

	
	
	
public function insert_post_id($postofid){

	global $wpdb;



		$check_schedule = $wpdb->get_row( 
		"SELECT * FROM ".$wpdb->prefix . "ea_posts where post_id=".$postofid
		);


	
	if($wpdb->num_rows <1)
	{
		

		$wpdb->insert( 
			$wpdb->prefix . 'ea_posts', 
			array( 
				'post_id' => $postofid
				)
		);

	}

}



public function update_email_history($post_id, $email_id)
{
	global $wpdb;
	
	$wpdb->insert( 
		$wpdb->prefix . 'ea_emails_history', 
		array( 
			'post_id' => $post_id,
			'email_id' => $email_id
			)
	);

	
}



public function update_email_schedule($nexttime){

		global $wpdb;							
		$check_schedule = $wpdb->get_row( 
		"SELECT * FROM ".$wpdb->prefix . "ea_schedule_trigger where id =1"
		);


	
	if($wpdb->num_rows >0)
	{
		

			$wpdb->update( 
			$wpdb->prefix . 'ea_schedule_trigger', 
				array( 
			
			'nexttime' => $nexttime
			),
				array( 'id' => 1 ) 
			);

	}
	else
	{
			$wpdb->insert( 
			$wpdb->prefix . 'ea_schedule_trigger', 
			array( 
			'id' => 1 , 
			'nexttime' => $nexttime 
			)
						);
			
	}

		$_SESSION['msg'] = '<span class="success">Database credentials updated successfully.</span>';

	}


public function mark_post_done($post_id){

	global $wpdb;
	
			$wpdb->update( 
			$wpdb->prefix . 'ea_posts', 
				array( 
			
			'done' => 1
			),
				array( 'id' => $post_id ) 
			);

}



public function getallsites(){
	global $wpdb;

	if($_GET['action'] == 'delete_db')
	{
		$wpdb->delete( $wpdb->prefix . "db_details", array( 'id' => $_REQUEST['db_id'] ) );

		$wpdb->delete( $wpdb->prefix . "ea_emails", array( 'site_id' => $_REQUEST['db_id'] ) );

		
	}

?>
	<h1>All Sites</h1>
    <hr />
    
	<table>
    <tr>
    <th>Site Name</th><th>Users</th><th>Action</th>
    </tr>

    <tr>
    <td><a href="#" >This Site & Imported Emails</a></td>
    <td>
    <?php
	
    // users

    $total_query = 'SELECT COUNT(1) FROM '.$wpdb->prefix.'ea_emails where site_id=0';
    $total = $wpdb->get_var( $total_query );

	echo $total;	
	
    ?>
     Users
    </td>
    
    <td>No Action Available</td>
    
    </tr>

          
       
    <?php
	
		$details_db = $wpdb->get_results( 
		"SELECT * FROM ".$wpdb->prefix . 'db_details '
		);
		
		foreach($details_db as $detail_db)
		{
			$ea_site_name = $detail_db->site_name;
			$ea_site_url = $detail_db->site_url;
			$ea_site_id = $detail_db->id;
			if(empty($ea_site_name))
			{
				$ea_site_name = '(no name)';
				
			}
	
	?>
    
    <tr>
    <td><a href="<?php echo $ea_site_url; ?>" target="new"><?php echo $ea_site_name; ?></a></td>
    <td>
    <?php
	
    // users

    $total_query = 'SELECT COUNT(1) FROM '.$wpdb->prefix.'ea_emails where site_id='.$ea_site_id;
    $total = $wpdb->get_var( $total_query );

	echo $total;	
	
    ?>
     Users
    </td>
    
    <td><a href="?page=etmbu-add-new-site&db_id=<?php echo $ea_site_id; ?>">Edit</a> | <a href="?page=etmbu-all-sites-settings&action=delete_db&db_id=<?php echo $ea_site_id; ?>" onclick="return delete_confirm();">Delete</a></td>
    
    </tr>
            
            
    <?php
			
		}
		
	?>
    </table>


<?php	
	
	
}




public function manual_sync_emails(){
	
		global $wpdb;
	?>
    
        <h1>Sync Emails</h1>
        <hr />

    
    
    	<table>
                <form action="?page=etmbu-manual-email-sync&action=get_emails" method="post">

    <tr>
<td>Choose Site</td>
<td>
<select name="db_id">
<!---
//EDITING
-->
<option>This Site</option>
    <?php
	
		$details_db = $wpdb->get_results( 
		"SELECT * FROM ".$wpdb->prefix . 'db_details '
		);
		
		foreach($details_db as $detail_db)
		{
			$ea_site_name = $detail_db->site_name;
			$ea_site_id = $detail_db->id;
			if(empty($ea_site_name))
			{
				$ea_site_name = '(no name)';
				
			}
	?>
    
  
       <option value="<?php echo $ea_site_id; ?>"><?php echo $ea_site_name; ?> </option>     
            
    <?php
			
		}
		
	?>
</select>
      </td>
    
    </tr>
    <tr>
    <td>Initiate Manual Sync </td>
    
    <td><input type="hidden" value='1' name="submit_syncemails" ><input type="submit" value="Sync Emails"  />
    </tr>
    </form>
    </table>
<h4 align="center">Please Note: Do not close the window after initiating the Sync untill page finishes loading.</h4></td>
    
    <?php	
	
	if($_GET['action'] == 'get_emails' && !empty($_POST['submit_syncemails']) )
	{
		echo '<h2>Please Wait..</h2>';	
		
		if($_POST['db_id'] == 001)
		{
			$this-getemails_thissite();	
		}
		else
		{
			$db_id = $_POST['db_id'];
			$this->getemails($db_id);

			
		}
		

	}

	
	
	
	
	
	}

public function ea_update_setting(){
	
	global $wpdb;
	
	
		if($_GET['action'] == 'save_settings' && !empty($_POST['submit_settings']) )
	{
		
		$adminemail = $_POST['admin_email'];
		$email_count = $_POST['email_count'];
		$notify_switch = $_POST['notify_switch'];
		
		
		$this->save_settings($adminemail, $email_count, $notify_switch );

	}

	
	
		$check_settings = $wpdb->get_row( 
		'SELECT * FROM '.$wpdb->prefix . 'ea_settings where id=1'
	);

	
	?>
	
	    <h1> General Settings</h1>
<hr />
    
    
    <?php echo $_SESSION['msg']; $_SESSION['msg'] = '';  ?>
    <form action="?page=etmbu-email-artillery&action=save_settings" method="post">
    <table>
    <tr>
    <td>Admin Email</td>
    <td><input type="text" name="admin_email" value="<?php echo $check_settings->admin_email; ?>" ><br />


</td>
    </tr>
    <tr>
    <td>Emails Count<br />
<em>Emails to be sent out every 5 min</em>
</td>
    <td><input type="text" name="email_count" value="<?php echo $check_settings->email_count; ?>" ></td>
    </tr>
    <tr>
    <td>Notifications</td>
    <td><select name="notify_switch">
    <option value="0" <?php if( $check_settings->notify_switch == 0 ){ echo 'selected'; } ?> >Disable</option>
    <option value="1" <?php if( $check_settings->notify_switch == 1 ){ echo 'selected'; } ?>>Enable</option>
    </select></td>
    </tr>
    <tr>
    <td><input type="hidden" value='1' name="submit_settings" ></td>
    <td><input type="submit" value="Update Settings" ></td>    
    </table>
    
    </form>

	<?php
	
	
	}
	
	
	
public function save_settings($adminemail, $email_count, $notify_switch ){
	
	global $wpdb;
	
			$check_db = $wpdb->get_row( 
		"SELECT * FROM ".$wpdb->prefix . "ea_settings where id=1"
		);


	
	if($wpdb->num_rows >0)
	{
		
		if($_GET['action'] == 'save_settings')
		{
			$wpdb->update( 
			$wpdb->prefix . 'ea_settings', 
				array( 
			
			'admin_email' => $adminemail, 
			'email_count' => $email_count, 
			'notify_switch' => $notify_switch 
			),
				array( 'id' => 1 ) 
			);

		}
	}
	else
	{
		
		if($_GET['action'] == 'save_settings')
		{
			
	
			$wpdb->insert( 
		$wpdb->prefix . 'ea_settings', 
		array( 
			'id' => 1, 
			'admin_email' => $adminemail, 
			'email_count' => $email_count, 
			'notify_switch' => $notify_switch 
			)
						);
			
		}
	}


		$_SESSION['msg'] = '<span class="success">Settings updated successfully.</span>';

	
	
	
	
	
	}
	


public function getsetting($arg){
	
	global $wpdb;
				$check_settings = $wpdb->get_row( 
		'SELECT * FROM '.$wpdb->prefix . 'ea_settings where id=1'
	);
		
		if($arg == 'adminemail')
		{
			$getarg = 'admin_email';
			
		}

		if($arg == 'emailcount')
		{
			$getarg = 'email_count';
			
		}
		
		if($arg == 'notify')
		{
			$getarg = 'notify_switch';
			
		}


		if($arg == 'remain')
		{
			$getarg = 'remaining_emails';
			
		}
		
	
		$valarg = $check_settings->$getarg;
		return $valarg;
	} 



public function send_notifyadmin( $type , $post_id , $nexttime, $email_count){
	
	global $wpdb;
	
	
	
	if($type == 'markdone')
	{
		$post = get_post( $post_id );
		$title = $post->post_title;
    	$permalink = get_permalink( $post_id );

		$subject = 'Email Task Finished for Post "'.$title.'"';

		$message = 'Hey Admin, <br>
		<p>The task to send out emails about your post "<a href="'.$permalink.'">'.$title.'</a>" has just been completed. We have sent out emails to all the synced users in your databse. We will be looking for next task in upcoming 5 minutes.
		</p>
		<br>		
		
		<p>This Email has been sent automatically by "Email Artillery" wordpress plugin. To stop recieving notification emails follow the following steps:
		<br>
		1. Login to Wordpress Admin Panel (wp-admin)<br>
		2. Go to Email Artillery<br>
		3. Disable the "Notifications" in General Settings
		</p>
		<br>
		<br>
		<br>
		Regards,
		<br>
		 Email Artillery
		
		
		
		';
		
	}
	
	
		if($type == 'nopending')
	{
		$subject = 'No Pending Post to send out : Email Artillery';

		$message = 'Hey Admin, <br>
		<p>There is no pending task to send out. We will be checking for any pending posts in the next 5 Min.
		</p>
		<br>		
		
		<p>This Email has been sent automatically by "Email Artillery" wordpress plugin. To stop recieving notification emails follow the following steps:
		<br>
		1. Login to Wordpress Admin Panel (wp-admin)<br>
		2. Go to Email Artillery<br>
		3. Disable the "Notifications" in General Settings
		</p>
		<br>
		<br>
		<br>
		Regards,
		<br>
		 Email Artillery
		
		
		
		';
		
	}
	
	
	
	if($type == 'failure')
	{
		$post = get_post( $post_id );
		$title = $post->post_title;
    	$permalink = get_permalink( $post_id );

		$subject = 'Failed to send out "'.$title.'"';

		$message = 'Hey Admin, <br>
		<p>We are unable to initiate an Emailing task for your Post "<a href="'.$permalink.'">'.$title.'</a>" right now. You have already sent out emails in current 5 mins. Dont worry we have scheduled a task for it and will be completing it as soon as possible. Sit tight !
		</p>
		<br>		
		
		<p>This Email has been sent automatically by "Email Artillery" wordpress plugin. To stop recieving notification emails follow the following steps:
		<br>
		1. Login to Wordpress Admin Panel (wp-admin)<br>
		2. Go to Email Artillery<br>
		3. Disable the "Notifications" in General Settings
		</p>
		<br>
		 
				<br>
		<br>
		Regards,
		<br>
		 Email Artillery
		
		
		';
		
	}

	if($type == 'initiate')
	{
		$post = get_post( $post_id );
		$title = $post->post_title;
    	$permalink = get_permalink( $post_id );

		$subject = 'Initiated Every 5 Min Task for Post "'.$title.'"';

		$message = 'Hey Admin, <br>
		<p>We have initiated an Emailing task for your Post "<a href="'.$permalink.'">'.$title.'</a>" right now. You will be recieving an email on completion. Sit tight !
		</p>
		<br>		
		
		<p>This Email has been sent automatically by "Email Artillery" wordpress plugin. To stop recieving notification emails follow the following steps:
		<br>
		1. Login to Wordpress Admin Panel (wp-admin)<br>
		2. Go to Email Artillery<br>
		3. Disable the "Notifications" in General Settings
		</p>
		<br>
		 
				<br>
		<br>
		Regards,
		<br>
		 Email Artillery
		
		
		';
		
	}

	if($type == 'nexttime')
	{
		$post = get_post( $post_id );
		$title = $post->post_title;
    	$permalink = get_permalink( $post_id );
		$count = $email_count;
		

		$subject = 'Every 5 Min Task Completed for Post "'.$title.'"';

		$message = 'Hey Admin, <br>
		<p>We have successfully sent emails to '.$count.' people about your Post "<a href="'.$permalink.'">'.$title.'</a>". We will be running the next task on '.$nexttime.' Sit tight !
		</p>
		<br>		
		
		<p>This Email has been sent automatically by "Email Artillery" wordpress plugin. To stop recieving notification emails follow the following steps:
		<br>
		1. Login to Wordpress Admin Panel (wp-admin)<br>
		2. Go to Email Artillery<br>
		3. Disable the "Notifications" in General Settings
		</p>
		<br>
		 
				<br>
		<br>
		Regards,
		<br>
		Email Artillery
		
		
		';
		
	}
	
	
	$to = $this->getsetting('adminemail');
    $headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

	
	wp_mail( $to, $subject, $message, $headers );

	
	
	
	}



public function handleremain_email($count){
	
	global $wpdb;
	$wpdb->update( 
	$wpdb->prefix . 'ea_settings', 
	array( 
	
	'remaining_emails' => $count 
	),
	array( 'id' => 1 ) 
	);

	
	
	
}




public function getallposts(){
	
    global $wpdb;
	
	$getmarkdone = $_GET['markdone'];
	$post_id = $_GET['post_id'];
	
	$s = $_GET['s'];
	
	if($s == 'yes')
	{
		$s_val = 1;
		
	}

	if($s == 'no')
	{
		$s_val = 0;
		
	}

	if(!empty($getmarkdone))
	{
		
		$this->post_marking($post_id , $getmarkdone );
		
	}
	
	?>
    <h1>All Posts</h1>
    <hr />
    <h2><a href="?page=etmbu-all-posts&s=no">All Pending Posts</a> | <a href="?page=etmbu-all-posts&s=yes">All Completed Posts</a></h2> 
    <table>
    
    <tr>
    <th>Post Name</th> <th>Status</th> <th>Details</th> <th>Action</th>
    </tr>
    <?php

	if(!empty($s))
	{
		
    $query = 'SELECT * from '.$wpdb->prefix.'ea_posts where done='.$s_val;
		
		
	}
	

	if(empty($s))
	{
		
    $query = 'SELECT * from '.$wpdb->prefix.'ea_posts';
		
		
	}

	if(!empty($s))
	{
		
    $total_query = 'SELECT COUNT(1) FROM '.$wpdb->prefix.'ea_posts where done='.$s_val;
		
		
	}


	if(empty($s))
	{
		
    $total_query = 'SELECT COUNT(1) FROM '.$wpdb->prefix.'ea_posts';
		
		
	}


    $total = $wpdb->get_var( $total_query );
    $items_per_page = 10;
    $page = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
    $offset = ( $page * $items_per_page ) - $items_per_page;
    $latestposts = $wpdb->get_results( $query . " ORDER BY id asc LIMIT ${offset}, ${items_per_page}" );

    foreach ($latestposts as $latestpost) {
        $da_id = $latestpost->post_id;
        $post_id_id = $latestpost->id;
		
		$post = get_post( $da_id );
		
        $da_title = $post->post_title;
        $markdone = $latestpost->done;
        $da_date = $post->post_date;
		$permalink = get_permalink( $da_id );
		
		if($markdone == 0)
		{
			
			$status = 'Scheduled for Sending..';
			
		}

		if($markdone == 1)
		{
			
			$status = 'Successfully Sent.';
			
		}
		
		if(empty($_GET['cpage']))
		{
			$cpage = 1;
			
		}
		else
		{
			$cpage = $_GET['cpage'];
			
		}
?>
		<tr>
        <td><a href="<?php echo $permalink; ?> " target="new"><?php echo $da_title; ?></a></td>
        <td><?php echo $status; ?></td>
        <td>
        <a href="?page=etmbu-all-posts&post_id=<?php echo $post_id_id; ?>&cpage=<?php echo $cpage; ?>&s=<?php echo $s; ?>">View Details</a>
        </td>
        <?php 
			
				?>		
        <td>
<?php			if($markdone == 0)
			{
?>        <a href="?page=etmbu-all-posts&post_id=<?php echo $post_id_id; ?>&cpage=<?php echo $cpage; ?>&markdone=yes&s=<?php echo $s; ?>">Mark Done / Cancle</a>
        <?php
			}
			
			if($markdone == 1)
			{
?>        <a href="?page=etmbu-all-posts&post_id=<?php echo $post_id_id; ?>&cpage=<?php echo $cpage; ?>&markdone=no&s=<?php echo $s; ?>">Mark Pending</a>
        <?php
			}
			
		?>
         </td>
       

        </tr>
		
<?php

    }

?>
    </table>
<?php
			echo '<br>';	
			echo '<div id="pagina_custom" align="center">';	

    echo paginate_links( array(
        'base' => add_query_arg( 'cpage', '%#%' ),
        'format' => '',
        'prev_text' => __('&laquo; Previous'),
        'next_text' => __('Next &raquo;'),
        'total' => ceil($total / $items_per_page),
        'current' => $page
    ));
	
			echo '</div>';
			echo '<br>';	
			echo '<br>';	
			echo '<br>';	

	
	if(!empty($post_id))
	{
		
		?>
        <h1>Post Details</h1>
        <hr />
        <?php
			
    $total_query1 = 'SELECT COUNT(1) FROM '.$wpdb->prefix.'ea_emails_history where post_id='.$post_id;
    $total1 = $wpdb->get_var( $total_query1 );
	
    $items_per_page1 = 20;
    $page1 = isset( $_GET['dpage'] ) ? abs( (int) $_GET['dpage'] ) : 1;
    $offset1 = ( $page1 * $items_per_page1 ) - $items_per_page1;
	
	



		    $post_details = $wpdb->get_results( "SELECT * from ".$wpdb->prefix."ea_emails_history where post_id=".$post_id." ORDER BY id asc LIMIT ${offset1}, ${items_per_page1} " );

    $pos_detail = $wpdb->get_row( "SELECT * from ".$wpdb->prefix."ea_posts where id=".$post_id."" );


	$post1 = get_post( $pos_detail->post_id );
	
	$title_post = $post1->post_title;
	$permalink1 = get_permalink( $pos_detail->post_id );
		
		$mdone = $pos_detail->done;
		
		if($mdone == 1)
		{
			$stat = 'Sent and Finished';
			
		}

		if($mdone == 0)
		{
			$stat = 'Scheduled for Sending..';
			
		}

	?>
    <table>
    <tr>
    <td>Post Name</td><td> <a href="<?php echo $permalink1; ?>" target="new"><?php echo $title_post; ?> </a>
</td>
    </tr>
    <tr>
    <td>Sent to</td>
    <td>
     <?php echo $total1; ?> Users
    </td>
    </tr>
    <tr>
    <td>Status</td>
    <td>
    <?php echo $stat; ?>
    </td>
    </tr>
    <tr>
    <td>
    Emails <em>(Sent to)</em>:
    </td>
<td>    
	
    <?php

		foreach($post_details as $post_detail)
		{
			$emailid = $post_detail->email_id;
			
					    $email_detail = $wpdb->get_row( "SELECT * from ".$wpdb->prefix."ea_emails where id=".$emailid );
						
						?>
                        

               <?php echo  $email_detail->email; ?>
     
                 <br />

<?php
			
			
		}
		
?>
</td>
</tr>
    </table>
<br />

<div id="pagina_custom" align="center">
<?php

			
			
		    echo paginate_links( array(
        'base' => add_query_arg( 'dpage', '%#%' ),
        'format' => '',
        'prev_text' => __('&laquo; Previous'),
        'next_text' => __('Next &raquo;'),
        'total' => ceil($total1 / $items_per_page1),
        'current' => $page1
    ));
?>
</div>		
            
<?php            
	}
	
	
	
}



public function post_marking($post_id , $action ){
	
		global $wpdb;
	
		$check_post_exist = $wpdb->get_row( 
		"SELECT * FROM ".$wpdb->prefix . "ea_posts where id=".$post_id
		);

		if($action != 'yes' && $action != 'no')
		{
			$val = 1;
			
		}
		
		if($action == 'yes')
		{
			$val = 1;
			
		}

		if($action == 'no')
		{
			$val = 0;
			
		}
		
		

		if($wpdb->num_rows >0)
		{
		
			$wpdb->update( 
			$wpdb->prefix . 'ea_posts', 
				array( 
			
			'done' => $val 
			),
				array( 'id' => $post_id ) 
			);
			
			
			echo '<h2 class="success">Updated Successfully</h2>';
			echo '<br><br><br>';
			echo '<a href="?page=etmbu-all-posts&cpage='.$_REQUEST['cpage'].'&s='.$_REQUEST['s'].'">Go Back</a>';
			die();
			
	}
	else
	{
		echo '<h2 class="unsuccess">Oops, Something went wrong.</h2>';
		echo '<br><br><br>';
			echo '<a href="?page=etmbu-all-posts&cpage='.$_REQUEST['cpage'].'&s='.$_REQUEST['s'].'">Go Back</a>';
		die();
		
	}


	
	
	
	
	
	}

	
public function import_emails_txt()
{
	
	global $wpdb;
		
?>
<h1>Import Emails</h1>
	<?php


	if($_SERVER['REQUEST_METHOD'] != 'POST')
	{
		
	?>
<form enctype="multipart/form-data" method="post">
	<table>
	<tr>
		<td>
			<input type="file" name="txt_file" >
			<br>
			<em>(Only text files are supported, One email per line in .txt file) </em>
		</td>
	</tr>
		<tr>

			<td>
			<input type="submit" value="Import Emails" >
			</td>
		</tr>
			
		
	</table>	
	</form>
		<?php	

		}
	else{
		
    $upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $upload_dir = $upload_dir . '/ea_uploads/';
    if (! is_dir($upload_dir)) {
       mkdir( $upload_dir, 0755 );

		$f = fopen($upload_dir .".htaccess", "a+");
		fwrite($f, "Deny from all");
		fclose($f);
	
	}

	

	 $target_file = $upload_dir .date('d-m-Y-H-i-'). $_FILES["txt_file"]["name"];
	
	 if (move_uploaded_file($_FILES["txt_file"]["tmp_name"], $target_file)) {
        
		 ?>
	 <h3 style="color:green;" align="center">File Uploaded Successfully <br> Importing... Please Wait..</h3>
	 
		 <?php
		 
		 //$file = fopen($target_file , 'r');		 
		 $data = file_get_contents($target_file);
		 $data_array = explode(PHP_EOL , $data);
		 
		 foreach( $data_array as $email_singular )
		 {
			 
			 $check = $wpdb->get_results( 
							"SELECT * FROM ".$wpdb->prefix . 'ea_emails where email="'.$email_singular.'"'
						);
						
									

						
					if($wpdb->num_rows < 1)
					{
							$wpdb->insert( 
								$wpdb->prefix . 'ea_emails', 
								array( 
								'email' => $email_singular,
								'site_id' => 0
								)
										);
							echo '<span class="success"><b> '.$email_singular.'</b> has been added.</span>';
							echo '<br>';

					}
					else
					{
						
							echo '<span class="unsuccess"><b> '.$email_singular.'</b> has been skipped. <br> Reason: Already in Database </span>';
							echo '<br>';
					}
					
						 
		 }
		 
    } else {
		 ?>
        <h3 style="color: red;" align="center">Sorry, there was an error uploading your file.</h3>
    <?php
    }
			
	}
			
	
	
}


public function email_marking($email_id , $action ){
	
	global $wpdb;
	
			$check_db = $wpdb->get_row( 
		"SELECT * FROM ".$wpdb->prefix . "ea_emails where id=".$email_id
		);

		if($action != 'yes' && $action != 'no')
		{
			$val = 1;
			
		}
		
		if($action == 'yes')
		{
			$val = 1;
			
		}

		if($action == 'no')
		{
			$val = 0;
			
		}
		
	
	if($wpdb->num_rows >0)
	{
		
			$wpdb->update( 
			$wpdb->prefix . 'ea_emails', 
				array( 
			
			'invalid_email' => $val 
			),
				array( 'id' => $email_id ) 
			);
			
			$page = $_REQUEST['cpage'];
			
			if(empty($page))
			{
				
				$page = 1;
				
			}
			
			echo '<h2 class="success">Updated Successfully</h2>';
			echo '<br><br><br>';
			echo '<a href="?page=etmbu-all-emails&site_id='.$_REQUEST['site_id'].'&cpage='.$page.'&s='.$_REQUEST['s'].'">Go Back</a>';
			die();

	}
	else
	{
		echo '<h2 class="unsuccess">Oops, Something Went Wrong !</h2>';
			echo '<br><br><br>';
			echo '<a href="?page=etmbu-all-emails&site_id='.$_REQUEST['site_id'].'&cpage='.$page.'&s='.$_REQUEST['s'].'">Go Back</a>';
			die();
		
	}


	
	
	
	
	
	}
	
	
	
public function unsub_marking($email_id , $action ){
	
	global $wpdb;
	
			$check_db = $wpdb->get_row( 
		"SELECT * FROM ".$wpdb->prefix . "ea_emails where id=".$email_id
		);

		if($action != 'yes' && $action != 'no')
		{
			$val = 1;
			
		}
		
		if($action == 'yes')
		{
			$val = 1;
			
		}

		if($action == 'no')
		{
			$val = 0;
			
		}
		
	
	if($wpdb->num_rows >0)
	{
		
			$wpdb->update( 
			$wpdb->prefix . 'ea_emails', 
				array( 
			
			'unsubscribe' => $val 
			),
				array( 'id' => $email_id ) 
			);
			
			
			$page = $_REQUEST['cpage'];
			
			if(empty($page))
			{
				
				$page = 1;
				
			}
			
			echo '<h2 class="success">Updated Successfully</h2>';
			echo '<br><br><br>';
			echo '<a href="?page=etmbu-all-emails&site_id='.$_GET['site_id'].'&cpage='.$page.'&s='.$_REQUEST['s'].'">Go Back</a>';
			die();

	}
	else
	{
		echo '<h2>Oops, Something Went Wrong !</h2>';
			echo '<br><br><br>';
			echo '<a href="?page=etmbu-all-emails&site_id='.$_GET['site_id'].'&cpage='.$page.'&s='.$_REQUEST['s'].'">Go Back</a>';
			die();
		
	}


	
	
	
	
	
	}	






public function getallemails(){
	
    global $wpdb;
	
	$unsub_set = $_GET['unsub'];
	$invalid_set = $_GET['invalid'];
	
	$get_email_id = $_GET['email_id'];
	
	$s = $_REQUEST['s'];
	
			if(empty($_GET['cpage']))
		{
			$cpage = 1;
			
		}
		else
		{
			$cpage = $_GET['cpage'];
			
		}

	if(!empty($invalid_set))
	{
		$this->email_marking($get_email_id , $invalid_set );
		
		
	}
	
	if(!empty($unsub_set))
	{
		$this->unsub_marking($get_email_id , $unsub_set );
		
		
	}	

	?>
    <h1>All Emails</h1>
    <hr />
    
    <table>
    <form action="" method="post"> 
    <tr>
    <td>Choose Site</td>
    <td>
    <select name="site_id">
    <option value="0">This Site & Imported Emails</option>
    <?php
	
	    $getalldbs = $wpdb->get_results( 'SELECT * from '.$wpdb->prefix."db_details" );
		
		foreach($getalldbs as $getalldb )
		{
			
			?>
            <option value="<?php echo $getalldb->id; ?>">
           <?php 
		   echo $getalldb->site_name;
		   ?> 
            </option>
            
            <?php
			
		}

	
    ?>
    </select>
    </td>
    <td><input type="submit" value="Choose Site"  /></td>
    </tr>
 </form>   
    </table>
    
    <table>
    <form action="" method="post">
    <tr>
    <td>Search</td> 
    <td><input type="text" name="s" value="<?php echo $s; ?>" /></td>
    <td><input type="submit" value="Search"</td></td>
    </tr>
    </form>
    
    </table>
    
    <table>
    
    <tr>
    <th>Name</th> <th>Email</th> <th>Site Name</th> <th>Subscription / Validation</th> <th>Action</th>
    </tr>
    <?php
	$site_id = $_REQUEST['site_id'];




	if(!empty($site_id))
	{

	if(!empty($s))
	{
		$sq = ' and email LIKE "%'.$s.'%"';
		
	}
		
	    $query = 'SELECT * from '.$wpdb->prefix.'ea_emails where site_id='.$site_id.$sq;
	
	}
	
	if(empty($site_id))
	{
		if(!empty($s))
		{
		$sq = ' where email LIKE "%'.$s.'%"';
		
		}
		
	    $query = 'SELECT * from '.$wpdb->prefix.'ea_emails '.$sq;
	
	}
	
		if(!empty($site_id))
	{
		
			if(!empty($s))
	{
		$sq = ' and email LIKE "%'.$s.'%"';
		
	}

		
	    $total_query = 'SELECT COUNT(1) FROM '.$wpdb->prefix.'ea_emails where site_id='.$site_id.$sq;
	
	}
	
		if(empty($site_id))
	{
				if(!empty($s))
		{
		$sq = ' where email LIKE "%'.$s.'%"';
		
		}

		
	    $total_query = 'SELECT COUNT(1) FROM '.$wpdb->prefix.'ea_emails'.$sq;
	
	}
	


    $total = $wpdb->get_var( $total_query );
    $items_per_page = 50;
    $page = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
    $offset = ( $page * $items_per_page ) - $items_per_page;
    $latestposts = $wpdb->get_results( $query . " ORDER BY id LIMIT ${offset}, ${items_per_page}" );

    foreach ($latestposts as $latestpost) {
		
		

		
		$email_id = $latestpost->id;
		$name = $latestpost->name;
		$email = $latestpost->email;
		$unsub = $latestpost->unsubscribe;
		$invalid = $latestpost->invalid_email;
		
		$site = $latestpost->site_id;
		
		$get_db = $wpdb->get_row( 
		"SELECT * FROM ".$wpdb->prefix . "db_details where id=".$site
		);
		
		$site_name = $get_db->site_name;
		$site_url = $get_db->site_url;
		
		if($site_id == 0)
		{
		$site_name = 'This Site & Imported Emails';
		$site_url = '#';
			
		}
		
		if($unsub == 1)
		{
			$unsub_st = 'Unsubscribed';
			
		}
		if($unsub == 0)
		{
			$unsub_st = 'Subscribed';
			
		}
		if($invalid == 1)
		{
			$invalid_st = 'Invalid';
			
		}
		if($invalid == 0)
		{
			$invalid_st = 'Valid';
			
		}
		
		if(empty($name))
		{
			$name = '(no name)';
			
		}
		
?>

        <tr>
        <td> <?php echo $name; ?> </td>
        <td> <?php echo $email; ?> </td>
        <td> <a href="<?php echo $site_url; ?>"><?php echo $site_name; ?></a></td>
        <td> <?php echo $unsub_st; ?> / <?php echo $invalid_st; ?> </td>
        <td>
        <?php 
		if($unsub == 0){ ?> <a href="?page=etmbu-all-emails&cpage=<?php echo $_GET['cpage']; ?>&email_id=<?php echo $email_id; ?>&unsub=yes&site_id=<?php echo $_REQUEST['site_id']; ?>&s=<?php echo $s; ?>">Mark Un-Subscribed</a> <?php } 		if($unsub == 1){ ?> <a href="?page=etmbu-all-emails&cpage=<?php echo $_GET['cpage']; ?>&email_id=<?php echo $email_id; ?>&unsub=no&site_id=<?php echo $_REQUEST['site_id']; ?>&s=<?php echo $s; ?> ">Mark Subscribed</a> <?php }
?> / <?php
		if($invalid == 0){ ?> <a href="?page=etmbu-all-emails&cpage=<?php echo $_GET['cpage']; ?>&email_id=<?php echo $email_id; ?>&invalid=yes&site_id=<?php echo $_REQUEST['site_id']; ?>&s=<?php echo $s; ?> ">Mark Invalid</a> <?php } 		if($invalid == 1){ ?> <a href="?page=etmbu-all-emails&cpage=<?php echo $_GET['cpage']; ?>&email_id=<?php echo $email_id; ?>&invalid=no&site_id=<?php echo $_REQUEST['site_id']; ?>&s=<?php echo $s; ?> ">Mark Valid</a> <?php }

		?>
        </td>

        
        
        </tr>
		
<?php

    }

?>
    </table>
<?php
			echo '<br>';
			echo '<div id="pagina_custom" align="center">';	

    echo paginate_links( array(
        'base' => add_query_arg( 'cpage', '%#%' ),
        'format' => '',
        'prev_text' => __('&laquo; Previous'),
        'next_text' => __('Next &raquo;'),
        'total' => ceil($total / $items_per_page),
        'current' => $page
    ));
	
			echo '</div>';
			echo '<br>';	
			echo '<br>';	
			echo '<br>';	
}





public function search_posts_emails( ){
	
	global $wpdb;
	$type_s = $_POST['type'];
	$s = $_POST['search_for'];
	$p_s = $_POST['status'];
	
	?>
    <h1>Search </h1>
    <hr />
    
    <table>
    <form action="" method="post">
    
    <tr>
    <td>Search for</td>
    <td>
    <input type="text" name="search_for" value="<?php echo $s; ?>" />
    </td>
    </tr>
    
    <tr>
    <td>
    </td>
    <td><input type="submit" value="Search" /></td>
    </tr>
    
    </form>    
    </table>
    
    <?php
	

	
}



public function includefiles(){
	
		if ( !is_admin( ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	include 'include.php';
	
	
	
	
	}
	
	
public function getnexttime_run(){
	
	
	global $wpdb;
		global $wpdb;
		$check_nexttime = $wpdb->get_row( 
		'SELECT * FROM '.$wpdb->prefix . 'ea_schedule_trigger where id =1'
	);
	
	$nexttime = $check_nexttime->nexttime;
	return $nexttime;
	
}




public function send_bugs_reports($name , $email , $message){
	
	global $wpdb;
	

	$to = 'support@xpertsol.org';
	$subject = 'Bugs Report (Inside Plugin)';
	$headers = "From: ".$name." < ".$email." > \r\n ".
    "Reply-To: ".$email. "\r\n" .
    "X-Mailer: PHP/" . phpversion();
    $headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

	
	wp_mail( $to, $subject, $message, $headers );

	echo '<p class="success">Email Sent. Sit Tight</p>';
	echo '<br />';
	echo '<a href="?page=report-bugs-email-artillery">Go Back<a>';
	
	}
	
	
	
	
	public function getemails_thissite()
			{
				global $wpdb;	
				
				
				
				$query = 'select user_login, user_email from '.$wpdb->prefix.'users';

				while($fetch = $wpdb->get_results($query))
				{
					
					
					$email = $fetch[1];
					$name = $fetch[0];
					$db_id = 001;
					
						$check = $wpdb->get_results( 
							"SELECT * FROM ".$wpdb->prefix . 'ea_emails where email="'.$email.'"'
						);
						
									

						
					if($wpdb->num_rows < 1)
					{
							$wpdb->insert( 
								$wpdb->prefix . 'ea_emails', 
								array( 
								'name' => $name, 
								'email' => $email,
								'site_id' => $db_id
								)
										);
							echo '<span class="success"><b> '.$email.'</b> has been added.</span>';
							echo '<br>';

					}
					else
					{
						
							echo '<span class="unsuccess"><b> '.$email.'</b> has been skipped. <br> Reason: Already in Database </span>';
							echo '<br>';
					}
					
				}
				
				
			}

	





}

?>