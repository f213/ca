<?php

this_dbd_db_conn();

function dbd_check_auth($username,$pass,$encrypted=false){
	if($encrypted){
		this_dbd_decrypt(&$username);
		this_dbd_decrypt(&$pass);
	}
	if(!this_dbd_check_auth($username,$pass))
		return null;
	return this_dbd_get_name($username);
}

function dbd_ecrypt($username){
	$user_id=this_dbd_get_user_id($username);
	if(!$user_id)
		die("this_dbd_get_user_id($username) failure!");
	$pwhash=this_dbd_get_pwhash($user_id);
	if(!$pwhash)
		die("this_dbd_get_pwhash($user_id) failure!");
	return array(
		this_dbd_ecnrypt($username),
		this_dbd_own_hash($pass), //double hashing here
	);
}
