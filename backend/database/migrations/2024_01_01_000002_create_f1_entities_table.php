<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Escuderías (Constructors)
        Schema::create('constructors', function (Blueprint $table) {
            $table->id();
            $table->string('api_id')->unique();         // 'mercedes', 'red_bull', etc.
            $table->string('name');
            $table->string('nationality')->nullable();
            $table->string('logo')->nullable();
            $table->string('color', 7)->default('#ffffff'); // Color hex de la escudería
            $table->integer('price')->default(20000000);    // Precio fantasy en centimos
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Pilotos
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('api_id')->unique();         // 'max_verstappen', etc.
            $table->string('code', 3)->nullable();      // 'VER', 'HAM', etc.
            $table->integer('number')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('nationality')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('photo')->nullable();
            $table->foreignId('constructor_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('price')->default(15000000); // Precio fantasy en centimos
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Circuitos
        Schema::create('circuits', function (Blueprint $table) {
            $table->id();
            $table->string('api_id')->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('country')->nullable();
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        // Carreras (Grand Prix)
        Schema::create('races', function (Blueprint $table) {
            $table->id();
            $table->string('api_id')->unique();         // '2024_1', etc.
            $table->integer('season');
            $table->integer('round');
            $table->string('name');
            $table->foreignId('circuit_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->time('time')->nullable();
            $table->boolean('is_sprint')->default(false);
            $table->enum('status', ['upcoming', 'active', 'scored', 'cancelled'])->default('upcoming');
            // Fantasy deadlines
            $table->timestamp('transfer_deadline')->nullable(); // Deadline para transferencias
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('races');
        Schema::dropIfExists('circuits');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('constructors');
    }
};
