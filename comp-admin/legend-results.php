<?
//full

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


$res=query_eval("SELECT cat_id FROM $compcatvar_dbt WHERE comp_id=$comp_id AND type='legend';");
if(!mysql_num_rows($res))
	die('для заданного соревнования не задано ни одной категории учатсников с Легендой!');
$start_number=(int)$_GET['start_number'];
//получаем список бортовых номеров участников, которые стартовали по легенде
$res=query_eval("SELECT start_number FROM $complegres_dbt WHERE comp_id=$comp_id;");
$valid_numbers=array();
$valid_numbers_str="";
while($row=mysql_fetch_row($res)){
	$valid_numbers[]=(int)$row[0];
	$valid_numbers_str.="'{$row[0]}',";
}
$valid_numbers_str=trim($valid_numbers_str,',');
if($start_number and !in_array($start_number,$valid_numbers))
	die('указан несуществующий или нестартовавший бортовой номер!');

if($start_number){ //cразу получаем категорию участника
	$cat_id=get_cat($comp_id,$start_number);
	//если указан номер, сразу узнаем максимальное кол-во КП в категории
	$max_kp=_cat_var($comp_id,$cat_id,'max_kp');
	if(!$max_kp)
		die("ошибка получения количетства КП для категории ($cat_id). Возможна ошибка в свойствах соревнования");
	if($detailed_legend_cat and in_array($cat_id,$detailed_legend_cat)){ //если работаем с легендой "подробно"
		//получаем список возможных в категории точек
		$res=query_eval("SELECT name,comment FROM $complegpoints_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id AND active='yes';");
		$valid_names=array();
		$valid_names_str="";
		while($row=mysql_fetch_row($res)){
			$valid_names[]=(int)$row[0];
			$valid_names_str.="'{$row[0]}',";
		}
		$valid_ids=$valid_names;
		$valid_names_str=trim($valid_names_str,',');
		
		$res=query_eval("SELECT point_name FROM $complegdetails_dbt WHERE comp_id=$comp_id AND start_number=$start_number;");
		$taken_names=$taken_ids=array();
		$taken_names_str="";
		while($row=mysql_fetch_row($res)){
			$cur_id=$taken_names[]=(int)$row[0];
			$taken_names_str.="'$cur_id',";
		}
		$taken_names_str=trim($taken_names_str,',');
		$taken_ids=$taken_names;
		$item['legend_kps']=sizeof($taken_names);
	}

			
}	


$flag=(int)$_GET['flag'];
if($flag and !$start_number)
	die('указан номер действия, но не указан стартовый номер!');
switch($flag){
case 1: //указание времени финиша
	list($h,$m,$s)=parse_user_time($_GET['finish_time']);
	$finish_time=$h*3600+$m*60+$s;
	update_finish_time($comp_id,$finish_time,$start_number);
	header("Location: ".append_rnd("legend-results.php?comp_id=$comp_id&start_number=$start_number"));
	die();
	break;
case 2: //указание кол-ва КП
	$kp=(int)$_GET['kp'];
	if(!$kp or $kp<0 or $kp>$max_kp)
		die('указано некорректное количество КП!');
	query_eval("UPDATE $complegres_dbt SET `kps`=$kp WHERE `comp_id`=$comp_id AND `start_number`=$start_number LIMIT 1;");
	header("Location: ".append_rnd("legend-results.php?comp_id=$comp_id&start_number=$start_number"));
	die();
	break;
case 3: //при подробной работе с легендой - сабмит формы с галочками
	$item_id=(int)$_GET['item_id'];
	if(!$item_id)
		die('не указано имя точки!');	
	if(!in_array($item_id,$valid_names))
		die('указан id несуществующей точки!');
	$taken=false;
	if($_GET['taken']=='1')
		$taken=true;
	if($taken and in_array($item_id,$taken_ids))
		die('Точка уже взята!');
	if($taken)
		add_item($complegdetails_dbt,array(
			'comp_id'=>$comp_id,
			'start_number'=>$start_number,
			'point_name'=>$item_id,
			'author'=>$admin_user,
		));
	else
		query_eval("DELETE FROM $complegdetails_dbt WHERE comp_id=$comp_id AND start_number=$start_number AND point_name=$item_id LIMIT 1;");
	recount_legend_results_from_detailed($comp_id,$start_number);
	header("Location: ".append_rnd("legend-results.php?comp_id=$comp_id&start_number=$start_number"));
	die();
	break;


case 4: //изменение времени старта
	list($h,$m,$s)=parse_user_time($_GET['start_time']);
	$time=$h*3600+$m*60+$s;
	update_start_time($comp_id,$time,$start_number);
	header("Location: ".append_rnd("legend-results.php?comp_id=$comp_id&start_number=$start_number"));
	die();
	break;	
case 5: //сабмит взятой точки текстом
	$num=(int)$_GET['num'];
	if(!in_array($num,$valid_names))
		die('указана несущствующаяя точка!');
	if(in_array($num,$taken_names))
		die('заданная точка уже взята!');
	//нужно определить категорию участника, для правильного поиска id точки по ее номеру. Потому что если встретятся точки с одинаковыми названиями, но в разных категориях, то может зачислится первая попавшаяся. 
	$cat_id=get_cat($comp_id,$start_number);

	$res=query_eval("SELECT name FROM $complegpoints_dbt WHERE `comp_id`=$comp_id AND `cat_id`=$cat_id AND `name`=$num;");
	if(!mysql_num_rows($res))
		die('указана несущствующаяя точка (SQL)');
	$row=mysql_fetch_row($res);
	$id=(int)$row[0];
	add_item($complegdetails_dbt,array(
		'comp_id'=>$comp_id,
		'start_number'=>$start_number,
		'point_name'=>$id,
		'author'=>$admin_user,
	));
	recount_legend_results_from_detailed($comp_id,$start_number);
	header("Location: ".append_rnd("legend-results.php?comp_id=$comp_id&start_number=$start_number"));
	die();
	break;
	
}


if($start_number){
	$res=query_eval("SELECT * FROM $compres_dbt WHERE `start_number`=$start_number AND `comp_id`=$comp_id;");
	if(!mysql_num_rows($res))
		die('заданный номер отсутсвует в таблице результатов. Возможно это ошибка при создании стартовой ведомости.');
	$row=mysql_fetch_assoc($res);
	$cat_id=$item['cat_id']=(int)$row['cat_id'];
	$item['cat_name']=$cat_name[$item['cat_id']];
	//list($item['start_time_h'],$item['start_time_m'])=explode(':',format_hm_time(get_start_time($comp_id,$start_number)*60));
	$item['start_time']=format_hms_time(get_start_time($comp_id,$start_number),$_null_sec_bool);
	$request_id=(int)$row['request_id'];
	$item=get_brief_request_data($comp_id,$request_id,$item);
	if(defined('CA_TRACK_PORTAL') and CA_TRACK_PORTAL)
		$item['have_portal']=has_portal($comp_id,$start_number);
	$tpl_have_finish=false;
	$finish_time=get_finish_time($comp_id,$start_number);
	if($finish_time){
		$item['finish_time']=format_user_hms_time($finish_time,false);
		$tpl_have_finish=true;
		$res=query_eval("SELECT kps FROM $complegres_dbt WHERE `comp_id`=$comp_id AND `start_number`=$start_number;");
		if(mysql_num_rows($res)){
			$row=mysql_fetch_row($res);
			if((int)$row[0])
				$item['legend_kps_results']=(int)$row[0];
		}
		$kp_detailed=false;
		if($detailed_legend_cat and in_array($cat_id,$detailed_legend_cat)){
			$kp_detailed=true;
			$res=query_eval("SELECT name,comment FROM $complegpoints_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id AND active='yes' ORDER BY name ASC;");
			$x=0;
			$y=0;
			while($row=mysql_fetch_assoc($res)){
				$id=(int)$row['name'];
				$kp_output[$y][$id]['cost']=(int)$row['cost'];
				$kp_output[$y][$id]['name']=stripslashes($row['name']);
				$kp_output[$y][$id]['comment']=stripslashes($row['comment']);
	
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
			$item['legend_kps']=sizeof($taken_ids);
		}else{
			$item['legend_kps']=$item['legend_kps_results'];
			
		}
	}
}
$tpl_max_kp=$max_kp;
if(!$tpl_max_kp)
	$tpl_max_kp=0;

if(!$start_number)
	$start_number=''; //чтобы ноль юзера не пугал
$title="Ввод результатов линейной гонки";

$change_start_form_url='legend-results.php';

require('admin_header.php');
require('_templates/legend-results.phtml');


function recount_legend_results_from_detailed($comp_id,$start_number){
	global $complegres_dbt,$complegdetails_dbt;
	if(!$comp_id or !$start_number)
		return false;
	$comp_id=(int)$comp_id;
	$start_number=(int)$start_number;

	$res=query_eval("SELECT COUNT(point_name) FROM $complegdetails_dbt WHERE comp_id=$comp_id AND start_number=$start_number;");
	if(!mysql_num_rows($res))
		die("Пересчет количества КП в результат: ошибка запроса");
	$row=mysql_fetch_row($res);

	$count=(int)$row[0];

	query_eval("UPDATE $complegres_dbt SET `kps`=$count WHERE `comp_id`=$comp_id AND `start_number`=$start_number LIMIT 1;");
	return true;

}

function tpl_check_max_kp($comp_id){
	global $complegres_dbt;
	global $cat_name;
	if(!$comp_id)
		return;
	//получаем список категорий, которые проверяем, берем все категории которые есть в complegres, следовательно есть и в стартовой ведомости
	$res=query_eval("SELECT DISTINCT(cat_id) FROM $complegres_dbt WHERE comp_id=$comp_id");
	$checking_cats=array();
	while($row=mysql_fetch_row($res))
		$checking_cats[]=(int)$row[0];

	$results='';
	foreach($checking_cats as $cat_id){
		if(!$cat_name[$cat_id])
			continue;
		if($cat_id>_CATEGORIES)
			continue;
		$max_kp=_cat_var($comp_id,$cat_id,'max_kp');
		if(!$max_kp or $max_kp<0)
			$results.=$cat_name[$cat_id].',';
	}
	$results=trim($results,',');
	if($results)
		return "<p class=achtung>Внимание! Возможно ошибка в свойствах соревнования - не задано максимальное количество КП для следующих категорий: $results</p>";
}
