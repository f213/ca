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
require_once('_includes/time_functions.php');

$title='Добавление\редактирование Соревнования';

if($_GET['item_id'])
	$item_id=(int)$_GET['item_id'];
if($_POST['flag'])
	$flag=(int)$_POST['flag'];
if($_GET['flag'])
	$flag=(int)$_GET['flag'];


switch($flag){
case 1:
	//добавление записей
	$comp_name=addslashes($_POST['comp_name']);
	if(strlen($comp_name)<3)
		die('Указано плохое имя соревнования!');

	$item_data=array(
		'Name'=>$comp_name,
	);
	$item_id=(int)$_POST['item_id'];
	$item_id=add_item($comp_dbt,$item_data,$item_id);
	for($i=1;$i<=_CATEGORIES;$i++){
		_cat_var($item_id,$i,'max_time',(int)$_POST['cat'.$i.'_time_h']*3600+(int)$_POST['cat'.$i.'_time_m']*60+(int)$_POST['cat'.$i.'_time_s']);
		$type='';
		if($_POST['cat'.$i.'_type'] and strlen($types_array[$_POST['cat'.$i.'_type']])){
			$type=addslashes($_POST['cat'.$i.'_type']);
			_cat_var($item_id,$i,'type',$type);
		}
		if($type=='legend')
			_cat_var($item_id,$i,'max_kp',(int)$_POST['cat'.$i.'_cp']);
		$need_tk=false;
		if($_POST['cat'.$i.'_need_tk'])
			$need_tk=true;
		_cat_var($item_id,$i,'need_tk',$need_tk);
		$is_official=false;
		if($_POST['cat'.$i.'_is_official'])
			$is_official=true;
		_cat_var($item_id,$i,'is_official',$is_official);
	}
	header("Location: competitions_add.php?item_id=$item_id");
	exit;
break;
}


//дефолтные значения
$item_output['LR_Y']=(int)date('Y');
if($item_id){
	$res=query_eval("SELECT * FROM $comp_dbt WHERE ID=$item_id");
	if(!mysql_num_rows($res))
		die('Указанное соревнование не найдено!');
	$item_output['name']=comp_name($item_id);

	for($i=1;$i<=_CATEGORIES;$i++){
		list($item_output['cat'.$i]['time_h'],
			$item_output['cat'.$i]['time_m'],$item_output['cat'.$i]['time_s'])=explode(':',format_hms_time((int)_cat_var($item_id,$i,'max_time'))); //пиздец.

		$item_output['cat'.$i]['type']=_cat_var($item_id,$i,'type');
		if($item_output['cat'.$i]['type']=='legend')
			$item_output['cat'.$i]['cp']=_cat_var($item_id,$i,'max_kp');
		$item_output['cat'.$i]['need_tk']=_cat_var($item_id,$i,'need_tk');
		$item_output['cat'.$i]['is_official']=_cat_var($item_id,$i,'is_official');
	}
}	
$form_submit_url='competitions_add.php';
$return_url='competitions.php';
require('admin_header.php');
require('_templates/competitions_add.phtml');

