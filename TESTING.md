# 🧪 Testing Guide

Comprehensive testing guide for Pixel Manager package.

## 📋 Test Coverage Goals

| Layer | Target Coverage | Current Status |
|-------|----------------|----------------|
| **Domain** | 100% | 🚧 In Progress |
| **Application** | 90%+ | 🚧 In Progress |
| **Infrastructure** | 85%+ | 🚧 In Progress |
| **Overall** | 90%+ | 🚧 In Progress |

---

## 🏗️ Test Structure

```
tests/
├── TestCase.php                    # Base test case
├── Unit/                          # Unit tests
│   ├── Domain/
│   │   ├── ValueObjects/         # Value Object tests
│   │   ├── Entities/             # Entity tests
│   │   └── Services/             # Domain service tests
│   └── Application/
│       ├── Services/             # Application service tests
│       └── UseCases/             # Use case tests
├── Integration/                   # Integration tests
│   ├── Repositories/             # Repository tests
│   └── PlatformAdapters/         # Platform adapter tests
└── Feature/                       # End-to-end tests
    └── TrackingTest.php          # Full tracking flow tests
```

---

## 🚀 Running Tests

### Run All Tests

```bash
composer test

# Or using PHPUnit directly
./vendor/bin/phpunit
```

### Run Specific Test Suite

```bash
# Unit tests only
./vendor/bin/phpunit --testsuite=Unit

# Integration tests only
./vendor/bin/phpunit --testsuite=Integration

# Feature tests only
./vendor/bin/phpunit --testsuite=Feature
```

### Run Specific Test File

```bash
./vendor/bin/phpunit tests/Unit/Domain/ValueObjects/EmailTest.php
```

### Run with Coverage

```bash
composer test-coverage

# Or
./vendor/bin/phpunit --coverage-html coverage
```

Then open `coverage/index.html` in your browser.

---

## ✅ Writing Tests

### Unit Test Example (Value Object)

```php
<?php

namespace MehdiyevSignal\PixelManager\Tests\Unit\Domain\ValueObjects;

use MehdiyevSignal\PixelManager\Domain\ValueObjects\Email;
use MehdiyevSignal\PixelManager\Tests\TestCase;

final class EmailTest extends TestCase
{
    public function test_can_create_valid_email(): void
    {
        $email = new Email('test@example.com');

        $this->assertEquals('test@example.com', $email->value());
    }

    public function test_throws_exception_for_invalid_email(): void
    {
        $this->expectException(InvalidEmailException::class);

        new Email('invalid-email');
    }
}
```

### Integration Test Example (Repository)

```php
<?php

namespace MehdiyevSignal\PixelManager\Tests\Integration\Repositories;

use MehdiyevSignal\PixelManager\Tests\TestCase;

final class MongoDBCredentialsRepositoryTest extends TestCase
{
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test database connection
        $this->repository = new MongoDBCredentialsRepository(...);
    }

    public function test_can_find_credentials_by_app_id(): void
    {
        // Arrange
        $this->seedTestCredentials();

        // Act
        $credentials = $this->repository->findByApplicationId(40);

        // Assert
        $this->assertNotNull($credentials);
        $this->assertEquals(40, $credentials->getAppId());
    }
}
```

### Feature Test Example (End-to-End)

```php
<?php

namespace MehdiyevSignal\PixelManager\Tests\Feature;

use MehdiyevSignal\PixelManager\Presentation\Facades\PixelManager;
use MehdiyevSignal\PixelManager\Tests\TestCase;

final class TrackingTest extends TestCase
{
    public function test_can_track_purchase_event(): void
    {
        // Arrange
        $this->mockPlatformAdapters();

        // Act
        PixelManager::track([
            'data' => [
                'event_type' => 'purchase',
                'value' => 99.99,
                'currency' => 'USD',
            ]
        ]);

        // Assert
        $this->assertEventWasSent('meta', 'purchase');
        $this->assertEventWasSent('google', 'purchase');
    }
}
```

---

## 🎯 Test Categories

### 1. Unit Tests

**What to Test:**
- Value Objects (Email, Money, Phone, etc.)
- Entities (PixelEvent, ApplicationCredentials)
- Domain Services (BotDetector, etc.)
- Application Services (EventFactory, PlatformSelector)

**Characteristics:**
- Fast (no external dependencies)
- Isolated (mock all dependencies)
- Test single responsibility
- 100% code coverage goal

**Example:**
```php
public function test_email_validates_format(): void
{
    $this->expectException(InvalidEmailException::class);

    new Email('not-an-email');
}
```

### 2. Integration Tests

**What to Test:**
- Repository implementations
- Platform adapters
- HTTP clients
- Database queries
- Cache operations

**Characteristics:**
- Medium speed (with external dependencies)
- Test component integration
- May use test database
- 85%+ coverage goal

**Example:**
```php
public function test_repository_saves_and_retrieves_credentials(): void
{
    $credentials = new ApplicationCredentials(...);

    $this->repository->save($credentials);
    $retrieved = $this->repository->findByApplicationId(40);

    $this->assertEquals($credentials, $retrieved);
}
```

### 3. Feature Tests

**What to Test:**
- Complete user workflows
- End-to-end scenarios
- Real usage patterns

**Characteristics:**
- Slower (full stack)
- Test complete flows
- Verify system behavior

**Example:**
```php
public function test_complete_purchase_tracking_flow(): void
{
    // User makes purchase
    $order = $this->createTestOrder();

    // Track event
    PixelManager::track($order->toPixelData());

    // Verify all platforms received event
    $this->assertAllPlatformsReceived($order);
}
```

---

## 🛠️ Testing Tools

### PHPUnit

Main testing framework:

```bash
# Install
composer require --dev phpunit/phpunit

# Configure
phpunit.xml
```

### Mockery

For mocking dependencies:

```php
use Mockery;

$mock = Mockery::mock(CredentialsRepositoryInterface::class);
$mock->shouldReceive('findByApplicationId')
     ->with(40)
     ->andReturn($credentials);
```

### Faker (Optional)

For generating test data:

```bash
composer require --dev fakerphp/faker
```

---

## 📊 Test Coverage Priorities

### High Priority (Must Test)

1. **Value Objects** - Foundation of domain
   - Email validation and hashing
   - Money calculations
   - Phone validation
   - Currency operations

2. **Entities** - Core business logic
   - PixelEvent creation and validation
   - ApplicationCredentials management

3. **Platform Adapters** - External integrations
   - Payload building
   - Event mapping
   - Error handling

4. **Use Cases** - Application logic
   - TrackPixelEventHandler
   - EventFactory
   - PlatformSelector

### Medium Priority

1. **Repositories** - Data access
2. **Decorators** - Cross-cutting concerns
3. **Services** - Business logic

### Lower Priority

1. **Service Provider** - Framework integration
2. **Config** - Configuration
3. **Facades** - Convenience layer

---

## 🎨 Best Practices

### 1. Test Naming

```php
// ✅ Good - describes what it tests
public function test_email_throws_exception_for_invalid_format(): void

// ❌ Bad - unclear what it tests
public function test_email(): void
```

### 2. Arrange-Act-Assert

```php
public function test_money_can_be_added(): void
{
    // Arrange
    $money1 = new Money(50, Currency::USD);
    $money2 = new Money(30, Currency::USD);

    // Act
    $result = $money1->add($money2);

    // Assert
    $this->assertEquals(80, $result->amount);
}
```

### 3. Test One Thing

```php
// ✅ Good - tests one behavior
public function test_email_normalizes_before_hashing(): void
{
    $email = new Email('  TEST@EXAMPLE.COM  ');

    $this->assertEquals('test@example.com', $email->normalized());
}

// ❌ Bad - tests multiple behaviors
public function test_email_everything(): void
{
    // Tests validation, normalization, hashing, etc.
}
```

### 4. Use Data Providers

```php
/**
 * @dataProvider invalidEmailProvider
 */
public function test_rejects_invalid_emails(string $invalid): void
{
    $this->expectException(InvalidEmailException::class);

    new Email($invalid);
}

public function invalidEmailProvider(): array
{
    return [
        ['not-an-email'],
        ['@example.com'],
        ['test@'],
        [''],
    ];
}
```

### 5. Mock External Dependencies

```php
public function test_sends_event_to_platform(): void
{
    // Mock HTTP client
    $httpClient = Mockery::mock(HttpClient::class);
    $httpClient->shouldReceive('post')
               ->once()
               ->andReturn($response);

    $adapter = new MetaPlatformAdapter($httpClient);

    $adapter->sendEvent($event, $credentials);
}
```

---

## 🚀 Test-Driven Development (TDD)

### Red-Green-Refactor Cycle

1. **Red** - Write failing test
```php
public function test_can_track_purchase(): void
{
    $result = PixelManager::track(['event_type' => 'purchase']);

    $this->assertTrue($result->isSuccess());
}
// ❌ Fails - method doesn't exist yet
```

2. **Green** - Make it pass
```php
public function track(array $data): Result
{
    return new Result(true);
}
// ✅ Passes - minimal implementation
```

3. **Refactor** - Improve code
```php
public function track(array $data): Result
{
    $event = $this->factory->create($data);
    $this->distributor->distribute($event);

    return new Result(true);
}
// ✅ Still passes - better implementation
```

---

## 📈 Current Test Status

### Completed Tests

- ✅ EmailTest - 10 test cases
- ✅ MoneyTest - 15 test cases
- ✅ CurrencyTest - 7 test cases

### In Progress

- 🚧 PhoneTest
- 🚧 PixelEventTest
- 🚧 EventFactoryTest

### Planned

- 📝 Platform adapter tests (Meta, Google, TikTok, etc.)
- 📝 Repository tests
- 📝 Use case tests
- 📝 Feature tests

---

## 🎯 Continuous Integration

### GitHub Actions (Recommended)

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: mongodb

      - name: Install Dependencies
        run: composer install

      - name: Run Tests
        run: composer test

      - name: Upload Coverage
        run: bash <(curl -s https://codecov.io/bash)
```

---

## 📚 Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Mockery Documentation](http://docs.mockery.io/)
- [Testing Best Practices](https://martinfowler.com/articles/practical-test-pyramid.html)

---

## 🆘 Troubleshooting

### Tests Failing Due to Missing MongoDB

Install MongoDB extension:

```bash
pecl install mongodb
```

Or use SQLite for tests:

```php
// In phpunit.xml
<env name="PIXEL_MANAGER_DRIVER" value="sql"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Memory Limit Issues

Increase memory limit in `phpunit.xml`:

```xml
<php>
    <ini name="memory_limit" value="512M"/>
</php>
```

---

**Let's achieve 90%+ test coverage for production readiness!** 🎯
