<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fne_invoice_certifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inv_num_auto_index'); // Reference to InvNum table
            $table->string('invoice_number'); // From InvNum.InvNumber
            $table->string('fne_reference')->nullable(); // FNE generated reference
            $table->string('fne_token')->nullable(); // QR code token from FNE
            $table->text('fne_qr_url')->nullable(); // Full QR verification URL
            $table->enum('certification_status', ['pending', 'certified', 'failed', 'cancelled'])->default('pending');
            $table->json('request_payload')->nullable(); // Store the API request
            $table->json('response_payload')->nullable(); // Store the API response
            $table->string('error_message')->nullable();
            $table->integer('balance_sticker')->nullable(); // Remaining sticker balance
            $table->boolean('warning')->default(false); // Stock warning from FNE
            $table->timestamp('certified_at')->nullable();
            $table->timestamps();

            // $table->foreign('inv_num_auto_index')->references('AutoIndex')->on('InvNum');
            $table->index(['certification_status', 'created_at']);
            $table->unique('fne_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fne_invoice_certifications');
    }
};
