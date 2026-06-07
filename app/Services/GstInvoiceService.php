<?php

namespace App\Services;

use App\Contracts\SettingsRepository;
use App\Helpers\NumberToWords;
use App\Models\Invoice;
use App\Models\Member;
use App\Services\JsonSettingsRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class GstInvoiceService
{
    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        return app(SettingsRepository::class)->get();
    }

    /**
     * @return array{
     * taxable_amount: float,
     * cgst_rate: float,
     * cgst_amount: float,
     * sgst_rate: float,
     * sgst_amount: float,
     * igst_rate: float,
     * igst_amount: float,
     * total_tax: float,
     * total_amount: float,
     * is_inter_state: bool
     * }
     */
    public function calculateGst(float $baseAmount, bool $isInterState = false): array
    {
        $settings = $this->settings();
        $gst = data_get($settings, 'gst', []);
        $enabled = (bool) ($gst['enabled'] ?? false);

        if (! $enabled) {
            return [
                'taxable_amount' => $baseAmount,
                'cgst_rate' => 0,
                'cgst_amount' => 0,
                'sgst_rate' => 0,
                'sgst_amount' => 0,
                'igst_rate' => 0,
                'igst_amount' => 0,
                'total_tax' => 0,
                'total_amount' => $baseAmount,
                'is_inter_state' => false,
            ];
        }

        $gstRate = (float) ($gst['gst_rate'] ?? 18.00);
        $taxableAmount = round($baseAmount, 2);
        $totalTax = round(($taxableAmount * $gstRate) / 100, 2);

        if ($isInterState) {
            return [
                'taxable_amount' => $taxableAmount,
                'cgst_rate' => 0,
                'cgst_amount' => 0,
                'sgst_rate' => 0,
                'sgst_amount' => 0,
                'igst_rate' => $gstRate,
                'igst_amount' => $totalTax,
                'total_tax' => $totalTax,
                'total_amount' => round($taxableAmount + $totalTax, 2),
                'is_inter_state' => true,
            ];
        }

        $halfTax = round($totalTax / 2, 2);

        return [
            'taxable_amount' => $taxableAmount,
            'cgst_rate' => round($gstRate / 2, 2),
            'cgst_amount' => $halfTax,
            'sgst_rate' => round($gstRate / 2, 2),
            'sgst_amount' => round($totalTax - $halfTax, 2),
            'igst_rate' => 0,
            'igst_amount' => 0,
            'total_tax' => $totalTax,
            'total_amount' => round($taxableAmount + $totalTax, 2),
            'is_inter_state' => false,
        ];
    }

    public function generateInvoiceNumber(): string
    {
        $repository = app(SettingsRepository::class);
        $invoiceNumber = null;

        $mutator = function (array $settings) use (&$invoiceNumber): array {
            $gst = data_get($settings, 'gst', []);
            $gst = is_array($gst) ? $gst : [];
            $prefix = strtoupper((string) ($gst['invoice_prefix'] ?? 'GSA'));
            $counter = max((int) ($gst['invoice_counter'] ?? 1), 1);
            $year = Carbon::now()->year;
            $serial = str_pad((string) $counter, 5, '0', STR_PAD_LEFT);
            $invoiceNumber = "{$prefix}-{$year}-{$serial}";

            $settings['gst'] = [
                ...$gst,
                'invoice_counter' => $counter + 1,
                'invoice_prefix' => $prefix,
            ];

            return $settings;
        };

        if ($repository instanceof JsonSettingsRepository) {
            $repository->updateWithLock($mutator);

            return (string) $invoiceNumber;
        }

        $settings = $repository->get();
        $repository->put($mutator($settings));

        return (string) $invoiceNumber;
    }

    public function generateInvoicePdf(Invoice $invoice): string
    {
        $invoice = $invoice->fresh(['subscription.member', 'subscription.plan']);
        $settings = $this->settings();
        $gst = data_get($settings, 'gst', []);
        $general = data_get($settings, 'general', []);

        $pdf = Pdf::loadView('invoices.gst-invoice', [
            'invoice' => $invoice,
            'settings' => $settings,
            'gym' => [
                'name' => data_get($general, 'gym_name', config('app.name')),
                'gstin' => $gst['gym_gstin'] ?? null,
                'pan' => $gst['gym_pan'] ?? null,
                'legal_name' => $gst['gym_legal_name'] ?? null,
                'address' => implode(', ', array_filter([
                    $gst['gym_address_line1'] ?? null,
                    $gst['gym_address_line2'] ?? null,
                    $gst['gym_city'] ?? null,
                    $gst['gym_state'] ?? null,
                    $gst['gym_pincode'] ?? null,
                ])),
                'state_code' => $gst['gym_state_code'] ?? null,
            ],
            'amount_in_words' => NumberToWords::toIndianCurrencyWords((float) $invoice->total_amount),
        ])->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
            ]);

        $fileName = 'invoices/'.($invoice->invoice_number ?: $invoice->number).'.pdf';
        Storage::disk('local')->put($fileName, $pdf->output());
        $invoice->update(['invoice_pdf_path' => $fileName]);

        return $fileName;
    }

    public function createGstInvoice(Member $member, float $amount, string $description = ''): Invoice
    {
        $settings = $this->settings();
        $gst = data_get($settings, 'gst', []);
        $gymState = (string) ($gst['gym_state'] ?? '');
        $memberState = (string) ($member->state ?? '');
        $isInterState = $gymState !== '' && $memberState !== '' && strcasecmp($gymState, $memberState) !== 0;
        $gstBreakdown = $this->calculateGst($amount, $isInterState);
        $invoiceNumber = $this->generateInvoiceNumber();
        $subscription = $member->subscriptions()->latest('end_date')->with('plan')->firstOrFail();

        $invoice = Invoice::query()->create([
            'subscription_id' => $subscription->id,
            'number' => $invoiceNumber,
            'invoice_number' => $invoiceNumber,
            'date' => now()->toDateString(),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'status' => 'issued',
            'subscription_fee' => $amount,
            'payment_method' => 'online',
            'discount_amount' => 0,
            'paid_amount' => 0,
            'member_gstin' => $member->gstin,
            'hsn_sac_code' => '999311',
            ...$gstBreakdown,
            'tax' => $gstBreakdown['total_tax'],
            'total_amount' => $gstBreakdown['total_amount'],
            'discount_note' => $description !== '' ? $description : null,
        ]);

        dispatch(fn () => $this->generateInvoicePdf($invoice))->afterCommit();

        return $invoice;
    }
}
