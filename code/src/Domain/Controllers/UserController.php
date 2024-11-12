<?php

namespace Domain\Controllers;

use Application\Application;
use Application\Render;
use Application\Auth;
use Domain\Models\User;

class UserController extends AbstractController {

    protected array $actionsPermissions = [
        'actionHash' => ['admin', 'some'],
        'actionSave' => ['admin'],
        'actionDelete' => ['admin'] // добавляем проверку actionDelete
    ];

    public function actionIndex(): string {
        $users = User::getAllUsersFromStorage();
        $render = new Render();
        $serverTime = date('H:i:s');// Получаем серверное время
        // Получаем данные текущего пользователя по ID из сессии
        $currentUser = null;
        if (isset($_SESSION['id_user'])) {
            $currentUser = User::getUserById($_SESSION['id_user']);
        }
        // Преобразуем данные текущего пользователя в массив
        $currentUserData = null;
        if ($currentUser) {
            $currentUserData = [
                'userName' => $currentUser->getUserName(),
                'userLastName' => $currentUser->getUserLastName()];
        }
        // Устанавливаем переменную, которая будет указывать, авторизован ли пользователь
        $user_authorized = isset($_SESSION['id_user']) && $_SESSION['id_user'] > 0;
        if(!$users){
            return $render->renderPage(
                'user-empty.twig',
                [
                    'title' => 'Список пользователей в хранилище',
                    'message' => "Список пуст или не найден",
                    'server_time' => $serverTime,  // Передаем серверное время в шаблон
                    'user_authorized' => $user_authorized, // Передаем состояние авторизации
                    'currentUser' => $currentUserData]);  // Передаем текущего пользователя
        } else {
            return $render->renderPage(
                'user-index.twig',
                [
                    'title' => 'Список пользователей в хранилище',
                    'users' => $users,
                    'isAdmin' => User::isAdmin($_SESSION['id_user'] ?? null),
                    'server_time' => $serverTime,  // Передаем серверное время в шаблон
                    'user_authorized' => $user_authorized, // Передаем состояние авторизации
                    'currentUser' => $currentUserData]);  // Передаем данные текущего пользователя в виде массива
        }
    }

    public function actionIndexRefresh(){
        $limit = null;
        if(isset($_POST['maxId']) && ($_POST['maxId'] > 0)){
            $limit = $_POST['maxId'];}
        $users = User::getAllUsersFromStorage($limit);
        $usersData = [];
        if(count($users) > 0) {
            foreach($users as $user){
                $usersData[] = $user->getUserDataAsArray();}
        }
        return json_encode($usersData);
    }

    public function actionSave(): string {
        // Проверка авторизации пользователя
        $user_authorized = isset($_SESSION['id_user']) && $_SESSION['id_user'] > 0;
        // Инициализация переменной для данных текущего пользователя
        $currentUserData = null;
        if ($user_authorized) {
            $currentUser = User::getUserById($_SESSION['id_user']);
            $currentUserData = [
                'userName' => $currentUser->getUserName(),
                'userLastName' => $currentUser->getUserLastName(),
            ];
        }
        if (User::validateRequestData()) {
            // Если передан ID пользователя, редактируем его, иначе создаем нового
            $userId = $_POST['user_id'] ?? null;
            if ($userId) {
                $user = User::getUserById($userId);
                if (!$user) {
                    throw new \Exception("Пользователь с таким ID не найден");
                }
                $user->setParamsFromRequestData();  // обновляем параметры
            } else {
                $user = new User();
                $user->setParamsFromRequestData();  // создаем нового пользователя
            }
            $user->saveToStorage();  // сохраняем (вставляем или обновляем)
            $render = new Render();
            return $render->renderPage(
                'user-created.twig',
                [
                    'title' => 'Пользователь сохранен',
                    'message' => "Пользователь " . $user->getUserName() . " " . $user->getUserLastName() . " сохранен.",
                    'user_authorized' => $user_authorized,  // Флаг авторизации
                    'currentUser' => $currentUserData  // Данные текущего пользователя
                ]);
        } else {
            throw new \Exception("Переданные данные некорректны");
        }
    }

    public function actionEdit(): string {
        if (!isset($_GET['id_user']) || empty($_GET['id_user'])) {
            throw new \Exception('ID пользователя не передан');
        }
        $userId = (int)$_GET['id_user'];
        $user = User::getUserById($userId);
        if (!$user) {
            throw new \Exception('Пользователь с таким ID не найден');
        }
        // Проверка авторизации пользователя
        $user_authorized = isset($_SESSION['id_user']) && $_SESSION['id_user'] > 0;
        // Инициализация переменной для данных текущего пользователя
        $currentUserData = null;
        if ($user_authorized) {
            $currentUser = User::getUserById($_SESSION['id_user']);
            $currentUserData = [
                'userName' => $currentUser->getUserName(),
                'userLastName' => $currentUser->getUserLastName(),];
        }
        $render = new Render();
        return $render->renderPageWithForm(
            'user-form.twig',
            [
                'title' => 'Редактирование пользователя',
                'user' => $user,
                'csrf_token' => $_SESSION['csrf_token'],
                'user_authorized' => $user_authorized,  // Флаг авторизации
                'currentUser' => $currentUserData]  // Данные текущего пользователя
        );
    }

    public function actionAuth(): string {
        $render = new Render();
        return $render->renderPageWithForm(
                'user-auth.twig',
                ['title' => 'Форма логина']);
    }

    public function actionHash(): string {
        return Auth::getPasswordHash($_GET['pass_string']);
    }

    public function actionLogin(): string {
        $result = false;
        if(isset($_POST['login']) && isset($_POST['password'])){
            $result = Application::$auth->proceedAuth($_POST['login'], $_POST['password']);
        }
        if(!$result){
            $render = new Render();
            return $render->renderPageWithForm(
                'user-auth.twig',
                [
                    'title' => 'Форма логина',
                    'auth-success' => false,
                    'auth-error' => 'Неверные логин или пароль'
                ]);
        }
        else {
            header('Location: /');
            return "";
        }
    }
    public function actionLogout(){// Завершаем сессию
        session_unset(); // Удаляем все переменные сессии
        session_destroy(); // Уничтожаем сессию
        // Редирект на страницу входа или главную страницу
        header('Location: /user/login'); // Или на главную: '/';
        exit(); // Останавливаем выполнение скрипта, чтобы редирект произошел
    }

    public function actionDelete(): string
    {
        if (!User::isAdmin($_SESSION['id_user'] ?? null)) {
            throw new \Exception('У вас нет прав для удаления пользователя');
        }
        if ($_SESSION['id_user'] == $_GET['user-id']) {
            throw new \Exception('Нельзя удалить самого себя');
        }
        // Проверяем, передан ли параметр 'user-id'
        if (!isset($_GET['user-id']) || empty($_GET['user-id'])) {
            throw new \Exception('ID пользователя не передан');
        }
        $userId = (int)$_GET['user-id']; // Получаем ID из параметров запроса
        $user = User::getUserById($userId);// Получаем пользователя по ID
        if (!$user) {
            throw new \Exception('Пользователь с таким ID не найден');
        }
        $user->delete();// Удаляем пользователя
        header('Location: /user/index');// После удаления редиректим на страницу...
        exit(); // Останавливаем выполнение скрипта, чтобы редирект прошел корректно
    }


    public function actionForm(): string { // Проверка авторизации пользователя
        $user_authorized = isset($_SESSION['id_user']) && $_SESSION['id_user'] > 0;
        // Инициализация переменной для данных текущего пользователя
        $currentUserData = null;
        if ($user_authorized) {
            $currentUser = User::getUserById($_SESSION['id_user']);
            $currentUserData = [
                'userName' => $currentUser->getUserName(),
                'userLastName' => $currentUser->getUserLastName(),
            ];
        }
        // Генерация CSRF токена
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $render = new Render();// Рендерим страницу с формой
        return $render->renderPageWithForm(
            'user-form.twig',  // Шаблон формы
            [
                'title' => 'Форма создания пользователя',  // Заголовок формы
                'csrf_token' => $_SESSION['csrf_token'],  // CSRF токен
                'user_authorized' => $user_authorized,  // Флаг авторизации
                'currentUser' => $currentUserData]  // Данные пользователя
        );
    }

    // Метод для отображения страницы контактов
    public function actionContact() {
        // Проверка авторизации пользователя
        $user_authorized = isset($_SESSION['id_user']) && $_SESSION['id_user'] > 0;
        // Инициализация переменной для данных текущего пользователя
        $currentUserData = null;
        if ($user_authorized) {
            $currentUser = User::getUserById($_SESSION['id_user']);
            $currentUserData = [
                'userName' => $currentUser->getUserName(),
                'userLastName' => $currentUser->getUserLastName(),];
        }
        // Данные для контактов
        $contactData = [
            'name' => 'Брайцев Константин Алексеевич',
            'phone' => '+375 (29) 158-68-50',
            'email' => 'kastett@mail.ru',
            'address' => 'Беларусь, г. Гомель, ул. Белицкая, д. 23, кв. 109',
            'user_authorized' => $user_authorized,
            'currentUser' => $currentUserData]; // Передаем данные пользователя
        $render = new Render();// Рендерим шаблон с контактами
        return $render->renderPage('user-contact.twig', $contactData);
    }
}