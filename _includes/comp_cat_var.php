<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//работа с поклассовыми параметрами соревнования
//_cat_var($comp_id,$cat_id,$name,[$value]) - если $value задано - установить параметр. если нет - получить. В случае установки параметра возвращает не ноль, если все хорошо, ноль если все хуево
// Все возможные параметры надо описать в хеше $_allowed_category_variable_names



$_allowed_category_variable_names=array( //существующие параметры категорий
	'type'=>'string',
	'max_time'=>'int',
	'max_kp'=>'int',
	'need_tk'=>'bool',
);

function _cat_var($comp_id,$cat_id,$name,$value=null){
	global $_allowed_category_variable_names;
	global $compcatvar_dbt;
	$comp_id=(int)$comp_id;
	$cat_id=(int)$cat_id;
	if(!$comp_id or !$cat_id)
		return null;
	$val_type=$_allowed_category_variable_names[$name];
	if(!$val_type)
		return null;
	if(is_null($value)){ //получаем
		$res=query_eval("SELECT `$name` FROM $compcatvar_dbt WHERE `comp_id`='$comp_id' AND `cat_id`='$cat_id' AND `$name`!='unset' AND `$name`!=0");
		if(!mysql_num_rows($res))
			return _cat_var_null($val_type);
		$row=mysql_fetch_row($res);
		if($val_type=='string')
			return stripslashes($row[0]);
		elseif($val_type=='int')
			return (int)$row[0];
		elseif($val_type=='bool'){
			if($row[0]=='yes')
				return true;
			else
				return false;
		}
	}else{ //устанавливаем
		if($val_type=='string')
			$value=addslashes(htmlspecialchars($value));
		elseif($val_type=='int')
			$value=(int)$value;
		elseif($val_type=='enum'){
			if($value)
				$value='yes';
			else
				$value='no';
		}
		$res=query_eval("SELECT * FROM $compcatvar_dbt WHERE `comp_id`='$comp_id' AND `cat_id`='$cat_id'");
		if(!mysql_num_rows($res))
			return query_eval("REPLACE INTO $compcatvar_dbt SET `$name`='$value',`comp_id`='$comp_id',`cat_id`='$cat_id';");
		else
			return query_eval("UPDATE $compcatvar_dbt SET `$name`='$value' WHERE `comp_id`='$comp_id' AND `cat_id`='$cat_id' LIMIT 1;");

	}
}


function _cat_var_null($type){
	switch ($type){
	case 'string':
		return '';
	break;
	case 'int':
		return 0;
	break;
	case 'bool':
		return false;
	}
}
