<?php

namespace App\Service;

use App\Helper\LoggerTrait;
use Nexy\Slack\Client;

class SlackClient
{
    use LoggerTrait;

    public function __construct(private readonly Client $slack)
    {
    }

    public function sendMessage(string $from, string $message)
    {
        $this->logInfo('Beaming a message to Slack!', [
            'message' => $message
        ]);

        $message = $this->slack->createMessage()
            ->from($from)
            ->withIcon(':ghost:')
            ->setText($message);
        $this->slack->sendMessage($message);
    }
}
