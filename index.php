<?php

    $_SERVER['backend_start']=microtime(true);
    include __DIR__.'/backend/include/all.php';
    autoload([__DIR__.'/classes',__DIR__.'/controllers']);
    $config=json_config(__DIR__.'/config/application.json');
    $bootstrap = new Bootstrap($config);

    $root=$bootstrap->getRoot();
    
    $uri=$_SERVER['REQUEST_URI'];
    if ($pos=strpos($uri,'?')) $uri=substr($uri,0,$pos);
    $uri=substr($uri,strlen($root));
    $q=urldecode(trim(str_replace(['-','/',"'"],' ',$uri)));
    
    $google_part='';
    $description=$q?str_replace('{q}',$q,$bootstrap->getConfig('page.description')):$bootstrap->getConfig('page.description0');
    

    if ( isset($_GET['_google']) || ( isset($_SERVER['HTTP_USER_AGENT']) && strstr(strtolower($_SERVER['HTTP_USER_AGENT']),'google')))
    {
        if ($q) {
            $google_part='<h1>'.$q.'</h1>'."\n";
            
            
            $template=new templateController();
            $template->init();
            $holidays=new holidaysController(0,['q'=>$q]);
            $holidays->init();
            
            $tmpl=$template->get(false);
            
            $tmpl=preg_replace('~\[if:[^\]]+\]~','',$tmpl);
            $tmpl=preg_replace('~\[endif:[^\]]+\]~','',$tmpl);
            $tmpl=preg_replace('~\[loop:[^\]]+\]~','',$tmpl);
            $tmpl=preg_replace('~\[endloop:[^\]]+\]~','',$tmpl);
            $tmpl=str_replace('style="display:none"','',$tmpl);
            
            $result=$holidays->get(10);
            
            foreach ($result['data'] AS $rec)
            {
                $row=$tmpl;
                foreach($rec AS $k=>$v) {
                    if (is_array($v)) continue;
                    $row=str_replace("[$k]",$v,$row);
                }
                $google_part.=$row;
            }
        
        } else {
            $google_part='<h1></h1>'."\n";
        
            $template=new templateController();
            $template->init();
            $countries=$template->countries();
            $google_part.='<ul>';
            
            $countries=array_merge($countries,['Majorka','Wyspy Kanaryjskie','Kreta','Kos']);
            sort($countries);
            foreach($countries AS $country)
            {
                $country=str_replace('Republika Południowej Afryki','RPA',$country);
                $country=str_replace('Trynidad I Tobago','Trynidad i Tobago',$country);
                
                $google_part.='<li>';
                $google_part.='<a href="'.Tools::str_to_url($country).'">';
                $google_part.=$country;
                $google_part.='</a></li>';
                
            }
            $google_part.='</ul>';
        
        }
        
    }
    
    $title=$q?str_replace('{q}',$q,Bootstrap::$main->getConfig('page.title')):Bootstrap::$main->getConfig('page.title0');
    
?><html lang="pl">
<head>
    <meta charset="utf-8"/>
    <title><?php echo $title;?></title>
    <meta property="description" content="<?php echo $description;?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?php echo $title;?>" />
    <meta property="og:description" content="<?php echo $description;?>" />
    <meta property="og:image" content="/img/og-wakacje.jpg" />    
    
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="<?php echo $root;?>resources/demo.css"/>
</head>
<body>
    
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-68496645-1', 'auto');
  ga('send', 'pageview');

</script>

<div style="overflow: hidden" id="webkameleon-top-demo">
<img src="<?php echo $root;?>img/beach-xs.jpg" alt="Wakacje" class="bkgimg hidden-lg hidden-md" style="display: none"/>
<video class="bkgimg hidden-xs hidden-sm" autoplay loop video-list >
    <source src="<?php echo $root;?>img/beach-loop.mp4" type="video/mp4; codecs=avc1.42E01E,mp4a.40.2"/>
    <source src="<?php echo $root;?>img/beach-loop.ogv" type="video/ogg"/>
</video>
</div>

<?php echo $google_part; ?>

<?php if ($q): ?>
<script>
    var master_q = '<?php echo $q;?>';
</script>
<?php endif; ?>

<script async src="<?php echo $root;?>holidays.js"></script>

<span id="scroll-top">
    <a class="scrollup" style="display: inline;"><i class="icomoon-arrow-up"></i></a>
</span>

<div class="container-fluid" id="webkameleon_holidays_results"></div>



</body>
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
<script src="<?php echo $root;?>resources/home.js"></script>
</html>
