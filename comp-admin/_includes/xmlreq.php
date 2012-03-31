<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//Библиотека для импорта и экспорта списков участников. Нужна для обмена между кастрированными версиями системы, которые стоят на сторонних хостах чисто для приема заявок и рабочими версиями. Состоит из двух функций: export_requests_xml и import_requests_xml. Если выкладывать эту библиотеку на кастрированный хост, то вторую функцию следует удалить.
//
//export_requests_xml($comp_id,$cat_ids,$sql_append) - экспорт списка участников в собственный формат.
//	$comp_id - ID соревнования
//	$cat_ids - массив из списка категорий, которые эскпортируем.
//	$sql_append (необязательный параметр) - добавка к sql-запросу выборки.
//
//import_requests_xml($comp_id,$filename) - импорт из xml. Возвращает количество импортированных записей.
function export_requests_xml($comp_id,$cat_ids,$sql_append=''){
	global $compreq_dbt;
	global $cat_name;
	$comp_id=(int)$comp_id;
	if(!$comp_id)
		return false;
	if(!is_array($cat_ids) or !sizeof($cat_ids))
		return false;
	//здесь не используется valid_numbers_str потому что эта функциональность может портироваться на кастрированные версии системы без библиотеки _started_functions.
	//implode тоже не нужен
	$cat_ids_str='';
	foreach($cat_ids as $cat_id)
		$cat_ids_str.="'".(int)$cat_id."',";
	$cat_ids_str=trim($cat_ids_str,',');
	if($sql_append and !preg_match('/\ *and/i',$sql_append))
		$sql_append="AND $sql_append";
	$res=query_eval("SELECT * FROM $compreq_dbt WHERE comp_id=$comp_id AND category IN ($cat_ids_str) $sql_append ORDER BY RegisterDate ASC, category ASC");
	if(!mysql_num_rows($res))
		return false;
	$doc=new DomDocument('1.0','utf-8');
	$req_list=$doc->appendChild($doc->createElement('requests'));
	while($row=mysql_fetch_assoc($res)){
		$el=$doc->createElement('request');

		__x_attr($doc,$el,'date',(int)$row['RegisterDate']);
		__x_attr($doc,$el,'cat_id',(int)$row['category']);
		if($row['request_cabine_number']) //желаемый борт номер
			__x_attr($doc,$el,'requested_number',(int)$row['request_cabine_number']);
		if($row['ip'])
			__x_attr($doc,$el,'ip',$row['ip']);
		if($row['source'])
			__x_attr($doc,$el,'source',$row['source']);
		

		//оплата
		if($row['payd']=='yes'){
			$payment=$doc->createElement('payment');
			__x_attr($doc,$payment,'payd','true');
			__x_attr($doc,$payment,'author',$row['payed_author']);
			$el->appendChild($payment);
		}
		//комманда
		$crew=$doc->createElement('crew');
		//пилот
		$pilot=$doc->createElement('member');
		__x_attr($doc,$pilot,'type','pilot');
		__x_attr($doc,$pilot,'name',$row['PilotName']);
		if($row['PilotNik'])
			__x_attr($doc,$pilot,'nick',$row['PilotNik']);
		if($row['PilotSize'])
			__x_attr($doc,$pilot,'size',$row['PilotSize']);
		__x_attr($doc,$pilot,'phone',$row['phone']);
		__x_attr($doc,$pilot,'email',$row['email']);
		__x_attr($doc,$pilot,'city',$row['city']);
		if($row['club'])
			__x_attr($doc,$pilot,'club',$row['club']);
		//штурман
		$nav=$doc->createElement('member');
		__x_attr($doc,$nav,'type','navigator');
		__x_attr($doc,$nav,'name',$row['NavigatorName']);
		if($row['NavigatorNik'])
			__x_attr($doc,$nav,'nick',$row['NavigatorNik']);
		if($row['NavigatorSize'])
			__x_attr($doc,$nav,'size',$row['NavigatorSize']);
		__x_attr($doc,$nav,'phone',$row['phone']);
		__x_attr($doc,$nav,'email',$row['email']);
		__x_attr($doc,$nav,'city',$row['city']);
		if($row['club'])
			__x_attr($doc,$nav,'club',$row['club']);
		$crew->appendChild($pilot);
		$crew->appendChild($nav);
		$el->appendChild($crew);


		//машина
		$auto=$doc->CreateElement('auto');
		__x_attr($doc,$auto,'name',$row['AutoBrand']);
		__x_attr($doc,$auto,'number',$row['AutoNumber']);
		__x_attr($doc,$auto,'wheel_brand','');
		__x_attr($doc,$auto,'wheel_size',(int)$row['WheelSize']);
		$el->appendChild($auto);
		$req_list->appendChild($el);

	}
	header("Content-Type: application/force-download");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=requests_".date('Ymdhi').'.xml');
	print $doc->saveXML();
}
function import_requests_xml($comp_id,$file){
	global $compreq_dbt;
	$comp_id=(int)$comp_id;
	if(!$comp_id)
		return false;
	if(!is_readable($file))
		return false;
	$doc=new DOMDocument;
	$doc->loadXML(file_get_contents($file));
	$cnt=0;
	foreach($doc->getElementsByTagName('request') as $req){
		$data=array();
		foreach($req->childNodes as $a){
			if($a->nodeName=='crew'){
				foreach($a->childNodes as $c){
					$attr=$c->attributes;
					$type_val=$attr->getNamedItem('type')->value;
					$member_type='';
					switch($type_val){
					case 'pilot':
						$member_type='Pilot';
						break;
					case 'navigator':
						$member_type='Navigator';
						break;
					}
					if(!$member_type) //неизвестный тип члена комманды
						continue;
					$data[$member_type.'Name']=$attr->getNamedItem('name')->value;
					$data[$member_type.'Nik']=$attr->getNamedItem('nick')->value;
					$data[$member_type.'Size']=$attr->getNamedItem('Size')->value;
					if($member_type=='Pilot'){ //это заглушка. Пока у нас в базе параметры, перечисленные ниже, хранятся всместе, на весь экипаж.
						$data['phone']=$attr->getNamedItem('phone')->value;
						$data['email']=$attr->getNamedItem('email')->value;
						$data['city']=$attr->getNamedItem('city')->value;
						$data['club']=$attr->getNamedItem('club')->value;
					}

				}
			}
			if($a->nodeName=='auto'){
				$attr=$a->attributes;
				$data['AutoBrand']=$attr->getNamedItem('name')->value;
				$data['AutoNumber']=$attr->getNamedItem('number')->value;
				$data['WheelSize']=$attr->getNamedItem('wheel_size')->value;
			}
			if($a->nodeName=='payment'){
				$data['payd']='yes';
				$attr=$a->attributes;
				$data['payed_author']=$attr->getNamedItem('author')->value;
			}
		}
		$attr=$req->attributes;
		$data['RegisterDate']=$attr->getNamedItem('date')->value;
		$data['ip']=$attr->getNamedItem('ip')->value;
		$data['request_cabine_number']=$attr->getNamedItem('requested_number')->value;
		if(!$data['request_cabine_number'])
			unset($data['request_cabine_number']);
		$data['category']=$attr->getNamedItem('cat_id')->value;
		$data['comp_id']=$comp_id;
		$data['source']='import';

		add_item($compreq_dbt,$data);
		$cnt++;	
	}
	return $cnt;
}


function __x_attr($doc,$el,$attr_name,$attr_val){
	$attr=$doc->createAttribute($attr_name);
	$attr->value=htmlspecialchars($attr_val);
	$el->appendChild($attr);
}
