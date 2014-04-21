<?php
namespace Vda\ServiceIntegration\Event;

interface IEventService
{
    /**
     * @param Event $event
     * @param bool $suppressExceptions   If true, don't throw exception, only log failure.
     * @throws \RuntimeException         If event sending failed.
     */
    public function publish(Event $event, $suppressExceptions = false);
}
