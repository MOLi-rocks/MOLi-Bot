<?php

namespace MOLiBot\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

use DB;
use MOLiBot\Models\WhoUseWhatCommand;

class SearchStaffContactCommand extends Command
{
    /**
     * @var string Command Name
     */

    protected $name = 'staffcontact';

    /**
     * @var string Command Description
     */

    protected $description = '使用關鍵字搜尋暨大教職員聯絡資訊(限私訊使用)';

    /**
     * @inheritdoc
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Throwable
     */
    public function handle()
    {
        $update = $this->getUpdate()->getMessage();

        $chatType = $update->chat->type;
        $messageFrom = $update->from->id;
        $messageId = $update->messageId;
        $argument = $this->arguments[0];

        if ( $chatType === 'private' ) {
            if (empty($argument)) {
                $this->replyWithChatAction(['action' => Actions::TYPING]);
                $this->replyWithMessage([
                    'text' => '請回覆想查詢的關鍵字(不支援多條件搜尋)',
                    'reply_to_message_id' => $messageId
                ]);

                DB::transaction(function () use ($messageFrom) {
                    WhoUseWhatCommand::where('user-id', '=', $messageFrom)->delete();

                    WhoUseWhatCommand::create([
                        'user-id' => $messageFrom,
                        'command' => $this->name
                    ]);
                });

                return response('OK', 200); // 強制結束 command
            }

            $ncnuService = app('MOLiBot\Services\NcnuService');

            $json = $ncnuService->getStaffContact($argument);

            $text = '';

            $count = 0;

            if (count($json) <= 1) {
                $this->replyWithChatAction(['action' => Actions::TYPING]);
                $this->replyWithMessage([
                    'text' => '查無資料 QQ',
                    'reply_to_message_id' => $messageId
                ]);

                WhoUseWhatCommand::where('user-id', '=', $messageFrom)->delete();

                return response('OK', 200);
            }

            if (count($json) > 12) {
                $text .= '結果超過 10 筆，建議使用更精確關鍵字搜尋或至 http://ccweb1.ncnu.edu.tw/telquery/StaffQuery.asp 直接搜尋'
                    . PHP_EOL . PHP_EOL . PHP_EOL;
            }

            foreach ($json as $index => $items) {
                if ($index !== 0 && $count < 10) {
                    $text .= $json[0][0] . ': ' . $json[$index][0] . PHP_EOL .
                             $json[0][2] . ': ' . $json[$index][2] . PHP_EOL .
                             $json[0][4] . ': ' . $json[$index][4] . PHP_EOL .
                             $json[0][6] . ': ' . $json[$index][6] . PHP_EOL .
                             $json[0][7] . ': ' . $json[$index][7] . PHP_EOL . PHP_EOL . PHP_EOL;
                    $count++;
                }
            }

            $this->replyWithChatAction(['action' => Actions::TYPING]);
            $this->replyWithMessage([
                'text' => $text,
                'reply_to_message_id' => $messageId
            ]);

            WhoUseWhatCommand::where('user-id', '=', $messageFrom)->delete();
        } else {
            $this->replyWithChatAction(['action' => Actions::TYPING]);
            $this->replyWithMessage([
                'text' => '此功能限一對一對話',
                'reply_to_message_id' => $messageId
            ]);

            return response('OK', 200); // 強制結束 command
        }

        return response('OK', 200);
    }
}
