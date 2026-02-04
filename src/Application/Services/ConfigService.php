<?php

declare(strict_types=1);

namespace MehdiyevSignal\PixelManager\Application\Services;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\EventType;
use MehdiyevSignal\PixelManager\Domain\ValueObjects\PlatformType;

/**
 * Configuration service.
 *
 * Provides access to application configuration.
 */
final class ConfigService
{
    public function __construct(
        private readonly array $config
    ) {
    }

    /**
     * Get application ID.
     *
     * @return int
     */
    public function getAppId(): int
    {
        return (int) $this->config['app_id'];
    }

    /**
     * Get MongoDB connection name.
     *
     * @return string
     */
    public function getDbConnection(): string
    {
        return $this->config['connection'] ?? 'mongodb';
    }

    /**
     * Get event collection name.
     *
     * @return string
     */
    public function getEventCollection(): string
    {
        return $this->config['collection'] ?? 'mp_customer_event';
    }

    /**
     * Get applications collection name.
     *
     * @return string
     */
    public function getApplicationsCollection(): string
    {
        return $this->config['applications_collection'] ?? 'applications';
    }

    /**
     * Get queue connection name.
     *
     * @return string
     */
    public function getQueue(): string
    {
        return $this->config['queue'] ?? 'default';
    }

    /**
     * Check if event logging is enabled.
     *
     * @return bool
     */
    public function isLoggingEnabled(): bool
    {
        return (bool) ($this->config['logging'] ?? true);
    }

    /**
     * Check if caching is enabled.
     *
     * @return bool
     */
    public function isCachingEnabled(): bool
    {
        return (bool) ($this->config['cache']['enabled'] ?? true);
    }

    /**
     * Get cache TTL in seconds.
     *
     * @return int
     */
    public function getCacheTtl(): int
    {
        return (int) ($this->config['cache']['ttl'] ?? 3600);
    }

    /**
     * Check if retry is enabled.
     *
     * @return bool
     */
    public function isRetryEnabled(): bool
    {
        return (bool) ($this->config['retry']['enabled'] ?? true);
    }

    /**
     * Get max retry attempts.
     *
     * @return int
     */
    public function getRetryMaxAttempts(): int
    {
        return (int) ($this->config['retry']['max_attempts'] ?? 3);
    }

    /**
     * Check if circuit breaker is enabled.
     *
     * @return bool
     */
    public function isCircuitBreakerEnabled(): bool
    {
        return (bool) ($this->config['circuit_breaker']['enabled'] ?? true);
    }

    /**
     * Get circuit breaker failure threshold.
     *
     * @return int
     */
    public function getCircuitBreakerThreshold(): int
    {
        return (int) ($this->config['circuit_breaker']['failure_threshold'] ?? 5);
    }

    /**
     * Check if rate limiting is enabled.
     *
     * @return bool
     */
    public function isRateLimitingEnabled(): bool
    {
        return (bool) ($this->config['rate_limiting']['enabled'] ?? true);
    }

    /**
     * Check if bot detection is enabled.
     *
     * @return bool
     */
    public function isBotDetectionEnabled(): bool
    {
        return (bool) ($this->config['security']['bot_detection_enabled'] ?? true);
    }

    /**
     * Check if credential encryption is enabled.
     *
     * @return bool
     */
    public function isCredentialEncryptionEnabled(): bool
    {
        return (bool) ($this->config['security']['encrypt_credentials'] ?? true);
    }

    /**
     * Get event mappings for a specific event type.
     *
     * @param EventType $eventType
     * @return array<PlatformType>
     */
    public function getEventMappings(EventType $eventType): array
    {
        $mappings = $this->config['event_mappings'][$eventType->value] ?? [];

        return array_map(
            fn($platform) => PlatformType::from($platform),
            array_filter($mappings, fn($p) => PlatformType::tryFrom($p) !== null)
        );
    }

    /**
     * Get all event mappings.
     *
     * @return array
     */
    public function getAllEventMappings(): array
    {
        return $this->config['event_mappings'] ?? [];
    }

    /**
     * Get platform configuration.
     *
     * @param PlatformType $platform
     * @return array|null
     */
    public function getPlatformConfig(PlatformType $platform): ?array
    {
        return $this->config['platforms'][$platform->value] ?? null;
    }

    /**
     * Get all platform configurations.
     *
     * @return array
     */
    public function getAllPlatformConfigs(): array
    {
        return $this->config['platforms'] ?? [];
    }
}
