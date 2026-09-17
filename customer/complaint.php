<?php

session_start();

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

date_default_timezone_set('Asia/Jakarta');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function formatTanggal($tanggal): string
{
    if (empty($tanggal)) {
        return '-';
    }

    return date('d M Y, H:i', strtotime($tanggal));
}


function statusClass($status): string
{
    $status = strtolower(trim((string) $status));

    return match ($status) {
        'open', 'baru' => 'status-open',
        'process', 'proses', 'diproses' => 'status-process',
        'closed', 'selesai' => 'status-closed',
        default => 'status-default',
    };
}


function statusLabel($status): string
{
    $status = strtolower(trim((string) $status));

    return match ($status) {
        'open', 'baru' => 'Baru',
        'process', 'proses', 'diproses' => 'Diproses',
        'closed', 'selesai' => 'Selesai',
        default => ucfirst(str_replace('_', ' ', $status)),
    };
}


function normalizeSubscriptionStatus($status): string
{
    $status = strtolower(trim((string) $status));

    return match ($status) {
        'aktif', 'active' => 'active',
        'pending', 'proses', 'menunggu_pemasangan' => 'pending',
        'belum_berlangganan' => 'belum_berlangganan',
        default => $status,
    };
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header("Location: ../login.php");
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
        c.user_id,
        c.nama,
        c.telephone,
        c.email,
        c.alamat,
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

$resultCustomer = $stmt->get_result();
$customer = $resultCustomer->fetch_assoc();

$stmt->close();


if (!$customer) {
    die("Data customer tidak ditemukan.");
}


$customerId = (int) $customer['id'];

$namaCustomer = $customer['nama'] ?? 'Customer';

$avatarInitial = strtoupper(
    mb_substr(trim($namaCustomer), 0, 1)
);

$statusLangganan = normalizeSubscriptionStatus(
    $customer['status_langganan'] ?? ''
);

$namaPaket = $customer['nama_paket'] ?? 'Belum memilih paket';

$speedPaket = $customer['speed_mbps'] ?? null;


/*
|--------------------------------------------------------------------------
| AMBIL DATA COMPLAINT
|--------------------------------------------------------------------------
*/

$complaints = [];

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
    WHERE customer_id = ?
    ORDER BY id DESC
");

$stmt->bind_param("i", $customerId);
$stmt->execute();

$resultComplaint = $stmt->get_result();

while ($row = $resultComplaint->fetch_assoc()) {
    $complaints[] = $row;
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| STATISTIK
|--------------------------------------------------------------------------
*/

$totalComplaint = count($complaints);

$openComplaint = 0;
$processComplaint = 0;
$closedComplaint = 0;

foreach ($complaints as $item) {

    $status = strtolower(
        trim((string) ($item['status'] ?? ''))
    );

    if (in_array($status, ['open', 'baru'], true)) {
        $openComplaint++;
    }

    if (in_array($status, ['process', 'proses', 'diproses'], true)) {
        $processComplaint++;
    }

    if (in_array($status, ['closed', 'selesai'], true)) {
        $closedComplaint++;
    }
}


/*
|--------------------------------------------------------------------------
| JUMLAH COMPLAINT AKTIF
|--------------------------------------------------------------------------
*/

$activeComplaint =
    $openComplaint +
    $processComplaint;


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
        AND (
            is_read = 0
            OR is_read IS NULL
        )
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $notificationResult = $stmt->get_result();

    $notificationData =
        $notificationResult->fetch_assoc();

    $notificationCount =
        (int) ($notificationData['total'] ?? 0);

    $stmt->close();

} catch (Throwable $e) {

    $notificationCount = 0;
}


$currentPage = 'complaint';

?>

<!DOCTYPE html>

<html lang="id">

<head>

```
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Keluhan Saya - WiFi Management</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<link
    rel="stylesheet"
    href="assets/css/dashboard.css"
>


<link
    rel="stylesheet"
    href="assets/css/complaint.css?v=2"
>
```

</head>

<body>

<div class="customer-layout">

```
<!-- ==========================================================
     SIDEBAR
=========================================================== -->

<aside class="sidebar" id="sidebar">


    <div class="sidebar-brand">

        <div class="brand-logo">
            W
        </div>

        <div class="brand-text">

            <strong>
                WiFi Management
            </strong>

            <small>
                Customer Portal
            </small>

        </div>

        <button
            type="button"
            class="sidebar-close"
            id="sidebarClose"
        >
            &times;
        </button>

    </div>


    <div class="sidebar-scroll">


        <div class="menu-section">

            <div class="menu-label">
                MENU UTAMA
            </div>


            <a
                href="dashboard.php"
                class="menu-item"
            >
                <span class="menu-icon">⌂</span>
                <span class="menu-text">
                    Dashboard
                </span>
            </a>


            <?php if ($statusLangganan === 'active'): ?>

                <a
                    href="billing.php"
                    class="menu-item"
                >
                    <span class="menu-icon">▣</span>
                    <span class="menu-text">
                        Tagihan
                    </span>
                </a>


                <a
                    href="usage.php"
                    class="menu-item"
                >
                    <span class="menu-icon">◔</span>
                    <span class="menu-text">
                        Pemakaian
                    </span>
                </a>


                <a
                    href="speedtest.php"
                    class="menu-item"
                >
                    <span class="menu-icon">◉</span>
                    <span class="menu-text">
                        Speed Test
                    </span>
                </a>


                <a
                    href="complaint.php"
                    class="menu-item active"
                >
                    <span class="menu-icon">⚠</span>

                    <span class="menu-text">
                        Keluhan
                    </span>

                    <?php if ($activeComplaint > 0): ?>

                        <span class="menu-badge">
                            <?= $activeComplaint ?>
                        </span>

                    <?php endif; ?>

                </a>


                <a
                    href="chat.php"
                    class="menu-item"
                >
                    <span class="menu-icon">☏</span>
                    <span class="menu-text">
                        Chat
                    </span>
                </a>


                <a
                    href="network_status.php"
                    class="menu-item"
                >
                    <span class="menu-icon">⌁</span>
                    <span class="menu-text">
                        Status Jaringan
                    </span>
                </a>


                <a
                    href="upgrade.php"
                    class="menu-item"
                >
                    <span class="menu-icon">↑</span>
                    <span class="menu-text">
                        Upgrade Paket
                    </span>
                </a>


                <a
                    href="service_request.php"
                    class="menu-item"
                >
                    <span class="menu-icon">⚙</span>
                    <span class="menu-text">
                        Permintaan Layanan
                    </span>
                </a>

            <?php endif; ?>

        </div>


        <div class="menu-section">

            <div class="menu-label">
                AKUN
            </div>


            <a
                href="profile.php"
                class="menu-item"
            >
                <span class="menu-icon">♙</span>
                <span class="menu-text">
                    Profil Saya
                </span>
            </a>


            <a
                href="notifikasi.php"
                class="menu-item"
            >
                <span class="menu-icon">🔔</span>
                <span class="menu-text">
                    Notifikasi
                </span>

                <?php if ($notificationCount > 0): ?>

                    <span class="menu-badge">
                        <?= $notificationCount ?>
                    </span>

                <?php endif; ?>

            </a>


            <a
                href="../logout.php"
                class="menu-item logout-item"
            >
                <span class="menu-icon">⇥</span>
                <span class="menu-text">
                    Keluar
                </span>
            </a>

        </div>

    </div>


    <div class="sidebar-user">

        <div class="sidebar-user-avatar">
            <?= e($avatarInitial) ?>
        </div>

        <div class="sidebar-user-info">

            <strong>
                <?= e($namaCustomer) ?>
            </strong>

            <small>
                Customer
            </small>

        </div>

    </div>

</aside>


<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- ==========================================================
     MAIN CONTENT
=========================================================== -->

<main class="main-content">


    <header class="topbar">

        <div class="topbar-left">

            <button
                type="button"
                class="mobile-menu-button"
                id="mobileMenuButton"
            >
                ☰
            </button>

            <div class="breadcrumb-text">
                Customer Portal / Keluhan
            </div>

        </div>


        <div class="topbar-right">

            <a
                href="notifikasi.php"
                class="notification-button"
            >
                🔔

                <?php if ($notificationCount > 0): ?>

                    <span class="notification-pulse">
                        <?= $notificationCount ?>
                    </span>

                <?php endif; ?>

            </a>


            <a
                href="profile.php"
                class="top-profile"
            >

                <div class="top-profile-avatar">
                    <?= e($avatarInitial) ?>
                </div>

                <div class="top-profile-info">

                    <strong>
                        <?= e($namaCustomer) ?>
                    </strong>

                    <small>
                        Customer
                    </small>

                </div>

            </a>

        </div>

    </header>


    <!-- ======================================================
         PAGE
    ======================================================= -->

    <div class="complaint-page">


        <!-- HEADER -->

        <div class="complaint-header">

            <div>

                <span class="page-eyebrow">
                    CUSTOMER SERVICE
                </span>

                <h1>
                    Keluhan Saya
                </h1>

                <p>
                    Pantau laporan gangguan layanan WiFi Anda.
                </p>

            </div>


            <?php if ($statusLangganan === 'active'): ?>

                <a
                    href="complaint_create.php"
                    class="btn-new-complaint"
                >
                    <span>＋</span>
                    Buat Keluhan
                </a>

            <?php endif; ?>

        </div>


        <!-- CUSTOMER INFO -->

        <div class="customer-info-card">

            <div class="customer-info-icon">
                ◉
            </div>


            <div class="customer-info-content">

                <strong>
                    <?= e($namaCustomer) ?>
                </strong>

                <span>

                    Paket:
                    <?= e($namaPaket) ?>

                    <?php if ($speedPaket): ?>

                        · <?= e($speedPaket) ?> Mbps

                    <?php endif; ?>

                </span>

            </div>


            <div class="customer-status">

                <span class="status-dot"></span>

                <?= $statusLangganan === 'active'
                    ? 'Layanan Aktif'
                    : e(ucfirst(
                        str_replace(
                            '_',
                            ' ',
                            $statusLangganan
                        )
                    ))
                ?>

            </div>

        </div>


        <!-- STATISTIK -->

        <div class="complaint-stats">


            <div class="complaint-stat-card">

                <div class="stat-icon stat-icon-total">
                    ☷
                </div>

                <div>

                    <span>
                        Total Keluhan
                    </span>

                    <strong>
                        <?= $totalComplaint ?>
                    </strong>

                </div>

            </div>


            <div class="complaint-stat-card">

                <div class="stat-icon stat-icon-open">
                    !
                </div>

                <div>

                    <span>
                        Keluhan Baru
                    </span>

                    <strong>
                        <?= $openComplaint ?>
                    </strong>

                </div>

            </div>


            <div class="complaint-stat-card">

                <div class="stat-icon stat-icon-process">
                    ↻
                </div>

                <div>

                    <span>
                        Diproses
                    </span>

                    <strong>
                        <?= $processComplaint ?>
                    </strong>

                </div>

            </div>


            <div class="complaint-stat-card">

                <div class="stat-icon stat-icon-closed">
                    ✓
                </div>

                <div>

                    <span>
                        Selesai
                    </span>

                    <strong>
                        <?= $closedComplaint ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- LIST -->

        <section class="complaint-card">


            <div class="complaint-card-header">

                <div>

                    <h2>
                        Riwayat Keluhan
                    </h2>

                    <p>
                        Daftar laporan yang telah Anda kirim.
                    </p>

                </div>


                <span class="complaint-total">

                    <?= $totalComplaint ?>
                    laporan

                </span>

            </div>


            <?php if (empty($complaints)): ?>


                <div class="complaint-empty">

                    <div class="empty-icon">
                        ✓
                    </div>

                    <h3>
                        Belum Ada Keluhan
                    </h3>

                    <p>
                        Anda belum memiliki laporan gangguan.
                    </p>


                    <?php if ($statusLangganan === 'active'): ?>

                        <a
                            href="complaint_create.php"
                            class="btn-new-complaint"
                        >
                            Buat Keluhan
                        </a>

                    <?php endif; ?>

                </div>


            <?php else: ?>


                <div class="complaint-list">


                    <?php foreach ($complaints as $item): ?>


                        <article class="complaint-item">


                            <div class="complaint-item-main">


                                <div class="complaint-item-top">

                                    <span class="complaint-id">
                                        #<?= (int) $item['id'] ?>
                                    </span>


                                    <span
                                        class="complaint-status <?= e(
                                            statusClass(
                                                $item['status']
                                            )
                                        ) ?>"
                                    >
                                        <?= e(
                                            statusLabel(
                                                $item['status']
                                            )
                                        ) ?>
                                    </span>

                                </div>


                                <h3>
                                    <?= e(
                                        $item['judul']
                                    ) ?>
                                </h3>


                                <p class="complaint-description">

                                    <?= e(
                                        $item['deskripsi']
                                    ) ?>

                                </p>


                                <div class="complaint-meta">


                                    <?php if (!empty($item['alamat'])): ?>

                                        <span>
                                            📍
                                            <?= e(
                                                $item['alamat']
                                            ) ?>
                                        </span>

                                    <?php endif; ?>


                                    <?php if (
                                        $item['latitude'] !== null &&
                                        $item['longitude'] !== null
                                    ): ?>

                                        <span>
                                            • Lokasi tersedia
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <div class="complaint-item-action">

                                <a
                                    href="complaint_detail.php?id=<?= (int) $item['id'] ?>"
                                    class="btn-detail"
                                >
                                    Detail
                                    <span>→</span>
                                </a>

                            </div>

                        </article>


                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


        </section>


        <!-- HELP -->

        <div class="complaint-help">

            <div class="help-icon">
                ?
            </div>


            <div class="help-content">

                <strong>
                    Butuh bantuan lebih lanjut?
                </strong>

                <p>
                    Hubungi customer service melalui fitur chat
                    jika membutuhkan bantuan tambahan.
                </p>

            </div>


            <a
                href="chat.php"
                class="help-button"
            >
                Hubungi CS
            </a>

        </div>


    </div>


    <!-- FOOTER -->

    <footer class="customer-footer">

        <span>
            © <?= date('Y') ?> WiFi Management
        </span>

        <span>
            Customer Portal
        </span>

    </footer>


</main>
```

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const sidebar =
        document.getElementById('sidebar');

    const overlay =
        document.getElementById('sidebarOverlay');

    const menuButton =
        document.getElementById('mobileMenuButton');

    const closeButton =
        document.getElementById('sidebarClose');


    function openSidebar() {

        if (sidebar) {
            sidebar.classList.add('show');
        }

        if (overlay) {
            overlay.classList.add('show');
        }

        document.body.classList.add('sidebar-open');
    }


    function closeSidebar() {

        if (sidebar) {
            sidebar.classList.remove('show');
        }

        if (overlay) {
            overlay.classList.remove('show');
        }

        document.body.classList.remove('sidebar-open');
    }


    if (menuButton) {
        menuButton.addEventListener(
            'click',
            openSidebar
        );
    }


    if (closeButton) {
        closeButton.addEventListener(
            'click',
            closeSidebar
        );
    }


    if (overlay) {
        overlay.addEventListener(
            'click',
            closeSidebar
        );
    }


    window.addEventListener(
        'resize',
        function () {

            if (window.innerWidth > 991) {
                closeSidebar();
            }

        }
    );

});

</script>

</body>

</html>
