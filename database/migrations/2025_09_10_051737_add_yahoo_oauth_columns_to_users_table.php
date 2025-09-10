<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('yahoo_email')->nullable()->index();
            $table->text('yahoo_access_token')->nullable();
            $table->text('yahoo_refresh_token')->nullable();
            $table->timestamp('yahoo_token_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'yahoo_email','yahoo_access_token','yahoo_refresh_token','yahoo_token_expires_at'
            ]);
        });
    }
};
