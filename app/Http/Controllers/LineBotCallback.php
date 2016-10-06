<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use LINE\LINEBot\HTTPClient\GuzzleHTTPClient;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Exception\InvalidEventRequestException;
use LINE\LINEBot\Exception\InvalidSignatureException;
use LINE\LINEBot\Exception\UnknownEventTypeException;
use LINE\LINEBot\Exception\UnknownMessageTypeException;
use Config;
use Log;
use Response;
use Rivescript;
use Storage;

class LineBotCallback extends Controller
{
    //
    public function __construct()
    {
        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(Config::get('linebot.channelAccessToken'));
        $this->bot = new \LINE\LINEBot($httpClient, ['channelSecret' => Config::get('channelSecret')]);
    	$this->words = ['嗯哼', '花生省魔術', '', '', '', '', 'UCCU', 'UccU', '你看看你', '嘿嘿', '啾咪', '⋯…⋯', '', '', '喔喔喔', '安安哦', '', '', '嘖嘖', '科科', '顆顆', '', '呵呵'];

        Rivescript::load(storage_path('rivescript/my.rive'));
    }

    public function talk($message) {
        $bot = $this->bot;

        $mywords = $this->words;

        $rivesay = Rivescript::reply(null, $message);
        $isay = (rand(0, 1)) ? $mywords[array_rand($mywords, 1)] : '';

        return Response::json(['rivesay'=>$rivesay, 'isay'=>$isay], 200);
    }

    public function index(Request $request)
    {

        $bot = $this->bot;

        foreach ($request['events'] as $event) {
            Log::info($event);

            $resp1 = $resp = null;
            $replyToken = $event['replyToken'];

            switch ($event['message']['type']) {
                case 'text':
             	    $mywords = $this->words;

                    $rivesay = Rivescript::reply(null, $event['message']['text']);
                    $isay = (rand(0, 1)) ? $mywords[array_rand($mywords, 1)] : '';

                    if ($rivesay) {
                        if ($this->isJson($rivesay)) {
            				$audio = json_decode($rivesay);
                        	$originalContentUrl = secure_url('/api/youtube/dl/'.$audio->videoid.'.m4a');

	                        $AudioMessage = new \LINE\LINEBot\MessageBuilder\AudioMessageBuilder($originalContentUrl, $audio->duration);
	                        $TextMessage = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($audio->title . $audio->url . "\n" . $audio->desc);
            				$respaudio = $resptext = null;

            				if (rand(0, 1)) {
            					$respaudio = $bot->replyMessage($replyToken, $AudioMessage);
            				} else {
            					$resptext = $bot->replyMessage($replyToken, $TextMessage);
            				}
            				if ($respaudio) {
            					Log::info($respaudio->getHTTPStatus() . ': ' . $respaudio->getRawBody());
            				}
            				if ($resptext) {
            					Log::info($resptext->getHTTPStatus() . ': ' . $resptext->getRawBody());
            				}
            		} elseif ($rep = $this->parseResponse($rivesay) and $rep) {
                            $rs = json_decode($rep);
                            switch ($rs->method) {
                                case 'image':
                                    $pickedImage = $rs->pickedImage;
                                    $pickedImagePreview = $rs->pickedImagePreview;

                                    $ImageMessage = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder(
                                    $pickedImage, $pickedImagePreview);

                                    $respImage = $bot->replyMessage($replyToken, $ImageMessage);

                                    if ($respImage) {
                                        Log::info($respImage->getHTTPStatus() . ': ' . $respImage->getRawBody());
                                    }
                                    break;

                                case 'video':
                                    $originalContentUrl = $rs->originalContentUrl;
                                    $previewImageUrl = $rs->previewImageUrl;

                                    $VideoMessage = new \LINE\LINEBot\MessageBuilder\VideoMessageBuilder(
                                    $originalContentUrl, $previewImageUrl);

                                    $respVideo = $bot->replyMessage($replyToken, $VideoMessage);

                                    if ($respVideo) {
                                        Log::info($respVideo->getHTTPStatus() . ': ' . $respVideo->getRawBody());
                                    }
                                    break;
                                default:
                                    # code...
                                    break;
                            }

                        } else {
                        	$resp = $bot->replyText($replyToken, $rivesay);
            		}
                    } elseif ($isay) {
                        $resp = $bot->replyText($replyToken, $isay);
                    }

                    if ($resp) {
                        Log::info($resp->getHTTPStatus() . ': ' . $resp->getRawBody());
                    }
                    break;

                case 'image':
                case 'sticker':
                    $files = Storage::disk('botimages')->files();
                    $pickedKey = array_rand($files);
                    $pickedImage = secure_url('/storage/botimage/' . $files[$pickedKey]);
                    $pickedImagePreview = secure_url('/storage/resized/botimage/240x/' . $files[$pickedKey]);

                    $ImageMessage = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder(
                    $pickedImage, $pickedImagePreview);

                    $resp1 = $bot->replyMessage($replyToken, $ImageMessage);

                    if ($resp1) {
                        Log::info($resp1->getHTTPStatus() . ': ' . $resp1->getRawBody());
                    }
                    break;

                default:
                    # code...
                    break;
            }
        }

        return Response::json(['resp'=>$resp, 'resp1'=>$resp1], 200);
    }

    private function isJson($str) {
    	json_decode($str);
    	return (json_last_error()===JSON_ERROR_NONE);
    }

    private function parseResponse($str) {
        $r = explode('@@:', $str);

        if (is_array($r) and isset($r[1])) {
            $json = [];

            switch ($r[0]) {
                case '<rimg>':
                    # code...
                    if ($r[1]) {
                        $file = $r[1] . '.jpg';
                    } else {
                        $files = Storage::disk('botimages')->files();
                        $pickedKey = array_rand($files);
                        $file = $files[$pickedKey];
                    }
                    $json['method'] = 'image';
                    $json['pickedImage'] = secure_url('/storage/botimage/' . $file);
                    $json['pickedImagePreview'] = secure_url('/storage/resized/botimage/240x/' . $file);

                    return json_encode($json);
                    break;

                case '<rvideo>':
                    # code...
                    $json['method'] = 'video';
                    $json['originalContentUrl'] = secure_url('/storage/botvideo/' . $r[1] . '.mp4');
                    $json['previewImageUrl'] = secure_url('/storage/botvideo/' . $r[1] . '.jpg');

                    return json_encode($json);
                    break;

                default:
                    # code...
                    break;
            }
        }

        return false;
    }

}
