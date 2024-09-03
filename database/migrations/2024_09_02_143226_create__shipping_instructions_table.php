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
        Schema::create('ShippingInstructions', function (Blueprint $table) {
            $table->id();
            $table->string('PONumber')->nullable();
$table->json('Containers')->nullable();
$table->timestamps();
$table->softDeletes();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('ShippingInstructions');
    }
};
