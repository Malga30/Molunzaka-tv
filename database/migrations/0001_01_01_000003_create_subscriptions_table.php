<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('plans')->onDelete('restrict');
            $table->timestamp('started_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('status')->default('active'); // active, cancelled, expired
            $table->string('payment_method')->nullable(); // credit_card, paypal, etc.
            $table->string('stripe_subscription_id')->nullable();
            $table->decimal('price_paid', 8, 2); // Price at time of subscription
            $table->boolean('auto_renew')->default(true);
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
