<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//
//Драйвер для авторизационной БД phpbb3
//
//Требует $dbopts['root'] и $dbopts['auth_required_group_id']
//

define('TABLE_PREFIX','phpbb_');
define('PHPBBVER',3);
if(!$dbopts['root'])
	die('Ошибка разбора конфигурационного файла phpbb3 - нет значения пути установки движка форума!');
if(!is_dir($dbopts['root']))
	die('Ошибка конфигурационного файла - некорректно указан путь к установке phpbb3');

define('IN_PHPBB', true);
define('CA_REQUIRED_GROUP',$dbopts['auth_required_group_id']);

$phpEx='php';
$phpbb_root_path = $dbopts['root'];
include($phpbb_root_path . 'common.' . $phpEx);
ca_db_con();





function dbd_check_auth($username,$pass,$encrypted=false){
	global $table_prefix;
	if(strlen($username)<3)
		return null;
	if(strlen($pass)<3)
		return null;
	if(!defined('CA_REQUIRED_GROUP')) //группа доступа phpbb, которая может входить в систему
		return false;
	if(!strlen($table_prefix) or !defined('USERS_TABLE') or !function_exists('phpbb_check_hash')) //это должно быть из установки phpbb
		return null;
	if(!$encrypted){ 
		$sql = 'SELECT user_id, username, user_password, user_passchg, user_pass_convert, user_email, user_type, user_login_attempts
        	        FROM ' . USERS_TABLE . "
			WHERE username = '".addslashes($username)."';";
		$res=query_eval($sql);
		if(!mysql_num_rows($res))
			return null;
		$row=mysql_fetch_assoc($res);
		if ($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE)
			return null;
		if (phpbb_check_hash($pass, $row['user_password'])){
			$user_id=$row['user_id'];
			$sql="SELECT * FROM {$table_prefix}user_group WHERE group_id='".CA_REQUIRED_GROUP."' AND user_id='$user_id';";
			$res=query_eval($sql);
			if(mysql_num_rows($res))
				return phpbb3_username($user_id);
			return null;
		}
		return null;
	}else{
		$sql="SELECT user_id,user_password,user_type FROM ".USERS_TABLE." WHERE SHA1(username)='".addslashes($username)."' LIMIT 1;";
		$res=query_eval($sql);
		if(!mysql_num_rows($res))
			return null;
		$row=mysql_fetch_assoc($res);
		if ($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE)
			return null;
		if($pass==sha1($row['user_password'])){
			$user_id=$row['user_id'];
			$sql="SELECT * FROM {$table_prefix}user_group WHERE group_id='".CA_REQUIRED_GROUP."' AND user_id='$user_id';";
			$res=query_eval($sql);
			if(mysql_num_rows($res))
				return phpbb3_username($user_id);

		}
		return null;
	}
	return null;

}
function dbd_encrypt($username){
	$ret=array();
	if(!$username or strlen($username)<3)
		die("Internal auth failure: dbd_encrypt($username) bad param!");
	$user_id=phpbb3_user_id($username);
	if(!$user_id)
		die("Internal auth failure (phpbb3_user_id($username)!");
	$ret[0]=phpbb3_username($user_id,1); //второй параметр значит типа вернуть шифрованную. В этом драйвере я вынес эту в отдельную функцию, потому что на некоторых системах sha1() вызывал галюны, лучше из БД выбирать, так надежнее
	if(!$ret[0] or !strlen($ret[0]))
		die("Internal auth_failure (phpbb3_username($user_id,1)!");
	$ret[1]=phpbb3_pwhash($user_id);
	if(!$ret[1] or !strlen($ret[1]))
		die("Internal auth failure (phpbb3_pwhash($user_id)!");
	return $ret;

}

function ca_db_con(){
        global $phpbb_root_path,$phpEx;
        if(defined('CA_NO_DBCONN'))
                return;
        require($phpbb_root_path.'config.'.$phpEx);
        mysql_connect($dbhost,$dbuser,$dbpasswd);
        mysql_select_db($dbname);
	mysql_query("SET NAMES utf8;");
}
function phpbb3_pwhash($user_id){
        if(!$user_id)
                return false;
        $user_id=(int)$user_id;
        $sql="SELECT user_password FROM ".USERS_TABLE." WHERE user_id='".addslashes($user_id)."' LIMIT 1;";
        $res=query_eval($sql);
        if(!mysql_num_rows($res))
                return false;
        $row=mysql_fetch_row($res);
        return sha1($row[0]);
}
function phpbb3_user_id($login){
        if(strlen($login)<3)
                return false;
        $sql="SELECT user_id FROM ".USERS_TABLE." WHERE username='".addslashes($login)."' LIMIT 1;";
        $res=query_eval($sql);
        if(!mysql_num_rows($res))
                return false;
        $row=mysql_fetch_row($res);
        return $row[0];
}
function phpbb3_username($user_id,$sha=0){
        if(!$user_id)
                return false;
        $user_id=(int)$user_id;
        if(!$sha)
                $sql="SELECT username FROM ".USERS_TABLE." WHERE user_id='".addslashes($user_id)."' LIMIT 1;";
        else
                $sql="SELECT SHA1(username) FROM ".USERS_TABLE." WHERE user_id='".addslashes($user_id)."' LIMIT 1;";

        $res=query_eval($sql);
        if(!mysql_num_rows($res))
                return false;
        $row=mysql_fetch_row($res);
        return $row[0];
}
/////////////
function parse_post($post_id){
        global $user,$phpbb_root_path,$phpEx;
        if(!$post_id)
                return null;
        $post_id=(int)$post_id;
        $res=query_eval("SELECT post_text AS text, bbcode_uid,  bbcode_bitfield FROM ".POSTS_TABLE." WHERE post_id='$post_id' LIMIT 1;");
        if(!mysql_num_rows($res))
                return null;
        $row=mysql_fetch_assoc($res);
        $text=nl2br($row['text']);
        include($phpbb_root_path . 'includes/bbcode.' . $phpEx);
        $user->setup();
        $bbcode = new bbcode(base64_encode($row['bbcode_bitfield']));
        $bbcode->bbcode_second_pass($text,$row['bbcode_uid'],$row['bbcode_bitfield']);
        return $text;
}

