var wk_map,wk_marker;

function replace_input_value(val)
{
    var serachPattern='#webkameleon_holidays_form input[name="q"]';
    
    var position=$$(serachPattern).offset();
    var width=$$(serachPattern).width();
    
    if ($$(serachPattern).val().length)
        $$('body').append('<div style="width: '+(width-30)+'px; top:'+(position.top)+'px ; left:'+(position.left+2)+'px;" class="webkameleon_holidays_form_input_curtain">'+$$(serachPattern).val()+'</div>');
    
    $$(serachPattern).val(val);
    
    //return;
    $$('.webkameleon_holidays_form_input_curtain').fadeOut(1500,function() {
        $$('.webkameleon_holidays_form_input_curtain').remove();
    });
    
}

$$('#webkameleon_holidays_results').lazygrid({
    template: $$('#webkameleon_holidays_template').html(),
    url: holidays_url+'holidays',
    form: $$('#webkameleon_holidays_form'),
    eachrow: function(row,i,opt) {
        if (i==0) {
            var html2=opt.options.results+' ('+opt.system.total_time+' s)';
            if (typeof(opt.options.change)!='undefined' && opt.options.change.length) {
                html2='<h2>'+opt.options.change+'</h2>'+html2;
            }
    
            var ul='<ul><li rel="0" class="'+(!opt.options.totalPrice || opt.options.totalPrice==0?'active':'')+'">Ceny za osobę</li><li rel="1" class="'+(opt.options.totalPrice==1?'active':'')+'">Ceny łączne</li></ul>';
            html2='<div class="row lazyload_grid_results">'+ul+html2+'</div>';
            
            var price_switch = $$(html2).prependTo(row.parent()).fadeIn(200);
            
            price_switch.find('ul li').click(function() {
                var url=holidays_url+'holidays/total/'+$$(this).attr('rel');
                
                $$get(url,function() {
                    $$.lazygrid_reload();
                });
            });            
            
        }
        post_lazyload(row);
    }
});



$$('#webkameleon_holidays_form a').click(function () {
    $$.lazygrid_reload();
});

$$get(holidays_url+'template/placeholder',function(data) {
    $$('#webkameleon_holidays_form input[name="q"]').attr('placeholder',data.template).focus();
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
    replace_input_value(q.replace(/\+/g,' '));
    setTimeout(function() {
        $$('#webkameleon_holidays_form a').trigger('click');
    },600);
    
} else {
    
    $$get(holidays_url+'holidays/q',function(data){
        replace_input_value(data.q);
        if (typeof(data.q)=='string' && data.q.length)
        {
            setTimeout(function() {
                $$('#webkameleon_holidays_form a').trigger('click');
            },600);
            
        }
    });

}



function post_lazyload(row) {
    
    
    var img_height=150;
    row.find('.holiday_photo img').each (function() {
        
        var hotel=$$(this).attr('rel');
        var img=$$(this);
        
        /*
        if(img.height()>img_height) {
            var margin=Math.round((img.height()-img_height)/2);
            img.css('margin-top','-'+margin+'px');
        }
        */
        
        $$get(holidays_url+'holidays/hotel/'+hotel,function(data){
            if (typeof(data.hotel.thumb)!='undefined') {
                img.hide().css('margin-top','0').attr('src',data.hotel.thumb).load(function(){
                    if(img.height()>img_height) {
                        var margin=Math.round((img.height()-img_height)/2);
                        img.css('margin-top','-'+margin+'px');
                        img.fadeIn(1000);
                    }
                });
            }
            
        });
        
        
    });
    
    row.find('a.q').each(function(){
        var title=$$(this).attr('xname');
        if (typeof(title)=='undefined') {
            title=value=$$(this).text();
        }
        $$(this).prop('title','Zawęź lub usuń zawężenie: '+title);        
        
    });



    
    row.find('a.q').click(function(event){
        
        if (typeof($$(this).attr('clicked'))!='undefined') return;
        $$(this).attr('clicked','clicked');
        event.stopPropagation();
        
        var txt=$$('#webkameleon_holidays_form').serialize();
        var value=$$(this).text();
        var rel=$$(this).attr('rel');
        if (typeof(rel)=='string' && rel.length) {
            value=rel;
        }
        txt+='&alter='+encodeURIComponent(value);
        
        var x=event.pageX;
        var y=event.pageY - 32;

        
        $$get(holidays_url+'holidays/query?'+txt,function(data){
            if (typeof(data.q.q)=='string' && data.q.q.length) {
                

                var alter='';
                var clas='alter_q';
                if(data.q.more.length>0) {
                    alter=data.q.more;
                    clas+=' alter_more';
                }
                if(data.q.less.length>0) {
                    alter=data.q.less;
                    clas+=' alter_less';
                }
                
                x-=alter.length*8;
                var style='left: '+x+'px; top: '+y+'px;';
                if (alter.length>0)
                {
                    $$("body").append('<div style="'+style+'" class="'+clas+'">'+alter+'</div>');
                
                    setTimeout(function () {
                        var position=$$('#webkameleon_holidays_form input[name="q"]').offset();
                        var newStyle={
                            'left': position.left,
                            'top': position.top,
                            'font-size': '14px'
                        };
                        
                        var animationTime=2000;
                        
                        $$(".alter_q").animate(newStyle,animationTime,function() {
                            $$(".alter_q").fadeOut(600,function() {
                                $$(".alter_q").remove();
                            });
                            replace_input_value(data.q.q);
                            $$.lazygrid_reload();                          
                        });
                        
                        $('html, body').animate({
                            scrollTop: 0
                        }, animationTime);
                    },500);
                }
                else
                {
                    replace_input_value('');
                    $$.lazygrid_reload();                    
                }
                
                
                

            }
        });
        
    

    });

    row.find('.holiday_photo').click(function(){
        var rel=$$(this).attr('rel');
        
        $$get(holidays_url+'holidays/offer/'+rel,function(data) {
            //console.log(data);
            
            if (typeof(data.holidays.obj.info.photos)!='undefined') {
                var inners=$$("#webkameleon_holidays_hotelmodal .carousel-inner div.item-tpl");
                var indicators=$$("#webkameleon_holidays_hotelmodal .carousel-indicators li");
                var desc=data.holidays.obj.info.desc;
                var photos=data.holidays.obj.info.photos;
            
                
                var min=inners.length;
                if (photos.length<min) min=photos.length;
                
                for (i=0;i<min;i++)
                {
                    $$(indicators[i]).css('display','inline-block');
                    $$(inners[i]).removeAttr('style');
                    $$(inners[i]).find('img').attr('src',photos[i]);
                    //$$(inners[i]).find('h3').text(desc[i].subject);
                    //$$(inners[i]).find('p').html(desc[i].content);
                    $$(inners[i]).addClass('item');
                    
                }
                for (i=min;i<inners.length;i++)
                {
                    $$(indicators[i]).css('display','none');
                    $$(inners[i]).removeClass('item');
                    $$(inners[i]).css('display','none');
                    
                }                
                
                $$('#webkameleon_holidays_hotelmodal h4.modal-title').html(data.holidays.obj.name+' '+data.holidays.stars);
                $$('#webkameleon_holidays_hotelmodal h5.modal-country').html(data.holidays.obj.country+', '+data.holidays.obj.region);
                
                
                
                var html='';
                
                for (i=0;i<desc.length;i++)
                {
                    html+='<h4>'+desc[i].subject+'</h4>';
                    html+='<p>'+desc[i].content+'</p>';
                }
                $$('#webkameleon_holidays_hotelmodal #desc_desc').html(html);
                
                var text=$$('#webkameleon_holidays_hotelmodal .btn-primary').attr('text')+data.holidays.dep_from+', '+data.holidays.startDate.DDD+' '+data.holidays.startDate.D+' '+data.holidays.startDate.MMM;
                $$('#webkameleon_holidays_hotelmodal .btn-primary').text(text).click(function() {
                    var url=$$(this).attr('rel');
                    url=url.replace('[adt]',data.holidays.adt);
                    url=url.replace('[chd]',data.holidays.chd);
                    url=url.replace('[id]',data.holidays.id);
                    
                    location.href=url;
                });
                
                
                
                if (data.holidays.obj.xLat!=0 && data.holidays.obj.xLong!=0) {
                    $$('#webkameleon_holidays_hotelmodal #map_tab').show();
                    
                    if (typeof(wk_map)!='undefined')
                    {
                        wk_map.setCenter(new google.maps.LatLng(data.holidays.obj.xLat, data.holidays.obj.xLong));
                        wk_marker.setPosition( new google.maps.LatLng( data.holidays.obj.xLat, data.holidays.obj.xLong ) );
                    }
                    
                } else {
                    
                    $$('#webkameleon_holidays_hotelmodal #map_tab').hide();
                }
                
                $$('#map_tab').click(function(){
                    
                    if (typeof(wk_map)=='undefined') {
                        var mapOptions = {
                            zoom: 14,
                            minzoom: 1,
                            mapTypeControl: true,
                            zoomControl: true,
                            scaleControl: true,
                            streetViewControl: true,          
                            center: new google.maps.LatLng(1,1)
                        }
                        
                        wk_map = new google.maps.Map(document.getElementById('modal_map'),mapOptions);
                        wk_marker = new google.maps.Marker({
                                position: mapOptions.center,
                                map: wk_map
                        }); 
                    }

    
                    wk_map.setCenter(new google.maps.LatLng(data.holidays.obj.xLat, data.holidays.obj.xLong));
                    wk_marker.setPosition( new google.maps.LatLng( data.holidays.obj.xLat, data.holidays.obj.xLong ) );
                    
                });
                
                $$("#webkameleon_holidays_hotelmodal").modal('show');
            }

            
            
            
        });
        
        return false;
    });

    


};

$$('#webkameleon_holidays_form').submit(function() {
    $$.lazygrid_reload();
    return false;
});

$$('#webkameleon_holidays_form .glyphicon').click (function() {
    $$("#webkameleon_holidays_helpmodal").modal('show');

});

function webkameleon_holidays_helpmodal_click()
{
    var helpFound=$$('#webkameleon_holidays_helpmodal');
    if (helpFound.length==0) {
        setTimeout(webkameleon_holidays_helpmodal_click,200);
    } else {
        $$('#webkameleon_holidays_helpmodal .modal-body .example').click(function() {
            replace_input_value($$(this).text());
            $$("#webkameleon_holidays_helpmodal").modal('hide');
            $$('#webkameleon_holidays_form a').trigger('click');
        });        
    }
}

webkameleon_holidays_helpmodal_click();

