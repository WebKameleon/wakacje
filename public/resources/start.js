
lazyload_grid('webkameleon_holidays_form','webkameleon_holidays_template','webkameleon_holidays_results',15,holidays_url+'holidays',true,false);
$('#webkameleon_holidays_form a').click(lazyload_grid_reload);

$.get(holidays_url+'template/placeholder',function(data) {
    $('#webkameleon_holidays_form input').attr('placeholder',data.template).focus();
});


$.get(holidays_url+'template/help',function(html) {
    $('#webkameleon_holidays_form').append(html);
});

var getUrlParameter = function getUrlParameter(sParam,url) {
    
    if (typeof(url)=='undefined') url=window.location;
    var sPageURL = decodeURIComponent(url.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};


var q=getUrlParameter('q');
if (typeof(q) != 'undefined') {
    $('#webkameleon_holidays_form input').val(q.replace(/\+/g,' '));
    lazyload_grid_reload();
} else {
    //console.log(document.referrer);
}



function post_lazyload() {
    var img_height=150;
    $('#webkameleon_holidays_results .holiday_photo img').each (function() {
        var img=$(this);
        img.load(function(){
            if(img.height()>img_height) {
                var margin=Math.round((img.height()-img_height)/2);
                img.css('margin-top','-'+margin+'px');
            }
        });
    });
    
    $('#webkameleon_holidays_results a.q').each(function(){
        var title=$(this).attr('xname');
        if (typeof(title)=='undefined') {
            title=value=$(this).text();
        }
        $(this).prop('title','Zawęź lub usuń zawężenie: '+title);        
        
    });
    
    $('#webkameleon_holidays_results a.q').click(function(){
        
        
        var txt=$('#webkameleon_holidays_form').serialize();
        var value=$(this).text();
        var rel=$(this).attr('rel');
        if (typeof(rel)=='string' && rel.length) {
            value=rel;
        }
        txt+='&alter='+encodeURIComponent(value);
        

        $.get(holidays_url+'holidays/query?'+txt,function(data){
            if (typeof(data.q)=='string' && data.q.length) {
                $('#webkameleon_holidays_form input[name="q"]').val(data.q);
                lazyload_grid_reload();
            }
        });
        
    
    });
};

$('#webkameleon_holidays_form').submit(function() {
    lazyload_grid_reload();
    return false;
});

$('#webkameleon_holidays_form .glyphicon').click (function() {
    $("#webkameleon_holidays_helpmodal").modal('show');

});