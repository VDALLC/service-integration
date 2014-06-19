<?php
namespace Vda\ServiceIntegration\Event;

use Vda\Messaging\Message;

interface IEventService
{
    /**
     * @param Event $event
     * @param bool $suppressExceptions   If true, don't throw exception, only log failure.
     * @throws \RuntimeException         If event sending failed.
     */
    public function publish(Event $event, $suppressExceptions = false);

    /**
     * @param Task $task
     * @param bool $suppressExceptions   If true, don't throw exception, only log failure.
     * @throws \RuntimeException         If event sending failed.
     */
    public function enqueue(Task $task, $suppressExceptions = false);

    /**
     * @param ListenerConfig $listenerConfig
     * @return IEventListener
     */
    public function createListener(ListenerConfig $listenerConfig);

    /**
     * @param Message $message
     * @return BaseEvent
     */
    public function extractEventFromMessage(Message $message);
}
