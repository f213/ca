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

if(empty($_GET['comp_id']) and empty($_POST['comp_id']))
	die('Не указан id соревнования!');

if($_POST['comp_id'])
	$comp_id=(int)$_POST['comp_id'];
else
	$comp_id=(int)$_GET['comp_id'];


$res=query_eval("SELECT cat_id FROM $compcatvar_dbt WHERE comp_id=$comp_id AND (type='gps' OR type='gr-gps');");
if(!mysql_num_rows($res)){
	require('admin_header.php');
	die('для заданного соревнования не задано ни одной категории учатсников с GPS!');
}

$back_url="competitions.php";

$flag=(int)$_GET['flag'];

if($flag){//тута обработка фильтров
	$filters_str="comp_id=$comp_id";
	if($_GET['f_category'])
		$filters_str.="&f_category={$_GET['f_category']}";
}	

$gps_cats=array();
while($row=mysql_fetch_row($res))
	$gps_cats[]=(int)$row[0];
$cat_id=(int)$_GET['f_category'];
if($flag){ //если задан тип действия - сразу проверяем, задана ли категория
	if(!$cat_id)
		die('не заданна категория!');
}
//ну и заодно получим список уже добавленных точек
$used_nums=array();
$used_nums_str="";
if($cat_id){
	$res=query_eval("SELECT name FROM $compgps_dbt WHERE `cat_id`=$cat_id;");
	while($row=mysql_fetch_row($res)){
		$used_nums[]=(int)$row[0];
		$used_nums_str.="'{$row[0]}',";
	}
	$used_nums_str=trim($used_nums_str,',');
}	
switch($flag){
case 1: //массовое добавление точек
	$begin=(int)$_GET['num_begin'];
	if(!$begin)
		die('не заданно начало диапазона!');
	$end=(int)$_GET['num_end'];
	if(!end)
		die('не задан конец диапазона!');
	$cost=(int)$_GET['cost'];
	if(!$cost)
		die('не заданна стоимость!');
	for ($i=$begin;$i<=$end;$i++){
		if(in_array($i,$used_nums))
			continue;
		$add_data=array(
			'comp_id'=>$comp_id,
			'cat_id'=>$cat_id,
			'name'=>$i,
			'cost'=>$cost,
			'active'=>'yes',
		);
		add_item($compgps_dbt,$add_data);
	}
	header("Location: ".append_rnd("gps-kp.php?$filters_str&multiple_added=1"));
	die();
	break;
case 2: //добавление одной точки
	$num=addslashes($_GET['num']);
	if(!$num)
		die('не задан номер!');
	if(in_array($num,$used_nums))
		die('точка с таким номером уже есть!');
	$cost=(int)$_GET['cost'];
	if(!$cost)
		die('не заданна стоимость!');
	$add_data=array(
		'comp_id'=>$comp_id,
		'cat_id'=>$cat_id,
		'name'=>$num,
		'cost'=>$cost,
		'active'=>'yes',
	);
	add_item($compgps_dbt,$add_data);
	header("Location: ".append_rnd("gps-kp.php?$filters_str&one_added=1"));
	die();
	break;
case 3: //массовое удаление точек для категории
	query_eval("DELETE FROM $compgps_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id;");
	header("Location: gps-kp.php?$filters_str");
	die();
	break;
case 4: //стоимость отдельной точки
	$item_id=(int)$_GET['item_id'];
	if(!$item_id)
		die('не указан id точки!');
	$cost=(int)$_GET['cost'];
	if(!$cost)
		die('не указана стоимость!!');
	query_eval("UPDATE $compgps_dbt SET `cost`=$cost WHERE id=$item_id LIMIT 1;");
	header("Location: gps-kp.php?$filters_str");
	die();
	break;

case 5: //активность отдельной точки
	$item_id=(int)$_GET['item_id'];
	if(!$item_id)
		die('не указан id точки!');
	if($_GET['active']=='1')
		$active='yes';
	else
		$active='no';
	query_eval("UPDATE $compgps_dbt SET `active`='$active' WHERE id=$item_id LIMIT 1;");
	header("Location: gps-kp.php?$filters_str");
	die();
	break;
case 6: //удаление точки
	$item_id=(int)$_GET['item_id'];
	if(!$item_id)
		die('не указан id точки!');
	query_eval("DELETE FROM $compgps_dbt WHERE id=$item_id LIMIT 1;");
	header("Location: gps-kp.php?$filters_str");
	die();
	break;
		
case 7: //комментарий отдельной точки
	$item_id=(int)$_GET['item_id'];
	if(!$item_id)
		die('не указан id точки!');
	$comment=addslashes($_GET['comment']);
	query_eval("UPDATE $compgps_dbt SET `comment`='$comment' WHERE id=$item_id LIMIT 1;");
	header("Location: gps-kp.php?$filters_str");
	die();
	break;
case 8: //необходимость отдельной точки
	$item_id=(int)$_GET['item_id'];
	if(!$item_id)
		die('не указан id точки!');
	if($_GET['required']=='1')
		$required='yes';
	else
		$required='no';
	query_eval("UPDATE $compgps_dbt SET `required`='$required' WHERE id=$item_id LIMIT 1;");
	header("Location: gps-kp.php?$filters_str");
	die();
	break;		
}	








$filters_sql="AND 1 ";
$filters_str="filters=1";
if($_GET['f_category'] and in_array((int)$_GET['f_category'],$gps_cats)){
	$f_category=(int)$_GET['f_category'];
	$filters_str.="&f_category=$f_category";
	$filters_sql.=" AND cat_id=$f_category";
}



if($f_category){ //дальше работаем, тока если заданна категория
	$res=query_eval("SELECT * FROM $compgps_dbt WHERE comp_id=$comp_id $filters_sql ORDER BY `active` ASC, `name` ASC;");
	while($row=mysql_fetch_assoc($res)){
		$id=(int)$row['id'];
		$item_output[$id]['num']=stripslashes($row['name']);
		$item_output[$id]['cost']=stripslashes($row['cost']);
		$item_output[$id]['comment']=stripslashes($row['comment']);
		if($row['active']=='yes')
			$item_output[$id]['active']=true;
		else
			$item_output[$id]['active']=false;
		if($row['required']=='yes')
			$item_output[$id]['required']=true;
		else
			$item_output[$id]['required']=false;
		$item_output[$id]['delete_link']="gps-kp.php?comp_id=$comp_id&f_category=$f_category&flag=6&item_id=$id";
	}
}	

$title="Управление списком GPS-точек";
require('admin_header.php');
require('_templates/gps-kp.phtml');



