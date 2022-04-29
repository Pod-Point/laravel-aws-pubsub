<?php

namespace PodPoint\AwsPubSub\Sub\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\SqsJob;
use PodPoint\AwsPubSub\Sub\Queue\EventResolvers\Event;
use PodPoint\AwsPubSub\Sub\Queue\EventResolvers\EventResolver;

class EventDispatcherJob extends SqsJob implements JobContract
{
    /** @var Event */
    private $event;

    public function __construct(
        Container $container,
        SqsClient $sqs,
        array $job,
        $connectionName,
        $queue,
        EventResolver $eventResolver
    ) {
        parent::__construct($container, $sqs, $job, $connectionName, $queue);

        if (! $eventResolver->validate($this)) {
            $eventResolver->failedValidation($this);
            return;
        }

        $this->event = $eventResolver->resolve($this);
    }

    /**
     * @inheritDoc
     */
    public function fire()
    {
        if ($this->event->name()) {
            $this->resolve(Dispatcher::class)->dispatch($this->event->name(), [
                'payload' => $this->event->payload(),
                'name' => $this->event->name(),
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    protected function failed($e)
    {
        // ...
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->event->name();
    }

    /**
     * @inheritDoc
     */
    public function resolveName()
    {
        return $this->getName();
    }
}
