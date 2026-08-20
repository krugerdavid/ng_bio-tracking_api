<?php

namespace App\Console\Commands;

use App\Jobs\NotifyApprovedNeverLoggedInJob;
use Illuminate\Console\Command;

class NotifyApprovedNeverLoggedInCommand extends Command
{
    protected $signature = 'members:notify-never-logged-in
                            {--dry-run : Lista destinatarios sin enviar correos}';

    protected $description = 'Avisa por email a miembros aprobados que todavía no iniciaron sesión';

    public function handle(NotifyApprovedNeverLoggedInJob $job): int
    {
        $recipients = NotifyApprovedNeverLoggedInJob::recipients();

        if ($recipients->isEmpty()) {
            $this->info('No hay miembros aprobados sin login.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Nombre', 'Email'],
            $recipients->map(fn ($user) => [
                $user->id,
                $user->member?->name ?? $user->name,
                $user->email,
            ])
        );

        if ($this->option('dry-run')) {
            $this->comment($recipients->count().' correos se enviarían (dry-run).');

            return self::SUCCESS;
        }

        $sent = $job->handle();
        $this->info("Se encolaron {$sent} correos. Si usás cola, el worker tiene que estar corriendo.");

        return self::SUCCESS;
    }
}
