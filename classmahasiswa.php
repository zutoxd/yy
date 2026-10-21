<?php
class Mahasiswa {
    public string $nama = "";
    public string $nim = "";
 
    public function tampilProfil(): string {
        return $this->nim . " - " . $this->nama;
    }
}
 
$m1 = new Mahasiswa();
$m1->nama = "Alya";
$m1->nim = "2024001";
 
$m2 = new Mahasiswa();
$m2->nama = "Budi";
$m2->nim = "2024002";
 
echo $m1->tampilProfil() . "<br>";
echo $m2->tampilProfil();
 
