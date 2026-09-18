<?php
session_start();

require_once __DIR__ . '/../config/koneksi.php';

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getInitial($name): string
{
    $name = trim((string) $name);

    if ($name === '') {
        return 'C';
    }

    $parts = preg_split('/\s+/', $name);

    if (count($parts) >= 2) {
        return strtoupper(
            substr($parts[0], 0, 1) .
            substr($parts[1], 0, 1)
        );
    }

    return strtoupper(substr($name, 0, 2));
}

/*
|--------------------------------------------------------------------------
| DATA CUSTOMER
|--------------------------------------------------------------------------
*/
$customer = [
    'nama' => $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Customer',
    'email' => $_SESSION['email'] ?? '-',
    'status_langganan' => 'belum_berlangganan',
    'paket_id' => null,
    'nama_paket' => '-',
    'speed_mbps' => 0
];

$sql = "
    SELECT
        c.nama,
        c.email,
        c.status_langganan,
        c.paket_id,
        p.nama_paket,
        p.speed_mbps
    FROM customers c
    LEFT JOIN paket_wifi p
        ON p.id = c.paket_id
    WHERE c.user_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $customer = array_merge($customer, $row);
    }

    mysqli_stmt_close($stmt);
}

/*
|--------------------------------------------------------------------------
| CUSTOMER DATA
|--------------------------------------------------------------------------
*/
$namaCustomer = $customer['nama'] ?: 'Customer';
$emailCustomer = $customer['email'] ?: '-';
$statusLangganan = strtolower(trim($customer['status_langganan'] ?? ''));

$namaPaket = $customer['nama_paket'] ?: '-';
$speedPaket = (float) ($customer['speed_mbps'] ?? 0);

$initial = getInitial($namaCustomer);

/*
|--------------------------------------------------------------------------
| STATUS AKTIF
|--------------------------------------------------------------------------
*/
$isActive = in_array(
    $statusLangganan,
    ['active', 'aktif'],
    true
);

/*
|--------------------------------------------------------------------------
| DEMO NETWORK DATA
|--------------------------------------------------------------------------
|
| Nilai ini bisa nanti diganti dengan data real dari API / MikroTik /
| server monitoring.
|
*/
$ping = 9;
$download = 87.4;
$upload = 18.2;

$networkStatus = 'Normal';
$connectionType = 'WiFi';
$serverLocation = 'Jakarta';
$ipAddress = '192.168.1.10';

$lastTest = 'Belum ada tes';
$testRecords = 12;

/*
|--------------------------------------------------------------------------
| DEMO HISTORY
|--------------------------------------------------------------------------
*/
$history = [
    [
        'date' => date('d M Y'),
        'time' => '20:15',
        'download' => '87.4',
        'upload' => '18.2',
        'ping' => '9'
    ],
    [
        'date' => date('d M Y', strtotime('-1 day')),
        'time' => '21:08',
        'download' => '82.6',
        'upload' => '17.9',
        'ping' => '11'
    ],
    [
        'date' => date('d M Y', strtotime('-2 day')),
        'time' => '19:42',
        'download' => '89.1',
        'upload' => '19.4',
        'ping' => '8'
    ],
    [
        'date' => date('d M Y', strtotime('-3 day')),
        'time' => '20:31',
        'download' => '85.7',
        'upload' => '18.8',
        'ping' => '10'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Status Jaringan - WiFi Management</title>

    <link
        rel="stylesheet"
        href="assets/css/network_status.css?v=1"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body>

<div class="network-layout">

    <!-- =====================================================
         SIDEBAR
    ====================================================== -->
    <aside class="sidebar">

        <div class="sidebar-logo">

            <div class="logo-icon">
                <i class="fa-solid fa-wifi"></i>
            </div>

            <div class="logo-text">
                <h5>WiFi Management</h5>
                <span>Customer Portal</span>
            </div>

        </div>

        <nav class="sidebar-menu">

            <div class="menu-title">
                MENU UTAMA
            </div>

            <a
                href="dashboard.php"
                class="menu-item"
            >
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>

            <a
                href="billing.php"
                class="menu-item"
            >
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <span>Tagihan</span>
            </a>

            <a
                href="usage.php"
                class="menu-item"
            >
                <i class="fa-solid fa-chart-line"></i>
                <span>Penggunaan</span>
            </a>

            <a
                href="speedtest.php"
                class="menu-item"
            >
                <i class="fa-solid fa-gauge-high"></i>
                <span>Speed Test</span>
            </a>

            <a
                href="network_status.php"
                class="menu-item active"
            >
                <i class="fa-solid fa-signal"></i>
                <span>Status Jaringan</span>
            </a>

            <a
                href="complaint.php"
                class="menu-item"
            >
                <i class="fa-solid fa-headset"></i>
                <span>Keluhan</span>
            </a>

            <a
                href="chat.php"
                class="menu-item"
            >
                <i class="fa-solid fa-comments"></i>
                <span>Chat</span>
            </a>

            <div class="menu-title menu-account">
                AKUN
            </div>

            <a
                href="profile.php"
                class="menu-item"
            >
                <i class="fa-solid fa-user"></i>
                <span>Profil</span>
            </a>

            <a
                href="notifikasi.php"
                class="menu-item"
            >
                <i class="fa-solid fa-bell"></i>
                <span>Notifikasi</span>
            </a>

            <a
                href="../logout.php"
                class="menu-item logout"
            >
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Keluar</span>
            </a>

        </nav>

        <!-- USER -->
        <div class="sidebar-user">

            <div class="user-avatar">
                <?= e($initial) ?>
            </div>

            <div class="user-detail">
                <strong><?= e($namaCustomer) ?></strong>
                <span>Customer</span>
            </div>

        </div>

    </aside>


    <!-- =====================================================
         MAIN
    ====================================================== -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">

            <div>
                <h4>Status Jaringan</h4>

                <span>
                    Pantau koneksi dan lakukan tes kecepatan internet
                </span>
            </div>

            <div class="topbar-right">

                <a
                    href="notifikasi.php"
                    class="topbar-action"
                    title="Notifikasi"
                >
                    <i class="fa-regular fa-bell"></i>
                </a>

                <div class="top-profile">

                    <div class="top-avatar">
                        <?= e($initial) ?>
                    </div>

                    <div class="top-user">
                        <strong><?= e($namaCustomer) ?></strong>
                        <span><?= e($emailCustomer) ?></span>
                    </div>

                </div>

            </div>

        </header>


        <!-- CONTENT -->
        <div class="content">

            <!-- PAGE HEADER -->
            <section class="page-header">

                <span class="page-label">
                    NETWORK & SPEED TEST
                </span>

                <h1>
                    Status Jaringan
                </h1>

                <p>
                    Periksa kondisi koneksi internet kamu dan lakukan
                    pengujian kecepatan untuk mengetahui performa jaringan
                    saat ini.
                </p>

            </section>


            <?php if (!$isActive): ?>

                <!-- =================================================
                     NOT ACTIVE
                ================================================== -->
                <section class="inactive-card">

                    <div class="inactive-icon">
                        <i class="fa-solid fa-wifi"></i>
                    </div>

                    <div class="inactive-content">

                        <span class="section-label">
                            INFORMASI
                        </span>

                        <h2>
                            Layanan Internet Belum Aktif
                        </h2>

                        <p>
                            Fitur status jaringan dan speed test akan
                            tersedia setelah layanan WiFi kamu aktif.
                        </p>

                        <div class="inactive-status">
                            <span></span>
                            Status:
                            <?= e(ucwords(str_replace('_', ' ', $statusLangganan))) ?>
                        </div>

                    </div>

                    <a
                        href="dashboard.php"
                        class="inactive-button"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                        Kembali ke Dashboard
                    </a>

                </section>

            <?php else: ?>

                <!-- =================================================
                     SPEED TEST
                ================================================== -->
                <section class="speedtest-card">

                    <!-- SPEEDOMETER -->
                    <div class="speedtest-main">

                        <div class="speedometer-wrapper">

                            <div
                                class="speedometer"
                                id="speedometer"
                            >

                                <div class="speedometer-inner">

                                    <span
                                        class="speed-status"
                                        id="speedStatus"
                                    >
                                        READY
                                    </span>

                                    <div class="speed-value">

                                        <strong id="speedValue">
                                            <?= number_format($download, 1) ?>
                                        </strong>

                                        <span>
                                            Mbps
                                        </span>

                                    </div>

                                    <small id="speedDescription">
                                        Tekan tombol untuk melakukan
                                        pengujian kecepatan
                                    </small>

                                </div>

                            </div>

                        </div>


                        <!-- BUTTON -->
                        <button
                            type="button"
                            class="start-test-btn"
                            id="startTestBtn"
                        >
                            <i class="fa-solid fa-bolt"></i>
                            <span>Mulai Tes Kecepatan</span>
                        </button>


                        <!-- PROGRESS -->
                        <div
                            class="speed-progress"
                            id="speedProgress"
                            style="display: none;"
                        >

                            <div class="progress-track">

                                <div
                                    class="progress-bar"
                                    id="progressBar"
                                ></div>

                            </div>

                            <div class="progress-info">

                                <span id="progressText">
                                    Mempersiapkan tes...
                                </span>

                                <span id="progressPercent">
                                    0%
                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- RESULTS -->
                    <div class="result-grid">

                        <!-- PING -->
                        <div class="result-card">

                            <div class="result-icon ping">
                                <i class="fa-solid fa-bolt"></i>
                            </div>

                            <div>

                                <span>
                                    Ping
                                </span>

                                <div class="result-number">

                                    <strong id="pingResult">
                                        <?= e($ping) ?>
                                    </strong>

                                    <small>ms</small>

                                </div>

                                <p>
                                    Latensi koneksi
                                </p>

                            </div>

                        </div>


                        <!-- DOWNLOAD -->
                        <div class="result-card">

                            <div class="result-icon download">
                                <i class="fa-solid fa-arrow-down"></i>
                            </div>

                            <div>

                                <span>
                                    Download
                                </span>

                                <div class="result-number">

                                    <strong id="downloadResult">
                                        <?= number_format($download, 1) ?>
                                    </strong>

                                    <small>Mbps</small>

                                </div>

                                <p>
                                    Kecepatan menerima data
                                </p>

                            </div>

                        </div>


                        <!-- UPLOAD -->
                        <div class="result-card">

                            <div class="result-icon upload">
                                <i class="fa-solid fa-arrow-up"></i>
                            </div>

                            <div>

                                <span>
                                    Upload
                                </span>

                                <div class="result-number">

                                    <strong id="uploadResult">
                                        <?= number_format($upload, 1) ?>
                                    </strong>

                                    <small>Mbps</small>

                                </div>

                                <p>
                                    Kecepatan mengirim data
                                </p>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                     CONNECTION
                ================================================== -->
                <section class="connection-card">

                    <div class="connection-header">

                        <div>

                            <span class="section-label">
                                CONNECTION
                            </span>

                            <h2>
                                Informasi Koneksi
                            </h2>

                            <p>
                                Informasi jaringan yang sedang digunakan.
                            </p>

                        </div>

                        <div class="online-status">
                            <span></span>
                            Online
                        </div>

                    </div>


                    <div class="connection-grid">

                        <div class="connection-item">

                            <span>
                                <i class="fa-solid fa-wifi"></i>
                                Status
                            </span>

                            <strong>
                                <?= e($networkStatus) ?>
                            </strong>

                        </div>


                        <div class="connection-item">

                            <span>
                                <i class="fa-solid fa-network-wired"></i>
                                Tipe Koneksi
                            </span>

                            <strong>
                                <?= e($connectionType) ?>
                            </strong>

                        </div>


                        <div class="connection-item">

                            <span>
                                <i class="fa-solid fa-location-dot"></i>
                                Server
                            </span>

                            <strong>
                                <?= e($serverLocation) ?>
                            </strong>

                        </div>


                        <div class="connection-item">

                            <span>
                                <i class="fa-solid fa-globe"></i>
                                IP Address
                            </span>

                            <strong>
                                <?= e($ipAddress) ?>
                            </strong>

                        </div>


                        <div class="connection-item">

                            <span>
                                <i class="fa-solid fa-box"></i>
                                Paket
                            </span>

                            <strong>
                                <?= e($namaPaket) ?>
                            </strong>

                        </div>


                        <div class="connection-item">

                            <span>
                                <i class="fa-solid fa-gauge-high"></i>
                                Kecepatan Paket
                            </span>

                            <strong>
                                <?= $speedPaket > 0
                                    ? e($speedPaket) . ' Mbps'
                                    : 'Tidak tersedia'
                                ?>
                            </strong>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                     USAGE
                ================================================== -->
                <section class="usage-card">

                    <div class="usage-header">

                        <div>

                            <span class="section-label">
                                NETWORK SUMMARY
                            </span>

                            <h2>
                                Ringkasan Pengujian
                            </h2>

                            <p>
                                Hasil pengujian jaringan terbaru.
                            </p>

                        </div>

                        <div class="usage-period">

                            <i class="fa-regular fa-clock"></i>

                            <span>
                                <?= e($lastTest) ?>
                            </span>

                        </div>

                    </div>


                    <div class="usage-summary-grid">

                        <div class="usage-summary-item download">

                            <i class="fa-solid fa-arrow-down"></i>

                            <span>
                                Download
                            </span>

                            <strong>
                                <span id="summaryDownload">
                                    <?= number_format($download, 1) ?>
                                </span>

                                <small>
                                    Mbps
                                </small>
                            </strong>

                        </div>


                        <div class="usage-summary-item upload">

                            <i class="fa-solid fa-arrow-up"></i>

                            <span>
                                Upload
                            </span>

                            <strong>
                                <span id="summaryUpload">
                                    <?= number_format($upload, 1) ?>
                                </span>

                                <small>
                                    Mbps
                                </small>
                            </strong>

                        </div>


                        <div class="usage-summary-item ping">

                            <i class="fa-solid fa-bolt"></i>

                            <span>
                                Ping
                            </span>

                            <strong>
                                <span id="summaryPing">
                                    <?= e($ping) ?>
                                </span>

                                <small>
                                    ms
                                </small>
                            </strong>

                        </div>


                        <div class="usage-summary-item records">

                            <i class="fa-solid fa-clock-rotate-left"></i>

                            <span>
                                Total Pengujian
                            </span>

                            <strong>
                                <?= e($testRecords) ?>

                                <small>
                                    kali
                                </small>
                            </strong>

                        </div>

                    </div>


                    <!-- HISTORY -->
                    <div class="usage-history">

                        <div class="usage-history-title">
                            Riwayat Speed Test
                        </div>

                        <?php if (!empty($history)): ?>

                            <div class="usage-history-list">

                                <div class="usage-history-row history-header">

                                    <strong>
                                        Waktu
                                    </strong>

                                    <strong>
                                        Download
                                    </strong>

                                    <strong>
                                        Upload
                                    </strong>

                                    <strong>
                                        Ping
                                    </strong>

                                </div>

                                <?php foreach ($history as $item): ?>

                                    <div class="usage-history-row">

                                        <span>
                                            <i class="fa-regular fa-calendar"></i>

                                            <?= e($item['date']) ?>

                                            <small>
                                                <?= e($item['time']) ?>
                                            </small>
                                        </span>

                                        <strong>
                                            <?= e($item['download']) ?> Mbps
                                        </strong>

                                        <strong>
                                            <?= e($item['upload']) ?> Mbps
                                        </strong>

                                        <strong>
                                            <?= e($item['ping']) ?> ms
                                        </strong>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <div class="usage-empty">
                                Belum ada riwayat speed test.
                            </div>

                        <?php endif; ?>

                    </div>

                </section>


                <!-- =================================================
                     TIPS
                ================================================== -->
                <section class="tips-card">

                    <div class="tips-icon">
                        <i class="fa-solid fa-lightbulb"></i>
                    </div>

                    <div>

                        <strong>
                            Tips mendapatkan hasil speed test yang akurat
                        </strong>

                        <p>
                            Tutup aplikasi yang sedang menggunakan internet,
                            gunakan perangkat yang dekat dengan router,
                            dan hindari aktivitas download atau streaming
                            ketika melakukan pengujian.
                        </p>

                    </div>

                </section>

            <?php endif; ?>

        </div>

    </main>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const button = document.getElementById('startTestBtn');

    if (!button) {
        return;
    }

    const speedometer = document.getElementById('speedometer');
    const speedValue = document.getElementById('speedValue');
    const speedStatus = document.getElementById('speedStatus');
    const speedDescription = document.getElementById('speedDescription');

    const progress = document.getElementById('speedProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const progressPercent = document.getElementById('progressPercent');

    const pingResult = document.getElementById('pingResult');
    const downloadResult = document.getElementById('downloadResult');
    const uploadResult = document.getElementById('uploadResult');

    const summaryPing = document.getElementById('summaryPing');
    const summaryDownload = document.getElementById('summaryDownload');
    const summaryUpload = document.getElementById('summaryUpload');

    button.addEventListener('click', function () {

        button.disabled = true;

        button.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i>' +
            '<span>Sedang Menguji...</span>';

        progress.style.display = 'block';

        speedStatus.textContent = 'TESTING';
        speedDescription.textContent =
            'Sedang mengukur performa koneksi internet...';

        let percent = 0;

        const stages = [
            'Menghubungkan ke server...',
            'Mengukur ping...',
            'Mengukur download...',
            'Mengukur upload...',
            'Menyelesaikan pengujian...'
        ];

        const timer = setInterval(function () {

            percent += Math.floor(Math.random() * 7) + 3;

            if (percent >= 100) {
                percent = 100;
            }

            progressBar.style.width = percent + '%';
            progressPercent.textContent = percent + '%';

            const stageIndex = Math.min(
                Math.floor(percent / 20),
                stages.length - 1
            );

            progressText.textContent = stages[stageIndex];

            /*
             * Animasi speedometer.
             */
            const simulatedSpeed =
                15 + (percent / 100) * 72;

            speedValue.textContent =
                simulatedSpeed.toFixed(1);

            const degree =
                Math.min(simulatedSpeed / 100, 1) * 360;

            speedometer.style.background =
                'conic-gradient(' +
                'var(--primary) 0deg ' +
                degree +
                'deg, ' +
                '#e8edf5 ' +
                degree +
                'deg 360deg)';

            if (percent >= 100) {

                clearInterval(timer);

                setTimeout(function () {

                    const finalDownload =
                        (82 + Math.random() * 10).toFixed(1);

                    const finalUpload =
                        (17 + Math.random() * 3).toFixed(1);

                    const finalPing =
                        Math.floor(7 + Math.random() * 6);

                    speedValue.textContent =
                        finalDownload;

                    pingResult.textContent =
                        finalPing;

                    downloadResult.textContent =
                        finalDownload;

                    uploadResult.textContent =
                        finalUpload;

                    summaryPing.textContent =
                        finalPing;

                    summaryDownload.textContent =
                        finalDownload;

                    summaryUpload.textContent =
                        finalUpload;

                    speedStatus.textContent =
                        'COMPLETED';

                    speedDescription.textContent =
                        'Pengujian berhasil diselesaikan.';

                    progressText.textContent =
                        'Tes selesai';

                    button.disabled = false;

                    button.innerHTML =
                        '<i class="fa-solid fa-rotate"></i>' +
                        '<span>Tes Lagi</span>';

                }, 500);
            }

        }, 250);

    });

});
</script>

</body>
</html>