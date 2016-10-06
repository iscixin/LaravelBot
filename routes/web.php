<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get(
    '/talk/{message}', [
    'as' => 'bot.talk',
    'uses' => 'LineBotCallback@talk',
    ]
);

Route::post(
    '/linebot', [
    'as' => 'bot.linebot',
    'uses' => 'LineBotCallback@index',
    ]
);

// Image Resize Routes
Route::get('/storage/resized/{folder}/{size}/{name}', function ($folder = null, $size = null, $name = null) {
    if (!is_null($folder) and !is_null($size) and !is_null($name) and \File::exists('storage/' . $folder . '/' . $name)) {
        $size = explode('x', $size);
        $width = (isset($size[0])) ? $size[0] : null;
        $height = (isset($size[1])) ? $size[1] : null;
        $cache_image = \Image::cache(function ($image) use ($width, $height, $folder, $name) {
           return $image->make(url('/storage/'.$folder.'/'.$name))
                  ->resize($width, $height, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                  }, 10); // cache for 10 minutes

        return Response::make($cache_image, 200, ['Content-Type' => 'image/jpeg']);
    } else {
        abort(404);
    }
});
