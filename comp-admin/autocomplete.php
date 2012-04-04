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
require('_includes/nocache.php');

$comp_id=_input_val('comp_id');
if(defined('CURRENT_COMP'))
	$comp_id=CURRENT_COMP;

if(!$comp_id)
	die("Не указан id соревнования!");

header("Content-Type: text/plain; charset=utf-8");

$q=_input_val('q');

if(!$q)
	exit;
if(isset($_GET['cities'])){
	$cities=array();
	$res=query_eval("SELECT DISTINCT(city) FROM $compreq_dbt;");
	while($row=mysql_fetch_row($res))
		$citiies[]=stripslashes($row[0]);
	$res=query_eval("SELECT DISTINCT(PilotCity) FROM $compreq_dbt;");
	while($row=mysql_fetch_row($res))
		$cities[]=stripslashes($row[0]);
	$res=query_eval("SELECT DISTINCT(NavigatorCity) FROM $compreq_dbt;");
	while($row=mysql_fetch_row($res))
		$cities[]=stripslashes($row[0]);
	foreach(array_unique($cities) as $city)
		if(_stristr(_substr($city,0,_strlen($q)),$q)) //здесь не стоит использовать регулярки в целях безопасности
			print "$city\n";
}
if(isset($_GET['ranks'])){
	$ranks=array();
	$res=query_eval("SELECT DISTINCT(attr_val) FROM $compreq_ext_dbt WHERE attr_name LIKE '%_rank';");
	while($row=mysql_fetch_row($res))
		$ranks[]=stripslashes($row[0]);
	foreach($ranks as $rank)
		if(_stristr(_substr($rank,0,_strlen($q)),$q))
			print "$rank\n";
}

