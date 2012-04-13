<?php

require_once('../_includes/core.php');
require_once('_includes/auth.php');

require_once('../_includes/pdf.php');


$pdf = new tFPDF();
$pdf->Open();
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();
$pdf->AliasNbPages();
$pdf->AddFont('times','','FreeSans.ttf',true);
$pdf->addFont('times','B','FreeSansBold.ttf',true);



$pdf->setY(raf_pdf_header($pdf,1));

//Формируем таблицу с типами гонки

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
for($i=1;$i<MAGIC_MAX_RAF_RACE_TYPES;$i++)
	if($OFFICIAL_DATA['raf_race_types'][$i])
		$reqheader[$i]['TEXT']=$OFFICIAL_DATA['raf_race_types'][$i];

$table->addRow($reqheader);
$table->close();

$pdf->setY($pdf->getY()+2);

//вторая сверху таблица - данные заявителя

$table = new tfpdfTable($pdf);
$table->initialize(array(24,24,79,15,55),$default_tbl_config);
$table->addRow(array(
	0=>array(
		'TEXT'=>"Участник (Заявитель)\r\n ",
		'TEXT_SIZE'=>$default_font_size,
		'COLSPAN'=>2,
		'PADDING_LEFT'=>1,
	),
	2=>array(
		'TEXT'=>'',
		'TEXT_SIZE'=>$default_font_size,
		'COLSPAN'=>2,
		'PADDING_LEFT'=>1,
	),
	4=>array(
		'TEXT'=>'Лицензия Участника № _______',
		'TEXT_SIZE'=>$default_font_size,
		'PADDING_LEFT'=>1,
	),
));
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

$table->addRow(array(
	0=>array(
		'TEXT'=>'Страна',
		'TEXT_SIZE'=>$default_font_size,
		'PADDING_LEFT'=>1,
	),
	1=>array(
		'TEXT'=>'',
		'TEXT_SIZE'=>$default_font_size,
		'PADDING_LEFT'=>1,
	),
	2=>array(
		'TEXT'=>'Адрес',
		'TEXT_SIZE'=>$default_font_size,
		'COLSPAN'=>3,
		'PADDING_LEFT'=>1,
	),
));

$table->addRow(array(
	0=>array(
		'TEXT'=>'e-mail',
		'TEXT_SIZE'=>$default_font_size,
		'PADDING_LEFT'=>1,
	),
	1=>array(
		'COLSPAN'=>2,
		'PADDING_LEFT'=>1,
	),
	3=>array(
		'TEXT'=>'Телефон',
		'TEXT_SIZE'=>$default_font_size,
		'COLSPAN'=>2,
		'PADDING_LEFT'=>1,
	),
));

$table->close();

$pdf->setY($pdf->getY()+2);

//третья сверху таблица - данные экипажа

$table = new tfpdfTable($pdf);
$table->initialize(array(44,50,28,78),$default_tbl_config);

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

_reqpdf_add_request_row($table,'ФАМИЛИЯ');
_reqpdf_add_request_row($table,'ИМЯ');
_reqpdf_add_request_row($table,'ОТЧЕСТВО');
_reqpdf_add_request_row($table,'ЛИЦО ДЛЯ КОНТАКТОВ');
_reqpdf_add_request_row($table,'КОНТАКТНЫЙ ТЕЛЕФОН');
_reqpdf_add_request_row($table,'E-MAIL');
_reqpdf_add_request_row($table,'ГОРОД');
_reqpdf_add_request_row($table,'СПОРТИВНОЕ ЗВАНИЕ, РАЗРЯД');
_reqpdf_add_request_row($table,"КАТЕГОРИЯ И № \r\nВОДИТ. УДОСТ-Я");
_reqpdf_add_request_row($table,"ПАСПОРТ\r\n(№, ДАТА ВЫДАЧИ)");
_reqpdf_add_request_row($table,'КАТЕГОРИЯ И № ЛИЦЕНЗИИ ВОДИТЕЛЯ');
_reqpdf_add_request_row($table,"ASN (НАФ)\r\nВЫДАВШАЯ ЛИЦЕНЗИЮ");
$table->addRow(array(
	0=>array(
		'TEXT'=>'Подписав эту заявку, участник и водители признают и обязуются выполнять все требования СК РАФ и иной регламентирующей документации РАФ, а также принимают на себя все риски и всю ответственность за возможные последствия своего участия в соревновании.',
		'TEXT_SIZE'=>$default_font_size,
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
$table->initialize(array(50,50,50,50),$default_tbl_config);

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
_reqpdf_add_auto_row($table,'ГОС. НОМЕРНОЙ ЗНАК','','МАРКА / МОДЕЛЬ','');
_reqpdf_add_auto_row($table,'ФИО ВЛАДЕЛЬЦА','','МИНИМАЛЬНЫЙ ВЕС','');
_reqpdf_add_auto_row($table,'','','МАРКА / МОДЕЛЬ КОЛЕС','');
for($i=1;$i<=2;$i++)
	_reqpdf_add_auto_row($table,'','','','');
_reqpdf_add_auto_row($table,"КАТЕГОРИЯ АВТОМОБИЛЯ\r\n (ТР1,ТР2,ТР3)",'',"ЗАЧЕТНАЯ ГРУППА\r\n(ТР1,ТР2,ТР3, АБСОЛЮТ И ДР.)");
$table->close();

//пятая сверху таблица - простенькая, необязательная реклама Организитора, находится без отступа от предыдущей

$table=new tfpdfTable($pdf);
$table->initialize(array(100,50,50),$default_tbl_config);
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

#$table = new tfpdfTable($pdf);


$pdf->Output();

function _reqpdf_add_request_row($table,$t1,$t2='',$t3=''){ //добавить стандартное поле в таблицу с экипажем. Там много полей одинаковых. $t1 - название поля, $t2 и $t3 - значения, для первого и второго водителя соответсвенно
	global $default_font_size;
#	if(_strlen($t1)<19)
		#$t1.="\r\n  ";
	$table->addRow(array(
		0=>array(
			'TEXT'=>$t1,
			'TEXT_SIZE'=>$default_font_size-2,
			'PADDING_LEFT'=>1,
		),
		1=>array(
			'TEXT'=>_reqpdf_append_n($t2),
			'TEXT_SIZE'=>$default_font_size-2,
			'COLSPAN'=>2,
			'PADDING_LEFT'=>1,
		),
		3=>array(
			'TEXT'=>_reqpdf_append_n($t3),
			'TEXT_SIZE'=>$default_font_size-2,
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
			'TEXT_SIZE'=>$default_font_size-1,
			'PADDING_LEFT'=>1,
		),
		2=>array(
			'TEXT'=>$t3,
			'TEXT_SIZE'=>$default_font_size-1,
			'PADDING_LEFT'=>1,
		),
		3=>array(
			'TEXT'=>_reqpdf_append_n($t4),
			'TEXT_SIZE'=>$default_font_size-1,
			'PADDING_LEFT'=>1,
		),
	));
}
function _reqpdf_append_n($t){
	if(_strlen($t<19))
		$t.="\r\n ";
	return $t;
}
