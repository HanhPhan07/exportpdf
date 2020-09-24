jQuery(document).ready(function() {
    if (response) {
        swal(response.error);
    }

    var datePickers = ["#IDCIssuedDate", "#SIBIssuedDate","#Birthday"]

    
    datepickerModule.initial(datePickers);
});

$(document).on("click",'#btn-submit',function(){
	var valid = true;
	$.each($("#formData").serializeArray(),function (key,item){
	    if(item.name != "Email" && item.value == ""){
	        swal('','Please insert all field','error');
	        valid = false;
	    }
	})
	if(valid){
		$("#formData").submit();
	}
})

$(document).on("click",'.upload-button',function(){
	$("#Avatar").val("");
	$("#Avatar").trigger('click');
	var staffId = $(this).data("staff-id");
	var oldAvatar = $(this).data("old-avatar");
	$("#demoAvatar").attr("src",oldAvatar);
})
$("#Avatar").change(function() {
  readURL(this);
}); 

function readURL(input) {
  if (input.files && input.files[0]) {
  	if(input.files[0].size > 3145728){
  		$("#Avatar").val("");
  		swal("","Please choose a file with size less than 3MB.","error");
  		return;
  	}
    var reader = new FileReader();
    reader.onload = function(e) {
      $('#demoAvatar').attr('src', e.target.result);
    }
    reader.readAsDataURL(input.files[0]); // convert to base64 string
  }
}