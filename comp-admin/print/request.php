<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

chdir('../');
require_once('../_includes/core.php');
require_once('_includes/auth.php');
require_once('_includes/online_requests.functions.php');
require_once('_includes/request_functions.php');

require('_includes/nocache.php');

$comp_id=(int)$_GET['comp_id'];
$request_id=(int)$_GET['request_id'];

if(!$comp_id)
	die('не указан id соревнования');
if(!$request_id)
	die('не указан id заявки');
$item_output=get_full_request_data($comp_id,$request_id);
$comp_name=comp_name($comp_id);
$res=query_eval("SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND request_id=$request_id;"); //получаем бортовой номер если есть.
if(mysql_num_rows($res)){
	$row=mysql_fetch_row($res);
	$item_output['start_number']=(int)$row[0];
}
if($_GET['pdf']){
	require_once('pdf/request.php');
	print_pdf_request($item_output);

}
if(defined('ADM_TRACK_EDITS') and ADM_TRACK_EDITS) //печать снимает отметку о редактировании
	cancel_tracked_edit($request_id);

	
require("_templates/print/requests/$tpl_dir/request.phtml");
