
lazyload_grid('webkameleon_holidays_form','webkameleon_holidays_template','webkameleon_holidays_results',15,holidays_url+'holidays',true,false);
jQueryTrv('#webkameleon_holidays_form a').click(lazyload_grid_reload);

jQueryTrv.get(holidays_url+'template/placeholder',function(data) {
    jQueryTrv('#webkameleon_holidays_form input').attr('placeholder',data.template).focus();
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
    jQueryTrv('#webkameleon_holidays_form input[name="q"]').val(q.replace(/\+/g,' '));
    setTimeout(function() {
        jQueryTrv('#webkameleon_holidays_form a').trigger('click');
    },500);
    
} else {
    jQueryTrv.get(holidays_url+'holidays/q',function(data){
        jQueryTrv('#webkameleon_holidays_form input[name="q"]').val(data.q);
        if (typeof(data.q)=='string' && data.q.length) jQueryTrv('#webkameleon_holidays_form a').trigger('click');
    });
}



function post_lazyload() {
    var img_height=150;
    jQueryTrv('#webkameleon_holidays_results .holiday_photo img').each (function() {
        var img=jQueryTrv(this);
        img.load(function(){
            if(img.height()>img_height) {
                var margin=Math.round((img.height()-img_height)/2);
                img.css('margin-top','-'+margin+'px');
            }
        });
    });
    
    jQueryTrv('#webkameleon_holidays_results a.q').each(function(){
        var title=jQueryTrv(this).attr('xname');
        if (typeof(title)=='undefined') {
            title=value=jQueryTrv(this).text();
        }
        jQueryTrv(this).prop('title','Zawęź lub usuń zawężenie: '+title);        
        
    });



    
    jQueryTrv('#webkameleon_holidays_results a.q').click(function(){
        
        
        var txt=jQueryTrv('#webkameleon_holidays_form').serialize();
        var value=jQueryTrv(this).text();
        var rel=jQueryTrv(this).attr('rel');
        if (typeof(rel)=='string' && rel.length) {
            value=rel;
        }
        txt+='&alter='+encodeURIComponent(value);
        

        jQueryTrv.get(holidays_url+'holidays/query?'+txt,function(data){
            if (typeof(data.q)=='string' && data.q.length) {
                jQueryTrv('#webkameleon_holidays_form input[name="q"]').val(data.q);
                lazyload_grid_reload();
            }
        });
        
    
    });

    jQueryTrv('#webkameleon_holidays_results .holiday_photo h3 i').click(function(){
        var rel=jQueryTrv(this).attr('rel');
        
        jQueryTrv.get(holidays_url+'holidays/offer/'+rel,function(data) {
            //console.log(data);
            
            if (typeof(data.holidays.obj.info.photos)!='undefined') {
                var inners=jQueryTrv("#webkameleon_holidays_hotelmodal .carousel-inner div.item-tpl");
                var indicators=jQueryTrv("#webkameleon_holidays_hotelmodal .carousel-indicators li");
                var desc=data.holidays.obj.info.desc;
                var photos=data.holidays.obj.info.photos;
            
                
                var min=inners.length;
                if (desc.length<min) min=desc.length;
                
                for (i=0;i<min;i++)
                {
                    jQueryTrv(indicators[i]).css('display','inline-block');
                    jQueryTrv(inners[i]).removeAttr('style');
                    jQueryTrv(inners[i]).find('img').attr('src',photos[i%photos.length]);
                    jQueryTrv(inners[i]).find('h3').text(desc[i].subject);
                    jQueryTrv(inners[i]).find('p').html(desc[i].content);
                    jQueryTrv(inners[i]).addClass('item');
                    
                }
                for (i=min;i<inners.length;i++)
                {
                    jQueryTrv(indicators[i]).css('display','none');
                    jQueryTrv(inners[i]).removeClass('item');
                    jQueryTrv(inners[i]).css('display','none');
                    
                }                
                
                jQueryTrv('#webkameleon_holidays_hotelmodal h4.modal-title').html(data.holidays.obj.name+' '+data.holidays.stars);
                jQueryTrv('#webkameleon_holidays_hotelmodal h5.modal-country').html(data.holidays.obj.country+', '+data.holidays.obj.region);
                
                var text=jQueryTrv('#webkameleon_holidays_hotelmodal .btn-primary').attr('text')+data.holidays.dep_from+', '+data.holidays.startDate.DDD+' '+data.holidays.startDate.D+' '+data.holidays.startDate.MMM;
                jQueryTrv('#webkameleon_holidays_hotelmodal .btn-primary').text(text).click(function() {
                    var url=jQueryTrv(this).attr('rel');
                    url=url.replace('[adt]',data.holidays.adt);
                    url=url.replace('[chd]',data.holidays.chd);
                    url=url.replace('[id]',data.holidays.id);
                    
                    location.href=url;
                });
                
                jQueryTrv("#webkameleon_holidays_hotelmodal").modal('show');
                
                
            }

            
            
            
        });
        
        return false;
    });


};

jQueryTrv('#webkameleon_holidays_form').submit(function() {
    lazyload_grid_reload();
    return false;
});

jQueryTrv('#webkameleon_holidays_form .glyphicon').click (function() {
    jQueryTrv("#webkameleon_holidays_helpmodal").modal('show');

});

function webkameleon_holidays_helpmodal_click()
{
    var helpFound=jQueryTrv('#webkameleon_holidays_helpmodal');
    if (helpFound.length==0) {
        setTimeout(webkameleon_holidays_helpmodal_click,200);
    } else {
        jQueryTrv('#webkameleon_holidays_helpmodal .modal-body .example').click(function() {
            jQueryTrv('#webkameleon_holidays_form input[name="q"]').val(jQueryTrv(this).text());
            jQueryTrv("#webkameleon_holidays_helpmodal").modal('hide');
            jQueryTrv('#webkameleon_holidays_form a').trigger('click');
        });        
    }
}

webkameleon_holidays_helpmodal_click();

