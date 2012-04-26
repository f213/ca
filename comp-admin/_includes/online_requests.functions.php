<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

function or_set_payd($compreq_dbt,$item_id,$author,$flag='yes'){ //сделать заявку оплаченной, $flag может быть 'yes'  или 'no'
	if($flag!='yes' and $flag!='no')
		return false;
	$res=query_eval("SELECT * FROM $compreq_dbt WHERE id='$item_id';");
	if(!mysql_num_rows($res)) //ежели указан несуществующий номер заявки
		return false;
	$sql="UPDATE $compreq_dbt SET `payd`='$flag', `payed_author`='$author' WHERE id=$item_id LIMIT 1;";
	query_eval($sql);
	return true;
}	
function or_set_approved($compreq_dbt,$item_id,$author,$flag){ //сделать подтвержденной
	if($flag!='yes' and $flag!='no')
		return false;
	$res=query_eval("SELECT * FROM $compreq_dbt WHERE id='$item_id';");
	if(!mysql_num_rows($res)) //ежели указан несуществующий номер заявки
		return false;
	$sql="UPDATE $compreq_dbt SET `approved`='$flag', `ApprovedBy`='$author' WHERE id=$item_id LIMIT 1;";
	query_eval($sql);
	return true;
}	
function or_set_registered($compreq_dbt,$item_id,$author,$flag){ //сделать зарегистрированной
	if($flag!='yes' and $flag!='no')
		return false;
	$res=query_eval("SELECT * FROM $compreq_dbt WHERE id='$item_id';");
	if(!mysql_num_rows($res)) //ежели указан несуществующий номер заявки
		return false;
	$sql="UPDATE $compreq_dbt SET `registered`='$flag', `registered_author`='$author' WHERE id=$item_id LIMIT 1;";
	query_eval($sql);
	if($flag=='yes' and defined('ADM_TRACK_EDITS') and ADM_TRACK_EDITS)
		cancel_tracked_edit($item_id);

	return true;
}	
function track_edit($item_id,$author){ //сделать запись о редактировании
	global $compedit_dbt;
	$item_id=addslashes($item_id);
	$author=addslashes($author);
	query_eval("REPLACE INTO $compedit_dbt SET request_id='$item_id', author='$author';");

}
function cancel_tracked_edit($item_id){ //отменить запись о редактировании
	global $compedit_dbt;
	$sql="DELETE FROM $compedit_dbt WHERE request_id='$item_id' LIMIT 1;";
	query_eval($sql);
}
function is_edited($item_id){ //редактирована ли
	global $compedit_dbt;
	$sql="SELECT * FROM $compedit_dbt WHERE request_id='$item_id' AND `date`>NOW()-172800 LIMIT 1;"; #ежели запись редактировалась меньше чем 2 дня назад
	$res=query_eval($sql);
	if(mysql_num_rows($res))
		return true;
	return false;
}	

function next_start_number($comp_id){
	global $compreq_dbt,$compbadnum_dbt,$compres_dbt;
	$comp_id=(int)$comp_id;
	if(!$comp_id)
		return null;

	//получение списка занятых по желанию борт номеров
	$used_numbers=array();
	$used_numbers_str='';
	$res=query_eval("SELECT DISTINCT request_cabine_number FROM $compreq_dbt WHERE `request_cabine_number`!='0' AND comp_id=$comp_id;");
	while($row=mysql_fetch_row($res)){
		$used_numbers[]=(int)$row[0];
		$used_numbers_str.="'".(int)$row[0]."',";
	}
	$used_numbers_str=trim($used_numbers_str,',');

	//получение списка "забракованных" борт. номеров. Забраковка действует только на авотматически выдаваемые номера, вручную их назначить все равно можно.
	$blocked_numbers=array();
	$blocked_numbers_str="";
	$res=query_eval("SELECT start_number FROM $compbadnum_dbt WHERE comp_id=$comp_id ORDER BY start_number ASC;");
	while($row=mysql_fetch_row($res))
		$blocked_numbers[]=(int)$row[0];
	//получение следущего стартового номера
	if(!defined('FANCY_NEXT_START_NUMBER') or !FANCY_NEXT_START_NUMBER){
		//"старый" алгоритм - берет максимальный выданый номер и прибаляет 1, если он не зареген и не заблокирован
		if($used_numbers or $blocked_numbers){
			$res=query_eval("SELECT MAX(start_number) FROM $compres_dbt WHERE `comp_id`=$comp_id AND `start_number` NOT IN ($used_numbers_str)");
			$row=mysql_fetch_row($res);
			$next_start_number=(int)$row[0];
			while(1){
				$next_start_number++;
				if(in_array($next_start_number,$used_numbers) or in_array($next_start_number,$blocked_numbers))
					continue;
				break;
			}
		}else
			$next_start_number='1';
	}else{
		//новый алгоритм - по одному проходим все номера начиная с первого и выбираем минимально возможный.
		if($used_numbers or $blocked_numbers){
			//получаем список уже выданных номеров, т.е. зарегеных участников
			$given_numbers=array();
			$res=query_eval("SELECT DISTINCT(start_number) FROM $compres_dbt WHERE `comp_id`=$comp_id  ORDER BY start_number ASC;");
			while($row=mysql_fetch_row($res))
				$given_numbers[]=(int)$row[0];

			$next_start_number=0;
			while(1){
				$next_start_number++;

				if(sizeof($used_numbers))
					if(in_array($next_start_number,$used_numbers))
						continue;
				if(sizeof($blocked_numbers))
					if(in_array($next_start_number,$blocked_numbers))
						continue;
				if(sizeof($given_numbers))
					if(in_array($next_start_number,$given_numbers))
						continue;

				break;
			}

	
		}else
			$next_start_number='1';
	}
	return $next_start_number;
}	
function remove_request($item_id){ //удалить заявку
	global $compreq_dbt;
	if(!$item_id)
		die('указан неверный id заявки!');
	$res=query_eval("SELECT * FROM $compreq_dbt WHERE id='$item_id';");
	if(!mysql_num_rows($res))
		die('указан несуществующий id заявки!');
	$sql="DELETE FROM $compreq_dbt WHERE id='$item_id' LIMIT 1;";
	query_eval($sql);
	return 1;
}	
function or_register($comp_id,$item_id){
	global $compreq_dbt, $compres_dbt;
	global $compedit_dbt;
	global $admin_user;
	$comp_id=(int)$comp_id; $item_id=(int)$item_id;
	if(!$comp_id or !$item_id)
		return array('err'=>'Не указан параметр $comp_id или $item_id');
	//проверим, а не зареген ли номер
	$res=query_eval("SELECT * FROM $compreq_dbt WHERE id=$item_id AND `registered`='yes';");
	if(mysql_num_rows($res))
		return array('err'=>"Заявка с id($item_id) уже зарегистрирована");
	//смотрим, был ли бронированный номер
	$res=query_eval("SELECT request_cabine_number,category FROM $compreq_dbt WHERE id=$item_id;");
	$row=mysql_fetch_row($res);
	$cat_id=(int)$row[1]; 
	if((int)$row[0])//номер если есть - юзаем его
		$start_number=(int)$row[0];
	else
		$start_number=next_start_number($comp_id);
	//на всякий случай проверим, вдруг уже зареген такой номер (не должно такого быть, пожже сверху еще проверку напишу
	$res=query_eval("SELECT * FROM $compres_dbt WHERE `comp_id`=$comp_id AND `start_number`=$start_number;");
	if(mysql_num_rows($res))
		return array('err'=>'Номер уже занят:( Повторите попытку');

	query_eval("LOCK TABLES $compres_dbt WRITE, $compreq_dbt WRITE, $compedit_dbt WRITE");
	if(!or_set_registered($compreq_dbt,$item_id,$admin_user,'yes'))
		return array('err'=>'Ошибка постановки флага "registered"');
	$start_data=array(
		'comp_id'=>$comp_id,
		'cat_id'=>$cat_id,
		'request_id'=>$item_id,
		'start_number'=>$start_number,
	);
	add_item($compres_dbt,$start_data);
	query_eval("UNLOCK TABLES;");
	return array('start_number'=>$start_number);
}
function or_deregister($comp_id,$item_id){
	global $compreq_dbt, $compres_dbt;
	global $compedit_dbt;
	global $admin_user;
	$comp_id=(int)$comp_id; $item_id=(int)$item_id;
	if(!$comp_id or !$item_id)
		return array('err'=>'Не указан параметр $comp_id или $item_id');
	//проверим, а не зареген ли номер
	$res=query_eval("SELECT * FROM $compreq_dbt WHERE id=$item_id AND `registered`='yes';");
	if(!mysql_num_rows($res))
		return array('err'=>"Заявка с id($item_id) не зарегистрирована");

	query_eval("LOCK TABLES $compres_dbt WRITE, $compreq_dbt WRITE, $compedit_dbt WRITE");
	if(!or_set_registered($compreq_dbt,$item_id,$admin_user,'no'))
		return array('err'=>'Ошибка снятия флага "registered"');
	query_eval("DELETE FROM $compres_dbt WHERE `request_id` = $item_id LIMIT 1;");
	query_eval("UNLOCK TABLES;");
	return array('result'=>'ok');
}

function get_request_children($comp_id,$request_id){
	global $compreq_dbt;

	$res=query_eval("SELECT id FROM $compreq_dbt WHERE comp_id=$comp_id AND parent_id=$request_id AND category != (SELECT category FROM $compreq_dbt WHERE id=$request_id);");
	if(!mysql_num_rows($res))
		return array();
	$ret=array();
	while($row=mysql_fetch_row($res))
		$ret[(int)$row[0]]=get_brief_request_data($comp_id,(int)$row[0]);
	foreach($ret as $key=>$value)
		if(req2num($comp_id,$key))
			$ret[$key]['start_number']=req2num($comp_id,$key);
	return $ret;
}
