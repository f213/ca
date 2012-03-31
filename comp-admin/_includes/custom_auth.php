<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

$custom_auth_enabled=false;
if(defined('CUSTOM_AUTH') and strlen(CUSTOM_AUTH) and file_exists(dirname($_SERVER['SCRIPT_FILENAME']).'/'.CUSTOM_AUTH)){
	$custom_auth_enabled=true;
	$custom_auth_file=dirname($_SERVER['SCRIPT_FILENAME']).'/'.CUSTOM_AUTH;
}
//
//http://php.net/manual/en/function.crypt.php
//mikey_nich (at) hotmР“РЋil . com 04-Mar-2007 03:47
//
function crypt_apr1_md5($plainpasswd,$salt='') {
	if(!$salt)
		$salt = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);
	$len = strlen($plainpasswd);
	$text = $plainpasswd.'$apr1$'.$salt;
	$bin = pack("H32", md5($plainpasswd.$salt.$plainpasswd));
	for($i = $len; $i > 0; $i -= 16) { $text .= substr($bin, 0, min(16, $i)); }
	for($i = $len; $i > 0; $i >>= 1) { $text .= ($i & 1) ? chr(0) : $plainpasswd{0}; }
	$bin = pack("H32", md5($text));
	for($i = 0; $i < 1000; $i++) {
		$new = ($i & 1) ? $plainpasswd : $bin;
		if ($i % 3) $new .= $salt;
		if ($i % 7) $new .= $plainpasswd;
		$new .= ($i & 1) ? $bin : $plainpasswd;
		$bin = pack("H32", md5($new));
	}
	for ($i = 0; $i < 5; $i++) {
		$k = $i + 6;
		$j = $i + 12;
		if ($j == 16) $j = 5;
		$tmp = $bin[$i].$bin[$k].$bin[$j].$tmp;
	}
	$tmp = chr(0).chr(0).$bin[11].$tmp;
	$tmp = strtr(strrev(substr(base64_encode($tmp), 2)),
	"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
	"./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz");
	return "$"."apr1"."$".$salt."$".$tmp;
}

function check_htpasswd($file,$login,$pass,$login_is_hash=false){

	if(!file_exists($file) or !is_readable($file))
		return false;
	$fh=fopen($file,'r');
	if(!$fh)
		return null;
	while($line=chop(fgets($fh))){
		list($user,$file_pass)=preg_split('/\:/',$line);
		if(!preg_match('/\$apr1\$[a-zA-z]{8}/',$file_pass))
			continue;

		if($login_is_hash){
			if(sha1($user)==$login and $file_pass==$pass)
				return true;
		}else{
			$q=array();
			preg_match('/\$apr1\$([a-zA-z]{8})/',$file_pass,$q);
			$salt=$q[1];
			$hash=crypt_apr1_md5($pass,$salt);	
			if($login==$user and $file_pass==$hash)
				return true;
		}	
	}
	return false;
}
function get_htpasswd_pw($file,$user){
	if(!file_exists($file) or !is_readable($file))
		return false;
	$fh=fopen($file,'r');
	if(!$fh)
		return null;
	while($line=chop(fgets($fh))){
		list($user,$pass)=preg_split('/\:/',$line);
		if(!preg_match('/\$apr1\$[a-zA-z]{8}/',$pass))
			continue;
		return $pass;
	}
	return null;
}
function get_htpasswd_login($file,$userhash){
	if(!file_exists($file) or !is_readable($file))
		return false;
	$fh=fopen($file,'r');
	if(!$fh)
		return null;
	while($line=chop(fgets($fh))){
		list($user,$pass)=preg_split('/\:/',$line);
		if(!preg_match('/\$apr1\$[a-zA-z]{8}/',$pass))
			continue;
		if(sha1($user)==$userhash)
			return $user;
	}
	return null;
}
?>
