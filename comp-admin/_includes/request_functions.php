<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

/*get_brief_request_data($comp_id,$request_id,$append_hash=array()) - Получение данных о заявке по ее id
 * Append_hash следует использовать, если необходимо добавить какие-то данные к существующим, т.е. будет возвращен append_hash с данными о заявке. 
 * Возвращает хеш со следующими ключами
 * pilot_name, navigator_name - имена пилота и штурамана. Если есть ники, ставятся после скобочек.
 * pilot_name_official, navigator_name_official - имена пилота и штурмана без ников
 * pilot_name_f, pilot_name_i, pilot_name_o; navigator_name_f, navigator_name_i, navigator_name_o - отдельно фамилия, имя и отчество на каждого члена экипажа
 * pilot_city, pilot_city_capitalized - город пилота
 * navigator_city, navigator_city_capitalized - город штурмана
 * auto_brand - марка машины
 * auto_number - госномер
 * wheel_size - размер колес
 * email
 * phone - телефон экипажа
 * city - город, как в базе
 * city_capitalized - город, первая буква всегда большая
 * cat_id - id категории участника
 * cat_name (если есть) - название категории участника
 * crew - экипаж, пилот и штурман, если есть.

 get_full_request_data($comp_id,$request_id,$append_hash=array()) - Более полный список данных. пока отличается только наличием "спортивных аттрибутов". Список атрибутов см. в соответсвующих файлах req_ext_data.php и people_names.php/

*/

function get_full_request_data($comp_id,$request_id,$append_hash=array()){
	$comp_id=(int)$comp_id;
	$request_id=(int)$request_id;
	
	if(!$comp_id or !$request_id)
		return null;
	$append_hash=get_brief_request_data($comp_id,$request_id,$append_hash);
	$append_hash=fill_ext_attr($comp_id,$request_id,$append_hash);
	if(empty($append_hash['pilot_email']) and !empty($append_hash['email']))
		$append_hash['pilot_email']=$append_hash['email'];
	if(empty($append_hash['shturman_email']) and !empty($append_hash['email']))
		$append_hash['shturman_email']=$append_hash['email'];

	return $append_hash;
}

function get_brief_request_data($comp_id,$request_id,$append_hash=array()){
	global $compreq_dbt;
	global $cat_name;

	$comp_id=(int)$comp_id;
	$request_id=(int)$request_id;
	if(!$request_id or !$comp_id)
		return null;
	$res=query_eval("SELECT category,PilotName,PilotNik,PilotCity,NavigatorName,NavigatorNik,NavigatorCity,city,AutoBrand,AutoNumber,WheelSize,phone,email FROM $compreq_dbt WHERE comp_id=$comp_id AND id=$request_id;");
	if(!mysql_num_rows($res))
		return null;
	$ret=$append_hash;
	$row=mysql_fetch_assoc($res);
	$ret['pilot_name']=stripslashes($row['PilotName']);
	$ret['pilot_name_official']=name2official($ret['pilot_name']);
	list($ret['pilot_name_f'],$ret['pilot_name_i'],$ret['pilot_name_o'])=get_fio($ret['pilot_name']);
	if($row['PilotNik'])
		$ret['pilot_name'].=' ('.stripslashes($row['PilotNik']).')';
	$ret['pilot_city']=stripslashes($row['PilotCity']);

	if(!_strlen($ret['pilot_city'])) //если нет города чувака - ставим город заявки
		$ret['pilot_city']=stripslashes($row['city']);
	$ret['pilot_city_capitalized']=_ucfirst($ret['pilot_city']);

	$ret['navigator_name']=stripslashes($row['NavigatorName']);
	$ret['navigator_name_official']=name2official($ret['navigator_name']);
	list($ret['navigator_name_f'],$ret['navigator_name_i'], $ret['navigator_name_o'])=get_fio($ret['navigator_name']);
	if($row['NavigatorNik'])
		$ret['navigator_name'].=' ('.stripslashes($row['NavigatorNik']).')';
	$ret['navigator_city']=stripslashes($row['NavigatorCity']);

	if(!_strlen($ret['navigator_city']))
		$ret['navigator_city']=stripslashes($row['city']);

	$ret['navigator_city_capitalized']=_ucfirst($ret['navigator_city']);
	$ret['phone']=stripslashes($row['phone']);
	$ret['email']=stripslashes($row['email']);
	$ret['auto_brand']=stripslashes($row['AutoBrand']);
	$ret['auto_number']=str_replace(' ','',stripslashes($row['AutoNumber']));
	$ret['city']=stripslashes($row['city']);
	$ret['city_capitalized']=_ucfirst($ret['city']);
	$ret['cat_id']=(int)$row['category'];
	if($ret['cat_id'])
		$ret['cat_name']=$cat_name[$ret['cat_id']];

	$ret['wheel_size']=(int)$row['WheelSize'];
	$ret['crew']=__get_crew($ret,'full','ca');
	return $ret;
}	
/* get_crew($comp_id,$request_id,$type,$where) - единая функция для вывода экипажа. Типы запросов:
 * 	full - все имена, с никами если есть
 * 	official - официальные имена, только имя и фамилия
 * 	requester - форма заявителя. Полностью имя одного заявителя без ника.
 *
 * $where - media, куда выводим, бывает
 * 	ca - система администрирования
 * 	request - заявка
 * 	print - печать
 */
function get_crew($comp_id,$request_id,$type='full',$where='ca'){
	$comp_id=(int)$comp_id;
	$request_id=(int)$request_id;
	if(!$request_id or !$comp_id)
		return null;
	return __get_crew(get_brief_request_data($comp_id,$request_id),$type,$where);

}
function __get_crew($req_data,$type='full',$where='ca'){
	global $_people_names;
	$ret='';
	if($type=='official' or $type=='requester')
		$ret=$req_data['pilot_name_official'];
	if($type=='full')
		$ret=$req_data['pilot_name'];
	if(!$_people_names['shturman'][$where] or !$_people_names['shturman']['ca'] or $type=='requester') //для типа "заявитель" или если не работаем со штурманом - чисто имя пилота
		return $ret;
	if(!$req_data['navigator_name']) //если штурмана нету в заявке тоже нафиг
		return $ret;
	$ret.=', ';
	if($type=='official')
		$ret.=$req_data['navigator_name_official'];
	if($type=='full')
		$ret.=$req_data['navigator_name'];
	return $ret;

}


function name2official($name){
	$name=preg_replace('/([^\ ]+)\ +([^\ ]+)\ +([^\ ]+)/u','$1 $2',$name);
	list($f,$i)=get_fi($name);
	//искуственный интеллект, бля
	if(_strlen($i)==1)
		$i.='.';
	$i=str_replace(',','.',$i);
	if(preg_match('/\./u',$i) and _strlen($i<6)){
		$i=_strtoupper($i);
		if(!preg_match('/\.$/u',$i))
			$i.='.';
		$i=preg_replace('/.\.$/u','',$i);
	}
	$name="$f $i";
	return $name;
}
function get_fi($name){
	$name=preg_replace('/([^\ ]+)\ +([^\ ]+)\ +([^\ ]+)/u','$1 $2',$name);
	list($f,$i)=preg_split('/\ +/',$name);
	$f=_ucfirst($f); $i=_ucfirst($i);
	return array($f,$i);
}
function get_fio($name){
	$name=preg_replace('/([^\ ]+)\ +([^\ ]+)\ +([^\ ]+)/u','$1 $2',$name);
	list($f,$i,$o)=preg_split('/\ +/',$name);
	$f=_ucfirst($f); $i=_ucfirst($i); $o=_ucfirst($o);
	return array($f,$i,$o);
}
