<?php

namespace MOLiBot\Services;

use MOLiBot\Jobs\DeleteWelcomeMessageJob;
use MOLiBot\Repositories\WelcomeMessageRecordRepository;
use MOLiBot\Repositories\WhoUseWhatCommandRepository;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;

class TelegramService
{
    /** @var Api */
    protected $telegram;

    /**
     * @var string
     */
    private $MOLiWelcomeMsg;

    /**
     * @var int
     */
    private $MOLiGroupId;

    /**
     * @var WelcomeMessageRecordRepository
     */
    private $welcomeMessageRecordRepository;

    /**
     * @var WhoUseWhatCommandRepository
     */
    private $whoUseWhatCommandRepository;

    /**
     * TelegramService constructor.
     *
     * @param Api $telegram
     * @param WelcomeMessageRecordRepository $welcomeMessageRecordRepository
     * @param WhoUseWhatCommandRepository $whoUseWhatCommandRepository
     */
    public function __construct(Api $telegram,
                                WelcomeMessageRecordRepository $welcomeMessageRecordRepository,
                                WhoUseWhatCommandRepository $whoUseWhatCommandRepository)
    {
        $this->telegram = $telegram;
        $this->welcomeMessageRecordRepository = $welcomeMessageRecordRepository;
        $this->whoUseWhatCommandRepository = $whoUseWhatCommandRepository;
        $this->MOLiGroupId = config('moli.telegram.group_id');
        $this->MOLiWelcomeMsg = config('moli.telegram.group_welcome_msg');
    }

    /**
     * @param $chatId
     * @param string $text
     * @param null $parseMode
     * @param bool $disableWebPagePreview
     * @param bool $disableNotification
     * @param null $replyToMessageId
     * @param null $replyMarkup
     * @return Message
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function sendMessage($chatId,
                                $text = '',
                                $parseMode = null,
                                $disableWebPagePreview = false,
                                $disableNotification = false,
                                $replyToMessageId = null,
                                $replyMarkup = null)
    {
        return $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => $disableWebPagePreview,
            'disable_notification' => $disableNotification,
            'reply_to_message_id' => $replyToMessageId,
            'reply_markup' => $replyMarkup
        ]);
    }

    /**
     * @param Message $hook
     * @return bool
     */
    public function sendWelcomeMsg(Message $hook)
    {
        $status = false;

        try {
            $chatId = $hook->chat->id;
            $memberIsBot = $hook->newChatMembers[0]->isBot;

            if (strval($chatId) === strval($this->MOLiGroupId) && !$memberIsBot) {
                $welcomeMsg = $this->sendMessage(
                    $this->MOLiGroupId,
                    $this->MOLiWelcomeMsg,
                    null,
                    true,
                    false,
                    $hook->messageId
                );

                $newChatMemberId = $hook->newChatMembers[0]->id;
                $welcomeMsgId = $welcomeMsg->messageId;

                $joinTimestamp = time();
                $checked = false;

                DeleteWelcomeMessageJob::dispatch($chatId, $newChatMemberId, $welcomeMsgId)
                    ->delay(now()->addSeconds(80));

                $this->welcomeMessageRecordRepository->createRecord(
                    $chatId, $newChatMemberId, $welcomeMsgId, $joinTimestamp, $checked
                );
            }

            $status = true;
            return $status;
        } catch (\Exception $e) {
            \Log::error($e);
            return $status;
        }
    }

    /**
     * @param Update $update
     * @return bool
     */
    public function continuousCommand(Update $update)
    {
        $status = false;

        try {
            $message = $update->getMessage();
            $userId = $message->from->id;
            $cmdInUse = $this->whoUseWhatCommandRepository->getCommand($userId);

            if (!empty($cmdInUse)) {
                $command = $cmdInUse->command;
                $arguments = '';

                if ($message->text != '/' . $command) {
                    $arguments = $message->text;
                }

                $this->telegram->triggerCommand($command, $update, $arguments);
            }

            $status = true;
            return $status;
        } catch (\Exception $e) {
            \Log::error($e);
            return $status;
        }
    }
}
