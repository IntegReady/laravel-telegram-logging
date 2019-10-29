<?php

namespace Logger;

use DateTime;
use Exception;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Class TelegramHandler
 * @package App\Logging
 */
class TelegramHandler extends AbstractProcessingHandler
{
    /**
     * Bot API token
     *
     * @var string
     */
    private $botToken;

    /**
     * Chat id for bot
     *
     * @var int
     */
    private $chatId;

    /**
     * Application name
     *
     * @string
     */
    private $appName;

    /**
     * Application environment
     *
     * @string
     */
    private $appEnv;

    /**
     * TelegramHandler constructor.
     * @param int $level
     */
    public function __construct($level)
    {
        $level = Logger::toMonologLevel($level);

        parent::__construct($level, true);

        // define variables for making Telegram request
        $this->botToken = config('telegram-logger.token');
        $this->chatId   = config('telegram-logger.chat_id');

        // define variables for text message
        $this->appName = config('app.name');
        $this->appEnv  = config('app.env');
    }

    /**
     * @param array $record
     */
    public function write(array $record)
    {
        if(!$this->botToken || !$this->chatId) {
            return;
        }


        dd($record['context']['exception']->getTrace());

        // trying to make request and send notification
        try {
            file_get_contents(
                'https://api.telegram.org/bot' . $this->botToken . '/sendMessage?'
                . http_build_query([
                    'text' => $this->formatText($record),
                    'chat_id' => $this->chatId,
                    'parse_mode' => 'html'
                ])
            );
        } catch (Exception $exception) {

        }
    }

    /**
     * @param string $text
     * @param string $level
     * @return string
     */
    private function formatText(array $record): string
    {
        try {
            $dateTime = $record['datetime']->format('Y-m-d H:i:s');
            $exLevel = $record['level_name'];
            $textError = $record['message'];
            $exception = $record['context']['exception'];
            $fileName = $exception->file;
            $fileLine = $exception->line;
            $trace = $exception->getTrace();

            $message = '';
            $message .= "[{$dateTime}]" . PHP_EOL;
            $message .= "<b>{$this->appName}</b> ({$exLevel})" . PHP_EOL;
            $message .= "Environment: {$this->appEnv}" . PHP_EOL . PHP_EOL;
            $message .= "Message: {$textError}" . PHP_EOL;
            $message .= "File: {$fileName}:{$fileLine}" . PHP_EOL . PHP_EOL;
            $message .= "Trace: " . PHP_EOL;
        } catch (Exception $ex) {
            $message = "Unable to get formatted error due to error: {$ex->getMessage()}" . PHP_EOL . PHP_EOL;
            $message .= json_encode($record);

        }
        
        return $message;
    }
}
