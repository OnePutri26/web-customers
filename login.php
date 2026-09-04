<?php

session_start();

require_once "config/database.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = "Username dan password wajib diisi.";

    } else {

        /*
         * Ambil user berdasarkan username
         */
        $stmt = $conn->prepare("
            SELECT id, username, password, role
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $error = "Query database gagal: " . $conn->error;

        } else {

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();

                /*
                 * Cek password
                 */
                if (password_verify($password, $user['password'])) {

                    /*
                     * Buat session
                     */
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    /*
                     * Redirect berdasarkan role
                     */
                    if ($user['role'] === 'admin') {

                        header("Location: admin/dashboard.php");
                        exit;

                    } elseif ($user['role'] === 'technician') {

                        header("Location: technician/dashboard.php");
                        exit;

                    } elseif ($user['role'] === 'customer') {

                        header("Location: customer/dashboard.php");
                        exit;

                    } else {

                        $error = "Role akun tidak dikenali.";
                    }

                } else {

                    $error = "Username atau password salah.";

                }

            } else {

                $error = "Username atau password salah.";

            }

            $stmt->close();
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

    <title>Login - WiFi Management System</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Custom CSS -->

    <link
        rel="stylesheet"
        href="assets/css/login.css"
    >

</head>


<body>


    <div class="background-circle circle-1"></div>

    <div class="background-circle circle-2"></div>


    <div class="login-wrapper">


        <div class="login-container">

            <!-- LEFT SIDE -->
            <div class="login-info">


                <div class="wifi-icon">
                    <img src="logo-yesnet.png" alt="Logo WiFi">
                </div>


                <h1>

                    WiFi<br>
                    <span>Management</span>
                </h1>


                <p>

                    Kelola layanan WiFi dengan lebih mudah,
                    cepat, dan terorganisir dalam satu sistem.

                </p>


                <div class="feature-list">


                    <div class="feature-item">

                        <div class="feature-icon">
                            ✓
                        </div>

                        <span>
                            Kelola data pelanggan
                        </span>

                    </div>


                    <div class="feature-item">

                        <div class="feature-icon">
                            ✓
                        </div>

                        <span>
                            Pantau instalasi WiFi
                        </span>

                    </div>


                    <div class="feature-item">

                        <div class="feature-icon">
                            ✓
                        </div>

                        <span>
                            Sistem terintegrasi
                        </span>

                    </div>


                </div>

            </div>


            <!-- RIGHT SIDE -->
            <div class="login-card">


                <div class="login-header">

                    <h2>
                        Selamat Datang 👋
                    </h2>

                    <p>
                        Silakan masuk ke akun Anda untuk melanjutkan.
                    </p>

                </div>


        <?php if (!empty($error)): ?>

                    <div class="alert alert-danger mb-4">

                        <?= htmlspecialchars($error) ?>

                    </div>

        <?php endif; ?>



                <!-- LOGIN FORM -->

                <form method="POST" action="">

                    <!-- Username -->

                    <div class="mb-3">

                        <label
                            for="username"
                            class="form-label"
                        >

                            Username

                        </label>


                        <div class="input-wrapper">

                            <span class="input-icon">
                                👤
                            </span>


                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-control"
                                placeholder="Masukkan username"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                required
                                autocomplete="username"
                            >

                        </div>

                    </div>



                    <!-- PASSWORD -->

                    <div class="mb-4">

                        <label
                            for="password"
                            class="form-label"
                        >

                            Password

                        </label>


                        <div class="input-wrapper">

                            <span class="input-icon">
                                🔒
                            </span>


                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Masukkan password"
                                required
                                autocomplete="current-password"
                            >

                        </div>

                    </div>



                    <!-- LOGIN BUTTON -->

                    <button
                        type="submit"
                        class="btn login-btn w-100"
                    >

                        Masuk ke Dashboard

                    </button>


                </form>



                <!-- REGISTER -->

                <div class="register-text">

                    Belum punya akun?

                    <a href="register.php">
                        Daftar sekarang
                    </a>

                </div>



                <!-- SECURITY -->

                <div class="security-text">

                    🔐 Sistem login aman & terproteksi

                </div>


            </div>


        </div>


    </div>


</body>

</html>