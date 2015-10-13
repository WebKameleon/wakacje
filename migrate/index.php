<?php

	ini_set('display_errors',true);
	$_REQUEST['_site']='pudel.webkameleon.com';
	
	include __DIR__.'/../backend/include/all.php';
	autoload([__DIR__.'/../models',__DIR__.'/../controllers']);
	
	include __DIR__.'/../backend/include/migrate.php';
	
	$ver=null;
    if (isset($argv[1]) && $argv[1]>0) $ver=$argv[1];

	$ver=backend_migrate(__DIR__.'/../config/application.json',__DIR__.'/classes',$ver);
    