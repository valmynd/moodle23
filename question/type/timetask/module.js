// Note you should disable Javascript Caching during Development!
// http://docs.moodle.org/dev/Developer_Mode

$(document).ready(function() {
	//console.log($("form.mform #appletField")[0].getResult());
	// applet must be put in a variable or it won't work!
	// see http://www.codingforums.com/showthread.php?t=137769
	var applet = $("#appletField")[0];
	$("form.mform").click(function () {
		$("textarea[name=memento]").val(applet.getMemento());
		return true;
	});
});

