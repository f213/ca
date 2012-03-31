<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//TODO вынести работу с бонусами в отдельную библиотеку, которую использовать пока здесь и bonus.php
//
require_once('../_includes/core.php');
require_once('_includes/auth.php');

if(empty($_GET['comp_id']) and empty($_POST['comp_id']))
	die('Не указан id соревнования!');

if($_POST['comp_id'])
	$comp_id=(int)$_POST['comp_id'];
else
	$comp_id=(int)$_GET['comp_id'];


//получаем список всех зарегеных участников, у которых тип соревнования - Золотая Лихорадка
$res=query_eval("SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id IN ( SELECT cat_id FROM $compcatvar_dbt WHERE type='gr-gps');");
$valid_numbers=array();
$valid_numbers_str="";
while($row=mysql_fetch_row($res)){
	$valid_numbers[]=(int)$row[0];
	$valid_numbers_str.="'{$row[0]}',";
}
$valid_numbers_str=trim($valid_numbers_str,',');


$flag=(int)$_GET['flag'];

switch($flag){
case 1:// добавление результата
	$start_number=(int)$_GET['start_number'];
	if(!$start_number)
		die('не указан бортовой номер');
	if(!in_array($start_number,$valid_numbers))
		die('Указан некорректный стартовый номер!');
	$points=(int)$_GET['points'];
	if(!$points)
		die('не указано количество штрафных очков');
	query_eval("REPLACE INTO $compgrdsu_dbt SET start_number='$start_number', comp_id='$comp_id', points='$points';");
	header("Location: grdsu.php?comp_id=$comp_id");
	die();
	break;
case 2:// добавление бонусов в базу
	$min=(int)$_GET['bon_min'];
	$max=(int)$_GET['bon_max'];
	if(!$min or !$max)
		die('Неверно указан промежуток бонусов!');
	$item_output=calc_bonus(get_dsu_data(),$min,$max);
	foreach($item_output as $key=>$value){
		$data=array(
			'comp_id'=>$comp_id,
			'start_number'=>$key,
			'points'=>$value['bon_int'],
			'reason'=>"auto:grdsu={$value['place']}place",
			'author'=>$admin_user,
		);
		add_item($compbonp_dbt,$data);
	}
	header("Location: grdsu.php?comp_id=$comp_id");
	die();
	break;
case 3: //очистить добавленные бонусы
	query_eval("DELETE FROM $compbonp_dbt WHERE `reason` LIKE 'auto:grdsu=%' AND comp_id=$comp_id;");
	header("Location: grdsu.php?comp_id=$comp_id");
	die();
	break;	
case 4: //удаление одной записи из таблици результатов ДСУ
	$start_number=(int)$_GET['start_number'];
	if(!$start_number)
		die('Не указан бортовой номер');
	query_eval("DELETE FROM $compgrdsu_dbt WHERE comp_id=$comp_id AND start_number='$start_number'");
	$loc="grdsu.php?comp_id=$comp_id";
	if($_GET['bon_min'] and $_GET['bon_max'])
		$loc=append_bonus($loc,(int)$_GET['bon_min'],(int)$_GET['bon_max']);
	header("Location: $loc");
	die();
	break;


}


$item_output=get_dsu_data();

$bonus_is_calced=false;
if($_GET['bon_max'] and $_GET['bon_min']){
	$item_output=calc_bonus($item_output,(int)$_GET['bon_min'],(int)$_GET['bon_max']);
	$bonus_is_calced=true;
}

$print_link=append_bonus("grdsu.php?comp_id=$comp_id&print=yes",(int)$_GET['bon_min'],(int)$_GET['bon_max']);

if($_GET['print'] and $_GET['print']=='yes'){
	require('_templates/grdsu_print.phtml');
}else{
	$title="СУ-Площадь (Золотая Лихорадка)";
	require('admin_header.php');
	require('_templates/grdsu.phtml');
}
///////////
function get_dsu_data(){
	global $compgrdsu_dbt,$compreq_dbt,$compres_dbt,$comp_id;
	$res=query_eval("SELECT b.PilotName AS PilotName, b.PilotNik AS PilotNik, b.NavigatorName AS NavigatorName, b.NavigatorNik AS NavigatorNik, b.AutoBrand AS AutoBrand, b.AutoNumber AS AutoNumber, a.points AS points, a.start_number AS start_number FROM $compgrdsu_dbt a, $compreq_dbt b, $compres_dbt c WHERE c.start_number=a.start_number AND b.id=c.request_id  AND a.comp_id=$comp_id ORDER BY points ASC;");
	$place=0;
	$prev_points=0;
	while($row=mysql_fetch_assoc($res)){
		$sn=$row['start_number'];
		$item_output[$sn]['pilot_name']=stripslashes($row['PilotName']);
		if($row['PilotNik'])
		$item_output[$sn]['pilot_name'].=' ('.stripslashes($row['PilotNik']).')';
		$item_output[$sn]['navigator_name']=stripslashes($row['NavigatorName']);
		if($row['NavigatorNik'])
			$item_output[$sn]['navigator_name'].=' ('.stripslashes($row['NavigatorNik']).')';
		$item_output[$sn]['auto_brand']=stripslashes($row['AutoBrand']);

		$points=(int)$row['points'];
		if($points>$prev_points)
			$place++;
		$prev_points=$points;
		$item_output[$sn]['points']=$points;
		$item_output[$sn]['place']=$place;
		$item_output[$sn]['del_link']="grdsu.php?comp_id=$comp_id&start_number=$sn&flag=4";
		if($_GET['bon_min'] and $_GET['bon_max']) //если уже были рассчитаны бонусы не надо их проебывать
			$item_output[$sn]['del_link']=append_bonus($item_output[$sn]['del_link'],(int)$_GET['bon_min'],(int)$_GET['bon_max']);
	}
	return $item_output;
}
function calc_bonus($item_output,$min,$max){
	$max_place=0; //максимальное место
	foreach($item_output as $key=>$value)
		if($value['place']>$max_place)
			$max_place=$value['place'];
	
	$prom=($max-$min)/($max_place-1);
	$current_bonus=$max+$prom;
	$prev_place=0;
	foreach($item_output as $key=>$value){
		if($value['place']>$prev_place)
			$current_bonus-=$prom;
		$item_output[$key]['bon_int']=$current_bonus;
		$item_output[$key]['bon_str']=gmdate('H:i',$current_bonus*60);
		$prev_place=$value['place'];
		
	}
	return $item_output;
}
function append_bonus($link,$min,$max){
	return $link."&bon_min=$min&bon_max=$max";
}
