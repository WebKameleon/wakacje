<?php
class templateController extends Controller {
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
                        <a href="https://fly.pl/rezerwacja/?reservation%5Bid%5D=[id]" target="_blank">
                            [price] [operCurr]/os.
                            <span>REZERWUJ TERAZ &raquo;</span>
                        </a>
                    </h5>
                    
                </div>
            </div>
        </div>';
        
        die ($html);
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
        
        return $this->status('np. Hiszpania z Poznania '.$this->next_month());
    }
    
    public function get_inputtitle()
    {
        return $this->status('Zadaj pytanie, przykłady:
                             
   Hiszpania z Warszawy lub Lublina
   Egipt '.$this->next_month().' 3-5 dni
   jutro z Poznania 2 osoby i 2 dzieci
   z Gdańska od 1500 do 2000 7 dni
   Wyspy Kanaryjskie 15-17 '.$this->next_month(1,3).'
   Majorka lub Fuerteventura od 3 '.$this->next_month(1,2).' do 25 '.$this->next_month(1,2).'
   Chorwacja od lipca do sierpnia 1 osoba 2 dzieci
   

lub różne kombinacje powyższych');
    
    }
}