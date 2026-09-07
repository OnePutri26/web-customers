<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');


/* =========================================================
   USER SESSION
========================================================= */

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    die("Session user tidak valid.");
}


/* =========================================================
   HELPER
========================================================= */

function e($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   AMBIL DATA COMPLAINT
========================================================= */

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

$customerId = $customer['id'];

$nama = $customer['nama'] ?? 'Customer';

$initial = strtoupper(
    substr(trim($nama), 0, 1)
);


/* =====================================================
   DATA COMPLAINT
===================================================== */

$stmt = $conn->prepare(
    "SELECT
        c.*
     FROM complaint AS c
     INNER JOIN customers AS cu
        ON cu.id = c.id_customer
     WHERE cu.user_id = ?
     ORDER BY c.id DESC"
);

if (!$stmt) {
    die("Query complaint gagal: " . $conn->error);
}

$stmt->bind_param("i", $userId);

$stmt->execute();

$complaint = $stmt->get_result();


<<<<<<< HEAD
/* =========================================================
   STATISTIK COMPLAINT
========================================================= */

$totalComplaint = 0;
$totalOpen = 0;
$totalProcess = 0;
$totalResolved = 0;


/*
|------------------------------------------------------------------
| Statistik dibuat dari data complaint milik customer yang sedang
| login agar angka tidak mengambil seluruh data customer lain.
|------------------------------------------------------------------
*/

$statStmt = $conn->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(
            CASE
                WHEN LOWER(status) IN ('open', 'baru', 'pending')
                THEN 1
                ELSE 0
            END
        ) AS open_count,
        SUM(
            CASE
                WHEN LOWER(status) IN ('proses', 'process', 'diproses')
                THEN 1
                ELSE 0
            END
        ) AS process_count,
        SUM(
            CASE
                WHEN LOWER(status) IN ('selesai', 'resolved', 'closed')
                THEN 1
                ELSE 0
            END
        ) AS resolved_count
     FROM complaint AS c
     INNER JOIN customers AS cu
        ON cu.id = c.id_customer
     WHERE cu.user_id = ?"
);

if ($statStmt) {

    $statStmt->bind_param("i", $userId);

    $statStmt->execute();

    $statResult = $statStmt->get_result();

    if ($statResult) {

        $stats = $statResult->fetch_assoc();

        $totalComplaint = (int) ($stats['total'] ?? 0);

        $totalOpen = (int) ($stats['open_count'] ?? 0);

        $totalProcess = (int) ($stats['process_count'] ?? 0);

        $totalResolved = (int) ($stats['resolved_count'] ?? 0);
    }

    $statStmt->close();
}


/* =========================================================
   STATUS BADGE
========================================================= */

function complaintStatusBadge($status): string
{
    $status = strtolower(trim((string) $status));

    switch ($status) {

        case 'open':
        case 'baru':
        case 'pending':

            return '
                <span class="complaint-badge status-pending">
                    <span class="status-dot"></span>
                    ' . e(ucwords(str_replace('_', ' ', $status))) . '
                </span>
            ';


        case 'proses':
        case 'process':
        case 'diproses':

            return '
                <span class="complaint-badge status-process">
                    <span class="status-dot"></span>
                    ' . e(ucwords(str_replace('_', ' ', $status))) . '
                </span>
            ';


        case 'selesai':
        case 'resolved':
        case 'closed':

            return '
                <span class="complaint-badge status-resolved">
                    <span class="status-dot"></span>
                    ' . e(ucwords(str_replace('_', ' ', $status))) . '
                </span>
            ';


        case 'ditolak':
        case 'rejected':

            return '
                <span class="complaint-badge status-rejected">
                    <span class="status-dot"></span>
                    ' . e(ucwords(str_replace('_', ' ', $status))) . '
                </span>
            ';


        default:

            return '
                <span class="complaint-badge status-badge">
                    <span class="status-dot"></span>
                    ' . e(ucwords(str_replace('_', ' ', $status ?: 'Unknown'))) . '
                </span>
            ';
    }
}


/* =========================================================
   PRIORITY BADGE
========================================================= */

function complaintPriorityBadge($priority): string
{
    $priority = strtolower(trim((string) $priority));

    switch ($priority) {

        case 'tinggi':
        case 'high':
            $label = 'Tinggi';
            break;

        case 'sedang':
        case 'medium':
            $label = 'Sedang';
            break;

        case 'rendah':
        case 'low':
            $label = 'Rendah';
            break;

        default:
            $label = $priority !== ''
                ? ucwords(str_replace('_', ' ', $priority))
                : 'Normal';
            break;
    }

    return '
        <span class="complaint-badge priority-badge">
            <span class="status-dot"></span>
            ' . e($label) . '
        </span>
    ';
=======
/* =====================================================
   STATUS COUNTER
===================================================== */

$totalComplaint = 0;
$openComplaint = 0;
$processComplaint = 0;
$closedComplaint = 0;

$complaints = [];

while ($row = $complaint->fetch_assoc()) {

    $complaints[] = $row;

    $totalComplaint++;

    $status = strtolower(trim($row['status'] ?? ''));

    if (
        in_array(
            $status,
            ['open', 'baru', 'pending']
        )
    ) {

        $openComplaint++;

    } elseif (
        in_array(
            $status,
            ['process', 'proses', 'on progress']
        )
    ) {

        $processComplaint++;

    } elseif (
        in_array(
            $status,
            ['closed', 'selesai', 'resolved']
        )
    ) {

        $closedComplaint++;
    }
}


/* =====================================================
   HELPER
===================================================== */

function statusClass($status)
{
    $status = strtolower(trim($status));

    return match ($status) {

        'open',
        'baru',
        'pending'
            => 'status-open',

        'process',
        'proses',
        'on progress'
            => 'status-process',

        'closed',
        'selesai',
        'resolved'
            => 'status-closed',

        default
            => 'status-default'
    };
}


function priorityClass($priority)
{
    $priority = strtolower(trim($priority));

    return match ($priority) {

        'high',
        'tinggi'
            => 'priority-high',

        'medium',
        'sedang'
            => 'priority-medium',

        'low',
        'rendah'
            => 'priority-low',

        default
            => 'priority-default'
    };
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa
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

    <title>Complaint Saya | Customer Dashboard</title>


<<<<<<< HEAD
    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->
=======
    <!-- Bootstrap -->
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


<<<<<<< HEAD
    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->
=======
    <!-- Bootstrap Icons -->
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


<<<<<<< HEAD
    <!-- =====================================================
         COMPLAINT CSS
    ====================================================== -->
=======
    <!-- Customer Dashboard -->

    <link
        rel="stylesheet"
        href="../assets/css/customer-dashboard.css"
    >


    <!-- Complaint CSS -->
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa

    <link
        rel="stylesheet"
        href="../assets/css/complaint.css"
    >

</head>


<body>


<<<<<<< HEAD
<!-- =========================================================
     MAIN PAGE
========================================================= -->

<div class="complaint-container">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="complaint-header">


        <!-- HEADING -->

        <div class="complaint-heading">


            <!-- ICON -->

            <div class="complaint-icon">

                <i class="bi bi-ticket-detailed"></i>

            </div>


            <!-- TITLE -->

            <div>

                <h1 class="complaint-title">
                    Complaint Saya
                </h1>

                <p class="complaint-subtitle">
                    Lihat dan pantau laporan complaint Anda.
                </p>

            </div>


        </div>


        <!-- HEADER ACTION -->

        <div class="complaint-header-action">

            <a
                href="complaint_create.php"
                class="btn-new-complaint"
            >

                <span class="plus">
                    <i class="bi bi-plus-lg"></i>
                </span>

                Buat Complaint

            </a>

        </div>

=======
<div class="dashboard-wrapper">


    <!-- =================================================
         SIDEBAR
    ================================================== -->
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa

    <aside class="sidebar">

<<<<<<< HEAD

    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <div class="complaint-stat-grid">


        <!-- TOTAL -->

        <div class="complaint-stat-card">

            <div class="complaint-stat-icon icon-blue">

                <i class="bi bi-ticket-perforated"></i>

            </div>

            <div class="complaint-stat-content">

                <span>Total Complaint</span>

                <strong>
                    <?= number_format($totalComplaint) ?>
                </strong>

                <small>
                    Semua laporan
                </small>

            </div>

        </div>


        <!-- OPEN -->

        <div class="complaint-stat-card">

            <div class="complaint-stat-icon icon-orange">

                <i class="bi bi-clock-history"></i>

            </div>

            <div class="complaint-stat-content">

                <span>Menunggu</span>

                <strong>
                    <?= number_format($totalOpen) ?>
                </strong>

                <small>
                    Menunggu ditangani
                </small>

            </div>

        </div>


        <!-- PROCESS -->

        <div class="complaint-stat-card">

            <div class="complaint-stat-icon icon-blue">

                <i class="bi bi-arrow-repeat"></i>

            </div>

            <div class="complaint-stat-content">

                <span>Diproses</span>

                <strong>
                    <?= number_format($totalProcess) ?>
                </strong>

                <small>
                    Sedang ditangani
                </small>

            </div>

        </div>


        <!-- RESOLVED -->

        <div class="complaint-stat-card">

            <div class="complaint-stat-icon icon-green">

                <i class="bi bi-check-circle"></i>

            </div>

            <div class="complaint-stat-content">

                <span>Selesai</span>

                <strong>
                    <?= number_format($totalResolved) ?>
                </strong>

                <small>
                    Complaint selesai
                </small>

            </div>

        </div>


    </div>


    <!-- =====================================================
         TABLE CARD
    ====================================================== -->

    <div class="complaint-card">


        <!-- =================================================
             CARD HEADER
        ================================================== -->

        <div class="complaint-card-header">

            <div>

                <h3>
                    Daftar Complaint
                </h3>

                <p>
                    Riwayat laporan complaint Anda
                </p>

            </div>

        </div>


        <!-- =================================================
             TABLE WRAPPER
        ================================================== -->

        <div class="table-responsive">


            <!-- =================================================
                 TABLE
            ================================================== -->

            <table class="complaint-table">


                <!-- TABLE HEADER -->
=======
        <div class="sidebar-brand">

            <div class="brand-icon">
                <i class="bi bi-wifi"></i>
            </div>
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa

            <div>

                <h5>WiFi Management</h5>

<<<<<<< HEAD
                        <th>
                            #
                        </th>

                        <th>
                            Kode
                        </th>

                        <th>
                            Subject
                        </th>

                        <th>
                            Prioritas
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Tanggal
                        </th>
=======
                <span>
                    Customer Portal
                </span>
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa

            </div>

        </div>


        <nav class="sidebar-menu">

            <a href="dashboard.php">

                <i class="bi bi-grid-1x2-fill"></i>

                <span>Dashboard</span>

            </a>


            <a href="billing.php">

                <i class="bi bi-receipt"></i>

                <span>Tagihan</span>

            </a>


            <a href="usage.php">

                <i class="bi bi-bar-chart-fill"></i>

                <span>Penggunaan</span>

            </a>


            <a href="speedtest.php">

                <i class="bi bi-speedometer2"></i>

                <span>Speed Test</span>

            </a>


            <a href="complaint.php" class="active">

                <i class="bi bi-ticket-detailed-fill"></i>

                <span>Complaint</span>

            </a>


            <a href="network_status.php">

                <i class="bi bi-broadcast-pin"></i>

                <span>Status Jaringan</span>

            </a>


            <a href="chat.php">

                <i class="bi bi-chat-dots-fill"></i>

                <span>Chat Support</span>

            </a>


            <a href="upgrade.php">

                <i class="bi bi-arrow-up-circle-fill"></i>

                <span>Upgrade Paket</span>

            </a>


            <a href="service_request.php">

                <i class="bi bi-tools"></i>

                <span>Permintaan Layanan</span>

            </a>


            <a href="profile.php">

                <i class="bi bi-person-fill"></i>

                <span>Profile</span>

            </a>

        </nav>


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


<<<<<<< HEAD
                <!-- TABLE BODY -->
=======

    <!-- =================================================
         MAIN CONTENT
    ================================================== -->
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa

    <main class="main-content">


<<<<<<< HEAD
                    <?php if ($complaint && $complaint->num_rows > 0): ?>


                        <?php $no = 1; ?>
=======
        <!-- TOPBAR -->

        <header class="topbar">

            <div>

                <h4>
                    Complaint
                </h4>

                <p>
                    Kelola dan pantau laporan gangguan kamu
                </p>

            </div>
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa


            <div class="topbar-profile">

<<<<<<< HEAD

                            <tr>
=======
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
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa


                                <!-- NUMBER -->

                                <td>

                                    <?= $no++ ?>

                                </td>


                                <!-- CUSTOMER CODE -->

                                <td>

                                    <span class="customer-code">

                                        <?= e(
                                            $row['kode_pelanggan'] ?? '-'
                                        ) ?>

                                    </span>

                                </td>


        <!-- PAGE -->

        <div class="complaint-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="complaint-header">

                <div>

                    <div class="page-label">

                        <i class="bi bi-ticket-detailed-fill"></i>

                        CUSTOMER SUPPORT

                    </div>

                    <h1>
                        Complaint Saya
                    </h1>

                    <p>
                        Laporkan masalah dan pantau proses penanganan
                        gangguan internet kamu.
                    </p>

                </div>


                <a
                    href="complaint_create.php"
                    class="btn-create-complaint"
                >

                    <i class="bi bi-plus-lg"></i>

                    Buat Complaint

                </a>

            </div>



            <!-- =================================================
                 STATISTICS
            ================================================== -->

            <div class="complaint-stats">


                <div class="stat-card">

                    <div class="stat-icon icon-total">

                        <i class="bi bi-ticket-perforated-fill"></i>

                    </div>

                    <div>

                        <span>
                            Total Complaint
                        </span>

                        <strong>
                            <?= $totalComplaint ?>
                        </strong>

                    </div>

                </div>



                <div class="stat-card">

                    <div class="stat-icon icon-open">

                        <i class="bi bi-exclamation-circle-fill"></i>

                    </div>

                    <div>

                        <span>
                            Complaint Baru
                        </span>

                        <strong>
                            <?= $openComplaint ?>
                        </strong>

                    </div>

                </div>



                <div class="stat-card">

                    <div class="stat-icon icon-process">

                        <i class="bi bi-arrow-repeat"></i>

                    </div>

                    <div>

                        <span>
                            Sedang Diproses
                        </span>

                        <strong>
                            <?= $processComplaint ?>
                        </strong>

                    </div>

                </div>



                <div class="stat-card">

                    <div class="stat-icon icon-closed">

                        <i class="bi bi-check-circle-fill"></i>

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



            <!-- =================================================
                 COMPLAINT LIST
            ================================================== -->

            <div class="complaint-card">


                <div class="card-heading">

                    <div>

                        <h5>
                            Riwayat Complaint
                        </h5>

                        <p>
                            Daftar laporan yang pernah kamu buat
                        </p>

                    </div>


                    <div class="total-badge">

                        <?= $totalComplaint ?>

                        Complaint

                    </div>

                </div>



                <?php if (count($complaints) > 0): ?>


                    <div class="complaint-list">


                        <?php foreach ($complaints as $row): ?>


                            <?php

                            $status = $row['status'] ?? 'unknown';

                            $priority = $row['prioritas'] ?? 'normal';

                            $statusClass = statusClass($status);

                            $priorityClass = priorityClass($priority);

                            ?>


                            <div class="complaint-item">


                                <!-- ICON -->

                                <div class="complaint-ticket-icon">

                                    <i class="bi bi-ticket-detailed-fill"></i>

                                </div>



                                <!-- CONTENT -->

                                <div class="complaint-main">


                                    <div class="complaint-top">


                                        <span class="complaint-code">

                                            <?= htmlspecialchars(
                                                $row['kode_pelanggan'] ?? '-'
                                            ) ?>

                                        </span>


                                        <span class="complaint-date">

                                            <i class="bi bi-clock"></i>

                                            <?= !empty($row['created_at'])
                                                ? date(
                                                    'd M Y, H:i',
                                                    strtotime($row['created_at'])
                                                )
                                                : '-'
                                            ?>

                                        </span>

                                    </div>



                                    <h6>

<<<<<<< HEAD
                                        <?= e(
                                            $row['subject'] ?? '-'
=======
                                        <?= htmlspecialchars(
                                            $row['subject'] ?? 'Tanpa Subject'
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa
                                        ) ?>

                                    </h6>


<<<<<<< HEAD
                                <!-- PRIORITY -->
=======

                                    <div class="complaint-meta">
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa


<<<<<<< HEAD
                                    <?= complaintPriorityBadge(
                                        $row['prioritas'] ?? ''
                                    ) ?>

                                </td>


                                <!-- STATUS -->
=======
                                        <span
                                            class="complaint-badge <?= $priorityClass ?>"
                                        >

                                            <i class="bi bi-flag-fill"></i>

                                            <?= strtoupper(
                                                htmlspecialchars($priority)
                                            ) ?>

                                        </span>



                                        <span
                                            class="complaint-badge <?= $statusClass ?>"
                                        >
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa

                                            <i class="bi bi-circle-fill"></i>

<<<<<<< HEAD
                                    <?= complaintStatusBadge(
                                        $row['status'] ?? ''
                                    ) ?>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <span class="complaint-date">

                                        <i class="bi bi-calendar3"></i>

                                        <?php

                                        if (!empty($row['created_at'])) {

                                            $timestamp = strtotime(
                                                $row['created_at']
                                            );

                                            echo $timestamp
                                                ? date(
                                                    'd M Y, H:i',
                                                    $timestamp
                                                )
                                                : '-';

                                        } else {

                                            echo '-';

                                        }

                                        ?>

                                    </span>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <!-- =================================================
                             EMPTY STATE
                        ================================================== -->

                        <tr>

                            <td
                                colspan="6"
                                class="empty-state"
                            >


                                <div class="empty-icon">

                                    <i class="bi bi-ticket-detailed"></i>

                                </div>


                                <strong class="empty-title">

                                    Belum Ada Complaint

                                </strong>


                                <span class="empty-description">

                                    Anda belum memiliki laporan complaint.

                                </span>
=======
                                            <?= strtoupper(
                                                htmlspecialchars($status)
                                            ) ?>

                                        </span>


                                    </div>


                                </div>



                                <!-- ACTION -->

                                <div class="complaint-action">

                                    <button
                                        type="button"
                                        class="btn-detail"
                                        title="Detail complaint"
                                    >

                                        <i class="bi bi-chevron-right"></i>

                                    </button>

                                </div>


                            </div>
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa


                        <?php endforeach; ?>


                    </div>


                <?php else: ?>


                    <!-- EMPTY STATE -->

                    <div class="empty-state">

                        <div class="empty-icon">

                            <i class="bi bi-ticket-detailed"></i>

                        </div>

                        <h4>
                            Belum Ada Complaint
                        </h4>

                        <p>
                            Kamu belum memiliki laporan complaint.
                            Jika mengalami masalah internet, kamu bisa
                            membuat laporan baru.
                        </p>

                        <a
                            href="complaint_create.php"
                            class="btn-create-complaint"
                        >

                            <i class="bi bi-plus-lg"></i>

                            Buat Complaint

                        </a>

                    </div>


                <?php endif; ?>


            </div>


        </div>


<<<<<<< HEAD
        <!-- =================================================
             CARD FOOTER
        ================================================== -->

        <div class="complaint-card-footer">

            <span>
                Menampilkan riwayat complaint Anda
            </span>

            <div class="footer-info">

                <i class="bi bi-info-circle"></i>

                Pastikan informasi complaint selalu lengkap.

            </div>

        </div>


    </div>
=======
    </main>
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa


</div>


<<<<<<< HEAD
<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->
=======
>>>>>>> 12f55d7fe3e6269a51ed7762751d15345bfd72fa

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>
```

Ada **satu tambahan penting**: CSS Complaint yang sebelumnya saya kasih belum memiliki styling untuk statistik yang baru saya masukkan. Jadi tambahkan bagian berikut ke `complaint.css` supaya statistiknya benar-benar mengikuti Billing.

```css
/* =========================================================
   COMPLAINT STATISTICS
========================================================= */

.complaint-stat-grid {
    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 18px;

    margin-top: 20px;

    margin-bottom: 18px;
}


/* =========================================================
   STAT CARD
========================================================= */

.complaint-stat-card {
    display: flex;

    align-items: center;

    gap: 15px;

    padding: 20px;

    background: var(--complaint-card);

    border: 1px solid var(--complaint-border);

    border-radius: var(--complaint-radius);

    box-shadow:
        0 3px 15px rgba(25, 35, 60, 0.035);

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.complaint-stat-card:hover {
    transform: translateY(-2px);

    box-shadow:
        0 10px 28px rgba(25, 35, 60, 0.07);
}


/* =========================================================
   STAT ICON
========================================================= */

.complaint-stat-icon {
    width: 48px;
    height: 48px;

    min-width: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 13px;

    font-size: 21px;
}


/* BLUE */

.complaint-stat-icon.icon-blue {
    color: #2563eb;

    background: #eff6ff;
}


/* GREEN */

.complaint-stat-icon.icon-green {
    color: #16a34a;

    background: #ecfdf3;
}


/* ORANGE */

.complaint-stat-icon.icon-orange {
    color: #ea8b00;

    background: #fff7e8;
}


/* RED */

.complaint-stat-icon.icon-red {
    color: #dc2626;

    background: #fef2f2;
}


/* =========================================================
   STAT CONTENT
========================================================= */

.complaint-stat-content {
    display: flex;

    flex-direction: column;

    min-width: 0;
}


.complaint-stat-content span {
    color: var(--complaint-muted);

    font-size: 13px;

    font-weight: 500;
}


.complaint-stat-content strong {
    margin-top: 3px;

    color: var(--complaint-text);

    font-size: 25px;

    line-height: 1.2;

    font-weight: 750;
}


.complaint-stat-content small {
    margin-top: 3px;

    color: #a0a7b5;

    font-size: 11px;
}


/* =========================================================
   RESPONSIVE STATISTICS
========================================================= */

@media (max-width: 1100px) {

    .complaint-stat-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

}


@media (max-width: 576px) {

    .complaint-stat-grid {
        grid-template-columns: 1fr;

        gap: 12px;
    }


    .complaint-stat-card {
        padding: 16px;
    }


    .complaint-stat-content strong {
        font-size: 21px;
    }

}
