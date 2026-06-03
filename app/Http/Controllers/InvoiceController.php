<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Email\InvoiceEmailService;
use App\Services\GstInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function download(Invoice $invoice, GstInvoiceService $service): StreamedResponse
    {
        $this->authorize('view', $invoice);

        if (! $invoice->invoice_pdf_path || ! Storage::disk('local')->exists($invoice->invoice_pdf_path)) {
            $service->generateInvoicePdf($invoice);
            $invoice->refresh();
        }

        $filename = ($invoice->invoice_number ?: $invoice->number ?: 'invoice').'.pdf';

        return Storage::disk('local')->download($invoice->invoice_pdf_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function sendEmail(Invoice $invoice, InvoiceEmailService $invoiceEmailService): RedirectResponse
    {
        $this->authorize('view', $invoice);

        $email = $invoice->subscription?->member?->email;
        if (blank($email)) {
            return back()->with('error', 'Member email not available.');
        }

        $invoiceEmailService->queueInvoiceIssuedEmail(
            invoiceId: (int) $invoice->id,
            toEmail: (string) $email,
            note: 'GST invoice',
            actorId: is_int(auth()->id()) ? auth()->id() : null,
        );

        return back()->with('success', 'Invoice email queued.');
    }
}
