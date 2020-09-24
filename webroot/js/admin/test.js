var selectedIds = [];
$(document).ready(function () {
    $("#select-choose-test").select2({
        placeholder: "Choose Test",
        ajax: {
            url: "/admin/test/mtest",
            dataType: "json",
            data: function (params) {
                var query = {
                    q: params.term,
                    selected_ids: selectedIds,
                };
                // Query parameters will be ?search=[term]&type=public
                return query;
            },
        },
    });
    $("#select-choose-test").on("select2:select", function (e) {
        var data = e.params.data;
        $("#select-choose-test").val(""); // Select the option with a value of '1'
        $("#select-choose-test").trigger("change"); // Notify any JS components that the value changed
        selectedIds.push(data.id);
        $("#table-data tbody").append(
            "<tr id='" +
                data.id +
                "'> " +
                "<td>" +
                data.test_id +
                "</td>" +
                "<td>" +
                data.test_date +
                "</td>" +
                "<td>" +
                data.description +
                "</td>" +
                "<td><button class='btn w-md btn-sm btn-primary waves-effect waves-light btn-delete' type='button'>Delete</button></td>" +
                "</tr>"
        );
    });
});
$(document).on("click", ".btn-delete", function () {
    var id = $(this).closest("tr").attr("id");
    $(this).closest("tr").remove();
    var index = selectedIds.indexOf(parseInt(id));
    if (index > -1) {
        selectedIds.splice(index, 1);
    }
});
$(document).on("click", "#btn-clear", function () {
    selectedIds = [];
    $("#table-data tbody").empty();
});

$(document).on("click", "#btn-export-test", function () {
    if (selectedIds.length <= 0) {
        swal("Please choose test!");
        return;
    }
    utilsModule().displayLoadingModal();
    $.ajax({
        url: "/admin/test/result-export",
        headers: {
            "X-CSRF-Token": csrfToken,
        },
        type: "post",
        data: {
            ids: selectedIds,
            type: $('input[name="export_mode"]:checked').val(),
        },
        success: function (res) {
            utilsModule().hideLoadingModal();
            if (res.error == "") {
                window.location.href = res.url;
            }
        },
        error: function (res) {
            utilsModule().hideLoadingModal();
        },
    });
});
