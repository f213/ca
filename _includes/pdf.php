<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


//Функции для работы с pdf
require_once("../3dparty/fpdf/tfpdf.php");
if(!defined('CA_PDF_ENABLED'))
	die('pdf.php: работа с pdf не включена в конфигурационном файле!');
require_once(CA_PDF_TFPDF_TABLE_PATH.'/myfpdf-table.php');
require_once(CA_PDF_TFPDF_TABLE_PATH.'/class.tfpdftable.php');

$default_tbl_config=array(
	'TABLE'=>array(
		'BORDER_COLOR'=>array(0,0,0),
	),
	'HEADER'=>array(
		'TEXT_FONT'=>'times',
	),
	'ROW'=>array(
		'TEXT_FONT'=>'times',
		'BORDER_COLOR'=>array(0,0,0),
		'TEXT_ALIGN'=>'L', //LRC - left, right, centergt

	),
);

$default_font_size=9;


function raf_pdf_header($pdf,$print_hueta=0){
	global $default_tbl_config;
	global $OFFICIAL_DATA;
	$pdf->Image('i/raf.png',5,9);
	$pdf->setFont('times','',5.5);
	$pdf->setXY(140,2); 
	$pdf->Write(5,'ОРГАНИЗОВАНО В СООТВЕТСТВИИ');
	$pdf->setXY(140,5);
	$pdf->Write(5,'СО СПОРТИВНЫМ КОДЕКСОМ РАФ');

	$pdf->setXY(50,8); $pdf->setFont('times','',13.5);
	$pdf->Write(5,'Российская автомобильная федерация');
	$pdf->setXY(85,12); $pdf->setFont('times','',11.5);
	$pdf->Write(5,$OFFICIAL_DATA['comp_type']);

	$pdf->setXY(50,18); $pdf->setFont('times','B',15);
	$pdf->Write(5,$OFFICIAL_DATA['name']);

	$pdf->SetXY(5,34); $pdf->setFont('times','',7.5); $pdf->Write(5,$OFFICIAL_DATA['place']);
	$pdf->setXY(130,34); $pdf->Write(5,$OFFICIAL_DATA['date']);
	if($print_hueta){
		$pdf->setY(18);
		$table = new tfpdfTable($pdf);
		$tbl_config=$default_tbl_config;
		$tbl_config['TABLE']['TABLE_ALIGN']='R';
		$table->initialize(array(15,13),$tbl_config);
		$table->addRow(array(
			0=>array(
				'ROWSPAN'=>2,
			),
			1=>array(
				'TEXT'=>" \r\n \r\n ",
				'TEXT_SIZE'=>18,
			),
		));
		$table->addRow(array(
			1=>array(
				'TEXT'=>" \r\n ",
				'TEXT_SIZE'=>18,
			),
		));
		$table->close();
	}

	return 40;
}
