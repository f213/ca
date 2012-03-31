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
require_once('_includes/penalize_functions.php');
require_once('_includes/change_cat_id.php');

if(empty($_GET['comp_id']) and empty($_POST['comp_id']))
	die('Не указан id соревнования!');

if($_POST['comp_id'])
	$comp_id=(int)$_POST['comp_id'];
else
	$comp_id=(int)$_GET['comp_id'];

$start_number=(int)$_GET['start_number'];
if($_POST['start_number'])
	$start_number=(int)$_POST['start_number'];

$flag=(int)$_GET['flag'];
if($_POST['flag'])
	$flag=(int)$_POST['flag'];

//получаем список валидных номеров участнегов
$res=query_eval("SELECT DISTINCT(start_number) FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id IN (SELECT cat_id FROM $compcatvar_dbt WHERE comp_id=$comp_id AND need_tk='yes');");
if(!mysql_num_rows($res))
	die("нет ни одного зарегистрированного участнега!");
$valid_numbers=array();
$valid_numbers_str="";
while($row=mysql_fetch_row($res)){
	$valid_numbers[]=(int)$row[0];
	$valid_numbers_str.="'{$row[0]}',";
}
$valid_numbers_str=trim($valid_numbers_str,',');

if($start_number and !in_array($start_number,$valid_numbers))
	die('указан несуществующий или нестартовавший бортовой номер. Возможно так же для этой категории на данном этапе отключено проведение тех. комиссии');

if($flag and !$start_number)
	die("Некорректно указан стартовый номер!");

switch($flag){
case 1: //добавить пенализацию
	$min=(int)$_POST['min'];
	if(!$min)
		die("Некорректно указаны минуты");
	$reason=$_POST['reason'];
	if(strlen($reason)<3)
		die("Некорректно указана причина");
	add_pen($comp_id,$start_number,$min,$reason,'tk');
	if(defined('CA_WINCH_AUTODETECT') and CA_WINCH_AUTODETECT)
		if(preg_match('/леб[её]дк?а/i',$reason))
			query_eval("UPDATE $compres_dbt SET winch='yes' WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;");


	header("Location: ".append_rnd("tk.php?comp_id=$comp_id&start_number=$start_number&just_added_pen=true"));
	exit;
	break;
case 2: //пройти техкомиссию
	$relative='no';
	$relative_reason='';
	if($_GET['relative'] and $_GET['relative']==1){
		$relative='yes';
		if($_GET['relative_reason'] and strlen($_GET['relative_reason']))
			$relative_reason=addslashes(htmlspecialchars($_GET['relative_reason']));

	}
	query_eval("REPLACE INTO $comptk_dbt SET start_number='$start_number', comp_id='$comp_id', date=".time().", `relative`='$relative', `relative_reason`='$relative_reason', author='$admin_user';");
	header("Location: ".append_rnd("tk.php?comp_id=$comp_id&just_passed_tk=$start_number"));
	exit;
	break;
case 3: //отменить прохождение техкомиссии
	//clear_pen($comp_id,$start_number,'tk');
	query_eval("DELETE FROM $comptk_dbt WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;");
	header("Location: ".append_rnd("tk.php?comp_id=$comp_id&just_deleted_tk=true&start_number=$start_number"));
	exit;
	break;
	
case 4: //очистить пенализацию
	$pen_id=(int)$_GET['pen_id'];
	if(!$pen_id)
		die("не указан id пенализации");
	del_pen($comp_id,$pen_id);
	header("Location: ".append_rnd("tk.php?comp_id=$comp_id&start_number=$start_number&just_deleted_pen=true"));
	exit;
	break;
case 5: //изменение состояния порталов
	if($_POST['portal']=='yes')
		$portal='yes';
	else
		$portal='no';
	query_eval("UPDATE $compres_dbt SET portal='$portal' WHERE comp_id=$comp_id AND start_number='$start_number';");
	header("Location: ".append_rnd("tk.php?comp_id=$comp_id&start_number=$start_number"));
	exit;
	break;
case 6: //изменение состояния лебедки
	if($_POST['winch']=='yes')
		$winch='yes';
	else
		$winch='no';
	query_eval("UPDATE $compres_dbt SET winch='$winch' WHERE comp_id=$comp_id AND start_number='$start_number';");
	header("Location: ".append_rnd("tk.php?comp_id=$comp_id&start_number=$start_number"));
	exit;
	break;	
//7 когда нибудь будет пломбой
case 8: //изменение категории участника
	$cat_id=(int)$_POST['category'];
	if(!$cat_id)
		die('Не указана категория!');	
	if(array_key_exists($cat_id,get_started_categories($comp_id)))
		die("Возможно изменение категории только на ту, по которой еще нет стартовой ведомости");
	change_cat_id($comp_id,$start_number,$cat_id);
	header("Location: ".append_rnd("tk.php?comp_id=$comp_id&start_number=$start_number&just_changed_class"));
	
	exit;
	break;

}

if($start_number){

	$item=get_brief_request_data($comp_id,num2req($comp_id,$start_number));

	$have_pen=false;
	$res=query_eval("SELECT * FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;");
	if(mysql_num_rows($res)){
		$row=mysql_fetch_assoc($res);
		$item['pen']['min']=(int)$row['time'];
		$item['pen']['reason']=stripslashes($row['reason']);
		$item['pen']['author']=stripslashes($row['author']);
		$item['pen']['source']=$row['source'];
		$item['pen']['del_link']=append_rnd("tk.php?comp_id=$comp_id&start_number=$start_number&flag=4&pen_id=".(int)$row['id']);
		$have_pen=true;
	}
	$item['tk_is_passed']=tk_is_passed($comp_id,$start_number);
	if(!$item['tk_is_passed'])
		$item['pass_link']=append_rnd("tk.php?comp_id=$comp_id&start_number=$start_number&flag=2");
	else{
		$item['revoke_link']=append_rnd("tk.php?comp_id=$comp_id&start_number=$start_number&flag=3");
		$res=query_eval("SELECT * FROM $comptk_dbt WHERE comp_id=$comp_id AND start_number=$start_number;");
		$row=mysql_fetch_assoc($res);
		$item['tk_info']="Техкомиссия пройдена ".date('d.m.Y H:i',(int)$row['date'])." у <b>{$row['author']}</b>";
		if($row['relative']=='yes'){
			$rel_reason=stripslashes($row['relative_reason']);
			$item['tk_info'].="<br><b>УСЛОВНЫЙ ДОПУСК!</b>";
			if($rel_reason)
				$item['tk_info'].=" Причина: $rel_reason";
		}
	}
	//причины пенализации
	$pen_reasons=array();
	$cat_id=$item['cat_id'];
	if(!$cat_id)
		die("ошибка получения категории участника ($start_number)!");

	$res=query_eval("SELECT * FROM $comptkreasons_dbt WHERE cat_id=$cat_id ORDER BY min ASC");
	$c=0;
	while($row=mysql_fetch_assoc($res)){
		$pen_reasons[$c]['reason']=stripslashes($row['reason']);
		$pen_reasons[$c]['min']=(int)$row['min'];
		$c++;
	}
	if(defined('CA_TRACK_PORTAL') and CA_TRACK_PORTAL)
		$item['portal']=has_portal($comp_id,$start_number);
	if(defined('CA_TRACK_WINCH') and CA_TRACK_WINCH and in_array($cat_id,$winch_cat))
		$item['winch']=has_winch($comp_id,$start_number);
}
$tpl_categories_to_change=categories_to_change($comp_id,$start_number);
$cancel_link=append_rnd("tk.php?comp_id=$comp_id");
if(!$start_number)
	$start_number=''; //чтобы ноль юзера не пугал
$title="Техкомиссия";
require('admin_header.php');
require('_templates/tk.phtml');

