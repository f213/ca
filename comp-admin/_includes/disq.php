<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//Функции для работы с дисквалификацией. Типы снятия указаны ниже
//типа current снимает участника с текущего СУ
//тип next снимает участника с текущего СУ и всех су, id которых больше текушего
//тип full снимает участника со всех СУ (и предыдущих), в таблице comp_id для него должен быть всегда 0
//Для определения, дисквалифицирован ли участник на текущую гонку следует использовать disq_type, ни в коем случе не has_disq(), которая является служебной.
$disq_types=array(
	'current'=>'Снятие с текущего СУ',
	'next'=>'Снитие со всех последующих СУ(включительно)',
	'full'=>'Снятие с гонки с анулированием результатов',
);

function has_disq($start_number){ //есть ли участника вообще хоть какая-нибудь дисквалификация.
	global $compdisq_dbt;
	if(!$start_number)
		return null;
	$res=query_eval("SELECT type FROM $compdisq_dbt WHERE start_number=$start_number;");
	if(mysql_num_rows($res))
		return true;
	return false;
}
/*
 * disq_type() - узнать тип дисквалификации, и вообще снят ли участник с СУ $comp_id или нет. Возвращает тип дисквалификации по массиву disq_types сверху вниз, то есть если есть диск на текущее СУ, то не смотрит, есть ли диск на последующие или на вс. гонку.
 * Возвращает список из двух элементов - тип дисквалификации и комментарий
 * 
 * $disq_data=disq_type($comp_id,$start_number);
 * if($disq_data[0]){
 * 	print "Дисквалифицирован, тип: ".$disq_types[$disq_data[0]].", причина: ".$disq_data[1];
 *
 */
function disq_type($comp_id,$start_number){ //тип дисквалификации
	global $compdisq_dbt;
	if(!has_disq($start_number))
		return array(null,null);
	if(!$comp_id)
		$comp_id=0;
	//сначала смотрим дисквалификацию на текущую гонку
	$res=query_eval("SELECT comment FROM $compdisq_dbt WHERE start_number=$start_number AND type='current' AND comp_id=$comp_id;");
	if(mysql_num_rows($res)){
		$row=mysql_fetch_row($res);
		return array('current',stripslashes($row[0]));
	}
	//потом смотрим, были ли дисквалификации до конца гонки на предыдущих СУ
	$res=query_eval("SELECT comment FROM $compdisq_dbt WHERE start_number=$start_number AND type='next' AND comp_id<=$comp_id;");
	if(mysql_num_rows($res)){
		$row=mysql_fetch_row($res);
		return array('next',stripslashes($row[0]));
	}
	//и теперь проверяем полную дисквалификацию
	$res=query_eval("SELECT comment FROM $compdisq_dbt WHERE start_number=$start_number AND type='full';");
	if(mysql_num_rows($res)){
		$row=mysql_fetch_row($res);
		return array('full',stripslashes($row[0]));
	}
	return array(null,null); //значит на текущую гонку участник не снят
}


function remove_disq($comp_id,$start_number){ //удалить дисквалификацию, тип берется из disq_type()
	global $compdisq_dbt;
	if(!$start_number)
		return null;
	if(!$comp_id)
		$comp_id=0;

	list($type,$junk)=disq_type($comp_id,$start_number);

	query_eval("DELETE FROM $compdisq_dbt WHERE start_number=$start_number AND type='$type';");
	return true;
}
/*
 * disq() - дисквалифицировать участника.
 * Для дисквалификации на все СУ гонки comp_id должен быть 0
*/
function disq($comp_id,$start_number,$type,$comment='',$author=''){ //дисквалифицировать
	global $disq_types,$compdisq_dbt,$admin_user;
	if(!$start_number)
		return null;
	if(!strlen($disq_types[$type]))
		return null;
	if(!$comp_id or $type=='full') //для дисквалификации на ВСЕ-ВСЕ су comp_id в таблице должно быть 0 
		$comp_id=0;
	//проверяем, нет ли дисквалификаций
	//сначала смотрим, не снят ли с гонки
	$res=query_eval("SELECT * FROM $compdisq_dbt WHERE start_number='$start_number' AND type='full';");
	if(mysql_num_rows($res))
		die("Дисквалификация: участник ($start_number) уже снят со всей гонки");

	//теперь смотрим не снят ли он на текущий СУ
	$res=query_eval("SELECT * FROM $compdisq_dbt WHERE start_number='$start_number' AND type='current' AND comp_id=$comp_id;");
	if(mysql_num_rows($res))
		die("Дисквалификация: участник ($start_number) уже снят с этого су($comp_id)");
	
	//и не был ли он снят на предыдущих гонках
	$res=query_eval("SELECT * FROM $compdisq_dbt WHERE start_number='$start_number' AND type='next' AND comp_id<=$comp_id;");
	if(mysql_num_rows($res))
		die("Дисквалификация: участник ($start_number) уже снят с этого  су($comp_id), возможно на предыдущих");
	
	//если автор не указан, будет по умолчанию
	if(!strlen($author))
		$author=$admin_user;
	$data=array(
		'comp_id'=>$comp_id,
		'start_number'=>$start_number,
		'type'=>$type,
		'comment'=>addslashes($comment),
		'author'=>addslashes($author),
	);
	add_item($compdisq_dbt,$data);
	return true;
	
}
?>
