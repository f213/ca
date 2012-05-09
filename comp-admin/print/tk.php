<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
chdir('../');
require_once('../_includes/core.php');
require_once('_includes/request_functions.php');
require_once('_includes/started_functions.php');
require_once('_includes/auth.php');
require_once('_includes/export_results.php');

$comp_id=(int)$_GET['comp_id'];
if(!$comp_id)
	die('некорректно указан id соревнования!');
$cat_id=(int)$_GET['cat_id'];
if(!$cat_id or !$cat_name[$cat_id] or $cat_id<0 or $cat_id>_CATEGORIES)
	die('некорректно указан категоря!');


$res=query_eval("SELECT a.PilotName, a.PilotNik, a.NavigatorName, a.NavigatorNik, a.AutoBrand, a.AutoNumber, a.WheelSize,a.category AS cat_id, b.start_number AS start_number, c.date AS date, b.portal, b.winch FROM  $compres_dbt b, $compreq_dbt a, $comptk_dbt c WHERE a.comp_id=$comp_id AND b.comp_id=$comp_id AND c.comp_id=$comp_id AND b.request_id = a.id AND b.start_number=c.start_number AND b.cat_id=$cat_id ORDER BY start_number ASC;");
$c=1;
$anyone_has_portal=$anyone_has_winch=false;
while($row=mysql_fetch_assoc($res)){
	$start_number=(int)$row['start_number'];
	$item_output[$c]['start_number']=$start_number=(int)$row['start_number'];
	$item_output[$c]=get_brief_request_data($comp_id,num2req($comp_id,$start_number),$item_output[$c]);
	$item_output[$c]['crew']=get_crew($comp_id,num2req($comp_id,$start_number),'official','print');
	$item_output[$c]['pen_time']='&nbsp;-&nbsp;';
	$item_output[$c]['pen_reason']='&nbsp;-&nbsp;';

	$res1=query_eval("SELECT * FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number=$start_number;");
	if(mysql_num_rows($res1)){
		$row1=mysql_fetch_assoc($res1);
		$item_output[$c]['pen_time']=gmdate('H:i',(int)$row1['time']*60);
		$item_output[$c]['pen_reason']=stripslashes($row1['reason']);
	}
	
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
if($_GET['xls']){
	$my_cat_name=_export_results_translit($cat_name[$cat_id]);
	error_reporting(0); //как я блядь ненавижу пехапе и все что с ним связано. Разработчики всей этой херни должны гореть в аду вися на собственных кровавых кишках, глядя как черти ебут в уши всех их родственников.
	require_once 'Spreadsheet/Excel/Writer.php';
	$xls=new Spreadsheet_Excel_Writer();
	$xls->setVersion(8); //без этого русский не работает

	$bold=$xls->addFormat(); //формат для заголовков
	$bold->setBold();
	
	$main_format=$xls->addFormat(); //дефолтный формат

	$place_format=$xls->addFormat();
	$place_format->setHAlign('right');
	$place_format->setBold();


	$xls->send('TechCom_'.date('Ymd_Hi_').$my_cat_name.'.xls');
	$sheet=$xls->addWorksheet($my_cat_name);
	$sheet->setInputEncoding('UTF-8');
	$sheet->setFirstSheet();
	$header=array(
		'№',
		'Борт',
		'1-й водитель',
		'2-й водитель',
		'Машина',
		'Госномер',
		'Пенал.',
		'Причина',
		'Комментарий',
	);

	
	$sheet->writeRow(0,0,$header,$bold);
	$rowcnt=1;
	$cnt=0;
	foreach($item_output as $key=>$value){
		$data=array();
		$data[0]=++$cnt; //номер п\п
		$data[1]=$value['start_number']; //борт
		$data[2]=$value['pilot_name']; //пилот
		$data[3]=$value['navigator_name']; //штурман
		$data[4]=$value['auto_brand']; //машина
		$data[5]=$value['auto_number']; //номер
		$data[6]=str_replace('&nbsp;','',$value['pen_time']); //время пинка
		$data[7]=str_replace('&nbsp;','',$value['pen_reason']); //причина пинка
		
		$com_str=''; //комментарий
		if($value['have_portal'])
			$com_str.="порталы,";
		if($value['have_winch'] and !preg_match('/леб[её]дк?а/iu',$value['pen_reason']))
			$com_str.="лебедка,";
		$data[8]=trim($com_str,',');
		$sheet->writeRow($rowcnt++,0,$data);
	}
	$sheet->setColumn(0,0,5,$place_format);
	$sheet->setColumn(1,1,5);
	$sheet->setColumn(2,3,35);
	$sheet->setColumn(4,4,12);
	$sheet->setColumn(5,5,15,$place_format);
	$sheet->setColumn(7,7,30);
	$sheet->setColumn(8,8,20);
	$xls->close();
	exit;
}
$title="Протокол техкомиссии";
$page_title=$title."\n"._strtoupper($cat_name[$cat_id]);
require('print/header.php');
require('_templates/print/tk.phtml');
