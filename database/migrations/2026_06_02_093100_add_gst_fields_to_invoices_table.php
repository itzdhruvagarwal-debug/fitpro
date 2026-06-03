<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('invoice_number')->nullable()->unique()->after('number');
            $table->date('invoice_date')->nullable()->after('date');
            $table->string('member_gstin', 15)->nullable()->after('invoice_date');
            $table->decimal('taxable_amount', 10, 2)->nullable()->after('member_gstin');
            $table->decimal('cgst_rate', 5, 2)->default(9.00)->after('taxable_amount');
            $table->decimal('cgst_amount', 10, 2)->default(0)->after('cgst_rate');
            $table->decimal('sgst_rate', 5, 2)->default(9.00)->after('cgst_amount');
            $table->decimal('sgst_amount', 10, 2)->default(0)->after('sgst_rate');
            $table->decimal('igst_rate', 5, 2)->default(0)->after('sgst_amount');
            $table->decimal('igst_amount', 10, 2)->default(0)->after('igst_rate');
            $table->decimal('total_tax', 10, 2)->default(0)->after('igst_amount');
            $table->boolean('is_inter_state')->default(false)->after('total_tax');
            $table->string('hsn_sac_code')->default('999311')->after('is_inter_state');
            $table->string('invoice_pdf_path')->nullable()->after('hsn_sac_code');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn([
                'invoice_number',
                'invoice_date',
                'member_gstin',
                'taxable_amount',
                'cgst_rate',
                'cgst_amount',
                'sgst_rate',
                'sgst_amount',
                'igst_rate',
                'igst_amount',
                'total_tax',
                'is_inter_state',
                'hsn_sac_code',
                'invoice_pdf_path',
            ]);
        });
    }
};
