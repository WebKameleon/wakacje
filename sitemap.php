<?php
  $_SERVER['backend_start']=microtime(true);
  include __DIR__.'/backend/include/all.php';
  autoload([__DIR__.'/classes',__DIR__.'/controllers']);
  $config=json_config(__DIR__.'/config/application.json');
  $bootstrap = new Bootstrap($config);
  
  function sitemap_date($t)
  {
    return date('c',$t);
    
  }
  $url='http://'.$_SERVER['HTTP_HOST'].'/';
  $sitemap=[];
  $sitemap[]=['loc'=>$url,'priority'=>1,'lastmod'=>sitemap_date(strtotime(date('Y-m-d')))];
  
  $map=json_decode(file_get_contents(Tools::saveRoot('map.json')),true);
  foreach ($map AS $from=>$reg)
  {
    foreach (array_keys($reg) AS $r)
    {
      $sitemap[]=['loc'=>$url.$r.'/'.$from,'priority'=>0.9,'lastmod'=>sitemap_date(strtotime(date('Y-m-d')))];
    }
    
  }
  
  
  if (isset($_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['REQUEST_URI'])) {
    Tools::log('bots',['agent'=>$_SERVER['HTTP_USER_AGENT'],'uri'=>$_SERVER['REQUEST_URI']]);
  }
  
  header('Content-type: application/xml; charset=utf-8');
  
  
?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">  
<?php foreach($sitemap AS $url): ?>
  <url>
    <loc><?php echo $url['loc']?></loc>
    <lastmod><?php echo $url['lastmod']?></lastmod>
    <priority><?php echo $url['priority']?></priority>
  </url>
<?php endforeach; ?>
</urlset>

  