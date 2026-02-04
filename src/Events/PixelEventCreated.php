<?php

namespace SalehSignal\PixelManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PixelEventCreated
{
    use Dispatchable, SerializesModels;

    /**
     * The event data.
     *
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param  array  $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
