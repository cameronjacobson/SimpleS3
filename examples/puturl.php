<?php

require_once(dirname(__DIR__).'/vendor/autoload.php');

use SimpleS3\SimpleS3;

$s3 = new SimpleS3(array(
	'profile'=>'default',
	'bucket'=>'{{BUCKETNAME}}',
	'region'=>'us-west-2',
	'error'=>function($result){
		throw new Exception($result);
	}
));

// IN ORDER TO PUT FILE INTO S3 BUCKET, USE FOLLOWING COMMAND:
//   curl -v -H "Content-Type: {{CONTENTTYPE}}" -T {{FILENAME}} "{{PUTURL}}"

echo $s3->puturl('{{S3KEY}}','image/png');
echo PHP_EOL;
