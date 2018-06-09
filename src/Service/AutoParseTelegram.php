<?php declare(strict_types=1);

namespace App\Service;

use TelegramBot\Api\BotApi;

/**
 * Class AutoParseTelegram
 * @package App\Service
 */
class AutoParseTelegram
{
    /**
     * @var BotApi $client
     */
    private $client;
    private $chatId;

    /**
     * AutoParseTelegram constructor.
     * @param BotApi $client
     * @param int $chatId
     */
    public function __construct(BotApi $client, int $chatId)
    {
        $this->client = $client;
        $this->chatId = $chatId;
    }

    /**
     * @param string $message
     */
    public function sendMessage(string $message): void
    {
        try {
            $this->client->sendMessage($this->chatId, $message);
        } catch (\TelegramBot\Api\Exception $e) {
            exit($e->getMessage()); // TODO: Will add monolog
        }
    }
}