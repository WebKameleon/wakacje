<?php

class merlinController extends Controller {
    
    
    protected $merlin;
    protected $star='🌠';
    
    
    public function init()
    {
        $config=Bootstrap::$main->getConfig();
        $this->merlin=new Merlin($config['merlin.login'],$config['merlin.pass'],$config['merlin.tourOp']);
    }
    
    
    protected function getConfig()
    {
        $token='config.'.Bootstrap::$main->getConfig('site');
        
        $config=Tools::memcache($token);
        if ($config && !$this->data('debug')) return $config;
        
        $config=json_config(__DIR__.'/../config/merlin.json',false,false);
        
        $config['words']=[];
        $config['words-with-space']=[];
        
        $extended=['number'];
        foreach ($config['dict'] AS $field=>$fields) {
            foreach ($fields AS $code=>$values)
            {
                foreach($values['words'] AS $d) {
                    $w=[];
                    if ($field!='extended') $w['field']=$field;
                    foreach($extended AS $f) if (isset($values[$f])) $w[$f]=$values[$f];
                    $value='value';
                    if (isset($values['name'])) $value=$values['name'];
                    
                    if (isset($values['evalue'])) $w[$value]='~'.$values['evalue'];
                    elseif (isset($values['value'])) $w[$value]=$values['value'];
                    elseif (!in_array($field,$extended)) $w[$value]=$code;
                    
                    $config['words'][$d]=$w;
                }
            }
        }
        
        unset($config['dict']);
        
        //mydie($config['words']);
        
        $reg=$this->merlin->getRegions('F',null,$this->data('debug')?false:true,true);
   
        $dest=[];
        $i=0;
        $far=[];
   
       
        foreach ($reg AS $r)
        {
            if (!isset($r['region'])) $r['region']='';
            if (!isset($r['country'])) continue;
            
        
            $country=trim(mb_strtolower($r['country'],'utf-8'));
            $region=trim(mb_strtolower($r['region'],'utf-8'));
            
            if ($r['price']>=Bootstrap::$main->getConfig('merlin.far.price')
                && !in_array($country,['bułgaria','portugalia','hiszpania','włochy','grecja','francja','niemcy','cypr','chorwacja']) ) {
                
                $far[]=$r['id'];
            }
                
            
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
                $w2=Tools::str_to_url($w);
                if ($w2==$w) continue;
                $w=$w2;
                
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
    
        $config['far']=$far;
        
        
        if ($this->data('debug')==1) {
            $hotels=$this->merlin->getFilters(['ofr_type'=>'F'],'obj_xCode',$this->data('debug')?false:true);
            
            $words=array_keys($config['words']);
            $hotel_map=[];
            
            foreach($hotels AS $hotel) {
                $hotel=str_replace(['+',',',';','~','(',')','"'],' ',$hotel);
                $hotel=str_replace(["'"],'"',$hotel);
                $hotel=preg_replace('/\s+/',' ',trim($hotel));
                $hotel=mb_strtolower($hotel,'utf-8');
                
                foreach(explode(' ',$hotel) AS $h) {
                    $h=Tools::str_to_url($h);
                    if (strlen($h)<4) continue;
                    if (isset($config['words'][$h])) continue;
                    if (isset($hotel_map[$h])) continue;
                    
                    $lev=false;
                    foreach($words AS $w)
                    {
                        if (levenshtein($h,$w)<=2) {
                            $lev=true;
                            break;
                        }
                    }
                    if ($lev) continue;  
                    $hotel_map[$h] = ['field'=>'ftsName','value'=>$h];
                }
            }
            
            
            $config['hotels']=$hotel_map;
        
        }
       
        //mydie($config,count($config['words']));
       
        foreach(array_keys($config['words']) AS $word) if (strstr($word,' ')) $config['words-with-space'][]=$word; 
        
        
        return Tools::memcache($token,$config,4*3600);
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