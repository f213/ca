<?
//full
require_once('../_includes/core.php');
require_once('_includes/auth.php');

if(empty($_GET['comp_id']) and empty($_POST['comp_id']))
	die('Не указан id соревнования!');

if($_POST['comp_id'])
	$comp_id=(int)$_POST['comp_id'];
else
	$comp_id=(int)$_GET['comp_id'];

if(!sizeof($detailed_legend_cat)){
	require('admin_header.php');
	die("не заданно ни одного типа участника с подробной легендой");
}
$res=query_eval("SELECT cat_id FROM $compcatvar_dbt WHERE comp_id=$comp_id AND type='legend' ORDER BY cat_id ASC;");
if(!mysql_num_rows($res)){
	require('admin_header.php');
	die('для заданного соревнования не задано ни одной категории учатсников с Легендой!');
}
$can_work=false;
$my_categories=array();
while($row=mysql_fetch_row($res)){
	$tmp_cat_id=(int)$row[0];
	if(in_array($tmp_cat_id,$detailed_legend_cat)){
		$can_work=true;
		$my_categories[]=$tmp_cat_id;
	}
}
if(!$can_work){
	require('admin_header.php');
	die('для заданного соревнования ни одна из категорий по легенде не детализируется');
}


$flag=(int)$_GET['flag'];

if($flag){//тута обработка фильтров
	$filters_str="comp_id=$comp_id";
	if($_GET['f_category'])
		$filters_str.="&f_category={$_GET['f_category']}";
}	

$cat_id=(int)$_GET['f_category'];
if($flag){ //если задан тип действия - сразу проверяем, задана ли категория
	if(!$cat_id)
		die('не заданна категория!');
}

//ну и заодно получим список уже добавленных точек
$used_nums=array();
$used_nums_str="";
if($cat_id){
	$res=query_eval("SELECT name FROM $complegpoints_dbt WHERE `cat_id`=$cat_id;");
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
	for ($i=$begin;$i<=$end;$i++){
		if(in_array($i,$used_nums))
			continue;
		$add_data=array(
			'comp_id'=>$comp_id,
			'cat_id'=>$cat_id,
			'name'=>$i,
			'active'=>'yes',
		);
		add_item($complegpoints_dbt,$add_data);
	}
	header("Location: ".append_rnd("legend-kp.php?$filters_str&multiple_added=1"));
	die();
	break;
case 2: //добавление одной точки
	$num=addslashes($_GET['num']);
	if(!$num)
		die('не задан номер!');
	if(in_array($num,$used_nums))
		die('точка с таким номером уже есть!');
	$add_data=array(
		'comp_id'=>$comp_id,
		'cat_id'=>$cat_id,
		'name'=>$num,
		'active'=>'yes',
	);
	add_item($complegpoints_dbt,$add_data);
	header("Location: ".append_rnd("legend-kp.php?$filters_str&one_added=1"));
	die();
	break;
case 3: //массовое удаление точек для категории
	query_eval("DELETE FROM $complegpoints_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id;");
	header("Location: legend-kp.php?$filters_str");
	die();
	break;
case 5: //активность отдельной точки
	$num=(int)$_GET['name'];
	if(!$num){
		die('не указан id точки!');
	}
	if($_GET['active']=='1')
		$active='yes';
	else
		$active='no';
	query_eval("UPDATE $complegpoints_dbt SET `active`='$active' WHERE name=$num LIMIT 1;");
	header("Location: legend-kp.php?$filters_str");
	die();
	break;

case 6: //удаление точки
	$num=(int)$_GET['name'];
	if(!$num){
		die('не указан id точки!');
	}
	query_eval("DELETE FROM $complegpoints_dbt WHERE name=$num LIMIT 1;");
	header("Location: legend-kp.php?$filters_str");
	die();
	break;
case 7: //комментарий отдельной точки
	$num=(int)$_GET['name'];
	if(!$num){
		die('не указан id точки!');
	}
	$comment=addslashes($_GET['comment']);
	query_eval("UPDATE $complegpoints_dbt SET `comment`='$comment' WHERE name=$num LIMIT 1;");
	header("Location: legend-kp.php?$filters_str");
	die();
	break;
}	



$filters_sql="AND 1 ";
$filters_str="filters=1";
if($_GET['f_category'] and in_array((int)$_GET['f_category'],$my_categories)){
	$f_category=(int)$_GET['f_category'];
	$filters_str.="&f_category=$f_category";
	$filters_sql.=" AND cat_id=$f_category";
}

if($f_category){ //дальше работаем, тока если заданна категория
	$res=query_eval("SELECT * FROM $complegpoints_dbt WHERE comp_id=$comp_id $filters_sql ORDER BY `active` ASC, `name` ASC;");
	while($row=mysql_fetch_assoc($res)){
		$id=(int)$row['name'];
		$item_output[$id]['name']=$id;
		$item_output[$id]['comment']=stripslashes($row['comment']);
		if($row['active']=='yes')
			$item_output[$id]['active']=true;
		else
			$item_output[$id]['active']=false;
		$item_output[$id]['delete_link']="legend-kp.php?comp_id=$comp_id&f_category=$f_category&flag=6&name=$id";
	}
}	
$title="Управление списком точек легенды";
require('admin_header.php');
require('_templates/legend-kp.phtml');
