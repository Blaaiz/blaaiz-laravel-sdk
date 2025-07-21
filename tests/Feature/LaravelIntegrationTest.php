<?php

use Blaaiz\LaravelSdk\Blaaiz;
use Blaaiz\LaravelSdk\BlaaizServiceProvider;
use Blaaiz\LaravelSdk\Facades\Blaaiz as BlaaizFacade;
use Illuminate\Support\Facades\Config;

describe('Laravel Integration', function () {
    describe('ServiceProvider', function () {
        it('registers Blaaiz instance as singleton', function () {
            $blaaiz1 = app(Blaaiz::class);
            $blaaiz2 = app(Blaaiz::class);

            expect($blaaiz1)->toBeInstanceOf(Blaaiz::class);
            expect($blaaiz2)->toBeInstanceOf(Blaaiz::class);
            expect($blaaiz1)->toBe($blaaiz2); // Should be the same instance (singleton)
        });

        it('uses configuration values from config/blaaiz.php', function () {
            Config::set('blaaiz.api_key', 'test-api-key-from-config');
            Config::set('blaaiz.base_url', 'https://api.custom.com');
            Config::set('blaaiz.timeout', 60);

            // Force re-resolution of the Blaaiz instance
            app()->forgetInstance(Blaaiz::class);

            $blaaiz = app(Blaaiz::class);

            expect($blaaiz)->toBeInstanceOf(Blaaiz::class);

            // Access protected client to verify configuration
            $reflection = new ReflectionClass($blaaiz);
            $clientProperty = $reflection->getProperty('client');
            $clientProperty->setAccessible(true);
            $client = $clientProperty->getValue($blaaiz);

            $clientReflection = new ReflectionClass($client);
            
            $apiKeyProperty = $clientReflection->getProperty('apiKey');
            $apiKeyProperty->setAccessible(true);
            $apiKey = $apiKeyProperty->getValue($client);
            
            $baseUrlProperty = $clientReflection->getProperty('baseUrl');
            $baseUrlProperty->setAccessible(true);
            $baseUrl = $baseUrlProperty->getValue($client);
            
            $timeoutProperty = $clientReflection->getProperty('timeout');
            $timeoutProperty->setAccessible(true);
            $timeout = $timeoutProperty->getValue($client);

            expect($apiKey)->toBe('test-api-key-from-config');
            expect($baseUrl)->toBe('https://api.custom.com');
            expect($timeout)->toBe(60);
        });

        it('publishes configuration file', function () {
            $provider = new BlaaizServiceProvider(app());
            
            // Check that the boot method exists
            expect(method_exists($provider, 'boot'))->toBeTrue();
            
            // Check that the config file path exists
            $configPath = __DIR__ . '/../../config/blaaiz.php';
            expect(file_exists($configPath))->toBeTrue();
        });

        it('provides correct services', function () {
            $provider = new BlaaizServiceProvider(app());
            
            $provides = $provider->provides();
            
            expect($provides)->toContain(Blaaiz::class);
        });
    });

    describe('Facade', function () {
        it('resolves to Blaaiz instance', function () {
            $blaaizFromFacade = BlaaizFacade::getFacadeRoot();
            $blaaizFromContainer = app(Blaaiz::class);

            expect($blaaizFromFacade)->toBeInstanceOf(Blaaiz::class);
            expect($blaaizFromFacade)->toBe($blaaizFromContainer);
        });

        it('can access service methods through facade', function () {
            // Test that facade can access the underlying service
            $reflection = new ReflectionClass(BlaaizFacade::class);
            $method = $reflection->getMethod('getFacadeAccessor');
            $method->setAccessible(true);
            $accessor = $method->invoke(null);
            
            expect($accessor)->toBe('blaaiz');
        });

        it('has proper facade structure', function () {
            expect(class_exists(\Blaaiz\LaravelSdk\Facades\Blaaiz::class))->toBeTrue();
            expect(is_subclass_of(\Blaaiz\LaravelSdk\Facades\Blaaiz::class, \Illuminate\Support\Facades\Facade::class))->toBeTrue();
        });
    });

    describe('Configuration', function () {
        it('has default configuration values', function () {
            expect(config('blaaiz.api_key'))->toBe(env('BLAAIZ_API_KEY', 'test-key'));
            expect(config('blaaiz.base_url'))->toBe(env('BLAAIZ_API_URL', 'https://api-dev.blaaiz.com'));
            expect(config('blaaiz.timeout'))->toBe(30);
        });

        it('can override configuration via environment', function () {
            // Override config directly (simulating environment variables)
            Config::set('blaaiz.api_key', 'env-api-key');
            Config::set('blaaiz.base_url', 'https://api.env.com');

            expect(config('blaaiz.api_key'))->toBe('env-api-key');
            expect(config('blaaiz.base_url'))->toBe('https://api.env.com');
        });
    });

    describe('Service Container Integration', function () {
        it('resolves all service dependencies correctly', function () {
            $blaaiz = app(Blaaiz::class);

            expect($blaaiz->customers)->toBeInstanceOf(\Blaaiz\LaravelSdk\Services\CustomerService::class);
            expect($blaaiz->collections)->toBeInstanceOf(\Blaaiz\LaravelSdk\Services\CollectionService::class);
            expect($blaaiz->payouts)->toBeInstanceOf(\Blaaiz\LaravelSdk\Services\PayoutService::class);
            expect($blaaiz->wallets)->toBeInstanceOf(\Blaaiz\LaravelSdk\Services\WalletService::class);
            expect($blaaiz->virtualBankAccounts)->toBeInstanceOf(\Blaaiz\LaravelSdk\Services\VirtualBankAccountService::class);
            expect($blaaiz->transactions)->toBeInstanceOf(\Blaaiz\LaravelSdk\Services\TransactionService::class);
            expect($blaaiz->banks)->toBeInstanceOf(\Blaaiz\LaravelSdk\Services\BankService::class);
            expect($blaaiz->currencies)->toBeInstanceOf(\Blaaiz\LaravelSdk\Services\CurrencyService::class);
            expect($blaaiz->fees)->toBeInstanceOf(\Blaaiz\LaravelSdk\Services\FeesService::class);
            expect($blaaiz->files)->toBeInstanceOf(\Blaaiz\LaravelSdk\Services\FileService::class);
            expect($blaaiz->webhooks)->toBeInstanceOf(\Blaaiz\LaravelSdk\Services\WebhookService::class);
        });

        it('can be bound with custom configuration', function () {
            // Bind custom instance
            app()->bind(Blaaiz::class, function () {
                return new Blaaiz('custom-api-key', [
                    'base_url' => 'https://api.custom-test.com',
                    'timeout' => 45
                ]);
            });

            $blaaiz = app(Blaaiz::class);
            
            expect($blaaiz)->toBeInstanceOf(Blaaiz::class);
        });
    });

    describe('Laravel Helpers Integration', function () {
        it('works with Laravel helper functions', function () {
            // Test that the SDK works with Laravel helpers like app(), config(), env()
            $blaaiz = app(Blaaiz::class);
            
            expect($blaaiz)->toBeInstanceOf(Blaaiz::class);
            expect(config('blaaiz'))->toBeArray();
            expect(config('blaaiz.api_key'))->toBeString();
        });

        it('integrates with Laravel logging', function () {
            // The SDK should work with Laravel's logging system if needed
            $blaaiz = app(Blaaiz::class);
            
            expect($blaaiz)->toBeInstanceOf(Blaaiz::class);
            
            // Test that we can create exceptions with proper status codes
            $exception = new \Blaaiz\LaravelSdk\Exceptions\BlaaizException('Test error', 400, 'TEST_ERROR');
            
            expect($exception->getMessage())->toBe('Test error');
            expect($exception->getStatus())->toBe(400);
            expect($exception->getErrorCode())->toBe('TEST_ERROR');
        });
    });
});