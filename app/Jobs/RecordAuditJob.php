<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecordAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array{event: string, auditable_type: string, auditable_id: string, old_values: ?array, new_values: ?array, user_id: ?int, ip_address: ?string, user_agent: ?string, url: ?string, method: ?string} $payload
     */
    public function __construct(
        private readonly array $payload
    ) {}

    public function handle(): void
    {
        try {
            AuditLog::query()->create($this->payload);
        } catch (\Throwable $e) {
            Log::channel('single')->error('Audit log job failed: '.$e->getMessage(), [
                'payload' => $this->payload,
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
