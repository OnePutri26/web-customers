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

$customerId = $customer['id'];

$nama = $customer['nama'] ?? 'Customer';

$initial = strtoupper(
    substr(trim($nama), 0, 1)
);


/* =====================================================
   DATA COMPLAINT
===================================================== */

$stmt = $conn->prepare(
    "SELECT c.*
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

    <title>Complaint Saya</title>


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


    <!-- Customer Dashboard -->

    <link
        rel="stylesheet"
        href="../assets/css/customer-dashboard.css"
    >


    <!-- Complaint CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/complaint.css"
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

                <h5>WiFi Management</h5>

                <span>
                    Customer Portal
                </span>

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



    <!-- =================================================
         MAIN CONTENT
    ================================================== -->

    <main class="main-content">


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

                                        <?= htmlspecialchars(
                                            $row['subject'] ?? 'Tanpa Subject'
                                        ) ?>

                                    </h6>



                                    <div class="complaint-meta">


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

                                            <i class="bi bi-circle-fill"></i>

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


    </main>


</div>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>