(function($) {

$.keypad.regional['ru'] = {
	buttonText: '...', buttonStatus: 'Открыть экранную клавиатуру',
	closeText: 'Закрыть', closeStatus: 'Закрыть',
	clearText: 'Очистить', clearStatus: 'Очистить поле ввода',
	backText: 'Назад', backStatus: 'Стереть предыдущий символ',
	alphabeticLayout: $.keypad.qwertzAlphabetic,
	fullLayout: $.keypad.qwertzLayout,
	isAlphabetic: $.keypad.isAlphabetic,
	isNumeric: $.keypad.isNumeric,
	isRTL: false};
$.keypad.setDefaults($.keypad.regional['ru']);

})(jQuery);
