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
     * @param string $arnPrefix
     * @param string $arnSuffix
     */
    public function __construct(string $arnPrefix = '', string $arnSuffix = '')
    {
        $this->snsClient = app(SnsClient::class);
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
