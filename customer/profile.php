<?php
session_start();
require_once '../config/database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $conn->prepare("
    SELECT id, username, email, telephone, alamat
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$customer = $result->fetch_assoc();

if (!$customer) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Profile Saya</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .profile-card {
            max-width: 700px;
            margin: 50px auto;
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
        }

        .profile-header {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            color: white;
            padding: 35px;
            text-align: center;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            background: white;
            color: #0d6efd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            font-weight: bold;
            margin: 0 auto 15px;
        }

        .profile-body {
            padding: 30px;
            background: white;
        }

        .profile-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .profile-item:last-child {
            border-bottom: none;
        }

        .profile-label {
            font-size: 13px;
            color: #888;
            margin-bottom: 4px;
        }

        .profile-value {
            font-size: 16px;
            font-weight: 500;
            color: #333;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="card profile-card">

        <!-- HEADER -->
        <div class="profile-header">

            <div class="profile-avatar">
                <?= strtoupper(substr($customer['username'], 0, 1)) ?>
            </div>

            <h3 class="mb-1">
                <?= htmlspecialchars($customer['username']) ?>
            </h3>

            <p class="mb-0">
                Customer WiFi
            </p>

        </div>

        <!-- BODY -->
        <div class="profile-body">

            <h5 class="mb-4">
                Profile Saya
            </h5>

            <!-- Nama -->
            <div class="profile-item">
                <div class="profile-label">
                    Nama Lengkap
                </div>

                <div class="profile-value">
                    <?= htmlspecialchars($customer['username']) ?>
                </div>
            </div>

            <!-- Email -->
            <div class="profile-item">
                <div class="profile-label">
                    Email
                </div>

                <div class="profile-value">
                    <?= htmlspecialchars($customer['email']) ?>
                </div>
            </div>

            <!-- No HP -->
            <div class="profile-item">
                <div class="profile-label">
                    Nomor HP
                </div>

                <div class="profile-value">
                    <?= htmlspecialchars($customer['telephone'] ?? '-') ?>
                </div>
            </div>

            <!-- Alamat -->
            <div class="profile-item">
                <div class="profile-label">
                    Alamat
                </div>

                <div class="profile-value">
                    <?= htmlspecialchars($customer['alamat'] ?? '-') ?>
                </div>
            </div>

            <!-- Bergabung -->
            <div class="profile-item">
                <div class="profile-label">
                    Bergabung Sejak
                </div>

                <div class="profile-value">
                    <?= !empty($customer['created_at'])
                        ? date('d F Y', strtotime($customer['created_at']))
                        : '-'
                    ?>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">

                <a href="profile_edit.php" class="btn btn-primary">
                    ✏️ Edit Profile
                </a>

                <a href="dashboard.php" class="btn btn-outline-secondary">
                    Kembali
                </a>

            </div>

        </div>

    </div>

</div>

</body>
</html>