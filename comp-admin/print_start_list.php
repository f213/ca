<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require_once('../_includes/core.php');
require_once('_includes/request_functions.php');
require_once('_includes/started_functions.php');
require_once('_includes/export_results.php');
require_once('_includes/auth.php');


$flag=(int)$_GET['flag'];
if(!$flag)
	die('не указан тип действия!');
$comp_id=(int)$_GET['comp_id'];
if(!$comp_id)
	die('некорректно указан id соревнования!');
$cat_id=(int)$_GET['cat_id'];
if(!$cat_id or !$cat_name[$cat_id] or $cat_id<0 or $cat_id>_CATEGORIES)
	die('некорректно указан категоря!');

$type=_cat_var($comp_id,$cat_id,'type');
if(!$type)
	die('для заданной категории не указан тип соревнования!');
if($type=='legend'){ 
	$res=query_eval("SELECT a.id as request_id, a.PilotName, a.PilotNik, a.NavigatorName, a.NavigatorNik, a.AutoBrand, a.AutoNumber, a.WheelSize, b.start_number, c.start_time, b.portal, b.winch FROM  $compres_dbt b, $compreq_dbt a, $complegres_dbt c WHERE a.comp_id=$comp_id AND b.comp_id=$comp_id AND c.comp_id=$comp_id AND b.request_id = a.id AND a.category=$cat_id AND c.cat_id=$cat_id AND c.start_number=b.start_number ORDER BY c.start_time ASC;"); //на стенку повешу!
	$c=1;
	while($row=mysql_fetch_assoc($res)){
		$item_output[$c]['start_number']=(int)$row['start_number'];
		$item_output[$c]['start_time']=format_user_hms_time((int)$row['start_time'],$_null_sec_bool);
		$item_output[$c]['wheel_size']=(int)$row['WheelSize'];
		$item_output[$c]=get_brief_request_data($comp_id,(int)$row['request_id'],$item_output[$c]);
		$item_output[$c]['auto_number']=stripslashes($row['AutoNumber']);
		if(defined('CA_TRACK_PORTAL') and CA_TRACK_PORTAL){
			$item_output[$c]['have_portal']=false;
			if($row['portal']=='yes'){
				$item_output[$c]['have_portal']=true;
				$anyone_has_portal=true;
			}
		}
		if(defined('CA_TRACK_WINCH') and CA_TRACK_WINCH and in_array($cat_id,$winch_cat)){
			$item_output[$c]['have_winch']=false;
			if($row['winch']=='yes'){
				$item_output[$c]['have_winch']=true;
				$anyone_has_winch=true;
			}
		}
		$c++;
	}
	$item['cat_name']=strtoupper($cat_name[$cat_id]);
	
}	
if($type=='gps' or $type=='gr-gps'){

	$res=query_eval("SELECT a.start_number AS start_number, a.start_time AS start_time, b.portal, b.winch FROM $compgpstime_dbt a, $compres_dbt b WHERE a.comp_id=$comp_id AND b.comp_id=$comp_id AND a.start_number=b.start_number AND b.cat_id=$cat_id ORDER BY start_time ASC;");
	$c=1;
	while($row=mysql_fetch_assoc($res)){
		$start_number=(int)$row['start_number'];
		$item_output[$c]['start_number']=$start_number;
		$item_output[$c]['start_time']=format_hms_time($row['start_time'],$_null_sec_bool);
		$item_output[$c]['request_id']=$request_id=num2req($comp_id,$start_number);
		$item_output[$c]=get_brief_request_data($comp_id,$request_id,$item_output[$c]);
		if(defined('CA_TRACK_PORTAL') and CA_TRACK_PORTAL){
			$item_output[$c]['have_portal']=false;
			if($row['portal']=='yes'){
				$item_output[$c]['have_portal']=true;
				$anyone_has_portal=true;
			}
		}
		if(defined('CA_TRACK_WINCH') and CA_TRACK_WINCH and in_array($cat_id,$winch_cat)){
			$item_output[$c]['have_winch']=false;
			if($row['winch']=='yes'){
				$item_output[$c]['have_winch']=true;
				$anyone_has_winch=true;
			}
		}

		$c++;

	}
}	


if($_GET['xls']){
	$my_cat_name=_export_results_translit($cat_name[$cat_id]);
	error_reporting(0); 
	require_once 'Spreadsheet/Excel/Writer.php';
	$xls=new Spreadsheet_Excel_Writer();
	$xls->setVersion(8); //без этого русский не работает

	$bold=$xls->addFormat(); //формат для заголовков
	$bold->setBold();
	
	$main_format=$xls->addFormat(); //дефолтный формат

	$place_format=$xls->addFormat();
	$place_format->setHAlign('right');
	$place_format->setBold();
	
	$xls->send('StartList_'.date('Ymd_Hi_').$my_cat_name.'.xls');
	$sheet=$xls->addWorksheet($my_cat_name);
	$sheet->setInputEncoding('UTF-8');
	$sheet->setFirstSheet();
	$header=array(
		'№',
		'Борт',
		'Время',
		'1-й водитель',
		'2-й водитель',
		'Город',
		'Машина',
		'Госномер',
		'Колеса',
	);
	
	$sheet->writeRow(0,0,$header,$bold);
	$rowcnt=1;
	$cnt=0;
	foreach($item_output as $key=>$value){
		$data=array();
		$data[0]=++$cnt; //номер п\п
		$data[1]=$value['start_number']; //борт
		$data[2]=$value['start_time']; //время старта
		$data[3]=$value['pilot_name']; //пилот
		$data[4]=$value['navigator_name']; //штурман
		$data[5]=$value['city']; //город
		$data[6]=$value['auto_brand']; //машина
		$data[7]=$value['auto_number']; //номер
		$data[8]=$value['wheel_size']; //колеса

		$sheet->writeRow($rowcnt++,0,$data);
	}
	$sheet->setColumn(0,0,5,$place_format);
	$sheet->setColumn(1,1,5);
	$sheet->setColumn(2,2,6);
	$sheet->setColumn(3,4,45);
	$sheet->setColumn(5,5,10);
	$sheet->setColumn(6,6,15);
	$sheet->setColumn(7,7,10);
	$sheet->setColumn(8,8,5,$place_format);
	$xls->close();
	exit;
}

###
if((defined('CA_START_LIST_IS_MIXABLE') and CA_START_LIST_IS_MIXABLE) or $type=='legend')
	$title="Порядок старта участников";
else
	$title="Список зарегистрированных участников";

if($_GET['print_title'])
	$title=$_GET['print_title'];

$page_title="$title \n ".strtoupper($cat_name[$cat_id]);
require('_templates/print_header.phtml');
if($flag==1){ //краткая ведомость
	require('_templates/start_lists/brief_legend.phtml');
	die();
}	
if($flag==2){//РАФ
	require('_templates/start_lists/raf_legend.phtml');
	die();
}	
