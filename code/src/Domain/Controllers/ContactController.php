<?php
namespace Domain\Controllers;
class ContactController {
    private $twig;

    public function __construct($twig) {
        $this->twig = $twig;
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->sendMessage();
        } else {
            $this->showForm();
        }
    }

    private function sendMessage() {
        // Проверка CSRF токена
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token validation failed.');
        }

        // Получение данных из формы
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $message = trim($_POST['message']);

        // Валидация
        if (empty($name) || empty($email) || empty($message)) {
            die('Пожалуйста, заполните все поля.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die('Некорректный email адрес.');
        }

        // Отправка сообщения
        $to = 'recipient@example.com'; // Замените на ваш email
        $subject = 'Новое сообщение от ' . $name;
        $body = "Имя: $name\nEmail: $email\nСообщение:\n$message";
        $headers = "From: $email\r\n";

        if (mail($to, $subject, $body, $headers)) {
            echo 'Сообщение успешно отправлено!';
        } else {
            echo 'Ошибка при отправке сообщения. Попробуйте позже.';
        }
    }

    private function showForm() {
        echo $this->twig->render('contact.twig', [
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }
}
?>
