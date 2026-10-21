<?php
declare(strict_types=1);

require_once __DIR__ . '/Mahasiswa.php';

class DataMahasiswa
{
    /** @var array<string, \Mahasiswa> */
    private array $data = [];

    /**
     * @param array<string, \Mahasiswa> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $nim => $item) {
            if ($item instanceof \Mahasiswa) {
                $this->data[(string) $nim] = $item;
            }
        }
    }

    /**
     * @param \Mahasiswa $mahasiswa
     */
    public function tambah(\Mahasiswa $mahasiswa): bool
    {
        if (isset($this->data[$mahasiswa->nim])) return false;
        $this->data[$mahasiswa->nim] = $mahasiswa;
        return true;
    }

    /** @return array<string, \Mahasiswa> */
    public function semua(): array { return $this->data; }

    /**
     * @param string $nim
     * @return \Mahasiswa|null
     */
    public function cari(string $nim): ?\Mahasiswa
    {
        return $this->data[$nim] ?? null;
    }

    /**
     * Fitur pencarian fleksibel berdasarkan kombinasi NIM atau Nama
     * @param string $keyword
     * @return array<string, \Mahasiswa>
     */
    public function cariFleksibel(string $keyword): array
    {
        if (trim($keyword) === '') return $this->data;
        
        $hasil = [];
        $keyword = strtolower($keyword);
        foreach ($this->data as $mhs) {
            if (str_contains(strtolower($mhs->nim), $keyword) || str_contains(strtolower($mhs->nama), $keyword)) {
                $hasil[$mhs->nim] = $mhs;
            }
        }
        return $hasil;
    }

    /**
     * Menghitung statistik sebaran jumlah mahasiswa per Fakultas
     */
    public function getStatistikFakultas(): array
    {
        $stats = [];
        foreach ($this->data as $mhs) {
            $stats[$mhs->fakultas] = ($stats[$mhs->fakultas] ?? 0) + 1;
        }
        return $stats;
    }

    /**
     * @param string $nimLama
     * @param \Mahasiswa $baru
     */
    public function ubah(string $nimLama, \Mahasiswa $baru): bool
    {
        if (!isset($this->data[$nimLama])) return false;
        if ($nimLama !== $baru->nim && isset($this->data[$baru->nim])) return false;
        unset($this->data[$nimLama]);
        $this->data[$baru->nim] = $baru;
        return true;
    }

    public function hapus(string $nim): bool
    {
        if (!isset($this->data[$nim])) return false;
        unset($this->data[$nim]);
        return true;
    }
}
