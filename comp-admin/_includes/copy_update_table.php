<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//Это быдлокод для того, чтобы создать в таблице записи из нее же, при этом изменив один параметр. Работает через временные таблицы(совместимость, бля!)
//
//пример использования:
//
//$res=copy_update_table($dbt,"WHERE comp_id=$old_comp_id","SET comp_id=$new_comp_id");
//
//$where Что селектим из таблицы, передать вида WHERE comp_id=
//$update Что меняем в результатах селекта, передавать вида SET comp_id=
//
//Возвращает хеш, соответсвия старых и новых id, типа $res[$old_id]=>$new_id
//
//
function copy_update_table($dbt,$where,$update,$id_name='id'){
	//Здеся нам нужно добавить в таблицу данные из нее же, но с изменением одного параметра, но алгоритм должен работать не зависимо от полей в БД, дабы я сюда не лазил при каждом изменении структуры.
	//Для этого мы создаем временную табилцу, в которой меняем этот самый параметр.
	//
	//узнаем макс id в старой таблице
	query_eval("LOCK TABLES $dbt WRITE;");
	$res=query_eval("SELECT MAX($id_name) FROM $dbt;");
	if(!mysql_num_rows($res)){
		query_eval("UNLOCK TABLES");
		die("copy_update_table:Не могу получить макс ID(id_name=$id_name)!");
	}
	$row=mysql_fetch_row($res);
	$new_id=(int)$row[0]; //new_id - максимальный ID в старой таблице
	//копируем данные
	$tmp_dbt=rnd_str(12); //функция rnd_str лежит в core.php

	query_eval("CREATE TEMPORARY TABLE $tmp_dbt SELECT * FROM $dbt $where");
	query_eval("UPDATE $tmp_dbt $update;");
	//а теперь нам надо подменить значение индекса ID в новой таблице, дабы когда будем запихивать его обратно не возникло пересечений. Лучше я пока ничего не придумал.
	$res=query_eval("SELECT $id_name FROM $tmp_dbt;");
	$old_ids=array();
	$results=array();
	while($row=mysql_fetch_row($res))
		$old_ids[]=(int)$row[0];
	foreach($old_ids as $old_id){
		$new_id++;
		query_eval("UPDATE $tmp_dbt SET $id_name=$new_id WHERE $id_name=$old_id;");
		$results[$old_id]=$new_id;
	}
	//теперя все айдишники новые, идут по порядку. то есть можно все запхать в старую таблицу.
	query_eval("INSERT INTO $dbt SELECT * FROM $tmp_dbt;");
	query_eval("UNLOCK TABLES;");
	return $results;
}

