<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//Печать РАФовского списка допущенных участников

require_once('../_includes/pdf.php');

function print_pdf_allowed_requests($item_output){
	global $OFFICIAL_DATA; //официальные данные гонки
	global $default_tbl_config; //настройки табилцы по умолчанию ../_includes/pdf.php
	global $default_font_size; //размер шрифта, от которого отталкиваются другие ../_includes/pdf.php
	
	if(!defined('CA_PDF_ALLOWED_REQUESTS_ENABLED') or !CA_PDF_ALLOWED_REQUESTS_ENABLED)
		die('Печать списка разрешенных участников (pdf) выключена в настройках');

	$pdf = new tFPDF();
	$pdf->Open();
	$pdf->SetAutoPageBreak(true, 20);
	$pdf->SetMargins(20, 20, 20);
	$pdf->AddPage();
	$pdf->AliasNbPages();
	$pdf->AddFont('times','',CA_PDF_ALLOWED_REQUESTS_FONT,true);
	$pdf->addFont('times','B',CA_PDF_ALLOWED_REQUESTS_FONT,true);
	$pdf->setY(raf_pdf_header($pdf,0));

	$pdf->setFont('times','B',$default_font_size+10);
	$pdf->setX(55); $pdf->Write(5,'Список допущенных водителей');
	$pdf->setY($pdf->getY()+9);
	//основная большая таблица
	$table=new tfpdfTable($pdf); 
	$table->initialize(array(6,12,34,18,34,20,19,13,19,20),$default_tbl_config);
	$table->addRow(array(
		0=>array(
			'TEXT'=>'№',
			'TEXT_SIZE'=>$default_font_size,
		),
		1=>array(
			'TEXT'=>'Старт. номер',
			'TEXT_SIZE'=>$default_font_size,
		),
		2=>array(
			'TEXT'=>"Участник\r\nГород",
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
		),
		3=>array(
			'TEXT'=>"Лицензия\r\nЗаявителя",
			'TEXT_SIZE'=>$default_font_size,
		),
		4=>array(
			'TEXT'=>"1 водитель\r\n2 водитель",
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
		),
		5=>array(
			'TEXT'=>"Лицензии\r\nводителя",
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
		),
		6=>array(
			'TEXT'=>"Город\r\nГород",
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
		),
		7=>array(
			'TEXT'=>"Спорт\r\nЗвание",
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
		),
		8=>array(
			'TEXT'=>"Марка\r\nа/м",
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
		),
		9=>array(
			'TEXT'=>"Зачетная\r\nгруппа",
			'TEXT_SIZE'=>$default_font_size,
		),
	));
	$cnt=0;
	foreach($item_output as $key=>$value){
		$row1=array();

		for($i=0;$i<=10;$i++)
			$row1[$i]['TEXT_SIZE']=$default_font_size-3;

		$row1[0]['TEXT']=++$cnt;
		$row1[1]['TEXT']=$key; $row1[1]['TEXT_ALIGN']='C'; $row1[1]['TEXT_TYPE']='B';
		$row1[2]['TEXT']=$value['declarant_name_official']."\r\n".$value['declarant_city_capitalized'];
		$row1[3]['TEXT']=$value['declarant_license_type'].' '.$value['declarant_license_num'];
		$row1[4]['TEXT']=$value['pilot_name_official']."\r\n".$value['navigator_name_official'];
		$row1[5]['TEXT']=$value['pilot_license_type'].' '.$value['pilot_license_num']."\r\n".
			$value['shturman_license_type'].' '.$value['shturman_license_num'];
		$row1[6]['TEXT']=$value['pilot_city_capitalized']."\r\n".$value['navigator_city_capitalized'];
		$row1[7]['TEXT']=$value['pilot_rank']."\r\n".$value['shturman_rank'];
		$row1[8]['TEXT']=$value['auto_brand'];
		$row1[9]['TEXT']=$value['cat_name'];

		$table->addRow($row1);
	}
	$table->close();
	
        //подсчет итогов по категориям
        $item_categories=array();
        foreach($item_output as $key=>$value){
                if(!$item_categories[$value['cat_id']])
                        $item_categories[$value['cat_id']]['name']=$value['cat_name'];
                $item_categories[$value['cat_id']]['cnt']++;
        }

        $pdf->setFont('times','',$default_font_size+1);
        $pdf->setY($pdf->getY()+3);
        foreach($item_categories as $value)
                $pdf->Write(5,'Итого по классу '.$value['name'].': '.$value['cnt']."\r\n");
        $pdf->setY($pdf->getY()+3);
        raf_pdf_footer($pdf);

	
	$pdf->output();
}
