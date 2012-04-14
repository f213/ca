<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

/* Наименования пилота и штурмана. 
 * Этот файл сделан для того, чтобы для разных гонок, и разных media можно было называть пилота и штурмана разными именами. Пока есть следующие media:
 * 	'ca' - основное media, наименование в системе управления. Если media не задано, или пустое, поле везде отключается.
 * 	'request' - заявки. Если не задано - в заявках не печатается
 * 	'print' - прочие места для печати. Если не задано - не печатается.
 * 	'ext_attr' - масив со список дополнительных атрибутов. Должны пересекаться с разрешенными дополнительными аттрибутами, расположенными в файле req_ext_data.php
 * Для пилота обязательно задавать минимум media ca, иначе пиздец.
 * Встроенная функция check_name() проверяет необходимость печати столбца.
 * В дальнейшем возможно сюда добавятся еще какие-то люди, к примеру заявители.
 */
define('__PEOPLE_NAMES__',1);
$_people_names['pilot']=array(
	'ca'=>'1-й водитель',
	'request'=>'1-й водитель(заявитель)',
	'print'=>'1-й водитель',
	'ext_attr'=>array(
		'addr',
		'birthday',
		'email',
		'passport_series',
		'passport_number',
		'passport_given_who',
		'passport_given_when',
		'license_type',
		'license_num',
		'rank',
	),
);

$_people_names['shturman']=array(
	'ca'=>'2-й водитель',
	'request'=>'2-й водитель',
	'print'=>'2-й водитель',
	'ext_attr'=>array(
		'addr',
		'birthday',
		'email',
		'passport_series',
		'passport_number',
		'passport_given_who',
		'passport_given_when',
		'license_type',
		'license_num',
		'rank',
	),
);

function check_name($name,$where){
	global $_people_names;
	if(!$_people_names[$name]['ca'] or !strlen($_people_names[$name]['ca']))
		return false;
	if(!$_people_names[$name][$where] or !strlen($_people_names[$name][$where]))
		return false;
	return true;
}

?>
