<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Persistence\MongoDB\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Customer Event MongoDB Model.
 *
 * Stores tracked pixel events for analytics and debugging.
 */
class CustomerEventModel extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = null;

    /**
     * The collection associated with the model.
     *
     * @var string|null
     */
    protected $collection = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'data',
        'destination',
        'event_name',
        'event_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'destination' => 'array',
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set connection and collection from config
        $this->connection = config('pixel-manager.connection', 'mongodb');
        $this->collection = config('pixel-manager.collection', 'mp_customer_event');
    }
}
