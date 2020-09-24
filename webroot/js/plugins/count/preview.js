$(document).ready(function() {	

	//Colors
	$("#colors > a").click(function(e) {
		e.preventDefault();
		var btn = $(this);
		
		api.circleTimer.style(
			btn.data("bg-color"),
			btn.data("circle-color")
		);
	});

});