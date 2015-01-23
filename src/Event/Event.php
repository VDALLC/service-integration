<?php
namespace Vda\ServiceIntegration\Event;

final class Event extends AbstractEvent
{
    public function getChannelType()
    {
        return self::CHANNEL_TYPE_EVENT;
    }
}
