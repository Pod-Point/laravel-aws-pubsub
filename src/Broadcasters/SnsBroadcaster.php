<?php

namespace PodPoint\SnsBroadcaster\Broadcasters;

use Aws\Sns\SnsClient;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
    public function __construct(string $arnPrefix, string $arnSuffix)
    {
        $this->snsClient = app(SnsClient::class);
        $this->arnPrefix = $arnPrefix;
        $this->arnSuffix = $arnSuffix;
    }

    /**
     * @inheritDoc
     * @param array $channels
     * @param string $event
     * @param array $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        foreach ($channels as $channel) {
            $this->snsClient->publish([
                'TopicArn' => $this->formatTopic($channel),
                'Message' => json_encode(Arr::except($payload, 'socket')),
                'Subject' => $this->formatSubject($channel, $event, $payload),
            ]);
        }
    }

    /**
     * Format the Topic.
     *
     * @param string $channel
     * @return string
     */
    protected function formatTopic(string $channel): string
    {
        return $this->arnPrefix . $channel . $this->arnSuffix;
    }

    /**
     * Format the Subject name.
     *
     * @param string $channel
     * @param string $event
     * @param array $payload
     * @return string
     */
    protected function formatSubject(string $channel, string $event, array $payload): string
    {
        $default = Str::snake(class_basename($event));

        $action = Arr::get($payload, 'action', $default);

        return Str::lower("{$channel}.{$action}");
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
