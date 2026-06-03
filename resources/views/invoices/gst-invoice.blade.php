<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tax Invoice {{ $invoice->invoice_number ?? $invoice->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 12px; margin: 0; }
        .page { padding: 24px; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 14px; }
        .title { text-align: right; font-size: 22px; font-weight: 700; color: #0f172a; }
        .subtitle { text-align: right; font-size: 11px; color: #475569; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .grid td { vertical-align: top; width: 50%; padding: 6px 8px; }
        .card { border: 1px solid #cbd5e1; padding: 10px; min-height: 110px; }
        .label { font-size: 10px; text-transform: uppercase; color: #64748b; margin-bottom: 6px; }
        .bold { font-weight: 700; }
        table.items, table.tax, table.totals { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th, table.items td, table.tax th, table.tax td, table.totals td { border: 1px solid #cbd5e1; padding: 7px; }
        table.items th, table.tax th { background: #e2e8f0; font-size: 11px; }
        .right { text-align: right; }
        .footer { margin-top: 20px; font-size: 10px; color: #475569; }
        .qr { border: 1px dashed #94a3b8; width: 90px; height: 90px; text-align: center; line-height: 90px; float: right; color: #64748b; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <table width="100%">
                <tr>
                    <td>
                        <div class="bold" style="font-size: 18px;">{{ $gym['name'] ?? config('app.name') }}</div>
                    </td>
                    <td>
                        <div class="title">TAX INVOICE</div>
                        <div class="subtitle">Original for Recipient</div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="grid">
            <tr>
                <td>
                    <div class="card">
                        <div class="label">Supplier Details</div>
                        <div class="bold">{{ $gym['legal_name'] ?: ($gym['name'] ?? config('app.name')) }}</div>
                        <div>{{ $gym['address'] ?: '-' }}</div>
                        <div>GSTIN: {{ $gym['gstin'] ?: '-' }}</div>
                        <div>PAN: {{ $gym['pan'] ?: '-' }}</div>
                        <div>State Code: {{ $gym['state_code'] ?: '-' }}</div>
                    </div>
                </td>
                <td>
                    <div class="card">
                        <div class="label">Invoice Details</div>
                        <div>Invoice No: <span class="bold">{{ $invoice->invoice_number ?? $invoice->number }}</span></div>
                        <div>Invoice Date: {{ optional($invoice->invoice_date ?? $invoice->date)->format('d/m/Y') }}</div>
                        <div>Due Date: {{ optional($invoice->due_date)->format('d/m/Y') ?: '-' }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="card">
                        <div class="label">Recipient Details</div>
                        <div class="bold">{{ $invoice->subscription?->member?->name ?? '-' }}</div>
                        <div>{{ $invoice->subscription?->member?->address ?? '-' }}</div>
                        <div>{{ $invoice->subscription?->member?->contact ?? '-' }} | {{ $invoice->subscription?->member?->email ?? '-' }}</div>
                        <div>GSTIN: {{ $invoice->member_gstin ?: 'Unregistered' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>HSN/SAC</th>
                    <th>Qty</th>
                    <th class="right">Rate</th>
                    <th class="right">Taxable Amt</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>
                        Gym Membership - {{ $invoice->subscription?->plan?->name ?? 'Plan' }} -
                        {{ optional($invoice->invoice_date ?? $invoice->date)->format('F Y') }}
                    </td>
                    <td>{{ $invoice->hsn_sac_code ?? '999311' }}</td>
                    <td>1</td>
                    <td class="right">Rs. {{ number_format((float) $invoice->taxable_amount, 2) }}</td>
                    <td class="right">Rs. {{ number_format((float) $invoice->taxable_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="tax">
            <thead>
                <tr>
                    <th>HSN/SAC</th>
                    <th class="right">Taxable Amt</th>
                    <th class="right">CGST Rate</th>
                    <th class="right">CGST Amt</th>
                    <th class="right">SGST Rate</th>
                    <th class="right">SGST Amt</th>
                    <th class="right">IGST Rate</th>
                    <th class="right">IGST Amt</th>
                    <th class="right">Total Tax</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $invoice->hsn_sac_code ?? '999311' }}</td>
                    <td class="right">Rs. {{ number_format((float) $invoice->taxable_amount, 2) }}</td>
                    <td class="right">{{ number_format((float) $invoice->cgst_rate, 2) }}%</td>
                    <td class="right">Rs. {{ number_format((float) $invoice->cgst_amount, 2) }}</td>
                    <td class="right">{{ number_format((float) $invoice->sgst_rate, 2) }}%</td>
                    <td class="right">Rs. {{ number_format((float) $invoice->sgst_amount, 2) }}</td>
                    <td class="right">{{ number_format((float) $invoice->igst_rate, 2) }}%</td>
                    <td class="right">Rs. {{ number_format((float) $invoice->igst_amount, 2) }}</td>
                    <td class="right">Rs. {{ number_format((float) $invoice->total_tax, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="totals">
            <tr><td>Taxable Amount</td><td class="right">Rs. {{ number_format((float) $invoice->taxable_amount, 2) }}</td></tr>
            <tr><td>CGST</td><td class="right">Rs. {{ number_format((float) $invoice->cgst_amount, 2) }}</td></tr>
            <tr><td>SGST</td><td class="right">Rs. {{ number_format((float) $invoice->sgst_amount, 2) }}</td></tr>
            <tr><td>IGST</td><td class="right">Rs. {{ number_format((float) $invoice->igst_amount, 2) }}</td></tr>
            <tr><td class="bold">Total Amount</td><td class="right bold">Rs. {{ number_format((float) $invoice->total_amount, 2) }}</td></tr>
            <tr><td colspan="2">Amount in words: {{ $amount_in_words }}</td></tr>
        </table>

        <div class="footer">
            <div>This is a computer-generated invoice and does not require a signature.</div>
            <div>Subject to {{ $gym['name'] ?? config('app.name') }} jurisdiction.</div>
            <div class="qr">QR</div>
        </div>
    </div>
</body>
</html>
