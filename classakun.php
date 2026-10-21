<?php
class Akun {
    public string $username = "";
    private string $password = "rahasia";
 
    public function info(): string {
        return $this->username;
    }
 
    // Jalur resmi (public) untuk memeriksa password tanpa membukanya
    public function cekPassword(string $input): bool {
        return $input === $this->password;
    }
}
 
$akun = new Akun();
$akun->username = "mahasiswa";
echo $akun->info() . "<br>";
var_dump($akun->cekPassword("rahasia"));
 
// Hilangkan tanda // di bawah ini untuk melihat error akses private:
// echo $akun->password;
 
