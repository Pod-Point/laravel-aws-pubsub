<?php

namespace PodPoint\AwsPubSub\Pub\Broadcasting\Broadcasters;

use Aws\Sns\SnsClient;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;

class SnsBroadcaster extends Broadcaster
{
    /**
     * @var SnsClient
     */
    protected $snsClient;

    /**
     * @var string
     */
    protected $arnPrefix;

    /**
     * @var string
     */
    protected $arnSuffix;

    /**
     * SnsBroadcaster constructor.
     *
     * @param  SnsClient  $snsClient
     * @param  string  $arnPrefix
     * @param  string  $arnSuffix
     */
    public function __construct(SnsClient $snsClient, string $arnPrefix = '', string $arnSuffix = '')
    {
        $this->snsClient = $snsClient;
        $this->arnPrefix = $arnPrefix;
        $this->arnSuffix = $arnSuffix;
    }

    /**
     * @inheritDoc
     */
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        foreach ($channels as $channel) {
            $this->snsClient->publish([
                'TopicArn' => "{$this->arnPrefix}{$channel}{$this->arnSuffix}",
                'Message' => json_encode($payload),
                'Subject' => $event,
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function auth($request)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function validAuthenticationResponse($request, $result)
    {
        //
    }
}
