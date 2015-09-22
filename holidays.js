var holidays_script=document.currentScript;
var holidays_url;

var old_jquery=null,old_dolar=null,$$;




function $$get(url,fun)
{
    return $$.ajax({
            url: url,
            xhrFields: {
                withCredentials: true
            },
            success: fun
    });
}

function holidays_jquery_loaded() {
    $$=$;
        

    $$.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
      if ( options.dataType == 'script' || originalOptions.dataType == 'script' ) {
          options.cache = true;
      }
    });    
    
    holidays_url = holidays_script.src.replace('holidays.js','');
    $$('head').prepend('<link rel="stylesheet" href="'+holidays_url+'resources/holidays.css"/>');
    
    var bootstrapFound = $$("link[href*='bootstrap']");
    if (bootstrapFound.length==0) {
        $$('head').prepend('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css"/>');
        $$('head').prepend('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/>');
    }

    
    var formFound=$$('#webkameleon_holidays_form');
    if (formFound.length==0) {
        var form='<form id="webkameleon_holidays_form"><input name="q"/><i class="glyphicon glyphicon-question-sign"></i><a>Szukaj</a></form>';
        $$(holidays_script).parent().append(form);
    }
    
    var resultsFound=$$('#webkameleon_holidays_results');
    if (resultsFound.length==0) {
        var div='<div id="webkameleon_holidays_results"></div>';
        $$(holidays_script).parent().append(div);
    }
    
    var templateFound=$$('#webkameleon_holidays_template');
    if (templateFound.length==0) {
        $$get(holidays_url+'template/',function(html) {
            $$(holidays_script).parent().append(html);
        });
        
    }

    var helpFound=$$('#webkameleon_holidays_helpmodal');
    if (helpFound.length==0) {
        $$get(holidays_url+'template/help',function(html) {
            $$(holidays_script).parent().append(html);
        });
    }

    
    $$.getScript("//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js", function() {
 
  
        var hotelFound=$$('#webkameleon_holidays_hotelmodal');
        if (hotelFound.length==0) {
            $$get(holidays_url+'template/hotel',function(html) {
                $$(holidays_script).parent().append(html);
                
                $$('#webkameleon_holidays_hotel_carousel').carousel({
                    interval: 3500
                });
                $$('#webkameleon_holidays_hotel_carousel').carousel('pause');
                
                $$('#webkameleon_holidays_hotelmodal').on('shown.bs.modal', function() {
                    $$('#webkameleon_holidays_hotel_carousel').carousel('cycle');
                }).on('hidden.bs.modal', function() {
                    $$('#webkameleon_holidays_hotel_carousel').carousel('pause');
                    $$("#webkameleon_holidays_hotelmodal .carousel-inner div.item-tpl").removeClass('active');
                    $$("#webkameleon_holidays_hotelmodal .carousel-indicators li").removeClass('active');
                    $$("#webkameleon_holidays_hotelmodal .carousel-inner div.item-tpl").first().addClass('active');
                    $$("#webkameleon_holidays_hotelmodal .carousel-indicators li").first().addClass('active');
                });
                
                $$.getScript('//maps.googleapis.com/maps/api/js?libraries=places&v=3.exp&sensor=false&callback=initmap');
  
            });
        }    
 
        $$.getScript(holidays_url+'resources/grid.js', function () {
            
            $$=$.noConflict(true);
            if (old_dolar!=null) $=old_dolar;
            if (old_jquery!=null) jQuery=old_jquery;
            
            $$.getScript(holidays_url+'resources/start.js');
        });
        
 
    });
    
 
}



function initmap()
{
    
}





if (typeof $ != "undefined") {
    old_jquery=jQuery;
    old_dolar=$;
}

var script = document.createElement("script");
script.type = "text/javascript";
script.src = "//code.jquery.com/jquery-1.10.2.js";
script.onload = holidays_jquery_loaded;
document.getElementsByTagName("head")[0].appendChild(script);


