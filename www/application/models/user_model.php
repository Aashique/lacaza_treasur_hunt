<?php
class user_model extends CI_Model {

	/*Note:
		Roles are used to easily separate admins without duplicating code.
		-1 : Banned user, cannot view levels, or post answers. Rank is 0, hidden from leaderboard
		1  : Regular user, the only user type shown on the leaderboard
		8  : Admin - add levels, change own levels for debugging, can get user answer log and other details.
			 Does not show up in the leaderboard (Rank 0)
		10 : Super-admin, no extra powers than admin at the moment

		Any other number >1 as role can be used in the future for adding
		special permissions to users. Eg. a user who can view only logs
	*/
	
	function get_user() {
		//Get user's uid from facebook
		if(!session_id())
			session_start();
		$token = NULL;
		try {
			$helper = $this->facebookapi->getRedirectLoginHelper();
			$token  = $helper->getAccessToken();
			
			if(!$token) {
				//user is not logged in
				log_message('error', 'Accesstoken is null');
				$data['is_true'] = FALSE;
				return $data;
			}
		}
		catch(Facebook\Exceptions\FacebookResponseException $e) {
  			// When Graph returns an error
  			log_message('error', 'Graph returned an error: ' . $e->getMessage());
  			return [ 'is_true' => FALSE];
		} 
		catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		  log_message('error', 'Facebook SDK returned an error: ' . $e->getMessage());
		  return [ 'is_true' => FALSE ];
		}

		// catch (FacebookApiException $e){
		// 	//check log folder. log exceptions for debugging.
		// 	//logging can be changed or disabled in config.php
		// 	log_message('error', 'get_user in user_model got an exception');

		// 	//some api error occured. user is not logged in.
		// 	$data['is_true'] = FALSE;
		// 	return $data;
		// }
	
		//If all goes good, return true, with uid
		// $data['is_true'] = TRUE;
		// $data['facebook_uid'] = $query;
		// return $data;

		// @author Nilesh
		/** Control reaches here if we have a valid AccessToken. */
		$this->facebookapi->setDefaultAccessToken($token);
		try {
			$response = $this->facebookapi->get('/me?fields=email,name,id');
			$user = $response->getDecodedBody();
			log_message("debug", "Facebook Graph API Response : ".print_r($user, true));
                        $this->session->set_userdata('facebook_uid', $user['id']);
			//$this->facebookapi->cacheUserData($user);
			return [
				'is_true' => TRUE,
				'facebook_uid' => $user['id'],
			];
		}
		catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  	log_message('error', 'get_user(): Graph returned an error: ' . $e->getMessage());
			return ['is_true' => FALSE];
		} 
		catch(Facebook\Exceptions\FacebookSDKException $e) {
		  	// When validation fails or other local issues
		  	log_message('error', 'get_user(): Facebook SDK returned an error: ' . $e->getMessage());
		  	return ['is_true' => FALSE];
		}
	}

	function get_user_details(){
		//get details of the current user
		//used to fill in the database initially.
		$query = NULL;

		try {
			$query = $this->facebookapi->get("/me?fields=id,name,email")->getDecodedBody();
			if(!$query) {
				return 0;
			}
		}
		catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  	log_message('error', 'get_user_details() : Graph returned an error: ' . $e->getMessage());
			return 0;
		} 
		catch(Facebook\Exceptions\FacebookSDKException $e) {
		  	// When validation fails or other local issues
		  	log_message('error', 'get_user_details() : Facebook SDK returned an error: ' . $e->getMessage());
		  	return 0;
		}
		// catch (FacebookApiException $e){
		// 	log_message('error', 'get_user_details in user_model got an exception.');

		// 	return 0;
		// }

		$details = array(
			'facebook_uid' => $query['id'],
			'username' => $query['name'],
			'full_name' => $query['name'],
			'email' => @$query['email']
		);
		return $details;
	}

	function signed_in(){
		//check if user is new, or returning
		//function is called when user has signed in (or signed up)
		$data = NULL;

		try {
			$data = $this->facebookapi->get("/me?fields=id,name,email")->getDecodedBody();
			$this->session->set_userdata('fb_cached_details', $data);
			if(!$data) {
				return 0;
			}
		}
		catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  	log_message('error', 'signed_in() : Graph returned an error: ' . $e->getMessage());
			return 0;
		} 
		catch(Facebook\Exceptions\FacebookSDKException $e) {
		  	// When validation fails or other local issues
		  	log_message('error', 'signed_in() : Facebook SDK returned an error: ' . $e->getMessage());
		  	return 0;
		}
		// catch (FacebookApiException $e){
		// 	log_message('error', 'signed_in in user_model got an exception');
		// 	return 0;
		// }

		$this->db->from('users');
		$this->db->where('fb_uid',$data['id']);
		$sql = $this->db->get()->result();
		if (is_array($sql)&&count($sql)==1){
			//user already exists. just sign-in the user
			return 'signin';
		}else{
			//return signup, and the controller loads the view
			//to read first-time details
			return 'signup';
		}
	}

	function signup($details){
		//user just signed up. request personal information
		//and add them to our database

		// $query = NULL;

		// try {
		// 	$query = $this->facebook->api('/me');
		// 	if(!$query) {
		// 		return 0;
		// 	}
		// }catch (FacebookApiException $e){
		// 	log_message('error', 'get_user in user_model got an exception');
		// 	return 0;
		// }
		$query = $this->session->userdata('fb_cached_details');
		if(! $query)
			return 0;
		// try {
		// 	$query = $this->facebookapi->get("/me?fields=id,name,email")->getDecodedBody();
		// 	if(!$query) {
		// 		return 0;
		// 	}
		// }
		// catch(Facebook\Exceptions\FacebookResponseException $e) {
		//   // When Graph returns an error
		//   	log_message('error', 'signed_in() : Graph returned an error: ' . $e->getMessage());
		// 	return 0;
		// } 
		// catch(Facebook\Exceptions\FacebookSDKException $e) {
		//   	// When validation fails or other local issues
		//   	log_message('error', 'signed_in() : Facebook SDK returned an error: ' . $e->getMessage());
		//   	return 0;
		// }
		$data = array(
			'fb_name' => $query['name'],
			'fb_uid' => $query['id'],
			'level' => 1,
			'mobile' => $details['mobile'],
			'college' => $details['college'],
			'email' => $details['email'],
			'role' => '1'
		);

		if($data['fb_name']=='' || $data['fb_name']==NULL){
			$data['fb_name'] = strtolower($query['first_name'].$query['last_name']);
		}

		//make sure this user doesn't exist
		$this->db->from('users');
		$this->db->where('fb_uid',$query['id']);
		$sql = $this->db->get()->result();
		if (is_array($sql)&&count($sql)==1){
			//User already exists.
			//Prevent manipulating details, but log the user in
			return 1;
		}else{
			return $this->db->insert('users',$data);
		}
		
	}

	function get_role($fb_uid=''){
		//get role for current user or user with fb_uid=?
		if($fb_uid == ''){
			$fb_uid = $this->session->userdata('facebook_uid');
		}

		if($fb_uid=='' || $fb_uid==NULL){
			return 1;
		}

		$sql = "SELECT role FROM users WHERE fb_uid = ?"; 
		$query = $this->db->query($sql, $fb_uid);
		if ($query->num_rows() > 0)
		{
		   $row = $query->row();
		   return $row->role;
		}
	}


	function get_level($fb_uid=''){
		//get current level

		if($fb_uid == ''){
			$fb_uid = $this->session->userdata('facebook_uid');
		}

		if($fb_uid=='' || $fb_uid==NULL){
			return 0;
		}

		$sql = "SELECT level FROM users WHERE fb_uid = ?"; 
		$query = $this->db->query($sql, $fb_uid);

		if ($query->num_rows() > 0)
		{
		   $row = $query->row();
		   $current_level = $row->level;
		   return $current_level;
		}else{
			return 0;
		}
	}


	function read_user($fb_uid=''){
		//read user's details
		//this is used in profile (and in admin's users tab)

		if($fb_uid == ''){
			$fb_uid = $this->session->userdata('facebook_uid');
		}

		if($fb_uid=='' || $fb_uid==NULL){
			return false;
		}

		$this->load->model('status_model');
		$rank = $this->status_model->get_rank($fb_uid);

		$sql = "SELECT fb_name, level, mobile, college, email, role FROM users WHERE fb_uid = ?"; 
		$query = $this->db->query($sql, $fb_uid);
		if ($query->num_rows() > 0)
		{
		   $row = $query->row();
		   $data = array(
		   	'fb_uid' => $fb_uid,
		   	'fb_name' => $row->fb_name,
		   	'level' => $row->level,
		   	'mobile' => $row->mobile,
		   	'college' => $row->college,
		   	'email' => $row->email,
		   	'role' => $row->role,
		   	'rank' => $rank
		   	);

		   return $data;
		}else{
			return false;
		}

	}

	function edit_role(){
		//change role of a user
		//can be coded to promote a user to admin, or ban user
		//from the admin interface
		//at the moment, role has to be changed in the database from phpmyadmin 
	}

	function fb_post($type,$level=NULL){
		//post level ups and signups to Facebook timeline
		//Signup posting has not been implemented.
		//It can be done in the signup() function

		$uid = $this->session->userdata('facebook_uid');

		if($uid=='' || $uid==NULL){
			return 0;
		}

		$role=$this->session->userdata('role');

		if($role=='' || $role==NULL){
			return 0;
		}

		if($role!=1){
			//Not a regular user, do not post to facebook
			return;
		}
		
		$token = NULL;

		//type can be level or signup.
		//level is used when posting level ups.

		//get access token to be used in the api
		// try {
		// 	$token = $this->facebookapi->getAccessToken();
		// 	if(!$token) {
		// 		log_message('error', 'could not get access_token for fb_post in user_model');
		// 		return 0;
		// 	}
		// }catch (FacebookApiException $e){
		// 	log_message('error', 'could not get access_token for fb_post in user_model, exception thrown');
		// 	return 0;
		// }
		
		$token =$this->facebookapi->getDefaultAccessToken();
		if($type!='level'){
			$post = array(
				'access_token'  => $token,
				'message' => 'Try out an exciting online treasure hunt!',
				'link' => 'http://clueless.tathva.org'
				);
		}else{
			$post = array(
				'access_token'  => $token,
				'message' => 'I\'ve finished Level '.$level.' of this awesome online treasure hunt.',
				'link' => 'http://clueless.tathva.org'
				);
		}

		//posting is done using graph api, via CURL
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,'https://graph.facebook.com/' . $uid . '/feed');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  //to suppress the curl output
		$result = curl_exec($ch);
		curl_close($ch);

		return;
	}

	function chapter2(){
		//function to update users to level 21 upon request
		//this function is called from profile() in home controller.
		//does not change if user is above level 21

		$fb_uid = $this->session->userdata('facebook_uid');

		$sql = "SELECT level FROM users WHERE fb_uid = ?"; 
		$query = $this->db->query($sql, $fb_uid);

		if ($query->num_rows() > 0)
		{
		   $row = $query->row();
		   if($row->level < 21){
		   		$this->db->set('level', 21); 
		   		$this->db->where('fb_uid', $fb_uid);
		   		$this->db->set('passtime', 'NOW()', FALSE);
		   		$this->db->update('users'); 
		   }
		}
		return;
	}
}