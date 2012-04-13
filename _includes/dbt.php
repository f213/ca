<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

//Имена таблиц системы
//
//TABLE_PREFIX специфичен для базовой БД и приходит из нужного dbd
//
$comp_dbt=TABLE_PREFIX.'CA_Competitions'; //списки соревнований и опции. Типы соревнований по категориям лежат в отдельной таблице, для обратной совместимости.
$compcatvar_dbt=TABLE_PREFIX.'CA_CompCatVar'; //параметры категорий
$compreg_dbt=TABLE_PREFIX.'CompRegister'; //было так, оставил, а ваще вроде где-то используеца
$compreq_dbt=TABLE_PREFIX.'CA_CompRequests'; //списки учаснегов. Там же онлайн-заявки
$compres_dbt=TABLE_PREFIX.'CA_CompResults'; //таблица с ОБЩИМИ результатами. Отсюда берутся данные при генерации стартовой ведомости. Данные сюда заносятся во время регистрации в лагере
#$compstart_dbt=TABLE_PREFIX.'CompStart'; //таблица используется тока для легенды
$complegres_dbt=TABLE_PREFIX.'CA_CompLegend_Results';
$compgps_dbt=TABLE_PREFIX.'CA_CompGPS'; //таблица с GPS точками
$compgpsres_dbt=TABLE_PREFIX.'CA_CompGPS_Points'; //таблица со списками взятых точек
$compgpstime_dbt=TABLE_PREFIX.'CA_CompGPS_Time'; //таблица с временем старта финиша GPS
$comppen_dbt=TABLE_PREFIX.'CA_CompPenalize'; //таблица с временными пенализациями
$compbont_dbt=TABLE_PREFIX.'CA_CompBonusTime'; //временные бонусы
$compbonp_dbt=TABLE_PREFIX.'CA_CompBonusPoints'; //бонусы по баллам (gps)
$compedit_dbt=TABLE_PREFIX.'CA_ADM_Edits'; //таблица с логами редактирования
$compgrdsu_dbt=TABLE_PREFIX.'CA_GRDSU'; //таблица с результатами "площади" для ЗЛ
$compbadnum_dbt=TABLE_PREFIX.'CA_BadNumbers'; //таблица с отбракованными бортовыми номерами, актуально тока на регистрации.
$comptk_dbt=TABLE_PREFIX.'CA_TK'; //таблица с результатами техкомиссии
$comptkreasons_dbt=TABLE_PREFIX.'CA_TK_Reasons'; //таблица с частоиспользуюмыми причинами пенализации на техкомиссии
$compfixedres_dbt=TABLE_PREFIX.'CA_ResultsFixed'; //таблица с фиксированными результатами одного соревнования. Служит для подведения итогов на гонках с несколькими СУ.
$compdisq_dbt=TABLE_PREFIX.'CA_CompDisq'; //таблица со снятиями с соревнований
$complegpoints_dbt=TABLE_PREFIX.'CA_Legend_Points'; //список возможных точек легенды
$complegdetails_dbt=TABLE_PREFIX.'CA_Legend_Details'; //промежуточная таблица - взятые точки на легенде
$comppp_dbt=TABLE_PREFIX.'CA_PP'; //полоса препятствий, прииск на ЗЛ
$compreq_ext_dbt=TABLE_PREFIX.'CA_Requests_ExtAttr'; //таблица с дополнительными полями заявок
