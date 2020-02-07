<?php

namespace MOLiBot\Http\Controllers;

use Illuminate\Http\Request;

use MOLiBot\Http\Requests;
use MOLiBot\Http\Controllers\Controller;

use Telegram;
use Storage;
use \GuzzleHttp\Client as GuzzleHttpClient;
use \GuzzleHttp\Exception\RequestException as GuzzleHttpRequestException;
use MOLiBot\Models\WhoUseWhatCommand;
use MOLiBot\Services\WelcomeMessageRecordService;

use Log;

class TelegramController extends Controller
{
    /**
     * @var WhoUseWhatCommand
     */
    private $WhoUseWhatCommandModel;

    /**
     * @var WelcomeMessageRecordService
     */
    private $welcomeMessageRecordService;

    /**
     * @var int
     */
    private $MOLiGroupId;

    /**
     * @var string
     */
    private $MOLiWelcomeMsg;

    /**
     * TelegramController constructor.
     *
     * @param WhoUseWhatCommand $WhoUseWhatCommandModel
     * @param WelcomeMessageRecordService $welcomeMessageRecordService
     *
     * @return void
     */
    public function __construct(WhoUseWhatCommand $WhoUseWhatCommandModel,
                                WelcomeMessageRecordService $welcomeMessageRecordService)
    {
        $this->WhoUseWhatCommandModel = $WhoUseWhatCommandModel;
        $this->welcomeMessageRecordService = $welcomeMessageRecordService;
        $this->MOLiGroupId = -1001029969071;
        $this->MOLiWelcomeMsg =
            '歡迎來到 MOLi（創新自造者開放實驗室），這裡是讓大家一起創造、分享、實踐的開放空間。' . PHP_EOL . PHP_EOL .
            '以下是一些資訊連結：' . PHP_EOL . PHP_EOL .
            '/* MOLi 相關 */' . PHP_EOL .
            '- MOLi 聊天群 @MOLi_Rocks' . PHP_EOL .
            '- MOLi Bot @MOLiRocks_bot' . PHP_EOL .
            '- MOLi 廣播頻道 @MOLi_Channel' . PHP_EOL .
            '- MOLi 天氣廣播台 @MOLi_Weather'  . PHP_EOL .
            '- MOLi 知識中心 http://hackfoldr.org/MOLi/' . PHP_EOL .
            '- MOLi 首頁 https://MOLi.Rocks' . PHP_EOL .
            '- MOLi Blog https://blog.moli.rocks' . PHP_EOL . PHP_EOL .
            '/* NCNU 相關 */' . PHP_EOL .
            '- 暨大最新公告 @NCNU_NEWS'  . PHP_EOL .
            '- 暨大最新公告 Line 通知申請 https://bot.moli.rocks/line-notify-auth'  . PHP_EOL . PHP_EOL .
            '/* Telegram 相關 */' . PHP_EOL .
            '- Telegram 非官方中文站 https://telegram.how';
    }

    /**
     * @param Request $request
     * @return Telegram\Bot\Message
     */
    public function postSendMessage(Request $request)
    {
        return $send = Telegram::sendMessage([
            'chat_id' => $request->input('chat_id', ''),
            'text' => $request->input('text', ''),
            'disable_notification' => $request->input('disable_notification', false),
            'reply_to_message_id' => $request->input('reply_to_message_id', NULL),
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|Telegram\Bot\Message
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function postSendPhoto(Request $request)
    {
        $fileName = 'BotAPI'.rand(11111,99999);

        $extension = '';

        $imgpath = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

        if ( $request->hasFile('photo') ) {
            $extension = $request->photo->extension();

            $path = $request->photo->storeAs('/', $fileName.'.'.$extension);
        }

        if ( $request->input('photo') ) {
            //收到網址的話先把圖抓下來，因為有些 host 沒有 User-Agent 這個 header 的話會沒辦法用
            //Ex: hydra DVR
            $client = new GuzzleHttpClient([
                'headers' => [
                    'User-Agent' => 'MOLi Bot'
                ]
            ]);

            try {
                $response = $client->request('GET', $request['photo']);
            } catch (GuzzleHttpRequestException $e) {
                return response()->json(['massages' => 'Can\'t Get Photo From Url'], 404);
            }

            $type = explode('/', $response->getHeader('Content-Type')[0]);

            $extension = $type[1];

            if ($type[0] == 'image') {
                Storage::disk('local')->put($fileName.'.'.$extension, $response->getBody());
            } else {
                return response()->json(['massages' => 'Can\'t Get Photo From Url'], 404);
            }
        }

        $send = Telegram::sendPhoto([
            'chat_id' => $request->input('chat_id', ''),
            'photo' => $imgpath.$fileName.'.'.$extension,
            'disable_notification' => $request->input('disable_notification', false),
            'reply_to_message_id' => $request->input('reply_to_message_id', NULL),
            'caption' => $request->input('caption', ''),
        ]);

        Storage::disk('local')->delete($fileName.'.'.$extension);

        return $send;
    }

    /**
     * @param Request $request
     * @return Telegram\Bot\Message
     */
    public function postSendLocation(Request $request)
    {
        return $send = Telegram::sendLocation([
            'chat_id' => $request->input('chat_id', ''),
            'latitude' => $request->input('latitude', ''),
            'longitude' => $request->input('longitude', ''),
            'disable_notification' => $request->input('disable_notification', false),
            'reply_to_message_id' => $request->input('reply_to_message_id', NULL),
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function postWebhook(Request $request)
    {
        $update = Telegram::commandsHandler(true);
        // Commands handler method returns an Update object.
        // So you can further process $update object
        // to however you want.
        if ( config('logging.log_input') ) {
            Log::info($update);
        }
        /*
        {
            "update_id":(number),
            "message":{
                "message_id":(number),
                "from":{
                    "id":(number),
                    "first_name":"",
                    "username":""
                },
                "chat":{
                    "id":(number),
                    "title":"",
                    "username":"",
                    "type":""
                },
                "date":(number),
                "new_chat_participant":{
                    "id":(number),
                    "first_name":"",
                    "username":""
                },
                "new_chat_member":{
                    "id":(number),
                    "first_name":"",
                    "username":""
                },
                "new_chat_members":[
                {
                    "id":(number),
                    "first_name":"",
                    "username":""
                },
                {
                    "id":(number),
                    "first_name":"",
                    "username":""
                }
                ]
            }
        }
        */
        $data = $update->all();
        $chatId = $data['message']['chat']['id'];
        $chatType = $data['message']['chat']['type'];

        if ( isset($data['message']['new_chat_members']) &&
            !$data['message']['new_chat_members']['is_bot'] &&
            $chatId === $this->MOLiGroupId ) {
            $welcomeMsg = Telegram::sendMessage([
                'chat_id' => $chatId,
                'reply_to_message_id' => $data['message']['message_id'],
                'disable_web_page_preview' => true,
                'text' => $this->MOLiWelcomeMsg
            ]);

            $newChatMemberId = $data['message']['new_chat_member']['id'];
            $welcomeMsgId = $welcomeMsg->getMessageId();
            $this->welcomeMessageRecordService->addNewRecord($chatId, $newChatMemberId, $welcomeMsgId);
        } else if ($chatType === 'private' &&
            !isset($data['message']['entities']) &&
            isset($data['message']['text']) &&
            $this->WhoUseWhatCommandModel->where('user-id', '=', $data['message']['from']['id'])->exists()) {
            $exec = Telegram::getCommandBus();

            $cmd_name = $this->WhoUseWhatCommandModel->where('user-id', '=', $data['message']['from']['id'])->first();

            if ($data['message']['text'] == '/'.$cmd_name->command) {
                $arguments = '';
            } else {
                $arguments = $data['message']['text'];
            }

            $exec->execute($cmd_name->command, $arguments, $update);
        }

        return response('Controller OK', 200);
    }
}
