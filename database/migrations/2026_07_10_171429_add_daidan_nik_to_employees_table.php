<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration ini pada connection 'hris' (Gandasari HRIS DB).
     *
     * daidan_nik adalah cross-system identity key antara DaidanERP dan Gandasari HRIS.
     * - Tipe string agar aman untuk NIK alfanumerik dan leading zero.
     * - nullable selama masa transisi dan backfill data lama.
     * - unique constraint wajib aktif untuk nilai non-null.
     * - Index ditambahkan secara eksplisit untuk lookup performa tinggi pada
     *   endpoint PUT /api/v1/employees/{daidanNik} dan Attendance COALESCE query.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('daidan_nik', 50)
                ->nullable()
                ->unique()
                ->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Hapus unique index terlebih dahulu sebelum drop kolom
            $table->dropUnique(['daidan_nik']);
            $table->dropColumn('daidan_nik');
        });
    }
};
