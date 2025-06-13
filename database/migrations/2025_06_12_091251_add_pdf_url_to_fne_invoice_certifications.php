<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('fne_invoice_certifications', function (Blueprint $table) {
            $table->string('pdf_url')->nullable()->after('fne_qr_url');
        });
    }

    public function down()
    {
        Schema::table('fne_invoice_certifications', function (Blueprint $table) {
            $table->dropColumn('pdf_url');
        });
    }
};
