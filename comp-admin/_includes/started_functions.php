<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//Библиотека для получения данных из БД после того, как дан старт.
//
//Параметр "время" для всех функций это время в секундах с 00:00 дня старта гонки.
//	
//	* get_started_categories($comp_id) - получить [довольно большую] информацию о стартовавших в текущей гонке категориях участников. Пример работы описан ниже, около самой функции.
//	* get_started_numbers($comp_id,$cat_id) - получить список стартовавших в категории участников
//	* get_full_valid_numbers($comp_id) - получить список всех стартовавших участников, во всех категориях
//
//	Функции работы со времем. Их можно использовать только, если уверен, что в базе запись есть. Если нет - ошибка.
//	* update_start_time($comp_id,$start_time,$start_number) - изменить время старта для участника
//	* get_start_time($comp_id,$start_number) - получить время старта
//	* has_start_time($comp_id,$start_number) - тоже самое, что выше, но без вывода ошибок. Нужно для того, чтобы проверять наличие участника в стартовой ведомости в тот момент, когда он еще не зарегистрирован, а стартовая ведомость не созданна.
//	* update_finish_time($comp_id,$finish_time,$start_number) - изменить время финиша для участника
//	* get_finish_time($comp_id,$start_number) - получить время финигша
//
//	Конвертация номера заявки в бортовой номер. И наоборот.
//	* num2req($comp_id,$start_number) - получить id заявки зная стартовый номер
//	* req2num($comp_id,$request_id) - получить стартовый номер, если участник зарегистрирован
//	
//	* in_start_list($comp_id,$start_number) - стартовал ли ваще такой участник?
//	* clear_results($comp_id,$start_number) - очистить нафиг все результаты участника, как будто и не стартовал.
//
//	Получение "флагов" экипажа
//	* tk_is_passed($comp_id,$start_number) - булин, истина, если техкомиссия пройдена
//	* tk_is_relative($comp_id,$start_number) - нихрена не возвращает, если техкомиссия не пройдена, вовзращает хоть-что-нибудь, если техкомиссия и пройдена, но стоит флаг "условный допуск". Хоть-что-нибудь может являться причиной условного допуска, указанной техкомиссаром.
//	* has_portal($comp_id,$start_number) - стоит ли флаг "портальные мосты"
//	* has_winch($comp_id,$start_number) - стоит ли флаг "лебедка"
//
//
//	Функции, которые писались как служебные, но все равно светятся и их можно использовать.
//	* get_cat($comp_id,$start_number) - получить id категории по стартовуму номеру
//	* get_type_by_sn($comp_id,$start_number) - получить тип соревнования по стартовому номеру
//	* get_type_by_cat_id($comp_id,$cat_id) - получить тип соревнования по категории участника. После перехода на category variables является оберткой над _cat_var
//	
//	Другие заглушки над _cat_var
//	* function max_time_by_cat_id($comp_id,$cat_id) - максимальное время на трассе для категории
//	* legend_max_kps($comp_id,$cat_id) - максимальное количество КП для категорий, которые работают по линейной гонке.
//
require_once('time_functions.php');
function get_started_numbers($comp_id,$cat_id){
	global $complegres_dbt,$compgpstime_dbt,$compres_dbt;
	$comp_id=(int)$comp_id;
	$cat_id=(int)$cat_id;
	$type=get_type_by_cat_id($comp_id,$cat_id);
	if(!$type)
		return null;
	if($type=='legend')
		$dbt=$complegres_dbt;
	elseif($type=='gps' or $type=='gr-gps')
		$dbt=$compgpstime_dbt;
	if(!$dbt) //есть такой глюк - если заюзать базу, в которой есть только данные под один тип соревнования (ну если я на будущее буду делать урезанные версии, то вылезает хуйня.
		return null;
	$res=query_eval("SELECT a.start_number AS start_number FROM $dbt a, $compres_dbt b WHERE a.comp_id=$comp_id AND b.comp_id=$comp_id AND b.start_number=a.start_number AND b.cat_id=$cat_id;");
	$valid_numbers=array();
	while($row=mysql_fetch_row($res))
		$valid_numbers[]=(int)$row[0];
	return $valid_numbers;
}	
function get_full_valid_numbers($comp_id){
	global $compres_dbt;
	$comp_id=(int)$comp_id;
	$res=query_eval("SELECT DISTINCT(start_number) FROM $compres_dbt WHERE comp_id=$comp_id;");
	$valid_numbers=array();
	while($row=mysql_fetch_row($res))
		$valid_numbers[]=(int)$row[0];
	return $valid_numbers;
}	

function update_start_time($comp_id,$time,$start_number){ //обновить время старта участнега.
	global $complegres_dbt,$compgpstime_dbt,$compres_dbt;
	$start_number=(int)$start_number;
	$comp_id=(int)$comp_id;
	$time=(int)$time;
	if(!$start_number or !$comp_id or !$time)
		return null;
	$type=get_type_by_sn($comp_id,$start_number);
	if($type=='legend')
		if(has_start_time($comp_id,$start_number))
			query_eval("UPDATE $complegres_dbt SET start_time='$time' WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;");
		else{
			$cat_id=get_cat($comp_id,$start_number);
			query_eval("INSERT INTO $complegres_dbt SET comp_id=$comp_id, start_number='$start_number', cat_id=$cat_id, start_time='$time';");
		}
	if($type=='gps' or $type=='gr-gps')
		if(has_start_time($comp_id,$start_number))
			query_eval("UPDATE $compgpstime_dbt SET start_time='$time' WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;");
		else
			query_eval("INSERT INTO $compgpstime_dbt SET comp_id=$comp_id, start_number='$start_number', start_time='$time';");
	return 1;
}
function update_finish_time($comp_id,$time,$start_number){ //обновить время финиша
	global $complegres_dbt,$compgpstime_dbt,$compres_dbt;
	$start_number=(int)$start_number;
	$comp_id=(int)$comp_id;
	$time=(int)$time;
	if(!$start_number or !$comp_id)
		return null;
	if(!$time)
		$time=0;
	$start_time=get_start_time($comp_id,$start_number);
	if($time<$start_time) //значит мы перешагиваем через 12 часов
		$time+=24*3600;
	$type=get_type_by_sn($comp_id,$start_number);
	if($type=='legend')
		query_eval("UPDATE $complegres_dbt SET finish_time='$time' WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;");
	if($type=='gps' or $type=='gr-gps')
		query_eval("UPDATE $compgpstime_dbt SET finish_time='$time' WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;");
	return 1;
}
function has_start_time($comp_id,$start_number){ //находится ли участник в стартовой ведомости
	if(get_start_time($comp_id,$start_number,false))
		return true;
	return false;
}
function get_start_time($comp_id,$start_number,$die_on_error=true){ //получить время старта
	global $complegres_dbt,$compgpstime_dbt;

	$start_number=(int)$start_number;
	if(!$start_number or !$comp_id)
		return null;
	$type=get_type_by_sn($comp_id,$start_number);
	if($type=='legend'){
		$res=query_eval("SELECT start_time FROM $complegres_dbt WHERE comp_id=$comp_id AND start_number='$start_number';");
		if(!mysql_num_rows($res))
			if($die_on_error)
				die("get_start_time(): невозможно получить время старта по линейке для ($start_number)");
			else
				return null;
		list($start_time)=mysql_fetch_row($res);
	}
	if($type=='gps' or $type=='gr-gps'){
		$res=query_eval("SELECT start_time FROM $compgpstime_dbt WHERE comp_id=$comp_id AND start_number='$start_number';"); 
		if(!mysql_num_rows($res))
			if($die_on_error)
				die("get_start_time(): невозможно получить время старта по GPS для ($start_number)");
			else
				return null;
		list($start_time)=mysql_fetch_row($res);
	}
	return $start_time;
}

function get_finish_time($comp_id,$start_number){ //получить время финиша
	global $complegres_dbt,$compgpstime_dbt;
	$start_number=(int)$start_number;
	if(!$start_number or !$comp_id)
		return null;
	$type=get_type_by_sn($comp_id,$start_number);
	$finish_time=0;
	if($type=='legend'){
		$res=query_eval("SELECT finish_time FROM $complegres_dbt WHERE comp_id=$comp_id AND start_number='$start_number';");
		if(!mysql_num_rows($res))
			die("get_start_time(): невозможно получить время старта по линейке для ($start_number)");
		list($finish_time)=mysql_fetch_row($res);
	}
	if($type=='gps' or $type=='gr-gps'){
		$res=query_eval("SELECT finish_time FROM $compgpstime_dbt WHERE comp_id=$comp_id AND start_number='$start_number';"); 
		if(!mysql_num_rows($res))
			die("get_start_time(): невозможно получить время старта по GPS для ($start_number)");
		list($finish_time)=mysql_fetch_row($res);
	}
	return $finish_time;
}
function clear_results($comp_id,$start_number){
	global $compres_dbt;
	global $complegres_dbt,$complegdetails_dbt;
	global $compgps_dbt, $compgpsres_dbt, $compgpstime_dbt;
	$comp_id=(int)$comp_id; $start_number=(int)$start_number;
	if(!$start_number or !$comp_id)
		return null;

	$type=get_type_by_sn($comp_id,$start_number);
	if($type=='legend'){
		query_eval("DELETE FROM $complegres_dbt WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;"); //из ведомости времени линейки
		query_eval("DELETE FROM $complegdetails_dbt WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;"); //из таблицы "подробная легенда"
	}
	if($type=='gps' or $type=='gr-gps'){
		query_eval("DELETE FROM $compgpstime_dbt WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;"); //из ведомости времени
		query_eval("DELETE FROM $compgpsres_dbt WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;"); //из списков взятых точек
	}
	return true;
}



function num2req($comp_id,$start_number){ //получить номер заявки по стартовому номеру
	global $compres_dbt;
	$start_number=(int)$start_number;
	$comp_id=(int)$comp_id;
	if(!$start_number or !$comp_id)
		return null;
	$res=query_eval("SELECT request_id FROM $compres_dbt WHERE comp_id=$comp_id AND start_number='$start_number';");
	if(!mysql_num_rows($res))
		return null;
	$row=mysql_fetch_row($res);
	return (int)$row[0];
}
function req2num($comp_id,$request_id){ //получить стартовый номер по номеру заявки
	global $compres_dbt;
	$comp_id=(int)$comp_id;
	$request_id=(int)$request_id;
	if(!$request_id or !$comp_id)
		return null;
	$res=query_eval("SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND request_id='$request_id';");
	if(!mysql_num_rows($res))
		return null;
	$row=mysql_fetch_row($res);
	return (int)$row[0];
}	
function get_cat($comp_id,$start_number){ //получить id категории
	global $compres_dbt;
	if(!$start_number or !$comp_id)
		return null;
	$res=query_eval("SELECT cat_id FROM $compres_dbt WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;");
	if(!mysql_num_rows($res))
		return null;
	$row=mysql_fetch_row($res);
	return (int)$row[0];
}
function get_type_by_sn($comp_id,$start_number){ //получить тип соревнования, зная стартовый номер
	if(!$start_number or !$comp_id)
		return null;
	$cat_id=get_cat($comp_id,$start_number);
	return get_type_by_cat_id($comp_id,$cat_id);
}
function get_type_by_cat_id($comp_id,$cat_id){ //получить типа соревнования, зная категорию
	return _cat_var($comp_id,$cat_id,'type');
}	
/*
get_started_categories($comp_id) - получить список стартовавших категорий, пример использования:

	$started_categories=array();
	$started_categories=get_started_categories($comp_id);

ВЫВОД

array(3) {
  [1]=>
  array(3) {
    ["type"]=>
    string(6) "legend"
    ["num_started"]=>
    int(7)
    ["name"]=>
    string(7) "Коляски"
  }
  [2]=>
  array(3) {
    ["type"]=>
    string(6) "legend"
    ["num_started"]=>
    int(21)
    ["name"]=>
    string(7) "Повозки"
  }
  [3]=>
  array(3) {
    ["type"]=>
    string(6) "legend"
    ["num_started"]=>
    int(9)
    ["name"]=>
    string(7) "Фургоны"
  }
}
Ключ - id категории
 */

function get_started_categories($comp_id){
	global $compres_dbt,$complegres_dbt,$compgpstime_dbt;
	global $cat_name; //имена категорий
	if(!$comp_id)
		return array();
	$cat=array();
	for($i=1;$i<=_CATEGORIES;$i++){
		$type=_cat_var($comp_id,$i,'type');
		if(!$type)
			continue;
		$num_started=0;
		if($type=='legend'){
			$res=query_eval("SELECT * FROM $complegres_dbt WHERE comp_id=$comp_id AND cat_id=$i;");
			$num_started=mysql_num_rows($res);
		}
		if($type=='gps' or $type=='gr-gps'){
			#$res=query_eval("SELECT * FROM $compgpstime_dbt WHERE comp_id=$comp_id AND cat_id=$i;");
			$res=query_eval("SELECT a.start_number FROM $compgpstime_dbt a, $compres_dbt b WHERE a.comp_id=$comp_id AND b.comp_id=$comp_id AND a.start_number = b.start_number AND b.cat_id=$i;");
			$num_started=mysql_num_rows($res);
		}
		if(!$num_started)
			continue;
		$cat[$i]['type']=$type;
		$cat[$i]['num_started']=$num_started;
		$cat[$i]['name']=$cat_name[$i];
	}
	return $cat;
}

function tk_is_passed($comp_id,$start_number){ //узнать, пройдена ли техкомиссия
	global $comptk_dbt;
	$start_number=(int)$start_number;
	$comp_id=(int)$comp_id;
	if(!$start_number or !$comp_id)
		return null;

	$res=query_eval("SELECT * FROM $comptk_dbt WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;");
	if(!mysql_num_rows($res))
		return false;
	return true;
}

function tk_relative($comp_id,$start_number){ //узнать, нет ли условного допуска
	global $comptk_dbt;
	$start_number=(int)$start_number;
	$comp_id=(int)$comp_id;
	if(!$start_number or !$comp_id)
		return null;
	if(!tk_is_passed($comp_id,$start_number))
		return null;
	$res=query_eval("SELECT relative, relative_reason FROM $comptk_dbt WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;");
	if(!mysql_num_rows($res))
		die("tk_is_relative($comp_id,$start_number): ошибка запроса к БД!");
	$row=mysql_fetch_row($res);
	if($row['0']!='yes')
		return false;
	if(strlen($row[1]))
		return stripslashes($row[1]);
	else
		return true;
}
function has_portal($comp_id,$start_number){ //узнать, есть ли на машине порталы
	global $compres_dbt;
	$start_number=(int)$start_number;
	$comp_id=(int)$comp_id;
	if(!$start_number or !$comp_id)
		return null;

	$res=query_eval("SELECT portal FROM $compres_dbt WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;");
	if(!mysql_num_rows($res))
		return null;
	$row=mysql_fetch_row($res);
	if($row[0]=='yes')
		return true;
	return false;
}
function has_winch($comp_id,$start_number){ //узнать, есть ли лебеда
	global $compres_dbt;
	$start_number=(int)$start_number;
	$comp_id=(int)$comp_id;
	if(!$start_number or !$comp_id)
		return null;

	$res=query_eval("SELECT winch FROM $compres_dbt WHERE comp_id=$comp_id AND start_number='$start_number' LIMIT 1;");
	if(!mysql_num_rows($res))
		return null;
	$row=mysql_fetch_row($res);
	if($row[0]=='yes')
		return true;
	return false;
}

function in_start_list($comp_id,$start_number){ //узнать, есть ли участник в стартовой ведомости
	global $complegres_dbt,$compgpstime_dbt;
	if(!$comp_id or !$start_number)
		return null;

	
	$type=get_type_by_sn($comp_id,$start_number); 
	if(!$type) //если функция get_type не отработала, значит участника нету в таблице $compres_dbt
		return false;

	$dbt='';
	//а теперь проверяем, есть ли участник в таблице, специфичной для типа соренования
	switch($type){
	case 'legend':
		$dbt=$complegres_dbt;
		break;
	case 'gps':
		$dbt=$compgpstime_dbt;
		break;
	case 'gr-gps':
		$dbt=$compgpstime_dbt;
		break;
	}
	$res=query_eval("SELECT * FROM $dbt WHERE comp_id=$comp_id AND start_number=$start_number;");
	if(mysql_num_rows($res))
		return true;
	return false;
}
function max_time_by_cat_id($comp_id,$cat_id){ //максимальное время на трассе для категории, работает для всех типов
	return _cat_var($comp_id,$cat_id,'max_time');
}
function legend_max_kps($comp_id,$cat_id){ //максимальное количество КП для категории, работает только для legend
	return _cat_var($comp_id,$cat_id,'max_kp');
}

//так получилось, что часть функций, получающих какие-то данные категории, являются оберткой над _cat_var(). Это сделано для обратной совместимости, да и читаемость кода навернео повысит.
