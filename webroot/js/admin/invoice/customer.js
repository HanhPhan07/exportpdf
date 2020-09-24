// *********** compare two arrays *****************
function arrayEquals(a, b) {
    return Array.isArray(a) && Array.isArray(b) &&
        a.length === b.length &&
        a.every((val, index) => val === b[index]);
}

// ********** clear *************
function clearForm(){
    $('#CustomerID').val('')
    $('#CustomerName').val('')
    $('#Phone').val('')
    $('#Address').val('')
    $('#Type').val('')
    $('#Provision').val('')
    $('#Category').val('')
    $('#PresidentName').val('')
}

// TODO: ONLOAD
function beforeRender(){
    getListCustomer('ID')
    getListCustomer('Name')
}
window.onload = beforeRender()

$(document).ready(function(){
    // ************* SHOW MODAL *************
    $('#show-modal-customer').on('click',function(){
        clearForm()
        $('#modalCustomer').modal()
    })
    // *************** SUBMIT ADD NEW ****************
    $('#btnSubmitCustomer').on('click',function(){
        if($('#type_submit').val() == 'add')
            addCustomer()
        else
            editCustomer()
    })
    // *************** CUSTOMER ID/NAME CHANGE *************
    $('#CustomerIDSearch').blur(function(){
        eventBlurCustomerSearch('ID',$(this).val())
    })
    $('#CustomerNameSearch').blur(function(){
        eventBlurCustomerSearch('Name',$(this).val())
    })
    // ************** SEARCH **************
    $('#btnSearchCustomer').on('click',function(){
        searchCustomer()
    })
    // **************** DELETE *************
    $('#tblCustomer').on('click','.btnDeleteCustomer',function(){
        swal({
            title: "Are you sure you want to delete this customer?",
            icon: "error",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {
                if (willDelete) {
                    var id = $(this).closest('tr').find("#id_customer").val()
                    deleteCustomer(id)
                }
        })

    })
    // *************** EDIT ****************
    $('#tblCustomer').on('click','.btnEditCustomer',function(){
        clearForm()
        var id = $(this).closest('tr').find("#id_customer").val()
        formEdit(id)
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
})


// TODO: VALIDATE FORM
function validateform(){
    // check input required
    if($("#CustomerID").val() == ""){
        alert('Please enter Customer ID !')
        return (false)
    }
    if($("#CustomerName").val() == ""){
        alert('Please enter Customer Name !')
        return (false)
    }
    // check match phone
    const reg = /^((\+|)(?:[0-9]{3,15}))$/;
    if($('#Phone').val() != "" && !$("#Phone").val().match(reg)){
        alert('Phone number is not correct.')
        return (false)
    }
    // check not select
    if($('#Type').val() == "-1"){
        alert('Please select Type !')
        return (false)
    }
    if($('#Provision').val() == "-1"){
        alert('Please select Provision !')
        return (false)
    }
    return true
}

// TODO: LIST CUSTOMER
function getListCustomer(type){
    $.ajax({
        url:baseUrl + 'admin/customer/listCustomer' + type,
        type:'get',
        success:function(response){
            appendListCustomer(type,response.data)
        },
        error:function(response){
            console.log(response)
        }
    })
}
// APPEND LIST CUSTOMER
function appendListCustomer(type,data){
    if(type!=""){
        $.each(data,function(index,value){
            $('#listCustomer' + type).append('<option value="'+ value.item +'">')
        })
    } else {
        appendTableCustomer(data)
    }
}

// TODO: APPEND TABLE CUSTOMER
function appendTableCustomer(data){
    if($('#tblCustomer').length){
        $('#tblCustomer').html('')
        // var template = document.getElementById("tplRowCustomer");
        const tplRow = $('#tplRowCustomer').html()
        var rows = ''
        var i = 0
        $.each(data,function(index,value){
            console.log(value)
            i++
            // provision
            if(value.Provision == 0)
                var val_provision = 'Netsurf'
            else
                var val_provision = 'NSV'
            // type
            if(value.Type == 0)
                var val_type = 'Sale'
            else
                var val_type = 'Buy'
            rows += tplRow.replace(/__ID__/g, value.ID)
                            .replace(/__No__/g, i)
                            .replace(/__CustomerID__/g, value.CustomerID)
                            .replace(/__CustomerName__/g, value.CustomerName)
                            .replace(/__PresidentName__/g, value.PresidentName)
                            .replace(/__Phone__/g, value.Phone)
                            .replace(/__Address__/g, value.Address)
                            .replace(/__Provision__/g, val_provision)
                            .replace(/__Type__/g, val_type)
                            .replace(/__Category__/g, value.Category)
        })
        $("#tblCustomer").append(rows)
        // append inputs search
        $('#listCustomerID').html('')
        $('#listCustomerName').html('')
        getListCustomer('ID')
        getListCustomer('Name')
    } else {
        var url = window.location.href
        window.location.href = url
    }

}

// TODO: EVENT CUSTOMER ID/NAME CHANGE
function eventBlurCustomerSearch(type,text){
    $.ajax({
        headers:{'X-CSRF-TOKEN': csrfToken},
        url:baseUrl + 'admin/customer/checkCustomer',
        type:'post',
        data:{
            'type':type,
            'text':text,
        },
        success:function(response){
            if(response != null){
                if(type == 'ID'){
                    $('#CustomerNameSearch').val(response.CustomerName)
                    $('#ProvisionSearch').val(response.Provision)
                } else {
                    $('#CustomerIDSearch').val(response.CustomerID)
                    $('#ProvisionSearch').val(response.Provision)
                }
            } else {
                $('#CustomerNameSearch').val('')
                $('#ProvisionSearch').val('-1')
            }
        },
        error:function(response){
            console.error();
        }
    })
}

// TODO: ADD NEW CUSTOMER
function addCustomer(){
    var check = true
    check = validateform()
    if(check){
        $.ajax({
            url: baseUrl + 'admin/customer/add',
            headers : {'X-CSRF-Token': csrfToken},
            type: 'post',
            data: {
                'CustomerID':$('#CustomerID').val(),
                'CustomerName':$('#CustomerName').val(),
                'Phone':$('#Phone').val(),
                'Address':$('#Address').val(),
                'Type':$('#Type').val(),
                'Provision':$('#Provision').val(),
                'Category':$('#Category').val(),
                'PresidentName':$('#PresidentName').val(),
            },
            success:function(response){
                if(response == 1){
                    $('#CustomerID').val(''),
                    $('#CustomerName').val(''),
                    $('#Phone').val(''),
                    $('#Address').val(''),
                    $('#Type').val(''),
                    $('#Provision').val(''),
                    $('#Category').val(''),
                    $('#PresidentName').val(''),
                    $('#modalCustomer').modal('hide')
                    swal({
                        title: "Added Successfully!",
                        icon: "success",
                    });
                }
            },
            error:function(){
                alert('Have error. Please double check that the customer ID & Name are not duplicates in the Database.')
            }
        })
        getListCustomer("")
    }
}

// TODO: SEARCH CUSTOMER
function searchCustomer(){
    var id = $('#CustomerIDSearch').val()
    var name = $('#CustomerNameSearch').val()
    var provision = $('#ProvisionSearch').val()
    var input = [id,name,provision]
    const empty = ["","","-1"]
    if(arrayEquals(input,empty) )
        alert('Please enter input!')
    else
        $.ajax({
            headers:{'X-CSRF-TOKEN':csrfToken},
            url:baseUrl + 'admin/customer/search',
            type:'post',
            data:{
                'id': id,
                'name':name,
                'provision': provision,
                'id_customer':'',
            },
            success:function(response){
                if(response.success == 1){
                    if(response.data.length){
                        $('#btnBackCustomer').css('display','block')
                        appendTableCustomer(response.data)
                    }
                    else
                        alert('No data.')
                }
            },
            error:function(response){
                console.log(response)
            }
        })
}

// TODO: DELETE CUSTOMER
function deleteCustomer(id){
    $.ajax({
        headers:{'X-CSRF-TOKEN': csrfToken},
        url:baseUrl + 'admin/customer/delete',
        type:'post',
        data:{'id_customer':id},
        success:function(response){
            if(response.success){
                swal({
                    'title':'Deleted Successfully!',
                    'icon':'success'
                })
                getListCustomer("")
            }
        },
        error:function(response){
            console.log(response)
        }
    })
}

// TODO: FORM EDIT
function formEdit(id){
    $('#id_customer_form').val(id)
    $('#type_submit').val('edit')
    $.ajax({
        headers:{'X-CSRF-TOKEN': csrfToken},
        url:baseUrl + 'admin/customer/search',
        type:'post',
        data:{'id_customer':id},
        success:function(response){
            if(response.success){
                const data = response.data
                $('#CustomerID').val(data.CustomerID)
                $('#CustomerName').val(data.CustomerName)
                $('#PresidentName').val(data.PresidentName)
                $('#Phone').val(data.Phone)
                $('#Address').val(data.Address)
                $('#Provision').val(data.Provision)
                $('#Type').val(data.Type)
                $('#Category').val(data.Category)
            }
        },
        error:function(response){
            console.log(response)
        }
    })
    $('#modalCustomer').modal()
}

// TODO: EDIT CUSTOMER
function editCustomer(){
    var id = $('#id_customer_form').val()
    var check = true
    check = validateform()
    if(check){
        $.ajax({
            headers:{'X-CSRF-TOKEN': csrfToken},
            url:baseUrl + 'admin/customer/edit',
            type:'post',
            data:{
                'ID':id,
                'CustomerID':$('#CustomerID').val(),
                'CustomerName':$('#CustomerName').val(),
                'Phone':$('#Phone').val(),
                'Address':$('#Address').val(),
                'Type':$('#Type').val(),
                'Provision':$('#Provision').val(),
                'Category':$('#Category').val(),
                'PresidentName':$('#PresidentName').val(),
            },
            success:function(response){
                if(response == 1){
                    $('#CustomerID').val(''),
                    $('#CustomerName').val(''),
                    $('#Phone').val(''),
                    $('#Address').val(''),
                    $('#Type').val(''),
                    $('#Provision').val(''),
                    $('#Category').val(''),
                    $('#PresidentName').val(''),
                    $('#modalCustomer').modal('hide')
                    swal({
                        title: "Edited Successfully!",
                        icon: "success",
                    });
                }
            },
            error:function(){
                alert('Have error. Please double check that the customer ID & Name are not duplicates in the Database.')
            }
        })
    }

    getListCustomer("")
}

// TODO: VALID CUSTOMER ID
function validCustomerID(){
    $.ajax({
        headers:{'X-CSRF-TOKEN': csrfToken},
        url:baseUrl + 'admin/customer/maxCustomerID',
        type:'post',
        data:{
            'Type':$('#Type').val(),
            'Provision':$('#Provision').val(),
        },
        success:function(response){
            $('#CustomerID').val(response)
        },
        error:function(response){
            console.log(response)
        }
    })
}

