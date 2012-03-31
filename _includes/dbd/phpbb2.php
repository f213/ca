<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


//
//Драйвер для авторизационной БД phpbb2
//
//Требует $dbopts['root']
//

define('TABLE_PREFIX','phpbb_');
define('PHPBBVER',2);
define('IN_PHPBB',true);
if(!$dbopts['root'])
	die('Ошибка разбора конфигурационного файла phpbb2 - нет значения пути установки движка форума!');
if(!is_dir($dbopts['root']))
	die('Ошибка конфигурационного файла - некорректно указан путь к установке phpbb2');

$phpbb_root_path =  $dbopts['root'];
include($phpbb_root_path.'extension.inc');
include($phpbb_root_path.'common.'.$phpEx);
include($phpbb_root_path.'includes/bbcode.'.$phpEx);
mysql_query("SET NAMES utf8;");

$template=new Template($phpbb_root_path.'templates/subSilver');



function dbd_check_auth($username,$pass,$encrypted=false){
	global $table_prefix;
	if(!$table_prefix) //это берется из движка phpbb
		return null; 
	if(strlen($username)<3)
		return null;
	if(strlen($pass)<3)
		return null;
	if(!$encrypted){
		$sql="SELECT user_password FROM {$table_prefix}users WHERE username = '".addslashes($username)."' AND user_active='1' AND user_level='1';";
		$res=query_eval($sql);
		if(!mysql_num_rows($res))
			return false;
		$row=mysql_fetch_row($res);
		if(md5($pass)==$row[0])
			return $username;
	}else{
		$sql="SELECT user_password FROM phpbb_users WHERE SHA1(username) = '".addslashes($username)."' AND user_active='1' AND user_level='1' LIMIT 1;";
		$res=query_eval($sql);
		if(!mysql_num_rows($res))
			return null;
		$row=mysql_fetch_row($res);
		if($pass==$row[0]){
			$res=query_eval("SELECT username FROM phpbb_users WHERE user_password='".addslashes($pass)."' AND SHA1(username)='".addslashes($username)."' LIMIT 1;");
			if(!mysql_num_rows($res))
				die('internal authentication error.');
			$row=mysql_fetch_row($res);
			return $row[0];
		}
		return null;		
	}
}	

function dbd_encrypt($username){
	global $table_prefix;
	if(!$table_prefix) //это берется из движка phpbb
		return null; 
	$pwhash=phpbb2_pwhash($username);
	if(!$pwhash)
		die("Internal auth failure phpbb2_pwhash($username)!");

	return array(
		sha1($username),
		$pwhash,
	);
}

function phpbb2_pwhash($username){
	if(!$username or !strlen($username))
		return null;
	
	$res=query_eval("SELECT user_password FROM phpbb_users WHERE username='".addslashes($username)."' AND user_active='1' AND user_level='1' LIMIT 1;");
	if(!mysql_num_rows($res))
		die("Internal auth failure: bad query in phpbb2_pwhash($username)!");
	$row=mysql_fetch_row($res);
	return $row[0];
}

