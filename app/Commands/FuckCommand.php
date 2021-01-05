<?php

namespace MOLiBot\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

use \GuzzleHttp\Client as GuzzleHttpClient;
use \GuzzleHttp\Exception\TransferException as GuzzleHttpTransferException;

class FuckCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'fuck';

    /**
     * @var string Command Description
     */
    protected $description = '骂人专用';

    /**
     * @inheritdoc
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function handle()
    {
        /*
        $baseUrl = 'https://nmsl.shadiao.app/';

        $client = new GuzzleHttpClient();

        try {
            $response = $client->request('GET', $baseUrl, [
                'verify' => false,
                'headers' => [
                    'User-Agent' => 'MOLiBot'
                ],
                'timeout' => 20
            ]);

            $this->replyWithChatAction(['action' => Actions::TYPING]);
            $this->replyWithMessage(['text' => $response->getBody()->getContents()]);
        } catch (GuzzleHttpTransferException $e) {
            $this->replyWithChatAction(['action' => Actions::TYPING]);
            $this->replyWithMessage(['text' => '呵呵！']);
            return response('OK', 200); // 強制結束 command
        }

        return response('OK', 200);
        */
        $this->replyWithChatAction(['action' => Actions::TYPING]);
        $this->replyWithMessage(['text' => '呵呵！']);
        return response('OK', 200); // 強制結束 command
    }
}
