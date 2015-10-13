<?php

namespace SimpleS3;

use \Aws\S3\S3Client;

/**
 * USEFUL RESOURCES:
 *  https://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/commands.html
 */

class SimpleS3
{
	private $client;
	private $bucket;
	private $region;
	private $granttypes;

	public function __construct(array $params){
		$this->errorhandler = $params['error'];
		$this->bucket = $params['bucket'];
		$this->region = $params['region'];
		$this->profile = isset($params['profile']) ? $params['profile'] : 'default';

		$this->granttypes = array(
			'GrantRead','GrantWrite','GrantReadACP','GrantWriteACP','GrantFullControl'
		);
		try{
			$this->client = S3Client::factory(array(
				'profile' => $this->profile,
				'region'  => $this->region,
				'version' => '2006-03-01'
			));
		}
		catch(\Exception $e){
			$this->errorhandler->__invoke($e->getMessage());
		}
	}

	public function get($key){
		try{
			$result = $this->client->getObject(array(
				'Bucket'=>$this->bucket,
				'Key'=>$key
			));
			return (string)$result->get('Body');
		}
		catch(\Exception $e){
			$this->errorhandler->__invoke($e);
		}
	}

	public function put($key,$value,$contenttype){
		try{
			$result = $this->client->PutObject(array(
				'Bucket'=>$this->bucket,
				'Key'=>$key,
				'Body'=>$value,
				'ContentType'=>$contenttype
			));
			return $result;
		}
		catch(\Exception $e){
			$this->errorhandler->__invoke($e);
		}
	}

	public function delete($key){
		try{
			$result = $this->client->deleteObject(array(
				'Bucket'=>$this->bucket,
				'Key'=>$key
			));
			return $result;
		}
		catch(\Exception $e){
			$this->errorhandler->__invoke($e);
		}
	}

	public function create(array $params = array()){
		$grants = array(
			'GrantRead'=>array(),
			'GrantWrite'=>array(),
			'GrantReadACP'=>array(),
			'GrantWriteACP'=>array(),
			'GrantFullControl'=>array()
		);

		if(isset($params['grant'])){
			// Read, Write, ReadACP, WriteACP, GrantFullControl
			// id, uri, emailAddress
			// ex: 'GrantRead' => 'emailAddress="user@domain.com", emailAddress="user2@domain2.com"
			foreach($params['grant'] as $type=>$ids){
				foreach($ids as $id){
					switch(strtolower($type)){
						case 'read':
						case 'grantread':
							$this->buildGrantList($grants['GrantRead'],$id);
							break;
						case 'write':
						case 'grantwrite':
							$this->buildGrantList($grants['GrantWrite'],$id);
							break;
						case 'full':
						case 'grantfull':
						case 'grantfullcontrol':
							$this->buildGrantList($grants['GrantFullControl'],$id);
							break;
						case 'readacp':
							$this->buildGrantList($grants['GrantReadACP'],$id);
							break;
						case 'writeacp':
							$this->buildGrantList($grants['GrantWriteACP'],$id);
							break;
					}
				}
			}
		}

		foreach($this->granttypes as $granttype){
			if(!empty($grants[$granttype])){
				$config[$granttype] = implode(',',$grants[$granttype]);
			}
		}

		if(isset($this->region)){
			$config['Location'] = $this->region;
		}

		$config['Bucket'] = isset($params['bucket']) ? $params['bucket'] : $this->bucket;
		if(isset($params['ACL'])){
			$config['ACL'] = $params['ACL'];
		}

		try{
			$result = $this->client->createBucket($config);
			if($result['@metadata']['statusCode'] !== 200){
				$this->errorhandler->__invoke($result);
			}
		}catch(\Exception $e){
			$this->errorhandler->__invoke($e);
		}
	}

	//http://docs.aws.amazon.com/aws-sdk-php/v2/api/class-Aws.S3.S3Client.html#_putBucketCors
	public function cors(array $params){
		$result = $this->client->putBucketCors(array(
			'Bucket' => isset($params['bucket']) ? $params['bucket'] : $this->bucket,
			'CORSConfiguration'=>array(
				'CORSRules' => array(
					array(
						'AllowedHeaders' => isset($params['headers']) ? $params['headers'] : array('Authorization'),
						'AllowedMethods' => $params['methods'],
						'AllowedOrigins' => $params['origins'],
						'MaxAgeSeconds' => isset($params['age']) ? $params['age'] : 300,
					)
				)
			)
		));
	}

	// https://docs.aws.amazon.com/aws-sdk-php/v3/guide/service/s3-presigned-url.html
	public function geturl($key){
		$cmd = $this->client->getCommand('GetObject', array(
			'Bucket'=>$this->bucket,
			'Key'=>$key
		));
		$request = $this->client->createPresignedRequest($cmd, '+2 minutes');
		return (string)$request->getUri();
	}

	// https://docs.aws.amazon.com/aws-sdk-php/v3/guide/service/s3-presigned-url.html
	public function puturl($key, $contenttype){
		$cmd = $this->client->getCommand('PutObject', array(
			'Bucket'=>$this->bucket,
			'Key'=>$key,
			'ContentType'=>$contenttype
		));
		$request = $this->client->createPresignedRequest($cmd, '+2 minutes');
		return (string)$request->getUri();
	}

	private function buildGrantList(&$grants, $id){
		if(filter_var($id,FILTER_VALIDATE_EMAIL)){
			$grants[] = 'emailAddress="'.$id.'"';
		}
		elseif(filter_var($id,FILTER_VALIDATE_URL)){
			$grants[] = 'uri="'.$id.'"';
		}
		else{
			$grants[] = 'id="'.$id.'"';
		}
	}

	private function E($value){
		error_log(var_export($value,true));
	}
}
