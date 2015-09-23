(function ( $ ) {
    var opt;
 
    
    $.extend({
        lazygrid_scroll: function() {
            var scroll_to = $(opt.results).find('.lazyload_grid_scroll_to');
        
            if (typeof(scroll_to.get(0))=='undefined')
            {
                $.lazygrid_log('SCROLL: no object with class lazyload_grid_scroll_to');
                return; 
            }
            
            var hT = scroll_to.offset().top,
                hH = scroll_to.outerHeight(),
                wH = opt.winheight,
                wS = $(window).scrollTop();
                
            
            var h3=hT+hH-wH;
            var dbg='SCROLL: '+wS+' > '+h3+' : '+(wS > h3)
            $.lazygrid_footerlog(dbg);
            $.lazygrid_log(dbg);
              
            if (1.3*wS > h3){
                $.lazygrid_log('SCROLL: scroller reached');
                $(opt.results).find('.lazyload_grid_scroll_to').addClass('lazyload_grid_scroll_to_wait').removeClass('lazyload_grid_scroll_to');
                $.lazygrid_load();
            }            
        },
        lazygrid_reload: function() {
            $(opt.results).html('<div class="lazyload_grid_scroll_to_wait"></div>');
            opt.offset=0;
            $.lazygrid_load();
            
        },
        
        lazygrid_load: function() {
            var txt='';
            
            if (opt.form!=null) {
                txt=opt.form.serialize();
                txt='&'+txt;
            }
            
            var url=opt.url+'?offset='+opt.offset+txt;
            
            dbg='LOAD: loading offset '+opt.offset+', url='+url;
            $.lazygrid_log(dbg);
            $.lazygrid_footerlog(dbg);
            
            
            $.ajax({
                url: url,
                xhrFields: {
                    withCredentials: true
                },
                success: function (r) {
                    $.lazygrid_log(r);
                    $(opt.results).find('.lazyload_grid_scroll_to_wait').remove();
                    
                    
                    var html=opt.template;
                                    
                    
                    data=r.data;
                    for(i=0;i<data.length;i++)
                    {
                        html2=$.lazygrid_smekta(html,data[i]);
                        var newrow=$(html2).appendTo(opt.results).fadeIn(200*(i+1));
                        opt.eachrow(newrow,i+opt.offset,{options:r.options,system:r.x_system});
                        
                    }
                    opt.offset=r.options.next_offset;
                    
            
                    if (data.length>0) {
                        $(opt.results).append('<div class="lazyload_grid_scroll_to"></div>');
                        $(window).scroll(function() {
                            $.lazygrid_scroll();
                        }); 
                        $.lazygrid_log('LOAD: waiting to scroll');
                        
                        //post_lazyload();
                    }
            
                    
                    $.lazygrid_log('LOAD: data loaded ('+data.length+'), offset->'+opt.offset);
                    
                }
            });   
        
        },
        
        lazygrid_smekta: function smekta(pattern,vars) {
            var key,re;
            
            for (key in vars)
            {
                if (vars[key]==null)  vars[key]='';
                
                if (typeof(vars[key])=='object') {
                    re=new RegExp('\\[loop:'+key+'\\](.|[\r\n])+\\[endloop:'+key+'\\]',"g");
                    var loop=pattern.match(re);
                    if (loop!=null) {
                        
                        for (var l=0;l<loop.length && l<7;l++)
                        {
                            var loopstring=loop[l];
                            var loopcontents=loop[l].substr(7+key.length);
                            loopcontents=loopcontents.substr(0,loopcontents.length-10-key.length);
                            var loopresult='';
                            for (var k=0;k<vars[key].length;k++)
                            {
                                loopresult+=smekta(loopcontents,vars[key][k]);
                            }
                            pattern=pattern.replace(loopstring,loopresult);
                        }
                        
                    }
                        
                }
                
                
                re=new RegExp('\\[if:'+key+'\\](.|[\r\n])+\\[endif:'+key+'\\]',"g");
                if (vars[key].length==0 || vars[key]==null || vars[key]=='0') pattern=pattern.replace(re,'');
                
                re=new RegExp('\\[if:!'+key+'\\](.|[\r\n])+\\[endif:!'+key+'\\]',"g");
                if (vars[key].length>0 || vars[key]) pattern=pattern.replace(re,'');
                
                
                re=new RegExp('\\['+key+'\\]',"g");
                pattern=pattern.replace(re,vars[key]);
                
                
                pattern=pattern.replace('[if:'+key+']','');
                pattern=pattern.replace('[endif:'+key+']','');
                pattern=pattern.replace('[if:!'+key+']','');
                pattern=pattern.replace('[endif:!'+key+']','');        
                
            }
            
            return pattern;
        
        },
        
        lazygrid_log: function (txt) {
            //console.log(txt);
        },
        
        lazygrid_footerlog: function (txt) {
            //$('.footer a').html(txt);
        }        
    });
    
    
    $.fn.lazygrid = function(options) {
        opt=$.extend({
            url: null,
            form: null,
            offset: 0,
            results: this,
            template: null,
            winheight: $(window).height(),
            eachrow: function (row,i,options) {
                $.lazygrid_log("NATIVE ROW NOP: "+i);
            }
        },options);
        
        if (opt.template==null) {
            opt.template=$(this).html();
        }
        
        
        return this;
    };
    
    
 
}( jQuery ));

