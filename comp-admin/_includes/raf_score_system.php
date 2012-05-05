<?php
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//подсчет очков по поощрительной системе РАФ. Таблицу и формулу я взял из ежегодника РАФ 2011 года (раздел 1 - общие документы), 219 страница.
define(FIRST_PLACE_SCORE_DEFAULT,100);
$FIRST_PLACE_SCORE_EXCEPTIONS=array(
	9=>90,
	8=>80,
	7=>70,
	6=>60,
	5=>50,
);
define(MIN_PLACE_SCORE,5);
function raf_score($place,$num_started){
	global $FIRST_PLACE_SCORE_EXCEPTIONS;
	if($num_started<MIN_PLACE_SCORE)
		return 0;
	$first_place_score=100;
	if($place>$num_started)
		return null;
	if(!empty($FIRST_PLACE_SCORE_EXCEPTIONS[$num_started]))
		$first_place_score=$FIRST_PLACE_SCORE_EXCEPTIONS[$num_started];
	else
		$first_place_score=FIRST_PLACE_SCORE_DEFAULT;
	$score=$first_place_score - ($first_place_score-1)/(sqrt($num_started)-1)*(sqrt($place)-1);
	return (int)$score;
}
