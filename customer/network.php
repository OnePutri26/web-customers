<?php

session_start();

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

date_default_timezone_set('Asia/Jakarta');

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header("Location: ../login.php");
    exit;
}

/* =====================================================
   HELPER
===================================================== */

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* =====================================================
   DATA CUSTOMER
===================================================== */

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.nama,
        c.email,
        c.paket_id,
        c.status_langganan,
        p.nama_paket,
        p.speed_mbps
    FROM customers c
    LEFT JOIN paket_wifi p ON p.id = c.paket_id
    WHERE c.user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) {
    die("Data customer tidak ditemukan.");
}

$nama = $customer['nama'] ?? 'Customer';

$initial = strtoupper(
    substr(trim($nama), 0, 1)
);

if ($initial === '') {
    $initial = 'C';
}

$customerId = (int)($customer['id'] ?? 0);

$paketNama = $customer['nama_paket'] ?? 'Belum ada paket';

$paketSpeed = isset($customer['speed_mbps']) && $customer['speed_mbps'] !== null
    ? (float)$customer['speed_mbps']
    : 0;

$statusLangganan = $customer['status_langganan'] ?? '';

/* =====================================================
   SERVER INFO
===================================================== */

$serverName = $_SERVER['SERVER_NAME'] ?? 'Local Server';
$serverIp   = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';

/* =====================================================
   PING ENDPOINT
   Dipakai oleh JavaScript untuk mengukur response time.
===================================================== */

if (isset($_GET['ping'])) {
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Pragma: no-cache");
    http_response_code(204);
    exit;
}

/* =====================================================
   USAGE SUMMARY
===================================================== */

$usageAverage = [
    'download' => 0,
    'upload' => 0,
    'ping' => 0,
    'records' => 0
];

$usageHistory = [];

$usageSummaryStatement = $conn->prepare("
    SELECT
        COALESCE(AVG(download_mbps), 0) AS average_download,
        COALESCE(AVG(upload_mbps), 0) AS average_upload,
        COALESCE(AVG(ping_ms), 0) AS average_ping,
        COUNT(*) AS total_records
    FROM usage_data
    WHERE customer_id = ?
      AND recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");

$usageSummaryStatement->bind_param("i", $customerId);
$usageSummaryStatement->execute();
$usageSummary = $usageSummaryStatement->get_result()->fetch_assoc();
$usageSummaryStatement->close();

if ($usageSummary) {
    $usageAverage = [
        'download' => (float) ($usageSummary['average_download'] ?? 0),
        'upload' => (float) ($usageSummary['average_upload'] ?? 0),
        'ping' => (float) ($usageSummary['average_ping'] ?? 0),
        'records' => (int) ($usageSummary['total_records'] ?? 0)
    ];
}

$usageHistoryStatement = $conn->prepare("
    SELECT download_mbps, upload_mbps, ping_ms, recorded_at
    FROM usage_data
    WHERE customer_id = ?
    ORDER BY recorded_at DESC
    LIMIT 5
");

$usageHistoryStatement->bind_param("i", $customerId);
$usageHistoryStatement->execute();
$usageHistoryResult = $usageHistoryStatement->get_result();

while ($usageRow = $usageHistoryResult->fetch_assoc()) {
    $usageHistory[] = $usageRow;
}

$usageHistoryStatement->close();

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Network & Speed Test - Customer Portal</title>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="network.css?v=1"
    >
</head>

<body>

<div class="network-layout">

    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">

        <div class="sidebar-logo">
            <div class="logo-icon">
                <i class="bi bi-wifi"></i>
            </div>

            <div class="logo-text">
                <h5>WiFi Management</h5>
                <span>Customer Portal</span>
            </div>
        </div>

        <nav class="sidebar-menu">

            <p class="menu-title">MENU</p>

            <a href="dashboard.php" class="menu-item">
                <i class="bi bi-grid-fill"></i>
                <span>Dashboard</span>
            </a>

            <a href="billing.php" class="menu-item">
                <i class="bi bi-credit-card-fill"></i>
                <span>Tagihan</span>
            </a>

            <a href="network.php" class="menu-item active">
                <i class="bi bi-bar-chart-fill"></i>
                <span>Network, Pemakaian &amp; Speed Test</span>
            </a>

            <a href="complaint.php" class="menu-item">
                <i class="bi bi-tools"></i>
                <span>Gangguan</span>
            </a>

            <a href="network_status.php" class="menu-item">
                <i class="bi bi-globe2"></i>
                <span>Status Jaringan</span>
            </a>

            <a href="chat.php" class="menu-item">
                <i class="bi bi-chat-dots-fill"></i>
                <span>Chat CS</span>
            </a>

            <p class="menu-title menu-account">AKUN</p>

            <a href="upgrade.php" class="menu-item">
                <i class="bi bi-arrow-up-circle-fill"></i>
                <span>Upgrade Paket</span>
            </a>

            <a href="service_request.php" class="menu-item">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Layanan Tambahan</span>
            </a>

            <a href="profile.php" class="menu-item">
                <i class="bi bi-person-circle"></i>
                <span>Profile Saya</span>
            </a>

            <a href="../logout.php" class="menu-item logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>

        </nav>

        <div class="sidebar-user">
            <div class="user-avatar">
                <?= e($initial) ?>
            </div>

            <div class="user-detail">
                <strong><?= e($nama) ?></strong>
                <span>Customer</span>
            </div>
        </div>

    </aside>


    <!-- =================================================
         MAIN
    ================================================== -->

    <main class="main-content">

        <header class="topbar">

            <div>
                <h4>Network, Pemakaian &amp; Speed Test</h4>
                <span>Periksa jaringan, pemakaian, dan kecepatan koneksi internet kamu.</span>
            </div>

            <div class="topbar-right">

                <a
                    href="chat.php"
                    class="topbar-action"
                    title="Chat CS"
                    aria-label="Chat CS"
                >
                    <i class="bi bi-headset"></i>
                </a>

                <a
                    href="notifikasi.php"
                    class="topbar-action"
                    title="Notifikasi"
                    aria-label="Notifikasi"
                >
                    <i class="bi bi-bell"></i>
                </a>

                <a href="profile.php" class="top-profile">

                    <div class="top-avatar">
                        <?= e($initial) ?>
                    </div>

                    <div class="top-user">
                        <strong><?= e($nama) ?></strong>
                        <span>Customer</span>
                    </div>

                </a>

            </div>

        </header>


        <div class="content">

            <!-- PAGE HEADER -->

            <div class="page-header">

                <span class="page-label">INTERNET TOOLS</span>

                <h1>Network, Pemakaian &amp; Speed Test</h1>

                <p>
                    Uji kecepatan internet, lihat ringkasan pemakaian,
                    dan pantau informasi koneksi yang sedang digunakan.
                </p>

            </div>


            <!-- =================================================
                 SPEED TEST CARD
            ================================================== -->

            <section class="speedtest-card">

                <div class="speedtest-main">

                    <div class="speedometer-wrapper">

                        <div class="speedometer" id="speedometer">

                            <div class="speedometer-inner">

                                <span class="speed-status" id="testStatus">
                                    SIAP
                                </span>

                                <div class="speed-value">
                                    <strong id="speedValue">0</strong>
                                    <span>Mbps</span>
                                </div>

                                <small id="testDescription">
                                    Tekan tombol untuk memulai
                                </small>

                            </div>

                        </div>

                    </div>


                    <button
                        type="button"
                        id="startTest"
                        class="start-test-btn"
                    >
                        <i class="bi bi-lightning-charge-fill"></i>
                        <span id="startText">Mulai Speed Test</span>
                    </button>


                    <div class="speed-progress">

                        <div class="progress-track">
                            <div
                                class="progress-bar"
                                id="progressBar"
                            ></div>
                        </div>

                        <div class="progress-info">
                            <span id="progressText">0%</span>
                            <span id="phaseText">Menunggu</span>
                        </div>

                    </div>

                </div>


                <!-- RESULT -->

                <div class="result-grid">

                    <div class="result-card">
                        <div class="result-icon ping">
                            <i class="bi bi-broadcast-pin"></i>
                        </div>

                        <div>
                            <span>Ping</span>

                            <div class="result-number">
                                <strong id="pingValue">--</strong>
                                <small>ms</small>
                            </div>

                            <p id="pingStatus">Belum diuji</p>
                        </div>
                    </div>


                    <div class="result-card">
                        <div class="result-icon download">
                            <i class="bi bi-download"></i>
                        </div>

                        <div>
                            <span>Download</span>

                            <div class="result-number">
                                <strong id="downloadValue">--</strong>
                                <small>Mbps</small>
                            </div>

                            <p id="downloadStatus">Belum diuji</p>
                        </div>
                    </div>


                    <div class="result-card">
                        <div class="result-icon upload">
                            <i class="bi bi-upload"></i>
                        </div>

                        <div>
                            <span>Upload</span>

                            <div class="result-number">
                                <strong id="uploadValue">--</strong>
                                <small>Mbps</small>
                            </div>

                            <p id="uploadStatus">Belum diuji</p>
                        </div>
                    </div>

                </div>

            </section>


            <!-- =================================================
                 CONNECTION INFO
            ================================================== -->

            <section class="connection-card">

                <div class="connection-header">

                    <div>
                        <span class="section-label">CONNECTION</span>

                        <h2>Informasi Koneksi</h2>

                        <p>
                            Detail customer, paket internet, dan server.
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
                            <i class="bi bi-person"></i>
                            Customer
                        </span>
                        <strong><?= e($nama) ?></strong>
                    </div>

                    <div class="connection-item">
                        <span>
                            <i class="bi bi-router"></i>
                            Paket
                        </span>
                        <strong><?= e($paketNama) ?></strong>
                    </div>

                    <div class="connection-item">
                        <span>
                            <i class="bi bi-speedometer2"></i>
                            Kecepatan Paket
                        </span>
                        <strong>
                            <?= $paketSpeed > 0
                                ? e(number_format($paketSpeed, 0, ',', '.')) . ' Mbps'
                                : 'Belum tersedia'
                            ?>
                        </strong>
                    </div>

                    <div class="connection-item">
                        <span>
                            <i class="bi bi-hdd-network"></i>
                            Server
                        </span>
                        <strong><?= e($serverName) ?></strong>
                    </div>

                    <div class="connection-item">
                        <span>
                            <i class="bi bi-globe2"></i>
                            Server IP
                        </span>
                        <strong><?= e($serverIp) ?></strong>
                    </div>

                    <div class="connection-item">
                        <span>
                            <i class="bi bi-clock"></i>
                            Waktu Test
                        </span>
                        <strong id="testTime">-</strong>
                    </div>

                </div>

            </section>


            <!-- =================================================
                 USAGE SUMMARY
            ================================================== -->

            <section class="usage-card">

                <div class="usage-header">

                    <div>
                        <span class="section-label">PEMAKAIAN INTERNET</span>
                        <h2>Ringkasan Pemakaian</h2>
                        <p>Rata-rata hasil pengukuran koneksi selama 7 hari terakhir.</p>
                    </div>

                    <span class="usage-period">
                        <i class="bi bi-calendar3"></i>
                        7 hari terakhir
                    </span>

                </div>


                <div class="usage-summary-grid">

                    <div class="usage-summary-item download">
                        <i class="bi bi-arrow-down-circle-fill"></i>
                        <span>Download</span>
                        <strong><?= number_format($usageAverage['download'], 2, ',', '.') ?> <small>Mbps</small></strong>
                    </div>

                    <div class="usage-summary-item upload">
                        <i class="bi bi-arrow-up-circle-fill"></i>
                        <span>Upload</span>
                        <strong><?= number_format($usageAverage['upload'], 2, ',', '.') ?> <small>Mbps</small></strong>
                    </div>

                    <div class="usage-summary-item ping">
                        <i class="bi bi-speedometer2"></i>
                        <span>Ping</span>
                        <strong><?= number_format($usageAverage['ping'], 1, ',', '.') ?> <small>ms</small></strong>
                    </div>

                    <div class="usage-summary-item records">
                        <i class="bi bi-activity"></i>
                        <span>Pengukuran</span>
                        <strong><?= e($usageAverage['records']) ?> <small>kali</small></strong>
                    </div>

                </div>


                <div class="usage-history">
                    <div class="usage-history-title">Riwayat Terakhir</div>

                    <?php if (empty($usageHistory)): ?>
                        <p class="usage-empty">Belum ada data pemakaian yang tersimpan.</p>
                    <?php else: ?>
                        <div class="usage-history-list">
                            <?php foreach ($usageHistory as $usageRow): ?>
                                <div class="usage-history-row">
                                    <span><?= e(date('d M Y, H:i', strtotime($usageRow['recorded_at']))) ?></span>
                                    <strong><i class="bi bi-download"></i> <?= number_format((float) $usageRow['download_mbps'], 2, ',', '.') ?> Mbps</strong>
                                    <strong><i class="bi bi-upload"></i> <?= number_format((float) $usageRow['upload_mbps'], 2, ',', '.') ?> Mbps</strong>
                                    <strong><i class="bi bi-broadcast-pin"></i> <?= number_format((float) $usageRow['ping_ms'], 0, ',', '.') ?> ms</strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </section>


            <!-- =================================================
                 TIPS
            ================================================== -->

            <div class="tips-card">

                <div class="tips-icon">
                    <i class="bi bi-lightbulb-fill"></i>
                </div>

                <div>
                    <strong>Tips mendapatkan hasil yang akurat</strong>

                    <p>
                        Pastikan tidak ada download, streaming, atau
                        perangkat lain yang sedang menggunakan koneksi
                        secara berat ketika melakukan speed test.
                    </p>
                </div>

            </div>

        </div>

    </main>

</div>


<script>

/* =====================================================
   ELEMENT
===================================================== */

const startButton = document.getElementById("startTest");
const startText = document.getElementById("startText");

const speedValue = document.getElementById("speedValue");
const speedometer = document.getElementById("speedometer");

const testStatus = document.getElementById("testStatus");
const testDescription = document.getElementById("testDescription");

const pingValue = document.getElementById("pingValue");
const downloadValue = document.getElementById("downloadValue");
const uploadValue = document.getElementById("uploadValue");

const pingStatus = document.getElementById("pingStatus");
const downloadStatus = document.getElementById("downloadStatus");
const uploadStatus = document.getElementById("uploadStatus");

const progressBar = document.getElementById("progressBar");
const progressText = document.getElementById("progressText");
const phaseText = document.getElementById("phaseText");

const testTime = document.getElementById("testTime");


/* =====================================================
   PROGRESS
===================================================== */

function setProgress(percent, phase)
{
    percent = Math.max(0, Math.min(100, percent));

    progressBar.style.width = percent + "%";
    progressText.textContent = Math.round(percent) + "%";
    phaseText.textContent = phase;
}


/* =====================================================
   SPEEDOMETER
===================================================== */

function setSpeed(speed)
{
    const value = Number(speed) || 0;

    speedValue.textContent = value.toFixed(2);

    /*
     * Skala visual:
     * 100 Mbps = satu putaran penuh.
     * Jika lebih dari 100 Mbps, indikator tetap penuh.
     */

    const maxSpeed = 100;

    const percentage = Math.min(value / maxSpeed, 1);
    const degree = percentage * 360;

    speedometer.style.background =
        `conic-gradient(
            #2563eb ${degree}deg,
            #e8edf5 ${degree}deg
        )`;
}


/* =====================================================
   PING
===================================================== */

async function testPing()
{
    const samples = [];

    const url =
        window.location.href.split("?")[0]
        + "?ping=1&_="
        + Date.now();

    for (let i = 0; i < 4; i++) {

        const start = performance.now();

        try {

            await fetch(url, {
                method: "HEAD",
                cache: "no-store"
            });

            const end = performance.now();

            samples.push(end - start);

        } catch (error) {

            console.error(error);

        }

    }

    if (samples.length === 0) {
        throw new Error("Ping gagal");
    }

    return samples.reduce((a, b) => a + b, 0) / samples.length;
}


/* =====================================================
   DOWNLOAD
===================================================== */

async function testDownload()
{
    const url =
        "speedtest-download.php?_="
        + Date.now();

    const start = performance.now();

    const response = await fetch(url, {
        cache: "no-store"
    });

    if (!response.ok) {
        throw new Error("Download test gagal");
    }

    const blob = await response.blob();

    const end = performance.now();

    const seconds = (end - start) / 1000;

    const bits = blob.size * 8;

    return bits / seconds / 1000000;
}


/* =====================================================
   UPLOAD
===================================================== */

async function testUpload()
{
    /*
     * Data 5 MB.
     */

    const size = 5 * 1024 * 1024;

    const data = new Uint8Array(size);

    const start = performance.now();

    const response = await fetch(
        "speedtest-upload.php?_=" + Date.now(),
        {
            method: "POST",

            headers: {
                "Content-Type": "application/octet-stream"
            },

            body: data,

            cache: "no-store"
        }
    );

    if (!response.ok) {
        throw new Error("Upload test gagal");
    }

    await response.text();

    const end = performance.now();

    const seconds = (end - start) / 1000;

    const bits = size * 8;

    return bits / seconds / 1000000;
}


/* =====================================================
   STATUS HELPERS
===================================================== */

function getPingStatus(ping)
{
    if (ping < 30) return "Sangat baik";
    if (ping < 60) return "Baik";
    if (ping < 100) return "Cukup";
    return "Tinggi";
}

function getDownloadStatus(speed)
{
    if (speed >= 50) return "Sangat cepat";
    if (speed >= 20) return "Cepat";
    if (speed >= 10) return "Cukup";
    return "Lambat";
}

function getUploadStatus(speed)
{
    if (speed >= 20) return "Sangat baik";
    if (speed >= 10) return "Baik";
    if (speed >= 5) return "Cukup";
    return "Lambat";
}


/* =====================================================
   START TEST
===================================================== */

startButton.addEventListener("click", async function()
{
    if (startButton.disabled) {
        return;
    }

    startButton.disabled = true;

    startText.textContent = "Sedang Menguji...";

    testStatus.textContent = "TESTING";
    testDescription.textContent = "Mohon tunggu...";

    testTime.textContent =
        new Date().toLocaleTimeString("id-ID");

    /*
     * RESET RESULT
     */

    pingValue.textContent = "--";
    downloadValue.textContent = "--";
    uploadValue.textContent = "--";

    pingStatus.textContent = "Sedang menguji...";
    downloadStatus.textContent = "Menunggu...";
    uploadStatus.textContent = "Menunggu...";

    setSpeed(0);

    setProgress(0, "Memulai test");


    try {

        /* PING */

        setProgress(10, "Mengukur ping");

        const ping = await testPing();

        pingValue.textContent = ping.toFixed(0);
        pingStatus.textContent = getPingStatus(ping);

        setProgress(30, "Ping selesai");


        /* DOWNLOAD */

        downloadStatus.textContent = "Sedang menguji...";

        setProgress(35, "Mengukur download");

        const download = await testDownload();

        downloadValue.textContent = download.toFixed(2);
        downloadStatus.textContent = getDownloadStatus(download);

        setSpeed(download);

        setProgress(65, "Download selesai");


        /* UPLOAD */

        uploadStatus.textContent = "Sedang menguji...";

        setProgress(70, "Mengukur upload");

        const upload = await testUpload();

        uploadValue.textContent = upload.toFixed(2);
        uploadStatus.textContent = getUploadStatus(upload);

        setSpeed(upload);

        setProgress(100, "Test selesai");


        /* SELESAI */

        testStatus.textContent = "SELESAI";
        testDescription.textContent = "Pengujian berhasil";

        startText.textContent = "Test Lagi";

    } catch (error) {

        console.error(error);

        testStatus.textContent = "ERROR";
        testDescription.textContent = "Speed test gagal";

        phaseText.textContent = "Terjadi kesalahan";

        startText.textContent = "Coba Lagi";

    } finally {

        startButton.disabled = false;

    }

});

</script>

</body>
</html>
