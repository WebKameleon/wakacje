<?php

    $dir=realpath(__DIR__.'/..');


	file_put_contents("$dir/merlin/regions_utf8.zip",file_get_contents('http://www.merlinx.pl/mdsws/regions_utf8.zip'));
	$zip = new ZipArchive;
	$res=$zip->open("$dir/merlin/regions_utf8.zip");
	$result=[];
	if ($res===true) {
		$csv=explode("\n",$zip->getFromIndex(0));
		$zip->close();
		$h=explode('";"',substr($csv[0],1,strlen($csv[0])-2));
		
		for($i=1;$i<count($csv);$i++)
		{
			$line=explode('";"',substr($csv[$i],1,strlen($csv[$i])-2));
			$rec=array();
			foreach($line AS $k=>$v) if (strlen(trim($v))) $rec[$h[$k]]=$v;
			if (count($rec)) $result[]=$rec;
		}
	}
	file_put_contents("$dir/merlin/regions_utf8.json",json_encode($result));

    $exclude=file(__DIR__.'/exclude');
    $dest=__DIR__.'/tmp';
    
    system('rm -rf '.$dest);
    @mkdir($dest,0755);
    
    $yaml=file_get_contents(__DIR__.'/app.yaml.www');
    $cron=file_get_contents(__DIR__.'/cron.yaml');
    $ini=file_get_contents(__DIR__.'/php.ini');
    
    if (!file_exists(__DIR__.'/email.txt')) die("Brak pliku email.txt\n");
    $mail=trim(file_get_contents(__DIR__.'/email.txt'));
    
    
    $files=array();
    foreach (scandir($dir) AS $f)
    {
        if ($f[0]=='.') continue;
        
		if (in_array("$f\n",$exclude)) continue;
	
	
        $cmd="cp -LRp $dir/$f $dest";
        $files[]=$f;
        system($cmd);
        
	
        if (is_dir("$dir/$f"))
        {
			continue;
            $yaml.="\n- url: /$f\n  static_dir: $f\n";
        }
        else
        {
			continue;
            $yaml.="\n- url: /$f\n  static_files: $f\n  upload: $f\n";
			//if ($f=='index.html') $yaml.="  login: admin\n";
        }
    }
    $yaml.=file_get_contents(__DIR__.'/app.yaml.post');

    file_put_contents("$dest/app.yaml",$yaml);
    file_put_contents("$dest/cron.yaml",$cron);
    file_put_contents("$dest/php.ini",$ini);

    system('sh '.__DIR__.'/remove.sh');
    
    system('git pull origin master >/dev/null');


    $dir=explode('/',__DIR__);
    file_put_contents(__DIR__.'/log.txt',date('Y-m-d H:i:s')."\n",FILE_APPEND);
    
	$cmd="/opt/google/appengine/appcfg.py --no_cookies -e $mail update $dest";
    
	system('git commit -m deploy '.__DIR__.' 2>/dev/null');
    
	system('git push origin master');
    system($cmd);
    
    //echo "$cmd\n";
    //system('rm -rf '.$dest);
    
    
    
