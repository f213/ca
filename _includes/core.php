<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
error_reporting(E_ALL ^ E_NOTICE);
require('conf_parse.php'); //обработка конфигурационных файлов
require('dbd/'.DB_TYPE.'.php'); //драйвер авторизационной (базовой) БД
require('dbt.php'); //имена таблиц

require('current_comp.php'); //узнаем ID текущего соревнования, дальше оно везде фигурирует как константа CURRENT_COMP
require('comp_data.php'); //"официальные" данные гонки
require('people_names.php'); //библиотека для именования членов экипажа, на разных гонках бывают разные
require('comp_cat_var.php'); //библиотека для хранения параметров гонки по каждой категории
require('unicode_str.php'); //работа с юникодными строками


$admin_user='fedor.test'; //заглушка
$admin_mail_to='fedor@gr4x4.ru';


//здесь только служебные настройки, либо те настройки, которые мне жалко выкинуть. Все юзерские настройки лежат в ini-файлах папки conf
define("GR_ENABLE_DRSU",0); //нужен ли раздел "ЗЛ-площадь"
define('CA_USE_AMP_LOL',0); //ЫЫ. Для валидации менять во всех урлах & на &amp;. Сделано по приколу, работает не везде.
$keypad_pages=array('tk'); //места, где нужно использовать выплывающий keypad, возможные значенения
// tk - техкомиссия, ввод бортового номера
//
//

//дальше вообще не смотреть.
$mon_name=array(
	1=>'Январь',
	2=>'Февраль',
	3=>'Март',
	4=>'Апрель',
	5=>'Май',
	6=>'Июнь',
	7=>'Июль',
	8=>'Август',
	9=>'Сентябрь',
	10=>'Октябрь',
	11=>'Ноябрь',
	12=>'Декабрь',
);	

$from_array=array(
	'online'=>'Онлайн-заявка',
	'forum'=>'Форум',
	'email'=>'E-mail',
	'admin'=>'Админ',
	'import'=>'Импорт',
);
$types_array=array(
	'legend'=>'Линейная гонка',
	'gps'=>'Ориентирование',
	'gr-gps'=>'Ориентирование ЗЛ',
);
$sl_sort_types=array( //все возможные типы сортировки списка участников при создании стартовой ведомости. Обрабатываются в gen_start_list.php, обрезаются чуть ниже, т.к. у нас есть возможность отключить размер колес.
	'start_number'=>'Бортовой номер',
	'start_number_rev'=>'Бортовой номер (обратный)',
	'request_date'=>'Дата подачи заявки',
	'register_date'=>'Время регистрации',
	'wheel_size'=>'Размер колес',
	'wheel_size_rev'=>'Размер колес (обратный)',
	'request_date,wheel_size'=>'Дата подачи заявки, размер колес',
);



if(defined('CA_WHEEL_SIZE') and !CA_WHEEL_SIZE)
	if(defined('CA_REQUIRE_WHEEL_SIZE') and CA_REQUIRE_WHEEL_SIZE)
		die('Ошибка в common.php! Недопустимо указание CA_REQUIRE_WHEEL_SIZE без CA_WHEEL_SIZE!');

if(defined('CA_WINCH_AUTODETECT') and CA_WINCH_AUTODETECT)
	if(!defined('CA_TRACK_WINCH') or !CA_TRACK_WINCH)
		die('Ошибка в common.php! Недопустимо указание CA_WINCH_AUTODETECT без CA_TRACK_WINCH!');
//нулевые секунды
$_null_sec_bool=false;
if(defined('SHOW_SECONDS_EVERYWHERE') and SHOW_SECONDS_EVERYWHERE)
	$_null_sec_bool=true;

if(!defined('CA_WHEEL_SIZE') or !CA_WHEEL_SIZE){
	$bad_sort_types=array();
	foreach($sl_sort_types as $key=>$value)
		if(preg_match('/wheel_size/i',$key))
			$bad_sort_types[]=$key;
	foreach($bad_sort_types as $key)
		unset($sl_sort_types[$key]);
}

////////////////////////////
function query_eval($query)
{
	 global $stat_queries,$query_log;
	 $stat_queries++;

	 $stat_alltime=microtime();
	 if($eval_result=@mysql_query($query)){
	 	list($usec,$sec)=explode(" ",$stat_alltime);
	 	$stat_alltime2=(float)$usec+(float)$sec;
	 	list($usec,$sec)=explode(" ",microtime());
	 	$stat_alltime2=(int)((((float)$usec+(float)$sec)-$stat_alltime2)*1000);
	 	$query_log.="<br> $stat_alltime2  $query\n";
	 	return $eval_result;
	}else{
		 echo $msg="<p>MySQL error:".mysql_error()."<br>Query: ".$query."</p>";
		 //error_log($msg,0);
		 return false;
	}
}
function user_error_handler($errno, $errmsg, $filename, $linenum)
{
 $errortype = array(1=>'Error', 2=>'Warning!', 4=>'Parsing Error', 8=>'Notice', 16=>'Core Error', 32=>'Core Warning!', 64=>'Compile Error', 128=>'Compile Warning!', 256=>'User Error', 512=>'User Warning!', 1024=>'User Notice');
 $msg="PHP error $errno {$errortype[$errno]}: $errmsg ($filename - line $linenum)";
 #error_log($msg,0);
}
function add_item($db_name,$item_data,$item_id=0,$id_name='ID')
{
 if(!empty($item_id)) { $pre="UPDATE"; $post=" WHERE `$id_name`='".addslashes($item_id)."'"; }
 else { $pre="INSERT INTO"; $post=''; }

 $str='';
 foreach($item_data as $key=>$value)
 {
  if($str!=='') $str.=', ';
  $str.="`".$key."`='".escape_value($value)."'";
 }
 if(query_eval($pre." $db_name SET $str".$post))
 {
  if($item_id) return $item_id;
  else return mysql_insert_id();
 }
 return false;
}

function delete_item($db_name,$item_id,$kid='id')
{
 if(isset($item_id)) query_eval("DELETE FROM $db_name WHERE $kid='".addslashes($item_id)."'");
}
function replace_item($db_name, $item_data)
{
  $pre = "REPLACE INTO";
  $str = '';
  foreach( $item_data as $key => $value )
  {
    if($str!=='') $str.=', ';
    $str.='`'.$key."`='".addslashes(str_replace(array(chr(171), chr(150), chr(187), chr(151), chr(179), chr(174), chr(153), chr(136), chr(132), chr(147), chr(167)), array('&laquo;','&ndash;','&raquo;','&mdash;','&#8470;','&reg;','&trade;','&euro;','&bdquo;','&ldquo;','&sect;'), $value))."'";
  }
  return query_eval($pre." $db_name SET $str");
}

function escape_value($value) {
  return (get_magic_quotes_gpc()?$value:mysql_real_escape_string($value));
}

function rnd_str($length){
	$ret='';
	for ($i=0; $i<$length; $i++) { 
		$d=rand(1,30)%2; 
		$ret.= $d ? chr(rand(65,90)) : chr(rand(48,57)); 
	}
	return $ret;
}	
function append_rnd($str){ //использовать на всех важных ссылках.
	$res='';
	if(preg_match('/\?[^\=]+\=/',$str))
		if(preg_match('/\&$/',$str))
			$res=$str."rnd=".rnd_str(7);
		else
			$res="$str&rnd=".rnd_str(7);
	else
		$res="$str?rnd=".rnd_str(7);
	if(defined('CA_USE_AMP_LOL') AND CA_USE_AMP_LOL)
		$res=str_replace('&','&amp;',$res);
	return $res;
}
function comp_name($comp_id=-127){ //имя соревнования
	global $comp_dbt;
	if($comp_id==-127)
		$comp_id=CURRENT_COMP;
	$comp_id=(int)$comp_id;
	$res=query_eval("SELECT Name FROM $comp_dbt WHERE ID=$comp_id");
	if(mysql_num_rows($res)){
		$row=mysql_fetch_row($res);
		return stripslashes($row[0]);
	}else
		return null;
}
function get_valid_numbers_str($valid_numbers){
	if(!sizeof($valid_numbers) or ! $valid_numbers)
		return null;
	$str=implode(',',$valid_numbers);
	$str=trim($str,',');
	return $str;
}
function _input_val($flagname){ //для одновременной проверки _GET и _POST
	if(array_key_exists($flagname,$_GET))
		return $_GET[$flagname];
	if(array_key_exists($flagname,$_POST))
		return $_POST[$flagname];
}
