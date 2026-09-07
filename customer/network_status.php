<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

$userId = $_SESSION['user_id'];


/* =====================================================
   DATA CUSTOMER
===================================================== */

$stmtCustomer = $conn->prepare("
    SELECT *
    FROM customers
    WHERE user_id = ?
    LIMIT 1
");

if (!$stmtCustomer) {
    die("Query customer gagal: " . $conn->error);
}

$stmtCustomer->bind_param("i", $userId);
$stmtCustomer->execute();

$customer = $stmtCustomer->get_result()->fetch_assoc();

if (!$customer) {
    die("Data customer tidak ditemukan.");
}


$customerId = $customer['id'] ?? null;

$nama = $customer['nama'] ?? 'Customer';

$initial = strtoupper(
    substr(trim($nama), 0, 1)
);

$paket = $customer['paket']
    ?? $customer['nama_paket']
    ?? 'Internet Home';

$speed = $customer['speed']
    ?? $customer['kecepatan']
    ?? '100 Mbps';


/* =====================================================
   SERVER INFORMATION
===================================================== */

$serverName = $_SERVER['SERVER_NAME']
    ?? 'Server Utama';

$serverIp = $_SERVER['SERVER_ADDR']
    ?? '127.0.0.1';


/* =====================================================
   NETWORK STATUS
===================================================== */

/*
|--------------------------------------------------------------------------
| Karena halaman ini berada di server yang sama dengan aplikasi,
| status "ONLINE" berarti server aplikasi dapat diakses.
|
| Untuk monitoring router/OLT/PPPoE secara real-time,
| bagian ini nantinya bisa dihubungkan ke API monitoring.
|--------------------------------------------------------------------------
*/

$networkStatus = 'online';

$statusLabel = 'Semua Sistem Normal';

$statusDescription =
    'Koneksi internet dan layanan jaringan berjalan normal.';


/* =====================================================
   CURRENT TIME
===================================================== */

$currentTime = date('H:i');

$currentDate = date('d M Y');


/* =====================================================
   SERVICE STATUS
===================================================== */

$services = [

    [
        'name' => 'Internet',
        'description' => 'Koneksi internet pelanggan',
        'status' => 'online',
        'icon' => 'bi-globe2'
    ],

    [
        'name' => 'DNS Server',
        'description' => 'Resolusi domain internet',
        'status' => 'online',
        'icon' => 'bi-diagram-3-fill'
    ],

    [
        'name' => 'Authentication',
        'description' => 'Autentikasi koneksi pelanggan',
        'status' => 'online',
        'icon' => 'bi-shield-check'
    ],

    [
        'name' => 'Customer Portal',
        'description' => 'Sistem layanan pelanggan',
        'status' => 'online',
        'icon' => 'bi-window-stack'
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

    <title>Status Jaringan</title>


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
        href="../assets/css/customer-dashboard.css"
    >


    <!-- Network Status CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/network_status.css"
    >

</head>


<body>


<div class="dashboard-wrapper">


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">


        <div class="sidebar-brand">

            <div class="brand-icon">

                <i class="bi bi-wifi"></i>

            </div>


            <div>

                <h5>
                    WiFi Management
                </h5>

                <span>
                    Customer Portal
                </span>

            </div>

        </div>


        <nav class="sidebar-menu">


            <a href="dashboard.php">

                <i class="bi bi-grid-1x2-fill"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <a href="billing.php">

                <i class="bi bi-receipt"></i>

                <span>
                    Tagihan
                </span>

            </a>


            <a href="usage.php">

                <i class="bi bi-bar-chart-fill"></i>

                <span>
                    Penggunaan
                </span>

            </a>


            <a href="speedtest.php">

                <i class="bi bi-speedometer2"></i>

                <span>
                    Speed Test
                </span>

            </a>


            <a href="complaint.php">

                <i class="bi bi-ticket-detailed-fill"></i>

                <span>
                    Complaint
                </span>

            </a>


            <a
                href="network_status.php"
                class="active"
            >

                <i class="bi bi-broadcast-pin"></i>

                <span>
                    Status Jaringan
                </span>

            </a>


            <a href="chat.php">

                <i class="bi bi-chat-dots-fill"></i>

                <span>
                    Chat Support
                </span>

            </a>


            <a href="upgrade.php">

                <i class="bi bi-arrow-up-circle-fill"></i>

                <span>
                    Upgrade Paket
                </span>

            </a>


            <a href="service_request.php">

                <i class="bi bi-tools"></i>

                <span>
                    Permintaan Layanan
                </span>

            </a>


            <a href="profile.php">

                <i class="bi bi-person-fill"></i>

                <span>
                    Profile
                </span>

            </a>


        </nav>


        <!-- SIDEBAR FOOTER -->

        <div class="sidebar-footer">


            <div class="sidebar-user">


                <div class="sidebar-avatar">

                    <?= htmlspecialchars($initial) ?>

                </div>


                <div>

                    <strong>

                        <?= htmlspecialchars($nama) ?>

                    </strong>


                    <small>
                        Customer
                    </small>

                </div>


            </div>


        </div>


    </aside>



    <!-- =================================================
         MAIN CONTENT
    ================================================== -->

    <main class="main-content">


        <!-- =================================================
             TOPBAR
        ================================================== -->

        <header class="topbar">


            <div>

                <h4>
                    Status Jaringan
                </h4>

                <p>
                    Pantau kondisi layanan internet kamu
                </p>

            </div>


            <div class="topbar-profile">


                <div class="topbar-avatar">

                    <?= htmlspecialchars($initial) ?>

                </div>


                <div>

                    <strong>
                        <?= htmlspecialchars($nama) ?>
                    </strong>

                    <small>
                        Customer
                    </small>

                </div>


            </div>


        </header>



        <!-- =================================================
             NETWORK PAGE
        ================================================== -->

        <div class="network-page">


            <!-- PAGE HEADER -->

            <div class="network-header">


                <div>

                    <div class="page-label">

                        <i class="bi bi-broadcast-pin"></i>

                        NETWORK MONITORING

                    </div>


                    <h1>
                        Status Jaringan
                    </h1>


                    <p>
                        Pantau kondisi koneksi dan layanan jaringan
                        internet kamu secara mudah.
                    </p>

                </div>


                <div class="last-update">

                    <i class="bi bi-clock-history"></i>

                    Update:

                    <?= htmlspecialchars($currentTime) ?>

                </div>


            </div>



            <!-- =================================================
                 MAIN STATUS
            ================================================== -->

            <div class="network-status-card">


                <div class="status-content">


                    <div class="status-icon-wrapper">


                        <div class="status-pulse">

                            <span></span>

                        </div>


                        <i class="bi bi-wifi"></i>


                    </div>


                    <div class="status-information">


                        <span class="status-small-title">

                            STATUS KONEKSI

                        </span>


                        <h2>

                            <?= htmlspecialchars($statusLabel) ?>

                        </h2>


                        <p>

                            <?= htmlspecialchars($statusDescription) ?>

                        </p>


                        <div class="status-online">

                            <span></span>

                            ONLINE

                        </div>


                    </div>


                </div>


                <div class="status-date">


                    <span>
                        Terakhir diperiksa
                    </span>


                    <strong>

                        <?= htmlspecialchars($currentDate) ?>

                    </strong>


                </div>


            </div>



            <!-- =================================================
                 CONNECTION INFO
            ================================================== -->

            <div class="section-title">

                <div>

                    <h3>
                        Informasi Koneksi
                    </h3>

                    <p>
                        Informasi layanan internet yang sedang digunakan
                    </p>

                </div>

            </div>


            <div class="connection-grid">


                <!-- PACKAGE -->

                <div class="info-card">


                    <div class="info-icon package-icon">

                        <i class="bi bi-box-seam-fill"></i>

                    </div>


                    <div class="info-content">

                        <span>
                            Paket Internet
                        </span>

                        <strong>
                            <?= htmlspecialchars($paket) ?>
                        </strong>

                    </div>


                </div>



                <!-- SPEED -->

                <div class="info-card">


                    <div class="info-icon speed-icon">

                        <i class="bi bi-speedometer2"></i>

                    </div>


                    <div class="info-content">

                        <span>
                            Kecepatan Paket
                        </span>

                        <strong>
                            <?= htmlspecialchars($speed) ?>
                        </strong>

                    </div>


                </div>



                <!-- SERVER -->

                <div class="info-card">


                    <div class="info-icon server-icon">

                        <i class="bi bi-hdd-network-fill"></i>

                    </div>


                    <div class="info-content">

                        <span>
                            Server
                        </span>

                        <strong>
                            <?= htmlspecialchars($serverName) ?>
                        </strong>

                    </div>


                </div>



                <!-- CUSTOMER ID -->

                <div class="info-card">


                    <div class="info-icon customer-icon">

                        <i class="bi bi-person-badge-fill"></i>

                    </div>


                    <div class="info-content">

                        <span>
                            Customer ID
                        </span>

                        <strong>
                            #<?= htmlspecialchars($customerId) ?>
                        </strong>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 SERVICE STATUS
            ================================================== -->

            <div class="service-section">


                <div class="section-title">


                    <div>

                        <h3>
                            Status Layanan
                        </h3>

                        <p>
                            Kondisi komponen layanan jaringan saat ini
                        </p>

                    </div>


                    <div class="system-status">

                        <span></span>

                        Semua Sistem Normal

                    </div>


                </div>



                <div class="service-grid">


                    <?php foreach ($services as $service): ?>


                        <div class="service-card">


                            <div class="service-icon">


                                <i
                                    class="bi <?= htmlspecialchars(
                                        $service['icon']
                                    ) ?>"
                                ></i>


                            </div>


                            <div class="service-information">


                                <h4>

                                    <?= htmlspecialchars(
                                        $service['name']
                                    ) ?>

                                </h4>


                                <p>

                                    <?= htmlspecialchars(
                                        $service['description']
                                    ) ?>

                                </p>


                            </div>


                            <div class="service-status">

                                <span></span>

                                Normal

                            </div>


                        </div>


                    <?php endforeach; ?>


                </div>


            </div>



            <!-- =================================================
                 INFORMATION BOX
            ================================================== -->

            <div class="network-info-box">


                <div class="network-info-icon">

                    <i class="bi bi-info-circle-fill"></i>

                </div>


                <div>


                    <h4>
                        Mengalami masalah koneksi?
                    </h4>


                    <p>

                        Jika status jaringan normal tetapi koneksi
                        internet kamu bermasalah, coba lakukan
                        <strong>Speed Test</strong> terlebih dahulu.
                        Jika masalah tetap terjadi, silakan buat
                        <strong>Complaint</strong> agar tim teknis
                        dapat membantu.

                    </p>


                    <div class="network-actions">


                        <a
                            href="speedtest.php"
                            class="network-btn secondary"
                        >

                            <i class="bi bi-speedometer2"></i>

                            Speed Test

                        </a>


                        <a
                            href="complaint_create.php"
                            class="network-btn primary"
                        >

                            <i class="bi bi-ticket-detailed-fill"></i>

                            Buat Complaint

                        </a>


                    </div>


                </div>


            </div>


        </div>


    </main>


</div>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>