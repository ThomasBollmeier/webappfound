<?php
/*
   Copyright 2016 Thomas Bollmeier <entwickler@tbollmeier.de>

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

namespace tbollmeier\webappfound;


class Session
{
	private static $instance = null;
	
	public static function getInstance()
	{
		if (!self::$instance) {
			session_start();
			self::$instance = new Session();
		}
		
		return self::$instance;
	}

	public static function deleteInstance()
	{
		self::$instance = null;
		
		$_SESSION = [];
		self::deleteSessionCookie();
		
		session_destroy();
		
	}
	
	public function get($name, $default=null)
	{
		return $_SESSION[$name] ?? $default; 
	}

	public function __get($name)
	{
		return $this->get($name, null); 
	}
	
	public function __set($name, $value)
	{
		$_SESSION[$name] = $value;
	}
		
	private function __construct()
	{
	}
	
	private static function deleteSessionCookie()
	{
		if (ini_get("session.use_cookies")) {
			
			$params = session_get_cookie_params();
			setcookie(session_name(),
					  '',
					  time() - 42000,
					  $params["path"],
					  $params["domain"],
					  $params["secure"],
					  $params["httponly"]);
		}
	}
	
}
