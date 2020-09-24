jQuery("body").on("submit", ".ajax-form", function (e) {
    e.preventDefault();
    const form = jQuery(".ajax-form");
    ajax_form(form);
});

function ajax_form(form) {
    const form_data = new FormData(form.get(0));

    const button = form.find("button[type=submit]");
    button.attr("disabled", true);

    var url = "";
    if (jQuery('#idYellowCardClick').length) {
        url = "admin/yellowCard/edit";
        const id = jQuery('#idYellowCardEdit').val();
        form_data.append("id", id);
    } else {
        url = form.attr("action");
    }

    if (jQuery(".ajax-multiple-file").length) {
        if (jQuery('#notChange'.length < 0)) {
            var totalImages = jQuery(".ajax-multiple-file").prop("files").length;
            for (var i = 0; i < totalImages; i++) {
                var file_data = jQuery(".ajax-multiple-file").prop("files")[i];
                form_data.append("files[]", file_data);
            }
        }
    }

    jQuery.ajax({
        url: baseUrl + url,
        headers: { "X-CSRF-TOKEN": csrfToken },
        type: "post",
        cache: false,
        contentType: false,
        processData: false,
        data: form_data,
        dataType: "json",

    })
        .done(function (response) {
            if (response.success) {
                swal({
                    title: "Successful!",
                    icon: "success"
                }).then((reload) => {
                    if (reload && jQuery('#idYellowCardClick').length) {
                        window.location.reload(true);
                    }
                });
                button.attr("disabled", false);
                form.get(0).reset();
                if (jQuery(".ajax-multiple-file").length) {
                    jQuery(".gallery").html('');
                }
            }

        });
}
