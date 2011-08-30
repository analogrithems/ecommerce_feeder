<?php

class WPSC_EC_Password_Migrator extends WPEC_ecommerce_feeder{

	var $logger;

        public function __construct() {
                global $logger;
                $this->logger = $logger;
		add_filter('check_password',  array($this, 'migratePassword'), 10,4);
	}

	/**
	* migratePassword($check, $password, $hash, $user_id)
	* This hooks into the standard wordpress authentication processes to compare the password against
	* a migrated format from other ecommerce systems like oscommerce or zencart.  If it finds a match then
	* it will update the password to the wordpress format 
	*
	* @param string $check
	* @param string $password the plaintext password from the user form
	* @param string $hash the hashed password from the database to compare against
	* @param int $user_id the user id that this is for
	* @return bool returnstrue if it found a match and updated it, else it returns whatever the previous status of check was
	*/
	function migratePassword($check, $password, $hash, $user_id){

		if($this->zencart_validate_password($password,$hash)){
			//It's a valid zencart password, update to newformat
			$this->logger->info("Migratting Zencart user_id: {$user_id} password to actual Wordpress Password");
			wp_set_password( $password, $user_id );
			return true;
		}elseif($this->oscommerce_validate_password($password,$hash)){
			//It's a valid zencart password, update to newformat
			$this->logger->info("Migratting OScommerce user_id: {$user_id} password to actual Wordpress Password");
			wp_set_password( $password, $user_id );
			return true;
		}

		return $check;
	}

	/**
	* oscommerce_validate_password($plain,$encrypted)
	* We use the oscommerce password validate function logic to see if the password is a valid oscommerce password, if it is return true
	*
	* @param string $palin the plaintext password
	* @param string $encrypted the encrypted password to compare
	* @return boolean true/false
	*/
	function oscommerce_validate_password($plain, $encrypted) {
		if ($this->isGood($plain) && $this->isGood($encrypted)) {
/*
			if (tep_password_type($encrypted) == 'salt') {
				return $this->zencart_validate_password($plain, $encrypted);
			}
*/

			if ( empty($wp_hasher) ) {
				require_once( ABSPATH . 'wp-includes/class-phpass.php');
				// By default, use the portable hash from phpass
				$wp_hasher = new PasswordHash(10, TRUE);
			}

			return $wp_hasher->CheckPassword($plain, $encrypted);
		}
		return false;
	}


	/**
	* zencart_validate_password($plain,$encrypted)
	* We use the zencart password validate function logic to see if the password is a valid zencart password, if it is return true
	*
	* @param string $palin the plaintext password
	* @param string $encrypted the encrypted password to compare
	* @return boolean true/false
	*/
	function zencart_validate_password($plain=false, $encrypted=false) {
		if ($this->isGood($plain) && $this->isGood($encrypted)) {
			$stack = explode(':', $encrypted);

			if (sizeof($stack) != 2) return false;

			if (md5($stack[1] . $plain) == $stack[0]) {
				return true;
			}
		}
		return false;
	}
}
?>
