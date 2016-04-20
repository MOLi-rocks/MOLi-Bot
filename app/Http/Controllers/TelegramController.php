<?php

namespace MOLiBot\Http\Controllers;

use Illuminate\Http\Request;

use MOLiBot\Http\Requests;
use MOLiBot\Http\Controllers\Controller;

use Telegram;
use Validator;
use Storage;

class TelegramController extends Controller
{

    protected $telegram;

    public function __construct( Telegram $telegram )
    {
        $this->telegram = $telegram;
    }

    public function postSendMessage(Request $request)
    {
        return $send = Telegram::sendMessage([
            'chat_id' => $request['chat_id'],
            'text' => $request['text']
        ]);
    }

    public function postSendPhoto(Request $request)
    {
        $fileName = rand(11111,99999);

        if ( $request->hasFile('photo') ) {
            $extension = $request['photo']->getClientOriginalExtension();

            storage::disk('local')->put($fileName.'.'.$extension, file_get_contents($request->file('photo')->getRealPath()));

            $send = Telegram::sendPhoto([
                'chat_id' => $request['chat_id'],
                'photo' => '../storage/app/'.$fileName.'.'.$extension
                //'caption' => 'Some caption'
            ]);

            Storage::disk('local')->delete($fileName.'.'.$extension);

            return $send;
        }

        if ( $request->input('photo') ) {
            //收到網址的話先把圖抓下來，因為有些 host 沒有 User-Agent 這個 header 的話會沒辦法用
            //Ex: hydra DVR
            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36)'
                ]
            ]);

            $response = $client->request('GET', $request['photo']);

            $type = explode("/",$response->getHeader('Content-Type')[0]);

            if ($type[0] == 'image') {
                storage::disk('local')->put($fileName.'.'.$type[1], $response->getBody());

                $send = Telegram::sendPhoto([
                    'chat_id' => $request['chat_id'],
                    'photo' => '../storage/app/'.$fileName.'.'.$type[1]
                    //'caption' => 'Some caption'
                ]);

                Storage::disk('local')->delete($fileName.'.'.$type[1]);

                return $send;
            }
        }

        return $send = Telegram::sendPhoto([
            'chat_id' => $request->input('chat_id', ''),
            'photo' => $request->input('photo', '')
            //'caption' => 'Some caption'
        ]);
    }

    public function postSendLocation(Request $request)
    {
        return $send = Telegram::sendLocation([
            'chat_id' => $request['chat_id'],
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude']
        ]);
    }

    public function postWebhook(Request $request)
    {
        $update = Telegram::commandsHandler(true);
        // Commands handler method returns an Update object.
        // So you can further process $update object
        // to however you want.
    }
}
