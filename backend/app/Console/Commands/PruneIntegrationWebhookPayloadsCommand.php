<?php

namespace App\Console\Commands;

use App\Models\IntegrationWebhookEvent;
use Illuminate\Console\Command;

final class PruneIntegrationWebhookPayloadsCommand extends Command
{
    protected $signature = 'integration-webhooks:prune-payloads
        {--chunk=200 : Quantidade de eventos processada por lote}';

    protected $description = 'Remove payloads originais de webhooks após o período de retenção';

    public function handle(): int
    {
        $chunkSize = filter_var($this->option('chunk'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($chunkSize === false) {
            $this->components->error('A opção --chunk deve ser um número inteiro maior que zero.');

            return self::INVALID;
        }

        $pruned = 0;

        IntegrationWebhookEvent::query()
            ->withoutGlobalScopes()
            ->whereNotNull('raw_payload')
            ->whereNotNull('retention_until')
            ->where('retention_until', '<', now())
            ->whereIn('projection_status', ['projected', 'unmatched', 'ambiguous', 'unsupported'])
            ->chunkById($chunkSize, function ($events) use (&$pruned): void {
                $ids = $events->pluck('id')->all();
                $pruned += IntegrationWebhookEvent::query()
                    ->withoutGlobalScopes()
                    ->whereIn('id', $ids)
                    ->update(['raw_payload' => null]);
            });

        $this->components->info("Payloads removidos: {$pruned}.");

        return self::SUCCESS;
    }
}
