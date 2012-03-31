<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//
//Функции для изменения категории участника после регистрации. Написание этой библиотеки вызвано тем, что регистрация задумавалась как окончательное и бесповоротное действие. Фактически оказалось, что категорию иногда необходимо менять - к примеру при проведении техкомиссии, или просто из-за распиздяйства того, кто сидит на регистрации. Смена категории весьма неоднозначна, т.к.  приспичить ее поменять может в любой момент времени - сразу после регистрации, либо ваще в конце гонки, когда уже сгенерирована стартовая ведомость, и забита часть результатов. 
//
//Фактически решение о том, что делать при замене категории принимается исходя из наличия в меняемой и меняющей категории стартовой ведомости. Таким образом у нас получается четыре случая по наличию стартовой ведомости - YY, YN, NY и NN. Основная функция этой библиотеки и занимается тем, что определяет состояние категорий и на основе него выбирает функцию, которую необходимо запустить для смены категории. 
//
//Сейчас описана два основных частных случая, YY и NN, остальные функции являются производными от них.
//
//	* change_cat_id($comp_id, $start_number, $new_cat_id) - сменить категорию. Если на такую категорию менять нельзя, сделает die()
//	* can_change_cat_id($comp_id, $start_number, $new_cat_id) - можно ли изменить категорию участника на заданную. В рассчет берется состояние категорий (наличие Стартовой ведомости) и настройки системы, в которых можно запретить трогать стартовые ведомости после их регистрации		$item_output[$id]['disable_register_link']=true;
//	* categories_to_change($comp_id, $start_number) - возвращает массив, вида ($cat_id=>true), в котором перечислены все категории, на которую можно сменить существующую
//	* category_accepting_changes($comp_id, $cat_id) - можно ли ваще изменять что-то в категории. Вернет ложь, лишь в случае если по категории есть стартовая ведомость, а трогать стартовые ведомости запрещено
//
//	Служебные функции:
//	* _what_to_change($comp_id,$start_number,$new_cat_id) - получить состояние категорий вида "StatStat" где Stat это Y или N в зависисмости от наличия стартовой ведомости в категории. Первый Stat - старая категория, второй - новая.
//	* _change_NN ($comp_id, $start_number, $new_cat_id, $delete_old=false) - первый основной частный случай.
//	* _change_YN ($comp_id, $start_number, $new_cat_id) - производная от предыдущей функции. Ставит последний параметр true, что означает "чистить стартовую ведомость в старой категории"
//	* _change_YY ($comp_id, $start_number, $new_cat_id, $delete_old=true) - второй основной частный случай.
//	* _change NY ($comp_id, $start_number, $new_cat_id) - производная от предыдущей функции. Ставит последний параметр false, что означает "НЕ чистить стартовую ведомость в старой категории"
//
//
//
//
require_once('_includes/started_functions.php');
require_once('_includes/request_functions.php');

function change_cat_id($comp_id,$start_number,$new_cat_id){ //глобальная функция для того, чтобы изменить категорию. Это враппер над нижними четырьмя функциями
	$comp_id=(int)$comp_id; $start_number=(int)$start_number; $new_cat_id=(int)$new_cat_id;
	if(!$comp_id or !$start_number or !$new_cat_id)
		return false;
	//нам необходимо определить состояние предыдущих и новых категорий
	if(!can_change_cat_id($comp_id,$start_number,$new_cat_id))
		die("Изменение категории невозможно!");

	$cat_status=_what_to_change($comp_id,$start_number,$new_cat_id);
	if(function_exists('_change_'.$cat_status))
		return eval("return _change_$cat_status($comp_id,$start_number,$new_cat_id);");
}

function can_change_cat_id($comp_id,$start_number,$new_cat_id){ //узнать, можно ли менять категорию
	$cat_status=_what_to_change($comp_id,$start_number,$new_cat_id);
	$req_data=get_brief_request_data($comp_id,num2req($comp_id,$start_number));
	if($req_data['cat_id']==$new_cat_id) //если ничего не меняем, то и смотреть нефига
		return true;
	if(!defined('CAN_CHANGE_CAT_ID_AFTER_REGISTER') or !CAN_CHANGE_CAT_ID_AFTER_REGISTER){ //ваще ничего менять нельзя
		return false;
	}
	if(strstr($cat_status,'Y')) //если или в новой или в старой категории уже есть стартовая ведомость, но менять нельзя - нахуй
		if(!defined('CAN_CHANGE_CAT_ID_AFTER_START_LIST') or !CAN_CHANGE_CAT_ID_AFTER_START_LIST)
			return false;
	return true;
}
function category_accepting_changes($comp_id,$cat_id){ //узнать, можно ли ваще вносить изменения в категорию
	$comp_id=(int)$comp_id; $cat_id=(int)$cat_id;
	if(!$comp_id or !$cat_id)
		return false;
	$started_categories=get_started_categories($comp_id);
	
	if(array_key_exists($cat_id,$started_categories))
		if(!defined('CAN_CHANGE_CAT_ID_AFTER_START_LIST') or !CAN_CHANGE_CAT_ID_AFTER_START_LIST)
			return false;
	return true;
}
	
function categories_to_change($comp_id,$start_number){ //узнать список категорий, на которые можно изменить категорию
	$comp_id=(int)$comp_id; $start_number=(int)$start_number;
	if(!$comp_id or !$start_number)
		return false;
	$ret=array();
	for($i=1;$i<=_CATEGORIES;$i++)
		if(can_change_cat_id($comp_id,$start_number,$i))
			$ret[$i]=true;
	return $ret;
}

function _what_to_change($comp_id,$start_number,$new_cat_id){ //получить состояние категорий, которые меняем
	$comp_id=(int)$comp_id; $start_number=(int)$start_number; $new_cat_id=(int)$new_cat_id;
	if(!$comp_id or !$start_number or !$new_cat_id)
		return false;
	$req_data=get_brief_request_data($comp_id,num2req($comp_id,$start_number));
	$old_cat_id=$req_data['cat_id'];
	$started_categories=get_started_categories($comp_id);
	$r1=$r2='N';
	if(array_key_exists($old_cat_id,$started_categories))
		$r1='Y';
	if(array_key_exists($new_cat_id,$started_categories))
		$r2='Y';
	return "$r1$r2";

}
function _change_NN($comp_id,$start_number,$new_cat_id,$delete_old=false){ //частный случай - меняем из категории, в которой еще не было стартовой ведомости на категорию в которой тоже нет ведомости
	global $compreq_dbt, $compres_dbt;
	$comp_id=(int)$comp_id; $start_number=(int)$start_number; $new_cat_id=(int)$new_cat_id;
	$item_id=num2req($comp_id,$start_number);
	if($delete_old)
		clear_results($comp_id,$start_number);
	query_eval("UPDATE $compreq_dbt SET category=$new_cat_id WHERE ID=$item_id LIMIT 1;");
	query_eval("UPDATE $compres_dbt SET cat_id=$new_cat_id WHERE start_number=$start_number LIMIT 1;");
	return 1;
}

function _change_YN($comp_id,$start_number,$new_cat_id){ //меняем из категории в которой есть стартовая ведомость на категорию в которой нет стартовой ведомости
	return _change_NN($comp_id,$start_number,$new_cat_id,true); //отличия в том, что мы "чистим результаты" в предыдущей категории
}
function _change_NY($comp_id,$start_number,$new_cat_id){ //меняем из категории в которой нет стартовой ведомости на категорию в которой есть
	return _change_YY($comp_id,$start_number,$new_cat_id,false); //отличия в том, что мы не "чистим результаты" в старой категории

}
function _change_YY($comp_id,$start_number,$new_cat_id, $delete_old=true){ //частный случай - меняем из категории в которой есть стартовая ведомость на категорию в которой тоже есть
	global $compreq_dbt, $compres_dbt;
	global $complegres_dbt;
	global $compgpstime_dbt;
	$comp_id=(int)$comp_id; $start_number=(int)$start_number; $new_cat_id=(int)$new_cat_id;
	if(!$comp_id)
		die("change_cat_id_in_start_list: bad comp_id");
	if(!$start_number)
		die("change_cat_id_in_start_list: bad start_number");
	if(!$new_cat_id)
		die("change_cat_id_in_start_list: bad new_cat_id");

	$req_data=get_brief_request_data($comp_id,num2req($comp_id,$start_number));
	$old_cat_id=$req_data['cat_id']; 
	$new_type=get_type_by_cat_id($comp_id,$new_cat_id);
	$request_id=num2req($comp_id,$start_number);

	//Подбираем новое время старта
	$new_time=0;
	switch($new_type){ 
	case 'legend': //здесь запихиваем его в очередь с максимальным временем в категории
		$res=query_eval("SELECT start_time FROM $complegres_dbt WHERE comp_id=$comp_id AND start_number IN (SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id=$new_cat_id) ORDER BY start_time DESC");
		if(!mysql_num_rows($res))
			die("change_cat_id_in_start_list: Невозможно получить максимальное время старта по легенде для категории ($new_cat_id)");
		$row=mysql_fetch_row($res); $st1=(int)$row[0];
		$row=mysql_fetch_row($res); $st2=(int)$row[0];
		$new_time=$st1+$st1-$st2; //новое время больше прошлого времени на интервал, взятый из двух последних стартовавших
	break;
	case 'gps': //просто берем максимальное стартовое время
		$res=query_eval("SELECT MAX(start_time) FROM $compgpstime_dbt WHERE comp_id=$comp_id AND start_number IN (SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id=$new_cat_id);");
		if(!mysql_num_rows($res))
			die("change_cat_id_in_start_list: невозможно получить время старта ни одного участника по gps");
		$row=mysql_fetch_row($res);
		$new_time=(int)$row[0];
	break;
	case 'gr-gps': //тоже самое
		$res=query_eval("SELECT MAX(start_time) FROM $compgpstime_dbt WHERE comp_id=$comp_id AND start_number IN (SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id=$new_cat_id);");
		if(!mysql_num_rows($res))
			die("change_cat_id_in_start_list: невозможно получить время старта ни одного участника по gps");
		$row=mysql_fetch_row($res);
		$new_time=(int)$row[0];
		break;
	}
	if(!$new_time)
		die("change_cat_id_in_start_list: ошибка формирования нового времени старта..");
	
	query_eval("UPDATE $compres_dbt SET cat_id=$new_cat_id WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;");
	query_eval("UPDATE $compreq_dbt SET category=$new_cat_id WHERE comp_id=$comp_id AND ID='$request_id' LIMIT 1;");
	if($delete_old)
		clear_results($comp_id,$start_number); //удаляем из специфических таблиц
	update_start_time($comp_id,$new_time,$start_number);
	return 1;

}
