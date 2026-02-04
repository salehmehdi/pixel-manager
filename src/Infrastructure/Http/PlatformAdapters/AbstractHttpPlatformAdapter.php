<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Infrastructure\Http\PlatformAdapters;

use Illuminate\Http\Client\Factory as HttpFactory;
use MehdiyevSignal\PixelManager\Domain\Entities\PixelEvent;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformAdapterInterface;
use MehdiyevSignal\PixelManager\Domain\Services\PlatformResponse;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformCredentialsInterface;
use Psr\Log\LoggerInterface;

/**
 * Abstract base class for HTTP-based platform adapters.
 *
 * Provides common HTTP functionality and error handling.
 * Reduces code duplication from ~2,000 lines to ~300 lines across adapters.
 */
abstract class AbstractHttpPlatformAdapter implements PlatformAdapterInterface
{
    protected const TIMEOUT = 10;
    protected const CONNECT_TIMEOUT = 5;

    public function __construct(
        protected readonly HttpFactory $http,
        protected readonly LoggerInterface $logger
    ) {
    }

    /**
     * Build platform-specific payload.
     *
     * @param PixelEvent $event
     * @param PlatformCredentialsInterface $credentials
     * @return array
     */
    abstract protected function buildPayload(
        PixelEvent $event,
        PlatformCredentialsInterface $credentials
    ): array;

    /**
     * Get endpoint URL for platform API.
     *
     * @param PlatformCredentialsInterface $credentials
     * @return string
     */
    abstract protected function getEndpointUrl(PlatformCredentialsInterface $credentials): string;

    /**
     * Get HTTP headers for request.
     *
     * @param PlatformCredentialsInterface $credentials
     * @return array
     */
    abstract protected function getHeaders(PlatformCredentialsInterface $credentials): array;

    /**
     * Send event to platform.
     *
     * @param PixelEvent $event
     * @param PlatformCredentialsInterface $credentials
     * @return PlatformResponse
     */
    public function sendEvent(PixelEvent $event, PlatformCredentialsInterface $credentials): PlatformResponse
    {
        try {
            $payload = $this->buildPayload($event, $credentials);
            $url = $this->getEndpointUrl($credentials);
            $headers = $this->getHeaders($credentials);

            $response = $this->http
                ->timeout(self::TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->withHeaders($headers)
                ->post($url, $payload);

            if ($response->successful()) {
                return PlatformResponse::success($response->json());
            }

            $error = "HTTP {$response->status()}: {$response->body()}";

            $this->logger->warning('Platform API error', [
                'platform' => $this->getPlatformType()->value,
                'status' => $response->status(),
                'error' => $error,
                'event_id' => $event->getId()->toString(),
            ]);

            return PlatformResponse::failure($error, $response->json());

        } catch (\Exception $e) {
            $this->logger->error('Platform adapter error', [
                'platform' => $this->getPlatformType()->value,
                'error' => $e->getMessage(),
                'event_id' => $event->getId()->toString(),
            ]);

            return PlatformResponse::failure($e->getMessage());
        }
    }
}
