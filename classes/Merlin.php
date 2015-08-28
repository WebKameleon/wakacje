<?php



// http://docu.mdsws.merlinx.pl
// http://docu.mdsws.merlinx.pl/data:fields:names:obj_xattributes uzytkownik ecco:3CC0
// http://docu.mdsws.merlinx.pl/data:fields:names:filters
// obrazki: http://docu.mdsws.merlinx.pl/apdx:data


class Merlin
{
    private $user,$pass;
    private $url='http://mdswsb.merlinx.pl/V3/';
    private $_ver=3;
    
    private static $months=['stycznia','lutego','marca','kwietnia','maja','czerwca','lipca','sierpnia','września','października','listopada','grudnia'];
    private static $dows=['niedziela','poniedziałek','wtorek','środa','czwartek','piątek','sobota'];
    private $_section_map=array(
                                'autosuggestV1'=>'citySearchByName,citySearchByCoords,airportSearchByCoords,airportSearchByCity,airportSearchByIata,airportSearchByName',
                                'bookingstatusV3'=>'bookingstatus',
                                'bookV3'=>'check,book',
                                'checkavailV3'=>'checkavail',
                                'confirmationprintV3'=>'confirmationprint',
                                'dataV3'=>'regions,skiregions,filters,groups,offers,details,check_external_flight_wait,check_external_flight_nowait,check_external_hotel_wait,check_external_hotel_nowait,check_external_hotel_nowait',
                                'externalservicesV1'=>'L,LH,D,S,O,B,F',
                                'extradataV3'=>'extradata',
                                'lukasV1'=>'instalmentlist,instalment',
                                'optionconfirmV3'=>'optionconfirm',
                                'exthotelsdetailsV1'=>'ARI,AHI'
    );
    protected $section_map;
                               
                      
    private $operator_code;
    private $filters;
    private $hotels;
    public $debug=[];
    

    public function __construct($user,$pass,$operators='')
    {        
        

        $this->section_map=array();
        foreach($this->_section_map AS $section=>$types)
        {
            foreach (explode(',',$types) AS $type)
            {
                $this->section_map[$type]=$section;
            }
        }


        $this->pass=$pass;
        $this->user=$user;

        $this->operator_code=$operators;
        
    }


    protected function session($k,$v=null)
    {
        if (class_exists('Bootstrap')){
            if (!is_null($v)) Bootstrap::$main->session('merlin.'.$k,$v);
            return Bootstrap::$main->session('merlin.'.$k);
        }
        
        if (!is_null($v)) $_SESSION['merlin.'.$k]=$v;
        if (isset($_SESSION['merlin.'.$k])) return $_SESSION['merlin.'.$k];
        return false;
    }

    
    protected function getUrl($type)
    {
        $urls = array('http://mdsws.merlinx.pl/','http://mdswsb.merlinx.pl/');
        $random=rand(0,count($urls)-1);
        
        if ($this->_ver==2 || empty($this->section_map[$type])) return $urls[$random].'V2.3.1/';
        
        $section=$this->section_map[$type];
        
        return $urls[$random].$section.'/';
    }
    
    protected function debug($obj=null, $debug='debug', $puke=0, $color='red')
    {
        $plus='';
        while(isset($this->debug[$debug.$plus])) $plus=$plus+1;
        $this->debug[$debug.$plus]=$obj;
    }
    
    protected function post_xml(&$xml,$type,$subtype='')
    {



        $url=$this->getUrl($type);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST,   1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_POSTFIELDS, preg_replace("/>[ \t\r\n]+</",'><',$xml) );

        $response = curl_exec($ch);
        curl_close($ch);
        $debug_xml=$xml;
        $debug_xml=preg_replace('/<pass>[^>]*<\/pass>/','<pass>***</pass>',$debug_xml);
        $debug_xml=preg_replace("~(</[^>]*>)~","\\1\n",$debug_xml);
        
        $debug_string=htmlspecialchars($debug_xml);

        $debug_string='POST '.$url.'<hr size="1"/>'.$debug_string;

        if (!strlen($response))
        {

            $this->debug($debug_string,'mds XML - ERROR - empty resp');
            return false;
        }

        $this->debug($debug_string.'<hr size="1">'.htmlspecialchars($response),'mds XML '.$type.$subtype);

        $ret=$this->_xml2arr($response);
        $this->debug($ret,'mds XML object '.$type.$subtype);
        return $ret;
    }
    
    protected function _xml2arr($response)
    {
        $ret=simplexml_load_string($response);
        $ret=json_decode(json_encode($ret),true);
        
        return $ret;
    }

    
    private function getAttributes(&$obj)
    {
        $wynik=array();
        
        foreach ($obj['@attributes'] AS $k=>$v)
        {
            $wynik["$k"]="$v";
        }
        
        return $wynik;
    }
    
    private function getAttribute(&$obj,$attr)
    {

        foreach ($obj['@attributes'] AS $k=>$v)
        {
            if ($k==$attr) return "$v";
        }
    }

    private function getIdAttributes(&$obj,$path,$id='id',$emptys=false)
    {
        $result=array();

        if (!is_array($obj)) return $result;

        foreach ($obj[$path] AS $item)
        {
            $v=$this->getAttribute($item,$id);
            if (!$emptys && !strlen("$v")) continue;
            $result[]="$v";
        }


        return $result;
    }
    
    private function _obj2xml($array,$name,$node=null)
    {
        if (is_null($node))
        {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><'.$name.'/>');
        }
        else
        {
            $xml=$node->addChild($name);
        }
        
        foreach ($array AS $k=>$v)
        {
            if (is_array($v))
            {
                $this->_obj2xml($v,$k,$xml);
            }
            else
            {
                $xml->addChild($k,$v);
            }
        }
        
        return $xml->asXML();
           
    }


    private function request($type,$conditions,$more=null)
    {

        $mds['auth']['login']=$this->user;
        $mds['auth']['pass']=$this->pass;
        $mds['request']['type']=$type;

        if ($this->operator_code && !isset($conditions['ofr_tourOp']) ) $conditions['ofr_tourOp']=$this->operator_code;     
        
        if (isset($conditions['obj_xCityFts']))
        {
            // OR:
            //$conditions['obj_xCityFts']=str_replace('|',',',$conditions['obj_xCityFts']);

            // AND:
            $conditions['obj_xCityFts']='|'.str_replace('|','| |',$conditions['obj_xCityFts']).'|';
        }

        $mds['request']['conditions']=$conditions;

        if (is_array($more)) foreach ($more AS $k=>$v) $mds['request'][$k]=$v;


        
        $xml=$this->_obj2xml($mds,'mds');



        return $xml;
    }

    public function type_convert($type)
    {
        $type_convert=array('WS'=>'F','LO'=>['NF','OW'],'DW'=>'H');
        
        $result=[];
        foreach (explode(',',$type) AS $t)
        {
            if (isset($type_convert[$t]))
            {
                if (is_array($type_convert[$t])) $result=array_merge($result,$type_convert[$t]);
                else $result[]=$type_convert[$t];
            }
            else {
                $result[]=$t;
            }
        }
        
        return implode(',',$result);
    }

    public function getFilters($cond=array(),$what='*')
    {
        if ($this->operator_code) $cond['ofr_tourOp']=$this->operator_code;
    
        $cond['filters']='obj_xServiceId,trp_depName,trp_durationM,ofr_type,ofr_tourOp,trp_depCode';

        if (!isset($cond['trp_retDate'])) $cond['trp_retDate']=date('Ymd',time()+365*24*3600);

        if (isset($cond['obj_code']) || isset($cond['obj_xCode']) ) $cond['filters'].=',obj_room';

        if (isset($cond['ofr_type']) && $cond['ofr_type']=='NF,OW') $cond['filters']='trp_depName,trp_depDate';

        $md5=md5(serialize($cond));


        if (!isset($this->filters[$md5]))
        {
            $this->filters[$md5]=$this->session('filters.'.$md5);
        }
        

        if (!$this->filters[$md5])
        {
            $xml=$this->request('filters',$cond);
            $this->filters[$md5]=$this->post_xml($xml,'filters');
            if (isset($this->filters[$md5]['fdef'])) $this->session('filters.'.$md5,$this->filters[$md5]);

        }

        $result=array();

        
        
        foreach($this->filters[$md5]['fdef'] AS $fdef)
        {
            if (isset($fdef['@attributes']['id']) && $fdef['@attributes']['id']==$what)
            {
                if (!is_array($fdef['f'])) continue;
                if (count($fdef['f']) && !isset($fdef['f'][0])) $fdef['f']=array($fdef['f']);
                foreach ($fdef['f'] AS $f)
                {
                
                    if (!isset($f['@attributes'])) mydie($fdef);
                    foreach ($f['@attributes'] AS $id=>$val)
                    {
                        if (count($f['@attributes'])==1)
                        {
                            if ($id=='id') $result[]="$val";
                        }
                        else
                        {
                            if ($id=='id') $key="$val";
                            if ($id=='v') $result[$key]="$val";
                        }
                    }
                }
            }
        }
        

        return $result;
    }
    
    protected function time2str($d)
    {
        $d=explode(':',$d);
        $t=strtotime($d[0]);
        if (!$t) return '';
        if (isset($d[1])) {
            $t1=strtotime($d[1]);
            if ($t1) return date('Ymd',$t).':'.date('Ymd',$t1);
        }
        return date('Ymd',$t);
    }

    public function getOfferOnToken($token)
    {
        $o=$this->getOffers(null,null,null,null,$token);
        if (isset($o['result'][0])) return $o['result'][0];
        return null;
    }
    
    protected function offer2cond($offer)
    {
        $cond=[];
        
        if (isset($offer['hotel']) && $offer['hotel'])
        {
            $cond['obj_code']= $offer['hotel'];
        }

        if (isset($offer['xhotel']) && $offer['xhotel'])
        {
            $cond['obj_xCode']= $offer['xhotel'];
        }        
        
        if (isset($offer['op']) && $offer['op'])
        {
            $cond['ofr_tourOp']= $offer['op'];
        }

        if (isset($offer['dep']) && $offer['dep'])
        {
            $cond['trp_depCode']= $offer['dep'];
        }
        
        if (isset($offer['dep_name']) && $offer['dep_name'])
        {
            $cond['trp_depName']= $offer['dep_name'];
        }        
        
        if (isset($offer['adt']) && $offer['adt'])
        {
            $cond['par_adt']= $offer['adt'];
        }
        if (isset($offer['chd']) )
        {
            $cond['par_chd']= $offer['chd'];
        }
        
        if (isset($offer['max_price'])) $cond['maxPrice']=$offer['max_price'];
        if (isset($offer['min_price'])) $cond['minPrice']=$offer['min_price'];
        
        if (isset($offer['service']) ) $cond['obj_xServiceId']= $offer['service'];
        
        
        /*
        if (isset($offer['o_kod_hotelu'])) $cond['obj_code']= $offer['o_kod_hotelu'];
        if (isset($offer['o_kod_pokoju'])) $cond['obj_room']= $offer['o_kod_pokoju'];


        $cond['par_adt']= $offer['rodzinka'][0];
        $rodzinka=$offer['rodzinka'];
        for ($i=1;$i<count($rodzinka);$i++)
        {
            if ($rodzinka[$i]<2) $cond['par_inf']++;
            else
            {
                $cond['par_chd']++;
                if (isset($cond['par_chdAge'])) $cond['par_chdAge'].=',';
                $cond['par_chdAge'].=$rodzinka[$i];
            }
        }


        */
        
        if (isset($offer['duration']) && $offer['duration']) {
            $cond['trp_duration']=$offer['duration'];
        }
       
        $zarok=$this->time2str(date('Y-m-d',time()+365*24*3600));
        if (isset($offer['from']) && $offer['from'])
        {
            $cond['trp_depDate']=$this->time2str($offer['from']);
            if (!strstr($offer['from'],':')) $cond['trp_depDate'].=':'.$zarok;
        }
        
        if (isset($offer['from']) && isset($offer['fromto']) && $offer['fromto'] && $offer['from'] && !strstr($offer['from'],':'))
        {
            $cond['trp_depDate']=$this->time2str($offer['from']).':'.$this->time2str($offer['fromto']);
        }
        
        if (isset($offer['to']) && $offer['to'])
        {
            $cond['trp_retDate']=$this->time2str(date('Y-m-d')).':'.$this->time2str($offer['to']);
        } 
 
        if (isset($offer['to']) && $offer['to'] && isset($offer['from']) && $offer['from'])
        {
            $cond['trp_retDate']=$this->time2str($offer['from']).':'.$this->time2str($offer['to']);
        }

        if (isset($offer['dest']) && $offer['dest'])
        {
            $cond['trp_destination']=$offer['dest'];
            if ($cond['trp_destination'][strlen($cond['trp_destination'])-1]==',') $cond['trp_destination']=substr($cond['trp_destination'],0,strlen($cond['trp_destination'])-1); 
        }
        
        if (isset($offer['attr']))
        {
            $cond=array_merge($cond,$this->getCondOnAttributeArray($offer['attr']));
        }        
        
        /*
        if (isset($offer['o_kod_miasta']))
        {
            $cond['trp_destination']=$offer['o_kod_miasta'];
        }



        if (isset($offer['o_kategoria']))
        {
            $offer['o_kategoria']=str_replace(')','',$offer['o_kategoria']);
            $offer['o_kategoria']=str_replace('(','',$offer['o_kategoria']);

            $kat=explode(',',$offer['o_kategoria']);

            if (count($kat)==1) $cond['obj_category']=10*$offer['o_kategoria'];
            else
            {
                $cond['obj_category']=(10*$kat[0]).':'.(10*$kat[count($kat)-1]);
            }

        }

        

        */
        
        if (isset($offer['type']) && $offer['type']) $cond['ofr_type']=$this->type_convert($offer['type']);

        return $cond;
    }
    
    
    public function getOffers($offer=[],$order='',$limit=10,$offset=0,$token='',$search4otherDates=false)
    {
        $cond=$this->offer2cond($offer);
        $cond['calc_found']=1000;
        $cond['limit_count']=$limit;
        $cond['limit_from']=$offset+1;

        $type='offers';


        if ($order) $cond['order_by']=$this->orderOnArray($order);

        if (strlen($token))
        {
            $cond=array('ofr_id'=>$token);
            $type='details';
        }
       

        $xml2=$this->request($type,$cond);

        $xml_response = $this->post_xml($xml2,$type);

        if (!strlen($token) && $xml_response['count']==0 && !strlen($token) && $limit>5 && $search4otherDates)
        {
            $cond2=$cond;
            unset($cond2['trp_depDate']);

            $cond2['order_by']='trp_depDate';
            $cond2['limit_count']=1;
            $cond2['limit_from']=1;

            $xml2=$this->request('offers',$cond2);
            $ofrs = $this->post_xml($xml2,$type);
            $ofr=$this->convertOffers($ofrs);
            if (isset($ofr[0]['o_data']))
            {
                $cond['trp_depDate']=date('Ymd',strtotime($ofr[0]['o_data']));

                if (isset($offer['o_data_przylotu_pow']))
                {
                    $diff=round((strtotime($offer['o_data_przylotu_pow'])-strtotime($offer['o_data_wylotu']))/(24*3600));
                    if ($diff>0) $cond['trp_depDate'].=':'.date('Ymd',strtotime($ofr[0]['o_data'])+24*3600*$diff);
                }

                $xml2=$this->request('offers',$cond);
                $xml_response = $this->post_xml($xml2,'offers','2');

            }
        }


        return $this->convertOffers($xml_response);

    }
    
    protected function xAttr2array($a)
    {
        $token='attr.'.$a;
        $attr=$this->session($token);
        if ($attr) return $attr;
        $attr=[];
        for ($e=1;$e<70;$e++)
        {
            if ( ($a+0) & pow(2,$e-1) ) $attr[]='m_'.$e;
        }
        return $this->session($token,$attr);
    }
    
    public function orderOnArray($order) {
        $cond=['order_by'=>''];
        
        foreach (explode(',',$order) AS $o)
        {
            $order_by='';

            if (strstr($o,'price')) $order_by='ofr_price';
            if (strstr($o,'country')) $order_by='obj_country';
            if (strstr($o,'date')) $order_by='trp_depDate';
            if (strstr($o,'dep')) $order_by='trp_depName';
            if (strstr($o,'region')) $order_by='obj_region';
            if (strstr($o,'city')) $order_by='obj_city';
            if (strstr($o,'name')) $order_by='obj_name';
            if (strstr($o,'duration')) $order_by='trp_duration';
            

            if (strstr(strtolower($o),'desc'))
            {
                $order_by='-'.$order_by;
                str_replace(',','-,',$order_by);
            }
            if (strlen($order_by))
            {
                if (strlen($cond['order_by'])) $cond['order_by'].=',';
                $cond['order_by'].=$order_by;
            }

        }
        
        return $cond['order_by'];
    }
    

    public function merlinDate($d)
    {
        return substr($d,0,4).'-'.substr($d,4,2).'-'.substr($d,6);
    }

    public function hotelInfo($op,$htlCode)
    {
        $token='hotel-'.$op.'-'.$htlCode;
        
        if ($op=='ECC1') $op='ECCO';
        if ($op=='ECT1') $op='ECTR';
        
        $ret=$this->session($token);
        if ($ret) return $ret;
        
        $url='http://data2.merlinx.pl/index.php?login='.$this->user.'&password='.$this->pass.'&tourOp='.$op.'&htlCode='.$htlCode;
        $response=file_get_contents($url);
        $hotel=$this->_xml2arr($response);

        $this->debug($hotel,'Hotel info: '.$htlCode);
        
        $ret=[];
        
        if (isset($hotel['hotelData']['images']['thumb']))
            $ret['thumb']=$hotel['hotelData']['images']['thumb'];
        if (isset($hotel['hotelData']['images']['pictures']['picture']))
            $ret['photos']=$hotel['hotelData']['images']['pictures']['picture'];
            
        if (isset($hotel['hotelData']['texts']['text']))
            $ret['desc']=$hotel['hotelData']['texts']['text'];
        
        return $this->session($token,$ret);
    }
    
    public function convertOffers($offers,$path='ofr')
    {
    
        $ret=[];
        if (isset($offers['count']))
        {
            $ret['count']=$offers['count'];
            if ($ret['count']==1 && !isset($offers[$path][0]) && isset($offers[$path])) {
                $offers[$path]=array($offers[$path]);
            }
        }
        
        $ret['result']=[];

        if (isset($offers[$path]) && is_array($offers[$path]) && count($offers[$path]) && !isset($offers[$path][0])) $offers[$path]=array($offers[$path]);
        
        if (isset($offers[$path]) && is_array($offers[$path])) foreach ($offers[$path] AS $resp)
        {
    
            if (isset($resp['ofr'])) $resp=$resp['ofr'];
            
            $rec=$resp['@attributes'];
            $rec['startDate']=array();
            $rec['obj']=$resp['obj']['@attributes'];
            $rec['trp']=$resp['trp']['@attributes'];
            
            
            if (isset($rec['obj']['xAttributes']) && $rec['obj']['xAttributes']) $rec['obj']['attributes']=$this->xAttr2array($rec['obj']['xAttributes']);

            $rec['obj']['info']=$this->hotelInfo($rec['tourOp'],$rec['obj']['code']);   
            
            $rec['startDate']['YYYYMMDD']=$this->merlinDate($rec['trp']['depDate']);
            $start=strtotime($rec['startDate']['YYYYMMDD']);
            $rec['startDate']['DDMMYYYY']=date('d-m-Y',$start);
            $rec['startDate']['MMDDYYYY']=date('m/d/Y',$start);
            
            $rec['startDate']['D']=date('d',$start);
            $rec['startDate']['MMM']=self::$months[date('m',$start)-1];
            $rec['startDate']['DDD']=self::$dows[date('w',$start)+0];
            
            
            $ret['result'][]=$rec;
            
        }
        
        return $ret;        

        $a=$this->getAttribute($offer->obj,'xAttributes');

        /*
        for ($e=1;$e<70;$e++)
        {
            if ( ($a+0) & pow(2,$e-1) ) $r['m_atrybuty'][]=$this->operator->atrybut("m_$e",'M');
        }
        */
        

    }

    public function getCatalogs($type='WS')
    {
        $cond=array();
        $cond['ofr_type']=$this->type_convert($type);


        //print_r($cond);
        $catalogs=$this->getFilters($cond,'ofr_catalog');

        return $catalogs;
    }

    
    protected function unzip($f)
    {
        $zip = new ZipArchive;
        $file=tempnam (sys_get_temp_dir(),'merlin');
        file_put_contents($file,file_get_contents($f));
        $zip->open($file);
        $csv=explode("\n",$zip->getFromIndex(0));
        $zip->close();
        $h=explode('";"',substr($csv[0],1,strlen($csv[0])-2));
        $result=array();
        for($i=1;$i<count($csv);$i++)
        {
            $line=explode('";"',substr($csv[$i],1,strlen($csv[$i])-2));
            $rec=array();
            foreach($line AS $k=>$v) if (strlen(trim($v))) $rec[$h[$k]]=$v;
            if (count($rec)) $result[]=$rec;
        }
        
        return $result;
    }
    
    public function getRegions($type=null,$limits=null,$cache=true,$all=false)
    {
        $result=array();

        $cond=array();
        $cond['par_adt']=2;
        if ($all) $cond['ofr_tourOp']='';
        if ($type) $cond['ofr_type']=$this->type_convert($type);

        if (is_array($limits)) $cond=array_merge($cond,$limits);

        $token='reg.'.($all?'1':'0').'.'.md5(serialize($cond));
        
        $r=$this->session($token);
        if ($r && $cache) return $r;
        
        $xml=$this->request('regions',$cond);

        $regions=$this->post_xml($xml,'regions');
        
        
        $rg=Tools::saveRoot('merlin/regions.json');
        
        if ( !file_exists($rg) ||  filemtime($rg)<time()-24*3600)
        {
            $rgns=$this->unzip('http://www.merlinx.pl/mdsws/regions_utf8.zip');
            file_put_contents($rg,json_encode($rgns));
        }
        else
        {
            $rgns=json_decode(file_get_contents($rg),true);
        }

        $result=(array)$regions;
        $wynik=array();
        if (isset($result['reg'])) foreach ($result['reg'] AS $r)
        {
            $rec=$r['@attributes'];
            $id=explode('_',$rec['id']);

            foreach ($rgns AS $region)
            {
                if (!isset($region['region'])) $region['region']='';
                
                if ($region['country']=='YES' && $id[0]==$region['num'])
                {
                    $rec['country']=$region['region'];
                    $rec['country_code']=$region['num'];
                }
                if ($region['country']=='NO' && $id[1]==$region['num'] && isset($region['region']) )
                {
                    $rec['region']=$region['region'];
                }
            }
            
            $wynik[]=$rec;
        }
        
        return $this->session($token,$wynik);

    }

  

    public function getServicess($type,$hotel='')
    {
        $cond=array();
        $cond['ofr_type']=$this->type_convert($type);
        if (strlen($hotel)) $cond['obj_code']=$hotel;

        $services=$this->getFilters($cond,'obj_xServiceId');

        $result=array();
        foreach ($services AS $service)
        {
            $result[]=array('kod'=>$service,'wyzywienie'=>$service/*$this->operator->label('wyzyw',$service)*/);
        }
        return $result;
    }

    public function getDays($type,$hotel)
    {
        $cond=array();
        $cond['ofr_type']=$this->type_convert($type);
        if (strlen($hotel)) $cond['obj_code']=$hotel;

        $days=$this->getFilters($cond,'trp_durationM');
        $tmp=array('Dowolna');

        if (is_array($days)) sort($days);
	$days=array_merge($tmp,$days);
        $result=array();
        foreach ($days AS $day)
        {
            $result[]=array('o_dni'=>$day);
        }
        return $result;
    }





    public function getFlightDates($type,$dest='',$hotel='',$dep='')
    {
        $cond=array();
        $cond['ofr_type']=$this->type_convert($type);
        if (strlen($hotel)) $cond['obj_code']=$hotel;
        if (strlen($dest)) $cond['trp_destination']=$dest;
        if (strlen($dep)) $cond['trp_depName']=$dep;

        $days=$this->getFilters($cond,'trp_depDate');

        $result=array();
        foreach ($days AS $day)
        {
            $result[]=array('o_data_wylotu'=>date('d-m-Y',strtotime($day)));
        }
        return $result;

    }

    public function getRooms($type,$hotel)
    {
        $cond=array();
        $cond['ofr_type']=$this->type_convert($type);
        if (strlen($hotel)) $cond['obj_code']=$hotel;

        $rooms=$this->getFilters($cond,'obj_room');


        $result=array();
        foreach ($rooms AS $code=>$room)
        {
            $result[]=array('o_kod_pokoju'=>$code,'label'=>$room);
        }
        return $result;

    }

   
    
    public function getGrouped($params,$order='',$limit=10,$offset=0)
    {
        $cond=$this->offer2cond($params);
        $cond['calc_found']=1000;


        if (is_array($order)) {
            $cond['order_by']=$this->orderOnArray($order);
        }
        elseif (strstr($order,'cena') || !$order) $cond['order_by']='ofr_price';


        if ($limit)  $cond['limit_count']=$limit;
        if ($offset)  $cond['limit_from']=$offset+1;
        

            
        $hotels = $this->post_xml($this->request('groups',$cond),'groups');
        $hotels = $this->convertOffers($hotels,'grp');



        return $hotels;
    }

    private function getCondOnAttributeArray($a)
    {
        $cond=array();

        $xAttr=0;
        $xCity=array();
        
        if (!is_array($a)) $a=explode(',',$a);
        
        foreach ($a AS $at)
        {
            //if (is_integer($at))
            $pow=$at==64?-9223372036854775808:pow(2,$at-1);
            
            $xAttr+=$pow;
            //elseif (strlen($at)) $xCity[]=$at;
        }

        if ($xAttr>0) $cond['obj_xAttributes']=$xAttr;
        else $cond['obj_xAttributes']=sprintf('0x%x',$xAttr);
        
        
        if (count($xCity)) $cond['obj_xCityFts']=implode('|',$xCity);

        $cond['obj_xAttributesCount']=count($a);
        return $cond;
    }

    private function add_service($add_service)
    {
        $_add_service=$this->session('add_service');
        $result=array();

        if (strlen($add_service))
        {
            foreach (explode(',',$add_service) AS $as)
            {
                $asas=explode(':',$as);
                if (is_array($_add_service[$asas[0]]))
                {
                    $_asas=$_add_service[$asas[0]];
                    if (strlen($asas[1]))
                    {
                        $c=count($_asas['allocation']['data']);
                        for($i=0;$i<$c;$i++)
                        {
                            if (!$asas[1][$i]) unset($_asas['allocation']['data'][$i]);
                        }
                        sort($_asas['allocation']['data']);
                    }
                    $result[]=$_asas;
                }
            }
        }

        return $result;
    }

    public function extradata($token,$dor=0,$dzieci=0,$inf=0,$only_attr=false)
    {
        static $cache;
        
        if (!strlen($token)) return;
        
        $cond=array('ofr_id'=>$token);
        if ($dor) $cond['par_adt']=$dor;
        if ($dzieci) $cond['par_chd']=$dzieci;
        if ($inf) $cond['par_inf']=$inf;

        $cache_token = md5(serialize($cond));
        
        if (isset($cache[$cache_token])) return $cache[$cache_token];            
        
        $xml=$this->request('extradata',$cond);
        $resp = $this->post_xml($xml,'extradata');


        if (!isset($resp->base_data->extra_data) && !isset($resp->extra_data)) return false;

        $extra_data = isset($resp->extra_data) ? $resp->extra_data : $resp->base_data->extra_data;
        
        
        $attr=$this->getAttributes($extra_data);

                
        
        if($only_attr)
        {
            $wynik=$attr;
        }
        else
        {
            $wynik=array();
            foreach ($extra_data AS $htl)
            {
                if (!isset($htl->htlCode)) continue;
    
                $d=array('code'=>(string)$htl->htlCode);
    
                $d['id']=$this->getAttribute($htl,'extra_id');
                $d['value']=$d['id'].'_'.$d['code'];
    
                $d['name']=(string)$htl->htlName;
                $d['cat']=(string)$htl->htlCat;
                $d['service_code']=(string)$htl->htlSrvCode;
                $d['room_code']=(string)$htl->htlRoomCode;
                $d['price']=(float)$htl->prcAdt;
                $d['city']=(string)$htl->htlCity;
    
                $d['room']=(string)$htl->htlRoomDesc;
                $d['from']=(string)$htl->fromDate;
                $d['to']=(string)$htl->toDate;
                $d['service']=(string)$htl->htlSrvDesc;
                
                $d['attr'] = $attr;
    
                $wynik[]=$d;
            }
        }
    
        $cache[$cache_token]=$wynik; 
        return $wynik;
    }

    public function check($token,$dor,$dzieci,$inf,$htl='',$add_service='',$wishes=null,$birthdays=null)
    {
        static $cache;
        
        $cond=array('ofr_id'=>$token);
        if ($dor) $cond['par_adt']=$dor;
        if ($dzieci) $cond['par_chd']=$dzieci;
        if ($inf) $cond['par_inf']=$inf;
        if ($htl) $cond['x_htl']=$htl;
        

        $more=array();

        if (count($as=$this->add_service($add_service))) $more['forminfo']['add_service']['data']=$as;

        if (!is_null($wishes)) $more['forminfo']['wishes']=$wishes;

        if (is_array($birthdays))
        {
            $cnt=0;
            foreach($birthdays AS $birthday)
            {
                $person=array();
                $cnt++;
                $person['birthdate']=date('d.m.Y',strtotime($birthday));
                
                if ($cnt<=$dor) $person['gender']='H';
                else {
                    //$age=$this->operator->age(date('Y-m-d',strtotime($birthday)));
                    $person['gender']=($age>=2 ? 'K' : 'I');
                }
                

                $more['forminfo']['Person']['data'][]=$person;
            }
        }

        $cache_token = md5(serialize($cond).serialize($more));
        
        if (isset($cache[$cache_token])) return $cache[$cache_token];
        
        $xml=$this->request('check',$cond,$more);
        $resp = $this->post_xml($xml,'check');

        $wynik=array();
        $wynik['status']=$this->getAttribute($resp->offerstatus,'status');
        $wynik['option']=$this->getAttribute($resp->offerstatus,'optionpossible');
        $wynik['price']=$this->getAttribute($resp->pricetotal,'price');
        $wynik['prices']=array();
        
        $er=error_reporting();
        error_reporting(0);
        foreach($resp->forminfo->Person->data AS $person) $wynik['prices'][]=array('price'=>(integer)$person->price->value,'gender'=>(string)$person->gender->selected);
        error_reporting($er);
        
        $wynik['currency']=$this->getAttribute($resp->pricetotal,'curr');
        $wynik['info']=$resp->merlin_offer_info->info;

        $wynik['result_message_code']=$this->getAttribute($resp->result_message,'msgCode');

        $wynik['prepayment']=$resp->forminfo->prepayment->data;
        $wynik['reservepay']=$resp->forminfo->reservepay->data;


        $wynik['add']=array();



        if (is_object($resp->forminfo->add_service) && count($resp->forminfo->add_service)>0) foreach ($resp->forminfo->add_service->data AS $add)
        {
            $number=$add->number->value+0;
            $code=''.$add->code->value;

            $wynik['add'][$code]['number']=$number;
            foreach($add->allocation->data AS $allocation)
            {
                $wynik['add'][$code]['allocation']['data'][]=0+$allocation->value;
            }
            $wynik['add'][$code]['fromDT']=''.$add->fromDT->values->data[0];
            $wynik['add'][$code]['toDT']=''.$add->toDT->selected;

            //$wynik['add'][$code]['type']=''.$add->number->type;

            $wynik['add'][$code]['accomodation']='';
            $wynik['add'][$code]['shift']='';

            $wynik['add'][$code]['default_checked']= (0+$add->number->checked) ? 1:0;
            $wynik['add'][$code]['type']=''.$add->type->value;
            $wynik['add'][$code]['code']=''.$add->code->value;
            $wynik['add'][$code]['len']=0+$add->len->value;
            $wynik['add'][$code]['text']=''.$add->text->value;
            $wynik['add'][$code]['exclude']=''.$add->excludeIndex->value;

        }

        $this->session('add_service',$wynik['add']);

        
        $cache[$cache_token]=$wynik;
        return $wynik;
    }


    public function book($r,$option=true,$wishes=null,$htl='')
    {
        $cond=array('ofr_id'=>$r['r_token']);
        if ($htl) $cond['x_htl']=$htl;

        $forminfo['InternalAction']=0;
        $forminfo['DelPersonIdx']=0;
        $forminfo['ReservationMode']=$option?0:1;
        $forminfo['check_price']=0;
        $forminfo['flaga']=0;
        $forminfo['unload_flag']=1;
        $forminfo['short_term']=0;
        $forminfo['additional_where_flag']='';
        $forminfo['load_orderby']=0;
        $forminfo['hideDefBirthdates']=1;

        $forminfo['new_search']='';
        $forminfo['check_payment_offer']=0;
        $forminfo['email_condition_checked']=0;
        $forminfo['client_radio']='';
        $forminfo['family_address']='';
        $forminfo['test1_0']=0;
        $forminfo['test1_1']=0;
        $forminfo['test2_0']=0;
        $forminfo['test2_1']=0;
        $forminfo['test3_0']=0;
        $forminfo['test3_1']=0;
        $forminfo['test4_0']=0;
        $forminfo['test4_1']=0;
        $forminfo['test5_0']=0;
        $forminfo['test5_1']=0;
        $forminfo['conditions']='';

        if (!is_null($wishes)) $forminfo['wishes']=$wishes;

        if (strlen($r['r_zgody'])) foreach (explode(',',$r['r_zgody']) AS $agree) $forminfo[$agree]=1;

        $dor=0;
        $children=0;
        $infants=0;
        for ($i=0;$i<count($r['uczestnicy']);$i++)
        {
            $person=array();

            $person['lastname']=$r['uczestnicy'][$i]['ru_nazwisko'];
            $person['name']=$r['uczestnicy'][$i]['ru_imie'];

            $person['birthdate']=$r['uczestnicy'][$i]['ru_data_ur']?date('d.m.Y',strtotime($r['uczestnicy'][$i]['ru_data_ur'])):'01.01.1970';
            $person['price']=0;

    //do dyskusji
            $person['zipcode']=$r['klient']['k_kod'];
            $person['city']=$r['klient']['k_miasto'];
            $person['street']=$r['klient']['k_ulica'].' '.$r['klient']['k_nr_domu'];
            $person['phone']=$r['klient']['k_telefon'];;
    //</do dyskusji
            $person['email']='';

            $person['gender']=$r['uczestnicy'][$i]['ru_plec'];

            $forminfo['Person']['data'][]=$person;

            //$dorosly=$this->operator->age($r['uczestnicy'][$i]['ru_data_ur'],$r['r_data_przylotu_pow'])>=18;
            if (!$r['uczestnicy'][$i]['ru_data_ur']) $dorosly=true;

            if($dorosly)$dor++;
            else
            {
                /*
                if ($this->operator->age($r['uczestnicy'][$i]['ru_data_ur'],$r['r_data_przylotu_pow'])>=2)
                    $children++;
                else
                    $infants++;
                */

            }
        }

        $cond['par_adt']=$dor;

        if ($children) $cond['par_chd']=$children;
        if ($infants) $cond['par_inf']=$infants;

        $forminfo['Client']['lastname']=$r['klient']['k_nazwisko'];
        $forminfo['Client']['name']=$r['klient']['k_imie'];
        $forminfo['Client']['street']=$r['klient']['k_ulica'].' '.$r['klient']['k_nr_domu'];
        $forminfo['Client']['zipcode']=$r['klient']['k_kod'];
        $forminfo['Client']['city']=$r['klient']['k_miasto'];
        $forminfo['Client']['phone']=$r['klient']['k_telefon'];
        $forminfo['Client']['email']=$r['klient']['k_email'];


        $forminfo['Client']['country']='Polska';


        if (count($as=$this->add_service($r['r_ubezpieczenia']))) $forminfo['add_service']['data']=$as;


        $xml=$this->request('book',$cond,array('forminfo'=>$forminfo));
        $xml=$this->str_to_url($xml);
        $resp = $this->post_xml($xml,'book');

        $wynik=array();
        if (isset($resp->booking_number)) $wynik['booking_number']=$resp->booking_number;
        else
        {
            $wynik['error']=''.$resp->booking_info;
        }

        if (isset($resp->booking_errors->booking_error)) $wynik['error']=$resp->booking_errors->booking_error;

        if (is_array($wynik['error'])) $wynik['error']=implode("\n",$wynik['error']);
        $wynik['msg_type']=$this->getAttribute($resp->result_message,'msgType');
        $wynik['msg_code']=$this->getAttribute($resp->result_message,'msgCode');
        $wynik['price']=$this->getAttribute($resp->pricetotal,'price');
        $wynik['currency']=$this->getAttribute($resp->pricetotal,'curr');

        $wynik['info']=$resp->merlin_offer_info->info;

        return $wynik;
    }


    public function str_to_url($s, $case=0)
    {
        $acc =	'É	Ê	Ë	š	Ì	Í	ƒ	œ	µ	Î	Ï	ž	Ð	Ÿ	Ñ	Ò	Ó	Ô	Š	£	Õ	Ö	Œ	¥	Ø	Ž	§	À	Ù	Á	Ú	Â	Û	Ã	Ü	Ä	Ý	';
        $str =	'E	E	E	s	I	I	f	o	m	I	I	z	D	Y	N	O	O	O	S	L	O	O	O	Y	O	Z	S	A	U	A	U	A	U	A	U	A	Y	';

        $acc.=	'Å	Æ	ß	Ç	à	È	á	â	û	Ĕ	ĭ	ņ	ş	Ÿ	ã	ü	ĕ	Į	Ň	Š	Ź	ä	ý	Ė	į	ň	š	ź	å	þ	ė	İ	ŉ	Ţ	Ż	æ	ÿ	';
        $str.=	'A	A	S	C	a	E	a	a	u	E	i	n	s	Y	a	u	e	I	N	S	Z	a	y	E	i	n	s	z	a	p	e	I	n	T	Z	a	y	';

        $acc.=	'Ę	ı	Ŋ	ţ	ż	ç	Ā	ę	Ĳ	ŋ	Ť	Ž	è	ā	Ě	ĳ	Ō	ť	ž	é	Ă	ě	Ĵ	ō	Ŧ	ſ	ê	ă	Ĝ	ĵ	Ŏ	ŧ	ë	Ą	ĝ	Ķ	ŏ	';
        $str.=	'E	l	n	t	z	c	A	e	I	n	T	Z	e	a	E	i	O	t	z	e	A	e	J	o	T	i	e	a	G	j	O	t	e	A	g	K	o	';

        $acc.=	'Ũ	ì	ą	Ğ	ķ	Ő	ũ	í	Ć	ğ	ĸ	ő	Ū	î	ć	Ġ	Ĺ	Œ	ū	ï	Ĉ	ġ	ĺ	œ	Ŭ	ð	ĉ	Ģ	Ļ	Ŕ	ŭ	ñ	Ċ	ģ	ļ	ŕ	Ů	';
        $str.=	'U	i	a	G	k	O	u	i	C	g	k	o	U	i	c	G	L	O	u	i	C	g	l	o	U	o	c	G	L	R	u	n	C	g	l	r	U	';

        $acc.=	'ò	ċ	Ĥ	Ľ	Ŗ	ů	ó	Č	ĥ	ľ	ŗ	Ű	ô	č	Ħ	Ŀ	Ř	ű	õ	Ď	ħ	ŀ	ř	Ų	ö	ď	Ĩ	Ł	Ś	ų	Đ	ĩ	ł	ś	Ŵ	ø	đ	';
        $str.=	'o	c	H	L	R	u	o	C	h	l	r	U	o	c	H	L	R	u	o	D	h	l	r	U	o	d	I	L	S	c	D	i	l	s	W	o	d	';

        $acc.=	'Ī	Ń	Ŝ	ŵ	ù	Ē	ī	ń	ŝ	Ŷ	Ə	ú	ē	Ĭ	Ņ	Ş	ŷ';
        $str.=	'I	N	S	w	u	E	i	n	s	Y	e	u	e	I	N	S	y';

        $acc.=	'Б	б	В	в	Г	г	Д	д	Ё	ё	Ж	ж	З	з	И	и	Й	й	К	к	Л	л	М	м	Н	н	П	п	О	о	Р	р	С	с	Т	т	У	у	Ф	ф	Х	х	Ц	ц	Ч	ч	Ш	ш	Щ	щ	Ъ	Ы	ы	Ь	Э	э	Ю	ю	Я	я';
        $str.=	'B	b	W	w	G	g	D	d	Yo	yo	Z	z	Z	z	I	i	N	n	K	k	L	l	M	m	H	h	P	p	O	o	P	p	S	s	T	t	U	u	f	F	Ch	h	C	c	C	c	Sz	sz	S	s	-	Y	y	-	E	e	Iu	iu	Ia	ia';



        $out = str_replace(explode("\t", $acc), explode("\t", $str), $s);

        if($case == -1)
        {
            return strtolower($out);
        }
        else if($case == 1)
        {
            return strtoupper($out);
        }
        else
        {
            return ($out);
        }
    }

}
