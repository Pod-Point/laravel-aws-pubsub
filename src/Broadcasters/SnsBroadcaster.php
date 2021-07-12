<?php

namespace PodPoint\SnsBroadcaster\Broadcasters;

use Aws\Sns\SnsClient;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Arr;

class SnsBroadcaster extends Broadcaster
{
    /**
     * @var SnsClient
     */
    protected SnsClient $snsClient;

    /**
     * @var string
     */
    protected string $arnPrefix;

    /**
     * SnsBroadcaster constructor.
     *
     * @param string $arnPrefix
     */
    public function __construct(string $arnPrefix)
    {
        $this->snsClient = app(SnsClient::class);
        $this->arnPrefix = $arnPrefix;
    }

    /**
     * @inheritDoc
     * @param  array  $channels
     * @param $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        $this->snsClient->publish([
            'TopicArn' => $this->topicName($channels),
            'Message' => json_encode(Arr::except($payload, 'socket')),
        ]);
    }

    /**
     * Returns topic name built for SNS.
     *
     * @param array $channels
     *
     * @return string
     */
    private function topicName(array $channels): string
    {
        return $this->arnPrefix . Arr::first($channels);
    }

    /**
     * @inheritDoc
     */
    public function auth($request)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function validAuthenticationResponse($request, $result)
    {
        return true;
    }
}
