<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require_once('../_includes/core.php');
require_once('_includes/auth.php');

require_once('_includes/started_functions.php');
require_once('_includes/request_functions.php');

if(defined(RESULTS_KPS_PER_ROW) and RESULTS_KPS_PER_ROW>0)
	$kps_per_row=RESULTS_KPS_PER_ROW;
else
	$kps_per_row=8;

if(empty($_GET['comp_id']) and empty($_POST['comp_id']))
	die('Не указан id соревнования!');

if($_POST['comp_id'])
	$comp_id=(int)$_POST['comp_id'];
else
	$comp_id=(int)$_GET['comp_id'];


$res=query_eval("SELECT cat_id FROM $compcatvar_dbt WHERE comp_id=$comp_id AND (type='gps' OR type='gr-gps') ;");
if(!mysql_num_rows($res)){
	require('admin_header.php');
	die('для заданного соревнования не задано ни одной категории учатсников с GPS!');
}	
$start_number=(int)$_GET['start_number'];

//получаем список бортовых номеров участников, которые стартовали по GPS
$res=query_eval("SELECT start_number FROM $compgpstime_dbt WHERE comp_id=$comp_id;");
$valid_numbers=array();
$valid_numbers_str="";
while($row=mysql_fetch_row($res)){
	$valid_numbers[]=(int)$row[0];
	$valid_numbers_str.="'{$row[0]}',";
}
$valid_numbers_str=trim($valid_numbers_str,',');
if($start_number and !in_array($start_number,$valid_numbers))
	die('указан несуществующий или нестартовавший бортовой номер!');
if($start_number){
	//получаем список возможнных GPS точек для заданной категории
	$cat_id=get_cat($comp_id,$start_number);
	$res=query_eval("SELECT id,name FROM $compgps_dbt WHERE `comp_id`=$comp_id AND `cat_id`=$cat_id AND `active`='yes';");

	if(!mysql_num_rows($res))
		die("Для категории заданного номера ($cat_id) не задано ни одной GPS-точки");
	$valid_names=$valid_ids=array();
	$valid_names_str="";
	while($row=mysql_fetch_row($res)){
		$valid_ids[]=(int)$row[0];
		$valid_names[]=(int)$row[1];
		$valid_names_str.="'{$row[1]}',";
	}
	$valid_names_str=trim($valid_names_str,',');
	//ну и наконец получаем список уже взятых участником точек
	//а здесь еще можно и баллы посчитать
	$res=query_eval("SELECT a.id, a.name,a.cost FROM $compgpsres_dbt b, $compgps_dbt a WHERE b.comp_id=$comp_id AND a.comp_id=$comp_id AND b.start_number=$start_number AND  a.active='yes' AND a.id=b.point_id;");
	$taken_names=$taken_ids=array();
	$taken_names_str="";
	$all_cost=0;
	while($row=mysql_fetch_assoc($res)){
		$taken_ids[]=(int)$row['id'];
		$taken_names[]=(int)$row['name'];
		$taken_names_str.="'{$row['name']}',";
		$all_cost+=(int)$row['cost'];
	}
	$item['gps_taken_ids']=$taken_ids;
	$item['gps_all_cost']=$all_cost;
	$taken_names_str=trim($taken_names_str,',');	
}

$flag=(int)$_GET['flag'];
if($flag and !$start_number)
	die('указан номер действия, но не указан стартовый номер!');

switch($flag){
case 1: //указание времени финиша
	list($h,$m,$s)=parse_user_time($_GET['finish_time']);
	$finish_time=$h*3600+$m*60+$s;
	update_finish_time($comp_id,$finish_time,$start_number);
	header("Location: ".append_rnd("gps-results.php?comp_id=$comp_id&start_number=$start_number"));
	die();
	break;
	
case 2: //добавление одной точки
	$num=(int)$_GET['num'];
	if(!in_array($num,$valid_names))
		die('указана несущствующаяя точка!');
	if(in_array($num,$taken_names))
		die('заданная точка уже взята!');
	//нужно определить категорию участника, для правильного поиска id точки по ее номеру. Потому что если встретятся точки с одинаковыми названиями, но в разных категориях, то может зачислится первая попавшаяся. 
	$cat_id=get_cat($comp_id,$start_number);

	$res=query_eval("SELECT id FROM $compgps_dbt WHERE `comp_id`=$comp_id AND `cat_id`=$cat_id AND `name`=$num;");
	if(!mysql_num_rows($res))
		die('указана несущствующаяя точка (SQL)');
	$row=mysql_fetch_row($res);
	$id=(int)$row[0];
	add_item($compgpsres_dbt,array(
		'comp_id'=>$comp_id,
		'start_number'=>$start_number,
		'point_id'=>$id,
		'author'=>$admin_user,
	));
	header("Location: ".append_rnd("gps-results.php?comp_id=$comp_id&start_number=$start_number"));
	die();
	break;
case 3: //клик на точку из таблицы
	$item_id=(int)$_GET['item_id'];
	if(!$item_id)
		die('не указан id точки!');
	if(!in_array($item_id,$valid_ids))
		die('указан id несуществующей точки!');
	$taken=false;
	if($_GET['taken']=='1')
		$taken=true;
	if($taken and in_array($item_id,$taken_ids))
		die('Точка уже взята!');
	if($taken)
		add_item($compgpsres_dbt,array(
		'comp_id'=>$comp_id,
		'start_number'=>$start_number,
		'point_id'=>$item_id,
		'author'=>$admin_user,
	));
	else
		query_eval("DELETE FROM $compgpsres_dbt WHERE comp_id=$comp_id AND start_number=$start_number AND point_id=$item_id LIMIT 1;");
	header("Location: ".append_rnd("gps-results.php?comp_id=$comp_id&start_number=$start_number"));
	die();
	break;
case 4: //изменение времени старта
	list($h,$m,$s)=parse_user_time($_GET['start_time']);
	$time=$h*3600+$m*60+$s;
	update_start_time($comp_id,$time,$start_number);
	header("Location: ".append_rnd("gps-results.php?comp_id=$comp_id&start_number=$start_number"));
	die();
	break;
}	
if($start_number){
	$res=query_eval("SELECT * FROM $compres_dbt WHERE `start_number`=$start_number AND `comp_id`=$comp_id;");
	if(!mysql_num_rows($res))
		die('заданный номер отсутсвует в таблице результатов. Возможно это ошибка при создании стартовой ведомости.');
	$row=mysql_fetch_assoc($res);
	$item['cat_id']=(int)$row['cat_id'];
	$item['cat_name']=$cat_name[$item['cat_id']];
	$item['start_time']=format_hms_time(get_start_time($comp_id,$start_number),$_null_sec_bool);
	$request_id=(int)$row['request_id'];
	$item=get_brief_request_data($comp_id,$request_id,$item);
	if(defined('CA_TRACK_PORTAL') and CA_TRACK_PORTAL)
		$item['have_portal']=has_portal($comp_id,$start_number);
	$tpl_have_finish=false;
	$finish_time=get_finish_time($comp_id,$start_number);

	if($finish_time and $cat_id){
		$item['finish_time']=format_user_hms_time($finish_time,false);
		$tpl_have_finish=true;
		//получаем максимальный номер точки
		$res=query_eval("SELECT MAX(name) FROM $compgps_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id AND active='yes';");
		if(!mysql_num_rows($res))//выходит что у нас ни одной точки на категории не забито
			die("не забито ни одной точки в категории!($cat_id)(have_finish)");
		$row=mysql_fetch_row($res);
		$max_num=(int)$row[0];
	
		$res=query_eval("SELECT id, cost,name,comment,required FROM $compgps_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id AND active='yes' ORDER BY name ASC;");
		$x=0;
		$y=0;
		while($row=mysql_fetch_assoc($res)){
			$id=(int)$row['id'];
			$kp_output[$y][$id]['cost']=(int)$row['cost'];
			$kp_output[$y][$id]['name']=stripslashes($row['name']);
			$kp_output[$y][$id]['comment']=stripslashes($row['comment']);
			if($row['required']=='yes')
				$kp_output[$y][$id]['required']=true;
			else
				$kp_output[$y][$id]['required']=false;

			if(strlen($kp_output[$y][$id]['name'])==1) //добавляем нолик ко всем точкам, дабы ровно было
				$kp_output[$y][$id]['name']="0".$kp_output[$y][$id]['name'];
			if((strlen($kp_output[$y][$id]['name'])==2) and $max_num>=100) //ежели у нас точек больше ста, тогда добавляем еще один нолик	
				$kp_output[$y][$id]['name']="0".$kp_output[$y][$id]['name'];
			
			if(in_array($id,$taken_ids))
				$kp_output[$y][$id]['taken']=true;
			else
				$kp_output[$y][$id]['taken']=false;
			if($x==$kps_per_row-1){
				$x=0;
				$y++;
				continue;
			}
			$x++;	
		}	
	}	
}

if(!$start_number)
	$start_number=''; //чтобы ноль юзера не пугал

$change_start_form_url=$kp_form_url='gps-results.php';
$title="Ввод результатов GPS-ориентирования";
require('admin_header.php');
require('_templates/gps-results.phtml');
