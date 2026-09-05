<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

$userId = $_SESSION['user_id'];


/* =====================================================
   DATA CUSTOMER
===================================================== */

$stmt = $conn->prepare("
    SELECT *
    FROM customers
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$customer = $stmt->get_result()->fetch_assoc();

if (!$customer) {
    die("Data customer tidak ditemukan.");
}

$customerId = $customer['id'];

$nama = $customer['nama'] ?? 'Customer';

$initial = strtoupper(
    substr(trim($nama), 0, 1)
);


/* =====================================================
   TOTAL INSTALASI
===================================================== */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM instalasi
    WHERE id_customer = ?
");

$stmt->bind_param("i", $customerId);
$stmt->execute();

$totalInstalasi =
    $stmt->get_result()->fetch_assoc()['total'] ?? 0;


/* =====================================================
   TOTAL COMPLAINT
===================================================== */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM complaint
    WHERE id_customer = ?
    AND status NOT IN ('closed', 'resolved')
");

$stmt->bind_param("i", $customerId);
$stmt->execute();

$totalComplaint =
    $stmt->get_result()->fetch_assoc()['total'] ?? 0;


/* =====================================================
   TAGIHAN
===================================================== */

$tagihan = null;

try {

    $stmt = $conn->prepare("
        SELECT *
        FROM tagihan
        WHERE id_customer = ?
        AND status IN ('unpaid', 'pending')
        ORDER BY jatuh_tempo ASC
        LIMIT 1
    ");

    $stmt->bind_param("i", $customerId);
    $stmt->execute();

    $tagihan = $stmt->get_result()->fetch_assoc();

} catch (Exception $e) {

    $tagihan = null;

}


/* =====================================================
   RIWAYAT PEMBAYARAN
===================================================== */

$payments = [];

try {

    $stmt = $conn->prepare("
        SELECT *
        FROM pembayaran
        WHERE id_customer = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");

    $stmt->bind_param("i", $customerId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }

} catch (Exception $e) {

    $payments = [];

}


/* =====================================================
   DATA PAKET
===================================================== */

$paketNama =
    $customer['paket'] ??
    $customer['nama_paket'] ??
    'Internet Home';

$paketSpeed =
    $customer['speed'] ??
    $customer['kecepatan'] ??
    '100 Mbps';


/* =====================================================
   FORMAT RUPIAH
===================================================== */

function rupiah($angka)
{
    return 'Rp ' . number_format(
        (float)$angka,
        0,
        ',',
        '.'
    );
}


/* =====================================================
   DATA TAGIHAN
===================================================== */

$jumlahTagihan =
    $tagihan['jumlah'] ??
    $tagihan['amount'] ??
    0;

$tanggalJatuhTempo =
    $tagihan['jatuh_tempo'] ??
    '-';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Customer Dashboard
    </title>


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


    <!-- Customer Dashboard CSS -->

    <link
        rel="stylesheet"
        href="assets/css/customer-dashboard.css"
    >

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">

    <div class="sidebar-logo">

        <div class="logo-icon">

            <i class="bi bi-wifi"></i>

        </div>

        <div class="logo-text">

            <h5>
                WiFi Management
            </h5>

            <span>
                Customer Portal
            </span>

        </div>

    </div>


    <div class="sidebar-menu">

        <p class="menu-title">
            MENU
        </p>


        <a
            href="dashboard.php"
            class="menu-item active"
        >

            <i class="bi bi-grid-fill"></i>

            <span>
                Dashboard
            </span>

        </a>


        <a
            href="billing.php"
            class="menu-item"
        >

            <i class="bi bi-credit-card-fill"></i>

            <span>
                Tagihan
            </span>

        </a>


        <a
            href="usage.php"
            class="menu-item"
        >

            <i class="bi bi-speedometer2"></i>

            <span>
                Pemakaian
            </span>

        </a>


        <a
            href="speedtest.php"
            class="menu-item"
        >

            <i class="bi bi-lightning-charge-fill"></i>

            <span>
                Speed Test
            </span>

        </a>


        <a
            href="complaint.php"
            class="menu-item"
        >

            <i class="bi bi-tools"></i>

            <span>
                Gangguan
            </span>

        </a>


        <a
            href="network_status.php"
            class="menu-item"
        >

            <i class="bi bi-globe2"></i>

            <span>
                Status Jaringan
            </span>

        </a>


        <a
            href="chat.php"
            class="menu-item"
        >

            <i class="bi bi-chat-dots-fill"></i>

            <span>
                Chat CS
            </span>

        </a>


        <p class="menu-title menu-account">
            AKUN
        </p>


        <a
            href="upgrade.php"
            class="menu-item"
        >

            <i class="bi bi-arrow-up-circle-fill"></i>

            <span>
                Upgrade Paket
            </span>

        </a>


        <a
            href="service_request.php"
            class="menu-item"
        >

            <i class="bi bi-plus-circle-fill"></i>

            <span>
                Layanan Tambahan
            </span>

        </a>


        <a
            href="profile.php"
            class="menu-item"
        >

            <i class="bi bi-person-circle"></i>

            <span>
                Profile Saya
            </span>

        </a>


        <a
            href="../logout.php"
            class="menu-item logout"
        >

            <i class="bi bi-box-arrow-right"></i>

            <span>
                Logout
            </span>

        </a>

    </div>


    <!-- USER -->

    <div class="sidebar-user">

        <div class="user-avatar">

            <?= htmlspecialchars($initial) ?>

        </div>

        <div class="user-detail">

            <strong>
                <?= htmlspecialchars($nama) ?>
            </strong>

            <span>
                Customer
            </span>

        </div>

    </div>

</aside>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="main-content">


<!-- =====================================================
     TOPBAR
===================================================== -->

<header class="topbar">

    <div>

        <h4>
            Dashboard
        </h4>

        <span>
            Kelola layanan internet kamu
        </span>

    </div>


    <div class="topbar-right">

        <div class="notification">

            <i class="bi bi-bell"></i>

            <?php if ($totalComplaint > 0): ?>

                <span class="notification-badge">
                    <?= $totalComplaint ?>
                </span>

            <?php endif; ?>

        </div>


        <a
            href="profile.php"
            class="top-profile"
        >

            <div class="top-avatar">

                <?= htmlspecialchars($initial) ?>

            </div>

            <div class="top-user">

                <strong>
                    <?= htmlspecialchars($nama) ?>
                </strong>

                <span>
                    Customer
                </span>

            </div>

        </a>

    </div>

</header>



<!-- =====================================================
     CONTENT
===================================================== -->

<div class="content">


<!-- =====================================================
     WELCOME
===================================================== -->

<section class="welcome-card">

    <div class="welcome-content">

        <span>
            CUSTOMER PORTAL
        </span>

        <h1>
            Halo, <?= htmlspecialchars($nama) ?> 👋
        </h1>

        <p>
            Pantau internet, bayar tagihan,
            laporkan gangguan, dan kelola paket
            dari satu tempat.
        </p>

    </div>


    <div class="welcome-wifi">

        <i class="bi bi-wifi"></i>

    </div>

</section>



<!-- =====================================================
     MAIN CARDS
===================================================== -->

<div class="row g-4">


<!-- TAGIHAN -->

<div class="col-lg-4">

    <div class="billing-card">

        <span class="billing-label">
            TAGIHAN BULAN INI
        </span>

        <div class="billing-price">

            <?= rupiah($jumlahTagihan) ?>

        </div>

        <div class="billing-date">

            <i class="bi bi-calendar3"></i>

            Jatuh tempo:
            <?= htmlspecialchars($tanggalJatuhTempo) ?>

        </div>


        <a
            href="billing.php"
            class="btn-pay"
        >

            <i class="bi bi-credit-card"></i>

            Bayar Sekarang

        </a>

    </div>

</div>



<!-- BANDWIDTH -->

<div class="col-lg-4">

    <div class="dashboard-card">

        <div class="card-title">

            <h5>
                Pemakaian Internet
            </h5>

            <span class="live-text">
                ● LIVE
            </span>

        </div>


        <span class="speed-label">
            Download
        </span>

        <div class="speed-value">

            87.4

            <small>
                Mbps
            </small>

        </div>


        <div class="usage-bar">

            <div
                class="usage-fill"
                id="usageBar"
            ></div>

        </div>


        <div class="usage-stats">

            <span>
                ↓ 87.4 Mbps
            </span>

            <span>
                ↑ 18.2 Mbps
            </span>

        </div>

    </div>

</div>



<!-- PAKET -->

<div class="col-lg-4">

    <div class="dashboard-card">

        <div class="card-title">

            <h5>
                Paket Internet
            </h5>

            <span class="active-text">
                AKTIF
            </span>

        </div>


        <div class="package-info">

            <div class="package-icon">

                <i class="bi bi-router-fill"></i>

            </div>


            <div>

                <strong>
                    <?= htmlspecialchars($paketNama) ?>
                </strong>

                <div class="package-speed">

                    <?= htmlspecialchars($paketSpeed) ?>

                </div>

                <span>
                    Paket internet aktif
                </span>

            </div>

        </div>


        <a
            href="upgrade.php"
            class="btn-outline"
        >

            Upgrade Paket

        </a>

    </div>

</div>

</div>



<!-- =====================================================
     SECOND ROW
===================================================== -->

<div class="row g-4 mt-1">


<!-- SPEED TEST -->

<div class="col-lg-4">

    <div class="dashboard-card speed-test">

        <div class="card-title">

            <h5>
                Speed Test
            </h5>

            <span>
                INTERNET
            </span>

        </div>


        <div class="speed-circle">

            <strong>
                87.4
            </strong>

            <span>
                Mbps
            </span>

        </div>


        <div class="speed-meta">

            <span>
                <b>18.2</b>
                Upload
            </span>

            <span>
                <b>9 ms</b>
                Ping
            </span>

        </div>


        <a
            href="speedtest.php"
            class="btn-speed"
        >

            <i class="bi bi-lightning-charge"></i>

            Mulai Speed Test

        </a>

    </div>

</div>



<!-- STATUS JARINGAN -->

<div class="col-lg-4">

    <div class="dashboard-card">

        <div class="card-title">

            <h5>
                Status Jaringan
            </h5>

            <span>
                AREA KAMU
            </span>

        </div>


        <div class="network-status">

            <div class="network-icon">

                <i class="bi bi-check-lg"></i>

            </div>


            <div>

                <strong>
                    Jaringan Normal
                </strong>

                <span>
                    Tidak ada gangguan di area kamu
                </span>

            </div>

        </div>


        <div class="location-info">

            <i class="bi bi-geo-alt-fill"></i>

            Area layanan kamu terpantau normal.

        </div>


        <a
            href="network_status.php"
            class="btn-light-custom"
        >

            Lihat Status Jaringan

        </a>

    </div>

</div>



<!-- LAPOR GANGGUAN -->

<div class="col-lg-4">

    <div class="dashboard-card">

        <div class="card-title">

            <h5>
                Bantuan
            </h5>

            <span>
                24/7
            </span>

        </div>


        <a
            href="complaint_create.php"
            class="service-item"
        >

            <div class="service-icon complaint-icon">

                <i class="bi bi-tools"></i>

            </div>


            <div class="service-info">

                <strong>
                    Laporkan Gangguan
                </strong>

                <span>
                    Sertakan foto & lokasi GPS
                </span>

            </div>

            <i class="bi bi-chevron-right"></i>

        </a>


        <a
            href="complaint_create.php"
            class="btn-outline-danger"
        >

            Buat Laporan

        </a>

    </div>

</div>

</div>



<!-- =====================================================
     THIRD ROW
===================================================== -->

<div class="row g-4 mt-1">


<!-- RIWAYAT PEMBAYARAN -->

<div class="col-lg-7">

    <div class="dashboard-card">

        <div class="card-title">

            <h5>
                Riwayat Pembayaran
            </h5>

            <a
                href="billing.php"
                class="view-all"
            >

                Lihat Semua

            </a>

        </div>


        <div class="table-responsive">

            <table class="payment-table">

                <thead>

                    <tr>

                        <th>
                            Tanggal
                        </th>

                        <th>
                            Invoice
                        </th>

                        <th>
                            Jumlah
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if (!empty($payments)): ?>

                    <?php foreach ($payments as $payment): ?>

                        <?php

                        $paymentStatus =
                            strtolower(
                                $payment['status'] ?? 'paid'
                            );

                        $statusClass =
                            $paymentStatus === 'paid'
                            ? 'status-paid'
                            : 'status-pending';

                        ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $payment['created_at'] ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $payment['invoice'] ??
                                    $payment['invoice_number'] ??
                                    '-'
                                ) ?>
                            </td>

                            <td>
                                <?= rupiah(
                                    $payment['jumlah'] ??
                                    $payment['amount'] ??
                                    0
                                ) ?>
                            </td>

                            <td>

                                <span
                                    class="<?= $statusClass ?>"
                                >

                                    <?= htmlspecialchars(
                                        strtoupper(
                                            $payment['status'] ??
                                            'PAID'
                                        )
                                    ) ?>

                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="4"
                            class="empty-payment"
                        >

                            Belum ada riwayat pembayaran.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>



<!-- QUICK ACTION -->

<div class="col-lg-5">

    <div class="dashboard-card">

        <div class="card-title">

            <h5>
                Layanan Cepat
            </h5>

        </div>


        <div class="quick-action">


            <a
                href="billing.php"
                class="quick-btn"
            >

                <i class="bi bi-receipt"></i>

                Tagihan

            </a>


            <a
                href="speedtest.php"
                class="quick-btn"
            >

                <i class="bi bi-speedometer2"></i>

                Speed Test

            </a>


            <a
                href="complaint_create.php"
                class="quick-btn"
            >

                <i class="bi bi-exclamation-triangle"></i>

                Lapor Gangguan

            </a>


            <a
                href="chat.php"
                class="quick-btn"
            >

                <i class="bi bi-chat-dots"></i>

                Chat CS

            </a>


            <a
                href="upgrade.php"
                class="quick-btn"
            >

                <i class="bi bi-arrow-up-circle"></i>

                Upgrade

            </a>


            <a
                href="service_request.php"
                class="quick-btn"
            >

                <i class="bi bi-plus-circle"></i>

                Layanan Tambahan

            </a>

        </div>

    </div>

</div>

</div>



<!-- =====================================================
     MONITORING BANDWIDTH + CHAT
===================================================== -->

<div class="row g-4 mt-1">


<!-- BANDWIDTH CHART -->

<div class="col-lg-8">

    <div class="dashboard-card">

        <div class="card-title">

            <div>

                <h5>
                    Monitoring Bandwidth
                </h5>

                <span>
                    Pemakaian jaringan secara real-time
                </span>

            </div>

            <span class="live-text">

                ● LIVE

            </span>

        </div>


        <canvas
            id="bandwidthChart"
            height="100"
        ></canvas>

    </div>

</div>



<!-- CUSTOMER SERVICE -->

<div class="col-lg-4">

    <div class="dashboard-card">

        <div class="card-title">

            <h5>
                Customer Service
            </h5>

            <span class="online-text">
                ● Online
            </span>

        </div>


        <div class="chat-preview">

            <div class="chat-avatar">

                <i class="bi bi-headset"></i>

            </div>

            <div>

                <strong>
                    Customer Service
                </strong>

                <p>
                    👋 Halo! Ada yang bisa kami bantu?
                </p>

            </div>

        </div>


        <div class="quick-action">


            <a
                href="chat.php?topic=wifi"
                class="quick-btn"
            >

                <i class="bi bi-wifi"></i>

                Reset WiFi

            </a>


            <a
                href="chat.php?topic=installation"
                class="quick-btn"
            >

                <i class="bi bi-calendar-check"></i>

                Instalasi

            </a>

        </div>


        <a
            href="chat.php"
            class="btn-chat"
        >

            <i class="bi bi-chat-dots"></i>

            Chat dengan CS

        </a>

    </div>

</div>

</div>



<!-- =====================================================
     ACCOUNT
===================================================== -->

<div class="dashboard-card account-card">

    <div class="card-title">

        <div>

            <h5>
                Informasi Akun
            </h5>

            <span>
                Data customer terdaftar
            </span>

        </div>


        <a
            href="profile.php"
            class="btn-light-custom account-button"
        >

            Profile

        </a>

    </div>


    <div class="row g-3">


        <div class="col-md-3">

            <small>
                Nama
            </small>

            <div class="account-value">

                <?= htmlspecialchars($nama) ?>

            </div>

        </div>


        <div class="col-md-3">

            <small>
                Customer ID
            </small>

            <div class="account-value">

                <?= htmlspecialchars(
                    $customer['kode_pelanggan'] ?? '-'
                ) ?>

            </div>

        </div>


        <div class="col-md-3">

            <small>
                Email
            </small>

            <div class="account-value">

                <?= htmlspecialchars(
                    $customer['email'] ?? '-'
                ) ?>

            </div>

        </div>


        <div class="col-md-3">

            <small>
                Nomor HP
            </small>

            <div class="account-value">

                <?= htmlspecialchars(
                    $customer['no_hp'] ?? '-'
                ) ?>

            </div>

        </div>

    </div>

</div>


</div>

</main>



<!-- =====================================================
     CHART JS
===================================================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>

const chartElement =
    document.getElementById('bandwidthChart');

if (chartElement) {

    const labels = [
        '10:00',
        '10:05',
        '10:10',
        '10:15',
        '10:20',
        '10:25',
        '10:30'
    ];

    const downloadData = [
        45,
        58,
        72,
        65,
        81,
        76,
        87
    ];

    const uploadData = [
        8,
        11,
        13,
        10,
        16,
        14,
        18
    ];


    new Chart(chartElement, {

        type: 'line',

        data: {

            labels: labels,

            datasets: [

                {

                    label: 'Download Mbps',

                    data: downloadData,

                    borderWidth: 2,

                    tension: .4,

                    fill: false

                },

                {

                    label: 'Upload Mbps',

                    data: uploadData,

                    borderWidth: 2,

                    tension: .4,

                    fill: false

                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {

                    labels: {

                        font: {
                            size: 10
                        }

                    }

                }

            },

            scales: {

                x: {

                    ticks: {

                        font: {
                            size: 9
                        }

                    },

                    grid: {
                        display: false
                    }

                },

                y: {

                    beginAtZero: true,

                    ticks: {

                        font: {
                            size: 9
                        }

                    }

                }

            }

        }

    });

}

</script>


</body>

</html>