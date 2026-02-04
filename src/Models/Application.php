<?php

namespace SalehSignal\PixelManager\Models;

use MongoDB\Laravel\Eloquent\Model;

class Application extends Model
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
     * @var array
     */
    protected $fillable = [
        'app_id',
        'category',
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'app_id' => 'integer',
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
        $this->collection = config('pixel-manager.applications_collection', 'applications');
    }
}
