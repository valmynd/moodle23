// Note you should disable Javascript Caching during Development!
// http://docs.moodle.org/dev/Developer_Mode

// http://stackoverflow.com/questions/2419749/get-selected-elements-outer-html
(function($) {
	$.fn.outerHTML = function() {
		return $(this).clone().wrap('<div></div>').parent().html();
	}
})(jQuery);

var click_on_addanswer = function() {
	var num_subquestion = this.name.substring("addanswerbtn_".length);
	var rqparams = $("form").serialize();
	rqparams += "&json_request_for_subquestion="+num_subquestion;
	rqparams += "&"+this.name+"="+this.value; // addanswerbtn_X was not serialize()d
	// submit subsequent form (AJAX)
	$.post("type/rtypetask/ajaxiface.php", rqparams, function(data, textStatus, jqXHR) {
		try { // replace section with returned HTML
			var $section = $(data).find("section");
			$('section[id="answers_for_question_'+num_subquestion+'"]').replaceWith($section);
			$('input[name^="addanswerbtn_"]').click(click_on_addanswer);
		} catch (e) {
			console.log(e);
		}
	})
	return false;
}

$(document).ready(function() {
	$('input[name^="addanswerbtn_"]').click(click_on_addanswer);
});
