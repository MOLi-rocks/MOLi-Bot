<?php

namespace MOLiBot\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

class StopWorkingInfoCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'stopworkinginfo';

    /**
     * @var string Command Description
     */
    protected $description = '停班停課查詢（颱風假查詢）';

    /**
     * @inheritdoc
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        $ncdrService = app('MOLiBot\Services\NcdrService');

        $data = $ncdrService->getStopWorkingInfo();

        $this->replyWithChatAction(['action' => Actions::TYPING]);

        switch ($data['status']) {
            case 0:
                $this->replyWithMessage(['text' => '暫無停班停課資訊']);
                break;

            case 1:
                $this->replyWithMessage(['text' => $data['data']]);
                break;

            default:
                $this->replyWithMessage(['text' => '無法取得停班停課資訊']);
        }

        return response('OK', 200);
    }
}
