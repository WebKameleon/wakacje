<?php
class holidaysController extends merlinController {
    
    
    
    protected function word2cond($word)
    {
        $config=$this->getConfig();
        $conf=Bootstrap::$main->getConfig();
        
        if (substr($word,0,6)=='hotel:') {
            return ['field'=>'hotel','value'=>substr($word,6)];
        }  
        
        if (isset($config['words'][$word])) return $config['words'][$word];
        if (preg_match('/[0-9\-]+/',$word)) return ['number'=>$word];
        
        switch ($word)
        {
            case 'jeden':
                return ['number'=>1];
            
            case 'dwa':
                return ['number'=>2];
            
            case 'trzy':
                return ['number'=>3];            
            
            case 'za':
            case 'przyszłym':
            case 'przyszły':
            case 'przyszlym':
            case 'przyszly':                
                return ['in'=>1];
            
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
            
            case 'dzisiaj':
            case 'dziś':
                return ['field'=>'date','value'=>date('Y-m-d')];

            case 'last':
            case 'minute':
            case 'lastminute':
                $conf=Bootstrap::$main->getConfig();
                return ['field'=>'date','value'=>date('Y-m-d').':'.date('Y-m-d',time()+$conf['lastminute.days']*24*3600)];
                
            case 'jutro':
                return ['field'=>'date','value'=>date('Y-m-d',time()+24*3600)];

            case 'pojutrze':
                return ['field'=>'date','value'=>date('Y-m-d',time()+2*24*3600)];
            
            case 'tydzień':
            case 'tydzien':
            case 'tygodnie':
            case 'tygodni':
            case 'tygodniu':
                return ['field'=>'weeks'];
            
            case 'dni':
            case 'dzień':
                return ['field'=>'duration'];
            
            case 'osoby':
            case 'osób':
            case 'osob':
            case 'osoba':
            case 'dorośli':
            case 'dorosłych':
            case 'doroslych':
            case 'dorosli':
                return ['field'=>'adt'];

            case 'dziecko':
            case 'dzieckiem':
                return ['field'=>'chd','number'=>1];
                
            case 'dzieci':
            case 'dziećmi':
            case 'dziecmi':
                return ['field'=>'chd','number'=>2];

        
            case 'singli':
            case 'single':
            case 'singiel':
                return ['field'=>'adt','number'=>1];
                
        
            case 'tanie':
            case 'tanio':
            case 'taniego':    
                return ['number'=>$conf['merlin.cheap']];
            
            case 'daleko':
            case 'dalekie':
            case 'dalekia':
            case 'egzotyka':
            case 'egzotyczne':
            case 'egzotycznie':
                return ['field'=>'dest', 'value'=>implode(',',$config['far'])];
        }
        
 

    }
    
    protected function lastDayOfMonth($m)
    {
        if (in_array($m+0,[1,3,5,7,8,10,12])) return '31';
        if (in_array($m+0,[4,6,9,11])) return '30';
        
        $year=date('Y');
        if (date('m')>2) $year++;
        if ($year%4==0) return '29';
        return '28';
    }
    
    private function update_cond(Array &$cond,$field,$value,Array &$phraze,Array &$phrazes)
    {
        if (isset($cond[$field])) {
            if (!strstr($cond[$field],$value)) $cond[$field].=','.$value;
        } else {
            $cond[$field]=$value;
        }
        
        if (!isset($phrazes[$field])) $phrazes[$field]=[];
        $phrazes[$field][$value]=implode(' ',$phraze);
        $phraze=[];
    }
    
    protected function q2cond($q)
    {
        $config=$this->getConfig();
        
        $q=str_replace(['+',',',';'],' ',$q);
        $q=preg_replace('/\s+/',' ',trim($q));
        if (!$q) return [];
        $userq=explode(' ',$q);
        $q=mb_strtolower($q,'utf-8');
        
        $q_token='q2cond.'.Bootstrap::$main->getConfig('site').'.'.md5($q);
        $cond=Tools::memcache($q_token);
        if ($cond && !$this->data('debug')) {
            $cond['memcache']=true;
            return $cond;
        }
        
        $cond=[];
        $in=$from=$to=$number=$number1=0;
        $phraze_responsible=[];
        $phrazes_responsible=[];
        $unknown=[];
    
        foreach(explode(' ',$q) AS $word_index=>$w)
        {
            $phraze_responsible[]=$userq[$word_index];
            
            $c=$this->word2cond($w);
            
            if (isset($c['in'])) {
                $in=1;
                continue;
            }            
            
            if (isset($c['from'])) {
                $from=1;
                continue;
            }

            if (isset($c['to'])) {
                $to=1;
                continue;
            }
            
            if (isset($c['number']) && !isset($c['field'])) {
                
                $c['number']=explode('-',$c['number']);
                if (!isset($c['number'][1])) $c['number'][1]=0;
                if ($c['number'][1]<$c['number'][0]) $c['number'][1]=0;
                
                if ($c['number'][0]>31 && ($from || $to) )
                {
                    
                    if ($from) {
                        $field='min_price';
                    }
                    else {
                        $field='max_price';
                    }
                    
                    if ($c['number'][1]) {
                        $field='min_price';
            
                        $cond['max_price']=$c['number'][1];
                    }
                    
                    $this->update_cond($cond,$field,$c['number'][0],$phraze_responsible,$phrazes_responsible);
                }
                
                if ($c['number'][0]>=500 && !$from && !$to )
                {
                    if (!$c['number'][1]) {
                        $cond['max_price']=$c['number'][0];
                    }
                    else {
                        $cond['min_price']=$c['number'][0];
                        $cond['max_price']=$c['number'][1];
                    }
                }                
                
                if ($c['number'][0]<=31) {
                    $number=$c['number'][0];
                    $number1=$c['number'][1];
                    continue;
                }
                
                if (!$number) $from=$to=$in=0;
            }
            
            
            if (isset($c['field']) && strlen($c['field'])) {
                
                $field=$c['field'];
                
                switch ($field) {
                    
                    case 'chd':
                    case 'duration':
                    case 'adt':
                        if (!$number && isset($c['number'])) $number=$c['number'];
                        if ($number) {
                            $val=$number;
                            if ($number1) $val.=':'.$number1;
                            $this->update_cond($cond,$field,$val,$phraze_responsible,$phrazes_responsible);
                        }
                        break;
                    
                    case 'hotel':
                        $val=explode(':',$c['value']);
                        $this->update_cond($cond,$field,$val[1],$phraze_responsible,$phrazes_responsible);    
                        $cond['op']=strtoupper($val[0]);
                        break;
                    
                    case 'date':
                        $this->update_cond($cond,'from',$c['value'],$phraze_responsible,$phrazes_responsible);
                        $cond['fromto']=$c['value'];
                        break;
                    
                    case 'month':
                        $month=$c['value'];
                        $year=date('Y');
                        if ($month<date('m')) $year++;
                        
                        if ($from) {
                            if (!$number) $number='01';
                            $tmp=$phraze_responsible;
                            $this->update_cond($cond,'from',$year.'-'.$month.'-'.$number,$phraze_responsible,$phrazes_responsible);
                            if ($number1) $this->update_cond($cond,'fromto',$year.'-'.$month.'-'.$number1,$tmp,$phrazes_responsible);
                            
                        } elseif ($to) {
                            if (!$number) $number=$this->lastDayOfMonth($month);
                            $this->update_cond($cond,'to',$year.'-'.$month.'-'.$number,$phraze_responsible,$phrazes_responsible);
                        } else {
                            if ($number) {

                                $val=$cond['fromto']=$year.'-'.$month.'-'.$number;
                                if ($number1) $cond['fromto']=$year.'-'.$month.'-'.$number1;
                                
                                $this->update_cond($cond,'from',$val,$phraze_responsible,$phrazes_responsible);
         
                            } else {
                                $tmp=$phraze_responsible;
                                
                                $this->update_cond($cond,'from',$year.'-'.$month.'-01',$phraze_responsible,$phrazes_responsible);
                                $this->update_cond($cond,'to',$year.'-'.$month.'-'.$this->lastDayOfMonth($month),$tmp,$phrazes_responsible);

                            }
                        }
                        break;
                    
                    case 'weeks':
                        if (!$number) $number=1;
                        if ($in) {
                            $tmp=$phraze_responsible;
                            $dow=date('w');
                            $minus=abs($dow-1);
                            $plus=7-$minus;
                            $this->update_cond($cond,'from',date('Y-m-d',time()+(7*$number-$minus)*24*3600),$phraze_responsible,$phrazes_responsible);
                            $this->update_cond($cond,'fromto',date('Y-m-d',time()+(7*$number+$plus)*24*3600),$tmp,$phrazes_responsible);

                        } else {
                            
                            $number*=7;
                            $val=($number-1).':'.($number+1);
                            $this->update_cond($cond,'duration',$val,$phraze_responsible,$phrazes_responsible);
                        }
                        break;
                    
                    default:
                        $this->update_cond($cond,$field,$c['value'],$phraze_responsible,$phrazes_responsible);
                        
                        break;
                }
                
                $in=$from=$to=$number=$number1=0;
                
                
            } elseif (strlen($w)>3) {
                if (!in_array($w,$config['dest_shit'])) $unknown[]=$w;
            }
        }
        
        return Tools::memcache($q_token,[$cond,$phrazes_responsible,$unknown]);
            

    }
    
    public function get_q()
    {
        return $this->status(Bootstrap::$main->session('q'),true,'q');
    }

    public function get()
    {
     
        $opt=$this->nav_array(Bootstrap::$main->getConfig('merlin.search.limit'));
        $site=Bootstrap::$main->getConfig('site');
        $config=$this->getConfig();
        Bootstrap::$main->system('cfg');
        
        $cond=$this->data('q')?$this->q2cond($this->data('q')):[];
        Bootstrap::$main->system('q2c');
        Bootstrap::$main->session('q',$this->data('q'));
        if (count($cond)) {
            if (!isset($cond[0]['type'])) $cond[0]['type']='F';
            if (!isset($cond[0]['adt'])) $cond[0]['adt']=2;
            
            $offers=isset($cond[0]['hotel']) ?
                $this->merlin->getOffers($cond[0],'date,duration,dep,price',$opt['limit'],$opt['offset'])
                :
                $this->merlin->getGrouped($cond[0],'',$opt['limit'],$opt['offset']);
            
            if (!isset($cond['memcache'])) @Tools::log('query-'.$site,['q'=>$this->data('q'),'count'=>$offers['count'],'cond'=>$cond]);
            
        } else {
            $offers=['result'=>[],'count'=>0];
        }
        Bootstrap::$main->system('mds');
        
        
        $opt['next_offset']=$opt['offset']+$opt['limit'];
        
        $opt['results']='Wyniki: ';
        if (isset($offers['count'])) {
            if ($offers['count']>=1000) $opt['results'].='ponad 1000';
            else $opt['results'].=$offers['count'];
        }
        //mydie($this->merlin->debug);
        
        $result=[];
        foreach ($offers['result'] AS $ofr)
        {
            if (isset($ofr['obj']['info']['photos']) || isset($ofr['obj']['info']['thumb'])) {
                $r=[];
                
                $r['photo']=$ofr['obj']['info']['thumb'];
                
                if (isset($ofr['obj']['info']['photos']) && count($ofr['obj']['info']['photos']))
                {
                    $r['photo'] = $ofr['obj']['info']['photos'][0];
                    if (isset($cond[0]['hotel'])) $r['photo'] = $ofr['obj']['info']['photos'][(count($result)+$opt['offset'])%count($ofr['obj']['info']['photos'])];
                }
                
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
                
                $r['stars']='';
                for($i=0;$i<$r['obj_category'];$i+=10) $r['stars'].=$this->star;
                
                $r['adt'] = isset($cond[0]['adt']) ? $cond[0]['adt'] : 2;
                $r['chd'] = isset($cond[0]['chd']) ? $cond[0]['chd'] : 0;
                
                
                Bootstrap::$main->session('adt',$r['adt']);
                Bootstrap::$main->session('chd',$r['chd']);
                
                
                $r['attr']=[];
                $attr=$r['obj_xAttributes']+0;
                for ($x=0;$x<64;$x++) {
                    $pow=pow(2,$x);
                    if ($x==63) $pow=-9223372036854775808;
                    if ($pow & $attr) $r['attr'][]=[
                                            'x'=>$x+1,
                                            'name'=>$config['attr_name'][$x+1],
                                            'active'=>isset($cond[1]['attr'][$x+1])
                                            ];
                }
                
                //0x68 00 08 08 45 02 20 60
                //0x78 00 21 00 55 b7 20 38
                
                $r['hotel_selected']=isset($cond[0]['hotel']);
                $result[]=$r;
            }
        }
        
        
        if ($this->data('debug')) {
            $ret=['conditions'=>$cond,'result'=>$result];
            if($this->data('debug')==2)  $ret['merlin']=$this->merlin->debug;
            mydie($ret);
        }
        
        return array('status'=>true,'options'=>$opt,'data'=>$result);
  
    }
    
    public function get_query()
    {
        $config=$this->getConfig();
        
        $q=trim($this->data('q'));
        $qlen=strlen($q);
        $cond=$this->q2cond($q);
        
        $more='';
        $less='';
        
        if ($this->data('alter')) {
            $wcond=$this->q2cond($this->data('alter'));
            
            if (count($wcond)){
                foreach ($wcond[1] AS $what=>$filters)
                {
                    if (isset($cond[1][$what])) {
                        foreach ($filters AS $code=>$name)
                        {
                           if (isset($cond[1][$what][$code]))
                           {
                                $q=str_replace($cond[1][$what][$code],'',$q);
                                $less=$cond[1][$what][$code];
                                $q=str_replace('  ',' ',trim($q));
                                continue 2;
                           }
                        }
                    }
                    
                    foreach ($filters AS $code=>$name) {
                        if ($what=='dep' && isset($config['dep_from'][$code])) {
                            $q.=' '.$config['dep_from'][$code];
                            $more=$config['dep_from'][$code];
                        } else {
                            $q.=' '.$name;
                            $more=$name;
                        }
                    }
                    
                }
            }
        }
        
        if (strlen($q)!=$qlen) return $this->status(['q'=>$q,'more'=>$more,'less'=>$less],true,'q');
        
        return $this->status('',true,'q');
    }
    
    public function get_offer()
    {
        $config=$this->getConfig();
        $offer=$this->merlin->getOfferOnToken($this->id);
        
        $offer['stars']='';
        if (isset($offer['obj']['category'])) for($i=0;$i<$offer['obj']['category'];$i+=10) $offer['stars'].=$this->star;
        
        if (isset($offer['obj']['info']['desc'])) {
            $desc=$offer['obj']['info']['desc'];
            $desc2=[];
            foreach($desc AS $d) {
                if (is_array($d['subject'])) continue;
                if (is_array($d['content'])) continue;
                if (!in_array(strtolower(trim($d['subject'])),['category','kategoria','region','kraj','kategoria lokalna']) )
                {
                    $pm=[];
                    if (preg_match_all('~<b>([^<]+)</b>([^<]+)~i',$d['content'],$pm)) {
                    
                        for ($i=0;$i<count($pm[1]);$i++)
                        {
                            $desc2[]=[
                                'subject'=>str_replace(':','',$pm[1][$i]),
                                'content'=>trim($pm[2][$i])
                            ];
                            
                        }
                    } else {
                        $desc2[]=$d;
                    }
                }
            }
            $offer['obj']['info']['desc']=$desc2;
            
            $offer['dep_from']=$config['dep_from'][$offer['trp']['depCode']];
            $offer['adt']=Bootstrap::$main->session('adt');
            $offer['chd']=Bootstrap::$main->session('chd');
            
            //mydie($offer['obj']['info']);
        }
        
        if ($this->data('debug'))
        {
            $ret=[$offer];
            if ($this->data('debug')==2) $ret[1]=$this->merlin->debug; 
            mydie($ret);
        }
        return $this->status($offer);
    }
    
}
