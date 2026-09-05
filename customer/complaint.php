<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT c.*
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


    <!-- Complaint CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/complaint.css"
    >

</head>


<body>

<div class="container py-4">

<div class="d-flex justify-content-between">

<h3>🎫 Complaint Saya</h3>

<a
href="complaint_create.php"
class="btn btn-danger"
>
+ Complaint
</a>

    </div>

<div class="table-responsive mt-3">

<table class="table table-bordered">

                <thead>

                    <tr>

<th>Kode</th>
<th>Subject</th>
<th>Prioritas</th>
<th>Status</th>
<th>Tanggal</th>

                    </tr>

                </thead>



                <!-- TABLE BODY -->

                <tbody>


                    <?php if ($complaint->num_rows > 0): ?>


                        <?php while ($row = $complaint->fetch_assoc()): ?>

<tr>

<td>
<?= htmlspecialchars($row['kode_pelanggan']) ?>
</td>



                                <!-- SUBJECT -->

                                <td>

                                    <span class="subject">

                                        <?= htmlspecialchars(
                                            $row['subject']
                                        ) ?>

                                    </span>

                                </td>



                                <!-- PRIORITAS -->

                                <td>

                                    <span
                                        class="complaint-badge priority-badge"
                                    >

                                        ●

                                        <?= strtoupper(
                                            htmlspecialchars(
                                                $row['prioritas']
                                            )
                                        ) ?>

                                    </span>

                                </td>



                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="complaint-badge status-badge"
                                    >

                                        ●

                                        <?= strtoupper(
                                            htmlspecialchars(
                                                $row['status']
                                            )
                                        ) ?>

                                    </span>

                                </td>



                                <!-- TANGGAL -->

                                <td>

                                    <span class="complaint-date">

                                        <?= date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $row['created_at']
                                            )
                                        ) ?>

                                    </span>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <!-- EMPTY STATE -->

                        <tr>

                            <td
                                colspan="5"
                                class="empty-state"
                            >


                                <div class="empty-icon">
                                    🎫
                                </div>


                                <div class="empty-title">
                                    Belum Ada Complaint
                                </div>


                                <p class="empty-description">
                                    Anda belum memiliki laporan complaint.
                                </p>


                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>


</div>



<!-- =========================================
     BOOTSTRAP JS
========================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>