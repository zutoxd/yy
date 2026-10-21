<?php
declare(strict_types=1);

require_once __DIR__ . '/DataMahasiswa.php';
session_start();

// Data diambil dari array objek Mahasiswa yang disimpan di session
$repo = new \DataMahasiswa($_SESSION['mahasiswa'] ?? []);
$pesan = (string) ($_SESSION['pesan'] ?? '');
unset($_SESSION['pesan']);
$error = '';

/** @var \Mahasiswa|null $edit */
$edit = null;

function aman(string $teks): string {
    return htmlspecialchars($teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Fungsi pembantu untuk memindahkan logika label dari HTML ke blok PHP utama
 * guna menghindari deteksi warning ketat pada ekstensi VS Code Namespace Resolver.
 */
function dapatkanBadgeTingkat(mixed $mahasiswa): array {
    if ($mahasiswa instanceof \Mahasiswa) {
        $label = $mahasiswa->getLabelTingkat();
        $class = 'badge-aktif';
        if ($label === 'Mahasiswa Baru') {
            $class = 'badge-baru';
        } elseif ($label === 'Tingkat Akhir') {
            $class = 'badge-akhir';
        }
        return ['label' => $label, 'class' => $class];
    }
    return ['label' => 'Umum', 'class' => 'badge-aktif'];
}

// Inovasi Fitur: Pemrosesan download CSV langsung dari kode controller utama
if (isset($_GET['aksi']) && $_GET['aksi'] === 'ekspor_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Data_Mahasiswa_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['NIM', 'Nama Lengkap', 'Fakultas', 'Program Studi', 'Asal Sekolah', 'Alamat', 'Email', 'Semester', 'Hobi', 'Status Akademik']);
    foreach ($repo->semua() as $m) {
        fputcsv($output, [
            $m->nim,
            $m->nama,
            $m->fakultas,
            $m->prodi,
            $m->asalSekolah,
            $m->alamat ?? '',
            $m->email ?? '',
            $m->semester,
            $m->hobi,
            $m->getLabelTingkat()
        ]);
    }
    fclose($output);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string) ($_POST['aksi'] ?? '');
    $nimLama = trim((string) ($_POST['nim_lama'] ?? ''));
    $nim = trim((string) ($_POST['nim'] ?? ''));
    try {
        if ($aksi === 'hapus') {
            $berhasil = $repo->hapus($nim);
            $pesan = $berhasil ? 'Data mahasiswa sukses dihapus dari sistem.' : 'NIM tidak terdaftar.';
        } elseif (in_array($aksi, ['tambah', 'ubah'], true)) {
            $nama = trim((string) ($_POST['nama'] ?? ''));
            $fakultas = trim((string) ($_POST['fakultas'] ?? ''));
            $prodi = trim((string) ($_POST['prodi'] ?? ''));
            $asalSekolah = trim((string) ($_POST['asal_sekolah'] ?? ''));
            $alamat = trim((string) ($_POST['alamat'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $hobi = trim((string) ($_POST['hobi'] ?? ''));
            $semester = filter_var($_POST['semester'] ?? null, FILTER_VALIDATE_INT);
            
            if ($nim === '' || $nama === '' || $fakultas === '' || $prodi === '' || $asalSekolah === '' || $alamat === '' || $email === '' || $semester === false || $semester < 1 || $semester > 14) {
                throw new InvalidArgumentException('Mohon lengkapi semua kolom wajib termasuk alamat, email, dan isi rentang semester 1-14.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Format email tidak valid.');
            }
            
            $mhs = new \Mahasiswa($nim, $nama, $fakultas, $prodi, $asalSekolah, $semester, $hobi, $alamat, $email);
            

            $berhasil = $aksi === 'tambah' ? $repo->tambah($mhs) : $repo->ubah($nimLama, $mhs);
            if (!$berhasil) throw new InvalidArgumentException('Gagal memproses data. NIM sudah digunakan oleh mahasiswa lain.');
            $pesan = $aksi === 'tambah' ? 'Mahasiswa baru berhasil ditambahkan!' : 'Perubahan data mahasiswa berhasil disimpan.';
        } else {
            throw new InvalidArgumentException('Aksi tidak valid.');
        }
        $_SESSION['mahasiswa'] = $repo->semua();
        $_SESSION['pesan'] = $pesan;
        header('Location: index.php');
        exit;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
}

if (isset($_GET['edit'])) {
    $edit = $repo->cari((string) $_GET['edit']);
}

$keyword = (string) ($_GET['cari'] ?? '');
$daftarMahasiswa = $repo->cariFleksibel($keyword);
$statFakultas = $repo->getStatistikFakultas();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Akademik Hub PBO</title>
    <style>
        /* Desain Kustom Mode Gelap Premium - 100% Offline Tanpa Internet */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #0f172a; color: #e2e8f0; padding: 2rem 1rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        header { border-bottom: 2px solid #1e293b; padding-bottom: 1.2rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        header h1 { font-size: 2rem; color: #f8fafc; font-weight: 800; }
        header h1 span { color: #10b981; }
        header p { font-size: 0.875rem; color: #94a3b8; margin-top: 0.25rem; }

        .alert { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.9rem; font-weight: 500; border-left: 4px solid; }
        .alert-success { color: #34d399; border-left-color: #10b981; background: #064e3b; }
        .alert-error { color: #f87171; border-left-color: #ef4444; background: #7f1d1d; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .card-stat { background: #1e293b; padding: 1.5rem; border: 1px solid #334155; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2); }
        .card-stat p { font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; font-weight: 600; letter-spacing: 0.05em; }
        .card-stat h3 { font-size: 1.75rem; margin-top: 0.25rem; color: #f8fafc; font-weight: 700; }

        .main-layout { display: grid; grid-template-columns: 1fr; gap: 2rem; }
        @media (min-width: 992px) { .main-layout { grid-template-columns: 1fr 2fr; } }

        .panel { background: #1e293b; border-radius: 1rem; border: 1px solid #334155; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2); margin-bottom: 2rem; }
        .panel h2 { font-size: 1.25rem; color: #f8fafc; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .panel h2::before { content: ''; display: inline-block; width: 6px; height: 1.25rem; border-radius: 3px; }
        .panel-form h2::before { background: #10b981; }
        .panel-table h2::before { background: #3b82f6; }

        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; margin-bottom: 0.35rem; }
        .form-group input, .form-group textarea { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #475569; border-radius: 0.5rem; font-size: 0.9rem; background: #0f172a; color: #f8fafc; transition: all 0.2s; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #10b981; background: #1e293b; box-shadow: 0 0 0 3px rgba(16,185,129,0.25); }
        
        .btn { display: inline-block; padding: 0.6rem 1.2rem; font-size: 0.875rem; font-weight: 600; border-radius: 0.5rem; border: none; cursor: pointer; text-decoration: none; text-align: center; transition: all 0.15s; }
        .btn-primary { background: #10b981; color: white; width: 100%; }
        .btn-primary:hover { background: #059669; }
        .btn-secondary { background: #475569; color: #e2e8f0; width: 100%; margin-top: 0.5rem; }
        .btn-secondary:hover { background: #334155; }
        .btn-csv { background: #2563eb; color: white; padding: 0.5rem 1rem; font-size: 0.85rem; border-radius: 0.5rem; text-decoration: none; display: inline-flex; align-items: center; font-weight: 600; }
        .btn-csv:hover { background: #1d4ed8; }
        
        .search-container { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .search-box { display: flex; gap: 0.5rem; width: 100%; max-width: 400px; }
        .search-box input { padding: 0.5rem 0.75rem; border: 1px solid #475569; border-radius: 0.5rem; font-size: 0.875rem; flex-grow: 1; background: #0f172a; color: #f8fafc; }
        .search-box button { background: #334155; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; font-size: 0.875rem; cursor: pointer; font-weight: 500; }
        .search-box button:hover { background: #475569; }
        .reset-link { font-size: 0.8rem; color: #94a3b8; align-self: center; text-decoration: none; padding: 0.25rem 0.5rem; background: #334155; border-radius: 0.25rem; }

        .table-responsive { overflow-x: auto; border: 1px solid #334155; border-radius: 0.75rem; background: #0f172a; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem; }
        th { background: #1e293b; color: #94a3b8; font-weight: 600; padding: 0.75rem 1rem; border-bottom: 2px solid #334155; }
        td { padding: 1rem; border-bottom: 1px solid #334155; color: #cbd5e1; vertical-align: middle; }
        tr:hover td { background: #1e293b; }
        
        .mhs-nama { font-weight: 700; color: #f8fafc; }
        .mhs-nim { font-family: monospace; font-size: 0.75rem; color: #64748b; margin-top: 0.15rem; }
        
        .badge { display: inline-block; padding: 0.15rem 0.5rem; font-size: 0.65rem; font-weight: 700; border-radius: 0.25rem; border: 1px solid; margin-top: 0.35rem; text-transform: uppercase; }
        .badge-baru { background: #064e3b; color: #34d399; border-color: #047857; }
        .badge-aktif { background: #1e3a8a; color: #60a5fa; border-color: #1d4ed8; }
        .badge-akhir { background: #78350f; color: #fbbf24; border-color: #b45309; }

        .action-cell { display: flex; gap: 0.35rem; justify-content: flex-end; }
        .btn-table { padding: 0.35rem 0.6rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.35rem; border: 1px solid; text-decoration: none; cursor: pointer; background: transparent; }
        .btn-edit { color: #60a5fa; border-color: #2563eb; }
        .btn-edit:hover { background: #1e3a8a; }
        .btn-delete { color: #f87171; border-color: #dc2626; }
        .btn-delete:hover { background: #7f1d1d; }

        footer { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #334155; display: flex; justify-content: space-between; font-size: 0.75rem; color: #64748b; }
        .state-label { background: #334155; color: #e2e8f0; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-weight: 600; }
    </style>
</head>
<body>

    <div class="container">
        <!-- Header -->
        <header>
            <div>
                <h1>Akademik Hub <span>PBO</span></h1>
                <p>Sistem Informasi Mahasiswa berbasis Objek (OOP) & Session State Management.</p>
            </div>
            <div>
                <a href="?aksi=ekspor_csv" class="btn-csv">📥 Ekspor Excel (.csv)</a>
            </div>
        </header>

        <!-- Notifikasi -->
        <?php if ($pesan): ?>
            <div class="alert alert-success"><strong>Sukses!</strong> <?= aman($pesan) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><strong>Perhatian!</strong> <?= aman($error) ?></div>
        <?php endif; ?>

        <!-- KARTU STATISTIK WIDGET -->
        <div class="stats-grid">
            <div class="card-stat">
                <p>Total Mahasiswa</p>
                <h3><?= count($repo->semua()) ?></h3>
            </div>
            <div class="card-stat">
                <p>Fakultas Terdaftar</p>
                <h3 style="color: #10b981;"><?= count($statFakultas) ?></h3>
            </div>
            <div class="card-stat">
                <p>Hasil Pencarian</p>
                <h3 style="color: #3b82f6;"><?= count($daftarMahasiswa) ?></h3>
            </div>
        </div>

        <div class="main-layout">
            <section class="panel panel-form">
                <h2><?= $edit ? 'Perbarui Data' : 'Registrasi Mahasiswa' ?></h2>
                <form method="post">
                    <input type="hidden" name="aksi" value="<?= $edit ? 'ubah' : 'tambah' ?>">
                    <input type="hidden" name="nim_lama" value="<?= aman($edit?->nim ?? '') ?>">

                    <div class="form-group">
                        <label>NIM</label>
                        <input name="nim" value="<?= aman($edit?->nim ?? '') ?>" placeholder="Contoh: 22010101" type="text" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input name="nama" value="<?= aman($edit?->nama ?? '') ?>" placeholder="Nama tanpa gelar" type="text" required>
                    </div>
                    <div class="form-group">
                        <label>Fakultas</label>
                        <input name="fakultas" value="<?= aman($edit?->fakultas ?? '') ?>" placeholder="Fakultas Ilmu Komputer, dll." type="text" required>
                    </div>
                    <div class="form-group">
                        <label>Program Studi</label>
                        <input name="prodi" value="<?= aman($edit?->prodi ?? '') ?>" placeholder="Teknik Informatika, dll." type="text" required>
                    </div>
                    <div class="form-group">
                        <label>Asal Sekolah</label>
                        <input name="asal_sekolah" value="<?= aman($edit?->asalSekolah ?? '') ?>" placeholder="SMA / SMK Asal" type="text" required>
                    </div>
                    <div class="form-group">
                        <label>Alamat Tempat Tinggal</label>
                        <textarea name="alamat" placeholder="Jalan, Kota/Kabupaten domisili" rows="2" required><?= aman($edit?->alamat ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input name="email" value="<?= aman($edit?->email ?? '') ?>" placeholder="nama@email.com" type="email" required>
                    </div>
                    <div class="form-group">
                        <label>Semester Aktif</label>
                        <input name="semester" value="<?= aman((string)($edit?->semester ?? '')) ?>" placeholder="Angka 1-14" type="number" min="1" max="14" required>
                    </div>
                    <div class="form-group">
                        <label>Hobi / Minat</label>
                        <input name="hobi" value="<?= aman($edit?->hobi ?? '') ?>" placeholder="Opsional - Contoh: Coding" type="text">
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary"><?= $edit ? 'Simpan Pembaruan' : 'Submit Data' ?></button>
                        <?php if ($edit): ?>
                            <a href="index.php" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="panel panel-table">
                <div class="search-container">
                    <h2>Database Mahasiswa</h2>
                    
                    <form method="get" class="search-box">
                        <input type="text" name="cari" value="<?= aman($keyword) ?>" placeholder="Cari NIM atau Nama...">
                        <?php if ($keyword !== ''): ?>
                            <a href="index.php" class="reset-link">Reset</a>
                        <?php endif; ?>
                        <button type="submit">Cari</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Identitas / NIM</th>
                                <th>Fakultas / Prodi</th>
                                <th>Asal Sekolah, Alamat & Email</th>
                                <th style="text-align: center;">Smstr</th>
                                <th>Hobi</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftarMahasiswa as $m): 
                                $badge = dapatkanBadgeTingkat($m);
                            ?>
                            <tr>
                                <td>
                                    <div class="mhs-nama"><?= aman($m->nama) ?></div>
                                    <div class="mhs-nim"><?= aman($m->nim) ?></div>
                                    <span class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #cbd5e1;"><?= aman($m->fakultas) ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;"><?= aman($m->prodi) ?></div>
                                </td>
                                <td>
                                    <div style="color: #cbd5e1;"><?= aman($m->asalSekolah) ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.15rem;"><?= aman($m->alamat ?? '-') ?></div>
                                    <div style="font-size: 0.75rem; color: #60a5fa; margin-top: 0.15rem;"><?= aman($m->email ?? '-') ?></div>
                                </td>
                                <td style="text-align: center; font-weight: bold; color: #f8fafc;"><?= $m->semester ?></td>
                                <td style="color: #94a3b8; font-style: italic;"><?= aman($m->hobi ?: '-') ?></td>
                                <td>
                                    <div class="action-cell">
                                        <a href="?edit=<?= urlencode($m->nim) ?>" class="btn-table btn-edit">Edit</a>
                                        <form method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data mahasiswa ini?')" style="display: inline;">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="nim" value="<?= aman($m->nim) ?>">
                                            <button type="submit" class="btn-table btn-delete">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$daftarMahasiswa): ?>
                            <tr>
                                <td colspan="6" style="padding: 2.5rem; text-align: center; color: #64748b; font-style: italic;">
                                    Tidak ditemukan data mahasiswa yang sesuai dengan kata kunci.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <footer>
                    <p>💡 Tip: Ketik NIM atau nama di kotak pencarian untuk menyaring data secara real-time.</p>
                    <p>Driver: <span class="state-label">PHP Session</span></p>
                </footer>
            </section>
        </div>
    </div>

</body>
</html>