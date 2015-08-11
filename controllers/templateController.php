<?php
class templateController extends Controller {
    public function get() {
        $html='
        <div id="webkameleon_holidays_template" style="display:none">
            <div style="display:none" class="row">
                <div class="holiday_photo col-md-3">
                    <div>
                        <img src="[photo]"/>
                        <h3>[obj_name]</h3>
                    </div>
                    
                </div>
                <div class="trip_desc col-md-6">
                    <h4>
                        <a class="q">[obj_country]</a> &raquo; <a class="q">[obj_region]</a>
                        [trp_duration] dni
                    </h4>
                    <h5>
                        [startDate_D] [startDate_MMM], [startDate_DDD]:
                        <a class="q flight">[trp_depName]</a>
                        <span class="flight" title="[trp_flightOp]"></span>
                        <a class="q flight">[trp_desDesc]</a>
                        
                    </h5>
                </div>
                <div class="holiday_price col-md-3">
                    <h5>[price] [operCurr]/os.</h5>
                </div>
            </div>
        </div>';
        
        die ($html);
    }
}