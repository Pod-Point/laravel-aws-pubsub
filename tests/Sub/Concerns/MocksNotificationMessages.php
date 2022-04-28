<?php

namespace PodPoint\AwsPubSub\Tests\Sub\Concerns;

trait MocksNotificationMessages
{
    private function mockedRichNotificationMessage(array $attributes = []): \Aws\Result
    {
        $attributes = array_merge([
            'Type' => 'Notification',
            'TopicArn' => $this->faker->word,
            'Message' => json_encode(['foo' => 'bar']),
            'MessageId' => $this->faker->uuid,
        ], $attributes);

        return new \Aws\Result([
            'Messages' => [
                ['Body' => json_encode(array_filter($attributes))],
            ],
        ]);
    }

    private function mockedRawNotificationMessage(): \Aws\Result
    {
        return new \Aws\Result([
            'Messages' => [
                ['Body' => json_encode(['foo' => 'bar'])],
            ],
        ]);
    }

    private function mockedEmptyNotificationMessage(): \Aws\Result
    {
        return new \Aws\Result(['Messages' => null]);
    }

    private function mockedEmptyRichNotificationMessage(): \Aws\Result
    {
        return $this->mockedRichNotificationMessage(['Message' => null]);
    }
}
