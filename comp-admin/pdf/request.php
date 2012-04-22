<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//Печать РАФовской заявки в PDF-формате

require_once('../_includes/pdf.php');

function print_pdf_request($item_output){
	global $OFFICIAL_DATA; //официальные данные гонки
	global $default_tbl_config; //настройки табилцы по умолчанию ../_includes/pdf.php
	global $default_font_size; //размер шрифта, от которого отталкиваются другие ../_includes/pdf.php

	if(!defined('CA_PDF_REQUEST_ENABLED') or !CA_PDF_REQUEST_ENABLED)
		die('print_pdf_request(): в настройках выключена печать pdf для заявок!');

	$pdf = new tFPDF();
	$pdf->Open();
	$pdf->SetAutoPageBreak(true, 20);
	$pdf->SetMargins(20, 20, 20);
	$pdf->AddPage();
	$pdf->AliasNbPages();
	$pdf->AddFont('times','',CA_PDF_REQUEST_FONT,true);
	$pdf->addFont('times','B',CA_PDF_REQUEST_FONT,true);
	$pdf->SetAutoPageBreak(false);
	$pdf->setY(raf_pdf_header($pdf,1));
	
	//Формируем таблицу с типами гонки, без бордера

	$table=new tfpdfTable($pdf); 
	$tbl_config=$default_tbl_config;
	$tbl_config['TABLE']['BORDER_COLOR']=$tbl_config['ROW']['BORDER_COLOR']=array(255,255,255); 
	$table->initialize(array(65,45,45,45),$tbl_config);
	$reqheader=array(
		0=>array(
			'TEXT'=>'ЗАЯВКА НА УЧАСТИЕ',
			'TEXT_SIZE'=>11,
			'TEXT_ALIGN'=>'C',
		),
		1=>array(
			'TEXT'=>'',
			'TEXT_SIZE'=>11,
			'TEXT_TYPE'=>'B',
		),
	);
	$reqheader[2]=$reqheader[3]=$reqheader[1];
	for($i=1;$i<MAGIC_MAX_RAF_RACE_TYPES;$i++) //все типы гонки, которые возможны лежат в файле official.ini
		if($OFFICIAL_DATA['raf_race_types'][$i])
			$reqheader[$i]['TEXT']=$OFFICIAL_DATA['raf_race_types'][$i];

	$table->addRow($reqheader);
	$table->close();

	$pdf->setY($pdf->getY()+2);

	//вторая сверху таблица - данные заявителя

	$table = new tfpdfTable($pdf);
	$table->initialize(array(24,24,79,15,55),$default_tbl_config);
	$row1=array(
		0=>array(
			'TEXT'=>"Участник (Заявитель)\r\n ",
			'TEXT_SIZE'=>$default_font_size,
			'COLSPAN'=>2,
			'PADDING_LEFT'=>1,
		),
		2=>array(
			'TEXT'=>$item_output['declarant_name_official'],
			'TEXT_SIZE'=>$default_font_size,
			'COLSPAN'=>2,
			'PADDING_LEFT'=>1,
		),
		4=>array(
			'TEXT'=>'Лицензия Участника №',
			'TEXT_SIZE'=>$default_font_size,
			'PADDING_LEFT'=>1,
		),
	);
	if($item_output['declarant_license_num'])
		$row1[4]['TEXT'].= $item_output['declarant_license_num'];
	else
		$row1[4]['TEXT'].='_______';
	$table->addRow($row1);

	$table->addRow(array(
		0=>array(
			'TEXT'=>'Представитель участника',
			'TEXT_SIZE'=>$default_font_size,
			'COLSPAN'=>2,
			'PADDING_LEFT'=>1,
		),
		2=>array(
			'COLSPAN'=>3,
		),
	));

	$row1=array(
		0=>array(
			'TEXT'=>'Страна',
			'TEXT_SIZE'=>$default_font_size,
			'PADDING_LEFT'=>1,
		),
		1=>array(
			'TEXT'=>$item_output['declarant_country'],
			'TEXT_SIZE'=>$default_font_size,
			'PADDING_LEFT'=>1,
		),
		2=>array(
			'TEXT'=>'Адрес',
			'TEXT_SIZE'=>$default_font_size,
			'COLSPAN'=>3,
			'PADDING_LEFT'=>1,
		),
	);
	if($item_output['declarant_addr'] and _strlen($item_output['declarant_addr']))
		$row1[2]['TEXT'].=' '.$item_output['declarant_addr'];

	$table->addRow($row1);

	$row1=array(
		0=>array(
			'TEXT'=>'e-mail',
			'TEXT_SIZE'=>$default_font_size,
			'PADDING_LEFT'=>1,
		),
		1=>array(
			'TEXT'=>$item_output['declarant_email'],
			'COLSPAN'=>2,
			'PADDING_LEFT'=>1,
		),
		3=>array(
			'TEXT'=>'Телефон',
			'TEXT_SIZE'=>$default_font_size,
			'COLSPAN'=>2,
			'PADDING_LEFT'=>1,
		),
	);
	if($item_output['declarant_phone'] and _strlen($item_output['declarant_phone']))
		$row1[3]['TEXT'].=' '.$item_output['declarant_phone'];

	$table->addRow($row1);

	$table->close();

	$pdf->setY($pdf->getY()+2);

	//третья сверху таблица - данные экипажа

	$table = new tfpdfTable($pdf);
	$table->initialize(array(44,50,25,78),$default_tbl_config);

	$table->addRow(array(
		0=>array(
			'BORDER'=>'RB',
		),
		1=>array(
			'TEXT'=>'1-й водитель',
			'TEXT_SIZE'=>$default_font_size+2,
			'TEXT_TYPE'=>'B',
			'TEXT_ALIGN'=>'C',
			'COLSPAN'=>2,
		),
		3=>array(
			'TEXT'=>'2-й водитель',
			'TEXT_SIZE'=>$default_font_size+2,
			'TEXT_TYPE'=>'B',
			'TEXT_ALIGN'=>'C',
		),
	));
#	die(var_dump($item_output));
	_reqpdf_add_request_row($table,'ФАМИЛИЯ',$item_output['pilot_name_f'],$item_output['navigator_name_f']);
	_reqpdf_add_request_row($table,'ИМЯ',$item_output['pilot_name_i'],$item_output['navigator_name_i']);
	_reqpdf_add_request_row($table,'ОТЧЕСТВО',$item_output['pilot_name_o'],$item_output['navigator_name_o']);
	_reqpdf_add_request_row($table,'ЛИЦО ДЛЯ КОНТАКТОВ');
	_reqpdf_add_request_row($table,'КОНТАКТНЫЙ ТЕЛЕФОН',$item_output['pilot_phone'],$item_output['shturman_phone']);
	_reqpdf_add_request_row($table,'E-MAIL',$item_output['pilot_email'],$item_output['shturman_email']);
	_reqpdf_add_request_row($table,'ГОРОД',$item_output['pilot_city_capitalized'],$item_output['navigator_city_capitalized']);
	_reqpdf_add_request_row($table,"СПОРТИВНОЕ ЗВАНИЕ, \r\nРАЗРЯД",$item_output['pilot_rank'],$item_output['shturman_rank']);
	_reqpdf_add_request_row($table,"КАТЕГОРИЯ И № \r\nВОДИТ. УДОСТ-Я");
	_reqpdf_add_request_row($table,"ПАСПОРТ\r\n(№, ДАТА ВЫДАЧИ)",$item_output['pilot_passport_series'].' '.$item_output['pilot_passport_num'].' '.$item_output['pilot_passport_when'],$item_output['shturman_passport_series'].' '.$item_output['shturman_passport_series'].' '.$item_output['shturman_passport_when']);
	_reqpdf_add_request_row($table,"КАТЕГОРИЯ И № \r\nЛИЦЕНЗИИ ВОДИТЕЛЯ",$item_output['pilot_license_type'].' '.$item_output['pilot_license_num'],$item_output['shturman_license_type'].' '.$item_output['shturman_license_num']);
	_reqpdf_add_request_row($table,"ASN (НАФ)\r\nВЫДАВШАЯ ЛИЦЕНЗИЮ");
	$table->addRow(array(
		0=>array(
			'TEXT'=>'Подписав эту заявку, участник и водители признают и обязуются выполнять все требования СК РАФ и иной регламентирующей документации РАФ, а также принимают на себя все риски и всю ответственность за возможные последствия своего участия в соревновании.',
			'TEXT_SIZE'=>$default_font_size-1,
			'COLSPAN'=>4,
			'PADDING_LEFT'=>1,
		),
	));
	_reqpdf_add_request_row($table,'ПОДПИСИ ВОДИТЕЛЕЙ');
	$table->addRow(array(
		0=>array(
			'TEXT'=>"ПОДПИСЬ УЧАСТНИКА\r\n(С ОБЯЗАТЕЛЬНОЙ РАСШИФРОВКОЙ",
			'TEXT_SIZE'=>$default_font_size,
			'COLSPAN'=>2,
			'PADDING_LEFT'=>1,
		),
		2=>array(
			'COLSPAN'=>2,
		),
	));

	$table->close();

	//четвертая сверху таблица - данные автомобиля

	$table=new tfpdfTable($pdf);
	$table->initialize(array(48,49,50,50),$default_tbl_config);

	$table->addRow(array(
		0=>array(
			'TEXT'=>'АВТОМОБИЛЬ',
			'TEXT_SIZE'=>$default_font_size+1,
			'COLSPAN'=>4,
			'TEXT_ALIGN'=>'C',
			'PADDING_TOP'=>1,
			'PADDING_BOTTOM'=>1,
		),
	));

	$table->addRow(array(
		0=>array(
			'TEXT'=>'По регистрационным документам:',
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_TYPE'=>'B',
			'PADDING_LEFT'=>1,
			'COLSPAN'=>2,
		),
		2=>array(
			'TEXT'=>'По СТП (спортивному техническому паспорту):',
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_TYPE'=>'B',
			'PADDING_LEFT'=>1,
			'COLSPAN'=>2,
		),
	));
	_reqpdf_add_auto_row($table,'№ РЕГ. СВИДЕТЕЛЬСТВА','','№ ТЕХ. ПАСПОРТА РАФ','');
	_reqpdf_add_auto_row($table,'ГОС. НОМЕРНОЙ ЗНАК',$item_output['auto_number'],'МАРКА / МОДЕЛЬ',$item_output['auto_brand']);
	_reqpdf_add_auto_row($table,'ФИО ВЛАДЕЛЬЦА','','МИНИМАЛЬНЫЙ ВЕС','');
	_reqpdf_add_auto_row($table,'','','МАРКА / МОДЕЛЬ КОЛЕС',$item_output['wheel_size']);
	for($i=1;$i<=2;$i++)
		_reqpdf_add_auto_row($table,'','','','');
	_reqpdf_add_auto_row($table,"КАТЕГОРИЯ АВТОМОБИЛЯ\r\n (ТР1,ТР2,ТР3)",'',"ЗАЧЕТНАЯ ГРУППА\r\n(ТР1,ТР2,ТР3, АБСОЛЮТ И ДР.)",$item_output['cat_name']);
	$table->close();

	//пятая сверху таблица - простенькая, необязательная реклама Организитора, находится без отступа от предыдущей

	$table=new tfpdfTable($pdf);
	$table->initialize(array(97,50,50),$default_tbl_config);
	$table->addRow(array(
		0=>array(
			'TEXT'=>'Необязательная реклама организатора',
			'TEXT_TYPE'=>'B',
			'TEXT_SIZE'=>$default_font_size+1,
			'PADDING_TOP'=>1,
			'PADDING_BOTTOM'=>1,
			'PADDING_LEFT'=>1,
		),
		1=>array(
			'TEXT'=>'Согласен',
			'TEXT_TYPE'=>'B',
			'TEXT_SIZE'=>$default_font_size+1,
			'TEXT_ALIGN'=>'C',
		),
		2=>array(
			'TEXT'=>'Отказ',
			'TEXT_TYPE'=>'B',
			'TEXT_SIZE'=>$default_font_size+1,
			'TEXT_ALIGN'=>'C',
		),
	));
	$table->close();

	//Шестая таблица - еще проще. Про комманду

	$table = new tfpdfTable($pdf);
	$table->initialize(array(72,125),$default_tbl_config);
	$table->addRow(array(
		0=>array(
			'TEXT'=>'Экипаж заявлен в комманде',
			'TEXT_SIZE'=>$default_font_size-2,
			'PADDING_LEFT'=>1,
			'TEXT_ALIGN'=>'R',
		),
		1=>array(
			'TEXT'=>'',
			'TEXT_SIZE'=>$default_font_size-2,
			'PADDING_LEFT'=>1,
		),
	));
	$table->close();

	//подтверждение о согласии
	$pdf->setY($pdf->getY()+3);
	$tmp_y=$pdf->getY();
	$pdf->setFont('times','B',$default_font_size-2);
	$pdf->Text(7,$tmp_y,'ПОДТВЕРЖДЕНИЕ О СОГЛАСИИ');
	$pdf->setFont('times','',$default_font_size-2);
	$pdf->Text(50,$tmp_y,'Своей подписью я подтверждаю, что вся информация, содержащаяся в Заявочной форме верна, и заявленный автомобиль');
	$pdf->Text(7,$tmp_y+2,'соответствует требованиям безопасности для ралли-рейдов. Я принимаю все условия оплаты и условия моего участия в этом соревновании.');

	$pdf->setFont('times','',$default_font_size);
	$pdf->Text(7,$tmp_y+6,'Представитель Участника ___________________________ (___________________________)');

	$pdf->setFont('times','',$default_font_size-2);
	$pdf->Text(65,$tmp_y+9,'(подпись)');
	$pdf->Text(110,$tmp_y+9,'(Фамилия И. О.)');

	$pdf->Output();
}

function _reqpdf_add_request_row($table,$t1,$t2='',$t3=''){ //добавить стандартное поле в таблицу с экипажем. Там много поле одинаковых. $t1 - название поля, $t2 и $t3 - значения, для первого и второго водителя соответсвенно
	global $default_font_size;
	$table->addRow(array(
		0=>array(
			'TEXT'=>$t1,
			'TEXT_SIZE'=>$default_font_size-2,
			'PADDING_LEFT'=>1,
		),
		1=>array(
				'TEXT'=>_reqpdf_append_n($t2),
			'TEXT_SIZE'=>$default_font_size,
			'COLSPAN'=>2,
			'PADDING_LEFT'=>1,
		),
		3=>array(
			'TEXT'=>_reqpdf_append_n($t3),
			'TEXT_SIZE'=>$default_font_size,
			'PADDING_LEFT'=>1,
		),
	));
}
function _reqpdf_add_auto_row($table,$t1,$t2,$t3,$t4){
	global $default_font_size;

	$table->addRow(array(
		0=>array(
			'TEXT'=>$t1,
			'TEXT_SIZE'=>$default_font_size-1,
			'PADDING_LEFT'=>1,
		),
		1=>array(
			'TEXT'=>_reqpdf_append_n($t2),
			'TEXT_SIZE'=>$default_font_size,
			'PADDING_LEFT'=>1,
		),
		2=>array(
			'TEXT'=>$t3,
			'TEXT_SIZE'=>$default_font_size-1,
			'PADDING_LEFT'=>1,
		),
		3=>array(
			'TEXT'=>_reqpdf_append_n($t4),
			'TEXT_SIZE'=>$default_font_size,
			'PADDING_LEFT'=>1,
		),
	));
}
function _reqpdf_append_n($t){
	if(_strlen($t<19))
		$t.="\r\n ";
	return $t;
}
