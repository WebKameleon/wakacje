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
        return ['aaa'];
    }
}
