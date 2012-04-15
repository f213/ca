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


require_once('_includes/online_requests.functions.php');
require_once('_includes/started_functions.php');
require_once('_includes/disq.php');
require_once('_includes/change_cat_id.php');

	
$sizes=array('M','S','L','XL','XXL');
if(empty($_GET['comp_id']) and empty($_POST['comp_id']))
	die('Не указан id соревнования!');

$comp_id=(int)_input_val('comp_id');
$item_id=(int)_input_val('item_id');

//обработка фильтров, чтобы назад возвращать
if(empty($_POST['filters_str'])){
	$filters_str="filters=1";
	if($_GET['f_category'])
		$filters_str.="&f_category={$_GET['f_category']}";
	if($_GET['f_approved'])
		$filters_str.="&f_approved={$_GET['f_approved']}";
	if($_GET['f_payed'])
		$filters_str.="&f_payed={$_GET['f_payed']}";
}else
	$filters_str=stripslashes($_POST['filters_str']);

$sizes=array('M','S','L','XL','XXL');

$form_submit_url="online_requests_add.php";
$form_return_url="online_requests.php?comp_id=$comp_id&$filters_str";

//получаем список забитых бортномеров
$used_numbers=array();
$res=query_eval("SELECT request_cabine_number FROM $compreq_dbt WHERE comp_id='$comp_id';");
while($row=mysql_fetch_array($res))
	if((int)$row[0])
		$used_numbers[]=(int)$row[0];
//к списку добавляем уже зарегистрированные номера
$res=query_eval("SELECT DISTINCT(start_number) FROM $compres_dbt WHERE comp_id='$comp_id';");
while($row=mysql_fetch_row($res))
	if((int)$row[0] and !in_array((int)$row[0],$used_numbers))
		$used_numbers[]=(int)$row[0];
//получаем список забракованных номеров (используется только в шаблоне)
$bad_numbers=array();
$res=query_eval("SELECT start_number FROM $compbadnum_dbt WHERE comp_id=$comp_id ORDER BY start_number ASC;");
while($row=mysql_fetch_row($res))
	$bad_numbers[]=(int)$row[0];

//получаем список стартовавших категорий
$tpl_started_categories=$started_categories=get_started_categories($comp_id);
		
//-----------------

$flag=(int)_input_val('flag');

switch($flag){
case 1: //оплата заявки
	if(!$item_id)
		die('Не указан id заявки');
	if(!or_set_payd($compreq_dbt, $item_id,$admin_user,'yes'))
		die('Произошла ошибка при оплате заявки:(');
	header("Location: online_requests_add.php?comp_id=$comp_id&item_id=$item_id&$filters_str");
	die();
	break;
case 2: //подтверждение заявки
	if(!$item_id)
		die('Не указан id заявки');
	if(!or_set_approved($compreq_dbt, $item_id,$admin_user,'yes'))
		die('Произошла ошибка при подтверждении заявки:(');
	header("Location: online_requests_add.php?comp_id=$comp_id&item_id=$item_id&$filters_str");
	die();
	break;

case 3: //добавление\редактирование
	$err="";
	$category=(int)$_POST['category'];
	//проверка валидности ниже, потому что сначала нужно разрулить, есть ли запрос на перегенерацию стратовой ведомости
	$pilot_name=addslashes(trim($_POST['pilot_name']));
	if(!strlen($pilot_name))
		$err.="<br>Не указано имя пилота!";
	$pilot_nik=addslashes(trim($_POST['pilot_nik']));
	$pilot_phone=addslashes(trim($_POST['pilot_phone']));
	$pilot_city=addslashes(trim($_POST['pilot_city']));
	if(defined('USE_SIZE') and USE_SIZE){
		$pilot_size=addslashes(trim($_POST['pilot_size']));
		if(!strlen($pilot_size))
			$err.="<br>Указан некорректный размер одежды пилота!";
	}else
		$pilot_size='';	
	if($_people_names['shturman']['ca'] and strlen($_people_names['shturman']['ca'])){
		$navigator_name=addslashes(trim($_POST['navigator_name']));
		if(!strlen($navigator_name))
			$err.="<br>Не указано имя штурмана!";
		$navigator_nik=addslashes(trim($_POST['navigator_nik']));
		$navigator_phone=addslashes(trim($_POST['navigator_phone']));
		$navigator_city=addslashes(trim($_POST['navigator_city']));
		if(defined('USE_SIZE') and USE_SIZE){
			$navigator_size=addslashes(trim($_POST['navigator_size']));
			if(!strlen($navigator_size) or !in_array($navigator_size,$sizes))
				$err.="<br>Указан некорректный размер одежды штурмана!";
		}else
			$navigator_size='';	
	}
	$auto_brand=addslashes(trim($_POST['auto_brand']));
	if(!strlen($auto_brand))
		$err.="<br>Некорректно указано название автомобиля!";
	if(defined('CA_WHEEL_SIZE') and CA_WHEEL_SIZE){
		$wheel_size=(int)$_POST['wheel_size'];
		if(!$wheel_size and defined('CA_REQUIRE_WHEEL_SIZE') and CA_REQUIRE_WHEEL_SIZE)
			$err.="<br>Некорректно указан размер колес!";
	}	
	$auto_number=addslashes(trim($_POST['auto_number']));
	if(!strlen($auto_number))
		$err.="<br>Некорректно указан гос. номер!";
	$city=addslashes(trim($_POST['city']));
	$email=addslashes(trim($_POST['email']));
	$phone=addslashes(trim($_POST['phone']));
	if(!strlen($phone))
		$err.="<br>Некорректно указан номер телефона!";
	$club=addslashes(trim($_POST['club']));
	$passangers=(int)$_POST['passangers'];
	$ext_attr_enabled='no';
	if(isset($_POST['ext_attr']))
		$ext_attr_enabled='yes';
	$data=array(
		'comp_id'=>CURRENT_COMP,
		'PilotName'=>$pilot_name,
		'PilotNik'=>$pilot_nik,
		'PilotSize'=>$pilot_size,
		'PilotPhone'=>$pilot_phone,
		'PilotCity'=>$pilot_city,
		'AutoBrand'=>$auto_brand,
		'AutoNumber'=>$auto_number,
		'phone'=>$phone,
		'email'=>$email,
		'city'=>$city,
		'club'=>$club,
		'ip'=>$_SERVER['REMOTE_ADDR'],
		'ext_attr_enabled'=>$ext_attr_enabled,
	);
	if($_people_names['shturman']['ca'] and strlen($_people_names['shturman']['ca'])){
		$data['NavigatorName']=$navigator_name;
		$data['NavigatorNik']=$navigator_nik;
		$data['NavigatorSize']=$navigator_size;
		$data['NavigatorPhone']=$navigator_phone;
		$data['NavigatorCity']=$navigator_city;
	}
	if(!$item_id) //если добавляем новую заяфку
		$data['RegisterDate']=time();
	$start_number=req2num($comp_id,$item_id);
	if(!$start_number) //категорию можно задавать только при первичной регистрации
		if(!$category or !$cat_name[$category])
			die('Не указана категория!');
		else
			$data['category']=$category;

	if(defined('CA_WHEEL_SIZE') and CA_WHEEL_SIZE) //размер колес обновляем только, если указано в опциях
		$data['WheelSize']=$wheel_size;

	$rewrite_sl=0;


	if(!(int)$item_id){ //если заявка забивается как новая, тогда забиваем новый источник и афтара заявки, а так же делаем заявку сразу подтвержденной
		$data['source']='admin';
		$data['approved']='yes';
		$data['author']=$data['ApprovedBy']=$admin_user;
	}
	if((int)$_POST['cabine_number'] and !in_array((int)$_POST['cabine_number'],$used_numbers)) //добавляем борт. номер только если он не забит
		$data['request_cabine_number']=(int)$_POST['cabine_number'];
	$data['comp_id']=$comp_id;
	if(!strlen($err)){
		$item_id=add_item($compreq_dbt,$data,$item_id);
		//добавили данные, теперь прогоняем по доп атрибутам
		foreach(array('pilot','shturman') as $p)
			foreach($_people_names[$p]['ext_attr'] as $attr)
				if(isset($_POST[$p.'_'.$attr]) and _ext_attr_enabled($p.'_'.$attr))
					_ext_attr($comp_id,$item_id,$p.'_'.$attr,$_POST[$p.'_'.$attr]);

		if($_POST['back']=='add' && $item_id)
			header("Location: online_requests_add.php?comp_id=$comp_id&item_id=$item_id&$filters_str");
		else
			header("Location: online_requests.php?comp_id=$comp_id&just_edited=$item_id&$filters_str");
		die();
	}
	die($err);
	break;

case 4: //снятие с соревнований
	if(!$item_id)
		die("Не указан ID заявки");
	$start_number=req2num($comp_id,$item_id);
	if(!$start_number)
		die("Ошибка получения бортового номера по номеру заявки($item_id)");
	$type=$_POST['take_off']; //тип дисквалификации
	if(!strlen($disq_types[$type]))
		die("Указан неправильный тип снятия($type)!");
	$reason=$_POST['take_off_reason']; //текстовая причина
	if(!disq($comp_id,$start_number,$type,$reason))
		die("Ошибка дисквалификации, вызов функции disq()");
	header("Location: ".append_rnd("online_requests_add.php?comp_id=$comp_id&item_id=$item_id&$filters_str"));
	exit;
	break;
case 5: //удалить снятие с соревнований
	if(!$item_id)
		die("Не указан ID заявки");
	$start_number=req2num($comp_id,$item_id);
	if(!$start_number)
		die("Ошибка получения бортового номера по номеру заявки($item_id)");
	remove_disq($comp_id,$start_number);
	header("Location: ".append_rnd("online_requests_add.php?comp_id=$comp_id&item_id=$item_id&$filters_str"));
	exit;
	break;

case 6: //снять бронь	
	if(!$item_id)
		die("Не указан ID заявки");
	if(req2num($comp_id,$item_id))
		die("Участнег уже зарегистрирован!");
	query_eval("UPDATE $compreq_dbt SET request_cabine_number=0 WHERE comp_id=$comp_id AND id=$item_id LIMIT 1;");
	header("Location: ".append_rnd("online_requests_add.php?comp_id=$comp_id&item_id=$item_id&request_cabine_number_deleted=1&$filters_str"));
	exit;
	break;
case 7: //изменение категории после регистрации
	if(!$item_id)
		die("Не указан ID заявки");
	$start_number=req2num($comp_id,$item_id);
	if(!$start_number)
		die("[flag7] Ошибка получения бортового номера ($item_id)!!");
	if(!defined('CAN_CHANGE_CAT_ID_AFTER_REGISTER') or !CAN_CHANGE_CAT_ID_AFTER_REGISTER)
		die("Функциональность отключена.");
	$new_cat_id=_input_val('new_cat_id');
	if(!$new_cat_id or $new_cat_id<0 or $new_cat_id>_CATEGORIES)
		die("Выбрана неправильная категория");
	change_cat_id($comp_id,$start_number,$new_cat_id);
	header("Location: ".append_rnd("online_requests_add.php?comp_id=$comp_id&item_id=$item_id&cat_changed=$new_cat_id"));
	exit;

	break;
}	



if($item_id){
	$res=query_eval("SELECT * FROM $compreq_dbt WHERE id = '$item_id';");
	if(!mysql_num_rows($res))
		die('bad item_id!');
	$row=mysql_fetch_assoc($res);
	$cat_id=$item_output['category']=(int)$row['category'];

	if($row['ext_attr_enabled']=='yes')
		$item_output['ext_attr_enabled']=true;
	else
		$item_output['ext_attr_enabled']=false;

	$item_output['pilot_name']=stripslashes($row['PilotName']);
	$item_output['pilot_nik']=stripslashes($row['PilotNik']);
	$item_output['pilot_phone']=stripslashes($row['PilotPhone']);
	$item_output['pilot_city']=stripslashes($row['PilotCity']);
	$item_output['pilot_size']=stripslashes($row['PilotSize']);


	$item_output['navigator_name']=stripslashes($row['NavigatorName']);
	$item_output['navigator_nik']=stripslashes($row['NavigatorNik']);
	$item_output['navigator_phone']=stripslashes($row['NavigatorPhone']);
	$item_output['navigator_city']=stripslashes($row['NavigatorCity']);
	$item_output['navigator_size']=stripslashes($row['NavigatorSize']);

	$item_output['auto_brand']=stripslashes($row['AutoBrand']);
	$item_output['auto_number']=stripslashes($row['AutoNumber']);
	$item_output['wheel_size']=stripslashes($row['WheelSize']);
	$item_output['phone']=stripslashes($row['phone']);
	$item_output['city']=stripslashes($row['city']);
	$item_output['email']=stripslashes($row['email']);
	$item_output['club']=stripslashes($row['club']);
	$item_output['from']=$from_array[stripslashes($row['source'])];
	$item_output['ip']=$row['ip'];
	$item_output['register_date']=date('d.m.Y',(int)$row['RegisterDate']);
	$item_output['cabine_number']=$row['request_cabine_number'];
	if(!$item_output['cabine_number']){
		$item_output['cabine_number']=''; //чтобы юзера не пугать
	}else{
		if(!req2num($comp_id,$item_id)) //если еще не зарегистрирован
			$item_output['remove_request_cabine_number_link']=append_rnd("online_requests_add.php?comp_id=$comp_id&flag=6&item_id=$item_id&$filters_str");
	}

	if($row['approved']=='yes'){
		$item_output['approved']=true;
		$item_output['approved_author']=stripslashes($row['ApprovedBy']);
	}else{
		$item_output['approved']=false;
		$item_output['approve_link']=append_rnd("online_requests_add.php?comp_id=$comp_id&flag=2&item_id=$item_id&$filters_str");
	}
	if($row['payd']=='yes'){
		$item_output['payd']=true;
		$item_output['payd_author']=stripslashes($row['payed_author']);
	}	
	else{
		$item_output['payd']=false;
		$item_output['pay_link']=append_rnd("online_requests_add.php?comp_id=$comp_id&flag=1&item_id=$item_id&$filters_str");
	}
	$need_tk=false;
	if(_cat_var($comp_id,$cat_id,'need_tk'))
		$need_tk=true;
	$tpl_can_change_cat_after_register=false;
	$tpl_can_change_cat_after_start_list=false;

	$item_output['start_number']=$start_number=req2num($comp_id,$item_id);
	if($start_number){
		if(defined('CAN_CHANGE_CAT_ID_AFTER_REGISTER') and CAN_CHANGE_CAT_ID_AFTER_REGISTER)
			$tpl_can_change_cat_after_register=true;
		$item['is_in_start_list']=in_start_list($comp_id,$start_number);
		if($item['is_in_start_list'])
			if(defined('CAN_CHANGE_CAT_ID_AFTER_START_LIST') and CAN_CHANGE_CAT_ID_AFTER_START_LIST)
				$tpl_can_change_cat_after_start_list=true;
		if($need_tk){
			$item_output['tk_is_passed']=tk_is_passed($comp_id,$start_number);
			if($item_output['tk_is_passed'])
				$item_output['tk_is_relative']=tk_relative($comp_id,$start_number);
			$item_output['tk_link']=append_rnd("tk.php?comp_id=$comp_id&start_number=$start_number");
		}
		if(defined('CA_TRACK_PORTAL') and CA_TRACK_PORTAL)
			$item_output['have_portal']=has_portal($comp_id,$start_number);
		//определение списка категорий, в которые можно сунуть участника
		//если отключена возможность менять категории после того, как стартовая ведомость уже создана, то в списке будут только категории, по которым нет еще ведомости
		$tpl_categories_to_change=categories_to_change($comp_id,$start_number);

	}
	else
		$item_output['is_in_start_list']=false;	

	if($start_number){
		if(has_disq($start_number)){
			$disq_data=disq_type($comp_id,$start_number);
			if($disq_data[0]){
				$item_output['disq']=$disq_type=$disq_data[0];
				$item_output['disq_comment']=$disq_comment=$disq_data[1];
				$item_output['dedisq_link']=append_rnd("online_requests_add.php?comp_id=$comp_id&flag=5&item_id=$item_id&$filters_str");
			}
		}
	}

	$item_output['print_link']=append_rnd("print-request.php?comp_id=$comp_id&request_id=$item_id");
	if(defined('CA_PDF_REQUEST_ENABLED') and CA_PDF_REQUEST_ENABLED)
		$item_output['print_link'].='&pdf=1';

	$item_output=fill_ext_attr($comp_id,$item_id,$item_output);
}	
if(!$item_output['register_date']) //default date
	$item_output['register_date']=date('d.m.Y');

if(defined(ATV_NUM) and ATV_NUM)
	$tpl_atv_num=ATV_NUM;
else
	$tpl_atv_num=65535;

$tpl_need_tk=$need_tk;

require('admin_header.php');
require('_templates/online_requests_add.phtml');




function php4_strtotime($value){
	$match = 0;
	if(ereg('^([0-9]{1,2})[/|.|\\]([0-9]{1,2})[/|.|\\]([0-9]{2,4})$',$value, $match))
		if(($ret = strtotime("{$match[2]}/{$match[1]}/{$match[3]}"))) 
			return $ret;
	return null;
}
