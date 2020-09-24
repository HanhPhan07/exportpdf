jQuery("#all-salary-details").click(function(event) {
    event.preventDefault();
    if(jQuery('#salary-month').val() == ""){
        alert("Please select month to create archive!");
    } else {
        getStaffExport();
    }

    // jQuery("#import-salary-detail-modal").modal('hide');
});

// jQuery("#all-salary-month").change(function(event){
//     event.preventDefault();
//     showLinkPreviousSalaryDetails();
// });

// jQuery("#btn-all-salary-details-to-zip").click(function(event){
//     event.preventDefault();
//     // zipAllSalaryDetails();
//     getStaffExport();
// });

function getStaffExport(){
    jQuery.ajax({
        url: baseUrl + 'admin/mypage/getStaffExport',
        headers : {'X-CSRF-Token': csrfToken},
        type: 'post',
        data:{
            date: jQuery("#salary-month").val(),
        },
        success:function(response){
            if(response.data.status == 'success'){
                jQuery("#all-salary-details-modal").modal();
                jQuery("#month-archive").html(jQuery("#salary-month").val());
                jQuery("#progress-creating-archive").show();
                jQuery("#file-name").html(response.data.file_name);

                jQuery("#check-is-modal").attr('progress-modal', 'true');
                jQuery("#import-salary-detail-modal").modal('hide');

                exportPDFSalaryDetails(response.data, 0);
            } else {
                swal(response.data.message,{
                    buttons: {
                        cancel: "OK",
                    },
                });
                return;
            }
        }
    });
}

var exportPDFSalaryDetails = function(data, index) {
    jQuery("#total-file").html(data.count);
    var percent = (index + 1) * 100 / data.count;
    if(percent < 100){
        jQuery("#progress-zip").width(percent + '%');
        jQuery("#progress-zip").html(percent.toFixed() + '%');
    }

    var num = index;
    if(num < data.count) {
        jQuery("#index-file").html(index + 1);
        jQuery("#staffID").html(data['result'][index]);

        jQuery.ajax({
            url: baseUrl + 'admin/mypage/exportSalaryDetailByMonth',
            headers : {'X-CSRF-Token': csrfToken},
            type: 'post',
            data: {
                staff_id: data['result'][index],
                date: jQuery("#salary-month").val(),
                export_mode: 'PDF',
            },
            success:function(response){
                var file = '';
                if(response.success){
                    file += data['result'][index] + ': Success';
                    jQuery("#status-files").append('<p class="status-item" style="height: 10px">' + file + '</p>');
                    jQuery("#status-files").scrollTop(index * 1000);
                } else {
                    file += data['result'][index] + ': Failure';
                    jQuery("#status-files").append('<p class="status-item" style="color: red;height: 10px" >' + file + '</p>');
                    jQuery("#status-files").scrollTop(index * 1000);
                }
                exportPDFSalaryDetails(data, num+1);
            },
            error:function(response){
                console.log(response)
            }
        });
    } else {
        zipAllSalaryDetails();
    }
};

function zipAllSalaryDetails(){
    jQuery.ajax({
        url: baseUrl + 'admin/mypage/zipAllSalaryDetails',
        type: 'post',
        headers: {'X-CSRF-TOKEN': csrfToken},
        data: {
            date: jQuery("#salary-month").val(),
        },
        success:function(response){
            jQuery("#import-salary-detail-modal").modal();
            if(response.status == 'success'){
                jQuery("#progress-zip").width('100%');
                jQuery("#progress-zip").html('100%');
                jQuery("#part-progress").html('Completing');

                location.href = baseUrl + response.file;
                jQuery("#file-name").attr('href',baseUrl + response.file);
                showFilesZip();
                return;
            } else {
                swal(response.message({
                    buttons: {
                        cancel: "OK",
                    }
                }));
                return;
            }
        },
        error:function(response){
            jQuery("#import-salary-detail-modal").modal();
            console.log(response)
        }
    });
}

function showFilesZip(){
    jQuery.ajax({
        url: baseUrl + 'admin/mypage/linkPreviousSalaryDetails',
        type: 'get',
        success:function(response){
            if(response.status == "success"){
                var link = '';
                jQuery.each(response.files, function(index, val){
                    link += '<a style="margin-bottom:7px" href="'+ val.href +'">'+ val.name + '</a>';

                })
                jQuery("#list-zip").html('');
                jQuery("#list-zip").append(link);
            } else {
                jQuery("#list-zip").html('');
                jQuery("#list-zip").append('<h1 class="text-center" style="padding: 50px 0px">Empty</h1>');
            }
        },
        error:function(){
            console.log('error');
            console.log(response);
        }
    });
}
