<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//
//библиотека для БД-независимой работы с временем, в основном его форматирование
//
//UPD: Добавлен парсинг введенного юзером времени. Принимаются данные в виде чч:мм:сс и чч:мм, отдает массив int (часы, минуты, секунды)
//parse_user_time("13:01:15")==array(13,1,15);
//parse_user_time("13:15")==array(13,15,0);
//parse_user_time("53:12:22")==die("неправильно указаны часы"
//
function format_hm_time($timestamp){ //выводим время в виде часы:минуты
	$ret='';
	if($timestamp>24*60*60){ //больше суток
		$days=floor($timestamp/(24*60*60));
		$timestamp=$timestamp%(24*60*60);
	}
	$hours=_append_zero(floor($timestamp/(60*60))+$days*24);
	$timestamp=$timestamp%(60*60);
	$ret.="$hours:";
	$min=_append_zero(floor($timestamp/60));
	$timestamp=$timestamp%60;
	$ret.="$min";

	return $ret;
}
function format_hms_time($timestamp,$show_sec_if_none=true){ //выводим время в виде часы:минуты:секунды, вместе с сутками
	$ret='';
	if($timestamp>24*60*60){ //больше суток
		$days=floor($timestamp/(24*60*60));
		$timestamp=$timestamp%(24*60*60);
	}
	$hours=_append_zero(floor($timestamp/(60*60))+$days*24);
	$timestamp=$timestamp%(60*60);
	$ret.="$hours:";
	$min=_append_zero(floor($timestamp/60));
	$timestamp=$timestamp%60;
	$ret.="$min";
	if(!$show_sec_if_none and !$timestamp)
		return $ret;
	$ret.=':'._append_zero($timestamp);

	return $ret;
}
function format_user_hms_time($timestamp,$show_sec_if_none=true){ //выводим время в виде часы:минуты:секунды для юзера, выкидаываем сутки
	$ret='';
	if($timestamp>24*60*60){ //больше суток
		$days=floor($timestamp/(24*60*60));
		$timestamp-=$days*24*60*60;
	}
	$hours=_append_zero(floor($timestamp/(60*60)));
	$timestamp=$timestamp%(60*60);
	$ret.="$hours:";
	$min=_append_zero(floor($timestamp/60));
	$timestamp=$timestamp%60;
	$ret.="$min";
	if(!$show_sec_if_none and !$timestamp)
		return $ret;
	$ret.=':'._append_zero($timestamp);

	return $ret;
}

function format_user_time($timestamp){ //тоже, что и format_hm_time, тока человекочитаемо, вычитаем сутки если больше
	$ret='';
	if($timestamp>=24*60*60){ //больше суток
		$days=floor($timestamp/(24*60*60));
		$timestamp-=$days*24*60*60;
	}
	$hours=_append_zero(floor($timestamp/(60*60)));
	$timestamp=$timestamp%(60*60);
	$ret.="$hours:";
	$min=_append_zero(floor($timestamp/60));
	$timestamp=$timestamp%60;
	$ret.="$min";
	
	return $ret;
}

function format_big_time($timestamp){
	$ret='';
	if($timestamp>24*60*60){ //больше суток
		$days=floor($timestamp/(24*60*60));
		$timestamp=$timestamp%(24*60*60);
		$ret=$days.'с. ';
	}
	$hours=_append_zero(floor($timestamp/(60*60)));
	$timestamp=$timestamp%(60*60);
	$ret.="$hours:";
	$min=_append_zero(floor($timestamp/60));
	$timestamp=$timestamp%60;
	$ret.="$min";
	return $ret;
}

function parse_user_time($time){
	if(!strlen($time))
		die('не указано время!');	
	if(!preg_match("/^\d+\:\d+(|\:\d+)$/",$time))
		die('время указано в неправильном формате!');
	$pt=array(); //parsed time
	preg_match("/^(\d+)\:(\d+)(|\:\d+)$/",$time,$pt);
	$h=(int)$pt[1]; $m=(int)$pt[2]; $s=(int)trim($pt[3],':');
	if($h<0 or $h>24)
		die("Нерпавильно указаны часы ($h)!");
	if($m<0 or $m>60)
		die("Неправилььно указаны минты ($m)!");
	if($s<0 or $s>60)
		die("Неправильно указаны секунды ($s)!");
	return array($h,$m,$s);
}
function _append_zero($data){
	if(!$data)
		return '00';
	if($data<10)
		return "0$data";
	return $data;
}
?>
