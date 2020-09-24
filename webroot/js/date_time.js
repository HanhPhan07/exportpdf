var timeInternal;
function date_time(id)
{
    
    date = new Date(_serverTime);
    year = date.getFullYear();
    month = date.getMonth();
    months = new Array('/01/', '/02/', '/03/', '/04/', '/05/', '/06/', '/07/', '/08/', '/09/', '/10/', '/11/', '/12/');
    d = date.getDate();
    day = date.getDay();
    days = new Array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
    h = date.getHours();
    if(h<10)
    {
            h = "0"+h;
    }
    m = date.getMinutes();
    if(m<10)
    {
            m = "0"+m;
    }
    if(d<10)
    {
            d = "0"+d;
    }

    s = date.getSeconds();
    if(s<10)
    {
            s = "0"+s;
    }
    result = ''+year+''+months[month]+''+d+'   &nbsp &nbsp '+h+':'+m+':'+s;
    _serverTime = new Date(year, month, d, h, m, s)
    _serverTime.setSeconds( _serverTime.getSeconds() + 1 )
    if (document.getElementById(id)) {
        document.getElementById(id).innerHTML = result;
        clearTimeout(timeInternal);
        timeInternal = setTimeout(' date_time ("'+id+'");','1000');
        return true;
    }
}