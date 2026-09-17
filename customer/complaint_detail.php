<?php

session_start();

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function statusClass(string $status): string
{
    $status = strtolower(trim($status));

    return match ($status) {
        'open', 'baru' => 'status-open',
        'process', 'proses', 'diproses' => 'status-process',
        'closed', 'selesai' => 'status-closed',
        default => 'status-default'
    };
}

function statusLabel(string $status): string
{
    $status = strtolower(trim($status));

    return match ($status) {
        'open', 'baru' => 'Terbuka',
        'process', 'proses', 'diproses' => 'Diproses',
        'closed', 'selesai' => 'Selesai',
        default => ucfirst($status)
    };
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$complaintId = (int) ($_GET['id'] ?? 0);

if ($userId <= 0 || $complaintId <= 0) {
    header("Location: complaint.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATA CUSTOMER
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.nama,
        c.status_langganan,
        c.paket_id,
        p.nama_paket,
        p.speed_mbps
    FROM customers c
    LEFT JOIN paket_wifi p
        ON p.id = c.paket_id
    WHERE c.user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) {
    header("Location: dashboard.php");
    exit;
}

$customerId = (int) $customer['id'];

/*
|--------------------------------------------------------------------------
| DATA COMPLAINT
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        customer_id,
        judul,
        deskripsi,
        alamat,
        latitude,
        longitude,
        status
    FROM complaint
    WHERE id = ?
      AND customer_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $complaintId, $customerId);
$stmt->execute();

$complaint = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$complaint) {
    header("Location: complaint.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

$status = strtolower(trim((string) $complaint['status']));

/*
|--------------------------------------------------------------------------
| KOORDINAT
|--------------------------------------------------------------------------
*/

$latitude = $complaint['latitude'];
$longitude = $complaint['longitude'];

$hasLocation =
    $latitude !== null &&
    $longitude !== null &&
    $latitude !== '' &&
    $longitude !== '';

$mapsUrl = '';

if ($hasLocation) {
    $mapsUrl = "https://www.google.com/maps?q="
        . rawurlencode($latitude . ',' . $longitude);
}

/*
|--------------------------------------------------------------------------
| NOTIFIKASI
|--------------------------------------------------------------------------
*/

$notificationCount = 0;

try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM notifikasi
        WHERE user_id = ?
          AND is_read = 0
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();
    $notificationCount = (int) ($result['total'] ?? 0);

    $stmt->close();
} catch (Throwable $e) {
    $notificationCount = 0;
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

    <title>Detail Keluhan - WiFi Management</title>

    <link
        rel="stylesheet"
        href="../assets/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/complaint_detail.css"
    >
</head>

<body>

<div class="dashboard-layout">

    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <aside class="sidebar">

        <div class="sidebar-header">

            <div class="brand-logo">
                <img
                    src="../assets/images/logo-yesnet.png"
                    alt="Logo"
                >
            </div>

            <div class="brand-text">
                <strong>WiFi Management</strong>
                <span>Customer Portal</span>
            </div>

        </div>

        <nav class="sidebar-menu">

            <a href="dashboard.php">
                <span>🏠</span>
                <span>Dashboard</span>
            </a>

            <?php if (
                in_array(
                    strtolower(trim($customer['status_langganan'] ?? '')),
                    ['active', 'aktif']
                )
            ): ?>

                <a href="billing.php">
                    <span>💳</span>
                    <span>Tagihan</span>
                </a>

                <a href="usage.php">
                    <span>📊</span>
                    <span>Pemakaian</span>
                </a>

                <a href="speedtest.php">
                    <span>🚀</span>
                    <span>Speed Test</span>
                </a>

                <a
                    href="complaint.php"
                    class="active"
                >
                    <span>🛠️</span>
                    <span>Keluhan</span>
                </a>

                <a href="network_status.php">
                    <span>🌐</span>
                    <span>Status Jaringan</span>
                </a>

                <a href="chat.php">
                    <span>💬</span>
                    <span>Chat</span>
                </a>

                <a href="upgrade.php">
                    <span>⬆️</span>
                    <span>Upgrade Paket</span>
                </a>

                <a href="service_request.php">
                    <span>📋</span>
                    <span>Permintaan Layanan</span>
                </a>

            <?php elseif (
                in_array(
                    strtolower(trim($customer['status_langganan'] ?? '')),
                    ['pending', 'proses', 'menunggu_pemasangan']
                )
            ): ?>

                <a href="installation.php">
                    <span>🔧</span>
                    <span>Pemasangan</span>
                </a>

            <?php else: ?>

                <a href="langganan.php">
                    <span>📦</span>
                    <span>Pilih Paket</span>
                </a>

            <?php endif; ?>

            <a href="profile.php">
                <span>👤</span>
                <span>Profil</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a
                href="../logout.php"
                class="logout-link"
            >
                <span>🚪</span>
                <span>Logout</span>
            </a>

        </div>

    </aside>


    <!-- =========================================================
         MAIN CONTENT
    ========================================================== -->

    <main class="main-content">

        <!-- TOPBAR -->

        <header class="topbar">

            <div class="topbar-left">

                <button
                    type="button"
                    class="sidebar-toggle"
                    id="sidebarToggle"
                >
                    ☰
                </button>

                <div>
                    <h1>Detail Keluhan</h1>
                    <p>
                        Informasi lengkap keluhan kamu
                    </p>
                </div>

            </div>

            <div class="topbar-right">

                <a
                    href="notifikasi.php"
                    class="notification-button"
                    title="Notifikasi"
                >
                    🔔

                    <?php if ($notificationCount > 0): ?>
                        <span class="notification-badge">
                            <?= $notificationCount > 99 ? '99+' : $notificationCount ?>
                        </span>
                    <?php endif; ?>

                </a>

                <a
                    href="profile.php"
                    class="user-profile"
                >
                    <div class="user-avatar">
                        <?= e(strtoupper(substr($customer['nama'], 0, 1))) ?>
                    </div>

                    <div class="user-info">
                        <strong><?= e($customer['nama']) ?></strong>
                        <span>Customer</span>
                    </div>
                </a>

            </div>

        </header>


        <!-- PAGE -->

        <section class="complaint-detail-page">

            <div class="page-header">

                <div>
                    <a
                        href="complaint.php"
                        class="back-link"
                    >
                        ← Kembali ke Keluhan
                    </a>

                    <h2><?= e($complaint['judul']) ?></h2>

                    <p>
                        Detail laporan keluhan #<?= e($complaint['id']) ?>
                    </p>
                </div>

                <span
                    class="status-badge <?= e(statusClass($status)) ?>"
                >
                    <?= e(statusLabel($status)) ?>
                </span>

            </div>


            <div class="detail-grid">

                <!-- INFORMASI KELUHAN -->

                <div class="detail-card">

                    <div class="card-title">
                        <span class="title-icon">📝</span>

                        <div>
                            <h3>Informasi Keluhan</h3>
                            <p>Detail laporan yang kamu kirim</p>
                        </div>
                    </div>

                    <div class="detail-content">

                        <div class="detail-item">

                            <span class="detail-label">
                                Judul Keluhan
                            </span>

                            <strong>
                                <?= e($complaint['judul']) ?>
                            </strong>

                        </div>

                        <div class="detail-item">

                            <span class="detail-label">
                                Deskripsi
                            </span>

                            <div class="description-box">
                                <?= nl2br(e($complaint['deskripsi'])) ?>
                            </div>

                        </div>

                        <div class="detail-item">

                            <span class="detail-label">
                                Status
                            </span>

                            <span
                                class="status-badge <?= e(statusClass($status)) ?>"
                            >
                                <?= e(statusLabel($status)) ?>
                            </span>

                        </div>

                    </div>

                </div>


                <!-- LOKASI -->

                <div class="detail-card">

                    <div class="card-title">

                        <span class="title-icon">📍</span>

                        <div>
                            <h3>Lokasi Keluhan</h3>
                            <p>Lokasi yang dikirim saat laporan dibuat</p>
                        </div>

                    </div>

                    <div class="location-content">

                        <div class="location-address">

                            <span class="detail-label">
                                Alamat
                            </span>

                            <p>
                                <?= nl2br(e($complaint['alamat'])) ?>
                            </p>

                        </div>


                        <?php if ($hasLocation): ?>

                            <div class="coordinates">

                                <div>
                                    <span>Latitude</span>
                                    <strong>
                                        <?= e($latitude) ?>
                                    </strong>
                                </div>

                                <div>
                                    <span>Longitude</span>
                                    <strong>
                                        <?= e($longitude) ?>
                                    </strong>
                                </div>

                            </div>

                            <a
                                href="<?= e($mapsUrl) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="maps-button"
                            >
                                📍 Buka di Google Maps
                            </a>

                        <?php else: ?>

                            <div class="no-location">
                                📍 Koordinat lokasi tidak tersedia.
                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- DATA PELANGGAN -->

                <div class="detail-card customer-card">

                    <div class="card-title">

                        <span class="title-icon">👤</span>

                        <div>
                            <h3>Data Pelanggan</h3>
                            <p>Informasi pelanggan yang membuat laporan</p>
                        </div>

                    </div>

                    <div class="customer-info-grid">

                        <div>
                            <span>Nama</span>
                            <strong>
                                <?= e($customer['nama']) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Paket</span>
                            <strong>
                                <?= e(
                                    $customer['nama_paket']
                                    ?: 'Belum ada paket'
                                ) ?>
                            </strong>
                        </div>

                        <?php if (!empty($customer['speed_mbps'])): ?>

                            <div>
                                <span>Kecepatan</span>
                                <strong>
                                    <?= e($customer['speed_mbps']) ?> Mbps
                                </strong>
                            </div>

                        <?php endif; ?>

                        <div>
                            <span>Status Langganan</span>
                            <strong>
                                <?= e(
                                    ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $customer['status_langganan']
                                        )
                                    )
                                ) ?>
                            </strong>
                        </div>

                    </div>

                </div>

            </div>


            <!-- FOOTER ACTION -->

            <div class="detail-actions">

                <a
                    href="complaint.php"
                    class="btn-secondary"
                >
                    ← Kembali
                </a>

                <?php if ($status === 'open' || $status === 'baru'): ?>

                    <a
                        href="chat.php"
                        class="btn-primary"
                    >
                        💬 Hubungi Customer Service
                    </a>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>


<script>
document.addEventListener("DOMContentLoaded", function () {

    const toggle = document.getElementById("sidebarToggle");
    const sidebar = document.querySelector(".sidebar");

    if (toggle && sidebar) {

        toggle.addEventListener("click", function () {
            sidebar.classList.toggle("show");
        });

    }

});
</script>

</body>
</html>
