<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require_once('../_includes/core.php');
require_once('_includes/auth.php');

$title='Управление списком Соревнований';
$add_url='competitions_add.php';


$flag=_input_val('flag');
if($flag)
	switch($flag){
		case 1: //сделать активным
			$comp_id=(int)$_GET['comp_id'];
			if(!$comp_id)
				die("Ошибка - не указан ID соревнования!");
			$res=query_eval("SELECT * FROM $comp_dbt WHERE ID=$comp_id;");
			if(!mysql_num_rows($res))
				die("Ошибка - указан не действительный ID соревнования!");
			query_eval("UPDATE $comp_dbt SET `current`='no';");
			query_eval("UPDATE $comp_dbt SET `current`='yes' WHERE ID=$comp_id LIMIT 1;");
			header("Location: competitions.php?made_current=yes");
			die();
		break;


	}




//Просмотр списка соревнований

$res=query_eval("SELECT * FROM $comp_dbt WHERE 1 ORDER BY ID DESC;");

while($row=mysql_fetch_assoc($res)){
	$id=$row['ID'];
	$item_output[$id]['current']=false;
	if($row['current']=='yes')
		$item_output[$id]['current']=true;
	$item_output[$id]['name']=stripslashes($row['Name']);
	$item_output[$id]['edit_url']="competitions_add.php?item_id=$id";
	$item_output[$id]['del_url']="competitions.php?flag=2&item_id=$id";
	$item_output[$id]['make_active_url']=append_rnd("competitions.php?flag=1&comp_id=$id");

}			
require('admin_header.php');
require('_templates/competitions.phtml');

$res=mysql_query("SELECT DATABASE();");
if(mysql_num_rows($res)){
	$row=mysql_fetch_row($res);
	$db_name=$row[0];
}
$res=mysql_query("SELECT USER();");
if(mysql_num_rows($res)){
	$row=mysql_fetch_row($res);
	$db_user=$row[0];
}
print "<br><br><br><br>";
?><div align=center><?if(DB_TYPE=='phpbb3' or DB_TYPE=='phpbb2'){?>Forum-ver: <b><?=PHPBBVER?></b>, Forum-path: <b><?=$phpbb_root_path?></b>,<?}?>db-name: <b><?=$db_name?></b>, db-user: <b><?=$db_user?></b>
</div><?

	
		
