<?php

namespace MOLiBot\Services;

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
     * @param Message $hook
     * @return bool
     */
    public function sendWelcomeMsg(Message $hook)
    {
        $status = false;

        try {
            $chatId = $hook->getChat()->getId();
            $memberIsBot = $hook->getNewChatMember()->getIsBot();

            if ($chatId === $this->MOLiGroupId && !$memberIsBot) {
                $welcomeMsg = $this->telegram->sendMessage([
                    'chat_id' => $this->MOLiGroupId,
                    'reply_to_message_id' => $hook->getMessageId(),
                    'disable_web_page_preview' => true,
                    'text' => $this->MOLiWelcomeMsg
                ]);

                $newChatMemberId = $hook->getNewChatMember()->getId();
                $welcomeMsgId = $welcomeMsg->getMessageId();

                $joinTimestamp = time();
                $checked = false;

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
     * @param Message $message
     * @return bool
     */
    public function continuousCommand(Message $message)
    {
        $status = false;

        try {
            $userId = $message->getFrom()->getId();
            $cmdInUse = $this->whoUseWhatCommandRepository->getCommand($userId);

            if (!empty($cmdInUse)) {
                $command = $cmdInUse->command;
                $arguments = '';

                if ($message->getText() != '/' . $command) {
                    $arguments =$message->getText();
                }

                $this->telegram->getCommandBus()->execute($command, $arguments, $message);
            }

            $status = true;
            return $status;
        } catch (\Exception $e) {
            \Log::error($e);
            return $status;
        }
    }
}