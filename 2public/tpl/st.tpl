<h3><tmpl_var name = cat_name></h3>

<table width="100%" border="1" cellspacing="0">
<tbody><tr>
	<td width="1%"><b>#</b></td>
	<td width="1%" align="center"><b>Р‘РѕСЂС‚</b></td>
	<td><b>Р­РєРёРїР°Р¶</b></td>
	<td><b>РњР°С€РёРЅР°</b></td>
	<td><b>РўРѕС‡РєРё</b></td>
	<td><b>РўСЂР°СЃСЃР°</b></td>
	<td><b>Р’СЂРµРјСЏ</b></td>
</tr>
<tmpl_loop name = main>
<tr>
	<td width="1%"><b><tmpl_var name = place></b></td>
	<td width="1%" align="center"><tmpl_var name = start_number></td>
	<td><tmpl_var name = pilot_name>, <tmpl_var name = navigator_name></td>
	<td><tmpl_var name = auto_brand></td>
	<td><tmpl_var name = gps_points_sum></td>
	<td><tmpl_var name = total_time></td>
	<td><tmpl_var name = final_time></td>
</tr>
</tmpl_loop>
</tbody>
</table>

<br /><br />
