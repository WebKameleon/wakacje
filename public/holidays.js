var holidays_script=document.currentScript;
var holidays_url;

var old_jquery=null,old_dolar=null,jQueryTrv;

function holidays_jquery_loaded() {
    jQueryTrv=$;

    
    
    holidays_url = holidays_script.src.replace('holidays.js','');
    jQueryTrv('head').prepend('<link rel="stylesheet" href="'+holidays_url+'resources/holidays.css"/>');
    
    var bootstrapFound = jQueryTrv("link[href*='bootstrap']");
    if (bootstrapFound.length==0) {
        jQueryTrv('head').prepend('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css"/>');
        jQueryTrv('head').prepend('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>');
    }
    

    jQueryTrv.getScript("//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js", function() {
        jQueryTrv=$.noConflict(true);
        if (old_dolar!=null) $=old_dolar;
        if (old_jquery!=null) jQuery=old_jquery;    
    });
    
    
    var formFound=jQueryTrv('#webkameleon_holidays_form');
    if (formFound.length==0) {
        var form='<form id="webkameleon_holidays_form"><input name="q"/><i class="glyphicon glyphicon-question-sign"></i><a>Szukaj</a></form>';
        jQueryTrv(holidays_script).parent().append(form);
    }
    
    var resultsFound=jQueryTrv('#webkameleon_holidays_results');
    if (resultsFound.length==0) {
        var div='<div id="webkameleon_holidays_results"></div>';
        jQueryTrv(holidays_script).parent().append(div);
    }
    
    var templateFound=jQueryTrv('#webkameleon_holidays_template');
    if (templateFound.length==0) {
        jQueryTrv.get(holidays_url+'template/',function(html) {
            jQueryTrv(holidays_script).parent().append(html);
        });
        
    }

    var helpFound=jQueryTrv('#webkameleon_holidays_helpmodal');
    if (helpFound.length==0) {
        jQueryTrv.get(holidays_url+'template/help',function(html) {
            jQueryTrv(holidays_script).parent().append(html);
        });
    }

    var hotelFound=jQueryTrv('#webkameleon_holidays_hotelmodal');
    if (hotelFound.length==0) {
        jQueryTrv.get(holidays_url+'template/hotel',function(html) {
            jQueryTrv(holidays_script).parent().append(html);
            setTimeout(function() {
                jQueryTrv('#webkameleon_holidays_hotel_carousel').carousel({
                    interval: 3500
                });
                jQueryTrv('#webkameleon_holidays_hotel_carousel').carousel('pause');
                
                jQueryTrv('#webkameleon_holidays_hotelmodal').on('shown.bs.modal', function() {
                    jQueryTrv('#webkameleon_holidays_hotel_carousel').carousel('cycle');
                }).on('hidden.bs.modal', function() {
                    jQueryTrv('#webkameleon_holidays_hotel_carousel').carousel('pause');
                    jQueryTrv("#webkameleon_holidays_hotelmodal .carousel-inner div.item-tpl").removeClass('active');
                    jQueryTrv("#webkameleon_holidays_hotelmodal .carousel-indicators li").removeClass('active');
                    jQueryTrv("#webkameleon_holidays_hotelmodal .carousel-inner div.item-tpl").first().addClass('active');
                    jQueryTrv("#webkameleon_holidays_hotelmodal .carousel-indicators li").first().addClass('active');
                });
            },1000);
        });
    }    
    
    jQueryTrv.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
      if ( options.dataType == 'script' || originalOptions.dataType == 'script' ) {
          options.cache = true;
      }
    });    

    var s = document.createElement("script");
    s.type = "text/javascript";
    s.src = holidays_url+'resources/grid.js';
    jQueryTrv("head").append(s);
    
    s = document.createElement("script");
    s.type = "text/javascript";
    s.src = holidays_url+'resources/start.js';
    jQueryTrv("head").append(s);
 

 

 
}

if (typeof $ != "undefined") {
    old_jquery=jQuery;
    old_dolar=$;
}

var script = document.createElement("script");
script.type = "text/javascript";
script.src = "//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js";
script.onload = holidays_jquery_loaded;
document.getElementsByTagName("head")[0].appendChild(script);


