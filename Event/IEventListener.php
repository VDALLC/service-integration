<?php
namespace Vda\ServiceIntegration\Event;


interface IEventListener
{
    /**
     * @param $timeout
     * @throws Exception\MessageReceivingFailedException
     * @return BaseEvent
     */
    public function receive($timeout = -1);

    /**
     * @param BaseEvent $baseEvent
     * @throws Exception\MessageAckFailedException
     */
    public function ack(BaseEvent $baseEvent);
}
