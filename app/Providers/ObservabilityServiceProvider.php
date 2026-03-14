<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\Exporter\OTLP\SpanExporter;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SemConv\ResourceAttributes;
use OpenTelemetry\SDK\Common\Attribute\Attributes;

class ObservabilityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(TracerInterface::class, function ($app) {
            $resource = ResourceInfoFactory::defaultResource()->merge(
                ResourceInfo::create(Attributes::create([
                    ResourceAttributes::SERVICE_NAME => config('app.name', 'Laravel'),
                    ResourceAttributes::SERVICE_VERSION => '1.0.0',
                    'deployment.environment' => config('app.env', 'production'),
                ]))
            );

            // Tempo OTLP HTTP endpoint
            $exporter = new SpanExporter('http://tempo:4318/v1/traces');
            
            $spanProcessor = new SimpleSpanProcessor($exporter);
            
            $tracerProvider = new TracerProvider(
                $spanProcessor,
                null,
                $resource
            );

            return $tracerProvider->getTracer('laravel-tracer');
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
