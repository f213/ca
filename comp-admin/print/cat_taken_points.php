<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
chdir('../');
require_once('../_includes/core.php');
require_once('_includes/auth.php');

require_once('_includes/started_functions.php');
require_once('pdf/points_list.php');

$comp_id=(int)$_GET['comp_id'];
$cat_id=(int)$_GET['cat_id'];

if(!$comp_id)
	die('некорректно указан id соревнования!');
if(!$cat_id or !$cat_name[$cat_id])
	die('некорректно указан id категории!');

$item_output['points']=array();

$res=query_eval("SELECT id,name,comment FROM $compgps_dbt WHERE `comp_id`=$comp_id AND `cat_id`=$cat_id AND `active`='yes' ORDER BY name ASC;");
while($row=mysql_fetch_row($res))
	if(_strlen($row[2]))
		$item_output['points'][(int)$row[0]]=stripslashes($row[2]);
	else
		$item_output['points'][(int)$row[0]]=stripslashes($row[1]);
foreach(get_started_numbers($comp_id,$cat_id) as $start_number){

	$item_output['data'][$start_number]=array();
	foreach($item_output['points'] as $point_id=>$point_name){
		$res=query_eval("SELECT point_id FROM $compgpsres_dbt WHERE comp_id=$comp_id AND start_number=$start_number AND point_id=$point_id;");
		if(mysql_num_rows($res))
			$item_output['data'][$start_number][$point_id]='+';
		else
			$item_output['data'][$start_number][$point_id]='-';
	}

}
foreach($item_output['points'] as $key=>$point_name) //сокращаем названия длинных точек до первой буквы
	if(!preg_match('/^\d+$/',$point_name))
		$item_output['points'][$key]=_substr($point_name,0,2).'.';

print_pdf_points_list($item_output,$cat_name[$cat_id]);
