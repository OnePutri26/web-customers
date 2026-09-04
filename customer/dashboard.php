<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

$userId = $_SESSION['user_id'];


/* ==============================
   DATA CUSTOMER
============================== */

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


/* ==============================
   TOTAL INSTALASI
============================== */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM instalasi
    WHERE id_customer = ?
");

$stmt->bind_param("i", $customerId);
$stmt->execute();

$totalInstalasi =
    $stmt->get_result()->fetch_assoc()['total'];


/* ==============================
   TOTAL COMPLAINT
============================== */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM complaint
    WHERE id_customer = ?
    AND status NOT IN ('closed', 'resolved')
");

$stmt->bind_param("i", $customerId);
$stmt->execute();

$totalComplaint =
    $stmt->get_result()->fetch_assoc()['total'];

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
        Dashboard Customer
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


    <!-- CSS -->

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


    <!-- LOGO -->

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


    <!-- MENU -->

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
            href="profile.php"
            class="menu-item"
        >

            <i class="bi bi-person-circle"></i>

            <span>
                Profile Saya
            </span>

        </a>


        <a
            href="installation.php"
            class="menu-item"
        >

            <i class="bi bi-router-fill"></i>

            <span>
                Instalasi
            </span>

        </a>


        <a
            href="complaint.php"
            class="menu-item"
        >

            <i class="bi bi-ticket-detailed-fill"></i>

            <span>
                Complaint
            </span>

        </a>


        <p class="menu-title menu-account">
            AKUN
        </p>


        <a
            href="profile_edit.php"
            class="menu-item"
        >

            <i class="bi bi-pencil-square"></i>

            <span>
                Edit Profile
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


    <!-- TOPBAR -->

    <header class="topbar">

        <div>

            <h4>
                Dashboard
            </h4>

            <span>
                Ringkasan layanan WiFi kamu
            </span>

        </div>


        <div class="topbar-right">


            <!-- NOTIFICATION -->

            <div class="notification">

                <i class="bi bi-bell"></i>

                <?php if ($totalComplaint > 0): ?>

                    <span class="notification-badge">

                        <?= $totalComplaint ?>

                    </span>

                <?php endif; ?>

            </div>


            <!-- PROFILE -->

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



    <!-- CONTENT -->

    <div class="content">


        <!-- WELCOME -->

        <section class="welcome-card">

            <div class="welcome-content">

                <span>
                    CUSTOMER PORTAL
                </span>

                <h1>
                    Halo, <?= htmlspecialchars($nama) ?> 👋
                </h1>

                <p>
                    Selamat datang di dashboard customer.
                    Kelola layanan WiFi kamu dengan mudah.
                </p>

            </div>


            <div class="welcome-wifi">

                <i class="bi bi-wifi"></i>

            </div>

        </section>



        <!-- STATISTIC -->

        <div class="row g-4">


            <!-- INSTALASI -->

            <div class="col-lg-4 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon blue">

                        <i class="bi bi-router-fill"></i>

                    </div>

                    <div class="stat-info">

                        <span>
                            Instalasi WiFi
                        </span>

                        <h2>
                            <?= $totalInstalasi ?>
                        </h2>

                        <small>
                            Total pemasangan
                        </small>

                    </div>

                    <a
                        href="installation.php"
                        class="stat-link"
                    >

                        <i class="bi bi-arrow-up-right"></i>

                    </a>

                </div>

            </div>



            <!-- COMPLAINT -->

            <div class="col-lg-4 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon orange">

                        <i class="bi bi-ticket-perforated-fill"></i>

                    </div>

                    <div class="stat-info">

                        <span>
                            Complaint Aktif
                        </span>

                        <h2>
                            <?= $totalComplaint ?>
                        </h2>

                        <small>
                            Belum diselesaikan
                        </small>

                    </div>

                    <a
                        href="complaint.php"
                        class="stat-link"
                    >

                        <i class="bi bi-arrow-up-right"></i>

                    </a>

                </div>

            </div>



            <!-- CUSTOMER ID -->

            <div class="col-lg-4 col-md-12">

                <div class="stat-card">

                    <div class="stat-icon purple">

                        <i class="bi bi-person-vcard-fill"></i>

                    </div>

                    <div class="stat-info">

                        <span>
                            Customer ID
                        </span>

                        <h2 class="customer-id">

                            <?= htmlspecialchars(
                                $customer['kode_pelanggan'] ?? '-'
                            ) ?>

                        </h2>

                        <small>
                            ID pelanggan
                        </small>

                    </div>

                </div>

            </div>

        </div>



        <!-- SECTION -->

        <div class="section-header">

            <div>

                <h3>
                    Layanan Customer
                </h3>

                <p>
                    Pilih layanan yang ingin kamu gunakan.
                </p>

            </div>

        </div>



        <!-- ACTION -->

        <div class="row g-4">


            <!-- PEMASANGAN -->

            <div class="col-lg-6">

                <div class="service-card blue-card">

                    <div class="service-icon">

                        <i class="bi bi-router-fill"></i>

                    </div>

                    <div class="service-content">

                        <span>
                            PEMASANGAN
                        </span>

                        <h4>
                            Pasang WiFi Baru
                        </h4>

                        <p>
                            Ajukan pemasangan layanan WiFi
                            baru dengan mudah.
                        </p>

                        <a
                            href="installation_create.php"
                            class="service-button"
                        >

                            Ajukan Pemasangan

                            <i class="bi bi-arrow-right"></i>

                        </a>

                    </div>

                </div>

            </div>



            <!-- COMPLAINT -->

            <div class="col-lg-6">

                <div class="service-card red-card">

                    <div class="service-icon">

                        <i class="bi bi-tools"></i>

                    </div>

                    <div class="service-content">

                        <span>
                            BANTUAN
                        </span>

                        <h4>
                            Laporkan Gangguan
                        </h4>

                        <p>
                            Internet bermasalah?
                            Buat laporan gangguan sekarang.
                        </p>

                        <a
                            href="complaint_create.php"
                            class="service-button"
                        >

                            Buat Complaint

                            <i class="bi bi-arrow-right"></i>

                        </a>

                    </div>

                </div>

            </div>

        </div>



        <!-- ACCOUNT -->

        <div class="section-header account-header">

            <div>

                <h3>
                    Informasi Akun
                </h3>

                <p>
                    Informasi customer yang terdaftar.
                </p>

            </div>

            <a
                href="profile.php"
                class="view-profile"
            >

                Lihat Profile

                <i class="bi bi-arrow-right"></i>

            </a>

        </div>



        <div class="account-card">


            <!-- NAMA -->

            <div class="account-item">

                <div class="account-icon">

                    <i class="bi bi-person"></i>

                </div>

                <div>

                    <span>
                        Nama Lengkap
                    </span>

                    <strong>
                        <?= htmlspecialchars($nama) ?>
                    </strong>

                </div>

            </div>



            <!-- EMAIL -->

            <div class="account-item">

                <div class="account-icon">

                    <i class="bi bi-envelope"></i>

                </div>

                <div>

                    <span>
                        Email
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $customer['email'] ?? '-'
                        ) ?>
                    </strong>

                </div>

            </div>



            <!-- HP -->

            <div class="account-item">

                <div class="account-icon">

                    <i class="bi bi-phone"></i>

                </div>

                <div>

                    <span>
                        Nomor HP
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $customer['no_hp'] ?? '-'
                        ) ?>
                    </strong>

                </div>

            </div>



            <!-- ALAMAT -->

            <div class="account-item">

                <div class="account-icon">

                    <i class="bi bi-geo-alt"></i>

                </div>

                <div>

                    <span>
                        Alamat
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $customer['alamat'] ?? '-'
                        ) ?>
                    </strong>

                </div>

            </div>


        </div>


    </div>

</main>


</body>

</html>