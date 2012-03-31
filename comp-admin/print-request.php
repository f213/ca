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
require_once('_includes/online_requests.functions.php');

require('_includes/nocache.php');

$comp_id=(int)$_GET['comp_id'];
$request_id=(int)$_GET['request_id'];

if(!$comp_id)
	die('не указан id соревнования');
if(!$request_id)
	die('не указан id заявки');

if(defined('ADM_TRACK_EDITS') and ADM_TRACK_EDITS) //печать снимает отметку о редактировании
	cancel_tracked_edit($request_id);

$res=query_eval("SELECT name FROM $comp_dbt WHERE id=$comp_id;");
if(!mysql_num_rows($res))
	die('ошибка получения названия соревнования!');
$row=mysql_fetch_row($res);
$comp_title=stripslashes($row[0]);
$res=query_eval("SELECT * FROM $compreq_dbt WHERE comp_id=$comp_id AND id=$request_id;");
if(!mysql_num_rows($res))
	die("Указанный request_id не найден в соревнования ($comp_id)");
$row=mysql_fetch_assoc($res);
$output['pilot_name']=stripslashes($row['PilotName']);
if($row['PilotNik'])
	$output['pilot_name'].=' ('.stripslashes($row['PilotNik']).')';
$output['navigator_name']=stripslashes($row['NavigatorName']);
if($row['NavigatorNik'])
	$output['navigator_name'].=' ('.stripslashes($row['NavigatorNik']).')';
$output['phone']=stripslashes($row['phone']);
$output['email']=stripslashes($row['email']);
$output['auto_brand']=stripslashes($row['AutoBrand']);
$output['auto_number']=stripslashes($row['AutoNumber']);
$output['wheel_size']=stripslashes($row['WheelSize']);
$output['class']=$cat_name[(int)$row['category']];


$res=query_eval("SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND request_id=$request_id;"); //получаем бортовой номер если есть.
if(mysql_num_rows($res)){
	$row=mysql_fetch_row($res);
	$output['start_number']=(int)$row[0];
}
	
require("_templates/$tpl_dir/print-request.phtml");
