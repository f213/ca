<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require('_includes/nocache.php');
if(empty($comp_id) or !$comp_id)
	$comp_id=CURRENT_COMP;

$active_comp_types=array();
$res=query_eval("SELECT DISTINCT(`type`) FROM $compcatvar_dbt WHERE comp_id=$comp_id;");
if(mysql_num_rows($res))
	while($row=mysql_fetch_row($res))
		$active_comp_types[$row[0]]=1;

$__admin_header_show_tk=true;
$res=query_eval("SELECT * FROM $compres_dbt WHERE comp_id=$comp_id;");
$registered_uch_kol=mysql_num_rows($res);

$res=query_eval("SELECT cat_id FROM $compcatvar_dbt WHERE comp_id=$comp_id AND need_tk='yes';");
if(!$registered_uch_kol or !mysql_num_rows($res))
	$__admin_header_show_tk=false;

$draw_detailed_legend=false;
if(array_key_exists('legend',$active_comp_types) and $detailed_legend_cat and sizeof($detailed_legend_cat))
	$draw_detailed_legend=_adm_header_check_draw_legend($comp_id);
	
$__admin_header_current_comp_name=comp_name(CURRENT_COMP);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="i/main_style.css" rel="stylesheet" type="text/css">
<title><?=$title?></title>
</head>
<body>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
<tr>
<td colspan=2><img src='i/sp.gif' height=3 width=1 alt=""></td>
</tr>
<tr>
<td><img src='i/sp.gif' height=1 width=100 alt=""></td>
<td width="100%">
<div class=top_menu<?=check_sel('competitions.php')?>><a href = "competitions.php">Список соревнований</a></div>
	<div class=top_menu<?=check_sel('online_requests.php')?>><a href = "online_requests.php?comp_id=<?=CURRENT_COMP?>">Участники</a></div>
	<?if($__admin_header_show_tk){?>
		<div class=top_menu<?=check_sel('tk.php')?>><a href = "tk.php?comp_id=<?=CURRENT_COMP?>">Техкомиссия</a></div>
	<?}?>
	<div class=top_menu<?=check_sel('start_list.php')?>><a href = "start_list.php?comp_id=<?=CURRENT_COMP?>">Стартовая ведомость</a></div>
	<?if(array_key_exists('gps',$active_comp_types) or array_key_exists('gr-gps',$active_comp_types)){?>
		<div class=top_menu<?=check_sel('gps-kp.php')?>><a href = "gps-kp.php?comp_id=<?=CURRENT_COMP?>">Точки GPS</a></div>
	<?}?>
	<?if($draw_detailed_legend){?>
		<div class=top_menu<?=check_sel('legend-kp.php')?>><a href = "legend-kp.php?comp_id=<?=CURRENT_COMP?>">Точки легенды</a></div>
	<?}?>
	<div class=top_menu<?=check_sel('penalize.php')?>><a href = "penalize.php?comp_id=<?=CURRENT_COMP?>">Пенализация</a></div>
	<div class=top_menu<?=check_sel('bonus.php')?>><a href = "bonus.php?comp_id=<?=CURRENT_COMP?>">Бонусы</a></div>
	<?if(array_key_exists('legend',$active_comp_types)){?>
		<div class=top_menu<?=check_sel('legend-results.php')?>><a href="legend-results.php?comp_id=<?=CURRENT_COMP?>">Ввод данных: линейка</a></div>
	<?}?>
	<?if(array_key_exists('gps',$active_comp_types) or array_key_exists('gr-gps',$active_comp_types)){?>
		<div class=top_menu<?=check_sel('gps-results.php')?>><a href = "gps-results.php?comp_id=<?=CURRENT_COMP?>">Ввод данных: ориентирование</a></div>
	<?}?>
	<?if(array_key_exists('gr-gps',$active_comp_types) and defined('GR_ENABLE_DRSU') and GR_ENABLE_DRSU){?>
		<div class=top_menu<?=check_sel('grdsu.php')?>><a href = "grdsu.php?comp_id=<?=CURRENT_COMP?>">ЗЛ: площадь</a></div>
	<?}?>
	<div class=top_menu<?=check_sel('results.php')?>><a href = "results.php?comp_id=<?=CURRENT_COMP?>">Результаты</a></div>
</td>
</tr></table>
<table width="100%">
<tr><td align=right>Текущее соревнование: <b><?=$__admin_header_current_comp_name?></b></td></tr>
<tr><td align=right>Зарегистрированный пользователь: <b><?=$admin_user?></b> (<a href = "logout.php">выход</a>)</td></tr>
</table>
<br><br>
<h1><?=$title?></h1>
<?
function check_sel($sc){
	$t=trim(basename($_SERVER['SCRIPT_NAME']));
	$t1=str_replace('_add','',$t);
	if($t1==$sc or $t==$sc)
		print "_select ";

}

function _adm_header_check_draw_legend($comp_id){ //проверяем, есть ли в текущем соревновании хоть одна категория, у которой включена опция подробной работы с легендой
	global $compcatvar_dbt,$detailed_legend_cat;
	$res=query_eval("SELECT cat_id FROM $compcatvar_dbt WHERE comp_id=$comp_id AND type='legend' ORDER BY cat_id ASC;");
	if(!mysql_num_rows($res))
		return false;
	while($row=mysql_fetch_row($res))
		if(in_array((int)$row[0],$detailed_legend_cat))
			return true;
	return false;
		
}
?>
