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
require_once('_includes/disq.php');

require_once('_includes/time_functions.php');
require_once('_includes/init_category.php');


$comp_id=(int)_input_val('comp_id');
$cat_id=(int)_input_val('cat_id');
$interval=(int)_input_val('interval');
$sort_type=_input_val('sort_type');
list($h,$m,$s)=parse_user_time(_input_val('time_begin'));

$time=$h*3600+$m*60+$s;

if(!$comp_id)
	die('Не указан id соревнования');
if(!$cat_id)
	die('Не указан id категории');
if(!$time)
	die('Не указано время начала');

if(!$sort_type or !$sl_sort_types[$sort_type])
	die('Указан некорректный метод сортировки');

if(get_started_numbers($comp_id,$cat_id))
	die("Стартовая ведомость для категории уже есть");

$type=get_type_by_cat_id($comp_id,$cat_id);

if($type=='legend' and !$interval)
	die("Не задан интервал!");

//строим критерии сортировки
$o=array(
	'start_number'=>'a.start_number ASC',
	'start_number_rev'=>'a.start_number DESC',
	'request_date'=>'b.RegisterDate ASC',
	'register_date'=>'a.id ASC',
	'wheel_size'=>'b.WheelSize ASC',
	'wheel_size_rev'=>'b.WheelSize DESC',
	'request_date,wheel_size'=>'b.RegisterDate ASC, b.WheelSize ASC',
);

if(!strlen($o[$sort_type]))
	die("Внутренняя ошибка - невозможно определить тип сортировки, обратитесь к разработчику");

init_category($comp_id,$cat_id); //инициализация таблиц результатов для категории

$start_numbers=array();

$res=query_eval("SELECT a.start_number FROM $compres_dbt a, $compreq_dbt b WHERE a.cat_id=$cat_id AND a.request_id=b.id AND a.comp_id=$comp_id AND b.comp_id=$comp_id ORDER BY {$o[$sort_type]}");

while($row=mysql_fetch_row($res))
	$start_numbers[]=(int)$row[0];


foreach($start_numbers as $start_number){
	update_start_time($comp_id,$time,$start_number);
	if($type=='legend')
		$time+=$interval*60;
}
header("Location: ".append_rnd("start_list.php?comp_id=$comp_id&start_list_generated=$cat_id&last_sort_type=$sort_type"));

