<?php

namespace MOLiBot\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

use DB;
use Telegram\Bot\Keyboard\Keyboard as TelegramKeyboard;
use Storage;
use \GuzzleHttp\Client as GuzzleHttpClient;
use \GuzzleHttp\Exception\TransferException as GuzzleHttpTransferException;

use MOLiBot\Models\WhoUseWhatCommand;

class HydraDVRRemoteControlCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'DVRremoteController';

    /**
     * @var string Command Description
     */
    protected $description = 'MOLi DVR 遙控器';

    /**
     * @inheritdoc
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Throwable
     */
    public function handle()
    {
        $message = $this->getUpdate()->getMessage();
        $chatType = $message->chat->type;
        $messageId = $message->messageId;
        $messageFromId = $message->from->id;
        $argument = $this->arguments[0];

        if ( $chatType === 'private' ) {
            if (empty($argument)) {
                $keyboard = TelegramKeyboard::make([
                    ['Up'],
                    ['Left', 'Right'],
                    ['Down'],
                    ['Zoom In', 'ESC', 'Zoom Out']
                ]);

                $this->replyWithMessage([
                    'text' => '歡迎使用遙控器 XD',
                    'reply_to_message_id' => $messageId,
                    'reply_markup' => $keyboard
                ]);

                DB::transaction(function () use ($messageFromId) {
                    WhoUseWhatCommand::where('user-id', '=', $messageFromId)->delete();

                    WhoUseWhatCommand::create([
                        'user-id' => $messageFromId,
                        'command' => $this->name
                    ]);
                });

                return response('OK', 200); // 強制結束 command
            }

            /*
            if (WhoUseWhatCommand::where('command', '=', $this->name)
                ->where('user-id', '!=', $update->all()['message']['from']['id'])
                ->exists()) {
                Telegram::sendMessage([
                    'chat_id' => $update->all()['message']['chat']['id'],
                    'text' => '有其他人正在使用遙控器，請稍後再試（？',
                    'reply_to_message_id' => $update->all()['message']['message_id']
                ]);

                return response('OK', 200); // 強制結束 command
            }
            */

            switch ($argument) {
                case 'ESC':
                    $keyboard = TelegramKeyboard::remove();

                    $this->replyWithChatAction(['action' => Actions::TYPING]);
                    $this->replyWithMessage([
                        'text' => '感謝使用遙控器 XD',
                        'reply_markup' => $keyboard
                    ]);

                    $this->control('door');

                    WhoUseWhatCommand::where('user-id', '=', $messageFromId)->delete();

                    break;

                case 'Up':
                    $this->control('up');
                    break;

                case 'Down':
                    $this->control('down');
                    break;

                case 'Left':
                    $this->control('left');
                    break;

                case 'Right':
                    $this->control('right');
                    break;

                case 'Zoom In':
                    $this->control('zoomin');
                    break;

                case 'Zoom Out':
                    $pic = $this->control('zoomout');
                    break;

                default:
                    $this->replyWithChatAction(['action' => Actions::TYPING]);
                    $this->replyWithMessage([
                        'text' => '不懂 QQ',
                        'reply_to_message_id' => $messageId,
                    ]);

                    break;
            }
        } else {
            $this->replyWithChatAction(['action' => Actions::TYPING]);
            $this->replyWithMessage([
                'text' => '此功能限一對一對話',
                'reply_to_message_id' => $messageId,
            ]);

            return response('OK', 200); // 強制結束 command
        }

        return response('OK', 200);
    }

    private function control($position) {
        try {
            $client = new GuzzleHttpClient();

            $client->request('GET', config('moli.dvr.control_url') . $position, [
                'headers' => [
                    'User-Agent'      => 'MOLiBot',
                    'Accept'          => 'application/json',
                    'Accept-Encoding' => 'gzip',
                    'cache-control'   => 'no-cache'
                ],
                'timeout' => 10
            ]);

            if ($position != 'door') {
                $response = $client->request('GET', config('moli.dvr.snapshot_url'), [
                    'headers' => [
                        'User-Agent'      => 'MOLiBot',
                        'Accept'          => 'application/json',
                        'Accept-Encoding' => 'gzip',
                        'cache-control'   => 'no-cache'
                    ],
                    'timeout' => 10
                ]);

                $type = explode('/', $response->getHeader('Content-Type')[0]);

                $imgpath = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

                if ($type[0] == 'image') {
                    $fileName = 'HydraDVRRemoteController' . rand(11111, 99999);

                    Storage::disk('local')->put($fileName . '.' . $type[1], $response->getBody());

                    $this->replyWithPhoto([
                        'reply_to_message_id' => $this->getUpdate()->getMessage()->messageId,
                        'photo' => $imgpath . $fileName . '.' . $type[1],
                    ]);

                    Storage::disk('local')->delete($fileName . '.' . $type[1]);
                }
            }

            return 'done';
        } catch (GuzzleHttpTransferException $e) {
            $this->replyWithChatAction(['action' => Actions::TYPING]);
            $this->replyWithMessage(['text' => '網路連線異常 QAQ']);
            return 'QQ';
        }
    }
}
