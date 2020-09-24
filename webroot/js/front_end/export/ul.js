jQuery(document).ready(function() {
    monthPickerModule.initial(['#input-al-date']);

    jQuery("#btn-export").click(function(event) {
        requestExportPL();
    });
});

function requestExportPL()
{
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'export/exportPL',
        headers: {'X-CSRF-TOKEN': csrfToken},
        data: {
            date: jQuery("#input-al-date").val(),
        }
    })
    .done(function (response) {
        utilsModule().hideLoadingModal();
        if (response.success) {
            var file = response.file;
            location.href = baseUrl + file;
            return;
        } else {
            swal(response.error, {
                buttons: {
                    cancel: "OK",
                },
            });
            return;
        }
    });
}