$(document).ready(function () {

	$('.borderflow').borderFlow({borderWidth: 8});
	$('#login-submit').click(function () {
		$('#login-box').data('borderFlow').playSimple({duration:10000});
	});

});