<?php
namespace Vda\ServiceIntegration\Event;


interface IEventListener
{
    /**
     * @param $timeout
     * @throws Exception\ReceivingFailedException
     * @return AbstractEvent
     */
    public function receive($timeout = -1);

    /**
     * @param AbstractEvent $baseEvent
     * @throws Exception\AckFailedException
     */
    public function ack(AbstractEvent $baseEvent);
}
