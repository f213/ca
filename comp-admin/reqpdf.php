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

$table = new tfpdfTable($pdf);
$table->initialize(array(24,24,79,15,55),$default_tbl_config);
$table->addRow(array(
	0=>array(
		'TEXT'=>'Участник (Заявитель)',
		'TEXT_SIZE'=>$default_font_size,
		'COLSPAN'=>2,
	),
	2=>array(
		'TEXT'=>'',
		'TEXT_SIZE'=>$default_font_size,
		'COLSPAN'=>2,
	),
	4=>array(
		'TEXT'=>'Лицензия Участника № _______',
		'TEXT_SIZE'=>$default_font_size,
	),
));

$table->addRow(array(
	0=>array(
		'TEXT'=>'Представитель участника',
		'TEXT_SIZE'=>$default_font_size,
		'COLSPAN'=>2,
	),
	2=>array(
		'COLSPAN'=>3,
	),
));

$table->addRow(array(
	0=>array(
		'TEXT'=>'Страна',
		'TEXT_SIZE'=>$default_font_size,
	),
	1=>array(
		'TEXT'=>'',
		'TEXT_SIZE'=>$default_font_size,
	),
	2=>array(
		'TEXT'=>'Адрес',
		'TEXT_SIZE'=>$default_font_size,
		'COLSPAN'=>3,
	),
));

$table->addRow(array(
	0=>array(
		'TEXT'=>'e-mail',
		'TEXT_SIZE'=>$default_font_size,
	),
	1=>array(
		'COLSPAN'=>2,
	),
	3=>array(
		'TEXT'=>'Телефон',
		'TEXT_SIZE'=>$default_font_size,
		'COLSPAN'=>2,
	),
));

//close the table
$table->close();

$pdf->setY($pdf->getY()+2);

$table = new tfpdfTable($pdf);
$table->initialize(array(42,50,28,78),$default_tbl_config);

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
	),
));
_reqpdf_add_request_row($table,'ПОДПИСИ ВОДИТЕЛЕЙ');
$table->addRow(array(
	0=>array(
		'TEXT'=>"ПОДПИСЬ УЧАСТНИКА\r\n(С ОБЯЗАТЕЛЬНОЙ РАСШИФРОВКОЙ",
		'TEXT_SIZE'=>$default_font_size,
		'COLSPAN'=>2,
	),
	2=>array(
		'COLSPAN'=>2,
	),
));

$table->close();

//send the pdf to the browser
$pdf->Output();

function _reqpdf_add_request_row($table,$t1,$t2='',$t3=''){
	global $default_font_size;
#	if(_strlen($t1)<19)
		#$t1.="\r\n  ";
	$table->addRow(array(
		0=>array(
			'TEXT'=>$t1,
			'TEXT_SIZE'=>$default_font_size,
			'PADDING_LEFT'=>1,
		),
		1=>array(
			'TEXT'=>$t2."\r\n ",
			'TEXT_SIZE'=>$default_font_size,
			'COLSPAN'=>2,
		),
		3=>array(
			'TEXT'=>$t3."\r\n",
			'TEXT_SIZE'=>$default_font_size,
		),
	));
}
