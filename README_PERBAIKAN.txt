PERBAIKAN: Pada index.php, require_once DataMahasiswa.php dipindahkan SEBELUM session_start().
Alasan: PHP harus mengenal class Mahasiswa sebelum object yang tersimpan di session dideserialisasi.
Sebelumnya object menjadi __PHP_Incomplete_Class, lalu dibuang oleh pemeriksaan instanceof pada constructor DataMahasiswa.
Salin file PHP ke C:\xampp\htdocs\pbo_php\pertemuan_03\ lalu buka http://localhost/pbo_php/pertemuan_03/.
Data lama yang telah terlanjur menjadi incomplete object mungkin perlu ditambahkan ulang.
