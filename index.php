<?php
// Подключение к базе данных и импорт необходимых файлов
require_once 'config.php';
require_once 'phpqrcode/qrlib.php'; // Если используется библиотека для генерации QR-кодов

// Инициализация переменных
$shortLink = '';
$qrCodePath = '';

// Получение новостей из базы данных
$newsItems = [];
$result = $conn->query("SELECT id, title, content FROM news ORDER BY created_at DESC");
if ($result) {
    $newsItems = $result->fetch_all(MYSQLI_ASSOC);
}

// Обработка создания короткой ссылки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['link'])) {
    $longUrl = $_POST['link'];

    // Генерация уникального идентификатора ссылки
    $shortCode = substr(md5(uniqid(rand(), true)), 0, 6);
    $shortLink = "https://lnk.monster/$shortCode";

    // Сохранение в базе данных
    $stmt = $conn->prepare("INSERT INTO four (long_url, short_url) VALUES (?, ?)");
    if (!$stmt) {
        die('Ошибка подготовки запроса: ' . $conn->error);
    }
    $stmt->bind_param("ss", $longUrl, $shortCode);
    $stmt->execute();

    // Генерация QR-кода
    $qrCodePath = 'qrcodes/' . $shortCode . '.png';
    QRcode::png($shortLink, $qrCodePath, QR_ECLEVEL_L, 10);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>4OUR - Умные Ссылки</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .main-container {
            min-height: 100svh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: #333;
            background-color: #f8f9fa;
        }
        .cta-text {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .input-group, .result-card {
            max-width: 600px;
            width: 100%;
            animation: fadeInUp 1.5s ease;
        }
        .news-carousel {
            position: fixed;
            bottom: 20px;
            width: 100%;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px 20px;
        }
        .news-carousel .carousel-item {
            text-align: center;
        }
        .news-carousel .news-title {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .news-carousel .news-content {
            font-size: 1rem;
            margin-top: 5px;
        }
        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <!-- Главный блок с полем для ссылки и кнопкой "Create" -->
    <div class="main-container">
        <h1 class="cta-text">Make Your Links Roar with <a href="dashboard" style="text-decoration: none; color: inherit;"><em>lnk.monster!</em></a></h1>
        
        <!-- Форма для ввода ссылки -->
        <form class="input-group" action="" method="POST">
            <input type="url" class="form-control" placeholder="Enter your link" name="link" required>
            <button type="submit" class="btn btn-primary">Create</button>
        </form>

        <?php if ($shortLink): ?>
            <!-- Карточка с короткой ссылкой и QR-кодом -->
            <div class="result-card card mt-5 p-4">
                <p><a href="<?= $shortLink ?>" target="_blank"><?= $shortLink ?></a></p>
                <img src="<?= $qrCodePath ?>" alt="QR Code">
            </div>
        <?php endif; ?>
    </div>

    <!-- Карусель новостей -->
    <div id="newsCarousel" class="carousel slide news-carousel" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php foreach ($newsItems as $index => $news): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <div class="news-title"><?= htmlspecialchars($news['title']) ?></div>
                    <div class="news-content"><?= htmlspecialchars($news['content']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#newsCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#newsCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>