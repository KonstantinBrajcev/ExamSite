<?php

namespace Domain\Controllers;

use Application\Render;
use Domain\Models\User;

class PageController {

    public function actionIndex() {
        $render = new Render();
        $user_authorized = $this->isUserAuthorized();
        if ($user_authorized) { // Если пользователь авторизован, получаем данные
            $currentUser = User::getUserById($_SESSION['id_user']); // Получаем данные пользователя из модели
            $currentUserData = [
                'userName' => $currentUser->getUserName(),
                'userLastName' => $currentUser->getUserLastName(),];
        } else { $currentUserData = null; }
        return $render->renderPage('page-index.twig', [
            'title' => 'Главная страница',
            'user_authorized' => $user_authorized,
            'currentUser' => $currentUserData // передаем данные пользователя
        ]);
    }

    private function isUserAuthorized() { // Метод для проверки авторизации
        return isset($_SESSION['id_user']) && $_SESSION['id_user'] > 0;
    }
}
