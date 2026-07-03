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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->string('group')->nullable();
            $table->string('key')->nullable();
            $table->text('value')->nullable();
            $table->string('type')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('group');
            $table->unique(['group', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
