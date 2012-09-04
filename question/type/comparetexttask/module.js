// Note you should disable Javascript Caching while Development!
// http://docs.moodle.org/dev/Developer_Mode

$(document).ready(function() {
	//console.log($("form.mform #appletField")[0].getResult());
	// applet must be put in a variable or it won't work!
	// see http://www.codingforums.com/showthread.php?t=137769
	var applet = $("#appletField")[0];
	//console.log(applet);
	$("form.mform").click(function () {
		//console.log(applet.getResult());
		$("textarea[name=initial_text]").val(applet.getInitialText());
		$("textarea[name=avaiable_tags]").val(applet.getAvaiableTags());
		$("textarea[name=sample]").val(applet.getSampleSolution());
		//console.log($("textarea[name=applet_result]").val());
		return true;
	});
	// put applet at same position as the <input> elements above it
	//console.log($("#id_name").offset().left);
	//$("#appletField").css("position", "absolute").css("left", $("#id_name").offset().left);
});

