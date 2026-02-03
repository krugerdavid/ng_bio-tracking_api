<?php

namespace App\Observers;

use App\Contracts\AuditableContract;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log('created', $model, null, $this->getAuditAttributes($model->getAttributes(), $model));
    }

    public function updating(Model $model): void
    {
        $this->log(
            'updated',
            $model,
            $this->getAuditAttributes($model->getOriginal(), $model),
            $this->getAuditAttributes($model->getAttributes(), $model)
        );
    }

    public function deleted(Model $model): void
    {
        $this->log('deleted', $model, $this->getAuditAttributes($model->getAttributes(), $model), null);
    }

    protected function log(string $event, Model $model, ?array $oldValues, ?array $newValues): void
    {
        if (! config('audit.enabled', true)) {
            return;
        }

        if (! in_array($event, config('audit.events', ['created', 'updated', 'deleted']), true)) {
            return;
        }

        $payload = [
            'event' => $event,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => (string) $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $this->resolveUserId(),
            'ip_address' => $this->resolveIpAddress(),
            'user_agent' => $this->resolveUserAgent(),
            'url' => $this->resolveUrl(),
            'method' => $this->resolveMethod(),
        ];

        if (config('audit.queue')) {
            dispatch(new \App\Jobs\RecordAuditJob($payload))
                ->onConnection(config('audit.queue_connection'));
        } else {
            $this->createAuditRecord($payload);
        }
    }

    protected function createAuditRecord(array $payload): void
    {
        try {
            AuditLog::query()->create($payload);
        } catch (\Throwable $e) {
            Log::channel('single')->error('Audit log failed: '.$e->getMessage(), [
                'payload' => $payload,
                'exception' => $e,
            ]);
        }
    }

    protected function getAuditAttributes(array $attributes, Model $model): array
    {
        $excluded = $this->excludedAttributes($model);

        return collect($attributes)->except($excluded)->all();
    }

    /**
     * @return array<int, string>
     */
    protected function excludedAttributes(Model $model): array
    {
        $global = config('audit.excluded_attributes', []);

        if ($model instanceof AuditableContract || method_exists($model, 'excludedAuditAttributes')) {
            return array_merge($global, $model->excludedAuditAttributes());
        }

        return $global;
    }

    protected function resolveUserId(): ?int
    {
        $user = auth()->user();

        return $user?->getAuthIdentifier();
    }

    protected function resolveIpAddress(): ?string
    {
        return request()?->ip();
    }

    protected function resolveUserAgent(): ?string
    {
        return request()?->userAgent();
    }

    protected function resolveUrl(): ?string
    {
        return request()?->fullUrl();
    }

    protected function resolveMethod(): ?string
    {
        return request()?->method();
    }
}
