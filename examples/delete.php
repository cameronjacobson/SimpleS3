<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use SimpleS3\SimpleS3;

$s3 = new SimpleS3(array(
	'profile'=>'default',
	'bucket'=>$argv[1],
	'region'=>'us-west-2',
	'error'=>function($result){
		throw new Exception($result);
	}
));

echo $s3->delete('samplefile.png');
