<?php
require __DIR__.'/vendor/autoload.php';

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;
 
	// set false for production
	$pass_signature = true;
	 
	// set LINE channel_access_token and channel_secret
	$channel_access_token = "ZaZLTEK1MTqDnpcTAMvtw9WlFdSoh5GgrHbXGR/2odKDVORCU/WHLu25dwsOOTJ+oBmusbPuAQ+CHbq9NJLbjZDUrbt8gpOea2KuNBdt6+m6XaYb1RZLLOQFWQ9DESoeW6GvkSh1M8e2Y41sCbIYJAdB04t89/1O/w1cDnyilFU=";
	$channel_secret = "afe04fa15dd4ae5b1fbb74948fb22cd8";
	 
	// inisiasi objek bot
	$httpClient = new CurlHTTPClient($channel_access_token);
	$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);
	 
	$configs =  [
	    'settings' => ['displayErrorDetails' => true],
	];
	$app = new Slim\App($configs);
	 
	// buat route untuk url homepage
	$app->get('/', function($req, $res)
	{
	  echo "Welcome  at Slim Framework";
	});
 
	// buat route untuk webhook
	$app->post('/webhook', function ($request, $response) use ($bot, $pass_signature)
	{
	    // get request body and line signature header
	    $body        = file_get_contents('php://input');
	    $signature = isset($_SERVER['HTTP_X_LINE_SIGNATURE']) ? $_SERVER['HTTP_X_LINE_SIGNATURE'] : '';
	 
	    // log body and signature
	    file_put_contents('php://stderr', 'Body: '.$body);
	 
	    if($pass_signature === false)
	    {
	        // is LINE_SIGNATURE exists in request header?
	        if(empty($signature)){
	            return $response->withStatus(400, 'Signature not set');
	        }
	 
	        // is this request comes from LINE?
	        if(! SignatureValidator::validateSignature($body, $channel_secret, $signature)){
	            return $response->withStatus(400, 'Invalid signature');
	        }
	    }
	 
	    $data = json_decode($body, true);
	    if (is_array($data['events'])) {
	    	foreach ($data['events'] as $event)
		    {
		        if ($event['type'] == 'message')
		        {
		        	if ($event['source']['type'] == "user") {
			            if($event['message']['type'] == 'text'){
			                // Send balik
			                $user = $bot->getProfile($event['source']['userId']);
			                $result = $bot->replyText($event['replyToken'], $event['message']['text']);
			 
			                return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
			            }else if( ($event['message']['type'] == 'image' || $event['message']['type'] == 'video') or
							    ($event['message']['type'] == 'audio' || $event['message']['type'] == 'file')){
						    $basePath  = $request->getUri()->getBaseUrl();
						    $contentURL  = $basePath."/content/".$event['message']['id'];
						    $contentType = ucfirst($event['message']['type']);
						    $result = $bot->replyText($event['replyToken'], $contentType. " yang Anda kirim bisa diakses dari link:\n " . $contentURL);
						 
						    return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
						}	
		        	} else {
		        		if($event['source']['userId']){
						    $userId     = $event['source']['userId'];
						    $getprofile = $bot->getProfile($userId);
						    $profile    = $getprofile->getJSONDecodedBody();
						    $greetings  = new TextMessageBuilder("Halo, ".$profile['displayName']);
					 
					    	$result = $bot->replyMessage($event['replyToken'], $greetings);
					    	return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
						} else {
						    // send same message as reply to user
						    $result = $bot->replyText($event['replyToken'], $event['message']['text']);
						    return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
						}
		        	}
		        }
		    }
	    }
	 
	});

	$app->get('/pushmessage', function($req, $res) use ($bot){
		$userid = '';
		$textMessageBuilder = new TextMessageBuilder('Halo, ini test doang');
		$result = $bot->pushMessage($userid, $textMessageBuilder);

		return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
	});

	$app->get('/profile', function($req, $res) use ($bot)
	{
	    // get user profile
	    $userId = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
	    $result = $bot->getProfile($userId);
	   
	    return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
	});

	$app->get('/content/{messageId}', function($req, $res) use ($bot){
		$route      = $req->getAttribute('route');
	    $messageId = $route->getArgument('messageId');
	    $result = $bot->getMessageContent($messageId);
	 
	    // set response
	    $res->write($result->getRawBody());
	 
	    return $res->withHeader('Content-Type', $result->getHeader('Content-Type'));
	});
 
$app->run();

?>