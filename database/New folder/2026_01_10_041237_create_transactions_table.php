<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('transactions');

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_gmail')->nullable();
            $table->string('method')->nullable();           // bkash | nagad | rocket
            $table->string('transaction_id')->nullable()->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('page')->nullable();             // checkout | addfunds
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('sender_number')->nullable();
            $table->string('receiver_number')->nullable();
            $table->enum('status', ['pending', 'verifying', 'success', 'failed', 'duplicate'])->default('pending')->index();
            $table->timestamp('time_paid')->nullable();
            $table->boolean('unpaid')->default(false);
            $table->text('note')->nullable();
            $table->json('webhook_response')->nullable();
            $table->timestamps();

            $table->unique(['method', 'transaction_id'], 'tx_method_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
