<?php
class templateController extends merlinController {
    public function get() {
        $html='
        <div id="webkameleon_holidays_template" style="display:none">
            <div style="display:none" class="row">
                <div class="holiday_photo col-md-3">
                    <div>
                        <a class="q" rel="hotel:[tourOp]:[obj_code]" xname="[obj_name]">
                        <img src="[photo]"/>
                        <h3>[obj_name]<span class="stars">[stars]</span></h3>
                        </a>
                    </div>
                    
                </div>
                <div class="trip_desc col-md-6">
                    <h4>
                        <a class="q">[obj_country]</a> &raquo; <a class="q">[obj_region]</a>
                        | [trp_duration] dni
                    </h4>
                    <h5>
                        [startDate_D] [startDate_MMM], [startDate_DDD]:
                        <a class="q flight">[trp_depName]</a>
                        <span class="flight" title="[trp_flightOp]"></span>
                        <a class="flight">[trp_desDesc]</a>
                    </h5>
                    <h6>
                        [obj_serviceDesc], [obj_roomDesc]
                    </h6>
                </div>
                <div class="holiday_price col-md-2">
                    <h5>
                        <a href="https://fly.pl/rezerwacja/?reservation%5Bid%5D=[id]&reservation%5Badults%5D=[adt][if:chd]&reservation%5Bchildren%5D=[chd][endif:chd]" target="_blank">
                            [price] [operCurr]/os.
                            <span>REZERWUJ TERAZ &raquo;</span>
                        </a>
                    </h5>
                    
                </div>
            </div>
        </div>';
        
        die ($html);
    }
    
    public function get_help()
    {
        $html='
            <div id="webkameleon_holidays_helpmodal" class="modal fade">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title">Jak zadawać pytania - przykłady</h4>
                        </div>
                        <div class="modal-body">
                            <p>Hiszpania '.$this->airport(0).' lub '.$this->airport(1).'</p>
                            <p>Egipt '.$this->next_month().' 3-5 dni</p>
                            <p>jutro '.$this->airport(2).' 2 osoby i 2 dzieci</p>
                            <p>'.$this->airport(3).' od 1500 do 2000 na 7 dni</p>
                            <p>Wyspy Kanaryjskie 15-17 '.$this->next_month(1,3).'</p>
                            <p>Majorka lub Fuerteventura od 3 '.$this->next_month(1,2).' do 25 '.$this->next_month(1,2).'</p>
                            <p>Chorwacja od lipca do sierpnia 1 osoba 2 dzieci</p>
                            
                            <p class="text-warning"><small>lub różne kombinacje powyższych</small></p>
                        </div>
                        <div class="modal-footer">
                            
                            <button type="button" class="btn btn-default" data-dismiss="modal">zamknij i znajdź udane wakacje</button>
                        </div>
                    </div>
                </div>
            </div>        
        ';
        
        die($html);
    }
    
    
    protected function next_month($day=0,$plus=1)
    {
        $months=[
            ['w styczniu','w lutym','w marcu','w kwietniu','w maju','w czerwcu','w lipcu','w sierpniu','we wrześniu','w październiku','w listopadzie','w grudniu'],
            ['stycznia','lutego','marca','kwietnia','maja','czerwca','lipca','sierpnia','września','października','listopada','grudnia']
        ];
        
        return $months[$day][(date('m')+$plus-1)%12];
    }
    
    public function get_placeholder()
    {
        
        return $this->status('np. Hiszpania '.$this->airport().' '.$this->next_month());
    }
    
    
    protected function airport($i=0)
    {
        $config=$this->getConfig();
        $geo=Tools::geoip();
        
        $airports=[];
        foreach($config['dep_latlng'] AS $ap=>$latlng)
        {
            $airports[$ap]=$this->distance($latlng,[$geo['location']['latitude'],$geo['location']['longitude']]);            
        }
        asort($airports);
        $ak=array_keys($airports);
        
        $code=$ak[$i];
        
        return $config['dep_from'][$code];
        

    }
}