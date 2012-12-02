// Note you should disable Javascript Caching during Development!
// http://docs.moodle.org/dev/Developer_Mode

var click_handler = function() {
	// find out the number of the current question -> to find more than one (number), the regex would end with /g
	var num_subquestion = this.name.match(/[0-9]+/);
	// submit subsequent form (AJAX)
	var rqparams = $("form").serialize();
	rqparams += "&json_request_for_subquestion="+num_subquestion;
	rqparams += "&"+this.name+"="+this.value; // the button's name was not serialize()d
	$.post("type/rtypetask/ajaxiface.php", rqparams, function(data, textStatus, jqXHR) {
		try { // replace section with returned HTML
			var $section = $(data).find("section");
			//if(!$section.length) throw"Error:"+data);
			$('section[id="answers_for_question_'+num_subquestion+'"]').replaceWith($section);
			// re-Bind action listeners, note that ^ is a "begins with"-selector
			$('input[name="addanswerbtn_'+num_subquestion+'"]').click(click_handler);
			$('input[name^="delanswerbtn_'+num_subquestion+'_"]').click(click_handler);
			// change the num_answers field
			var selector = 'input[name="num_answers_'+num_subquestion+'"]',
				value = $(data).find(selector).val();
			$(selector).val(value);
		} catch (e) {
			console.log(e);
		}
	})
	return false;
}

$(document).ready(function() {
	$('input[name^="addanswerbtn_"]').click(click_handler);
	$('input[name^="delanswerbtn_"]').click(click_handler);
});
