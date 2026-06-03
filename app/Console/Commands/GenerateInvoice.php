<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Services\GstInvoiceService;
use Illuminate\Console\Command;

class GenerateInvoice extends Command
{
    protected $signature = 'invoice:generate {member_id} {amount}';

    protected $description = 'Generate GST invoice for a member';

    public function handle(GstInvoiceService $gstInvoiceService): int
    {
        $member = Member::query()->find((int) $this->argument('member_id'));
        if (! $member) {
            $this->error('Member not found.');

            return self::FAILURE;
        }

        $amount = (float) $this->argument('amount');
        if ($amount <= 0) {
            $this->error('Amount must be greater than zero.');

            return self::FAILURE;
        }

        $invoice = $gstInvoiceService->createGstInvoice($member, $amount, 'Manual GST invoice generation');
        $this->info('Invoice created: '.($invoice->invoice_number ?: $invoice->number));

        return self::SUCCESS;
    }
}
