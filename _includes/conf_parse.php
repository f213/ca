<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//
//работа с конфигурационными файлами conf/ca.ini и conf/categories.ini
//
//Конфигурационные файлы подбираются по путям, указанным в начале кода. К этим путям есть возможность добавить свои, сделав перед инклюдом этого файла (ну или core.php) дефайн CA_INI_SEARCH_PATH или CATEGORIES_INI_SEARCH_PATH для файлов ca.ini и categories.ini соответсвенно.
//
//все функции парсинга каких-то специфических данных должны быть разнесены по отдельным процедурам, т.к. тут в дальнейшем может быть сложный код, чтобы он не пересекался с тем что ниже. Этот файл инклюдится самым первым
//
//Для работы есть небольшие помошники
//	cfg_has(param_name) - есть ли вообще такой параметр в конфиге
//	сfg_val(param_name) - узнать значение параметра, если параметра нет, будет null
//	cfg_bool(param_name) - узнать значение булинового параметра. Если параметра нет будет false
//
//	default_yes(param_name) - возвращает истину, если булинового параметра нет в конфиге, или он задан, и равен истине. Или 1, или yes или true. Определение истины - целиком вотчина parse_ini_file(). Если параметр задан и равен ложи, то вернется ложь.
//	default_no(param_name) - наоборот. Возвращает истину только если параметр есть в конфиге, и значит истину.
//	
// *param_name* везде указывается как 'секция.имя'. То есть main.param1 вовзращает значение параметра с названием param1 в сеции main. Параметры без секций не поддерживаются.
//
//XXX: ОТНОСИСЬ ВНИМАТЕЛЬНО К ПЕРЕМЕННЫМ, НЕ ОБЪЯВЛЯЙ ЗДЕСЬ НИЧЕГО ЛИШНЕГО!!!1111
//

define('CONFIG_PATH_ACCEPT_REGEX','/^[\_\-\/\\\:\.a-z\d]+$/i');
define('MAGIC_MAX_RAF_RACE_TYPES',10);

$_ca_ini=array('conf/ca.ini','../conf/ca.ini','../../conf/ca.ini','/etc/ca/ca.ini','/usr/local/etc/ca/ca.ini');
$_cat_ini=array('conf/categories.ini','../conf/categories.ini','../../conf/categories.ini','/etc/ca/categories.ini','/usr/local/etc/ca/categories.ini');
$_official_ini=array('conf/official.ini','../conf/official.ini','../../conf/official.ini','/etc/ca/official.ini','/usr/local/etc/ca/official.ini');

if(defined('CA_INI_SEARCH_PATH'))
	$_ca_ini=array_merge(array(CA_INI_SEARCH_PATH),$_ca_ini);
if(defined('CATEGORIES_INI_SEARCH_PATH'))
	$_cat_ini=array_merge(array('CATEGORIES_INI_SEARCH_PATH'),$_cat_ini);


$_raw_ca_conf=parse_ini_file(_get_ini($_ca_ini),true);
$_raw_cat_conf=parse_ini_file(_get_ini($_cat_ini),true);
$_raw_official_conf=parse_ini_file(_get_ini($_official_ini),true);

//
//разбираем настройки БД
//
//Здесь мы проверяем только наличие необходимых опций. Их правильность будут проверять соответсвующие скрипты
if(!cfg_has('db.type') or cfg_val('db.type')=='custom')
	define('DB_TYPE','custom');
elseif(cfg_val('db.type')=='phpbb2')
	define('DB_TYPE','phpbb2');
elseif(cfg_val('db.type')=='phpbb3')
	define('DB_TYPE','phpbb3');

if(!defined('DB_TYPE'))
	die('ca.ini: в секции db указан неподдерживаемый тип базовой БД!');

$dbopts=array();
switch(DB_TYPE){
case 'custom':
	if(!cfg_has('db_custom.host') or !cfg_val('db_custom.host') or !preg_match('/^[\.a-z\d]+$/',cfg_val('db_custom.host')))
		die('Не указан хост для custom базовой БД');
	$dbopts['host']=cfg_val('db_custom.host');

	if(!cfg_has('db_custom.user') or !cfg_val('db_custom.user'))
		die('Не указано имя пользователя для custom базовой БД');
	$dbopts['user']=cfg_val('db_custom.user');

	if(!cfg_has('db_custom.pass'))
		die('Не указан пароль для custom базовой БД');
	$dbopts['pass']=cfg_val('db_custom.pass');

	if(!cfg_has('db_custom.db_name') or !cfg_val('db_custom.db_name') or !preg_match('/^[\_\-\.a-z\d]+$/',cfg_val('db_custom.db_name')))
		die('Не указано имя БД для custom базовой БД');
	$dbopts['db']=cfg_val('db_custom.db_name');
	break;
case 'phpbb2': 
	if(!cfg_has('db_phpbb2.root') or !cfg_val('db_phpbb2.root') or !preg_match(CONFIG_PATH_ACCEPT_REGEX,cfg_val('db_phpbb2.root')))
		die('Не указан путь к установке движка форума для базовой БД phpbb2');
	$dbopts['root']=cfg_val('db_phpbb2.root');
	if(!preg_match('/\/$/',$dbopts['root']))
		$dbopts['root'].='/';
	break;
case 'phpbb3':
	if(!cfg_has('db_phpbb3.root') or !cfg_val('db_phpbb3.root') or !preg_match(CONFIG_PATH_ACCEPT_REGEX,cfg_val('db_phpbb3.root')))
		die('Не указан путь к установке движка форума для базовой БД phpbb3');
	$dbopts['root']=cfg_val('db_phpbb3.root');
	if(!preg_match('/\/$/',$dbopts['root']))
		$dbopts['root'].='/';
	if(!cfg_has('db_phpbb3.auth_required_group_id') or !(int)cfg_val('db_phpbb3.auth_required_group_id'))
		die('Не указан id группы доступа для базовой БД phpbb3');
	$dbopts['auth_required_group_id']=(int)cfg_val('db_phpbb3.auth_required_group_id');
	break;
}

//
//разбираем настройки категорий из отдельного файла categories.ini
//
$cat_name=get_categories_list(); //массив $cat_name используется практически везде и содержит в себе id и имена активных категорий
define('_CATEGORIES',sizeof($cat_name)); //константа _CATEGORIES используется тоже практически везде, где перебираются категории. По большому счету это странный атавизм
if(!_CATEGORIES)
	die("Ошибка в конфигурационном файле - не указано ни одной категории!");


$detailed_legend_cat=get_detailed_legend_cat(); //список категорий, у которых работа с точками линейной гонки - подробоная, т.е. ввод точек происходит не количество, а по одной взятой точке, как при навигации

$winch_cat=get_winch_cat(); //список категорий, у которых следует особо отмечать наличие лебедки

//
//Получаем "официальные" данные гонки
//
$OFFICIAL_DATA=get_official_data();

//
//секция MAIN 
//
if(cfg_val('main.custom_auth'))
	define('CUSTOM_AUTH',cfg_val('main.custom_auth')); //авторизация по apache-like файлам .htpasswd

if(cfg_val('main.no_auth_key'))
	define('NO_AUTH_KEY',cfg_val('main.no_auth_key')); //ключ для внешнего забора данных
if(!cfg_val('main.comp_short_name')){
	die("ca.ini: необходимо указать краткое имя соревнования (main.comp_short_name)!");
}else{
	define('CA_LOGO_NAME',cfg_val('main.comp_short_name')); //имя логотипа, для подстановки в официальные документы
	$tpl_dir=CA_LOGO_NAME; //папка со специфичными шаблонами
}

if(!cfg_has('main.next_sn_algorithm') or cfg_val('main.next_sn_algorithm')==1) //по умолчанию да
	define('FANCY_NEXT_START_NUMBER',1); //более умный алгоритм выдачи следующего бортового номера при регистрации

if(default_no('main.show_seconds'))
	define('SHOW_SECONDS_EVERYWHERE',1); //показ нулевых секунд, если их нет.


if(!cfg_has('main.nav_points_mult') or !(int)cfg_val('main.nav_points_mult'))
	define('GR_POINTS_MULT',60); //множитель баллов для типа gr-gps
else
	define('GR_POINTS_MULT',(int)cfg_val('main.nav_points_mult'));

//
//секция REQUESTS
//
if(default_no('requests.show_size'))
	define('USE_SIZE',true); //показывать ли в заявках, поле для записи размера одежды. наверное действует уже не везде

if(default_yes('requests.wheel_size'))
	define('CA_WHEEL_SIZE',1); //давать ли возможность указать размер колес
else
	define('CA_WHEEL_SIZE',0);

if(CA_WHEEL_SIZE and default_yes('requests.wheel_size_required'))
	define('CA_REQUIRE_WHEEL_SIZE',1); //в случае, если даем возможность указать размер колес, эта опция заставляет юзера в обязательном порядке вводить размер колес.

if(default_no('requests.portal'))
	define('CA_TRACK_PORTAL',1); //поддержка признака "портальные мосты", который можно указывать на техкомиссии
else
	define('CA_TRACK_PORTAL',0); //почему-то раньше было задано 0, а не undefined. Пусть и дальше так будет.


if(default_yes('requests.winch')){
	define('CA_TRACK_WINCH',1); //поддержка признака "лебедка"
}else{ //если нету поддержки лебедки, тогда очистим на всякий случай массив $winch_cat
	define('CA_TRACK_WINCH',0);
	$winch_cat=array();
}

if(default_yes('requests.can_change_cat_id_after_registration'))
	define('CAN_CHANGE_CAT_ID_AFTER_REGISTER',true); //можно ли менять категорию после регистрации, но до генерации стартовой ведомости
else
	define('CAN_CHANGE_CAT_ID_AFTER_REGISTER',false);

if(default_no('requests.can_change_cat_id_after_start_list_generation'))
	define('CAN_CHANGE_CAT_ID_AFTER_START_LIST',true); //можно ли менять категорию, когда стартовая ведомость уже сгенерирована.
else
	define('CAN_CHANGE_CAT_ID_AFTER_START_LIST',false);

if(default_no('requests.dummy_declarant_pilot_equal'))
	define('CA_DECLARANT_PILOT_EQUAL',1); //заглушка - заявитель всегда равен имени пилота
if(cfg_has('requests.dummy_single_country'))
	define('CA_DUMMY_SINGLE_COUNTRY',cfg_val('requests.dummy_single_country')); //заглушка - единая страна для всех участников
//
//секция INTERFACE
//

if(default_yes('interface.auto_print'))
	define('AUTO_PRINT','yes'); //автоматический вызов окошка браузера с выбором принтера при инклюде print_header

if(default_yes('interface.track_edits'))
	define('ADM_TRACK_EDITS',true); //напоминалка зарегистрировать отредактированные заявки

if(!cfg_has('interface.search_hotkey') or !cfg_val('interface.search_hotkey') or !preg_match('/^[a-z0-9\+]+$/i',cfg_val('interface.search_hotkey')))
	define('CA_SEARCH_HOTKEY','ctrl+f'); //хоткей для активации виджета поиска

else
	define('CA_SEARCH_HOTKEY',cfg_val('interface.search_hotkey'));

if(default_no('interface.or_show_site_filters'))
	define('CA_REQUESTS_SHOW_SITE_FILTERS',1); //показывать ли в online-requests фильтры, предназначенные чисто для сайта, антиспамерское подтверждение, и т.д.

if(CA_TRACK_WINCH and default_yes('interface.winch_auto_detect'))
	define('CA_WINCH_AUTODETECT',1); //пытаться ли угадать наличие ледеки в процессе техкомисси по добавленным пенализациям. Техкомиссару так на одну кнопку меньше приходится нажимать.


if(!cfg_has('interface.r_kps_per_row') or !(int)cfg_val('interface.r_kps_per_row'))
	define('RESULTS_KPS_PER_ROW',8); //количество точек, выводящихся в ряд при вводе результатов ориентирования
else
	define('RESULTS_KPS_PER_ROW',(int)cfg_val('interface.r_kps_per_row'));

if(default_yes('interface.time_input_helper'))
	define('USE_TIME_INPUT_HELPER',true); //включить ли автоматическую расставлялку знаков ':' при вводе времени
if(default_no('interface.show_raf_score'))
	define('CA_SHOW_RAF_SCORE',true);

if(default_no('pdf.enabled')){
	define('CA_PDF_ENABLED',1); //включена генерация pdf
	if(!cfg_has('pdf.tfpdf_table_path'))
		die('ca.ini: включен режим регенации pdf, но не задан путь к классам tfpdf <a href="http://www.interpid.eu/fpdf-table">http://www.interpid.eu/fpdf-table</a>!');
	define('CA_PDF_TFPDF_TABLE_PATH',rtrim(cfg_val('pdf.tfpdf_table_path'),'\/\\'));
	if(!file_exists(CA_PDF_TFPDF_TABLE_PATH.'/myfpdf-table.php'))
		die('ca.ini: не найден класс tfpdf по указанному пути: '.CA_PDF_TFPDF_TABLE_PATH);
	if(default_yes('pdf.request_enabled')){
		define('CA_PDF_REQUEST_ENABLED',1);
		if(cfg_has('pdf.request_font'))
			define('CA_PDF_REQUEST_FONT',cfg_val('pdf.request_font'));
		else
			define('CA_PDF_REQUEST_FONT','FreeSans.ttf');
		if(!strlen(CA_PDF_REQUEST_FONT) or !file_exists('../3dparty/fpdf/font/unifont/'.CA_PDF_REQUEST_FONT))
			die('Не найден шрифт для печати заявок (pdf.request_font) по пути 3dparty/fpdf/font/unifont/'.CA_PDF_REQUEST_FONT);
	}
	if(default_yes('pdf.allowed_requests_enabled')){
		define('CA_PDF_ALLOWED_REQUESTS_ENABLED',1);
		if(cfg_has('pdf.allowed_requests_font'))
			define('CA_PDF_ALLOWED_REQUESTS_FONT',cfg_val('pdf.allowed_requests_font'));
		else
			define('CA_PDF_ALLOWED_REQUESTS_FONT','FreeSans.ttf');
		if(!strlen(CA_PDF_ALLOWED_REQUESTS_FONT) or ! file_exists('../3dparty/fpdf/font/unifont/'.CA_PDF_ALLOWED_REQUESTS_FONT))
			die('Не найден шрифт для печати списка допущенных участников (pdf.allowed_requests_font) по пути /3dparty/fpdf/font/unifont/'.CA_PDF_ALLOWED_REQUESTS_FONT);
	}
	if(default_yes('pdf.results_comp_enabled')){
		define('CA_PDF_RESULTS_COMP_ENABLED',1);
		if(cfg_has('pdf.results_comp_font'))
			define('CA_PDF_RESULTS_COMP_FONT',cfg_val('pdf.RESULTS_COMP_font'));
		else
			define('CA_PDF_RESULTS_COMP_FONT','FreeSans.ttf');
		if(!strlen(CA_PDF_RESULTS_COMP_FONT) or ! file_exists('../3dparty/fpdf/font/unifont/'.CA_PDF_RESULTS_COMP_FONT))
			die('Не найден шрифт для печати списка допущенных участников (pdf.RESULTS_COMP_font) по пути /3dparty/fpdf/font/unifont/'.CA_PDF_RESULTS_COMP_FONT);
	}
	if(default_yes('pdf.results_su_enabled')){
		define('CA_PDF_RESULTS_SU_ENABLED',1);
		if(cfg_has('pdf.results_su_font'))
			define('CA_PDF_RESULTS_SU_FONT',cfg_val('pdf.RESULTS_SU_font'));
		else
			define('CA_PDF_RESULTS_SU_FONT','FreeSans.ttf');
		if(!strlen(CA_PDF_RESULTS_SU_FONT) or ! file_exists('../3dparty/fpdf/font/unifont/'.CA_PDF_RESULTS_SU_FONT))
			die('Не найден шрифт для печати списка допущенных участников (pdf.RESULTS_SU_font) по пути /3dparty/fpdf/font/unifont/'.CA_PDF_RESULTS_SU_FONT);
	}
	if(default_no('pdf.points_list_enabled')){
		define('CA_PDF_POINTS_LIST_ENABLED',1);
		if(cfg_has('pdf.points_list_font'))
			define('CA_PDF_POINTS_LIST_FONT',cfg_val('pdf.POINTS_LIST_font'));
		else
			define('CA_PDF_POINTS_LIST_FONT','FreeSans.ttf');
		if(!strlen(CA_PDF_POINTS_LIST_FONT) or ! file_exists('../3dparty/fpdf/font/unifont/'.CA_PDF_POINTS_LIST_FONT))
			die('Не найден шрифт для печати списка допущенных участников (pdf.POINTS_LIST_font) по пути /3dparty/fpdf/font/unifont/'.CA_PDF_POINTS_LIST_FONT);
	}


}
function get_categories_list(){
	global $_raw_cat_conf;
	$cfg=$_raw_cat_conf;
	$ret=array();
	$prev_cat_id=0;
	foreach($cfg as $key=>$value){
		$q=array();
		preg_match('/(\d+)$/',$key,$q);
		$cat_id=(int)$q[1];
		if(!$cat_id)
			continue;
		if(array_key_exists($cat_id,$ret) and $ret[$cat_id])
			continue;
		if($cat_id-$prev_cat_id!=1) //номера категорий должны быть по порядку
			continue;
		$cat_name=$value['name'];
		if(!$cat_name or !strlen($cat_name))
			continue;
		$ret[$cat_id]=$cat_name;
		$prev_cat_id=$cat_id;
	}
	return $ret;
}
function get_detailed_legend_cat(){
	global $cat_name,$_raw_cat_conf;
	$cfg=$_raw_cat_conf;
	$ret=array();
	foreach($cat_name as $id=>$name)
		if(array_key_exists("cat$id",$cfg) and array_key_exists('detailed_legend',$cfg["cat$id"]) and $cfg["cat$id"]['detailed_legend']=='1')
			$ret[]=$id;
	return $ret;
		
}
function get_winch_cat(){
	global $cat_name,$_raw_cat_conf;
	$cfg=$_raw_cat_conf;
	$ret=array();
	foreach($cat_name as $id=>$name)
		if(array_key_exists("cat$id",$cfg) and array_key_exists('can_have_winch',$cfg["cat$id"]) and $cfg["cat$id"]['can_have_winch']=='1')
			$ret[]=$id;
	return $ret;
}
function get_official_data(){
	global $_raw_official_conf;
	$cfg=$_raw_official_conf;
	$ret=array();
	$ret['name']=$cfg['official']['name'];
	$ret['comp_type']=$cfg['official']['comp_type'];
	$ret['rukogon']=$cfg['official']['rukogon'];
	$ret['secretary']=$cfg['official']['secretary'];
	$ret['tech_commissar']=$cfg['official']['tech_commissar'];
	$ret['date']=$cfg['official']['date'];
	$ret['place']=$cfg['official']['place'];
	$ret['ksk']=array();
	foreach($cfg['ksk'] as $key=>$value)
		$ret['ksk'][]=$value;
	$ret['raf_race_types']=array();
	for($i=1;$i<=MAGIC_MAX_RAF_RACE_TYPES;$i++)
		if($cfg['raf_race_types'][$i])
			$ret['raf_race_types'][$i]=$cfg['raf_race_types'][$i];
	return $ret;
}

function cfg_val($val){
	global $_raw_ca_conf;
	$cfg=$_raw_ca_conf;
	if(!$val or !preg_match('/^[^\.]+\.[^\.]+$/',$val))
		return null;
	if(!$cfg)
		return null;
	$q=array();
	preg_match('/^([^\.]+)\.([^\.]+)$/',$val,$q);
	$v1=$q[1]; $v2=$q[2];
	if(!$cfg[$v1][$v2] or !strlen($cfg[$v1][$v2]))
		return null;
	return $cfg[$v1][$v2];
}
function cfg_has($val){
	global $_raw_ca_conf;
	$cfg=$_raw_ca_conf;
	if(!$val or !preg_match('/^[^\.]+\.[^\.]+$/',$val))
		return null;
	if(!$cfg)
		return null;
	$q=array();
	preg_match('/^([^\.]+)\.([^\.]+)$/',$val,$q);
	$v1=$q[1]; $v2=$q[2];
	if(!array_key_exists($v1,$cfg))
		return false;
	if(!array_key_exists($v2,$cfg[$v1]))
		return false;
	return true;
}
function cfg_bool($val){
	if(cfg_val($val))
		return 1;
	else
		return 0;
}
function default_yes($val){
	if(!cfg_has($val) or cfg_bool($val))
		return true;
	else
		return false;
}
function default_no($val){
	if(cfg_bool($val))
		return true;
}
function _get_ini($paths){
	foreach($paths as $f)
		if(is_readable($f))
			return $f;
	die('conf_parse.php:_get_ini(): Не найден файл настроек '.$paths[0]);
}
