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

function print_pdf_results_su($item_output,$cat_name,$su_name,$anyone_taked_required=false){
	global $OFFICIAL_DATA; //официальные данные гонки
	global $default_tbl_config; //настройки табилцы по умолчанию ../_includes/pdf.php
	global $default_font_size; //размер шрифта, от которого отталкиваются другие ../_includes/pdf.php

	if(!defined('CA_PDF_RESULTS_SU_ENABLED') or !CA_PDF_RESULTS_SU_ENABLED)
		die('Печать списка результатов СУ (pdf) выключена в настройках');

	$pdf = new tFPDF();
	$pdf->Open();
	$pdf->SetAutoPageBreak(true, 20);
	$pdf->SetMargins(20, 20, 20);
	$pdf->AddPage();
	$pdf->AliasNbPages();
	$pdf->AddFont('times','',CA_PDF_RESULTS_SU_FONT,true);
	$pdf->addFont('times','B',CA_PDF_RESULTS_SU_FONT,true);
	$pdf->setY(raf_pdf_header($pdf,0));
	$pdf->setFont('times','B',$default_font_size+10);
	$pdf->setX(20); 
	$pdf->MultiCell(0,10,"Промежуточная классификация $su_name в классе\r\n$cat_name",0,'C');
	$pdf->setY($pdf->getY()+9);

	//основная большая таблица
	$table=new tfpdfTable($pdf); 
	$table->initialize(array(12,12,50,17,17,17,14,12,17,20),$default_tbl_config);
	$table->addRow(array(
		0=>array(
			'TEXT'=>'Место',
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_TYPE'=>'B',
		),
		1=>array(
			'TEXT'=>"Номер",
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
			'TEXT'=>"Старт",
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
			'TEXT_TYPE'=>'B',
		),
		4=>array(
			'TEXT'=>"Штраф КП",
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
			'TEXT_TYPE'=>'B',
		),
		5=>array(
			'TEXT'=>'Финиш',
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
			'TEXT_TYPE'=>'B',
		),
		6=>array(
			'TEXT'=>'Пенал.',
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
			'TEXT_TYPE'=>'B',
		),
		7=>array(
			'TEXT'=>'Итого КП',
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
			'TEXT_TYPE'=>'B',
		),
		8=>array(
			'TEXT'=>'Итог',
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
			'TEXT_TYPE'=>'B',
		),
		9=>array(
			'TEXT'=>'Очки',
			'TEXT_SIZE'=>$default_font_size,
			'TEXT_ALIGN'=>'C',
			'TEXT_TYPE'=>'B',
		),

	));
	foreach($item_output as $key=>$value){
		$row1=array();
		$place=$value['place'];
		$score=$value['raf_score'];
		if($value['dontfix']){
			$score=0;
			$place='н\з';
		}
		if(!(in_array('gps_untaken_time',$value)))
			$untaken_sum='00:00:00';
		else
			$untaken_sum=$value['gps_untaken_time'];

		if(!in_array('legend_kps',$value) or !strlen($value['legend_kps'])){
			$kps=$value['gps_points_sum'];
			if($anyone_taked_required)
				$kps.='('.$value['gps_required_points_sum'].')';
		}
		else
			$kps=$value['legend_kps'];
		for($i=0;$i<=9;$i++)
			$row1[$i]['TEXT_SIZE']=$default_font_size;

		$row1[0]['TEXT']=$place;
		$row1[1]['TEXT']=$value['start_number'];
		$row1[2]['TEXT']=$value['pilot_name_official']."\r\n".$value['navigator_name_official'];
		$row1[3]['TEXT']=$value['start_time'];
		$row1[4]['TEXT']=$untaken_sum;
		$row1[5]['TEXT']=$value['finish_time_hms'];
		$row1[6]['TEXT']=$value['pinok_time'];
		$row1[7]['TEXT']=$kps;
		$row1[8]['TEXT']=$value['final_time_hms'];
		$row1[9]['TEXT']=$score;
		
		$table->addRow($row1);
	}
	$table->close();
	
	raf_pdf_footer($pdf);
	$pdf->output();
}

