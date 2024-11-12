<?php
session_start();

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo 'Полученный токен: ' . htmlspecialchars($csrf_token) . ' | Ожидаемый токен: ' . htmlspecialchars($_SESSION['csrf_token']);
        die('CSRF token validation failed.');
    }

    // Получение данных из формы
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    // Простейшая валидация
    if (empty($name) || empty($email) || empty($message)) {
        die('Пожалуйста, заполните все поля.');
    }

    // Проверка корректности email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Некорректный email адрес.');
    }

    // Здесь вы можете добавить логику для отправки сообщения
    // Например, отправка email
    $to = 'recipient@example.com'; // Замените на ваш email
    $subject = 'Новое сообщение от ' . $name;
    $body = "Имя: $name\nEmail: $email\nСообщение:\n$message";
    $headers = "From: $email\r\n";

    // Отправка email
    if (mail($to, $subject, $body, $headers)) {
        echo 'Сообщение успешно отправлено!';
    } else {
        echo 'Ошибка при отправке сообщения. Попробуйте позже.';
    }
} else {
    // Если запрос не POST, выводим сообщение об ошибке
    die('Неверный запрос.');
}
?>
