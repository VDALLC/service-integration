<?php
namespace Vda\ServiceIntegration\Event;


interface IEventListener
{
    /**
     * @param $timeout
     * @throws Exception\MessageReceivingFailedException
     * @return AbstractEvent
     */
    public function receive($timeout = -1);

    /**
     * @param AbstractEvent $baseEvent
     * @throws Exception\MessageAckFailedException
     */
    public function ack(AbstractEvent $baseEvent);
}
