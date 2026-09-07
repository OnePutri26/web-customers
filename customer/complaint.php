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
    die("Query gagal: " . $conn->error);
}

$stmt->bind_param("i", $userId);

$stmt->execute();

$complaint = $stmt->get_result();


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


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- =====================================================
         COMPLAINT CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/complaint.css"
    >

</head>


<body>


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


    </div>


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

                <thead>

                    <tr>

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

                    </tr>

                </thead>


                <!-- TABLE BODY -->

                <tbody>


                    <?php if ($complaint && $complaint->num_rows > 0): ?>


                        <?php $no = 1; ?>


                        <?php while ($row = $complaint->fetch_assoc()): ?>


                            <tr>


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


                                <!-- SUBJECT -->

                                <td>

                                    <span class="subject">

                                        <?= e(
                                            $row['subject'] ?? '-'
                                        ) ?>

                                    </span>

                                </td>


                                <!-- PRIORITY -->

                                <td>

                                    <?= complaintPriorityBadge(
                                        $row['prioritas'] ?? ''
                                    ) ?>

                                </td>


                                <!-- STATUS -->

                                <td>

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


                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>


            </table>


        </div>


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


</div>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

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
