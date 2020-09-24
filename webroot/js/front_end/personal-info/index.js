jQuery(document).ready(function() {
    jQuery("#btn-change-password").click(function(event) {
        jQuery("#popup-change-password").modal();
    });

    jQuery("#btn-submit-password").click(function(event) {
        // if form input is not valid then trigger a hidden submit to show the validation error
        if (!jQuery("#change-password-form")[0].checkValidity()) {
            jQuery("#btn-hidden-submit-password").click();
            return false;
        }

        changePassword();

    });
});


function changePassword()
{
    jQuery("#popup-change-password").modal('hide');
    utilsModule().displayLoadingModal();
    jQuery.ajax({
        type: 'post',
        url: baseUrl + 'password/update',
        headers: {'X-CSRF-TOKEN': csrfToken},
        data: {
            current: jQuery("#input-current-password").val(),
            new: jQuery("#input-new-password").val(),
            confirm: jQuery("#input-confirm-password").val(),
        }
    })
    .done(function (response) {
        utilsModule().hideLoadingModal();
        if (response.success) {
            swal("Your password has been updated!");
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
