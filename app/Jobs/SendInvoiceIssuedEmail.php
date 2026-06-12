<?php

namespace App\Jobs;

use App\Services\Email\InvoiceEmailService;
use App\Support\Invoices\InvoiceDocumentNotRenderable;
use App\Jobs\Concerns\LogsJobFailures;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Jobs\TenantAware;

/**
 * Send the "invoice issued" email (queued).
 */
class SendInvoiceIssuedEmail implements ShouldBeUnique, ShouldQueue, TenantAware
{
    use Dispatchable, InteractsWithQueue, LogsJobFailures, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    public int $uniqueFor = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $invoiceId,
        public readonly string $toEmail,
        public readonly ?string $note = null,
        public readonly ?int $actorId = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(InvoiceEmailService $service): void
    {
        try {
            $service->sendInvoiceIssuedEmail(
                invoiceId: $this->invoiceId,
                toEmail: $this->toEmail,
                note: $this->note,
            );
        } catch (InvoiceDocumentNotRenderable $exception) {
            Log::warning('Skipping invoice email: missing required invoice data.', [
                'invoice_id' => $this->invoiceId,
                'missing' => $exception->viewData['missing'] ?? [],
            ]);
        }
    }

    public function uniqueId(): string
    {
        $tenantId = app()->bound('currentTenant') && app('currentTenant')
            ? (string) app('currentTenant')->id
            : 'global';

        return implode(':', [
            $tenantId,
            (string) $this->invoiceId,
            md5($this->toEmail),
            md5((string) ($this->note ?? '')),
        ]);
    }
}
