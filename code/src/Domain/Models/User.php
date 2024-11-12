<?php

namespace Domain\Models;

use Application\Application;
use Application\Auth;

class User {
    private ?int $userId;
    private ?string $userName;
    private ?string $userLastName;
    private ?int $userBirthday;
    private ?string $userLogin;
    private ?string $userPassword;

    public function __construct(int $id = null, string $name = null, string $lastName = null, int $birthday = null){
        $this->userId = $id;
        $this->userName = $name;
        $this->userLastName = $lastName;
        $this->userBirthday = $birthday;
    }

    public function setName(string $userName) : void {
        $this->userName = $userName;
    }

    public function setLastName(string $userLastName) : void {
        $this->userLastName = $userLastName;
    }

    public function getUserName(): ?string {
        return $this->userName;
    }

    public function getUserLastName(): ?string {
        return $this->userLastName;
    }

    public function getUserBirthday(): ?int {
        return $this->userBirthday;
    }

    public function getUserLogin(): ?string {
        return $this->userLogin; // Возвращаем логин
    }

    public function getUserId(): ?int {
        return $this->userId;
    }

    public function setBirthdayFromString(string $birthdayString) : void {
        $this->userBirthday = strtotime($birthdayString);
    }

    public static function getAllUsersFromStorage(?int $limit = null): array {
        $sql = "SELECT * FROM users";
        if(isset($limit) && $limit > 0) { $sql .= " WHERE id_user > " .(int)$limit; }
        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute();
        $result = $handler->fetchAll();
        $users = [];
        foreach($result as $item) {
            $user = new User($item['id_user'], $item['user_name'], $item['user_lastname'], $item['user_birthday_timestamp']);
            $users[] = $user; }
        return $users;
    }

    public static function validateRequestData(): bool{
        $result = true;
        if(!(
            isset($_POST['name']) && !empty($_POST['name']) &&
            isset($_POST['lastname']) && !empty($_POST['lastname']) &&
            isset($_POST['birthday']) && !empty($_POST['birthday']) &&
            isset($_POST['login']) && !empty($_POST['login']) &&
            isset($_POST['password']) && !empty($_POST['password'])
        )){ $result = false; }
        if(!preg_match('/^(\d{2}-\d{2}-\d{4})$/', $_POST['birthday'])){$result =  false;}
        if(!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] != $_POST['csrf_token']){$result = false;}
        return $result;
    }

    public function setParamsFromRequestData(): void {
        $this->userName = htmlspecialchars($_POST['name']);
        $this->userLastName = htmlspecialchars($_POST['lastname']);
        $this->setBirthdayFromString($_POST['birthday']);
        $this->userLogin = htmlspecialchars($_POST['login']);
        $this->userPassword = Auth::getPasswordHash($_POST['password']);
    }

    public function saveToStorage() {
        if ($this->userId) { // Обновляем существующего пользователя
            $sql = "UPDATE users SET user_name = :user_name, user_lastname = :user_lastname, user_birthday_timestamp = :user_birthday, `login` = :user_login, password_hash = :user_password WHERE id_user = :id_user";
            $handler = Application::$storage->get()->prepare($sql);
            $handler->execute([
                'id_user' => $this->userId,
                'user_name' => $this->userName,
                'user_lastname' => $this->userLastName,
                'user_birthday' => $this->userBirthday,
                'user_login' => $this->userLogin,
                'user_password' => $this->userPassword ]);
        } else {// Создаем нового пользователя
            $sql = "INSERT INTO users(user_name, user_lastname, user_birthday_timestamp, `login`, password_hash) VALUES (:user_name, :user_lastname, :user_birthday, :user_login, :user_password)";
            $handler = Application::$storage->get()->prepare($sql);
            $handler->execute([
                'user_name' => $this->userName,
                'user_lastname' => $this->userLastName,
                'user_birthday' => $this->userBirthday,
                'user_login' => $this->userLogin,
                'user_password' => $this->userPassword ]);
        }
    }

    public function getUserDataAsArray(): array {
        $userArray = [
            'id' => $this->userId,
            'username' => $this->userName,
            'userlastname' => $this->userLastName,
            'userbirthday' => date('d.m.Y', $this->userBirthday)];
        return $userArray;
    }

    public static function isAdmin(?int $idUser): bool {
        if ($idUser > 0) {
            $sql = "SELECT role FROM user_roles WHERE role = 'admin' AND id_user = :id_user";
            $handler = Application::$storage->get()->prepare($sql);
            $handler->execute(['id_user' => $idUser]);
            return count($handler->fetchAll()) > 0;
        }
        return false;
    }

    public static function getUserById(int $id): ?User
    {
        $sql = "SELECT * FROM users WHERE id_user = :id";
        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute(['id' => $id]);
        $result = $handler->fetch();
        if ($result) {
            $user = new User(
                $result['id_user'],
                $result['user_name'],
                $result['user_lastname'],
                $result['user_birthday_timestamp']);
            $user->userLogin = $result['login'];  // Инициализируем $userLogin
            return $user; }
        return null; // если пользователь не найден
    }

    public function delete(): bool // Метод для удаления пользователя
    {
        $sql = "DELETE FROM users WHERE id_user = :id_user"; // Удаляем пользователя по ID
        $handler = Application::$storage->get()->prepare($sql);
        $handler->execute(['id_user' => $this->userId]);
        return $handler->rowCount() > 0; // Возвращаем true, если удаление успешно
    }

}