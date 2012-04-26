<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//Печать РАФовской таблицы результатов одного СУ

require_once('../_includes/pdf.php');

function print_pdf_points_list($item_output,$cat_name){
	global $OFFICIAL_DATA; //официальные данные гонки
	global $default_tbl_config; //настройки табилцы по умолчанию ../_includes/pdf.php
	global $default_font_size; //размер шрифта, от которого отталкиваются другие ../_includes/pdf.php

	if(!defined('CA_PDF_POINTS_LIST_ENABLED') or !CA_PDF_POINT_LIST_ENABLED)
		die('Печать списка разрешенных взятых КП (pdf) выключена в настройках');

	$pdf = new tFPDF('L');
	$pdf->Open();
	$pdf->SetAutoPageBreak(true, 20);
	$pdf->SetMargins(10, 10, 10);
	$pdf->AddPage();
	$pdf->AliasNbPages();
	$pdf->AddFont('times','',CA_PDF_POINTS_LIST_FONT,true);
	$pdf->addFont('times','B',CA_PDF_POINTS_LIST_FONT,true);
	$pdf->setY(raf_pdf_header($pdf,0));
	$pdf->setFont('times','B',$default_font_size+10);
	$pdf->setX(20); 
	$pdf->MultiCell(0,10,"Список взятых КП в классе\r\n$cat_name",0,'C');
	$pdf->setY($pdf->getY()+9);

	$init_row=array();
	$init_row[0]=6;
	foreach($item_output['points'] as $key=>$value)
		$init_row[]=4;
	
	//основная большая таблица
	$table=new tfpdfTable($pdf); 
	$table->initialize($init_row,$default_tbl_config);

	$row1=array();
	$row1[0]=array();
	$i=1;
	foreach($item_output['points'] as $point_name)
		$row1[$i++]=array(
			'TEXT'=>$point_name,
			'TEXT_TYPE'=>'B',
			'TEXT_SIZE'=>$default_font_size-6,
		);

	$table->addRow($row1);
	
	foreach($item_output['data'] as $key=>$value){
		$row1=array();
		$row1[0]=array(
			'TEXT'=>$key,
			'TEXT_SIZE'=>$default_font_size-4,
			'TEXT_TYPE'=>'B',
			'TEXT_ALIGN'=>'C',
		);
		$i=1;
		foreach($value as $point_data)
			$row1[$i++]=array(
				'TEXT'=>$point_data,
				'TEXT_SIZE'=>$default_font_size-2,
				'TEXT_ALIGN'=>'C',
			);
		$table->addRow($row1);

	}

	$table->close();
	$pdf->output();

}
	
