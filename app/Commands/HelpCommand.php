<?php

namespace MOLiBot\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use MOLiBot\Traits\GetHelpTrait;

class HelpCommand extends Command
{
    use GetHelpTrait;

    /**
     * @var string Command Name
     */
    protected $name = 'help';

    /**
     * @var array Command Aliases
     */
    // protected $aliases = ['help-command'];

    /**
     * @var string Command Description
     */
    protected $description = '列出可用指令';

    /**
     * @inheritdoc
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function handle()
    {
        $text = $this->helptext();

        $this->replyWithChatAction(['action' => Actions::TYPING]);
        $this->replyWithMessage(compact('text'));

        return response('OK', 200);
    }
}
