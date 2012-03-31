function time_input_helper(el,e){ //эта функция занимается тем, что сама, за юзера расставляет двоеточния при вводе времени
	var keynum;
	var IE='\v'=='v'; //http://habrahabr.ru/blogs/javascript/50544/
	if(IE){
		keynum=e.keyCode;
	}else{
		keynum=e.which;
	}

	if(keynum>=48 && keynum<= 57){ //нажали циферку
		if(el.val().match(/^\d{3,4}$/)){ //введены часы и одна или две цифры минут
			var q=/^(\d{2})(\d{1,2})$/.exec(el.val());
			el.val(q[1]+':'+q[2]);
		}
		if(el.val().match(/^\d{2}\:\d{3,4}$/)){ //ведены часы, минуты и одна или две цифры секунд
			var q=/^(\d+)\:(\d{2})(\d{1,2})$/.exec(el.val());
			el.val(q[1]+':'+q[2]+':'+q[3]);
		}
	}
	if(keynum==27){ //ESC очищает поле
		el.val('');
	}
}
function toggle_div(el,e){ //jquery,event
	if(el.hasClass('green-div-invisible')){
		el.removeClass('green-div-invisible')
			.addClass('green-div-visible');
		var posx=0;
		var posy=0;
		if (!e) var e = window.event;
		if (e.pageX || e.pageY){
			posx = e.pageX;
			posy = e.pageY;
		}else if (e.clientX || e.clientY) {
			posx = e.clientX;
			posy = e.clientY;
		}
		el.css('left',posx+'px');
		el.css('top',posy+'px');
		$(document).unbind('keyup','esc',function(){});
		$(document).bind('keyup','esc',function(e){
			toggle_div(el,e);
		});
		el.find('.first_focus').each(function(){
			$(this).focus() .select();
		});

	}else{
		el.removeClass('green-div-visible')
			.addClass('green-div-invisible');
		$(document).unbind('keyup','esc',function(){});
		doc_focus();
	}
}
