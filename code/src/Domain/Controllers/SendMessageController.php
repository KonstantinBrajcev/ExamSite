<?php
namespace Domain\Controllers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class SendMessageController extends AbstractController {

    public function actionSendMessage() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Проверка CSRF токена
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die("Ошибка CSRF. Попробуйте повторить запрос."); }
            $name = $_POST['name']; // Получаем данные из формы
            $email = $_POST['email'];
            $message = $_POST['message'];
            if (!empty($name) && !empty($email) && !empty($message)) { // Проверяем, что данные заполнены
                $mail = new PHPMailer(true); // Настройка PHPMailer
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'kastettb';
                    $mail->Password = 'kas5127766';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom($email, $name);
                    $mail->addAddress('kastettb@gmail.com');
                    $mail->isHTML(true);
                    $mail->Subject = 'Новое сообщение с сайта';
                    $mail->Body = "Имя: $name<br>Email: $email<br>Сообщение: $message";
                    $mail->send();
                    echo 'Сообщение отправлено';
                } catch (Exception $e) {echo "Ошибка при отправке сообщения: {$mail->ErrorInfo}";}
            } else {echo "Пожалуйста, заполните все поля.";}
        }
    }
}