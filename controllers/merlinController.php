<?php

class merlinController extends Controller {
    
    
    protected $merlin;
    protected $star='🌠';
    
    
    public function init()
    {
        $config=Bootstrap::$main->getConfig();
        $this->merlin=new Merlin($config['merlin.login'],$config['merlin.pass']);
    }
    
    
    protected function getConfig()
    {
        $config=json_config(__DIR__.'/../config/merlin.json',false,false);
        
        $config['words']=[];
        
        foreach (['dep','month'] AS $field) {
            foreach ($config[$field] AS $code=>$dep)
            {
                foreach($dep AS $d) $config['words'][$d]=['field'=>$field,'value'=>$code];
            }
        }
        
        $reg=$this->merlin->getRegions('F');
        //dest
        $dest=[];
        $i=0;
   
       
        foreach ($reg AS $r)
        {
            if (!isset($r['region'])) $r['region']='';
            if (!isset($r['country'])) continue;
            
        
            $country=trim(mb_strtolower($r['country'],'utf-8'));
            $region=trim(mb_strtolower($r['region'],'utf-8'));
            
            
            foreach($config['dest_shit'] AS $shit) {
                for($ii=0;$ii<2;$ii++) {
                    if (strstr($country,' ') && strstr($country,$shit)) $country=trim(str_replace($shit,'',$country));
                    if (strstr($region,' ') && strstr($region,$shit)) $region=trim(str_replace($shit,'',$region));
                }
            }
            $i++;
            if ( strstr($country,' ') || strstr($region,' ')) {
                continue;
                mydie($r,"$country:$region:$i/".count($reg));
            }
            foreach ([$country,$region] AS $w)
            {
                if (!$w) continue;
                if (!isset($dest[$w])) {
                    $dest[$w]=['field'=>'dest','value'=>$r['id']];
                } else {
                    $dest[$w]['value'].=','.$r['id'];
                }
            }
        }
        
        foreach (['dest'] AS $field) {
            foreach ($config[$field] AS $code=>$dep)
            {
                foreach($dep AS $d) {
                    if (isset($dest[$d])) $dest[$d]['value'].=','.$code;
                    else $dest[$d]=['field'=>'dest','value'=>$code];
                }
            }
        }        
        
        foreach($dest AS $w=>$r)
        {
            if (!isset($config['words'][$w])) $config['words'][$w]=$r;
        }
    
        
        return $config;
    }
    
    
    protected function distance($p1,$p2)
    {
        
        if (!is_array($p1)) $p1=explode(',',$p1);
        if (!is_array($p2)) $p2=explode(',',$p2);
        
        return sqrt(
                    pow( ($p1[1]-$p2[1]) * cos($p1[0] * pi() / 180),2)
                            +
                            pow($p2[0]-$p1[0],2)
                        ) * pi() * 12756.274 / 360 ;

    }
    
}