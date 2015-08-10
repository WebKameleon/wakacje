<?php
class indexController extends Controller {
    
    protected $merlin;
    
    public function init()
    {
        $config=Bootstrap::$main->getConfig();
        $this->merlin=new Merlin($config['merlin.login'],$config['merlin.pass']);
    }
    
    
    public function get()
    {
        $opt=$this->nav_array(Bootstrap::$main->getConfig('merlin.search.limit'));
              
        //$data=$this->merlin->getFilters([],'ofr_tourOp');
        
        $cond=['type'=>'F'];
        
        $offers=$this->merlin->getGrouped($cond,'',$opt['limit'],$opt['offset']);
        
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
                $result[]=$r;
            }
        }
        
        mydie($result);
        
        return $this->status($offers);
        return ['aaa'];
    }
}
