# Changelog

All notable changes to `pixel-manager` will be documented in this file.

## [Unreleased]

## [1.0.0] - 2026-02-04

### Added
- Initial release
- Multi-platform pixel event tracking (Meta, Google, Brevo, TikTok, Pinterest, Snapchat)
- Asynchronous event processing with Laravel queues
- Configurable event-to-platform mapping
- MongoDB event logging
- Comprehensive documentation
- Service Provider with auto-discovery
- Facade for easy usage
- Event and Listener architecture
- Domain-driven design with clear separation of concerns

### Fixed
- Fixed `date_of_birth` typo in BrevoEventAction (was `datee_of_birth`)
- Fixed `zip_code` parameter mismatch in BrevoEventAction (was using `zipcode`)

### Security
- No known security vulnerabilities

## Release Notes

### Version 1.0.0

This is the initial release of the Pixel Manager package for Laravel 11+. The package provides a clean, maintainable solution for tracking customer events across multiple marketing platforms.

**Key Features:**
- Support for 6 major marketing platforms
- Queue-based asynchronous processing
- Flexible configuration system
- MongoDB logging for analytics
- Clean domain-driven architecture
- Comprehensive test coverage
- Well-documented API

**Platform Support:**
- Meta Pixel (Facebook)
- Google Analytics 4
- Brevo (formerly Sendinblue)
- TikTok Pixel
- Pinterest Tag
- Snapchat Pixel

**Requirements:**
- PHP 8.2+
- Laravel 11.0+
- MongoDB PHP Extension

For upgrade instructions and breaking changes, please see the [UPGRADING.md](UPGRADING.md) file.

---

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
