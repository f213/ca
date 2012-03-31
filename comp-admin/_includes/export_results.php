<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

/*код для эскпорта результатов в различные форматы
 * АХТУНГ!!!!! Этот код отключает вывод ошибок, потому что разработчики пехапе - ебучие пидарасы, которым блять насрать на обратную совместимость.
*/
function export_results_xls($item_output,$category){
	global $cat_name; //имена категорий
	error_reporting(0);
	$my_cat_name=_export_results_translit($cat_name[$category]);

	require_once 'Spreadsheet/Excel/Writer.php';

	$xls=new Spreadsheet_Excel_Writer();
	$xls->setVersion(8); //без этого русский не работает

	$bold=&$xls->addFormat(); //формат для заголовков
	$bold->setBold();
	
	$main_format=&$xls->addFormat(); //дефолтный формат

	$place_format=&$xls->addFormat();
	$place_format->setHAlign('right');
	$place_format->setBold();

	$xls->send('Results_'.date('Ymd_Hi_').$my_cat_name.'.xls');
	$sheet=&$xls->addWorksheet($my_cat_name);
	$sheet->setInputEncoding('UTF-8');
	$sheet->setFirstSheet();
	$header=array(
		'Место',
		'№',
		'1-й водитель',
		'2-й водитель',
		'Машина',
		'Госномер',
		'Трасса',
		'Время',
	);
	
	$sheet->writeRow(0,0,$header,$bold);
	$rowcnt=1;

	foreach($item_output as $key=>$value){
		$data=array();
		//место
		if($value['res'])
			$data[0]=$value['res'];
		else
			$data[0]=$key;
		//номер
		$data[1]=$value['start_number'];
		//пилот
		$data[2]=$value['pilot_name'];
		//штурман
		$data[3]=$value['navigator_name'];
		//машина
		$data[4]=$value['auto_brand'];
		//госномер
		$data[5]=$value['auto_number'];
		//трасса
		$data[6]=$value['total_time'];
		//время
		$data[7]=$value['final_time'];

		$sheet->writeRow($rowcnt++,0,$data);
	}
	$sheet->setColumn(0,0,5,$place_format);
	$sheet->setColumn(1,1,3);
	$sheet->setColumn(2,3,35);
	$sheet->setColumn(4,4,12);
	$sheet->setColumn(5,5,15);
	$sheet->setColumn(6,7,7);

	$xls->close();

}


function can_export_xls(){

	if(!@include_once('Spreadsheet/Excel/Writer.php'))
		return 0;
	return 1;
}
function export_xls_descr(){
	return "Для экспорта в xls необходим пакет PEAR Spreadsheet_Excel_Writer";
}

function _export_results_translit($str){
	$tr = array(
		"А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
		"Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
		"Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
		"О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
		"У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
		"Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
		"Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
		"в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
		"з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
		"м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
		"с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
		"ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
		"ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
	);
	return strtr($str,$tr);
}
