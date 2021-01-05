<?php

namespace MOLiBot\Commands;

use Exception;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

use MOLiBot\DataSources\MoliKktix;

class ActivityCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'activity';

    /**
     * @var string Command Description
     */
    protected $description = '顯示 MOLi 未來公開活動';

    /**
     * @inheritdoc
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        $kktix = new MoliKktix();

        try {
            $events = $kktix->getContent();

            $activity = 0;

            foreach ($events['entry'] as $num => $detail) {
                if ( strtotime($detail['published']) > strtotime('now') ) {
                    $this->replyWithChatAction(['action' => Actions::TYPING]);

                    $this->replyWithMessage([
                        'text' => $detail['title'] . PHP_EOL . '' . PHP_EOL . $detail['content'] . PHP_EOL . '' . PHP_EOL . $detail['url']
                    ]);

                    $activity++;
                } else break;
            }

            if ($activity == 0) {
                $this->replyWithChatAction(['action' => Actions::TYPING]);
                $this->replyWithMessage(['text' => '最近無排定活動，歡迎在群組挖坑' . PHP_EOL . 'https://www.facebook.com/groups/MOLi.rocks']);
            }

            return response('OK', 200);
        } catch (Exception $e) {
            $this->replyWithChatAction(['action' => Actions::TYPING]);
            $this->replyWithMessage(['text' => '壞惹 QAQ']);
            return response('OK', 200); // 強制結束 command
        }
    }
}
