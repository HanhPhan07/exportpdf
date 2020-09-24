// *********** compare two arrays *****************
function arrayEquals(a, b) {
    return Array.isArray(a) && Array.isArray(b) &&
        a.length === b.length &&
        a.every((val, index) => val === b[index]);
}

[eSubTotal,eVAT,eTotal] = AutoNumeric.multiple(['#SubTotal','#VAT','#Total'],{decimalPlaces : 0});
[eAmount] = AutoNumeric.multiple('.amount > input', { decimalPlaces : 0 })

function getTypeURL(){
    if(window.location.href.indexOf('/add/') > -1){
        return type = 'add'
    } else{
        return type = 'update'
    }
}

var type = getTypeURL()

$("#Date")
        .datepicker({
            // defaultDate: "+1w",
            changeMonth: false,
            numberOfMonths: 1,
            dateFormat: "yy/mm/dd",
            // disable past date
            maxDate: 0
        })
        .on("click", function () {
            optimizePositionDatePicker('Date');
        });

// TODO: ONLOAD
function beforeRender(){
    $('#formDetail').prop('hidden',true)
}
window.onload = beforeRender()

$(document).ready(function(){
    // ************** ADD NEW / DELETE ROW *********************
    $('#add_row').on('click', function(e){
        e.preventDefault()
        const curr_no = $('#current_no').val()
        const no = Number(curr_no) + 1
        $('#current_no').val(no)
        const tpl = $('#tpl_row_invoice_item').html()
        var row = ''
        row = tpl.replace(/__no__/g,'<span class="no-row">'+ no +'</span>')
        $('#tblRedInvoiceItem').append(row)
    })
    $('#tblRedInvoiceItem').on('click','.delete-row', function(e){
        e.preventDefault()
        $(this).closest('tr').html('')
        var i = 0
        $('.no-row').each(function(){
            i++
            $(this).html(i)
        })
        $('#current_no').val(i)
    })
    // ************* CLEAR / BACK *******************
    $('#btn_clear_form').on('click',function(e){
        e.preventDefault()
        $("#formRedInvoice")[0].reset()
    })
    $('#btn_back').on('click',function(e){
        e.preventDefault()
        window.location.href = baseUrl + 'admin/red-invoice'
    })
    // ************** TYPE/PROVISION CHANGE ***********
    $('#Provision').on('blur',function(){
        if($('#type_submit').val()=='add' && $(this).val() != "-1" && $('#Type').val() != "-1"){
            validCustomerID()
        }
    })
    $('#Type').on('blur',function(){
        if($('#type_submit').val()=='add' && $(this).val() != "-1" && $('#Provision').val() != "-1"){
            validCustomerID()
        }
    })
    // *********** COMPUTE AMOUNT *****************
    $('#tblRedInvoiceItem').on('blur','.unit-price',function(){
        var quantity = $(this).closest('tr').find('.quantity').val().replaceAll(",","")
        if(quantity > 0){
            var price = $(this).val().replaceAll(",","")
            var amount = Number(price)*Number(quantity)
            $(this).closest('tr').find('.amount').val(amount.toLocaleString('en'))
        }
    })
    $('#tblRedInvoiceItem').on('blur','.quantity',function(){
        var price = $(this).closest('tr').find('.unit-price').val().replaceAll(",","")
        if(price > 0){
            var quantity = $(this).val().replaceAll(",","")
            var amount = Number(price)*Number(quantity)
            $(this).closest('tr').find('.amount').val(amount.toLocaleString('en'))
        }
    })
    // ===========case update=============
    $('#tblRedInvoiceItem').on('blur','.unit-price input',function(){
        var quantity = $(this).closest('tr').find('.quantity input').val().replaceAll(",","")
        if(quantity > 0){
            var price = $(this).val().replaceAll(",","")
            var amount = Number(price)*Number(quantity)
            $(this).closest('tr').find('.amount input').val(amount.toLocaleString('en'))
        }
    })
    $('#tblRedInvoiceItem').on('blur','.quantity input',function(){
        var price = $(this).closest('tr').find('.unit-price input').val().replaceAll(",","")
        if(price > 0){
            var quantity = $(this).val().replaceAll(",","")
            var amount = Number(price)*Number(quantity)
            $(this).closest('tr').find('.amount input').val(amount.toLocaleString('en'))
        }
    })
    // ************* COMPUTE TOTAL ****************
    $('#compute_total').on('click',function(e){
        e.preventDefault()
        if(type=='add'){
            var sub_total = 0
            $('.amount').each(function(){
                if($(this).val().indexOf(','))
                    sub_total += Number($(this).val().replaceAll(",",""))
                else
                    sub_total += Number($(this).val())
            })
            var vat = sub_total/100*10
            var total = sub_total + vat
        } else {
            var sub_total = 0
            $('.amount input').each(function(){
                if($(this).val().indexOf(','))
                    sub_total += Number($(this).val().replaceAll(",",""))
                else
                    sub_total += Number($(this).val())
            })
            var vat = sub_total/100*10
            var total = sub_total + vat
        }
        eSubTotal.set(sub_total)
        eVAT.set(vat)
        eTotal.set(total)
    })
})

