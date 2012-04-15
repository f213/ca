<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require_once('../_includes/core.php');
require_once('_includes/request_functions.php');
require_once('_includes/started_functions.php');
require_once('_includes/export_results.php');
require_once('_includes/init_category.php');
require_once('_includes/disq.php');
require_once('_includes/auth.php');

$comp_id=(int)$_GET['comp_id'];
if(!$comp_id)
	die('некорректно указан id соревнования!');

$cat=array();
$cat=get_started_categories($comp_id); //получаем список стартовавших категорий
$filters_str="comp_id=$comp_id";

if($_GET['f_category']){
	$f_category=(int)$_GET['f_category'];
	if(!$cat[$f_category]['type'])
		die("Задан неправильный номер категории ($f_category)");
	$filters_str.="&f_category=$f_category";
}	
$flag=(int)$_GET['flag'];
if($flag){
//формируем фильтры для возврата взад
	if($_GET['f_category'])
		$filters_str.="&f_category={$_GET['f_category']}";
	
	switch($flag){
		case 1: //редактирование времени старта
			list($h,$m,$s)=parse_user_time($_GET['time']);
			$time=$h*3600+$m+60+$s;
			$start_number=(int)$_GET['start_number'];
			if(!$start_number)
				die("Нерпавильно указан участнег");
			$time=$h*3600+$m*60+$s;
			if(!$time)
				die("технические ограничения - указывать время 00:00:00 для старта нельзя");
			update_start_time($comp_id,$time,$start_number);
			header("Location: start_list.php?$filters_str&just_edited=$start_number");
			
			exit;
			break;
		case 2: //перемешать нафиг ведомость с заданным интервалом (только для gps и gr-gps)
			die('disabled'); //тут был код, который я отключил, потому что "старт очередями" (флаг 6) работает лучше и он универсальный
			break;
		case 3: //сдвинуть на одну позицию вверх
			$prev_id=(int)$_GET['prev_id']; //помни, что id здесь это start_number
			if(!$prev_id)
				die("Не задан id предыдущего участнега!");
			$item_id=(int)$_GET['item_id'];
			if(!$item_id)
				die("Не задан id участнега!");
			$prev_time=get_start_time($comp_id,$prev_id);
			$now_time=get_start_time($comp_id,$item_id);
			if(!$prev_time)
				die("Ошибка получения предыдущего времени старта");
			if(!$now_time)
				die("Ошибка получения текущего времени старта");

			update_start_time($comp_id,$now_time,$prev_id);
			update_start_time($comp_id,$prev_time,$item_id);

			header("Location: start_list.php?$filters_str&just_edited=$item_id");
			exit;
			break;
		case 4: //сдвинуть на одну позицию вниз
			$next_id=(int)$_GET['next_id']; //помни, что id здесь это start_number
			if(!$next_id)
				die("Не задан id следущего участнега!");
			$item_id=(int)$_GET['item_id'];
			if(!$item_id)
				die("Не задан id участнега!");
			$next_time=get_start_time($comp_id,$next_id);
			$now_time=get_start_time($comp_id,$item_id);
			if(!$next_time)
				die("Ошибка получения следующего времени");
			if(!$now_time)
				die("Ошиюбка получения текущего времени");

			update_start_time($comp_id,$now_time,$next_id);
			update_start_time($comp_id,$next_time,$item_id);

			header("Location: start_list.php?$filters_str&just_edited=$item_id");
			exit;
			break;

		case 5: //переделать старт в одновременный (легенда only)
			$cat_id=$f_category;
			if(get_type_by_cat_id($comp_id,$cat_id)!='legend')
				die('Переделка старта в одновременный возможна только при легенде');
			$res=query_eval("SELECT MIN(b.start_time) FROM $compres_dbt a, $complegres_dbt b WHERE a.comp_id=$comp_id AND b.comp_id=$comp_id AND a.cat_id=$cat_id AND b.start_number=a.start_number;");
			if(!mysql_num_rows($res))
				die("В стартовой ведомости нет ни одного участника заданной категории ($cat_id)");
			$row=mysql_fetch_row($res);
			$start_time=(int)$row[0]; //это - самое маленькое время старта. Надо выставить его для всех участников заданной категории
			foreach(get_started_numbers($comp_id,$cat_id) as $start_number)
				update_start_time($comp_id,$start_time,$start_number);
			header("Location: start_list.php?$filters_str&just_synced=true");
			exit;
			break;
		case 6: //старт очередями. Пока тока по легенде, возможно будет работать и на gps.
			$cat_id=$f_category;

			$col=(int)$_GET['col'];
			if(!$col)
				die('Не указано количество машин, стартующих за раз!');
			$interval=(int)$_GET['interval'];
			if(!$interval)
				die('Не указан интервал старта!');

			$type=get_type_by_cat_id($comp_id,$cat_id);
			
			//узнаем самое маленькое время старта, смотрим в таблицу в зависимости от категории
			if($type=='legend')
				$res=query_eval("SELECT MIN(b.start_time) FROM $compres_dbt a, $complegres_dbt b WHERE a.comp_id=$comp_id AND b.comp_id=$comp_id AND a.cat_id=$cat_id AND b.start_number=a.start_number;");

			if($type=='gps' or $type=='gr-gps')
				$res=query_eval("SELECT MIN(b.start_time) FROM $compres_dbt a,$compgpstime_dbt b WHERE a.comp_id=$comp_id AND b.comp_id=$comp_id AND a.cat_id=$cat_id AND b.start_number=a.start_number;");

			//запросы должны отдавать одни и те же данные
			if(!mysql_num_rows($res))
				die("В стартовой ведомости отсутсвуют участники заданной категории ($cat_id: тип соревнования - $type)");
			$row=mysql_fetch_row($res);
			$start_time=(int)$row[0]; //это самое маленькое время старта в категории
			$cnt=0;
			
			foreach(get_started_numbers($comp_id,$cat_id) as $start_number){
				if($cnt>=$col){
					$start_time+=$interval*60;
					$cnt=0;
				}
				update_start_time($comp_id,$start_time,$start_number);
				$cnt++;
			}
			header("Location: start_list.php?$filters_str&just_queued=true");
			exit;
			break;
		case 7: //очистка стартовой ведомости
			$cat_id=$f_category;
			init_category($comp_id,$cat_id);
			header("Location: ".append_rnd("start_list.php?comp_id=$comp_id&cleaned_up=$cat_id"));
			exit;
			break;
	}
}
$prev_id=0;

if($f_category){
	$need_tk=false;
	if(_cat_var($comp_id,$f_category,'need_tk'))
		$need_tk=true;
	$tpl_queue_start_link=append_rnd("start_list.php?comp_id=$comp_id&f_category=$f_category&flag=6");
	if($cat[$f_category]['type']=='legend'){
		$res=query_eval("SELECT a.id,a.start_number,a.start_time,b.portal, b.winch FROM $complegres_dbt a, $compres_dbt b WHERE a.comp_id=$comp_id AND b.comp_id=$comp_id AND a.start_number = b.start_number AND b.cat_id=$f_category ORDER BY start_time ASC;");
		$tpl_sync_start_link=append_rnd("start_list.php?comp_id=$comp_id&f_category=$f_category&flag=5");
	}

	if($cat[$f_category]['type']=='gps' or $cat[$f_category]['type']=='gr-gps'){
		$res=query_eval("SELECT a.start_number, a.start_time,b.portal, b.winch FROM $compgpstime_dbt a, $compres_dbt b WHERE a.comp_id=$comp_id AND b.comp_id=$comp_id AND a.start_number = b.start_number AND b.cat_id=$f_category ORDER BY start_time ASC;");
	}
	//запросы должны отдавать одно и тоже независимо от типа соревнования
	$tk_cnt=$total_cnt=0;
	while($row=mysql_fetch_assoc($res)){
		$id=$start_number=(int)$row['start_number'];
		$item_output[$id]['start_time']=format_user_hms_time((int)$row['start_time'],$_null_sec_bool);
		$item_output[$id]['start_number']=$start_number;
		$item_output[$id]=get_full_request_data($comp_id,num2req($comp_id,$start_number),$item_output[$id]);
		$item_output[$id]['req_link']=append_rnd("online_requests_add.php?comp_id=$comp_id&item_id=".num2req($comp_id,$start_number));
		if($prev_id){
			$item_output[$id]['move_up_link']=$_SERVER['PHP_SELF']."?$filters_str&flag=3&prev_id=$prev_id&item_id=$id";
			$item_output[$prev_id]['move_down_link']=$_SERVER['PHP_SELF']."?$filters_str&flag=4&next_id=$id&item_id=$prev_id";
		}
		$prev_id=$id;
		if($need_tk){
			$item_output[$id]['tk_is_passed']=false;
			if(tk_is_passed($comp_id,$start_number)){
				$item_output[$id]['tk_is_passed']=true;
				$item_output[$id]['tk_is_relative']=tk_relative($comp_id,$start_number);
				$tk_cnt++;
			}
		}
		if(defined('CA_TRACK_PORTAL') and CA_TRACK_PORTAL){
			$item_output[$id]['have_portal']=false;
			if($row['portal']=='yes')
				$item_output[$id]['have_portal']=true;
		}
		if(defined('CA_TRACK_WINCH') and CA_TRACK_WINCH and in_array($f_category,$winch_cat)){
			$item_output[$id]['have_winch']=false;
			if($row['winch']=='yes')
				$item_output[$id]['have_winch']=true;
		}
		if(has_disq($start_number))
			list($item_output[$id]['disq'],$junk)=disq_type($comp_id,$start_number);
		$total_cnt++;
	}
	if($item_output){
		$tpl_print_link=append_rnd("print/start_list.php?flag=1&cat_id=$f_category&comp_id=$comp_id");
		$tpl_clean_link=append_rnd("start_list.php?comp_id=$comp_id&flag=7&f_category=$f_category");
		if($need_tk)
			$tpl_tkproto_link=append_rnd("print/tk.php?comp_id=$comp_id&cat_id=$f_category");
	}
	//проверяем, есть ли хоть кто-то, кто прошел техкомиссию. нужно вдруг она не нужна..
	$tpl_tkproto_disabled=true;
	if($need_tk){
		$start_numbers_str='';
		foreach($item_output as $cur_id=>$q)
			$start_numbers_str.="$cur_id,";
		$start_numbers_str=trim($start_numbers_str,',');
		$res=query_eval("SELECT * FROM $comptk_dbt WHERE comp_id=$comp_id AND start_number IN($start_numbers_str);");
		if(mysql_num_rows($res))
			$tpl_tkproto_disabled=false;
	}
}else{ //просто зашли на страницу, категорию еще не выбирали. Здесь у нас появляется возможность сгенерировать стартовую ведомость.
	$categories_without_start_list=array();
	for($i=1;$i<=_CATEGORIES;$i++)
		if(get_type_by_cat_id($comp_id,$i)!='' and !$cat[$i])
			$categories_without_start_list[$i]=get_type_by_cat_id($comp_id,$i);
		
}
$tpl_need_tk=$need_tk;
$just_edited=0;
if($_GET['just_edited'])
	$just_edited=(int)$_GET['just_edited'];
$title="Стартовая ведомость";
require('admin_header.php');
require('_templates/start_list.phtml');
