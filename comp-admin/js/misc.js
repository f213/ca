function in_array(what, where){ //не смеяца!!
	var a=false;
	for(var i=0; i<where.length; i++) {
		if(parseInt(what) == parseInt(where[i])) {
			a=true;
			break;
		}
	}	
	return a;

}
function _keynum(e){
	var keynum;
	var IE='\v'=='v'; //http://habrahabr.ru/blogs/javascript/50544/
	if(IE){
		keynum=e.keyCode;
	}else{
		keynum=e.which;
	}
	return keynum;
}

function only_numbers(e){
	var keynum=_keynum(e);
	if(!keynum){
		return;
	}
	return (keynum<48 || (keynum>=48 && keynum<= 57));
}
function only_numbers_time(e){ //отличается от предыдущей тем, что разрешает знак :
	var keynum=_keynum(e);
	if(!keynum){
		return;
	}
	return (keynum<48 || (keynum>=48 && keynum<= 57) || keynum==58);
}


function print_with_title(link,checkbox,def_title){
	if(!checkbox){
		window.open(link);
		return;
	}
	if(checkbox.attr('checked')){
		var title=prompt('Укажите заголовок',def_title);
		if(title){
			link=link+'&print_title='+title;
		}
	}
	window.open(link);


}
function kp(elem){ //включить keypad
	elem.keypad({
		showAnim: '',
		keypadOnly: false,
		layout: ['123' + $.keypad.BACK,
			'456' + $.keypad.CLEAR,
			'789' + $.keypad.CLOSE,
			$.keypad.SPACE + '0']
	});
}

function check_time(time){
	var h; var m; var s;
	if(time.match(/^\d+\:\d+$/)){
		var t=/^(\d+)\:(\d+)$/.exec(time);
		h=t[1]; m=t[2]; s=0;
	}else{
		if(time.match(/^\d+\:\d+\:\d+$/)){
			var t=/^(\d+)\:(\d+)\:(\d+)$/.exec(time);
			h=t[1]; m=t[2]; s=t[3];
		}else{
			return false;
		}
			
	}
	h=parseInt(h); m=parseInt(m); s=parseInt(s);
	if(h<0 || h>24){
		return false;
	}
	if(m<0 || m>60){
		return false;
	}
	if(s<0 || s>60){
		return false;
	}
	return true;
}
