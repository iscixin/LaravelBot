<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use YoutubeDl\YoutubeDl;
use YoutubeDl\Exception\CopyrightException;
use YoutubeDl\Exception\NotFoundException;
use YoutubeDl\Exception\PrivateVideoException;
use File;

class APIController extends Controller
{
    //
    public function youtube($videoid = null) {
    	if ($videoid) {
			$rs = \Youtube::getVideoInfo($videoid);
    	} else {
	        $res = \Youtube::getPlaylistItemsByPlaylistId('PLle97lwCzO_TYcemOOa7YFbxeDuxB9vdX');
	        $rs = array_shift($res);
    	}

        return response()->json($rs, 200);
    }

    public function VideoToAudio($videoid) {
    	if (!File::exists(public_path() . '/storage/botaudio/'.$videoid.'.m4a')) {
	    	$dl = new YoutubeDl([
			    'extract-audio' => true,
			    'audio-format' => 'm4a',
			    'audio-quality' => 0, // best
			    'output' => $videoid.'.%(ext)s',
			]);
			$dl->setDownloadPath(public_path() . '/storage/botaudio/');
			$video = $dl->download('https://www.youtube.com/watch?v='.$videoid);
    	}

	$headers = [
              'Content-Type: audio/mp4',
        ];

		return response()->file(public_path() . '/storage/botaudio/' . $videoid . '.m4a', $headers);
    }

    public function RailTime($date) {
        $jsonfile = storage_path() . '/railway/' . $date . '.json';
	if (File::exists($jsonfile)) {
	  $headers = [
              'Content-Type: application/json',
          ];
          return response()->file($jsonfile, $headers);
	} else {
          return response()->json([], 404);
	}
    }
}
