<?php
class holidaysController extends Controller {
    
    protected $merlin;
    
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
        
        $reg=$this->merlin->getRegions();
        //dest
        $dest=[];
        $i=0;
        foreach ($reg AS $r)
        {
            if (!isset($r['region'])) $r['region']='';
            
            $country=trim(mb_strtolower($r['country']));
            $region=trim(mb_strtolower($r['region']));
            
            foreach($config['region_shit'] AS $shit) {
                if (strstr($region,$shit)) $region='';
            }
            
            
            foreach($config['dest_shit'] AS $shit) {
                if (strstr($country,' ') && strstr($country,$shit)) $country=trim(str_replace($shit,'',$country));
                if (strstr($region,' ') && strstr($region,$shit)) $region=trim(str_replace($shit,'',$region));
                
            }
            $i++;
            if ( strstr($country,' ') || strstr($region,' ')) mydie($r,"$country:$region:$i/".count($reg));   
        }
        
        mydie($reg);
        
        return $config;
    }
    
    protected function word2cond($word)
    {
        $config=$this->getConfig();
        if (isset($config['words'][$word])) return $config['words'][$word];
        if (preg_match('/[0-9]+/',$word)) return ['number'=>$word+0];
        
        switch ($word)
        {
            case 'od':
            case 'min':
            case 'minimium':
            case 'powyżej':
            case 'powyzej':    
            case 'po':    
                return ['from'=>1];

            case 'do':
            case 'max':
            case 'maks':
            case 'maksimum':
            case 'poniżej':
            case 'ponizej':                 
            case 'przed':    
                return ['to'=>1];
        }
    }
    
    protected function lastDayOfMonth($m)
    {
        if (in_array($m+0,[1,3,5,7,8,10,12])) return '31';
        if (in_array($m+0,[4,6,9,11])) return '30';
        
        $year=$date('Y');
        if (date('m')>2) $year++;
        if ($year%4==0) return '29';
        return '28';
    }
    
    protected function q2cond($q)
    {
        $q=preg_replace('/\s+/',' ',trim($q));
        if (!$q) return [];
        
        $q=mb_strtolower($q,'utf-8');
        
        $cond=[];
        $from=$to=$number=0;
        foreach(explode(' ',$q) AS $w)
        {
            $c=$this->word2cond($w);
            
            if (isset($c['from'])) {
                $from=1;
                continue;
            }

            if (isset($c['to'])) {
                $to=1;
                continue;
            }
            
            if (isset($c['number'])) {
                if ($c['number']>31 && ($from || $to) )
                {
                    if ($from) $cond['min_price']=$c['number'];
                    else $cond['max_price']=$c['number'];
                }
                if ($c['number']>=1000 && !$from && !$to )
                {
                    $cond['max_price']=$c['number'];
                }                
                
                if ($c['number']<=31) {
                    $number=$c['number'];
                    continue;
                }
                if (!$number) $from=$to=0;
            }
            
            if (isset($c['field'])) {
                
                $field=$c['field'];
                
                switch ($field) {
                    
                    case 'month':
                        $month=$c['value'];
                        $year=date('Y');
                        if ($month<date('m')) $year++;
                        if ($from) {
                            if (!$number) $number='01';
                            $cond['from']=$year.'-'.$month.'-'.$number;
                        } elseif ($to) {
                            if (!$number) $number=$this->lastDayOfMonth($month);
                            $cond['to']=$year.'-'.$month.'-'.$number;
                        } else {
                            $cond['from']=$year.'-'.$month.'-01';
                            $cond['to']=$year.'-'.$month.'-'.$this->lastDayOfMonth($month);
                        }
                        break;
                    
                    
                    default:
                        if (isset($cond[$field])) {
                            $cond[$field].=','.$c['value'];
                        } else {
                            $cond[$field]=$c['value'];
                        }
                        break;
                }
                
                $from=$to=$number=0;
            }
        }
        
        return $cond;
        
        $data=$this->merlin->getFilters(['ofr_type'=>'F'],'trp_depName');
        
     
        //mydie($data);  
  
    
        //return ['dep'=>'TXL'];
    
        return ['dep_name'=>'Łódź'];
    }
    
    public function get()
    {
        $opt=$this->nav_array(Bootstrap::$main->getConfig('merlin.search.limit'));
              
        
        
        $cond=$this->data('q')?$this->q2cond($this->data('q')):[];
        
        $cond['type']='F';
        
        $offers=$this->merlin->getGrouped($cond,'',$opt['limit'],$opt['offset']);
        $opt['next_offset']=$opt['offset']+$opt['limit'];
        
        //mydie($this->merlin->debug);
        
        $result=[];
        foreach ($offers['result'] AS $ofr)
        {
            if (isset($ofr['obj']['info']['photos']) || isset($ofr['obj']['info']['thumb'])) {
                $r=[];
                $r['photo']=isset($ofr['obj']['info']['photos']) && count($ofr['obj']['info']['photos']) ? $ofr['obj']['info']['photos'][0] : $ofr['obj']['info']['thumb'];  
                foreach($ofr AS $k=>$v)
                {
                    if (!is_array($v)) $r[$k]=$v;
                    else foreach($v AS $kk=>$vv) {
                        if (!is_array($vv)) $r[$k.'_'.$kk]=$vv;
                    }
                    
                }
                   
                foreach (['trp_depName','trp_desDesc','obj_serviceDesc','obj_roomDesc'] AS $k)
                    if (isset($r[$k]))
                        $r[$k]=mb_strtolower($r[$k],'utf-8');
                
                $result[]=$r;
            }
        }
        
        if ($this->data('debug')) mydie([$cond,$result]);
        
        
        return array('status'=>true,'options'=>$opt,'data'=>$result);
  
    }
}
