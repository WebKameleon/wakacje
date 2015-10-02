<?php
class holidaysController extends merlinController {
    
    
    protected function word2cond($word,$levenstein=0)
    {
        $w_token='w2cond.'.$levenstein.'.'.Bootstrap::$main->getConfig('site').'.'.md5($word);
        $ret=Tools::memcache($w_token);
        if ($ret && !$this->data('debug')) return $ret;
        
        $config=$this->getConfig();
        $conf=Bootstrap::$main->getConfig();
        
        if (in_array($word,$config['shit_word']) || in_array($word,$config['dest_shit'])) return false;   

        
        if (substr($word,0,6)=='hotel:') {
            return ['field'=>'hotel','value'=>substr($word,6)];
        }
        
        if (preg_match('/[0-9\-]+/',$word)) return Tools::memcache($w_token,['number'=>$word]);
        
        if ($levenstein==0)
        {
            if (isset($config['words'][$word])) {
                Bootstrap::$main->system('exact:'.$word);
                return Tools::memcache($w_token,$config['words'][$word]);
            }
        }
        
        
        if ($levenstein>0)
        {
            $word=Tools::str_to_url($word);
            $lev=[];
            foreach(array_keys($config['words']) AS $w) {
                $l=levenshtein($w,$word);
                if ($l<=$levenstein) $lev[$w]=$l;
                
            }
            
            if (count($lev)) {
                asort($lev);
                $ak=array_keys($lev);
                $ret=$config['words'][$ak[0]];
                $ret['word']=$ak[0];
                $ret['levenstein']=$lev[$ak[0]];
                Bootstrap::$main->system('near:'.$word);
                return Tools::memcache($w_token,$ret);
            }
            
        
            if (isset($config['hotels'][$word])) {
                Bootstrap::$main->system('exact-hotel:'.$word);
                return Tools::memcache($w_token,$config['hotels'][$word]);
            }
        }
        
        
        return null;
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
        $q=str_replace(['+',',',';','~'],' ',$q);
        $q=str_replace(["'"],'"',$q);
        $q=preg_replace('/\s+/',' ',trim($q));
        
        while (preg_match('/"[^"]* [^"]*"/',$q) ) $q=preg_replace('/"([^" ]*) ([^"]*)"/','"\\1~\\2"',$q);
        $q=str_replace('"','',$q);

        if (!$q) return [];
        $userq=explode(' ',$q);
        $q=mb_strtolower($q,'utf-8');
        
        
        foreach ($config['words-with-space'] AS $wws) $q=str_replace($wws,str_replace(' ','~',$wws),$q);
        
        //mydie($config['words-with-space'],$q);
        $changed=$q;
        
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
            $w=str_replace('~',' ',$w);
            $phraze_responsible[]=$userq[$word_index];
            
            $c=$this->word2cond($w);
            
            if (is_null($c) && strlen($w)>3) {
                $c=$this->word2cond($w,2);
                if (isset($c['levenstein']) && $c['levenstein']==2) $changed=str_replace($w,$c['word'],$changed);
            }
            
            if ($c===false) continue;
            
            foreach(['value','number'] AS $f) {
                if (isset($c[$f]) && strlen($c[$f]) && $c[$f][0]=='~')
                {
                    $cv=substr($c[$f],1);
                    eval("\$cv = $cv;");
                    $c[$f]=$cv;
                }
            }
            
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
                    case 'inf':
                        if (!$number && isset($c['number'])) $number=$c['number'];
                        if ($number) {
                            $val=$number;
                            if ($number1) $val.=':'.$number1;
                            $this->update_cond($cond,$field,$val,$phraze_responsible,$phrazes_responsible);
                        }
                        if ($field=='chd') {
                            $age=[];
                            for ($a=1;$a<=$number;$a++) $age[]=$a*3;
                            $cond['age']=implode(',',$age);
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
                    
                    case 'ftsName':
                        $this->update_cond($cond,'hotelName',$c['value'],$phraze_responsible,$phrazes_responsible);
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
        
        return Tools::memcache($q_token,[$cond,$phrazes_responsible,$unknown,$changed==$q?'':str_replace('~',' ',$changed)]);
            

    }
    
    public function get_q()
    {
        return $this->status(Bootstrap::$main->session('q'),true,'q');
    }

    public function get_total()
    {
        Bootstrap::$main->session('total',$this->id);
        return $this->status(Bootstrap::$main->session('total'),true,'total');
    }
    
    
    protected function results(&$cond,$limit,$offset,&$config,$rowattr=[])
    {
    
        
        $offers=isset($cond[0]['hotel']) ?
            $this->merlin->getOffers($cond[0],'date,duration,dep,price',$limit,$offset,'',false)
            :
            $this->merlin->getGrouped($cond[0],'',$limit,$offset,false);
        
          
        $result=[];
        foreach ($offers['result'] AS $ofr)
        {
            if (true || isset($ofr['obj']['info']['photos']) || isset($ofr['obj']['info']['thumb'])) {
                
                $r=[];
                
                $r['photo']='//'.$_SERVER['HTTP_HOST'].Bootstrap::$main->getRoot().'img/nophoto.png';
                /*
                $r['photo']=$ofr['obj']['info']['thumb'];
                
                if (isset($ofr['obj']['info']['photos']) && count($ofr['obj']['info']['photos']))
                {
                    $r['photo'] = $ofr['obj']['info']['photos'][0];
                    if (isset($cond[0]['hotel'])) $r['photo'] = $ofr['obj']['info']['photos'][(count($result)+$offset)%count($ofr['obj']['info']['photos'])];
                }
                */
                
                foreach($ofr AS $k=>$v)
                {
                    if (!is_array($v))
                    {
                        $r[$k]=$v;
                    }
                    else foreach($v AS $kk=>$vv) {
                        if (!is_array($vv)) $r[$k.'_'.$kk]=$vv;
                    }
                    
                }
                   
                foreach (['trp_depName','trp_desDesc','obj_serviceDesc','obj_roomDesc'] AS $k)
                    if (isset($r[$k]))
                        $r[$k]=mb_strtolower($r[$k],'utf-8');
                
                $r['stars']='';
                for($i=0;$i<$r['obj_category'];$i+=10) $r['stars'].=Bootstrap::$main->getConfig('star');
                
                $r['adt'] = isset($cond[0]['adt']) ? $cond[0]['adt'] : 2;
                $r['chd'] = isset($cond[0]['chd']) ? $cond[0]['chd'] : 0;
                $r['inf'] = isset($cond[0]['inf']) ? $cond[0]['inf'] : 0;
                
                
                Bootstrap::$main->session('adt',$r['adt']);
                Bootstrap::$main->session('chd',$r['chd']);
                Bootstrap::$main->session('inf',$r['inf']);
                
                if (!isset($r['obj_xAttributes'])) $r['obj_xAttributes']=0;
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
                
                
                
                $r['hotel_selected']=isset($cond[0]['hotel']);
                
                $r=array_merge($r,$rowattr);
                
                
                foreach ($cond[1] AS $what=>$values) {
                    foreach($values AS $key=>$name) {
                        switch ($what) {
                            case 'dep':
                                $r['trp_depName']=@preg_replace('~('.$config['dep_name'][$key].')~i','<i>\\1</i>',$r['trp_depName']);
                                break;
                            
                            case 'dest':
                                foreach(explode(' ',$name) AS $n)
                                {
                                    $r['obj_region']=@preg_replace('~('.$n.')~i','<i>\\1</i>',$r['obj_region']);
                                    $r['obj_country']=@preg_replace('~('.$n.')~i','<i>\\1</i>',$r['obj_country']);
                                }
                                break;
                            
                            case 'from':
                            case 'to':
                                $r['startDate_D']='<i>'.$r['startDate_D'].'</i>';
                                $r['startDate_MMM']='<i>'.$r['startDate_MMM'].'</i>';
                                break;
                            
                            case 'service':
                                $r['obj_serviceDesc']='<i>'.$r['obj_serviceDesc'].'</i>';
                                break;
                            
                            case 'hotelName':
                                $r['obj_name']=@preg_replace('~('.$name.')~i','<i>\\1</i>',$r['obj_name']);
                                break;
                            
                            case 'max_price':
                            case 'min_price':
                                $r['price']='<i>'.$r['price'].'</i>';
                                break;
                                
                        }
                    }
                }
                
                
                $result[]=$r;
            }
        }
    
        return [
            'offers'=>$offers,
            'result'=>$result
        ];
    }
    
    public function get()
    {
        $opt=$this->nav_array(Bootstrap::$main->getConfig('merlin.search.limit'));
        $opt['totalPrice']=Bootstrap::$main->session('total');
        $site=Bootstrap::$main->getConfig('site');
        $config=$this->getConfig();
        Bootstrap::$main->system('cfg');
        
        $cond=$this->data('q')?$this->q2cond($this->data('q')):[];
        Bootstrap::$main->system('q2c');
        Bootstrap::$main->session('q',$this->data('q'));
        if (count($cond)) {
            if (!isset($cond[0]['type'])) $cond[0]['type']='F';
            if (!isset($cond[0]['adt'])) $cond[0]['adt']=2;
            
            $cond[0]['total']=$opt['totalPrice']?true:false;
            $rowattr=[];
            $rowattr['total']=$opt['totalPrice'];
            
            $results=$this->results($cond,$opt['limit'],$opt['offset'],$config,$rowattr);
            
            $offers=$results['offers'];
            $result=$results['result'];
            
            $offers2=$offers;
            $opt['next_offset']=$opt['offset']+$opt['limit'];

            $limit=$opt['limit'];
            $security=20;
            while(count($offers2['result'])==$limit && count($result)<$opt['limit'])
            {
                $offset=$opt['next_offset'];
                $limit=$opt['limit']-count($result);
                $opt['next_offset']+=$limit;
               
                
                $results=$this->results($cond,$limit,$offset,$config,$rowattr);
                if (count($results['result'])) $result=array_merge($result,$results['result']);
                $offers2=$results['offers'];
            
                if (!$security--) break;
            }
            
            
            
            if (!isset($cond['memcache'])) @Tools::log('query-'.$site,['q'=>$this->data('q'),'count'=>$offers['count'],'cond'=>$cond]);
      
            
            
        } else {
            $offers=['result'=>[],'count'=>0];
            $result=[];
            $opt['next_offset']=$opt['offset'];
        }
        Bootstrap::$main->system('mds');
        
        
        $opt['results']='Wyniki: ';
        
        if (isset($offers['count'])) {
            if ($offers['count']>=1000) $opt['results'].='ponad 1000';
            else $opt['results'].=$offers['count'];
        }
      
        
        $opt['change'] = isset($cond[3]) && strlen($cond[3]) ? $cond[3] : '';
        
        if (isset($cond[2]) && is_array($cond[2])) foreach($cond[2] AS $unknown)
        {
            if (strstr($opt['change'],$unknown)) $opt['change']=str_replace($unknown,"<i>$unknown</i>",$opt['change']); 
            else {
                if (strlen($opt['change'])) $opt['change'].=' ';
                $opt['change'].="<i>$unknown</i>";
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
        if (isset($offer['obj']['category'])) for($i=0;$i<$offer['obj']['category'];$i+=10) $offer['stars'].=Bootstrap::$main->getConfig('star');
        
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
    
    
    public function get_hotel()
    {
        $id=explode(':',$this->id);
        if (count($id)!=2) return $this->status(null,false);
        
        $hotel=$this->merlin->hotelInfo($id[0],$id[1]);
        if (isset($hotel['photos']) && is_array($hotel['photos']) && count($hotel['photos']))
        $hotel['thumb']=$hotel['photos'][0];
        
        return $this->status($hotel,true,'hotel');
    }
}
