<?php
chdir('../');
require_once('../_includes/core.php');
require_once('_includes/auth.php');
require_once('_includes/request_functions.php');
require_once('_includes/started_functions.php');

require('_includes/nocache.php');

require('pdf/allowed_requests.php');


$comp_id=(int)$_GET['comp_id'];
if(!$comp_id)
	die('не указан id соревнования');

$item_output=array();
$categories=array();
$res=query_eval("SELECT cat_id FROM $compcatvar_dbt WHERE is_official='yes';");
while($row=mysql_fetch_row($res))
	$categories[]=(int)$row[0];

foreach($categories as $cat_id){
	$res=query_eval("SELECT start_number FROM $compres_dbt WHERE comp_id=$comp_id AND cat_id=$cat_id ORDER BY start_number ASC;");
	while($row=mysql_fetch_row($res)){
		$start_number=(int)$row[0];
		if(_cat_var($comp_id,$cat_id,'need_tk') and !tk_is_passed($comp_id,$start_number))
			continue;
		$request_id=num2req($comp_id,$start_number);
		if(!$request_id)
			continue;
		$item_output[$start_number]=get_full_request_data($comp_id,$request_id);
	}
}
print_pdf_allowed_requests($item_output);
