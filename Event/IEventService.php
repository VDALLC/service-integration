<?php
namespace Vda\ServiceIntegration\Event;

use Vda\Messaging\Message;

interface IEventService
{
    /**
     * @param Event $event
     * @param bool $suppressExceptions
     * @throws \RuntimeException
     */
    public function publish(Event $event, $suppressExceptions = false);

    /**
     * @param Task $task
     * @param bool $suppressExceptions
     * @throws \RuntimeException
     */
    public function enqueue(Task $task, $suppressExceptions = false);

    /**
     * @param ListenerConfig $listenerConfig
     * @return Listener
     */
    public function createListener(ListenerConfig $listenerConfig);

    /**
     * @param Message $message
     * @return BaseEvent
     */
    public function extractEventFromMessage(Message $message);
}
