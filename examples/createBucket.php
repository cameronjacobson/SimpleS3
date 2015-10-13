<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use SimpleS3\SimpleS3;

$s3 = new SimpleS3(array(
	'profile'=>'default',
	'bucket'=>'mytestbucket'.time(),
	'region'=>'us-west-2',
	'error'=>function($result){
		throw new Exception($result);
	}
));

$s3->create(array(
	'ACL'=>'authenticated-read'
));

$s3->cors(array(
	'headers'=>array('Authorization'),
	'methods'=>array('GET','POST','PUT'),
	'origins'=>array('micropay.rocks'),
	'age'=>300
));
