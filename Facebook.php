<?php

/***
 * FacebookConnect - A simple way to connect and use the Facebook API
 * @name FacebookConnect
 * @author Thomas Tricarico
 * @copyright (c) 2013 Thomas Tricarico
 * @version 1.0a
 ****/

class Facebook {
	
	const FBCONFIG = "config.php";
	private static $state; //also stored in session
	private static $access_token;
	private static $expiretime;
	private static $appid;
	private static $appsecret;
	private static $callbackurl;
	private static $finalcallback;
	
	public $user;
	
	/**
	 * Here we are going to get the information from Facebook about the person that we are dealing with.
	 */
	public function __construct() {

		include(dirname(__FILE__) . DIRECTORY_SEPARATOR . self::FBCONFIG);
		self::$appid = $config['app_id'];
		self::$appsecret = $config['app_secret'];
		self::$callbackurl = $config['callback_url'];
		self::$finalcallback = $config['final_callback'];
		echo self::$appid;
		if(func_num_args() == 2) {
			self::$access_token = $_SESSION['FacebookConnect']['access_token'] = func_get_arg(0);
			self::$expiretime = $_SESSION['FacebookConnect']['expiretime'] = time() + func_get_arg(1);
		}
		else {
			if(!isset($_SESSION['FacebookConnect']) || !is_array($_SESSION['FacebookConnect'])) {
				//start login procedure
				self::connectionPartOne();	
				$_SESSION['FacebookConnect']['inprocess'] = true;
			}
			elseif($_SESSION['FacebookConnect']['inprocess']) {
				if(!isset($_REQUEST['code'])) {
					self::connectionPartOne();
				}
				self::connectionPartTwo(self::$finalcallback);
			}
			else {
				self::$access_token = $_SESSION['FacebookConnect']['access_token'];
				self::$expiretime = $_SESSION['FacebookConnect']['expiretime'];
			}
		}
		
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/me?access_token='.self::$access_token);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$u = curl_exec($ch);
		
		if(!$u) {
			throw new Exception(curl_error($ch));
		}
		
		$this->user = json_decode($u);
		
	}
	
	/*
	 * creates a state to help defend against CSRF
	 * @param none
	 * @return $state
	 */
	private static function createState() {
		$s = mt_rand(0, mt_getrandmax());
		$s = md5(time() . $s);
		$s = substr($s, 0, 16);
		$_SESSION['FacebookConnect']['state'] = $s;
		self::$state = $s;
		return $s;
	}
	
	/*
	 * checks to make sure the states are still okay
	 * @param string $state
	 * @return boolean
	 */
	private static function checkState($state) {
		//if($_SESSION['FacebookConnect']['state'] == $state)
			return true;
		//else 
		//	return false;
	}
	
	/**
	 * So we need to make the login over different functions, since the script
	 * has to contact Facebook, get a response, then send the access code back
	 */
	public static function connectionPartOne() {
		header('Location: https://www.facebook.com/dialog/oauth?client_id='.self::$appid.
																'&redirect_uri='. self::$callbackurl.
																'&state='.self::createState());
	}
	public static function connectionPartTwo($finalcallback) {
		//first, check state
		if(!self::checkState($_REQUEST['state']))
			throw new Exception("Possible CSRF Attack; Returned state does not equal saved state");
			
		

		//check if error; throw exception, display error
		if(isset($_REQUEST['error'])) {
			$_REQUEST['error'];
			$_REQUEST['error_code'];
			$_REQUEST['error_description'];
			$_REQUEST['error_reason'];
		}
		
		
		//check if code is sent back, if so, all okay!
		if($_REQUEST['code'] != '') {
			//do the final part of the connection
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/oauth/access_token?client_id='.self::$appid.
																			'&redirect_uri='.self::$callbackurl.
																			'&client_secret='.self::$appsecret.
																			'&code='.$_REQUEST['code'].
																			'&state='.self::createState());
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$return = curl_exec($ch);
			
			if(!$return)
				throw new Exception("There was a problem retrieving data from Facebook, CURL responded with: ".curl_error($ch));
			
			$return = explode('&', $return);
			$access_token = explode('=', $return[0]);
			$expires = explode('=', $return[1]);
			
			new Facebook($access_token[1], $expires[1]);
			
			header('Location: '.$finalcallback);

		}
	}
}


