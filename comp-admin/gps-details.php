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

require_once('_includes/request_functions.php');
require_once('_includes/started_functions.php');

$comp_types=array('gps','gr-gps'); //типы соревнований, с которыми работает этот скрипт

$points_mult=GR_POINTS_MULT; //множитель баллов, для типа gr-gps
if(!$points_mult)
	$points_mult=1;


$comp_id=(int)$_GET['comp_id'];
if(!$comp_id)
	die('некорректно указан id соревнования!');
$start_number=(int)$_GET['start_number'];
if(!$start_number)
	die('некорректно указан бортовой номер!');

//категория участника
$cat_id=get_cat($comp_id,$start_number);

$comp_type=get_type_by_sn($comp_id,$start_number);

if(!in_array($comp_type,$comp_types))
	die("Тип соревнования ($comp_type) не поддерживается этим скриптом!");

//макс время на трассе для категории
$max_time=max_time_by_cat_id($comp_id,$cat_id);
//количество участников
$res=query_eval("SELECT COUNT(id) FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id");
if(mysql_num_rows($res)){
	$row=mysql_fetch_row($res);
	$total_cat_uch=(int)$row[0];
}
//точки для категории
$res=query_eval("SELECT id,name,cost,comment FROM $compgps_dbt WHERE `comp_id`=$comp_id AND `cat_id`=$cat_id AND `active`='yes' ORDER BY name ASC;");

if(!mysql_num_rows($res))
	die("Для категории заданного номера ($cat_id) не задано ни одной GPS-точки");
$point_data=array();
$total_cost=0; //суммарная стоимость всех точек
$total_count=0; //количество всех точек
$taken_count=0; //кол-во взятых точек
$taken_cost=0; //стоимость взятых точек
$untaken_count=0; //кол-во невзятых точек
$untaken_cost=0; //стоимость невзятых точек

while($row=mysql_fetch_row($res)){
	$id=(int)$row[0];
	$point_data[$id]['name']=stripslashes($row[1]);
	$point_data[$id]['cost']=(int)$row[2];
	if($row[3])
		$point_data[$id]['name']=stripslashes($row[3]).'('.stripslashes($row[1]).')';
	$total_cost+=(int)$row[2];
	$total_count++;
}

foreach($point_data as $point_id=>$value){
	$item_output['points_data'][$point_id]['name']=$value['name'];
	$item_output['points_data'][$point_id]['cost']=$value['cost'];

	//проверяем, взял ли экипаж точку
	$res=query_eval("SELECT * FROM $compgpsres_dbt WHERE comp_id=$comp_id AND start_number=$start_number AND point_id=$point_id;");
	if(mysql_num_rows($res)){
		$item_output['points_data'][$point_id]['taken']=true;
		$row=mysql_fetch_assoc($res);
		$item_output['points_data'][$point_id]['author']=stripslashes($row['author']);

		$taken_cost+=$value['cost']; //здеся суммируем все взятые участником точки
		$taken_count++; //
	}else{ //не взял..
		$item_output['points_data'][$point_id]['taken']=false;
		if($comp_type=='gr-gps'){ //для ЗЛ штрафуем за невзятую точку
			$untaken_cost+=$value['cost'];
			$untaken_count++;
		}
	}
	//проверяем, скока народу вообще взяло эту точку
	$res=query_eval("SELECT COUNT(*) FROM $compgpsres_dbt WHERE comp_id=$comp_id AND point_id=$point_id;");
	if(mysql_num_rows($res)){
		$row=mysql_fetch_row($res);
		$item_output['points_data'][$point_id]['other_taked']=(int)$row[0];
	}else{ //никто не взял..
		$item_output['points_data'][$point_id]['other_taked']='-';
	}

}	

//время на трассе
$res=query_eval("SELECT * FROM $compgpstime_dbt WHERE comp_id=$comp_id AND start_number=$start_number;");
if(!mysql_num_rows($res))
	die("Ошибка получения времени на трассе");
$row=mysql_fetch_assoc($res);
$start_time=(int)$row['start_time'];
$finish_time=(int)$row['finish_time'];
$trassa_time=$finish_time-$start_time;

//время пенализации
$pinok_time=0;
$res=query_eval("SELECT time FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number=$start_number");
if(mysql_num_rows($res)){
	$row=mysql_fetch_row($res);
	$pinok_time=(int)$row[0]*60;
}

//бонусное время
$bonus_time=0;
$res=query_eval("SELECT time FROM $compbont_dbt WHERE comp_id=$comp_id AND start_number=$start_number");
if(mysql_num_rows($res)){
	$row=mysql_fetch_row($res);
	$bonus_time=(int)$row[0]*60;
}

//бонусные баллы
$bonus_points=0;
$res=query_eval("SELECT points FROM $compbonp_dbt WHERE comp_id=$comp_id AND start_number=$start_number");
if(mysql_num_rows($res)){
	$row=mysql_fetch_row($res);
	$bonus_points=(int)$row[0];
}

$final_time=$trassa_time+$pinok_time-$bonus_time; //время с учетом пенализаций и бонусов
$final_cost=$taken_cost+$bonus_points;

//обязательные точки
//сначала узнаем количество обязательных точек, необходимых для категории
$res=query_eval("SELECT  COUNT(id)  FROM $compgps_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id AND `required`='yes' AND `active`='yes';");
$row=mysql_fetch_row($res);
$required4cat_points=(int)$row[0];

//теперь считаем количество взятых участником обязательных точек
$res=query_eval("SELECT COUNT(*) FROM $compgpsres_dbt WHERE comp_id=$comp_id AND start_number=$start_number AND point_id IN (SELECT id FROM  $compgps_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id AND `required`='yes' AND `active`='yes');");
$row=mysql_fetch_row($res);
$required_points_sum=(int)$rpw[0];

////
if($comp_type=='gr-gps') //время с учетом штрафов за невзятые точки
	$realy_final_time=$final_time+($total_cost-$final_cost)*$points_mult*60;


$item_output['comp_type']=$comp_type; //тип соревнования
$item_output['cat_name']=$cat_name[$cat_id]; 
$item_output['total_cat_uch']=$total_cat_uch;
if(_cat_var($comp_id,$cat_id,'need_tk'))
	$item_output['need_tk']=true;
else
	$item_output['need_tk']=false;
$item_output['max_time']=format_hms_time($max_time,$_null_sec_bool); //макс время на трассе
if($comp_type=='gr-gps')
	$item_output['points_mult']=$points_mult;


$item_output['time']['start']=format_hms_time($start_time,$_null_sec_bool); //время старта
$item_output['time']['finish']=format_hms_time($finish_time,$_null_sec_bool); //время финиша
$item_output['time']['trassa']=format_hms_time($trassa_time,$_null_sec_bool); //время на трассе
$item_output['time']['final']=format_hms_time($final_time,$_null_sec_bool); //время с учетом бонусов и пенализаций
$item_output['time']['bonus']=format_hms_time($bonus_time,$_null_sec_bool);
if($pinok_time)
	$item_output['has_pinok']=true;
else
	$item_output['has_pinok']=false;
if($bonus_time)
	$item_output['has_bonus']=true;
else
	$item_output['has_bonus']=false;

if($comp_type=='gr-gps')
	$item_output['time']['realy']=format_hm_time($realy_final_time);

$item_output['points']['total']=$total_count; //все точки в категории
$item_output['points']['total_cost']=$total_cost; //стоимость всех точек в категории

$item_output['points']['taken']=$taken_count; //количество взятых точек
$item_output['points']['taken_cost']=$taken_cost; //стоимость взятых точек
$item_output['points']['final_cost']=$final_cost; //стоимость взятых точек с учетом бонусов
if($comp_type=='gr-gps'){
	$item_output['points']['untaken']=$untaken_count; //количество невзятых точек
	$item_output['points']['untaken_cost']=$untaken_cost; //стомость невзятых точек
}


//данные участника
$item_output['req_data']=get_brief_request_data($comp_id,num2req($comp_id,$start_number));
if($item_output['need_tk'])
	$item_output['req_data']['tk_is_passed']=tk_is_passed($comp_id,$start_number);
$page_title=$title=comp_name($comp_id)."\nКарта подсчета участника №$start_number";
require('_templates/print_header.phtml');
require('_templates/gps-details.phtml');

