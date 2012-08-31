// Note you should disable Javascript Caching while Development!
// http://docs.moodle.org/dev/Developer_Mode

$(document).ready(function() {
	//console.log($("form.mform #appletField")[0].getResult());
	// applet must be put in a variable or it won't work!
	// see http://www.codingforums.com/showthread.php?t=137769
	var applet = $("form.mform #appletField")[0];
	//console.log(applet);
	$("form.mform").click(function () {
		//console.log(applet.getResult());
		$("form.mform textarea[name=applet_result]").val(applet.getResult());
		//console.log($("form.mform textarea[name=applet_result]").val());
		return true;
	});
});

