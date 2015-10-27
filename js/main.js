function addUrl() {
    console.log('addUrl');

    name = document.getElementById('nameNoty').value;
    url = document.getElementById('urlNoty').value;
    semestre = document.getElementById('semestreNoty').value;
    groupTD = document.getElementById('groupNoty').value;
    groupTP = document.getElementById('underGroupNoty').value;

    tonight = document.getElementById('toNightNoty').checked;
    tonightTime = document.getElementById('toNightTime').value;

    beforeClass = document.getElementById('beforeClassNoty').checked;
    beforeClassTime = document.getElementById('beforeClassTime').value;
    beforeClassTimeSecond = beforeClassTime.split(':')[0]*3600 + beforeClassTime.split(':')[1] * 60;


    beforeBegin = document.getElementById('beforeBegin').checked;
    beforeBeginTime = document.getElementById('beforeBeginTime').value;
    beforeBeginTimeSecond = beforeBeginTime.split(':')[0]*3600 + beforeBeginTime.split(':')[1] * 60;

    disableB('buttonUrl');

    var xmlhttp = gXMLHTTP();

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            console.log(xmlhttp.responseText);
            
            if(xmlhttp.responseText == 'true') {
                
                var n = noty({
                    text        : 'L\'url a été ajouté.',
                    type        : 'success',
                    theme       : 'relax',
                    layout      : 'topRight',
                    animation   : {
                        open  : 'animated bounceInRight',
                        close : 'animated bounceOutRight'
                    },
                    timeout     : '5000'
                });
                
                location.reload();
            }
            else {
                var n = noty({
                        text        : 'Erreur! Impossible d\'ajouter l\'url.',
                        type        : 'error',
                        theme       : 'relax',
                        layout      : 'topRight',
                        animation   : {
                            open  : 'animated bounceInRight',
                            close : 'animated bounceOutRight'
                        },
                        timeout     : '5000'
                    });
            }
            
            enableB('buttonUrl');
        }
    };

    xmlhttp.open('POST', 'php/main.php', true);
    xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    xmlhttp.send('f=addUrl&name=' + name + '&url=' + url + '&semestre=' + semestre + '&groupTD=' + groupTD + '&groupTP=' + groupTP +  '&tonight=' + (tonight ? 1 : 0) + '&beforeClass=' + (beforeClass ? 1 : 0) + '&tonightTime=' + tonightTime + '&beforeBegin=' + ( beforeBegin ? 1 : 0) + '&beforeBeginTime=' + beforeBeginTimeSecond +'&beforeClassTime='+ beforeClassTimeSecond );
}

function deleteUrl(urlId) {
    disableB('buttonDelete' + urlId);

    var xmlhttp = gXMLHTTP();

    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            console.log(xmlhttp.responseText);
            
            if(xmlhttp.responseText == 'true') {
                
                var n = noty({
                    text        : 'Les propriétés ont été mises à jour.',
                    type        : 'success',
                    theme       : 'relax',
                    layout      : 'topRight',
                    animation   : {
                        open  : 'animated bounceInRight',
                        close : 'animated bounceOutRight'
                    },
                    timeout     : '5000'
                });
                
                location.reload();
            }
            else {
                var n = noty({
                        text        : 'Erreur! Impossible de mettre à jour les propriétés.',
                        type        : 'error',
                        theme       : 'relax',
                        layout      : 'topRight',
                        animation   : {
                            open  : 'animated bounceInRight',
                            close : 'animated bounceOutRight'
                        },
                        timeout     : '5000'
                    });
            }
            
            enableB('buttonDelete' + urlId);
        }
    };

    xmlhttp.open('POST', 'main.php', true);
    xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    xmlhttp.send('f=deleteUrl&urlId=' + urlId);
}

function gXMLHTTP() {
    if (window.XMLHttpRequest)
    {// code for IE7+, Firefox, Chrome, Opera, Safari
        return new XMLHttpRequest();
    }
    else
    {// code for IE6, IE5
        return new ActiveXObject("Microsoft.XMLHTTP");
    }
}

function enableB(buttonId) {
    document.getElementById(buttonId).disabled = false;
}

function disableB(buttonId) {
    document.getElementById(buttonId).disabled = true;
}

window.onload = function () {
    $('#toNightTime').datetimepicker({
        datepicker:false,
        format:'H:i',
        step:10
    });
    $('#beforeBeginTime').datetimepicker({
        datepicker:false,
        format:'H:i',
        step:10,
        defaultTime : '00:10',
        minTime : '00:05',
        maxTime : '05:00'
    });
    $('#beforeClassTime').datetimepicker({
        datepicker:false,
        format:'H:i',
        step:5,
        defaultTime : '00:10',
        minTime : '00:10',
        maxTime : '01:00'
    });
};

document.getElementById('beforeBegin').onclick = function () {
    (document.getElementById('beforeBegin').checked ? document.getElementById('beforeBeginTime').removeAttribute('disabled'): document.getElementById('beforeBeginTime').setAttribute('disabled',''));
};

document.getElementById('beforeClassNoty').onclick = function () {
    (document.getElementById('beforeClassNoty').checked ? document.getElementById('beforeClassTime').removeAttribute('disabled'): document.getElementById('beforeClassTime').setAttribute('disabled',''));
};

document.getElementById('toNightNoty').onclick = function () {
    (document.getElementById('toNightNoty').checked ? document.getElementById('toNightTime').removeAttribute('disabled') : document.getElementById('toNightTime').setAttribute('disabled',''));
};