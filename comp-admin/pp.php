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


if(empty($_GET['comp_id']))
	die('Не указан id соревнования!');

$comp_id=(int)$_GET['comp_id'];

$cat=array();
$cat=get_started_categories($comp_id); //получаем список стартовавших категорий

$filters_str="comp_id=$comp_id";
if($_GET['f_category']){
	$cat_id=$f_category=(int)$_GET['f_category'];
	if(!$cat[$f_category]['type'])
		die("Задан неправильный номер категории ($f_category)");
	$filters_str.="&f_category=$f_category";
}	

if($cat_id){
	$valid_numbers=get_started_numbers($comp_id,$cat_id);
	$valid_numbers_str=get_valid_numbers_str($valid_numbers);
}

//flags
if($_GET['flag'])
	$flag=(int)$_GET['flag'];

if($flag)
	switch($flag){
	case 1: //удаление
		$start_number=(int)$_GET['start_number'];
		if(!in_array($start_number,$valid_numbers))
			die("Указан неверный бортовой номер!");
		query_eval("DELETE FROM $comppp_dbt WHERE comp_id=$comp_id AND start_number=$start_number LIMIT 1;");
		header("Location: ".append_rnd("pp.php?$filters_str&del=true"));
	break;
	}

if($cat_id){

	$res=query_eval("SELECT * FROM $comppp_dbt WHERE comp_id=$comp_id AND start_number IN ($valid_numbers_str) ORDER BY `when` ASC;");
	while($row=mysql_fetch_assoc($res)){
		$start_number=(int)$row['start_number'];
		$result=false;
		if($row['result']=='yes')
			$result=true;
		$item_output[$start_number]['result']=$result;
		$item_output[$start_number]['wait_time']=format_hm_time((int)$row['wait_time']*60);
		if($result)
			$item_output[$start_number]['pass_time']=format_hm_time((int)$row['pass_time']*60);
		$item_output[$start_number]=get_brief_request_data($comp_id,num2req($comp_id,$start_number),$item_output[$start_number]);
		$item_output[$start_number]['del_link']=append_rnd("pp.php?$filters_str&start_number=$start_number&flag=1");
	}
}
$title="Полоса препятствий";
require('admin_header.php');
require('_templates/pp.phtml');
