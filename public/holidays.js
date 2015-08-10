var holidays_script=document.currentScript;

function holidays_jquery_loaded() {
    
    var holidays_url = holidays_script.src.replace('holidays.js','');
    $('head').prepend('<link rel="stylesheet" href="'+holidays_url+'resources/holidays.css"/>');
    
    var bootstrapFound = $("link[attribute*='bootstrap']");
    if (bootstrapFound.length==0) {
        $('head').prepend('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css"/>');
    }
    
    var formFound=$('#webkameleon_holidays_form');
    if (formFound.length==0) {
        var form='<form id="webkameleon_holidays_form"><input/><a>Szukaj</a></form>';
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
 
    console.log(0);
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
