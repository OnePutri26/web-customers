<?php

session_start();

require_once "config/database.php";

$error = "";
$success = "";

$username  = "";
$nama      = "";
$telephone = "";
$email     = "";
$alamat    = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil data dengan aman
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $nama      = trim($_POST['nama'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');

    // Validasi
    if (
        $username === '' ||
        $password === '' ||
        $nama === '' ||
        $telephone === '' ||
        $alamat === ''
    ) {

        $error = "Semua field wajib diisi.";

    } elseif (strlen($password) < 6) {

        $error = "Password minimal 6 karakter.";

    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    } else {

        // Cek username
        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE username = ?
             LIMIT 1"
        );

        if (!$check) {

            $error = "Terjadi kesalahan database.";

        } else {

            $check->bind_param("s", $username);
            $check->execute();

            $result = $check->get_result();

            if ($result->num_rows > 0) {

                $error = "Username sudah digunakan.";

            } else {

                // Hash password
                $hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Buat user
                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (username, password, role)
                    VALUES (?, ?, 'customer')"
                );

                if (!$stmt) {

                    $error = "Gagal membuat akun.";

                } else {

                    $stmt->bind_param(
                        "ss",
                        $username,
                        $hash
                    );

                    if ($stmt->execute()) {

                        $userId = $stmt->insert_id;

                        // Buat kode pelanggan
                        $customerCode =
                            "CUS" .
                            date("Ymd") .
                            rand(100, 999);

                        // Simpan data customer
                        $customerStmt = $conn->prepare(
                            "INSERT INTO customers
                            (
                                user_id,
                                kode_pelanggan,
                                nama,
                                telephone,
                                email,
                                alamat
                            )
                            VALUES (?, ?, ?, ?, ?, ?)"
                        );

                        if (!$customerStmt) {

                            // Jika gagal membuat data customer,
                            // hapus user yang sudah dibuat
                            $delete = $conn->prepare(
                                "DELETE FROM users WHERE id = ?"
                            );

                            $delete->bind_param("i", $userId);
                            $delete->execute();
                            $delete->close();

                            $error = "Gagal menyimpan data customer.";

                        } else {

                            $customerStmt->bind_param(
                                "isssss",
                                $userId,
                                $customerCode,
                                $nama,
                                $telephone,
                                $email,
                                $alamat
                            );

                            if ($customerStmt->execute()) {

                                $success =
                                    "Registrasi berhasil. Silakan login.";

                                // Kosongkan form
                                $username  = "";
                                $nama      = "";
                                $telephone = "";
                                $email     = "";
                                $alamat    = "";

                            } else {

                                // Hapus user jika customer gagal disimpan
                                $delete = $conn->prepare(
                                    "DELETE FROM users WHERE id = ?"
                                );

                                $delete->bind_param("i", $userId);
                                $delete->execute();
                                $delete->close();

                                $error =
                                    "Data customer gagal disimpan.";
                            }

                            $customerStmt->close();
                        }

                    } else {

                        $error = "Gagal membuat akun.";
                    }

                    $stmt->close();
                }
            }

            $check->close();
        }
    }
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

    <title>Register Customer - WiFi Management System</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container">

    <div class="row justify-content-center py-5">

        <div class="col-md-6">

            <div class="card shadow border-0">

                <div class="card-body p-4">

                    <h3 class="text-center mb-2">
                        📡 WiFi Management System
                    </h3>

                    <p class="text-center text-muted mb-4">
                        Registrasi Customer
                    </p>

                    <?php if ($error !== ''): ?>

                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>

                    <?php endif; ?>

                    <?php if ($success !== ''): ?>

                        <div class="alert alert-success">
                            <?= htmlspecialchars($success) ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST" action="">

                        <!-- Nama -->
                        <div class="mb-3">

                            <label
                                for="nama"
                                class="form-label"
                            >
                                Nama Lengkap
                            </label>

                            <input
                                type="text"
                                id="nama"
                                name="nama"
                                class="form-control"
                                placeholder="Masukkan nama lengkap"
                                value="<?= htmlspecialchars($nama) ?>"
                                required
                            >

                        </div>

                        <!-- Telephone -->
                        <div class="mb-3">

                            <label
                                for="telephone"
                                class="form-label"
                            >
                                Nomor Telepon
                            </label>

                            <input
                                type="text"
                                id="telephone"
                                name="telephone"
                                class="form-control"
                                placeholder="081234567890"
                                value="<?= htmlspecialchars($telephone) ?>"
                                required
                            >

                        </div>

                        <!-- Email -->
                        <div class="mb-3">

                            <label
                                for="email"
                                class="form-label"
                            >
                                Email
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="nama@email.com"
                                value="<?= htmlspecialchars($email) ?>"
                            >

                        </div>

                        <!-- Alamat -->
                        <div class="mb-3">

                            <label
                                for="alamat"
                                class="form-label"
                            >
                                Alamat
                            </label>

                            <textarea
                                id="alamat"
                                name="alamat"
                                class="form-control"
                                rows="3"
                                placeholder="Masukkan alamat lengkap"
                                required
                            ><?= htmlspecialchars($alamat) ?></textarea>

                        </div>

                        <!-- Username -->
                        <div class="mb-3">

                            <label
                                for="username"
                                class="form-label"
                            >
                                Username
                            </label>

                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-control"
                                placeholder="Masukkan username"
                                value="<?= htmlspecialchars($username) ?>"
                                required
                            >

                        </div>

                        <!-- Password -->
                        <div class="mb-3">

                            <label
                                for="password"
                                class="form-label"
                            >
                                Password
                            </label>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Minimal 6 karakter"
                                minlength="6"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Daftar
                        </button>

                    </form>

                    <div class="text-center mt-3">

                        Sudah punya akun?

                        <a href="login.php">
                            Login di sini
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>