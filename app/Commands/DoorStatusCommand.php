<?php

namespace MOLiBot\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

use Storage;
use Exception;
use MOLiBot\DataSources\MoliDoorStatus;
use \GuzzleHttp\Client as GuzzleHttpClient;
use \GuzzleHttp\Exception\TransferException as GuzzleHttpTransferException;

class DoorStatusCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'status';

    /**
     * @var string Command Description
     */
    protected $description = '現在 MOLi 是開的還是關的呢？';

    /**
     * @inheritdoc
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        try {
            $data = new MoliDoorStatus();

            $content = $data->getContent();

            $status = $content['Status'];

            switch ($status){
                case 1:
                    $reply = 'MOLi 現在 關門中';
                    break;

                case 0:
                    $reply = 'MOLi 現在 開門中';
                    break;

                default:
                    $reply = '門鎖狀態不明，猴子們正努力維修中！';
            }

            $chatType = $this->getUpdate()->getMessage()->chat->type;

            $this->replyWithChatAction(['action' => Actions::TYPING]);
            $send = $this->replyWithMessage(['text' => $reply]);

            if ($chatType === 'private') {
                $client = new GuzzleHttpClient([
                    'headers' => [
                        'User-Agent'      => 'MOLiBot',
                        'Accept-Encoding' => 'gzip',
                        'cache-control'   => 'no-cache'
                    ],
                    'timeout' => 10
                ]);

                try {
                    $response = $client->request('GET', config('moli.rpos.snapshot_url'), ['timeout' => 10]);
                } catch (GuzzleHttpTransferException $e) {
                    $this->replyWithMessage(['text' => '暫時無法取得截圖！']);
                    return response('OK', 200);// 強制結束 command
                }

                $type = explode('/', $response->getHeader('Content-Type')[0]);

                $imgpath = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix();

                if ($type[0] == 'image') {
                    $fileName = 'DoorStatusCommand' . rand(11111,99999);

                    Storage::disk('local')->put($fileName . '.' . $type[1], $response->getBody());

                    $this->replyWithChatAction(['action' => Actions::UPLOAD_PHOTO]);
                    $this->replyWithPhoto([
                        'reply_to_message_id' => $send->getMessageId(),
                        'photo' => $imgpath . $fileName . '.' . $type[1],
                    ]);

                    Storage::disk('local')->delete($fileName . '.' . $type[1]);
                }
            }

            return response('OK', 200);
        } catch (Exception $e) {
            $this->replyWithChatAction(['action' => Actions::TYPING]);
            $this->replyWithMessage(['text' => '服務未啟動']);
            return response('OK', 200); // 強制結束 command
        }
    } 
}
