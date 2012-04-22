<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

/*
Этот скрипт занимается результатми только одного соревнования. Для работы на гонках с несколькими СУ (соревнованиями) скрипт может "зафиксировать" результаты для того, чтобы потом смотреть итоги по нескольких соревнованиям.

На данный момент фиксация производится в таблице
CREATE TABLE IF NOT EXISTS `phpbb_CA_ResultsFixed` (
  `comp_id` int(10) unsigned NOT NULL,
  `start_number` int(10) unsigned NOT NULL,
  `place` int(10) unsigned NOT NULL,
  `active` enum('yes','no') NOT NULL,
  UNIQUE KEY `main` (`comp_id`,`start_number`)
) ENGINE=MyISAM

Поле 'active' означает идет результат в зачет или нет.

Формирование результатов следует производить один раз за сессию, потом при необходимости сделать с ними что-то кроме вывода нужно брать тот же массив, что передается в шаблон. Это сделано потому, что запрос подсчета весьма сложен, и использует почти все имеющиеся в БД таблицы, то есть его не стоит выносить в отдельную функцию.



 */
require_once('../_includes/core.php');
require_once('_includes/auth.php');
require_once('_includes/started_functions.php');
require_once('_includes/disq.php');
require_once('_includes/request_functions.php');
require_once('_includes/export_results.php');
require_once('_includes/raf_score_system.php');

$finish_types=array(
	0=>'Все',
	1=>'Финишировавшие',
	2=>'Не финишировавшие',
	3=>'Во время',
);
$kp_types=array(
	0=>'Все',
	1=>'Взял все',
	2=>'Взял не все',
	3=>'Ни одного',
);
$result_types=array(
	0=>'Все',
	1=>'Есть',
	2=>'Нет',
);
$pinok_types=array(
	0=>'Все',
	1=>'Есть',
	2=>'Нет',
);
$bonus_types=array(
	0=>'Все',
	1=>'Есть',
	2=>'Нет',
);
if(empty($_GET['comp_id']) and empty($_POST['comp_id']))
	die('Не указан id соревнования!');

if($_POST['comp_id'])
	$comp_id=(int)$_POST['comp_id'];
else
	$comp_id=(int)$_GET['comp_id'];


for($i=1;$i<=_CATEGORIES;$i++)
	$cat_types[$i]=_cat_var($comp_id,$i,'type');
if($_GET['flag'])
	$flag=(int)$_GET['flag'];
if($flag){//тута обработка фильтров
	$filters_str="comp_id=$comp_id";
	if($_GET['f_category']){
		$f_category=(int)$_GET['f_category'];
		$filters_str.="&f_category=$f_category";
	}
	if($_GET['f_finished'])
		$filters_str.="&f_finished=".(int)$_GET['f_finished'];
	if($_GET['f_kp'])
		$filters_str.="&f_kp=".(int)$_GET['f_kp'];
	if($_GET['f_result'])
		$filters_str.="&f_result=".(int)$_GET['f_result'];
	if($_GET['f_pinok'])
		$filters_str.="&f_pinok=".(int)$_GET['f_pinok'];
	if($_GET['f_time_bonus'])
		$filters_str.="&f_time_bonus=".(int)$_GET['f_time_bonus'];
	if($_GET['f_points_bonus'])
		$filters_str.="&f_points_bonus=".(int)$_GET['f_points_bonus'];


}

if($_GET['f_category'])
	$f_category=(int)$_GET['f_category'];
if($f_category){
	//получаем максимальное количество времени на трассе
	$cat_id=$f_category;
	$max_time=max_time_by_cat_id($comp_id,$cat_id);
	$type=get_type_by_cat_id($comp_id,$cat_id);
	if($type=='legend'){
		//ну и количество КП для тех, кто по легенде тоже получаем
		$max_kp=legend_max_kps($comp_id,$cat_id);
		if(!$max_kp)
			die("ошибка получения количества КП(легенда) для категории ($f_category)");
	}
	//проверяем, стартовала ли вообще такая категория
	$res=query_eval("SELECT id FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id=$f_category;");
	if(!mysql_num_rows($res))
		die("Для заданной категории ($f_category) не найдено ни одной записи в стартовом протоколе!");
}

//получаем список всех стратовавших номеров
$started_numbers=get_full_valid_numbers($comp_id);


//тута флаги будут

switch($flag){
case 1: //добавление комментария
	$start_number=(int)$_GET['start_number'];
	if(!$start_number)
		die('некорректно указан стартовый номер!');
	if(!in_array($start_number,$started_numbers))
		die("Указан нестратовавший стартовый номер ($start_number)!");
	$comment=addslashes($_GET['comment']);
	if(!$comment)
		die('не указан комментарий!');
	query_eval("UPDATE $compres_dbt SET `comment`='$comment'  WHERE comp_id=$comp_id AND start_number=$start_number LIMIT 1;");
	header("Location: results.php?$filters_str");
	die();
	break;
}

//готовим вывод данных
//обработка фильтров
//$compres_dbt - c
//$complegres_dbt - d
//$compgpsres_dbt - e
//$compgpstime_dbt - g
if($cat_id){ //дальше работаем, тока если есть категория
$filters_sql="AND 1 ";
if($_GET['f_finished']){
	$f_finished=(int)$_GET['f_finished'];
	switch($f_finished){
	case 1: //финишировавшие
		if($type=='legend')
			$filters_sql.=" AND d.finish_time!=0 ";
		if($type=='gps' or $type=='gr-gps')
			$filters_sql.=" AND g.finish_time!=0 ";
		break;
	case 2: //не финишировавшие
		if($type=='legend')
			$filters_sql.=" AND d.finish_time=0 ";
		if($type=='gps' or $type=='gr-gps')
			$filters_sql.=" AND g.finish_time=0 ";
		break;
	case 3: //финишировавшие во время
		if($type=='legend')
			$filters_sql.=" AND ((d.finish_time-d.start_time)+IFNULL((SELECT `time` FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60-IFNULL((SELECT `time` FROM $compbont_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60)<$max_time ";
		if($type=='gps' or $type=='gr-gps')
			$filters_sql.=" AND ((g.finish_time-g.start_time)+IFNULL((SELECT `time` FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60-IFNULL((SELECT `time` FROM $compbont_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60)<$max_time ";
	break;
	}	
}
if($_GET['f_pinok']){
	$f_pinok=(int)$_GET['f_pinok'];
	switch($f_pinok){
	case 1: //с пинком
		$filters_sql.=" AND (SELECT `time` FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number=c.start_number) IS NOT NULL ";
		break;
	case 2: //без пинка
		$filters_sql.=" AND (SELECT `time` FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number=c.start_number) IS NULL ";
		break;
	}
}
if($_GET['f_time_bonus']){
	$f_time_bonus=(int)$_GET['f_time_bonus'];
	switch($f_time_bonus){
	case 1: // с бонусом
		$filters_sql.=" AND (SELECT `time` FROM $compbont_dbt WHERE comp_id=$comp_id AND start_number=c.start_number) IS NOT NULL ";
		break;
	case 2: //без бонуса
		$filters_sql.=" AND (SELECT `time` FROM $compbont_dbt WHERE comp_id=$comp_id AND start_number=c.start_number) IS NULL ";
		break;
	}
}
if($_GET['f_points_bonus'] and ($type=='gps' or $type=='gr-gps')){
	$f_points_bonus=(int)$_GET['f_points_bonus'];
	switch($f_points_bonus){
	case 1: //с бонусом
		$filters_sql.=" AND (SELECT `points` FROM $compbonp_dbt WHERE comp_id=$comp_id AND start_number=c.start_number) IS NOT NULL ";
		break;
	case 2: //без бонуса
		$filters_sq.=" AND (SELECT `points` FROM $compbonp_dbt WHERE comp_id=$comp_id AND start_number=c.start_number) IS NULL ";
		break;
	}
}
if($_GET['f_kp'] and $type=='legend'){ //фильтр КП обрабатываем тока если полегенде
	$f_kp=(int)$_GET['f_kp'];
	switch($f_kp){
	case 1:
		$filters_sql.=" AND d.kps=$max_kp ";
		break;
	case 2:
		$filters_sql.=" AND d.kps>0 AND d.kps<$max_kp";
		break;
	case 3:
		$filters_sql.=" AND d.kps=0 ";
	}
}
if($_GET['f_result']){ //наличие результатов обработки
	$f_result=(int)$_GET['f_result'];
	switch($f_result){
	case 1: //есть
		if($type=='legend')
			$filters_sql.=" AND (d.finish_time!=0 OR d.kps!=0) ";
		if($type=='gps' or $type=='gr-gps') //
				$filters_sql.=" AND EXISTS (SELECT * FROM $compgpsres_dbt WHERE comp_id=$comp_id AND start_number=c.start_number)";
		
		break;
	case 2: //нету
		if($type=='legend')
			$filters_sql.=" AND d.finish_time = 0 AND d.kps = 0";
		if($type=='gps' or $type=='gr-gps') //ну тут принцип как выше, тока наоборот
				$filters_sql.=" AND NOT EXISTS (SELECT * FROM $compgpsres_dbt WHERE comp_id=$comp_id AND start_number=c.start_number)";

			//тока тут ничего писать не будем, потому что и так все показываются.	
		break;
	}
}	
if($type=='gps' or $type=='gr-gps'){
	//получаем количество и список обязательных для взятия точек, если работаем по gps
	$res=query_eval("SELECT id,name,comment FROM $compgps_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id AND `required`='yes' AND `active`='yes';");
	$required4cat_count=mysql_num_rows($res);
	while($row=mysql_fetch_assoc($res)){
		$required4cat_str.=stripslashes($row['name']).'('.stripslashes($row['comment']).'),';
	}
	$required4cat_str=trim($required4cat_str,',');
}
if($type=='legend'){
	//получаем максимальное количество КП
	$maxkp4cat_count=legend_max_kps($comp_id,$cat_id);
}
//запрос на проверку снятия с соревнований одинаков для всех типов соревнования
define(TAKED_OFF_QUERY,"IF(
	(SELECT COUNT(*) FROM $compdisq_dbt WHERE start_number=c.start_number AND (
		(comp_id=$comp_id AND type='current') OR
		(comp_id>=$comp_id AND type='next') OR 
		type = 'full'
	)),1,0) AS taked_off");


//самое интересное, щас ваще пиздец будет, такое можно только написать, читать это нельзя
//тут был совсем страшный код и я его похерил
//вроде все
//а тепреь заново и понятно
//
if($type=='gps'){
	$sql="SELECT c.start_number, c.comment, c.portal, c.winch,
		".TAKED_OFF_QUERY." ,
		IFNULL((SELECT SUM(z.cost) FROM $compgps_dbt z, $compgpsres_dbt x WHERE z.id=x.point_id AND z.active='yes' AND x.start_number=c.start_number AND z.comp_id=$comp_id),0) AS total_cost,
		IFNULL((SELECT SUM(z.cost) FROM $compgps_dbt z, $compgpsres_dbt x WHERE z.id=x.point_id AND z.active='yes' AND x.start_number=c.start_number AND z.comp_id=$comp_id)+IFNULL((SELECT `points` FROM $compbonp_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0),0) AS final_cost,
		(SELECT COUNT(DISTINCT(z.id)) FROM $compgps_dbt z, $compgpsres_dbt x WHERE z.id=x.point_id AND z.active='yes' AND x.start_number=c.start_number AND z.comp_id=$comp_id) AS points_sum,
		(g.finish_time-g.start_time) AS total_time,
		IFNULL((SELECT `time` FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60 AS pinok_time,
		IFNULL((SELECT `time` FROM $compbont_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60 AS bonus_time,
		IFNULL((SELECT `points` FROM $compbonp_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0) AS bonus_points,
		(g.finish_time-g.start_time)+IFNULL((SELECT `time` FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60-IFNULL((SELECT `time` FROM $compbont_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60 AS final_time,
		(SELECT  COUNT(id)  FROM $compgps_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id AND `required`='yes' AND `active`='yes') AS required4cat_count,
		(SELECT COUNT(*) FROM $compgpsres_dbt WHERE comp_id=$comp_id AND start_number=c.start_number AND point_id IN (SELECT id FROM  $compgps_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id AND `required`='yes' AND `active`='yes')) AS required_points_sum,
		g.start_time, g.finish_time,
		a.id AS request_id, a.PilotName, a.PilotNik, a.NavigatorName, a.NavigatorNik,a.city, a.AutoBrand, a.AutoNumber, a.WheelSize
		FROM $compreq_dbt a, $compres_dbt c, $compgpstime_dbt g
	WHERE
	a.comp_id=$comp_id AND c.comp_id=$comp_id AND g.comp_id=$comp_id AND
	c.request_id=a.id AND a.category=$cat_id AND c.cat_id=$cat_id AND
	g.start_number=c.start_number

	$filters_sql
	ORDER BY
		taked_off ASC,
		IF (final_time<=$max_time,0,1) ASC,
		IF (required_points_sum=required4cat_count,0,1) ASC,
		final_cost DESC, 
		final_time ASC, a.WheelSize ASC, a.RegisterDate ASC";
}
if($type=='gr-gps'){
	$points_mult=GR_POINTS_MULT;
	if(!$points_mult)
		$points_mult=1;
	$sql="SELECT c.start_number, c.comment, c.portal, c.winch,
	".TAKED_OFF_QUERY." ,
	IFNULL((SELECT SUM(z.cost) FROM $compgps_dbt z, $compgpsres_dbt x WHERE z.id=x.point_id AND z.active='yes' AND x.start_number=c.start_number AND z.comp_id=$comp_id),0) AS total_cost,
	IFNULL((SELECT SUM(z.cost) FROM $compgps_dbt z, $compgpsres_dbt x WHERE z.id=x.point_id AND z.active='yes' AND x.start_number=c.start_number AND z.comp_id=$comp_id)+IFNULL((SELECT `points` FROM $compbonp_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0),0) AS final_cost,
	IFNULL((SELECT `points` FROM $compbonp_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0) AS bonus_cost,
	(SELECT SUM(cost) FROM $compgps_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id AND `active`='yes') AS all_cat_cost,
	(SELECT COUNT(DISTINCT(id)) FROM $compgps_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id AND `active`='yes') AS all_cat_sum,
	(SELECT COUNT(DISTINCT(z.id)) FROM $compgps_dbt z, $compgpsres_dbt x WHERE z.id=x.point_id AND z.active='yes' AND x.start_number=c.start_number AND z.comp_id=$comp_id) AS points_sum,
	(g.finish_time-g.start_time) AS total_time,
	IFNULL((SELECT `time` FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60 AS pinok_time,
	IFNULL((SELECT `time` FROM $compbont_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60 AS bonus_time,
	IFNULL((SELECT `points` FROM $compbonp_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0) AS bonus_points,
	(g.finish_time-g.start_time)+IFNULL((SELECT `time` FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60-IFNULL((SELECT `time` FROM $compbont_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60 AS final_time,
	(SELECT  COUNT(id)  FROM $compgps_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id AND `required`='yes' AND `active`='yes') AS required4cat_count,
	(SELECT COUNT(*) FROM $compgpsres_dbt WHERE comp_id=$comp_id AND start_number=c.start_number AND point_id IN (SELECT id FROM  $compgps_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id AND `required`='yes' AND `active`='yes')) AS required_points_sum,
	
	g.start_time, g.finish_time,
	a.id AS request_id, a.PilotName, a.PilotNik, a.NavigatorName, a.NavigatorNik, a.city, a.AutoBrand, a.AutoNumber, a.WheelSize

		FROM $compreq_dbt a, $compres_dbt c, $compgpstime_dbt g
	WHERE
	a.comp_id=$comp_id AND c.comp_id=$comp_id AND g.comp_id=$comp_id AND
	c.request_id=a.id AND a.category=$cat_id AND c.cat_id=$cat_id AND
	g.start_number=c.start_number

	$filters_sql
	ORDER BY
		taked_off ASC,
		IF (final_time<=$max_time,0,1) ASC,
		IF (required_points_sum=required4cat_count,0,1) ASC,
		final_time+(all_cat_cost-total_cost)*$points_mult*60-bonus_cost ASC, 
		final_time ASC, a.WheelSize ASC, a.RegisterDate ASC";
//die('<pre>'.$sql);
}

if($type=='legend'){
	$sql="SELECT c.start_number, c.comment, c.portal, c.winch,
		d.kps,
		(d.finish_time-d.start_time) AS total_time,
		".TAKED_OFF_QUERY." ,
		IFNULL((SELECT `time` FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60 AS pinok_time,
		IFNULL((SELECT `time` FROM $compbont_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60 AS bonus_time,
		(d.finish_time-d.start_time)+IFNULL((SELECT `time` FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60-IFNULL((SELECT `time` FROM $compbont_dbt WHERE comp_id=$comp_id AND start_number=c.start_number),0)*60  AS final_time,
		d.start_time, d.finish_time, 
		a.id AS request_id, a.PilotName, a.PilotNik, a.NavigatorName, a.NavigatorNik, a.city, a.AutoBrand, a.AutoNumber, a.WheelSize
		FROM $compreq_dbt a, $compres_dbt c, $complegres_dbt d 
	WHERE
	a.comp_id=$comp_id AND c.comp_id=$comp_id AND d.comp_id=$comp_id AND
	c.request_id=a.id AND a.category=$cat_id AND c.cat_id=$cat_id AND 
	d.start_number=c.start_number 
	$filters_sql
	ORDER BY
		taked_off ASC,
		IF (final_time<=$max_time,0,1) ASC,
		d.kps DESC,
		final_time ASC, a.WheelSize ASC, a.RegisterDate ASC";
}
$res=query_eval($sql);
$p=1;
$cnt=1;
$anyone_has_portal=false;
$anyone_has_winch=false;
$anyone_taked_required=false;
$need_tk=false;
if(_cat_var($comp_id,$cat_id,'need_tk'))
	$need_tk=true;
if(mysql_num_rows($res)) //всего стартовавших
	$num_started=sizeof(get_started_numbers($comp_id,$f_category));

while($row=mysql_fetch_assoc($res)){
	$req_id=$item_output[$p]['request_id']=(int)$row['request_id'];
	$item_output[$p]['place']=$p;
	$start_number=$item_output[$p]['start_number']=(int)$row['start_number'];
	if((int)$row['finish_time']){
		$item_output[$p]['total_time']=format_hms_time((int)$row['total_time'],$_null_sec_bool);
		$item_output[$p]['finish_time']=format_user_hms_time((int)$row['finish_time'],$_null_sec_bool);
		if($type=='gr-gps'){
			$final_time=(int)$row['final_time']+((int)$row['all_cat_cost']*GR_POINTS_MULT*60-(int)$row['total_cost']*GR_POINTS_MULT*60)-(int)$row['bonus_cost']*GR_POINTS_MULT*60;
			/* 
			 * GR_POINTS_MULT - множитель баллов, в минутах. Допустим, чтобы штраф за невзятие 1 точки стоимостью два балла был 2 часа надо задать GR_POINTS_MULT = 120 
			 */
			$item_output[$p]['final_time_hm']=format_hm_time($final_time); //чисто часы и минуты
			$item_output[$p]['final_time_hms']=format_hms_time($final_time,$_null_sec_bool); //часы:минуты:секунды
			$item_output[$p]['final_time']=format_big_time($final_time); //атавизм нах! ебаные сутки!
			$item_output[$p]['gps_untaken_cost']=(int)$row['all_cat_cost']-(int)$row['total_cost']; //это значение может получится отрицательным из-за того, что участник в базе "взял" точки другой категории. Такой глюк был на ЗЛ осень 2010, сейчас я его вроде исправил
			$item_output[$p]['gps_untaken_sum']=(int)$row['all_cat_sum']-(int)$row['points_sum'];;
		}else{
			$final_time=(int)$row['final_time'];
			$item_output[$p]['final_time_hm']=format_hm_time($final_time); 
			$item_output[$p]['final_time_hms']=format_hms_time($final_time,$_null_sec_bool);
			$item_output[$p]['final_time']=format_big_time($final_time);

		}	
	}
	else{
		$item_output[$p]['total_time']='-';
		$item_output[$p]['finish_time']='-';
		$item_output[$p]['final_time']='-';
	}	
	$item_output[$p]['start_time']=format_hms_time((int)$row['start_time'],$_null_sec_bool);

	$item_output[$p]=get_full_request_data($comp_id,$req_id,$item_output[$p]);
	if($_GET['print_results']){
		$print_where='full';
		if($_GET['official_names'])
			$print_where='official';
		$item_output[$p]['crew']=get_crew($comp_id,$req_id,$print_where,'print');
	}
	$item_output[$p]['edit_link']="online_requests_add.php?comp_id=$comp_id&item_id=".(int)$row['request_id'];
	//техкомиссия
	if($need_tk){
		$item_output[$p]['tk_is_passed']=tk_is_passed($comp_id,$start_number);
		if($item_output[$p]['tk_is_passed'])
			$item_output[$p]['tk_is_relative']=tk_relative($comp_id,$start_number);
	}
	$item_output[$p]['comment']=stripslashes($row['comment']);
	if(!$item_output[$p]['comment'])
		$item_output[$p]['comment']='&nbsp;';
	if($type=='gps' or $type=='gr-gps'){
		$item_output[$p]['gps_total_cost']=(int)$row['final_cost']; //сумма вместе с бонусами
		$item_output[$p]['gps_points_cost']=(int)$row['total_cost']; //сумма только по собранным точкам
		$item_output[$p]['gps_points_sum']=(int)$row['points_sum']; //количество собранных точек
		if($row['bonus_points'])
			$item_output[$p]['gps_bonus_points']=(int)$row['bonus_points'];
		else
			$item_output[$p]['gps_bonus_points']='-';

		$item_output[$p]['gps_required_points_sum']=(int)$row['required_points_sum']; //количество взятых обязательных точек
		if($item_output[$p]['gps_required_points_sum'])
			$anyone_taked_required=true;

	}
	elseif($type=='legend')
		$item_output[$p]['legend_kps']=(int)$row['kps'];

	if($row['pinok_time'])
		$item_output[$p]['pinok']=format_hm_time((int)$row['pinok_time']);
	else
		$item_output[$p]['pinok']='-';
	if($row['bonus_time'])
		$item_output[$p]['bonus_time']=format_hm_time((int)$row['bonus_time']);
	else
		$item_output[$p]['bonus_time']='-';
	$cnt++;

	/*
		* final_time - время с учетом всех невзятых точек и пинков
		* finish_time - время финиша
		* total_time (надо переделать - йобаная обратная совместимость) - время на трассе без учета штрафов за невзятые точки
	*/
	$time=(int)$row['final_time'];
	if($time>$max_time and (int)$row['finish_time']){
		$item_output[$p]['res']="ЛИМ.";
		$item_output[$p]['dontfix']=true; //dontfix значит что результат не идет в зачет на данном СУ, см ниже если непонятно
	}
	$time=(int)$row['finish_time'];
	if(!$time){
		$item_output[$p]['res']='СХОД';
		$item_output[$p]['dontfix']=true;
	}elseif($type=='gr-gps')
		$item_output[$p]['details_link']=append_rnd("print/gps-details.php?start_number=$start_number&comp_id=$comp_id");
	elseif($type=='legend')
		$item_output[$p]['details_link']=append_rnd("print/legend-details.php?start_number=$start_number&comp_id=$comp_id");

	if(!$item_output[$p]['dontfix']) //если идет в зачет, то начисляем рафовские очки
		$item_output[$p]['raf_score']=raf_score($item_output[$p]['place'],$num_started);
	else
		$item_output[$p]['raf_score']='н\з';
	//проверка на снятие
	if($row['taked_off']=='1'){
		$item_output[$p]['res']='СНЯТ';
		$item_output[$p]['dontfix']=true;
		//узнаем причину дисквалификации
		list(,$item_output[$p]['disq_comment'])=disq_type($comp_id,$start_number);
	}
	//портальные мосты
	if(defined('CA_TRACK_PORTAL') and CA_TRACK_PORTAL){
		$item_output[$p]['have_portal']=false;
		if($row['portal']=='yes'){
			$item_output[$p]['have_portal']=true;
			$anyone_has_portal=true;
		}
	}
	//лебедка
	if(defined('CA_TRACK_WINCH') and CA_TRACK_WINCH and in_array($f_category,$winch_cat)){
		$item_output[$p]['have_winch']=false;
		if($row['winch']=='yes'){
			$item_output[$p]['have_winch']=true;
			$anyone_has_winch=true;
		}
	}
	
		


	$p++;
}

}



$active_categories=get_started_categories($comp_id);

$tpl_print_results_link=append_rnd("results.php?comp_id=$comp_id&print_results=1&f_category=$f_category&f_finished=$f_finished&f_result=$f_result");
$tpl_fix_results_link=append_rnd("results.php?comp_id=$comp_id&fix_results=1&f_category=$f_category&f_finished=$f_finished&f_result=$f_result");
if(defined('CA_PDF_RESULTS_COMP_ENABLED') and CA_PDF_RESULTS_COMP_ENABLED)
	$tpl_pdf_link=append_rnd("results.php?comp_id=$comp_id&pdf=1&f_category=$f_category&f_finished=$f_finished&f_result=$f_result");
if(defined('CA_PDF_POINTS_LIST_ENABLED') and CA_PDF_POINTS_LIST_ENABLED)
	$tpl_points_list_link=append_rnd("print/cat_taken_points.php?comp_id=$comp_id&cat_id=$f_category");


if(can_export_xls()){
	$tpl_export_xls_link=append_rnd("results.php?comp_id=$comp_id&export_results=xls&f_category=$f_category&f_finished=$f_finished&f_result=$f_result");
}

if($type=='legend')
	$tpl_print_results_link.="&f_kp=$f_kp";

if($_GET['json']){
	print json_encode($json_output);
	exit;
}
	
if($_GET['export_results'] and $_GET['export_results']=='xls'){

	export_results_xls($item_output,$f_category);
	exit;
}
if($_GET['fix_results']){
	foreach($item_output as $place=>$value){
		$data=array(
			'comp_id'=>$comp_id,
			'start_number'=>$value['start_number'],
		);
		if(!$value['dontfix']){
			$data['place']=$place;
			$data['active']='yes';
		}else{
			$data['place']=0;
			$data['active']='no';
		}
		$res=query_eval("REPLACE INTO $compfixedres_dbt SET comp_id='{$data['comp_id']}', start_number='{$data['start_number']}', place='{$data['place']}', active='{$data['active']}';");
	}
	header("Location: results.php?comp_id=$comp_id&results_fixed=1&f_category=$f_category&f_finished=$f_finished&f_result=$f_result");
	exit;
}

$tpl_need_tk=$need_tk;
if($_GET['print_results']){	
	$results_title="Результаты\n{$cat_name[$f_category]}";
	if($_GET['prelim'])
		$results_title="Предварительные результаты\n{$cat_name[$f_category]}";

	if($_GET['print_title'])
		$results_title=$_GET['print_title'];
	$page_title=$title=$results_title;
	//TODO сделать отдельные шаблоны для разных видов соревнований, а так же в зависимости от template_path
	include('_includes/nocache.php');
	require('print/header.php');
	require('_templates/print/results.phtml');	
	exit;
}	

if($_GET['pdf']){
	require_once('pdf/results_comp.php');
	print_pdf_results_comp($item_output,$cat_name[$f_category]);
	exit;
}
$title='Просмотр результатов';
$tpl_onload_function="results_onload()";
require('admin_header.php');
require('_templates/results.phtml');
