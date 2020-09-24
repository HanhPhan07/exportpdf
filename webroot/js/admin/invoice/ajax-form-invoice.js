$("body").on("submit", ".ajax-form-invoice", function (e) {
    e.preventDefault();
    const form = $(".ajax-form-invoice");
    ajax_form(form);
});

function ajax_form(form) {
    const form_data = new FormData(form.get(0));

    const button = form.find("button[type=submit]");
    // button.attr("disabled", true);

    // declare url
    var url = ""
    var type = ""
    if(window.location.href.indexOf('/add/') > -1){
        url="admin/redInvoice/add"
        type = 'add'
    } else{
        url="admin/redInvoice/update"
        type = 'update'
    }


    // customer
    form_data.append('CustomerID',$('#CustomerID').html())
    form_data.append('CustomerName',$('#CustomerName').html())

    // data rows
    if ($(".no-row").length) {
        if(type=='add'){
            $('.no-row').each(function(){
                var val_row = {}
                val_row['GoodService'] = $(this).closest('tr').find('.good-service').val()
                val_row['Unit'] = $(this).closest('tr').find('.unit').val()
                val_row['Quantity'] = $(this).closest('tr').find('.quantity').val()
                val_row['UnitPrice'] = $(this).closest('tr').find('.unit-price').val()
                val_row['Amount'] = $(this).closest('tr').find('.amount').val().replace(',',"")
                form_data.append("Items[]", JSON.stringify(val_row));
            })
        } else {
            $('#tblRedInvoiceItem tr').each(function(){
                // console.log($(this).find('.amount input').val())
                var val_row = {}
                val_row['GoodService'] = $(this).find('.good-service input').val()
                val_row['Unit'] = $(this).find('.unit input').val()
                val_row['Quantity'] = $(this).find('.quantity input').val()
                val_row['UnitPrice'] = $(this).find('.unit-price input').val()
                val_row['Amount'] = $(this).find('.amount input').val().replace(',',"")
                console.log(val_row['Amount'])
                form_data.append("Items[]", JSON.stringify(val_row));
            })
        }
    }


    // total
    form_data.append('SubTotal',$('#SubTotal').val().replace(',',""))
    form_data.append('VAT',$('#VAT').val().replace(',',""))
    form_data.append('Total',$('#Total').val().replace(',',""))

    // log
    console.log(form_data['SubTotal'])
    console.log(form_data['VAT'])
    console.log(form_data['Total'])


    $.ajax({
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
                    title: response.alert,
                    icon: "success"
                }).then((reload) => {
                    if (reload) {
                        // window.location.href =
                        window.location.reload(true);
                    }
                });
                // button.attr("disabled", false);
                // form.get(0).reset();
                // if ($(".ajax-multiple-file").length) {
                //     $(".gallery").html('');
                // }
            }

        });
}
