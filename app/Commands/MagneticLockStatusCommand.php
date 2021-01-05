<?php

namespace MOLiBot\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

use Storage;
use \GuzzleHttp\Client as GuzzleHttpClient;
use \GuzzleHttp\Exception\TransferException as GuzzleHttpTransferException;

class MagneticLockStatusCommand extends Command
{
   /**
     * @var string Command Name
     */
    protected $name = 'MagneticLockStatus';

    /**
     * @var string Command Description
     */
    protected $description = 'MOLi 磁力鎖狀態（測試中）';

    /**
     * @inheritdoc
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        $client = new GuzzleHttpClient();

        try {
            $response = $client->request('GET', 'http://163.22.32.201:3087/status', ['timeout' => 10]);
        } catch (GuzzleHttpTransferException $e) {
            $this->replyWithChatAction(['action' => Actions::TYPING]);
            $this->replyWithMessage(['text' => '服務未啟動']);
            return response('OK', 200); // 強制結束 command
        }

        if (isset(json_decode($response->getBody())->{'status'})) {
            $status = json_decode($response->getBody())->{'status'};
            $message = json_decode($response->getBody())->{'message'};
        } else {
            $status = -3;// 隨便設定一個非 0 或 1 的值就當作壞掉了
            $message = '';
        }

        switch ($status){
            case '1':
            case '0':
                $reply = $message;
            break;

            default:
                $reply = '磁力鎖狀態不明，猴子們正努力維修中！';
        }

        $this->replyWithChatAction(['action' => Actions::TYPING]);
        $send = $this->replyWithMessage(['text' => $reply]);

        $chatType = $this->getUpdate()->getMessage()->getChat()->getType();

        if ( $chatType === 'private' ) {
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

            if ($type[0] === 'image') {
                $fileName = 'MagneticLockStatusCommand' . rand(11111,99999);

                Storage::disk('local')->put($fileName . '.' . $type[1], $response->getBody());

                $this->replyWithChatAction(['action' => Actions::UPLOAD_PHOTO]);
                $this->replyWithPhoto([
                    'reply_to_message_id' => $send->getMessageId(),
                    'photo' => $imgpath . $fileName . '.' . $type[1],
                ]);

                Storage::disk('local')->delete($fileName.'.'.$type[1]);
            }
        }

        return response('OK', 200);
    } 
}
