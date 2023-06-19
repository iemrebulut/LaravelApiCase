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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('detail');
            $table->integer('is_price_limit_campaign')->comment('1: Alışveriş tutarı kampanyası');
            $table->integer('is_x_al_y_ode_campaign')->comment('1: X al y öde kampanyası');
            $table->double('price_min_limit');
            $table->double('percent');
            $table->integer('x_al_y_ode_limit');
            $table->integer('x_al_y_ode_free');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
