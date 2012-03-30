<?php
/**
 * users.class.php
 *
 * @author Analogrithems <Analogrithems@gmail.com>
 * @version 0.1-Dev
 * @license http://www.analogrithems.com/rant/portfolio/project-licensing/
 */


/**
 *
 * This is a class to work with the Wordpress users.  It gives an easy to use interface to import and export uer records 
 *
 *
 *
 * @package Wordpress eCommerce Datafeeder
 * @subpackage WPEC_Users
 */


class WPEC_Users extends WPEC_ecommerce_feeder{

	var $users;
	var $profiles;
	
	var $user_fields = array('user_login'=>'username', 'user_nicename'=>'nickname','user_email'=>'email', 
				'user_registered'=>'registered_date', 'display_name'=>'display_name');
	var $user_meta = array('last_name', 'first_name', 'nickname', 'aim', 'yim', 'description', 'jabber');
	var $wpec_meta = array();
	var $export_fields = array();

	function __construct(){
		$this->setCheckoutArray();
		parent::__construct();
	}

	/**
	 * updateUsers($users)
	 *
	 * Take an array of users and start updating them in the system
	 *
	 * @param mixed $users
	 * @return mixed $result statistics
	 */
	function updateUsers($users){
                wp_reset_query();
                global $wpdb;
                set_time_limit(0);

                $r = 0;
		$results = array('updated'=>0, 'added'=>0);
                
                foreach($users as $row){
			$r++;
			//Scrub non-utf8 data
			foreach($row as $key=>$val){
				$row[$key] = iconv("UTF-8", "ISO-8859-1//IGNORE", $val);
			}
			
			if($this->isGood($row['username'])){
				$username = $row['username'];
				$row['user_login'] = $row['username'];
				unset($row['username']);
			}elseif(!$this->isGood($row['username']) && $this->isGood($row['email']) ){
				$username = $row['email'];
				$row['user_login'] = $row['email'];
			}elseif($this->isGood($row['user_login'])){
				$username = $row['user_login'];
			}
			if($this->isGood($row['password'])){
				$password = $row['password'];
				$row['user_pass'] = $password;
				unset($row['password']);
			}elseif($this->isGood($row['encrypted_password'])){
				$encrypted = $row['encrypted_password'];
				unset($row['encrypted_password']);
				$row['user_pass']=$encrypted;
			}

			if($this->isGood($row['nickname'])){
				$nickname = $row['nickname'];
			}

			if($this->isGood($row['display_name'])){
				$displayName = $row['display_name'];
			}
				
			if($this->isGood($row['email'])){
				$email = $row['email'];
				$row['user_email'] = $row['email'];
				unset($row['email']);
			}elseif($this->isGood($row['user_email'])){
				$email = $row['user_email'];
			}
			
			foreach($row as $column=>$value){
				if(preg_match('/^meta_/', $column) >0){
					$user_meta[$column] = $value;
				}else if(preg_match('/^billing_/i',$column) > 0){
					$user_wpec[$column] = $value;
				}else if(preg_match('/^shipping_/i',$column) > 0){
					$user_wpec[$column] = $value;
				}else{
					$user[$column] = $value;
				}
			}
				
			$user_id = username_exists( $username );
			$this->logger->info("Looking up user: ".print_r($username,1).". Result=".print_r($user_id,1));
			if ( !$user_id ) {
				if(!$this->isGood($user['user_pass'])){
					$user['user_pass'] = wp_generate_password( 12, false );
				}
				$user_id = wp_insert_user( $user );
				if(is_numeric($user_id)){
					$results['added']++;
					$_SESSION['status_msg'] .= __("Adding New User: ",'ecommerce_feeder').$username.' Email: '.$email;
					$this->logger->info('Adding new user: '.$username.' Email: '.$email);
				}else{
					$_SESSION['error_msg'] .= __("Failed Adding New User: ",'ecommerce_feeder').print_r($user,1);
				}
					
			} else {
				if(is_numeric($user_id)){
					$user['ID'] = $user_id;
					if(wp_insert_user($user)){
						$_SESSION['status_msg'] .= __("Updating User: ",'ecommerce_feeder').$username.' Email: '.$email;
						$this->logger->info('Updating user: '.$username.' Email: '.$email);
					}else{
						$_SESSION['error_msg'] .= __("Failed Adding New User: ",'ecommerce_feeder').print_r($user,1);
						$this->logger->warn('Failed to udpate user:'.$username.' with '.print_r($row,true));
					}
					$results['updated']++;
				}
				
			}

			$update_wpec = false;
			foreach($user_wpec as $field=>$value){
				$field = substr($field,5);
				if($wpec = array_search($field,$this->wpec_meta)){
					$wpec_data[$wpec] = $value;
					$update_wpec = true;
				}else{
					if($res = update_user_meta($user_id, $field, $value)){
						$this->logger->warn("Failed to update user_id:".print_r($user_id,1)." meta:".print_r($field,1)."=".print_r($value,1)."\nFrom:".print_r($row,true).':Result was:'.print_r($res,1));
					}
				}
			}
			if($this->isGood($encrypted)){
				$this->setEncryptedPassword($user_id,$encrypted);
				$this->logger->info("Setting previously encrypted password: {$username}={$encrypted}");
			}
			//TODO handle billing & shipping address
						
			if($update_wpec && isset($wpec_data) && !empty($wpec_data)) update_user_meta($user_id, 'wpshpcrt_usr_profile', $wpec_data);

		}
		return $results;
	}

	function exportUsers(){

		$users = $this->getUsers();
		$exList = array();
		$i=0;
		foreach($users as $user){
			foreach($this->user_fields as $k=>$v){
				if($this->isGood($user['user'][$k])){
					if(!isset($this->export_fields[$v])) $this->export_fields[$v] = true;
					$exList[$i][$v] = $user['user'][$k];
				}
			}			
			foreach($user['meta'] as $k=>$v){
				if(in_array($k,$this->user_meta) || in_array($k,$this->wpec_meta)){
					if(!isset($this->export_fields['meta_'.$k])) $this->export_fields['meta_'.$k] = true;
					$exList[$i]['meta_'.$k] = $v;
				}
			}
			$i++;
		}
		foreach($exList as $id=>$entry){
			foreach($this->export_fields as $field=>$set){
				if(!isset($entry[$field])){
					$exList[$id][$field] = '';
				}
			}
		}
				
		$this->logger->debug("WPEC_Users::exportUsers".print_r($exList,true));
		return $exList;
	}
	

	/**
	 * getUsers([$options = false])
	 *
	 * get alist of all the users (if no options filter given) or a specific user if defined
	 * very useful for exporting users list.
	 *
	 * @param mixed $options to filter and set orderby for the returned list.  Use this if you only want certain users
	 * @return mixed array of users
	 */
	function getUsers($options = false){
		global $wpdb;
		if(!$options){
			$filter = $orderby = '';
		}else{
			extract($options);
		}
		
		$users_sql = "SELECT * FROM ". $wpdb->users .$filter.$orderby;
		$users = $wpdb->get_results($users_sql,ARRAY_A);
		foreach($users as $user){
			$user_meta_sql = "SELECT * FROM ".$wpdb->usermeta." WHERE user_id='".$user['ID']."'";
			$this->users[$user['ID']]['user'] = $user;
			$meta = $wpdb->get_results($user_meta_sql,ARRAY_A);
			foreach($meta as $m){
				if($m['meta_key'] == 'wpshpcrt_usr_profile'){
					$wpshpcrt_data = unserialize($m['meta_value']);
					foreach($this->wpec_meta as $k=>$v){
						if($v == 'shippingcountry' || $v == 'billingcountry') $wpshpcrt_data[$k] = $wpshpcrt_data[$k][0];
						$this->users[$user['ID']]['meta'][$v] = stripslashes($wpshpcrt_data[$k]);
					}
				}elseif(in_array($m['meta_key'],$this->user_meta)){
					$this->users[$user['ID']]['meta'][$m['meta_key']] = stripslashes($m['meta_value']);	
				}
			}
		}
		$this->logger->debug("WPEC_Users::getUsers found:".print_r($this->users,true));
		return($this->users);
	}

	/**
	 * getIdByAttribute([string $query=''], [string $attribute='user_email'])
	 *
	 * simple function to retrieve a user id by a specific piece of information like email, or name
	 * use the following to get the user id by email address.
	 *
	 * <code>
	 * WPEC_Users::getIdByAttribute('email','bob@foo.com');
	 * </code>
	 *
	 * @param string $query like the actual email address
	 * @param string $attribute to use in search, 
	 * @return int the user id we looked for for false on no results
	 */
	function getIdByAttribute($query='', $attribute='user_email'){
		$user_lookup_sql = "SELECT id from ".$wpdb->users." WHERE {$attribute}='{$query}'";
		$user_id = get_var($user_lookup_sql);
		if(empty($user_id) || $user_id<1) return false;
		else return (int)$user_id;
	}

	/**
	 * saveUser($user)
	 *
	 * Very generic function to save a properly formatted user array back to the users table
	 * used in conjunction with our export functions that would save it in a usable format.
	 * 
	 * TODO: make sure that when saving records we make sure it doesn't collide with an exsisting user record
	 * @param mixed $user to save
	 * @return boolean
	 */
	function saveUser($user){
		if(!isset($user['user']) && !is_array($user['user'])) return false;

		//Check to make sure the user doesn't already exsits.  Use email as reference
		if(!isset($user['user']['user_email'])) return false;
		$user_id = getIdByAttribute($user['user']['user_email'],'user_email');
		$user['id'] = $user_id;
		return $this->save($user);
	}

	function setCheckoutArray(){
		global $wpdb;
		$sql = "SELECT id, unique_name FROM ".WPSC_TABLE_CHECKOUT_FORMS;
		$fields = $wpdb->get_results($sql,ARRAY_A);
		foreach($fields as $field){
			$this->wpec_meta[$field['id']] = $field['unique_name'];
		}
	}


	function setEncryptedPassword($user_id, $sec_password){
		global $wpdb;

		$wpdb->update($wpdb->users,array('user_pass'=>$sec_password), array('ID'=>$user_id), array('%s'), array('%d'));
	}
}
