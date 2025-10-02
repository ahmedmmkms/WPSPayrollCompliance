<?php

namespace App\Providers;

use App\Models\PayrollException;
use App\Observers\PayrollExceptionObserver;
use App\Support\Audit\AuditTrailRecorder;
use App\Support\Mudad\MudadAdapter;
use App\Support\Mudad\MudadClient;
use App\Support\Notifications\ExceptionNotificationQueue;
use App\Support\Sif\SifGenerator;
use App\Support\Sif\SifTemplateRepository;
use App\Support\Validation\BatchValidationManager;
use App\Support\Validation\RuleRepository;
use App\Support\Validation\ValidationExceptionSynchronizer;
use App\Support\Validation\ValidationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RuleRepository::class, static fn () => RuleRepository::fromConfig());
        $this->app->singleton(ValidationService::class, static fn ($app) => new ValidationService($app->make(RuleRepository::class)));
        $this->app->singleton(ValidationExceptionSynchronizer::class, static fn () => new ValidationExceptionSynchronizer);
        $this->app->singleton(AuditTrailRecorder::class, static fn () => new AuditTrailRecorder);
        $this->app->singleton(BatchValidationManager::class, static fn ($app) => new BatchValidationManager(
            $app->make(ValidationService::class),
            $app->make(ValidationExceptionSynchronizer::class),
            $app->make(AuditTrailRecorder::class),
        ));

        $this->app->singleton(SifTemplateRepository::class, static fn () => SifTemplateRepository::fromConfig());
        $this->app->singleton(SifGenerator::class, static fn () => new SifGenerator);

        $this->app->singleton(ExceptionNotificationQueue::class, static fn () => new ExceptionNotificationQueue);

        $this->app->singleton(MudadClient::class, static fn () => MudadClient::fromConfig());
        $this->app->singleton(MudadAdapter::class, static fn ($app) => new MudadAdapter($app->make(MudadClient::class)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        PayrollException::observe(PayrollExceptionObserver::class);
    }
}
