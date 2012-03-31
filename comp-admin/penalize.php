<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require_once('../_includes/core.php');
require_once('_includes/started_functions.php');
require_once('_includes/request_functions.php');
require_once('_includes/penalize_functions.php');
require_once('_includes/auth.php');

if(empty($_GET['comp_id']))
	die('Не указан id соревнования!');

$comp_id=(int)$_GET['comp_id'];

//flags
if($_GET['flag'])
	$flag=(int)$_GET['flag'];

$valid_numbers=get_full_valid_numbers($comp_id);
$valid_numbers_str=get_valid_numbers_str($valid_numbers);

$res=query_eval("SELECT DISTINCT(start_number) FROM $comppen_dbt WHERE comp_id=$comp_id;");
$penalized_numbers=array();
while($row=mysql_fetch_row($res))
	$penalized_numbers[]=(int)$row[0];
$penalized_numbers_str=get_valid_numbers_str($penalized_numbers);
//получаем список причин для пенализации
$res=query_eval("SELECT DISTINCT(reason) FROM $comppen_dbt");
$reasons=array();
while($row=mysql_fetch_row($res))
	$reasons[]="'".stripslashes($row[0])."'";
$reasons_str=get_valid_numbers_str($reasons);

if($flag){
	switch($flag){
	case 1: //добавление пенализации
		$start_number=(int)$_GET['start_number'];
		if(!in_array($start_number,$valid_numbers))
			die("номер ($start_number) отсутсвует в стартовой ведомости!");

		$min=(int)$_GET['min'];
		$reason=$_GET['reason'];
		if(!$min)
			die('Не указано время');
		if(strlen($reason)<=3)
			die('не указана причина!');
		add_pen($comp_id,$start_number,$min,$reason);
		header('Location: '.append_rnd("penalize.php?comp_id=$comp_id&just_edited=$start_number"));
		exit;
		break;
	case 2: //удаление пенализации
		$item_id=(int)$_GET['item_id'];
		if(!$item_id)
			die('не указан id!');
		del_pen($comp_id,$item_id);
		header('Location: '.append_rnd("penalize.php?comp_id=$comp_id"));
		exit;
	}

}	
$res=query_eval("SELECT a.id AS id, a.start_number AS start_number, a.time AS time, a.reason AS reason, a.author AS author, b.cat_id AS cat_id  FROM $comppen_dbt a, $compres_dbt b WHERE a.comp_id=$comp_id AND b.comp_id=$comp_id AND b.start_number=a.start_number ORDER BY cat_id ASC, start_number ASC;");
while($row=mysql_fetch_assoc($res)){
	$id=$row['id'];
	$item_output[$id]['start_number']=(int)$row['start_number'];
	$item_output[$id]['request_id']=$request_id=num2req($comp_id,$item_output[$id]['start_number']);
	$item_output[$id]=get_brief_request_data($comp_id,$request_id,$item_output[$id]);
	$item_output[$id]['min']=gmdate('H:i',(int)$row['time']*60);
	$item_output[$id]['reason']=stripslashes($row['reason']);
	$item_output[$id]['author']=stripslashes($row['author']);
	$item_output[$id]['del_url']="penalize.php?comp_id=$comp_id&item_id=$id&flag=2";
}
if($_GET['just_edited'])
	$just_edited=(int)$_GET['just_edited'];
else
	$just_edited=0;
$title="Управление пенализациями";
require('admin_header.php');
require('_templates/penalize.phtml');
