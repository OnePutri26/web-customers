<?php

require_once "../config/database.php";
require_once "../config/auth.php";

requireRole('customer');

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT id FROM customers WHERE user_id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$customer =
    $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $category = $_POST['category'];
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);
    $priority = $_POST['priority'];

    $code =
        "CMP" . date("YmdHis") . rand(10,99);

    $stmt = $conn->prepare(
        "INSERT INTO complaints
        (
            customer_id,
            complaint_code,
            category,
            subject,
            description,
            priority
        )
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "isssss",
        $customer['id'],
        $code,
        $category,
        $subject,
        $description,
        $priority
    );

    $stmt->execute();

    header("Location: complaint.php");
    exit;
}
?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<title>Complaint</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet"
>

</head>

<body>

<div class="container py-4">

<h3>
🎫 Buat Complaint
</h3>

<form method="POST">

<label class="form-label">
Jenis Gangguan
</label>

<select
name="category"
class="form-select mb-3"
required
>

<option value="internet_down">
Internet Down
</option>

<option value="slow_connection">
Internet Lambat
</option>

<option value="wifi_problem">
Masalah WiFi
</option>

<option value="router_problem">
Masalah Router
</option>

<option value="billing">
Billing
</option>

<option value="other">
Lainnya
</option>

</select>

<label>
Judul
</label>

<input
name="subject"
class="form-control mb-3"
required
>

<label>
Prioritas
</label>

<select
name="priority"
class="form-select mb-3"
>

<option value="low">Low</option>
<option value="medium">Medium</option>
<option value="high">High</option>
<option value="critical">Critical</option>

</select>

<label>
Deskripsi Gangguan
</label>

<textarea
name="description"
class="form-control mb-3"
rows="5"
required
></textarea>

<button class="btn btn-danger">
Kirim Complaint
</button>

</form>

</div>

</body>

</html>