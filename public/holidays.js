var holidays_script=document.currentScript;
var holidays_url;

function holidays_jquery_loaded() {
    
    holidays_url = holidays_script.src.replace('holidays.js','');
    $('head').prepend('<link rel="stylesheet" href="'+holidays_url+'resources/holidays.css"/>');
    
    var bootstrapFound = $("link[href*='bootstrap']");
    if (bootstrapFound.length==0) {
        $('head').prepend('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css"/>');
        $('head').prepend('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>');
    }
    
    var bootstrapJSFound = $("[src*='bootstrap']");
    if (bootstrapJSFound.length==0) {
        $.getScript("//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js");
    }
    
    
    var formFound=$('#webkameleon_holidays_form');
    if (formFound.length==0) {
        var form='<form id="webkameleon_holidays_form"><input name="q"/><i class="glyphicon glyphicon-question-sign"></i><a>Szukaj</a></form>';
        $(holidays_script).parent().append(form);
    }
    
    var resultsFound=$('#webkameleon_holidays_results');
    if (resultsFound.length==0) {
        var div='<div id="webkameleon_holidays_results"></div>';
        $(holidays_script).parent().append(div);
    }
    
    var templateFound=$('#webkameleon_holidays_template');
    if (templateFound.length==0) {
        $.get(holidays_url+'template/',function(html) {
            $(holidays_script).parent().append(html);
        });
        
    }

    var helpFound=$('#webkameleon_holidays_helpmodal');
    if (helpFound.length==0) {
        $.get(holidays_url+'template/help',function(html) {
            $(holidays_script).parent().append(html);
        });
    }

    var hotelFound=$('#webkameleon_holidays_hotelmodal');
    if (hotelFound.length==0) {
        $.get(holidays_url+'template/hotel',function(html) {
            $(holidays_script).parent().append(html);
            setTimeout(function() {
                $('#webkameleon_holidays_hotel_carousel').carousel({
                    interval: 3500
                });
                $('#webkameleon_holidays_hotel_carousel').carousel('pause');
                
                $('#webkameleon_holidays_hotelmodal').on('shown.bs.modal', function() {
                    $('#webkameleon_holidays_hotel_carousel').carousel('cycle');
                }).on('hidden.bs.modal', function() {
                    $('#webkameleon_holidays_hotel_carousel').carousel('pause');
                    $("#webkameleon_holidays_hotelmodal .carousel-inner div.item-tpl").removeClass('active');
                    $("#webkameleon_holidays_hotelmodal .carousel-indicators li").removeClass('active');
                    $("#webkameleon_holidays_hotelmodal .carousel-inner div.item-tpl").first().addClass('active');
                    $("#webkameleon_holidays_hotelmodal .carousel-indicators li").first().addClass('active');
                });
            },1000);
        });
    }    
    
    $.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
      if ( options.dataType == 'script' || originalOptions.dataType == 'script' ) {
          options.cache = true;
      }
    });    

    var s = document.createElement("script");
    s.type = "text/javascript";
    s.src = holidays_url+'resources/grid.js';
    $("head").append(s);
    
    s = document.createElement("script");
    s.type = "text/javascript";
    s.src = holidays_url+'resources/start.js';
    $("head").append(s);
 

}



if (typeof $ == "undefined") {
    var script = document.createElement("script");
    script.type = "text/javascript";
    script.src = "//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js";
    script.onload = holidays_jquery_loaded;
    document.getElementsByTagName("head")[0].appendChild(script);
} else {
    holidays_jquery_loaded();
}
