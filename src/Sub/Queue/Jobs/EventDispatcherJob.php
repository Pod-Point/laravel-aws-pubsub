<?php

namespace PodPoint\AwsPubSub\Sub\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\SqsJob;
use PodPoint\AwsPubSub\Sub\EventDispatchers\EventDispatcher;

class EventDispatcherJob extends SqsJob implements JobContract
{
    /** @var EventDispatcher */
    private $eventDispatcher;

    public function __construct(
        Container $container,
        SqsClient $sqs,
        array $job,
        $connectionName,
        $queue,
        EventDispatcher $eventDispatcher
    ) {
        parent::__construct($container, $sqs, $job, $connectionName, $queue);

        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @inheritDoc
     */
    public function fire()
    {
        $this->eventDispatcher->dispatch($this, $this->container->make(Dispatcher::class));
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
        return self::class;
    }
}
