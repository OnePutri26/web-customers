<?php
session_start();

/*
|--------------------------------------------------------------------------
| Koneksi Database
|--------------------------------------------------------------------------
| Sesuaikan dengan file koneksi milik kamu.
*/
require_once 'config/koneksi.php';

/*
|--------------------------------------------------------------------------
| Data user
|--------------------------------------------------------------------------
*/
$role = $_SESSION['role'] ?? 'admin';
$nama = $_SESSION['nama'] ?? 'Admin';

/*
|--------------------------------------------------------------------------
| Filter
|--------------------------------------------------------------------------
*/
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');

/*
|--------------------------------------------------------------------------
| Query Billing
|--------------------------------------------------------------------------
| Sesuaikan nama tabel/kolom dengan database kamu.
*/
$where = [];

if ($status !== 'all') {
    $statusEscaped = mysqli_real_escape_string($conn, $status);
    $where[] = "b.status = '$statusEscaped'";
}

if ($search !== '') {
    $searchEscaped = mysqli_real_escape_string($conn, $search);

    $where[] = "(
        b.invoice LIKE '%$searchEscaped%'
        OR w.nama LIKE '%$searchEscaped%'
        OR w.nik LIKE '%$searchEscaped%'
    )";
}

$whereSQL = '';

if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

/*
|--------------------------------------------------------------------------
| Ambil Data Billing
|--------------------------------------------------------------------------
*/
$query = "
    SELECT
        b.id,
        b.invoice,
        b.periode,
        b.jatuh_tempo,
        b.nominal,
        b.status,
        b.metode_pembayaran,
        w.nama,
        w.nik
    FROM billing b
    LEFT JOIN warga w ON w.id = b.warga_id
    $whereSQL
    ORDER BY b.jatuh_tempo DESC
";

$result = mysqli_query($conn, $query);

/*
|--------------------------------------------------------------------------
| Statistik
|--------------------------------------------------------------------------
*/
$totalTagihan = 0;
$totalLunas = 0;
$totalBelumLunas = 0;
$totalTerlambat = 0;
$totalNominal = 0;

$countQuery = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'lunas' THEN 1 ELSE 0 END) AS lunas,
        SUM(CASE WHEN status = 'belum_lunas' THEN 1 ELSE 0 END) AS belum_lunas,
        SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) AS terlambat,
        COALESCE(SUM(nominal), 0) AS nominal
    FROM billing
";

$countResult = mysqli_query($conn, $countQuery);

if ($countResult) {
    $stats = mysqli_fetch_assoc($countResult);

    $totalTagihan = (int) ($stats['total'] ?? 0);
    $totalLunas = (int) ($stats['lunas'] ?? 0);
    $totalBelumLunas = (int) ($stats['belum_lunas'] ?? 0);
    $totalTerlambat = (int) ($stats['terlambat'] ?? 0);
    $totalNominal = (float) ($stats['nominal'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| Format Rupiah
|--------------------------------------------------------------------------
*/
function rupiah($nominal)
{
    return 'Rp ' . number_format(
        (float) $nominal,
        0,
        ',',
        '.'
    );
}

/*
|--------------------------------------------------------------------------
| Badge Status
|--------------------------------------------------------------------------
*/
function statusBadge($status)
{
    switch ($status) {
        case 'lunas':
            return '<span class="status-badge status-lunas">
                        <span class="status-dot"></span>
                        Lunas
                    </span>';

        case 'terlambat':
            return '<span class="status-badge status-terlambat">
                        <span class="status-dot"></span>
                        Terlambat
                    </span>';

        default:
            return '<span class="status-badge status-belum">
                        <span class="status-dot"></span>
                        Belum Lunas
                    </span>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Billing | ISP Dashboard</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Billing CSS -->
    <link
        rel="stylesheet"
        href="css/billing.css"
    >
</head>

<body>

<div class="billing-page">

    <!-- HEADER -->
    <div class="billing-header">

        <div>
            <div class="breadcrumb-text">
                Dashboard / Billing
            </div>

            <h1>
                Billing Pelanggan
            </h1>

            <p>
                Kelola tagihan, pembayaran, dan status billing pelanggan.
            </p>
        </div>

        <div class="header-actions">

            <a
                href="billing_tambah.php"
                class="btn-add"
            >
                <i class="bi bi-plus-lg"></i>
                Buat Tagihan
            </a>

        </div>

    </div>


    <!-- STATISTICS -->
    <div class="billing-stat-grid">

        <div class="billing-stat-card">

            <div class="stat-icon icon-blue">
                <i class="bi bi-receipt"></i>
            </div>

            <div class="stat-content">
                <span>Total Tagihan</span>

                <strong>
                    <?= number_format($totalTagihan) ?>
                </strong>

                <small>
                    Semua invoice
                </small>
            </div>

        </div>


        <div class="billing-stat-card">

            <div class="stat-icon icon-green">
                <i class="bi bi-check-circle"></i>
            </div>

            <div class="stat-content">
                <span>Sudah Lunas</span>

                <strong>
                    <?= number_format($totalLunas) ?>
                </strong>

                <small>
                    Pembayaran berhasil
                </small>
            </div>

        </div>


        <div class="billing-stat-card">

            <div class="stat-icon icon-orange">
                <i class="bi bi-clock-history"></i>
            </div>

            <div class="stat-content">
                <span>Belum Lunas</span>

                <strong>
                    <?= number_format($totalBelumLunas) ?>
                </strong>

                <small>
                    Menunggu pembayaran
                </small>
            </div>

        </div>


        <div class="billing-stat-card">

            <div class="stat-icon icon-red">
                <i class="bi bi-exclamation-circle"></i>
            </div>

            <div class="stat-content">
                <span>Terlambat</span>

                <strong>
                    <?= number_format($totalTerlambat) ?>
                </strong>

                <small>
                    Melewati jatuh tempo
                </small>
            </div>

        </div>

    </div>


    <!-- REVENUE -->
    <div class="revenue-card">

        <div class="revenue-left">

            <div class="revenue-icon">
                <i class="bi bi-wallet2"></i>
            </div>

            <div>
                <span>Total Nilai Tagihan</span>

                <h2>
                    <?= rupiah($totalNominal) ?>
                </h2>
            </div>

        </div>

        <div class="revenue-label">
            <i class="bi bi-graph-up-arrow"></i>
            Billing periode berjalan
        </div>

    </div>


    <!-- TABLE CARD -->
    <div class="billing-card">

        <!-- TABLE HEADER -->
        <div class="billing-card-header">

            <div>
                <h3>
                    Daftar Tagihan
                </h3>

                <p>
                    Daftar invoice pelanggan
                </p>
            </div>

            <div class="table-tools">

                <!-- SEARCH -->
                <form
                    method="GET"
                    class="billing-search"
                >

                    <i class="bi bi-search"></i>

                    <input
                        type="search"
                        name="q"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Cari invoice / pelanggan..."
                    >

                    <?php if ($status !== 'all'): ?>
                        <input
                            type="hidden"
                            name="status"
                            value="<?= htmlspecialchars($status) ?>"
                        >
                    <?php endif; ?>

                </form>


                <!-- FILTER -->
                <form
                    method="GET"
                    class="status-filter"
                >

                    <?php if ($search !== ''): ?>
                        <input
                            type="hidden"
                            name="q"
                            value="<?= htmlspecialchars($search) ?>"
                        >
                    <?php endif; ?>

                    <select
                        name="status"
                        onchange="this.form.submit()"
                    >

                        <option
                            value="all"
                            <?= $status === 'all' ? 'selected' : '' ?>
                        >
                            Semua Status
                        </option>

                        <option
                            value="lunas"
                            <?= $status === 'lunas' ? 'selected' : '' ?>
                        >
                            Lunas
                        </option>

                        <option
                            value="belum_lunas"
                            <?= $status === 'belum_lunas' ? 'selected' : '' ?>
                        >
                            Belum Lunas
                        </option>

                        <option
                            value="terlambat"
                            <?= $status === 'terlambat' ? 'selected' : '' ?>
                        >
                            Terlambat
                        </option>

                    </select>

                </form>

            </div>

        </div>


        <!-- TABLE -->
        <div class="table-wrapper">

            <table class="billing-table">

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Invoice</th>
                        <th>Pelanggan</th>
                        <th>Periode</th>
                        <th>Jatuh Tempo</th>
                        <th>Nominal</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>

                </thead>

                <tbody>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <td>
                                <?= $no++ ?>
                            </td>

                            <td>
                                <div class="invoice-number">
                                    <?= htmlspecialchars($row['invoice'] ?? '-') ?>
                                </div>
                            </td>

                            <td>

                                <div class="customer-cell">

                                    <div class="customer-avatar">
                                        <?= strtoupper(
                                            substr(
                                                $row['nama'] ?? 'P',
                                                0,
                                                1
                                            )
                                        ) ?>
                                    </div>

                                    <div>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $row['nama'] ?? 'Pelanggan'
                                            ) ?>
                                        </strong>

                                        <small>
                                            NIK:
                                            <?= htmlspecialchars(
                                                $row['nik'] ?? '-'
                                            ) ?>
                                        </small>

                                    </div>

                                </div>

                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row['periode'] ?? '-'
                                ) ?>
                            </td>

                            <td>

                                <span class="due-date">
                                    <i class="bi bi-calendar3"></i>

                                    <?= !empty($row['jatuh_tempo'])
                                        ? date(
                                            'd M Y',
                                            strtotime($row['jatuh_tempo'])
                                        )
                                        : '-'
                                    ?>
                                </span>

                            </td>

                            <td>

                                <strong class="nominal">
                                    <?= rupiah($row['nominal'] ?? 0) ?>
                                </strong>

                            </td>

                            <td>

                                <span class="payment-method">
                                    <?= htmlspecialchars(
                                        $row['metode_pembayaran']
                                        ?? 'Belum ada'
                                    ) ?>
                                </span>

                            </td>

                            <td>
                                <?= statusBadge(
                                    $row['status'] ?? 'belum_lunas'
                                ) ?>
                            </td>

                            <td>

                                <div class="action-buttons">

                                    <a
                                        href="billing_detail.php?id=<?= (int) $row['id'] ?>"
                                        class="action-btn btn-view"
                                        title="Detail"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a
                                        href="billing_edit.php?id=<?= (int) $row['id'] ?>"
                                        class="action-btn btn-edit"
                                        title="Edit"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <a
                                        href="billing_hapus.php?id=<?= (int) $row['id'] ?>"
                                        class="action-btn btn-delete"
                                        title="Hapus"
                                        onclick="return confirm('Yakin ingin menghapus invoice ini?')"
                                    >
                                        <i class="bi bi-trash3"></i>
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="9"
                            class="empty-state"
                        >

                            <div class="empty-icon">
                                <i class="bi bi-receipt-cutoff"></i>
                            </div>

                            <strong>
                                Belum ada data billing
                            </strong>

                            <span>
                                Data tagihan yang sesuai akan muncul di sini.
                            </span>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>


        <!-- FOOTER -->
        <div class="billing-card-footer">

            <span>
                Menampilkan data billing
            </span>

            <div class="footer-info">
                <i class="bi bi-info-circle"></i>
                Pastikan status pembayaran selalu diperbarui.
            </div>

        </div>

    </div>

</div>

</body>
</html>
