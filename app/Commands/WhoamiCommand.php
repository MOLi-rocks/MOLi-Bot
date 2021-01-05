<?php

namespace MOLiBot\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

class WhoamiCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'whoami';

    /**
     * @var string Command Description
     */
    protected $description = '取得 Telegram 使用者詳細資料';

    /**
     * @inheritdoc
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function handle()
    {
        $msg = $this->getUpdate()->getMessage();

        $userid = $msg->from->id;

        $username = $msg->from->username;

        $first_name = $msg->from->firstName;

        $chatid = $msg->chat->id;

        $this->replyWithChatAction(['action' => Actions::TYPING]);
        $this->replyWithMessage([
            'text' => '您是 ' . $first_name . PHP_EOL .
                '您所設定的 username 為 @' . $username . PHP_EOL .
                '您的 Telegram user ID 為 ' . $userid . PHP_EOL .
                '目前所在的頻道 ID 為 ' . $chatid,
            'reply_to_message_id' => $msg->messageId
        ]);

        return response('OK', 200);
    }
}
