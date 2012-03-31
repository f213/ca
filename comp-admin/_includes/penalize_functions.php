<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

function add_pen($comp_id,$start_number,$min,$reason,$source='normal'){

	global $comppen_dbt;
	global $admin_user;
	$comp_id=(int)$comp_id;
	$start_number=(int)$start_number;
	$min=(int)$min;
	$reason=addslashes($reason);
	$source=addslashes($source);
	if(!$comp_id or !$start_number or !$min or !$reason)
		return false;
	query_eval("REPLACE INTO $comppen_dbt SET start_number='$start_number', comp_id=$comp_id, time='$min', reason='$reason', author='$admin_user', source='$source';");
	return mysql_insert_id();
}
function del_pen($comp_id,$id){
	global $comppen_dbt;
	$comp_id=(int)$comp_id;
	$id=(int)$id;
	if(!$comp_id or !$id)
		return false;
	query_eval("DELETE FROM $comppen_dbt WHERE id=$id AND comp_id=$comp_id LIMIT 1;");
	return true;
}
function clear_pen($comp_id,$start_number,$source_type='all'){
	global $comppen_dbt;
	$comp_id=(int)$comp_id;
	$start_number=(int)$start_number;
	if(!$comp_id or !$start_number)
		return false;
	if($source_type=='all'){
		query_eval("DELETE FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number='$start_number';");
	}else{
		$source_type=addslashes($source_type);
		query_eval("DELETE FROM $comppen_dbt WHERE comp_id=$comp_id AND start_number='$start_number' AND source='$source_type';");
	}
	return true;
}
?>
