<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $connection = config('pixel-manager.connection', 'mongodb');
        $collection = config('pixel-manager.collection', 'mp_customer_event');

        // Create indexes for the customer events collection
        Schema::connection($connection)->table($collection, function ($collection) {
            $collection->index('event_id');
            $collection->index('event_name');
            $collection->index('created_at');
            $collection->index('destination');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $connection = config('pixel-manager.connection', 'mongodb');
        $collection = config('pixel-manager.collection', 'mp_customer_event');

        Schema::connection($connection)->table($collection, function ($collection) {
            $collection->dropIndex(['event_id' => 1]);
            $collection->dropIndex(['event_name' => 1]);
            $collection->dropIndex(['created_at' => 1]);
            $collection->dropIndex(['destination' => 1]);
        });
    }
};
