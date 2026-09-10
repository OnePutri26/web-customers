<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

$userId = $_SESSION['user_id'];


/* =========================================================
   AMBIL DATA CUSTOMER
   ========================================================= */

$stmt = $conn->prepare("
    SELECT id, nama
    FROM customers
    WHERE user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) {
    die("Data customer tidak ditemukan.");
}

$customerId = (int) $customer['id'];
$nama = $customer['nama'] ?? 'Customer';

$initial = strtoupper(
    substr(trim($nama), 0, 1)
);


/* =========================================================
   DEFAULT DATA FORM
   ========================================================= */

$category = $_POST['category'] ?? 'internet_down';
$subject = $_POST['subject'] ?? '';
$description = $_POST['description'] ?? '';
$priority = $_POST['priority'] ?? 'medium';

$error = '';



/* =========================================================
   PROSES LAPORAN
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $category = trim($_POST['category'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = trim($_POST['priority'] ?? 'medium');


    /* -----------------------------------------------------
       KATEGORI SESUAI ENUM DATABASE
       ----------------------------------------------------- */

    $allowedCategories = [
        'internet_down',
        'slow_connection',
        'wifi_problem',
        'router_problem',
        'billing',
        'other'
    ];


    /* -----------------------------------------------------
       PRIORITAS SESUAI ENUM DATABASE
       ----------------------------------------------------- */

    $allowedPriorities = [
        'low',
        'medium',
        'high',
        'critical'
    ];


    /* -----------------------------------------------------
       VALIDASI
       ----------------------------------------------------- */

    if (!in_array($category, $allowedCategories, true)) {

        $error = "Jenis gangguan tidak valid.";

    } elseif (!in_array($priority, $allowedPriorities, true)) {

        $error = "Prioritas tidak valid.";

    } elseif ($subject === '') {

        $error = "Judul laporan wajib diisi.";

    } elseif ($description === '') {

        $error = "Deskripsi gangguan wajib diisi.";

    } elseif (strlen($description) < 10) {

        $error = "Deskripsi gangguan terlalu singkat. Jelaskan masalah yang terjadi.";

    } else {


        /* =================================================
           CARI LAYANAN CUSTOMER
           ================================================= */

        $serviceStmt = $conn->prepare("
            SELECT id
            FROM customer_service
            WHERE id_customer = ?
            AND status IN ('active', 'pending')
            ORDER BY id DESC
            LIMIT 1
        ");

        $serviceStmt->bind_param("i", $customerId);
        $serviceStmt->execute();

        $service = $serviceStmt
            ->get_result()
            ->fetch_assoc();

        $serviceStmt->close();


        if (!$service) {

            $error = "Layanan internet kamu belum ditemukan.";

        } else {

            $idLayanan = (int) $service['id'];


            /* =============================================
               GENERATE KODE COMPLAINT
               ============================================= */

            $code = "CMP"
                  . date("YmdHis")
                  . rand(10, 99);


            /* =============================================
               GABUNG JUDUL + DESKRIPSI
               
               Karena tabel complaint hanya mempunyai
               kolom keterangan.
               ============================================= */

            $complaintText =
                "Judul: " . $subject .
                "\n\n" .
                "Deskripsi Gangguan:\n" .
                $description;


            /* =============================================
               STATUS AWAL
               ============================================= */

            $status = 'open';


            /* =============================================
               BUAT ID COMPLAINT
               
               Digunakan karena dari struktur database
               yang kamu kirim, kolom id belum terlihat
               AUTO_INCREMENT.
               ============================================= */

            $idResult = $conn->query("
                SELECT COALESCE(MAX(id), 0) + 1 AS next_id
                FROM complaint
            ");

            if (!$idResult) {

                $error = "Gagal membuat ID laporan.";

            } else {

                $complaintId = (int) $idResult
                    ->fetch_assoc()['next_id'];


                /* =========================================
                   INSERT COMPLAINT
                   ========================================= */

                $insert = $conn->prepare("
                    INSERT INTO complaint
                    (
                        id,
                        id_customer,
                        id_layanan,
                        kode_complaint,
                        kategori,
                        keterangan,
                        prioritas,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");


                if (!$insert) {

                    $error = "Query laporan gagal dibuat.";

                } else {

                    $insert->bind_param(
                        "iiisssss",
                        $complaintId,
                        $customerId,
                        $idLayanan,
                        $code,
                        $category,
                        $complaintText,
                        $priority,
                        $status
                    );


                    if ($insert->execute()) {

                        $insert->close();

                        /*
                         * Redirect agar ketika refresh
                         * browser tidak mengirim POST lagi.
                         */

                        header(
                            "Location: complaint.php?success="
                            . urlencode($code)
                        );

                        exit;

                    } else {

                        $error =
                            "Laporan gagal disimpan: "
                            . $insert->error;

                        $insert->close();
                    }
                }
            }
        }
    }
}


/* =========================================================
   NOTIFIKASI BERHASIL
   ========================================================= */

$successCode = $_GET['success'] ?? '';

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
        Gangguan & Bantuan | Customer Portal
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


    <!-- Dashboard CSS -->

    <link
        rel="stylesheet"
        href="assets/css/customer-dashboard.css"
    >


    <!-- Complaint CSS -->

    <link
        rel="stylesheet"
        href="assets/css/complaint.css"
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
            class="menu-item"
        >
            <i class="bi bi-grid-fill"></i>
            <span>Dashboard</span>
        </a>


        <a
            href="billing.php"
            class="menu-item"
        >
            <i class="bi bi-credit-card-fill"></i>
            <span>Tagihan</span>
        </a>


        <a
            href="usage.php"
            class="menu-item"
        >
            <i class="bi bi-speedometer2"></i>
            <span>Pemakaian</span>
        </a>


        <a
            href="speedtest.php"
            class="menu-item"
        >
            <i class="bi bi-lightning-charge-fill"></i>
            <span>Speed Test</span>
        </a>


        <a
            href="complaint.php"
            class="menu-item active"
        >
            <i class="bi bi-tools"></i>
            <span>Gangguan</span>
        </a>


        <a
            href="network_status.php"
            class="menu-item"
        >
            <i class="bi bi-globe2"></i>
            <span>Status Jaringan</span>
        </a>


        <a
            href="chat.php"
            class="menu-item"
        >
            <i class="bi bi-chat-dots-fill"></i>
            <span>Chat CS</span>
        </a>


        <p class="menu-title menu-account">
            AKUN
        </p>


        <a
            href="upgrade.php"
            class="menu-item"
        >
            <i class="bi bi-arrow-up-circle-fill"></i>
            <span>Upgrade Paket</span>
        </a>


        <a
            href="service_request.php"
            class="menu-item"
        >
            <i class="bi bi-plus-circle-fill"></i>
            <span>Layanan Tambahan</span>
        </a>


        <a
            href="profile.php"
            class="menu-item"
        >
            <i class="bi bi-person-circle"></i>
            <span>Profile Saya</span>
        </a>


        <a
            href="../logout.php"
            class="menu-item logout"
        >
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>

    </div>


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
                Gangguan & Bantuan
            </h4>

            <span>
                Sampaikan kendala internet kamu
            </span>

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

    </header>



    <div class="complaint-page">


        <!-- =================================================
             PAGE HEADER
             ================================================= -->

        <div class="complaint-header">

            <div>

                <div class="breadcrumb-text">

                    <i class="bi bi-house-door"></i>

                    Dashboard

                    <i class="bi bi-chevron-right"></i>

                    Gangguan

                    <i class="bi bi-headset"></i>

                    <strong>
                        CUSTOMER SUPPORT
                    </strong>

                </div>


                <div class="page-label">
                    <i class="bi bi-life-preserver"></i>
                    CUSTOMER SUPPORT
                </div>


                <h1>
                    Laporkan gangguan
                </h1>


                <p>
                    Laporkan gangguan internet yang kamu alami.
                    Jelaskan masalah dengan detail agar tim teknis
                    dapat membantu lebih cepat.
                </p>

            </div>


            <div class="complaint-hero-icon">

                <i class="bi bi-headset"></i>

            </div>

        </div>



        <!-- =================================================
             SUCCESS
             ================================================= -->

        <?php if ($successCode): ?>

            <div class="alert-box alert-success-custom">

                <div class="alert-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>

                <div>

                    <strong>
                        Laporan berhasil dikirim
                    </strong>

                    <span>
                        Nomor laporan kamu:
                        <b>
                            <?= htmlspecialchars($successCode) ?>
                        </b>
                    </span>

                    <small>
                        Tim teknis akan memeriksa laporan
                        dan menindaklanjutinya.
                    </small>

                </div>

            </div>

        <?php endif; ?>



        <!-- =================================================
             ERROR
             ================================================= -->

        <?php if ($error): ?>

            <div class="alert-box alert-error-custom">

                <div class="alert-icon">
                    <i class="bi bi-exclamation-circle-fill"></i>
                </div>

                <div>

                    <strong>
                        Laporan belum dapat dikirim
                    </strong>

                    <span>
                        <?= htmlspecialchars($error) ?>
                    </span>

                </div>

            </div>

        <?php endif; ?>



        <!-- =================================================
             FORM + INFO
             ================================================= -->

        <div class="complaint-form-layout">


            <!-- FORM -->

            <section
                class="complaint-card complaint-form-card"
            >


                <div class="card-heading">

                    <div>

                        <h5>
                            Detail laporan
                        </h5>

                        <p>
                            Isi informasi berikut agar gangguan
                            dapat ditangani dengan tepat.
                        </p>

                    </div>


                    <span class="form-step">
                        01 / 01
                    </span>

                </div>



                <form
                    method="POST"
                    class="complaint-form"
                >


                    <!-- =====================================
                         KATEGORI
                         ===================================== -->

                    <div class="form-field">

                        <label for="category">
                            Jenis gangguan
                        </label>


                        <div class="input-with-icon">

                            <i class="bi bi-wrench-adjustable-circle"></i>


                            <select
                                id="category"
                                name="category"
                                required
                            >

                                <option
                                    value="internet_down"
                                    <?= $category === 'internet_down' ? 'selected' : '' ?>
                                >
                                    Internet Down
                                </option>

                                <option
                                    value="slow_connection"
                                    <?= $category === 'slow_connection' ? 'selected' : '' ?>
                                >
                                    Internet Lambat
                                </option>

                                <option
                                    value="wifi_problem"
                                    <?= $category === 'wifi_problem' ? 'selected' : '' ?>
                                >
                                    Masalah WiFi
                                </option>

                                <option
                                    value="router_problem"
                                    <?= $category === 'router_problem' ? 'selected' : '' ?>
                                >
                                    Masalah Router / Modem
                                </option>

                                <option
                                    value="billing"
                                    <?= $category === 'billing' ? 'selected' : '' ?>
                                >
                                    Masalah Tagihan
                                </option>

                                <option
                                    value="other"
                                    <?= $category === 'other' ? 'selected' : '' ?>
                                >
                                    Lainnya
                                </option>

                            </select>

                        </div>


                        <small class="field-help">
                            Pilih jenis masalah yang paling sesuai
                            dengan gangguan yang kamu alami.
                        </small>

                    </div>



                    <!-- =====================================
                         JUDUL
                         ===================================== -->

                    <div class="form-field">

                        <label for="subject">
                            Judul laporan
                        </label>


                        <div class="input-with-icon">

                            <i class="bi bi-pencil-square"></i>


                            <input
                                id="subject"
                                name="subject"
                                type="text"
                                maxlength="150"
                                value="<?= htmlspecialchars($subject) ?>"
                                placeholder="Contoh: Internet mati sejak pagi"
                                required
                            >

                        </div>

                    </div>



                    <!-- =====================================
                         PRIORITAS
                         ===================================== -->

                    <div class="form-field">

                        <label>
                            Seberapa parah gangguannya?
                        </label>


                        <div class="priority-options">


                            <label class="priority-option priority-low">

                                <input
                                    type="radio"
                                    name="priority"
                                    value="low"
                                    <?= $priority === 'low' ? 'checked' : '' ?>
                                >

                                <span>
                                    <b>Low</b>
                                    <small>
                                        Gangguan ringan
                                    </small>
                                </span>

                            </label>



                            <label class="priority-option priority-medium">

                                <input
                                    type="radio"
                                    name="priority"
                                    value="medium"
                                    <?= $priority === 'medium' ? 'checked' : '' ?>
                                >

                                <span>
                                    <b>Medium</b>
                                    <small>
                                        Mengganggu penggunaan
                                    </small>
                                </span>

                            </label>



                            <label class="priority-option priority-high">

                                <input
                                    type="radio"
                                    name="priority"
                                    value="high"
                                    <?= $priority === 'high' ? 'checked' : '' ?>
                                >

                                <span>
                                    <b>High</b>
                                    <small>
                                        Internet sangat terganggu
                                    </small>
                                </span>

                            </label>



                            <label class="priority-option priority-critical">

                                <input
                                    type="radio"
                                    name="priority"
                                    value="critical"
                                    <?= $priority === 'critical' ? 'checked' : '' ?>
                                >

                                <span>
                                    <b>Critical</b>
                                    <small>
                                        Internet tidak dapat digunakan
                                    </small>
                                </span>

                            </label>


                        </div>

                    </div>



                    <!-- =====================================
                         DESKRIPSI
                         ===================================== -->

                    <div class="form-field">

                        <label for="description">
                            Jelaskan gangguan
                        </label>


                        <div class="input-with-icon textarea-wrap">

                            <i class="bi bi-chat-left-text"></i>


                            <textarea
                                id="description"
                                name="description"
                                rows="6"
                                minlength="10"
                                placeholder="Contoh: Internet mati sejak pukul 08.00. Lampu LOS pada modem berwarna merah dan semua perangkat tidak dapat terhubung ke internet."
                                required
                            ><?= htmlspecialchars($description) ?></textarea>

                        </div>


                        <small class="field-help">
                            Jelaskan kapan gangguan terjadi,
                            perangkat yang terdampak, kondisi lampu modem,
                            dan langkah yang sudah kamu coba.
                        </small>

                    </div>



                    <!-- =====================================
                         CONTOH LAPORAN
                         ===================================== -->

                    <div class="report-guide">

                        <div class="guide-icon">
                            <i class="bi bi-lightbulb"></i>
                        </div>

                        <div>

                            <strong>
                                Contoh laporan yang jelas
                            </strong>

                            <p>
                                "Internet mati sejak pukul 08.00.
                                Lampu LOS modem merah. Semua HP
                                dan laptop tidak bisa internet.
                                Modem sudah saya restart sebanyak
                                dua kali tetapi tetap tidak bisa."
                            </p>

                        </div>

                    </div>



                    <!-- =====================================
                         BUTTON
                         ===================================== -->

                    <button
                        type="submit"
                        class="btn-create-complaint"
                    >

                        <i class="bi bi-send-fill"></i>

                        Kirim laporan

                    </button>


                </form>

            </section>



            <!-- =================================================
                 INFO PANEL
                 ================================================= -->

            <aside class="complaint-info-panel">


                <div class="info-art">

                    <i class="bi bi-shield-check"></i>

                </div>


                <span class="info-kicker">
                    RESPON CEPAT
                </span>


                <h3>
                    Kami siap membantu.
                </h3>


                <p>
                    Laporanmu akan diteruskan ke tim teknis
                    untuk diperiksa dan ditindaklanjuti.
                </p>


                <div class="info-divider"></div>


                <div class="info-row">

                    <i class="bi bi-clock-history"></i>

                    <span>

                        <strong>
                            Jam layanan
                        </strong>

                        Setiap hari, 08.00 - 22.00

                    </span>

                </div>


                <div class="info-row">

                    <i class="bi bi-clipboard-check"></i>

                    <span>

                        <strong>
                            Status laporan
                        </strong>

                        Laporan dimulai dengan status
                        <b>Open</b>

                    </span>

                </div>


                <div class="info-row">

                    <i class="bi bi-chat-dots"></i>

                    <span>

                        <strong>
                            Butuh bantuan langsung?
                        </strong>

                        Hubungi Chat CS

                    </span>

                </div>


            </aside>


        </div>

    </div>

</main>


</body>
</html>