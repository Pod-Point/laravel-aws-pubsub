<?php

namespace PodPoint\AwsPubSub\Sub\Queue;

class ValidationResult
{
    private $result;
    private $message;

    public function __construct(bool $result, string $message = '')
    {
        $this->result = $result;
        $this->message = $message;
    }

    public function result(): bool
    {
        return $this->result;
    }

    public function message(): string
    {
        return $this->message;
    }
}
