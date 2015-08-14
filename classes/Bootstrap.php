<?php

class Bootstrap extends Bootstrapbase {

    public function mediaPath($prefix='')
    {
		$path=__DIR__.'/../media';
		if ($prefix) $path.='/'.$prefix;
		return $path;
    }

}