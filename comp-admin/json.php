<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require_once('../_includes/core.php');
require_once('_includes/auth.php');
require('_includes/nocache.php');

$comp_id=_input_val('comp_id');
if(defined('CURRENT_COMP'))
	$comp_id=CURRENT_COMP;

if(!$comp_id)
	die("Не указан id соревнования!");

header("Content-Type: text/plain; charset=utf-8");
if(isset($_GET['registered_numbers'])){ //список зарегистрированных номеров
	$res=query_eval("SELECT DISTINCT(start_number) FROM $compres_dbt WHERE comp_id=$comp_id;");
	if(!mysql_num_rows($res))
		return;
	$ret=array();
	while($row=mysql_fetch_row($res))
		$ret[]=$row[0];
	print json_encode($ret);
	exit;
}
if(isset($_GET['next_start_number'])){ //следущий выдаваемый номер
	require('_includes/online_requests.functions.php');
	print json_encode(next_start_number($comp_id));
	exit;
}
if(isset($_GET['reg'])){//регистрация в лагере
	$item_id=_input_val('item_id');
	if(!$item_id)
		die('Ошибка - не указан item_id!');
	require('_includes/online_requests.functions.php');
	//судя по всему, здесь следует проверять переданные параметры, чтобы выдавать юзеру вменяемую ошибку
	$res=or_register($comp_id,$item_id);
	print json_encode($res);
	exit;
}
if(isset($_GET['unreg'])){//отмена регистрации в лагере
	$item_id=_input_val('item_id');
	if(!$item_id)
		die('Ошибка - не указан item_id!');
	require('_includes/online_requests.functions.php');
	$res=or_deregister($comp_id,$item_id);
	print json_encode($res);
	exit;
}

