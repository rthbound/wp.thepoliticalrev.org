<?php

if (!defined('ABSPATH')) die('Access denied.');

class Simba_TFA  {

	private $salt_prefix;
	private $pw_prefix;

	public function __construct($base32_encoder, $otp_helper)
	{
		$this->base32_encoder = $base32_encoder;
		$this->otp_helper = $otp_helper;
		$this->time_window_size = apply_filters('simbatfa_time_window_size', 30);
		$this->check_back_time_windows = apply_filters('simbatfa_check_back_time_windows', 2);
		$this->check_forward_counter_window = apply_filters('simbatfa_check_forward_counter_window', 20);
		$this->otp_length = 6;
		$this->emergency_codes_length = 8;
		$this->salt_prefix = AUTH_SALT;
		$this->pw_prefix = AUTH_KEY;
		$this->default_hmac = 'totp';
	}

	public function generateOTP($user_ID, $key_b64, $length = 6, $counter = false)
	{
		
		$length = $length ? (int)$length : 6;
		
		$key = $this->decryptString($key_b64, $user_ID);
		$alg = $this->getUserAlgorithm($user_ID);
		
		if($alg == 'hotp')
		{
			$db_counter = $this->getUserCounter($user_ID);
			
			$counter = $counter ? $counter : $db_counter;
			$otp_res = $this->otp_helper->generateByCounter($key, $counter);
		}
		else
		{
			//time() is supposed to be UTC
			$time = $counter ? $counter : time();
			$otp_res = $this->otp_helper->generateByTime($key, $this->time_window_size, $time);
		}
		$code = $otp_res->toHotp($length);
		
		return $code;
	}

	public function generateOTPsForLoginCheck($user_ID, $key_b64)
	{
		$key = trim($this->decryptString($key_b64, $user_ID));
		$alg = $this->getUserAlgorithm($user_ID);
		
		if($alg == 'totp')
			$otp_res = $this->otp_helper->generateByTimeWindow($key, $this->time_window_size, -1*$this->check_back_time_windows, 0);
		elseif($alg == 'hotp')
		{
			$counter = $this->getUserCounter($user_ID);
			
			$otp_res = array();
			for($i = 0; $i < $this->check_forward_counter_window; $i++)
				$otp_res[] = $this->otp_helper->generateByCounter($key, ($counter+$i));
		}
		return $otp_res;
	}
	

	public function addPrivateKey($user_ID, $key = false)
	{
		//Generate a private key for the user. 
		//To work with Google Authenticator it has to be 10 bytes = 16 chars in base32
		$code = $key ? $key : strtoupper($this->randString(10));

		//Lets encrypt the key
		$code = $this->encryptString($code, $user_ID);
		
		//Add private key to users meta
		update_user_meta($user_ID, 'tfa_priv_key_64', $code);
		
		$alg = $this->getUserAlgorithm($user_ID);
		
		do_action('simba_tfa_adding_private_key', $alg, $user_ID, $code, $this);
		
		$this->changeUserAlgorithmTo($user_ID, $alg);
		
		return $code;
	}

	// Port over keys that were encrypted with mcrypt and its non-compliant padding scheme, so that if the site is ever migrated to a server without mcrypt, they can still be decrypted
	public function potentially_port_private_keys() {

		$simba_tfa_priv_key_format = get_site_option('simba_tfa_priv_key_format', false);
		
		$attempts = 0;
		$successes = 0;
		
		if ($simba_tfa_priv_key_format < 1 && function_exists('openssl_encrypt')) {
		
			error_log("TFA: Beginning attempt to port private key encryption over to openssl");
			global $wpdb;
			$sql = "SELECT user_id, meta_value FROM ".$wpdb->usermeta." WHERE meta_key = 'tfa_priv_key_64'";
			
			$user_results = $wpdb->get_results($sql);
			
			foreach ($user_results as $u) {
				$dec_openssl = $this->decryptString($u->meta_value, $u->user_id, true);

				$ported = false;
				if ('' == $dec_openssl) {

					$attempts++;

					$dec_default = $this->decryptString($u->meta_value, $u->user_id);
					
					if ('' != $dec_default) {

						$enc = $this->encryptString($dec_default, $u->user_id);
						
						if ($enc) {

							$ported = true;
							$successes++;
							update_user_meta($u->user_id, 'tfa_priv_key_64', $enc);
						}
					}

				}
				
				if ($ported) {
					error_log("TFA: Successfully ported the key for user with ID ".$u->user_id." over to openssl");
				} else {
					error_log("TFA: Failed to port the key for user with ID ".$u->user_id." over to openssl");
				}
			}
			if ($attempts == 0 || $successes > 0) update_site_option('simba_tfa_priv_key_format', 1);
		
		}
	}

	public function getPrivateKeyPlain($enc, $user_ID)
	{
		$dec = $this->decryptString($enc, $user_ID);
		$this->potentially_port_private_keys();
		return $dec;
	}


	public function getPanicCodesString($arr, $user_ID)
	{
		if(!is_array($arr)) return '<em>'.__('No emergency codes left. Sorry.', 'two-factor-authentication').'</em>';
		
		$emergency_str = '';
		
		foreach($arr as $p_code) {
			$emergency_str .= $this->decryptString($p_code, $user_ID).', ';
		}

		$emergency_str = rtrim($emergency_str, ', ');
		
		$emergency_str = $emergency_str ? $emergency_str : '<em>'.__('No emergency codes left. Sorry.', 'two-factor-authentication').'</em>';
		return $emergency_str;
	}
	
	public function preAuth($params)
	{
		global $wpdb;
		$query = filter_var($params['log'], FILTER_VALIDATE_EMAIL) ? $wpdb->prepare("SELECT ID, user_email from ".$wpdb->users." WHERE user_email=%s", $params['log']) : $wpdb->prepare("SELECT ID, user_email from ".$wpdb->users." WHERE user_login=%s", $params['log']);
		$user = $wpdb->get_row($query);
		if (!$user && filter_var($params['log'], FILTER_VALIDATE_EMAIL)) {
			// Corner-case: login looks like an email, but is a username rather than email address
			$user = $wpdb->get_row($wpdb->prepare("SELECT ID, user_email from ".$wpdb->users." WHERE user_login=%s", $params['log']));
		}
		$is_activated_for_user = true;
		$is_activated_by_user = false;
		
		if($user) {
			$tfa_priv_key = get_user_meta($user->ID, 'tfa_priv_key_64', true);
			$is_activated_for_user = $this->isActivatedForUser($user->ID);
			$is_activated_by_user = $this->isActivatedByUser($user->ID);
			
			if($is_activated_for_user && $is_activated_by_user)
			{
// 				$delivery_type = get_user_meta($user->ID, 'simbatfa_delivery_type', true);
				
				//No private key yet, generate one.
				//This is safe to do since the code is emailed to the user.
				//Not safe to do if the user has disabled email.
				if(!$tfa_priv_key)
					$tfa_priv_key = $this->addPrivateKey($user->ID);
				
				$code = $this->generateOTP($user->ID, $tfa_priv_key);

				return true;//Set to true
			}
			return false;
		}
		return false;
	}
	
	public function authUserFromLogin($params)
	{
		
		$params = apply_filters('simbatfa_auth_user_from_login_params', $params);
		
		global $simba_two_factor_authentication, $wpdb;
		
		if(!$this->isCallerActive($params))
			return true;
		
		$field = filter_var($params['log'], FILTER_VALIDATE_EMAIL) ? 'user_email' : 'user_login';
		$query = $wpdb->prepare("SELECT ID, user_registered from ".$wpdb->users." WHERE ".$field."=%s", $params['log']);
		$response = $wpdb->get_row($query);

		$user_ID = is_object($response) ? $response->ID : false;
		$user_registered = is_object($response) ? $response->user_registered : false;

		$user_code = trim(@$params['two_factor_code']);
		
		if(!$user_ID)
			return true;
		
		if(!$this->isActivatedForUser($user_ID))
			return true;
			
		if(!$this->isActivatedByUser($user_ID)) {

			if (!$this->isRequiredForUser($user_ID)) {
				return true;
			}

			$requireafter = absint($simba_two_factor_authentication->get_option('tfa_requireafter')) * 86400;

			$account_age = time() - strtotime($user_registered);

			if ($account_age > $requireafter) {
				return new WP_Error('tfa_required', apply_filters('simbatfa_notfa_forbidden_login', '<strong>'.__('Error:', 'two-factor-authentication').'</strong> '.__('The site owner has forbidden you to login without two-factor authentication. Please contact the site owner to re-gain access.', 'two-factor-authentication')));
			}

			return true;
		}

		$tfa_creds_user_id = !empty($params['creds_user_id']) ? $params['creds_user_id'] : $user_ID;
		
		if ($tfa_creds_user_id != $user_ID) {
		
			// Authenticating using a different user's credentials (e.g. https://wordpress.org/plugins/use-administrator-password/)
			// In this case, we require that different user to have TFA active - so that this mechanism can't be used to avoid TFA
		
			if(!$this->isActivatedForUser($tfa_creds_user_id) || !$this->isActivatedByUser($tfa_creds_user_id)) {
				return new WP_Error('tfa_required', apply_filters('simbatfa_notfa_forbidden_login_altuser', '<strong>'.__('Error:', 'two-factor-authentication').'</strong> '.__('You are attempting to log in to an account that has two-factor authentication enabled; this requires you to also have two-factor authentication enabled on the account whose credentials you are using.', 'two-factor-authentication')));
			}
		
		}
		
		$tfa_priv_key = get_user_meta($tfa_creds_user_id, 'tfa_priv_key_64', true);
// 		$tfa_last_login = get_user_meta($tfa_creds_user_id, 'tfa_last_login', true); // Unused
		$tfa_last_pws_arr = get_user_meta($tfa_creds_user_id, 'tfa_last_pws', true);
		$tfa_last_pws = @$tfa_last_pws_arr ? $tfa_last_pws_arr : array();
		$alg = $this->getUserAlgorithm($tfa_creds_user_id);
		
		$current_time_window = intval(time()/30);
		
		//Give the user 1,5 minutes time span to enter/retrieve the code
		//Or check $this->check_forward_counter_window number of events if hotp
		$codes = $this->generateOTPsForLoginCheck($tfa_creds_user_id, $tfa_priv_key);
	
		//A recently used code was entered.
		//Not ok
		if(in_array($this->hash($user_code, $tfa_creds_user_id), $tfa_last_pws))
			return false;
	
		$match = false;
		foreach($codes as $index => $code)
		{
			if(trim($code->toHotp(6)) == trim($user_code))
			{
				$match = true;
				$found_index = $index;
				break;
			}
		}
		
		//Check emergency codes
		if(!$match)
		{
			$emergency_codes = get_user_meta($tfa_creds_user_id, 'simba_tfa_emergency_codes_64', true);
			
			if(!@$emergency_codes)
				return $match;
			
			$dec = array();
			foreach($emergency_codes as $emergency_code)
				$dec[] = trim($this->decryptString(trim($emergency_code), $tfa_creds_user_id));

			$in_array = array_search($user_code, $dec);
			$match = $in_array !== false;
			
			if($match)//Remove emergency code
			{
				array_splice($emergency_codes, $in_array, 1);
				update_user_meta($tfa_creds_user_id, 'simba_tfa_emergency_codes_64', $emergency_codes);
				do_action('simba_tfa_emergency_code_used', $tfa_creds_user_id, $emergency_codes);
			}
			
		} else {
			//Add the used code as well so it cant be used again
			//Keep the two last codes
			$tfa_last_pws[] = $this->hash($user_code, $tfa_creds_user_id);
			$nr_of_old_to_save = $alg == 'hotp' ? $this->check_forward_counter_window : $this->check_back_time_windows;
			
			if(count($tfa_last_pws) > $nr_of_old_to_save)
				array_splice($tfa_last_pws, 0, 1);
				
			update_user_meta($tfa_creds_user_id, 'tfa_last_pws', $tfa_last_pws);
		}
		
		if($match)
		{
			//Save the time window when the last successful login took place
			update_user_meta($tfa_creds_user_id, 'tfa_last_login', $current_time_window);
			
			//Update the counter if HOTP was used
			if($alg == 'hotp')
			{
				$counter = $this->getUserCounter($tfa_creds_user_id);
				
				$enc_new_counter = $this->encryptString($counter+1, $tfa_creds_user_id);
				update_user_meta($tfa_creds_user_id, 'tfa_hotp_counter', $enc_new_counter);
				
				if($found_index > 10)
					update_user_meta($tfa_creds_user_id, 'tfa_hotp_off_sync', 1);
			}
		}
		
		return $match;
		
	}

	public function getUserCounter($user_ID)
	{
		$enc_counter = get_user_meta($user_ID, 'tfa_hotp_counter', true);
		
		if($enc_counter)
			$counter = $this->decryptString(trim($enc_counter), $user_ID);
		else
			return '';
			
		return trim($counter);
	}
	
	public function changeUserAlgorithmTo($user_id, $new_algorithm)
	{
		update_user_meta($user_id, 'tfa_algorithm_type', $new_algorithm);
		delete_user_meta($user_id, 'tfa_hotp_off_sync');
		
		$counter_start = rand(13, 999999999);
		$enc_counter_start = $this->encryptString($counter_start, $user_id);
		
		if($new_algorithm == 'hotp')
			update_user_meta($user_id, 'tfa_hotp_counter', $enc_counter_start);
		else
			delete_user_meta($user_id, 'tfa_hotp_counter');
	}
	
	//Added
	public function changeEnableTFA($user_id, $setting)
	{
		$setting = ($setting === 'true') ? 1 : 0;
		
		update_user_meta($user_id, 'tfa_enable_tfa', $setting);
	}
	
	public function getUserAlgorithm($user_id)
	{
		global $simba_two_factor_authentication;
		$setting = get_user_meta($user_id, 'tfa_algorithm_type', true);
		$default_hmac = $simba_two_factor_authentication->get_option('tfa_default_hmac');
		$default_hmac = $default_hmac ? $default_hmac : $this->default_hmac;
		
		$setting = $setting === false || !$setting ? $default_hmac : $setting;
		return $setting;
	}
	
	public function isActivatedForUser($user_id)
	{

		if (empty($user_id)) return false;

		global $simba_two_factor_authentication;

		// Super admin is not a role (they are admins with an extra attribute); needs separate handling
		if (is_multisite() && is_super_admin($user_id)) {
			// This is always a final decision - we don't want it to drop through to the 'admin' role's setting
			$role = '_super_admin';
			$db_val = $simba_two_factor_authentication->get_option('tfa_'.$role);
			$db_val = $db_val === false || $db_val ? 1 : 0; //Nothing saved or > 0 returns 1;
			
			return ($db_val) ? true : false;
		}

		$user = new WP_User($user_id);

		foreach($user->roles as $role)
		{
			$db_val = $simba_two_factor_authentication->get_option('tfa_'.$role);
			$db_val = $db_val === false || $db_val ? 1 : 0; //Nothing saved or > 0 returns 1;
			
			if($db_val)
				return true;
		}
		
		return false;
		
	}
	
	// N.B. - This doesn't check isActivatedForUser() - the caller would normally want to do that first
	public function isRequiredForUser($user_id)
	{

		if (empty($user_id)) return false;

		global $simba_two_factor_authentication;

		// Super admin is not a role (they are admins with an extra attribute); needs separate handling
		if (is_multisite() && is_super_admin($user_id)) {
			// This is always a final decision - we don't want it to drop through to the 'admin' role's setting
			$role = '_super_admin';
			$db_val = $simba_two_factor_authentication->get_option('tfa_required_'.$role);
			
			return ($db_val) ? true : false;
		}

		$user = new WP_User($user_id);

		foreach($user->roles as $role)
		{
			$db_val = $simba_two_factor_authentication->get_option('tfa_required_'.$role);
			
			if($db_val)
				return true;
		}
		
		return false;
		
	}
	
	//Added
	public function isActivatedByUser($user_id){
		$enabled = get_user_meta($user_id, 'tfa_enable_tfa', true);
		$enabled = empty($enabled) ? false : true;

		return $enabled;
	}

	// Disabled: unused
// 	public function saveCallerStatus($caller_id, $status)
// 	{
// 		global $simba_two_factor_authentication;
// 		if($caller_id == 'xmlrpc')
// 			$simba_two_factor_authentication->set_option('tfa_xmlrpc_on', $status);
// 	}

	private function isCallerActive($params)
	{

		if(!preg_match('/(\/xmlrpc\.php)$/', trim($params['caller'])))
			return true;

		global $simba_two_factor_authentication;
		$saved_data = $simba_two_factor_authentication->get_option('tfa_xmlrpc_on');
		
		if($saved_data)
			return true;
		
		return false;
	}
	
	private function get_iv_size() {
		// mcrypt first, for backwards compatibility
		if (function_exists('mcrypt_get_iv_size')) {
			return mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		} elseif (function_exists('openssl_cipher_iv_length')) {
			return openssl_cipher_iv_length('AES-128-CBC');
		}
		throw new Exception('One of the mcrypt or openssl PHP modules needs to be installed');
	}
	
	private function create_iv($iv_size) {
		if (function_exists('mcrypt_create_iv')) {
			 return mcrypt_create_iv($iv_size, MCRYPT_RAND);
		} elseif (function_exists('openssl_random_pseudo_bytes')) {
			return openssl_random_pseudo_bytes($iv_size);
		}
		throw new Exception('One of the mcrypt or openssl PHP modules needs to be installed');
	}
	
	private function encrypt($key, $string, $iv) {
		// Prefer OpenSSL, because it uses correct padding, and its output can be decrypted by mcrypt - whereas, the converse is not true
		if (function_exists('openssl_encrypt')) {
			return openssl_encrypt($string, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
		} elseif (function_exists('mcrypt_encrypt')) {
			return mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string, MCRYPT_MODE_CBC, $iv);
		}
		throw new Exception('One of the mcrypt or openssl PHP modules needs to be installed');
	}

	private function decrypt($key, $enc, $iv, $force_openssl = false) {
		// Prefer mcrypt, because it can decrypt the output of both mcrypt_encrypt() and openssl_decrypt(), whereas (because of mcrypt_encrypt() using bad padding), the converse is not true
		if (function_exists('mcrypt_decrypt') && !$force_openssl) {
			return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $enc, MCRYPT_MODE_CBC, $iv);
		} elseif (function_exists('openssl_decrypt')) {
			$decrypted = openssl_decrypt($enc, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
			if (false === $decrypted && !$force_openssl) { error_log("TFA decryption failure: was your site migrated to a server without mcrypt? You may need to install mcrypt, or disable TFA, in order to successfully decrypt data that was previously encrypted with mcrypt."); }
			return $decrypted;
		}
		if ($force_openssl) return false;
		throw new Exception('One of the mcrypt or openssl PHP modules needs to be installed');
	}

	public function encryptString($string, $salt_suffix)
	{
		$key = $this->hashAndBin($this->pw_prefix.$salt_suffix, $this->salt_prefix.$salt_suffix);
		
		$iv_size = $this->get_iv_size();
		$iv = $this->create_iv($iv_size);
		
		$enc = $this->encrypt($key, $string, $iv);
		
		if (false === $enc) return false;
		
		$enc = $iv.$enc;
		$enc_b64 = base64_encode($enc);
		return $enc_b64;
	}
	
	private function decryptString($enc_b64, $salt_suffix, $force_openssl = false)
	{
		$key = $this->hashAndBin($this->pw_prefix.$salt_suffix, $this->salt_prefix.$salt_suffix);
		
		$iv_size = $this->get_iv_size();
		$enc_conc = base64_decode($enc_b64);
		
		$iv = substr($enc_conc, 0, $iv_size);
		$enc = substr($enc_conc, $iv_size);
		
		$string = $this->decrypt($key, $enc, $iv, $force_openssl);

		// Remove padding bytes
		return rtrim($string, "\x00..\x1F");
	}

	private function hashAndBin($pw, $salt)
	{
		$key = $this->hash($pw, $salt);
		$key = pack('H*', $key);
		// Yes: it's a null encryption key. See: https://wordpress.org/support/topic/warning-mcrypt_decrypt-key-of-size-0-not-supported-by-this-algorithm-only-k?replies=5#post-6806922
		// Basically: the original plugin had a bug here, which caused a null encryption key. This fails on PHP 5.6+. But, fixing it would break backwards compatibility for existing installs - and note that the only unknown once you have access to the encrypted data is the AUTH_SALT and AUTH_KEY constants... which means that actually the intended encryption was non-portable, + problematic if you lose your wp-config.php or try to migrate data to another site, or changes these values. (Normally changing these values only causes a compulsory re-log-in - but with the intended encryption in the original author's plugin, it'd actually cause a permanent lock-out until you disabled his plugin). If someone has read-access to the database, then it'd be reasonable to assume they have read-access to wp-config.php too: or at least, the number of attackers who can do one and not the other would be small. The "encryption's" not worth it.
		// In summary: this isn't encryption, and is not intended to be.
		return str_repeat(chr(0), 16);
	}

	private function hash($pw, $salt)
	{
		//$hash = hash_pbkdf2('sha256', $pw, $salt, 10);
		//$hash = crypt($pw, '$5$'.$salt.'$');
		$hash = md5($salt.$pw);
		return $hash;
	}

	private function randString($len = 6)
	{
		$chars = '23456789QWERTYUPASDFGHJKLZXCVBNM';
		$chars = str_split($chars);
		shuffle($chars);
		$code = implode('', array_splice($chars, 0, $len));
		
		return $code;
	}
	
	public function setUserHMACTypes()
	{
		//We need this because we dont want to change third party apps users algorithm
		$users = get_users(array('meta_key' => 'simbatfa_delivery_type', 'meta_value' => 'third-party-apps'));
		if(!empty($users))
		{
			foreach($users as $user)
			{
				$tfa_algorithm_type = get_user_meta($user->ID, 'tfa_algorithm_type', true);
				if($tfa_algorithm_type)
					continue;
				
				update_user_meta($user->ID, 'tfa_algorithm_type', $this->getUserAlgorithm($user->ID));
			}
		}
	}
	
}
