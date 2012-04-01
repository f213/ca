<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//функции для работы со строками, пока не все, а те, которые пригождались


function _stristr($str, $search){ //based on kohana code
	if ($search == '')
		return $str;

	$str_lower = mb_strtolower($str,'utf-8');
	$search_lower = mb_strtolower($search,'utf-8');
	preg_match('/^(.*?)'.preg_quote($search_lower, '/').'/s', $str_lower, $matches);

	if (isset($matches[1]))
		return substr($str, strlen($matches[1]));

	return FALSE;
}
function _substr($str,$start,$length=0){ 
	if($length==0)
		$length=_strlen($str);
	return mb_substr($str,$start,$length,'utf-8');
}
function _strlen($str){
	return mb_strlen($str,'utf-8');
}
function _strtolower($str){
	return mb_convert_case($str,MB_CASE_LOWER,'utf-8');
}
function _ucfirst($str){
	return mb_convert_case($str,MB_CASE_TITLE,'utf-8');
}
function _strtoupper($str){
	return mb_convert_case($str,MB_CASE_UPPER,'utf-8');
}
