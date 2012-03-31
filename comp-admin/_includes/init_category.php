<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//Инициализация соревновательных таблиц, очистка стартовой ведомости.
require_once('_includes/started_functions.php');
function init_category($comp_id,$cat_id){
	global $cat_name; //имена категорий
	global $compres_dbt;
	global $complegres_dbt, $complegdetails_dbt;
	global $compgpsres_dbt, $compgpstime_dbt;

	$comp_id=(int)$comp_id; $cat_id=(int)$cat_id;
	if(!$comp_id)
		die("init_category($comp_id,$cat_id) - некорректно указан id соревнования");
	if(!$cat_id or $cat_id>_CATEGORIES or !strlen($cat_name[$cat_id]))
		die("init_category($comp_id,$cat_id) - некорректно указан id категории");

	$type=get_type_by_cat_id($comp_id,$cat_id);

	switch($type){

	case 'legend':
		query_eval("DELETE FROM $complegres_dbt WHERE comp_id=$comp_id AND start_number IN (SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id)"); //очистка таблицы со временем 
		query_eval("DELETE FROM $complegdetails_dbt WHERE comp_id=$comp_id AND start_number IN (SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id)"); //очистка таблицы со взятыми точками (если линейка подробная)
		break;
	case 'gps':
		query_eval("DELETE FROM $compgpstime_dbt WHERE comp_id=$comp_id AND start_number IN (SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id)");  //таблица со временем
		query_eval("DELETE FROM $compgpsres_dbt WHERE comp_id=$comp_id AND start_number IN (SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id)"); //таблица с точками
		break;
	case 'gr-gps':
		//пока здесь то же, что и выше
		query_eval("DELETE FROM $compgpstime_dbt WHERE comp_id=$comp_id AND start_number IN (SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id)");  //таблица со временем
		query_eval("DELETE FROM $compgpsres_dbt WHERE comp_id=$comp_id AND start_number IN (SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id)"); //таблица с точками
		break;
	}
}
