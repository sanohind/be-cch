<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CauseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('m_causes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::table('m_causes')->insert([
            // ── Occurrence Causes (Blok 8) ────────────────────────────────────
            [
                'type'        => 'occurrence',
                'cause_name'  => 'Mesin/alat tidak dikalibrasi',
                'description' => 'Peralatan produksi tidak melalui proses kalibrasi berkala sesuai jadwal',
                'is_active'   => 1,
            ],
            [
                'type'        => 'occurrence',
                'cause_name'  => 'Operator tidak mengikuti SOP',
                'description' => 'Tenaga kerja menyimpang dari prosedur operasi standar yang ditetapkan',
                'is_active'   => 1,
            ],
            [
                'type'        => 'occurrence',
                'cause_name'  => 'Material tidak sesuai spesifikasi',
                'description' => 'Bahan baku yang diterima tidak memenuhi standar dimensi atau komposisi',
                'is_active'   => 1,
            ],
            [
                'type'        => 'occurrence',
                'cause_name'  => 'Metode kerja tidak tepat',
                'description' => 'Instruksi kerja (WI/SOP) tidak sesuai kondisi aktual produksi',
                'is_active'   => 1,
            ],
            [
                'type'        => 'occurrence',
                'cause_name'  => 'Desain produk tidak sesuai kondisi produksi',
                'description' => 'Spesifikasi desain tidak mempertimbangkan keterbatasan atau kondisi proses aktual',
                'is_active'   => 1,
            ],
            [
                'type'        => 'occurrence',
                'cause_name'  => 'Parameter proses di luar batas kontrol',
                'description' => 'Suhu, tekanan, waktu, atau parameter proses lainnya tidak terjaga dalam batas yang ditentukan',
                'is_active'   => 1,
            ],
            [
                'type'        => 'occurrence',
                'cause_name'  => 'Operator baru/belum terlatih',
                'description' => 'Pekerja belum mendapatkan pelatihan yang cukup untuk proses yang ditangani',
                'is_active'   => 1,
            ],
            [
                'type'        => 'occurrence',
                'cause_name'  => 'Tooling/cetakan aus',
                'description' => 'Kondisi tooling, dies, atau cetakan sudah melebihi batas umur pakai',
                'is_active'   => 1,
            ],

            // ── Outflow Causes (Blok 9) ───────────────────────────────────────
            [
                'type'        => 'outflow',
                'cause_name'  => 'Sampling inspection tidak dilakukan',
                'description' => 'Proses pemeriksaan sampling dilewati atau tidak dilaksanakan sesuai frekuensi yang ditetapkan',
                'is_active'   => 1,
            ],
            [
                'type'        => 'outflow',
                'cause_name'  => 'Dokumen QC hilang/tidak lengkap',
                'description' => 'Formulir pemeriksaan kualitas tidak tersimpan atau tidak terisi dengan benar',
                'is_active'   => 1,
            ],
            [
                'type'        => 'outflow',
                'cause_name'  => 'Alat ukur tidak akurat/tidak terkalibrasi',
                'description' => 'Instrumen pengukuran (micrometer, caliper, dll.) tidak terkalibrasi atau rusak',
                'is_active'   => 1,
            ],
            [
                'type'        => 'outflow',
                'cause_name'  => 'Inspeksi visual tidak dilakukan',
                'description' => 'Pemeriksaan visual diabaikan atau tidak mencakup seluruh area produk saat proses outflow',
                'is_active'   => 1,
            ],
            [
                'type'        => 'outflow',
                'cause_name'  => 'Kriteria kelulusan tidak jelas',
                'description' => 'Standar penerimaan/penolakan produk tidak terdefinisi dengan jelas di instruksi kerja inspeksi',
                'is_active'   => 1,
            ],
            [
                'type'        => 'outflow',
                'cause_name'  => 'Tata letak area inspeksi tidak sesuai',
                'description' => 'Pemisahan produk OK dan NG tidak jelas, memungkinkan produk cacat tercampur',
                'is_active'   => 1,
            ],
            [
                'type'        => 'outflow',
                'cause_name'  => 'Inspector tidak memahami spesifikasi',
                'description' => 'Petugas QC tidak memiliki pengetahuan yang cukup tentang standar produk',
                'is_active'   => 1,
            ],
        ]);
    }
}
