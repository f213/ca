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

require_once('_includes/online_requests.functions.php'); //функции оплаты и подтверждения заявок. Они используются еще в скрипте добавления заявки, возможно потом будет где-нибудь еще 
require_once('_includes/request_functions.php');
require_once('_includes/copy_update_table.php');
require_once('_includes/disq.php');
require_once('_includes/change_cat_id.php');
require_once('_includes/xmlreq.php');
$approve_types=array(
	0 => 'Все',
	1 => 'Подтвержденные',
	2 => 'Неподтвержденные',
);	
$pay_types=array(
	0 => 'Все',
	1 => 'Оплаченные',
	2 => 'Неоплаченные',
);	
$register_types=array(
	'0'=> 'Все',
	'1'=> 'Зарегистрированные',
	'2'=> 'Незарегистрированные',
);

$comp_id=(int)_input_val('comp_id');
if(!$comp_id)
	die('Не указан id соревнования');



//flags
$flag=_input_val('flag');
if($flag){//тута обработка фильтров
	$filters_str="comp_id=$comp_id";
	if(_input_val('f_category'))
		$filters_str.="&f_category="._input_val('f_category');
	if(_input_val('f_approved'))
		$filters_str.="&f_approved="._input_val('f_approved');
	if(_input_val('f_payed'))
		$filters_str.="&f_payed="._input_val('f_payed');
	if(_input_val('f_registered'))
		$filters_str.="&f_registered="._input_val('f_registered');
}
$add_url=append_rnd("online_requests_add.php?comp_id=$comp_id&$filters_str");
$back_url="competitions.php";

//получение списка "забракованных" борт. номеров. Забраковка действует только на авотматически выдаваемые номера, вручную их назначить все равно можно.
$bad_numbers=array();
$res=query_eval("SELECT start_number FROM $compbadnum_dbt WHERE comp_id=$comp_id ORDER BY start_number ASC;");
while($row=mysql_fetch_row($res))
	$bad_numbers[]=(int)$row[0];

$next_start_number=next_start_number($comp_id);

switch($flag){
case 1: //оплата заявки (pay)
	$item_id=(int)$_GET['item_id'];
	if(!$item_id)
		die('указан неверный id заявки!');
	if(!or_set_payd($compreq_dbt, $item_id,$admin_user,'yes'))
		die('Произошла ошибка при оплате заявки:(');
	header("Location: ".append_rnd("online_requests.php?$filters_str&just_edited=".$item_id));
	die();
	break;

case 2: //подтверждение заявки (approve)
	$item_id=(int)$_GET['item_id'];
	if(!$item_id)
		die('указан неверный id заявки!');
	if(!or_set_approved($compreq_dbt,$item_id,$admin_user,'yes'))
		die('Произошла ошибка при подтверждении заявки');
	header("Location: ".append_rnd("online_requests.php?$filters_str&just_edited=".$item_id));
	die();
	break;
case 3: //удаление заявки	
	$item_id=(int)$_GET['item_id'];
	if(!$item_id)
		die('указан неверный id заявки!');
	remove_request($item_id);
	header("Location: ".append_rnd("online_requests.php?$filters_str&just_edited=".$item_id));
	die();
	break;
case 4: //регистрация в лагере
	$item_id=(int)$_GET['item_id'];
	or_register($comp_id,$item_id);
	header("Location: ".append_rnd("online_requests.php?$filters_str&just_edited=".$item_id));
	die();
	break;
case 5: //отмена регистрации в лагере
	$item_id=(int)$_GET['item_id'];
	or_deregister($comp_id,$item_id);
	header("Location: ".append_rnd("online_requests.php?$filters_str&just_edited=".$item_id));
	die();
	break;
case 6: //убрать подсветку о том, что запись редактировалась
	$item_id=(int)$_GET['item_id'];
	if(!$item_id)
		die('указан неверный id заявки!');
	cancel_tracked_edit($item_id);
	header("Location: ".append_rnd("online_requests.php?$filters_str&just_edited=".$item_id));
	die();
	break;
case 7: //забраковать номер
	$bad_num=(int)$_GET['bad_num'];
	if(!$bad_num)
		die('неправильно указан номер!');
	if(in_array($bad_num,$bad_numbers))
		die('номер уже забракован!');
	$data=array(
		'comp_id'=>$comp_id,
		'start_number'=>$bad_num,
	);
	add_item($compbadnum_dbt,$data);
	header("Location: ".append_rnd("online_requests.php?$filters_str&bad_number_edited=1"));
	break;
case 8: //разбраковать номер
	$del_num=(int)$_GET['del_num'];
	if(!$del_num)
		die('неправильно указан номер!');
	if(!in_array($del_num,$bad_numbers))
		die('указаный номер не забракован!');
	query_eval("DELETE FROM $compbadnum_dbt WHERE comp_id=$comp_id and start_number='$del_num' LIMIT 1;");
	header("Location: ".append_rnd("online_requests.php?$filters_str&bad_number_edited=1"));
	break;
case 9: //копировать список участников
	//$comp_id здесь id нового соревнования, В КОТОРОЕ копируем
	$from_comp_id=(int)$_GET['from_comp_id'];
	if(!$from_comp_id)
		die('Неправильно указан from_comp_id!');
	$res=query_eval("SELECT Name FROM $comp_dbt WHERE ID=$from_comp_id;");
	if(!mysql_num_rows($res))
		die('Неправильно указан from_comp_id(db_check)!');
	//для копирования списка зарегистрированных участнегов надо скопировать две таблицы, $compres_dbt и $compreq_dbt
	$oldreq=copy_update_table($compreq_dbt,"WHERE comp_id=$from_comp_id","SET comp_id=$comp_id");
	copy_update_table($compres_dbt,"WHERE comp_id=$from_comp_id","SET comp_id=$comp_id");
	//а в таблице $compres_dbt надо еще поменять соответсвия, т.к. меняются id
	foreach($oldreq as $old_id=>$new_id){
		query_eval("UPDATE $compres_dbt SET request_id=$new_id WHERE request_id=$old_id AND comp_id=$comp_id;");
	}
	header("Location: ".append_rnd("online_requests.php?$filters_str&requests_copied=1"));
	break;

case 10: //удалить лишних
	query_eval("DELETE FROM $compreq_dbt WHERE comp_id=$comp_id AND approved='no';");
	query_eval("DELETE FROM $compreq_dbt WHERE comp_id=$comp_id AND registered='no';");
	header("Location: ".append_rnd("online_requests.php?$filters_str&unnec_removed=1"));
	break;

case 11: //очистить бронь у незарегистрированных участников
	query_eval("UPDATE $compreq_dbt SET request_cabine_number=0 WHERE comp_id=$comp_id AND registered='no';");
	header("Location: ".append_rnd("online_requests.php?$filters_str&unreged_request_cabine_numbers_removed=1"));
	break;
case 12: //экспорт списка участникаов
	export_requests_xml($comp_id,array(1,2,3,4));
	die();
	break;
case 13: //импорт списка участников
	if(!is_uploaded_file($_FILES['import_file']['tmp_name']))
		die('Не загружен файл!');
	$cnt=import_requests_xml($comp_id,$_FILES['import_file']['tmp_name']);
	header("Location: ".append_rnd("online_requests.php?$filters_str&imported_count=$cnt"));
	
	die();
	break;


}	
//end flags

//filters
$filters_sql="AND 1 ";
$filters_str="filters=1";
if($_GET['f_category'] and (int)$_GET['f_category']<=_CATEGORIES and (int)$_GET['f_category']>=1){
	$f_category=(int)$_GET['f_category'];
	$filters_sql.=" AND `category`=$f_category ";
	$filters_str.="&f_category=$f_category";
}	
if($_GET['f_approved'] and strlen($approve_types[(int)$_GET['f_approved']])){
	$f_approved=(int)$_GET['f_approved'];
	switch($f_approved){
	case 1:
		$filters_sql.=" AND `approved`='yes'";
	break;
	case 2:
		$filters_sql.=" AND `approved`='no'";
	break;
	}
	$filters_str.="&f_approved=$f_approved";
}	
if($_GET['f_payed'] and strlen($pay_types[(int)$_GET['f_payed']])){
	$f_payed=(int)$_GET['f_payed'];
	switch($f_payed){
	case 1:
		$filters_sql.=" AND `payd`='yes'";
	break;
	case 2:
		$filters_sql.=" AND `payd`='no'";
	break;
	}
	$filters_str.="&f_payed=$f_payed";
}	
if($_GET['f_registered'] and strlen($register_types[(int)$_GET['f_registered']])){
	$f_registered=(int)$_GET['f_registered'];
	switch($f_registered){
	case 1:
		$filters_sql.=" AND `registered`='yes'";
		break;
	case 2:
		$filters_sql.=" AND `registered`='no'";
		break;
	}
	$filters_str.="&f_registered=$f_registered";
}	
//end filters
$just_edited=0;
if($_GET['just_edited'])
	$just_edited=(int)$_GET['just_edited'];

$res=query_eval("SELECT Name FROM $comp_dbt WHERE ID=$comp_id;");
if(!mysql_num_rows($res))
	die('Указан некорректный id соревнования');
$row=mysql_fetch_row($res);
$comp_name=stripslashes($row[0]);

//кол-во зарегистрированных участнегов
$reg_cnt=array();
for($i=1;$i<=_CATEGORIES;$i++){
	$res=query_eval("SELECT COUNT(id) FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id=$i;");
	if(mysql_num_rows($res)){
		$row=mysql_fetch_row($res);
		$reg_cnt[$i]=(int)$row[0];
	}
}


$title="Управление заявками $comp_name";

$unnec_count=0; //количество "ненужных" записей, то есть записей, неактуальных на момент начала гонки

$res=query_eval("SELECT * FROM $compreq_dbt WHERE comp_id=$comp_id $filters_sql ORDER BY RegisterDate ASC;");
while($row=mysql_fetch_assoc($res)){
	$id=$row['id'];
	$item_output[$id]['category']=(int)$row['category'];
	$item_output[$id]['category_name']=$cat_name[(int)$row['category']];
	if($row['payd']=='yes'){
		$item_output[$id]['payd']=true;
		$item_output[$id]['payd_author']=stripslashes($row['payed_author']);
	}	
	else{
		$item_output[$id]['payd']=false;
		$item_output[$id]['pay_link']=append_rnd("online_requests.php?comp_id=$comp_id&flag=1&item_id=$id&$filters_str");
	}
	$item_output[$id]['crew']=get_crew($comp_id,$id);
	$item_output[$id]['auto_brand']=stripslashes($row['AutoBrand']);
	$item_output[$id]['wheel_size']=(int)$row['WheelSize'];
	$item_output[$id]['register_date']=date('d.m.Y',(int)$row['RegisterDate']);
	//номер
	if((int)$row['request_cabine_number'])
		$item_output[$id]['cabine_number']=(int)$row['request_cabine_number'];
	else
		$item_output[$id]['cabine_number']='-';
	if($row['approved']=='yes'){
		$item_output[$id]['approved']=true;
		$item_output[$id]['approved_author']=stripslashes($row['ApprovedBy']);
	}else{	
		$item_output[$id]['approved']=false;
		$item_output[$id]['approve_link']=append_rnd("online_requests.php?comp_id=$comp_id&flag=2&item_id=$id&$filters_str");
		$unnec_count++;
	}
	$item_output[$id]['can_do_something']=false;
		if(category_accepting_changes($comp_id,$item_output[$id]['category'])) //регистрировать в категории, в которые нельзя (уже есть стартовая ведомость) нам может разрешить только Высший разум.
			$item_output[$id]['can_do_something']=true; 
	if($row['registered']=='yes'){
		$item_output[$id]['registered']=true;
		$item_output[$id]['registered_author']=stripslashes($row['registered_author']);
		$item_output[$id]['deregister_link']=append_rnd("online_requests.php?comp_id=$comp_id&flag=5&item_id=$id&$filters_str");
		$numres=query_eval("SELECT start_number FROM $compres_dbt WHERE `comp_id` = $comp_id AND `request_id` = $id;");
		$numrow=mysql_fetch_row($numres);
		$item_output[$id]['start_number']=$start_number=(int)$numrow[0];
		//проверка на снятие с соревнования
		if($start_number and has_disq($start_number)){
			$disq_data=disq_type($comp_id,$start_number);
			if($disq_data[0])
				$item_output[$id]['disq']=$disq_type=$disq_data[0];
		}

	}else{
		$item_output[$id]['registered']=false;
		$item_output[$id]['register_link']=append_rnd("online_requests.php?comp_id=$comp_id&flag=4&item_id=$id&$filters_str");
		$item_output[$id]['start_number']='-';
		$unnec_count++;
		
	}

	$item_output[$id]['from']=$from_array[stripslashes($row['source'])];
	$item_output[$id]['print_url']=append_rnd("print-request.php?comp_id=$comp_id&request_id=$id");
	$item_output[$id]['edit_link']=append_rnd("online_requests_add.php?comp_id=$comp_id&item_id=$id&$filters_str");
	$item_output[$id]['delete_link']=append_rnd("online_requests.php?comp_id=$comp_id&item_id=$id&flag=3&$filters_str");
	$item_output[$id]['un_hl_link']=append_rnd("online_requests.php?comp_id=$comp_id&item_id=$id&flag=6$filters_str");
	
	//обработка записей о редактировании
	if(defined('ADM_TRACK_EDITS') and ADM_TRACK_EDITS){
		$item_output[$id]['is_edited']=is_edited($id);
	}

}		
if(sizeof($item_output)){ //добавим нолики к стартовым номерам для поиска
	foreach($item_output as $key=>$value){
		$zero_sn='(';
		if($value['start_number']<10)
			$zero_sn.="00{$value['start_number']},";
		if($value['start_number']<100)
			$zero_sn.="0{$value['start_number']}";
		if(strlen($zero_sn)>1){
			$zero_sn.=')';
			$item_output[$key]['start_number_with_zeros']=$zero_sn;
		}
	}

}
//узнаем количество незарегистрированных участников, забронировавших номера
$res=query_eval("SELECT COUNT(*) FROM $compreq_dbt WHERE comp_id=$comp_id AND registered='no' AND request_cabine_number != '0';");
if(mysql_num_rows($res)){
	$row=mysql_fetch_row($res);
	$tpl_unregistered_with_request_cabine_numbers=(int)$row[0];
}
if(!sizeof($item_output)){ //если никого нет - формируем список соревнований для копирования
	$res=query_eval("SELECT ID,Name FROM $comp_dbt WHERE ID!=$comp_id;");
	if(mysql_num_rows($res)){
		while($row=mysql_fetch_row($res))
			$item_comp_list[$row[0]]=stripslashes($row[1]);
	}
}
if($f_category)
	$tpl_tkproto_link=append_rnd("print_tk.php?comp_id=$comp_id&cat_id=$f_category");

require('admin_header.php');
require('_templates/online_requests.phtml');
