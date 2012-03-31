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
require_once('_includes/disq.php');

$comp_id=_input_val('comp_id');
if(!$comp_id)
	die('Не указан ID Соревнования!');

$comps=array();
$res=query_eval("SELECT * FROM $comp_dbt WHERE 1 ORDER BY ID DESC;");
while($row=mysql_fetch_assoc($res)){
	$id=(int)$row['ID'];
	$comp[$id]['current']=false;
	if($row['current']=='yes')
		$comp[$id]['current']=true;
	$comp[$id]['name']=stripslashes($row['Name']);
}
//получаем список всех возможных категорий

$res=query_eval("SELECT DISTINCT(cat_id) FROM $compres_dbt ORDER BY cat_id ASC;");
while($row=mysql_fetch_row($res))
	$cat[(int)$row[0]]['name']=$cat_name[(int)$row[0]];

	
if($_GET['f_category'])
	$f_category=(int)$_GET['f_category'];
if($f_category and in_array($f_category,$cat)){
	//тута мы получаем список всех участников, стартовавших в данной категории во всех соревнованиях
	$started_numbers=array();
	foreach($comp as $current_comp=>$value)
		foreach(get_started_numbers($current_comp,$f_category) as $sn)
			if(!in_array($sn,$started_numbers))
				$started_numbers[]=$sn;

	foreach($comp as $current_comp=>$value)
		foreach($started_numbers as $num){
			$res=query_eval("SELECT `place` FROM $compfixedres_dbt WHERE comp_id=$current_comp AND start_number='$num';");
			if(mysql_num_rows($res)){
				$row=mysql_fetch_row($res);
				$place=(int)$row[0];
				$item_output[$num][$current_comp]=$place;
			}else{
				$item_output[$num][$current_comp]='-';
			}
		}
}
$title="Просмотр результатов гонки";
require('admin_header.php');
require('_templates/results_multi.phtml');
