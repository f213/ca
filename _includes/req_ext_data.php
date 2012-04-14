<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//Дополнительные данные заявки - нужны для того, чтобы можно было гибко собирать любые данные с пользователей. Сейчас это используется для т.н. "Спортивных данных" - рафовских документов, где нужны нестандартные для традиционки данные

$_allowed_ext_attr=array(
	'addr' => 'Адрес',
	'birthday'=>'День рождения',
	'passport_series'=>'Серия',
	'passport_number'=>'Номер',
	'passport_given_who'=>'Кем выдан',
	'passport_given_when'=>'Когда выдан',
	'license_type'=>'Тип лицензии',
	'license_num'=>'Номер лицензии',
	'rank'=>'Звание',
	'zayav_name'=>'заявитель',
);

$_req_ext_attr=array('zayav_name');
if(!(defined('__PEOPLE_NAMES__'))){
	die('req_ext_data.php: следует подключать только после people_names.php');
}

function _ext_attr($comp_id,$req_id,$name,$value=null){ //получение\редактирование дополнительного атрибута
	global $_allowed_ext_attr;
	global $_people_names;
	global $compreq_ext_dbt;
	
	$comp_id=(int)$comp_id; $req_id=(int)$req_id;

	if(!$comp_id or !$req_id)
		return null;

	if(!_ext_attr_enabled($name))
		return false;

	if(is_null($value)){ //получаем
		$res=query_eval("SELECT attr_val FROM $compreq_ext_dbt WHERE comp_id=$comp_id AND request_id=$req_id AND attr_name='$name';");
		if(!mysql_num_rows($res))
			return null;
		$row=mysql_fetch_row($res);
		return stripslashes($row[0]);
	}else{ //устанавливаем
		$value=addslashes($value);
		$res=query_eval("SELECT attr_val FROM $compreq_ext_dbt WHERE comp_id=$comp_id AND request_id=$req_id AND attr_name='$name';");
		if(!mysql_num_rows($res))
			return query_eval("REPLACE INTO $compreq_ext_dbt SET attr_val='$value',comp_id=$comp_id,request_id=$req_id,attr_name='$name';");
		else
			return query_eval("UPDATE $compreq_ext_dbt SET attr_val='$value' WHERE comp_id=$comp_id AND request_id=$req_id AND attr_name='$name';");
	}
		
}
function _ext_attr_enabled($name){
	global $_allowed_ext_attr;
	global $_people_names;
	if(in_array($name,$_allowed_ext_attr))
		return true;
	$allowed_prefixes=array(); //здеся выбираем все возможные префиксы для названий, типа поле addr может быть так же pilot_addr и navigator_addr
	foreach($_people_names as $allowed_prefix=>$q)
		$allowed_prefixes[]=$allowed_prefix;

	foreach($_allowed_ext_attr as $allowed_attr=>$q)
		foreach($allowed_prefixes as $prefix)
			if($name==$prefix.'_'.$allowed_attr)
				if(in_array($allowed_attr,$_people_names[$prefix]['ext_attr']))
					return true;
	return false;
}
function fill_ext_attr($comp_id,$req_id,$item_output){
	global $_people_names;
	global $_req_ext_attr;
        $comp_id=(int)$comp_id; $req_id=(int)$req_id;

        if(!$comp_id or !$req_id)
                return null;
	//сначала набираем атрибуты людей
	foreach($_people_names as $type=>$value)
		foreach($value['ext_attr'] as $ext_attr){
			$ext_attr=$type.'_'.$ext_attr;
			if(_ext_attr_enabled($ext_attr))
				$item_output[$ext_attr]=_ext_attr($comp_id,$req_id,$ext_attr);
		}
	//теперя набираем другие атрибуты заявки
	foreach($_req_ext_attr as $ext_attr)
		if(_ext_attr_enabled($ext_attr))
			$item_output[$ext_attr]=_ext_attr($comp_id,$req_id,$ext_attr);
	return $item_output;
}
