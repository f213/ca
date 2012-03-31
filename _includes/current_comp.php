<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//
//Узнаем текущее соревнование. В $comp_dbt может быть несколько соревнований. Если одно - вертаем его как активное. Если несколько - проверяем, только одно из них должно быть активным, т.е. иметь current==yes
//
function get_current_comp(){
	global $comp_dbt;
	$res=query_eval("SELECT ID,current FROM $comp_dbt;");
	$comp_count=mysql_num_rows($res);
	if(!$comp_count)
		die("get_current_comp(): в БД нет ни одного соревнования");
	if($comp_count==1){ //у нас есть одно соревнование, оно и активно
		$row=mysql_fetch_row($res);
		return (int)$row[0];
	}else{ //у нас есть несколько соревнований
		$we_have_current=false;
		$current_comp_id=0;
		while($row=mysql_fetch_assoc($res)){
			$comp_id=(int)$row['ID'];
			$is_current=false;
			if($row['current']=='yes')
				$is_current=true;
			if($is_current){
				if($we_have_current)
					die("get_current_comp(): ошибка в БД - указано несколько текущих соревнований!");
				$current_comp_id=$comp_id;
				$we_have_current=true;
			}
		}
		if(!$we_have_current)
			die("get_current_comp(): ошибка БД - присутсвует несколько соревнований, но ни одно не отмечено как активное");
		return $current_comp_id;
	}
}
define('CURRENT_COMP',get_current_comp());
