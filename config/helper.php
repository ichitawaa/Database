<?php
/**
 * Helper Functions (Formatting, Session, ID Generator, Flash Message)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Format angka ke format Rupiah (Rp xx.xxx)
 */
function formatRupiah($angka): string {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format tanggal Indonesia
 */
function formatTanggalIndo(?string $datetime): string {
    if (!$datetime) return "-";
    $time = strtotime($datetime);
    if (!$time) return $datetime;
    return date('d/m/Y H:i', $time);
}

/**
 * Konversi angka ke ucapan terbilang bahasa Indonesia
 */
function terbilang($angka): string {
    $bilangan = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    $n = (int)abs($angka);

    if ($n < 12) return $bilangan[$n];
    if ($n < 20) return terbilang($n - 10) . " Belas";
    if ($n < 100) return terbilang(floor($n / 10)) . " Puluh " . terbilang($n % 10);
    if ($n < 200) return "Seratus " . terbilang($n - 100);
    if ($n < 1000) return terbilang(floor($n / 100)) . " Ratus " . terbilang($n % 100);
    if ($n < 2000) return "Seribu " . terbilang($n - 1000);
    if ($n < 1000000) return terbilang(floor($n / 1000)) . " Ribu " . terbilang($n % 1000);
    if ($n < 1000000000) return terbilang(floor($n / 1000000)) . " Juta " . terbilang($n % 1000000);
    if ($n < 1000000000000) return terbilang(floor($n / 1000000000)) . " Miliar " . terbilang($n % 1000000000);
    return (string)$n;
}

/**
 * Simpan notifikasi flash message ke session
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Ambil notifikasi flash message dan hapus dari session
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generate ID otomatis berurutan (contoh: PRD-011, KAS-05, USR-007)
 */
function generateNextId(string $prefix, string $table, string $pkColumn, PDO $pdo, int $padLength = 3): string {
    $stmt = $pdo->query("SELECT `$pkColumn` FROM `$table` ORDER BY `$pkColumn` DESC LIMIT 1");
    $lastId = $stmt->fetchColumn();

    if (!$lastId) {
        return $prefix . "-" . str_pad("1", $padLength, "0", STR_PAD_LEFT);
    }

    // Ambil angka dari ID terakhir
    preg_match('/\d+$/', $lastId, $matches);
    $nextNum = isset($matches[0]) ? ((int)$matches[0] + 1) : 1;
    return $prefix . "-" . str_pad((string)$nextNum, $padLength, "0", STR_PAD_LEFT);
}

/**
 * Sanitasi string input
 */
function sanitize($input): string {
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}
