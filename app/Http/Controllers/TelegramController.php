<?php

namespace MOLiBot\Http\Controllers;

use Illuminate\Http\Request;

use MOLiBot\Http\Requests;
use MOLiBot\Http\Controllers\Controller;

use Telegram;
use Telegram\Bot\Api;
use Storage;
use \GuzzleHttp\Client as GuzzleHttpClient;
use \GuzzleHttp\Exception\RequestException as GuzzleHttpRequestException;
use MOLiBot\Models\WhoUseWhatCommand;
use MOLiBot\Services\WelcomeMessageRecordService;
use MOLiBot\Services\TelegramService;

use Log;

class TelegramController extends Controller
{
    /** @var Api */
    protected $telegram;

    /**
     * @var WhoUseWhatCommand
     */
    private $WhoUseWhatCommandModel;

    /**
     * @var WelcomeMessageRecordService
     */
    private $welcomeMessageRecordService;

    /**
     * @var TelegramService
     */
    private $telegramService;

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
     * @param Api $telegram
     * @param WhoUseWhatCommand $WhoUseWhatCommandModel
     * @param WelcomeMessageRecordService $welcomeMessageRecordService
     * @param TelegramService $telegramService
     *
     * @return void
     */
    public function __construct(Api $telegram,
                                WhoUseWhatCommand $WhoUseWhatCommandModel,
                                WelcomeMessageRecordService $welcomeMessageRecordService,
                                TelegramService $telegramService)
    {
        $this->telegram = $telegram;
        $this->WhoUseWhatCommandModel = $WhoUseWhatCommandModel;
        $this->welcomeMessageRecordService = $welcomeMessageRecordService;
        $this->telegramService = $telegramService;
    }

    /**
     * @param Request $request
     * @return Telegram\Bot\Objects\Message
     * @throws Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function postSendMessage(Request $request)
    {
        return $this->telegramService->sendMessage(
            $request->input('chat_id', ''),
            $request->input('text', ''),
            $request->input('parse_mode', null),
            $request->input('disable_web_page_preview', false),
            $request->input('disable_notification', false),
            $request->input('reply_to_message_id', null),
            $request->input('reply_markup', null)
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|Telegram\Bot\Objects\Message
     * @throws \GuzzleHttp\Exception\GuzzleException|Telegram\Bot\Exceptions\TelegramSDKException
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
                    'User-Agent'      => 'MOLiBot',
                    'Accept-Encoding' => 'gzip',
                    'cache-control'   => 'no-cache'
                ],
                'timeout' => 10
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

        $send = $this->telegram->sendPhoto([
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
     * @return Telegram\Bot\Objects\Message
     * @throws Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function postSendLocation(Request $request)
    {
        return $this->telegram->sendLocation([
            'chat_id' => $request->input('chat_id', ''),
            'latitude' => $request->input('latitude', ''),
            'longitude' => $request->input('longitude', ''),
            'disable_notification' => $request->input('disable_notification', false),
            'reply_to_message_id' => $request->input('reply_to_message_id', NULL),
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function postWebhook(Request $request)
    {
        try {
            $update = $this->telegram->commandsHandler(true);

            // Commands handler method returns an Update object.
            // So you can further process $update object
            // to however you want.
            if ( config('logging.log_input') ) {
                Log::info($update);
            }

            $message = $update->getMessage();

            if (!$message) {
                return response('No Message', 200);
            }

            $chatType = $message->chat->type;

            if ($message->has('new_chat_member')) {
                $this->telegramService->sendWelcomeMsg($message);
            } elseif ($chatType === 'private' && $message->has('text') && !$message->has('entities')) {
                $this->telegramService->continuousCommand($update);
            }

            return response('Controller OK', 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response('Controller Failed!', 200);
        }
    }
}
