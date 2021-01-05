<?php

namespace MOLiBot\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

class MapCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'map';

    /**
     * @var string Command Description
     */
    protected $description = '用地圖告訴你 MOLi 在哪裡';

    /**
     * @inheritdoc
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function handle()
    {
        $this->replyWithChatAction(['action' => Actions::FIND_LOCATION]);

        $this->replyWithMessage(['text' => '我們在這裡呦～']);
        // This will send a message using `sendMessage` method behind the scenes to
        // the user/chat id who triggered this command.
        // `replyWith<Message|Photo|Audio|Video|Voice|Document|Sticker|Location|ChatAction>()` all the available methods are dynamically
        // handled when you replace `send<Method>` with `replyWith` and use the same parameters - except chat_id does NOT need to be included in the array.
        $this->replyWithLocation(['latitude' => 23.9519631, 'longitude' => 120.9274402]);

        return response('OK', 200);
    }
}
