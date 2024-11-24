<?php
ob_start(); // Начинаем буферизацию вывода
require_once 'config.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка пароля</title>

    <!-- Подключение Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Собственные стили (опционально) -->
    <style>
        .password-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        .password-container h2 {
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <?php
    if (isset($_GET['id']) || isset($_GET['url'])) {
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $conn->prepare("SELECT long_url, password FROM four WHERE id = ?");
            $stmt->bind_param("i", $id);
        } else if (isset($_GET['url'])) {
            $short_url = $_GET['url'];
            $stmt = $conn->prepare("SELECT long_url, password FROM four WHERE short_url = ?");
            $stmt->bind_param("s", $short_url);
        }

        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($long_url, $link_password);
        $stmt->fetch();

        if ($stmt->num_rows > 0) {
            if ($link_password) {
                if (isset($_POST['password'])) {
                    $entered_password = $_POST['password'];

                    if (password_verify($entered_password, $link_password)) {
                        // Увеличиваем счетчик переходов после успешного ввода пароля
                        if (isset($_GET['id'])) {
                            $update_stmt = $conn->prepare("UPDATE four SET qr_count = qr_count + 1 WHERE id = ?");
                            $update_stmt->bind_param("i", $id);
                        } else if (isset($_GET['url'])) {
                            $update_stmt = $conn->prepare("UPDATE four SET short_count = short_count + 1 WHERE short_url = ?");
                            $update_stmt->bind_param("s", $short_url);
                        }
                        $update_stmt->execute();
                        $update_stmt->close();

                        // Пароль верный, перенаправляем на длинную ссылку
                        header("Location: " . $long_url);
                        exit();
                    } else {
                        // Ошибка. Выводим форму для повторного ввода пароля
                        echo "<div class='password-container'>";
                        echo "<h2>Ошибка</h2>";
                        echo "<p>Неверный пароль. Попробуйте ещё раз:</p>";
                        echo "<form method='POST'>";
                        echo "<input type='password' class='form-control mb-3' name='password' placeholder='Введите пароль'>";
                        echo "<button type='submit' class='btn btn-primary w-100'>Подтвердить</button>";
                        echo "</form>";
                        echo "</div>";
                    }
                } else {
                    // Выводим форму для ввода пароля
                    echo "<div class='password-container'>";
                    echo "<h2>Введите пароль</h2>";
                    echo "<form method='POST'>";
                    echo "<input type='password' class='form-control mb-3' name='password' placeholder='Введите пароль'>";
                    echo "<button type='submit' class='btn btn-primary w-100'>Подтвердить</button>";
                    echo "</form>";
                    echo "</div>";
                }
            } else {
                // Увеличиваем счетчик переходов, если пароля нет
                if (isset($_GET['id'])) {
                    $update_stmt = $conn->prepare("UPDATE four SET qr_count = qr_count + 1 WHERE id = ?");
                    $update_stmt->bind_param("i", $id);
                } else if (isset($_GET['url'])) {
                    $update_stmt = $conn->prepare("UPDATE four SET short_count = short_count + 1 WHERE short_url = ?");
                    $update_stmt->bind_param("s", $short_url);
                }
                $update_stmt->execute();
                $update_stmt->close();

                // Перенаправляем сразу на длинную ссылку
                header("Location: " . $long_url);
                exit();
            }
        } else {
            echo "Ссылка не найдена.";
        }

        $stmt->close();
    } else {
        echo "ID или URL не указан.";
    }
    $conn->close();
    ?>
</div>

<!-- Подключение Bootstrap JS и Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
ob_end_flush(); // Заканчиваем буферизацию и отправляем данные
?>