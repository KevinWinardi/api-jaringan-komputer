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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 24)->unique();
            $table->string('judul', 250);
            $table->json('penulis');
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->json('kategori');
            $table->unsignedInteger('stok')->default(0);
            $table->string('open_library_key', 100)
                ->nullable()
                ->unique();
            $table->unsignedInteger('cover_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
