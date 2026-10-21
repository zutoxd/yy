<?php
declare(strict_types=1);

class Mahasiswa
{
    public function __construct(
        public string $nim,
        public string $nama,
        public string $fakultas,
        public string $prodi,
        public string $asalSekolah,
        public int $semester,
        public string $hobi = '',
        public string $alamat = '',
        public string $email = ''
    ) {}

    public function getLabelTingkat(): string
    {
        if ($this->semester <= 2) {
            return 'Mahasiswa Baru';
        }
        if ($this->semester >= 7) {
            return 'Tingkat Akhir';
        }
        return 'Mahasiswa Aktif';
    }
}