<?php
namespace Vda\ServiceIntegration\Event;

final class Task extends AbstractEvent
{
    public function getChannelType()
    {
        return self::CHANNEL_TYPE_TASK;
    }
}
