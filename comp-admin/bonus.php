<?
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
require_once('_includes/auth.php');

if(empty($_GET['comp_id']))
	die('Не указан id соревнования!');

$comp_id=(int)$_GET['comp_id'];

//flags
if($_GET['flag'])
	$flag=(int)$_GET['flag'];

$valid_numbers=get_full_valid_numbers($comp_id);
$valid_numbers_str=get_valid_numbers_str($valid_numbers);

//получаем списки тех, кто стартовал по gps или gr-gps, им можно добавлять так же бонусы по баллам
$res=query_eval("SELECT DISTINCT(cat_id) FROM $compcatvar_dbt WHERE comp_id=$comp_id;");
$p_valid_numbers=array();
while($row=mysql_fetch_row($res))
	$sn[$row[0]]=get_started_numbers($comp_id,(int)$row[0]);

if($sn)
	foreach($sn as $value)
		if($value)
			foreach($value as $num)
				$p_valid_numbers[]=$num;

$p_valid_numbers_str=get_valid_numbers_str($p_valid_numbers);

//получаем список номеров уже с бонусом, чтобы шаблон орал, когда бонус редактируют.
$res=query_eval("SELECT DISTINCT(start_number) FROM $compbont_dbt WHERE comp_id=$comp_id;");
$t_bonused_numbers=array();
while($row=mysql_fetch_row($res))
	$t_bonused_numbers[]=(int)$row[0];
$res=query_eval("SELECT DISTINCT(start_number) FROM $compbont_dbt WHERE comp_id=$comp_id;");
$p_bonused_numbers=array();
while($row=mysql_fetch_row($res))
	$p_bounsed_numbers[]=(int)$row[0];
$t_bonused_numbers_str=get_valid_numbers_str($t_bonused_numbers);
$p_bonused_numbers_str=get_valid_numbers_str($p_bonused_numbers);


//автодополнение причин
$res=query_eval("SELECT reason FROM $compbont_dbt UNION SELECT reason FROM $compbonp_dbt ORDER BY reason ASC;");
$reasons=array();
while($row=mysql_fetch_row($res))
	$reasons[]="'".stripslashes($row[0])."'";
$reasons_str=get_valid_numbers_str($reasons);

if($flag)
	switch($flag){
	case 1: //добавление бонуса по времени
		$start_number=(int)$_GET['start_number'];
		if(!in_array($start_number,$valid_numbers))
			die("номер ($start_number) отсутсвует в стартовой ведомости!");

		$min=(int)$_GET['min'];
		$reason=addslashes($_GET['reason']);
		if(!$min)
			die('Не указано время');
		if(strlen($reason)<=3)
			die('не указана причина!');
		query_eval("REPLACE INTO $compbont_dbt SET start_number='$start_number', comp_id=$comp_id, time='$min', reason='$reason', author='$admin_user';");
		header("Location: bonus.php?comp_id=$comp_id&added=t");
		exit;
		break;
	case 2: //добавление бонуса по баллам
		$start_number=(int)$_GET['start_number'];
		if(!in_array($start_number,$p_valid_numbers))
			die("номер ($start_number) не стартовал по GPS!");
		$points=(int)$_GET['points'];
		$reason=addslashes($_GET['reason']);
		if(!$points)
			die("Не указано количество очков");
		if(strlen($reason)<=3)
			die('Не указана причина');
		query_eval("REPLACE INTO $compbonp_dbt SET start_number='$start_number', comp_id=$comp_id, points='$points', reason='$reason',author='$admin_user';");
		header("Location: bonus.php?comp_id=$comp_id&added=b;");
		exit;
		break;
	case 3: //удаление бонуса
		$item_id=(int)$_GET['item_id'];
		if(!$item_id)
			die('не указан item_id');
		if($_GET['bonus_type']=='t')
			$dbt=$compbont_dbt;
		else
			$dbt=$compbonp_dbt;
		query_eval("DELETE FROM $dbt WHERE id='$item_id' AND comp_id=$comp_id LIMIT 1;");
		header("Location: bonus.php?comp_id=$comp_id");
		exit;
		break;
	}
$res=query_eval("SELECT CONCAT('t-',id) AS id, id AS item_id, start_number, `time`, '-' AS points, reason, author, 'time' AS `type` FROM $compbont_dbt WHERE comp_id=$comp_id  UNION SELECT CONCAT('p-',id) AS id, id AS item_id, start_number, '-' AS `time`, points, reason, author, 'points' AS `type` FROM $compbonp_dbt WHERE comp_id=$comp_id ORDER BY start_number ASC;");
while($row=mysql_fetch_assoc($res)){
	$id=$row['id'];
	$item_id=(int)$row['item_id'];
	$item_output[$id]['start_number']=(int)$row['start_number'];
	$item_output[$id]['request_id']=$request_id=num2req($comp_id,$item_output[$id]['start_number']);
	$item_output[$id]=get_brief_request_data($comp_id,$request_id,$item_output[$id]);
	if($row['time']!='-')
		$item_output[$id]['min']=gmdate('H:i',(int)$row['time']*60);
	else
		$item_output[$id]['min']='-';
	$item_output[$id]['points']=$row['points'];
	$item_output[$id]['reason']=stripslashes($row['reason']);
	$item_output[$id]['author']=stripslashes($row['author']);
	$item_output[$id]['del_url']="bonus.php?flag=3&comp_id=$comp_id&item_id=$item_id&bonus_type=".substr($row['type'],0,1);
}
$title="Управление бонусами";
require('admin_header.php');
require('_templates/bonus.phtml');
