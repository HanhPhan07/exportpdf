$(document).on("click", ".btn-delete", function () {
    var id = $(this).closest("tr").data("id");
    var _this = $(this);
    swal({
        title: "Are you sure?",
        text: "Once deleted, you will not be able to recover this file!",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            utilsModule().displayLoadingModal();
            $.ajax({
                url: "/admin/company-regulations/delete",
                headers: {
                    "X-CSRF-Token": csrfToken,
                },
                type: "post",
                data: {
                    id: id,
                },
                success: function (res) {
                    utilsModule().hideLoadingModal();
                    swal("Poof! Your file has been deleted!", {
                        icon: "success",
                    });
                    _this.closest("tr").remove();
                },
                error: function (res) {
                    utilsModule().hideLoadingModal();
                },
            });
        } else {
            swal("Your file is safe!");
        }
    });
});
$(document).on("click", ".btn-edit", function () {
    var id = $(this).closest("tr").data("id");
    $("#modal").modal("show");
    utilsModule().displayLoadingModal();
    $.ajax({
        url: "/admin/company-regulations/get-data",
        headers: {
            "X-CSRF-Token": csrfToken,
        },
        type: "post",
        dataType: "JSON",
        data: {
            id: id,
        },
        success: function (res) {
            utilsModule().hideLoadingModal();
            $("#orderNo").val(res.OrderNo);
            $("#name").val(res.Name);
            $("#companyRegulationId").val(res.ID);
        },
        error: function (res) {
            utilsModule().hideLoadingModal();
        },
    });
});
$(document).on("click", "#btn-reset", function () {
    window.location.reload();
});
$(document).on("click", "#btn-add-menu", function () {
    $("#modal").modal("show");
    $("#orderNo").val($("#orderNo").data("old-value"));
    $("#companyRegulationId").val("");
});
$(document).on("click", "#btn-import", function () {
    var name = $("#name").val();
    var orderNo = $("#orderNo").val();
    var id = $("#companyRegulationId").val();
    if (name == "" || ($("#pdfFile")[0].files.length <= 0 && id == "")) {
        swal("Please enter name and choose a pdf file");
        return;
    }
    var file = "";
    if ($("#pdfFile")[0].files.length > 0) {
        file = $("#pdfFile")[0].files[0];
    }
    var formdata = new FormData();
    if (id != "") {
        formdata.append("id", id);
    }
    formdata.append("name", name);
    formdata.append("order_no", orderNo);
    formdata.append("pdf_file", file);
    utilsModule().displayLoadingModal();
    $.ajax({
        type: "POST",
        data: formdata,
        url: "/admin/company-regulations/import",
        async: true,
        cache: false,
        dataType: "JSON",
        contentType: false,
        processData: false,
        error: function (err) {
            utilsModule().hideLoadingModal();
        },
        success: function (res) {
            utilsModule().hideLoadingModal();
            swal("Import successful!").then((value) => {
                window.location.reload();
            });
        },
    });
});
