<?php
/**
 * Export Database Tables to SQL File
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helper.php';

$system = $_GET['system'] ?? 'supermarket';
$download = isset($_GET['download']);

$pdo = ($system === 'perpajakan') ? Database::getPerpajakan() : Database::getSupermarket();
$dbName = ($system === 'perpajakan') ? DB_NAME_PERPAJAKAN : DB_NAME_SUPERMARKET;

if (!$pdo) {
    die("Gagal menghubungkan ke database: " . Database::getError());
}

$tables = ($system === 'perpajakan') 
    ? ['kategori_pajak', 'jenis_pajak', 'pengguna', 'pembayaran_pajak']
    : ['karyawan', 'kasir', 'pelanggan', 'produk', 'transaksi', 'detail_transaksi'];

$sql = "-- ==========================================\n";
$sql .= "-- EXPORT DUMP DATABASE: " . strtoupper($dbName) . "\n";
$sql .= "-- Waktu Ekspor: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- PHP Version: " . phpversion() . " | MariaDB / MySQL\n";
$sql .= "-- ==========================================\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table) {
    $stmt = $pdo->query("SELECT * FROM `$table`");
    $rows = $stmt->fetchAll();

    if (!empty($rows)) {
        $sql .= "-- --------------------------------------------------------\n";
        $sql .= "-- Data untuk tabel `$table`\n";
        $sql .= "-- --------------------------------------------------------\n";

        $columns = array_keys($rows[0]);
        $colNames = implode('`, `', $columns);

        $sql .= "INSERT INTO `$table` (`$colNames`) VALUES\n";

        $valRows = [];
        foreach ($rows as $row) {
            $vals = [];
            foreach ($row as $val) {
                if ($val === null) {
                    $vals[] = "NULL";
                } elseif (is_numeric($val)) {
                    $vals[] = $val;
                } else {
                    $vals[] = "'" . addslashes((string)$val) . "'";
                }
            }
            $valRows[] = "(" . implode(', ', $vals) . ")";
        }

        $sql .= implode(",\n", $valRows) . ";\n\n";
    }
}

$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

if ($download) {
    $filename = $system . "_dump_" . date('Ymd_His') . ".sql";
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql));
    echo $sql;
    exit;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $sql;
    exit;
}
