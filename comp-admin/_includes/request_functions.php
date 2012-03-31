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
 * auto_brand - марка машины
 * auto_number - госномер
 * wheel_size - размер колес
 * city - город, как в базе
 * city_capitalized - город, первая буква всегда большая
 * cat_id - id категории участника
 * cat_name (если есть) - название категории участника
 * crew - экипаж, пилот и штурман, если есть.
*/


function get_brief_request_data($comp_id,$request_id,$append_hash=array()){
	global $compreq_dbt;
	global $cat_name;

	$comp_id=(int)$comp_id;
	$request_id=(int)$request_id;
	if(!$request_id or !$comp_id)
		return null;
	$res=query_eval("SELECT category,PilotName,PilotNik,NavigatorName,NavigatorNik,city,AutoBrand,AutoNumber,WheelSize FROM $compreq_dbt WHERE comp_id=$comp_id AND id=$request_id;");
	if(!mysql_num_rows($res))
		return null;
	$ret=$append_hash;
	$row=mysql_fetch_assoc($res);
	$ret['pilot_name']=stripslashes($row['PilotName']);
	$ret['pilot_name_official']=name2official($ret['pilot_name']);
	if($row['PilotNik'])
		$ret['pilot_name'].=' ('.stripslashes($row['PilotNik']).')';
	$ret['navigator_name']=stripslashes($row['NavigatorName']);
	$ret['navigator_name_official']=name2official($ret['navigator_name']);
	if($row['NavigatorNik'])
		$ret['navigator_name'].=' ('.stripslashes($row['NavigatorNik']).')';
	$ret['auto_brand']=stripslashes($row['AutoBrand']);
	$ret['auto_number']=str_replace(' ','',stripslashes($row['AutoNumber']));
	$ret['city']=stripslashes($row['city']);
	$ret['city_capitalized']=ucfirst($ret['city']);
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
	$enc='UTF-8';
	$name=preg_replace('/([^\ ]+)\ +([^\ ]+)\ +([^\ ]+)/','$1 $2',$name);
	list($f,$i)=preg_split('/\ +/',$name);
	if(function_exists('mb_strtolower')){
		$f=mb_strtolower($f,$enc);
		$i=mb_strtolower($i,$enc);
	}else{
		$f=strtolower($f);
		$i=strtolower($i);
	}
	$f=__req_ucfirst($f);
	$i=__req_ucfirst($i);
	//искуственный интеллект, бля
	if(strlen($i)==1)
		$i.='.';
	$i=str_replace(',','.',$i);
	if(preg_match('/\./',$i) and strlen($i<6)){
		if(function_exists('mb_strtoupper'))
			$i=mb_strtoupper($i,$enc);
		else
			$i=strtoupper($i);
		if(!preg_match('/\.$/',$i))
			$i.='.';
		$i=preg_replace('/.\.$/','',$i);
	}
	$name="$f $i";
	return $name;
}

function __req_ucfirst($str){
	$str=chr(ord(substr($str,0,1))-32).substr($str,1);
	return $str;
}
