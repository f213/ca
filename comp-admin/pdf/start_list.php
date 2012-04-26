<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//Печать стартовой ведомости РАФ

require_once('../_includes/pdf.php');

function print_pdf_start_list($item_output,$cat_name,$su_name){
	global $OFFICIAL_DATA; //официальные данные гонки
	global $default_tbl_config; //настройки табилцы по умолчанию ../_includes/pdf.php
	global $default_font_size; //размер шрифта, от которого отталкиваются другие ../_includes/pdf.php

	if(!defined('CA_PDF_START_LIST_ENABLED') or !CA_PDF_START_LIST_ENABLED)
		die('Печать стартовой ведомости (pdf) выключена в настройках');

	$pdf = new tFPDF();
	$pdf->Open();
	$pdf->SetAutoPageBreak(true, 20);
	$pdf->SetMargins(20, 20, 20);
	$pdf->AddPage();
	$pdf->AliasNbPages();
	$pdf->AddFont('times','',CA_PDF_START_LIST_FONT,true);
	$pdf->addFont('times','B',CA_PDF_START_LIST_FONT,true);
	$pdf->setY(raf_pdf_header($pdf,0));
	$pdf->setFont('times','B',$default_font_size+10);
	$pdf->setX(20); 
	$pdf->MultiCell(0,10,"Стартовая ведомость $su_name \r\n$cat_name",0,'C');
	$pdf->setY($pdf->getY()+9);
	

	//основная большая таблица
	$table=new tfpdfTable($pdf); 
	$table->initialize(array(12,12,50,20),$default_tbl_config);
	$table->addRow(array(
		0=>array(
			'TEXT'=>'№',
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_TYPE'=>'B',
		),
		1=>array(
			'TEXT'=>"Старт\r\nНомер",
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_TYPE'=>'B',
		),
		2=>array(
			'TEXT'=>"Экипаж",
			'TEXT_SIZE'=>$default_font_isze,
			'TEXT_ALIGN'=>'C',
			'TEXT_TYPE'=>'B',
		),
		3=>array(
			'TEXT'=>"Время старта",
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
			'TEXT_TYPE'=>'B',
		),
	));
	$cnt=1;
	foreach($item_output as $key=>$value){
		$table->addRow(array(
			0=>array(
				'TEXT'=>$cnt++,
				'TEXT_SIZE'=>$default_font_size,
				'TEXT_ALIGN'=>'C',
			),
			1=>array(
				'TEXT'=>$value['start_number'],
				'TEXT_SIZE'=>$default_font_size,
				'TEXT_ALIGN'=>'C',
			),
			2=>array(
				'TEXT'=>$value['pilot_name_official']."\r\n".$value['navigator_name_official'],
				'TEXT_SIZE'=>$default_font_size,
			),
			3=>array(
				'TEXT'=>$value['start_time'],
				'TEXT_SIZE'=>$default_font_size,
				'TEXT_ALIGN'=>'C',
			),
		));

	}
	$table->close();

	raf_pdf_footer($pdf);
	$pdf->output();
	

}
