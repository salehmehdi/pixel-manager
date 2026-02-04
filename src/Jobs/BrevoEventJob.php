<?php

namespace SalehSignal\PixelManager\Jobs;

use SalehSignal\PixelManager\Actions\Brevo\BrevoEventAction;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class BrevoEventJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The event data.
     *
     * @var array
     */
    protected $data;

    /**
     * The application configuration.
     *
     * @var array
     */
    protected $application;

    /**
     * Create a new job instance.
     *
     * @param  array  $data
     * @param  array  $application
     * @return void
     */
    public function __construct(array $data, array $application)
    {
        $this->data = $data;
        $this->application = $application;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $brevoEvent = new BrevoEventAction();
        $response = $brevoEvent->execute($this->data, $this->application);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::error('Pixel Manager: Brevo Event Job Failed', [
            'event_type' => $this->data['data']['event_type'] ?? 'unknown',
            'application' => $this->application,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
