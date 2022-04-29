<?php

namespace PodPoint\AwsPubSub\Sub\Queue\EventResolvers;

class Event
{
    private $name;
    private $payload;

    public function __construct(string $name, $payload)
    {
        $this->name = $name;
        $this->payload = $payload;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function payload()
    {
        return $this->payload;
    }
}
