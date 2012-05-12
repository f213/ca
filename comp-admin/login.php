<?
//Copyright (c) 2012, Fedor Borshev <fedor9@gmail.com>
//
//Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require_once('../_includes/core.php');
require_once('_includes/custom_auth.php');

$back=_input_val('back');
if(!empty($_POST['login']) and !empty($_POST['pass'])){ //если заполнили форму, проверяем авторизацию
	$tt=test_authEX(addslashes($_POST['login']),addslashes($_POST['pass']));
	if($tt){ //если заебись, кладем куку
		if($custom_auth_enabled and strstr($tt,'{custom}')){
			setcookie('login',sha1($_POST['login']).'@hta');
			setcookie('pass',get_htpasswd_pw($custom_auth_file,$_POST['login']));
		}elseif(DB_TYPE!='custom' and function_exists('dbd_encrypt')){
			list($l,$p)=dbd_encrypt(addslashes($_POST['login']));
			setcookie('login',$l);
			setcookie('pass',$p);
		}	
		if(!$back)
			header("Location: index.php");
		else
			header("Location: $back");
		die('hooj');
	}else
		print_auth_window($back);
}else
	print_auth_window($back);	



function print_auth_window($back){
?><html>
<head>
	<title>Авторизация</title>	
	<meta http-equiv="content-type" content="text/html; charset=utf-8">	
	<link href=i/main_style.css rel=stylesheet type=text/css>
</head>
<body>
	<script langunage=JavaScript>
	function check_form(){
		if(document.getElementById('login').value.length<3){
			alert('Введите логин!');
			return false;
		}
		if(document.getElementById('pass').value.length<3){
			alert('Введите пароль!');
			return false;
		}
		return true;
	}
	</script>
<form method = post action = "login.php" onSubmit="return check_form();">
<input type = hidden name = back value = "<?=$back?>">
<table width=100% height=100%>
	<tr>
		<td align=center valign=center>
			<table class=body cellspacing=0 cellpadding=0>
				<tr class=head><td colspan=2>Введите логин и пароль</td></tr>
				<tr>
					<td>Логин: </td> <td><input type = text name = login id = login size=10 maxlength=20></td>
				</tr>
				<tr>
					<td>Пароль: </td> <td><input type = password name = pass id = pass size=10 maxlength=20></td>
				</tr>
				<tr><td colspan=2 align=center>
					<input type = submit value = OK>
					<img src="i/sp.gif" width=10>
					<input type = reset value = Сбросить>
				</td></tr>	
			</table>
		</td>
	</tr>
	</table>
</form>
</body>
</html>

<?
	die();
}
function test_authEX($username,$pass){
	global $custom_auth_enabled,$custom_auth_file;
	if($custom_auth_enabled)
		if(check_htpasswd($custom_auth_file,$username,$pass))
			return "{custom}$username@$";
	if(DB_TYPE!='custom' and function_exists('dbd_check_auth'))
		return dbd_check_auth($username,$pass);
	return null;
}	
