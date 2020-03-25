<?php

namespace Virtue\Api\Testing;

use Psr\Log\LoggerInterface;

class LoggerStub implements LoggerInterface
{
    private $logs = [];

    public function emergency($message, array $context = array())
    {
        $this->log('emergency', $message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->log('critical', $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->log('error', $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->log('warning', $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->log('notice', $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->log('info', $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        $this->logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }

    public function contains($level, $message): int
    {
        return count(
            array_filter(
                $this->logs,
                function (array $entry) use ($level, $message) {
                    return $entry['level'] === $level && $entry['message'] === $message;
                }
            )
        );
    }
}
