<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require_once('_includes/custom_auth.php');
$message="admin";
if(empty($_GET['noauth']) or !$_GET['noauth'] or !strlen($_GET['noauth']) or !test_noauth($_GET['noauth'])){
	if(!$_COOKIE['login'] or !$_COOKIE['pass']){
		header("Location: login.php?back=".urlencode($_SERVER['REQUEST_URI']));
		die('hooj');
	}
	$admin_user=test_authEX($_COOKIE['login'],$_COOKIE['pass']);

	if(!$admin_user){
		header("Location: login.php?back=".urlencode($_SERVER['REQUEST_URI']));
		die('hooj');
	}
}

function test_authEX($username,$pass){
	global $custom_auth_enabled,$custom_auth_file;

	if($custom_auth_enabled and strstr($username,'@hta')){
		list($username)=explode('@',$username);
		if(check_htpasswd($custom_auth_file,$username,$pass,true))
			return get_htpasswd_login($custom_auth_file,$username);
	
	}
	if(DB_TYPE!='custom' and function_exists('dbd_check_auth'))
		return dbd_check_auth($username,$pass,1);
}
function test_noauth($noauth){
	if(defined('NO_AUTH_KEY') and strlen(NO_AUTH_KEY))
		if(md5(NO_AUTH_KEY.strftime('%Y%m%d%H'))==$noauth)
			return true;
	return false;
}


