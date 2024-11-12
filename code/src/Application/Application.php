<?php

namespace Application;

use Domain\Controllers\AbstractController;
use Infrastructure\Config;
use Infrastructure\Storage;
use Application\Auth;

class Application {

    private const APP_NAMESPACE = 'Domain\Controllers\\';

    private string $controllerName;
    private string $methodName;

    public static Config $config;

    public static Storage $storage;

    public static Auth $auth;

    public function __construct(){
        Application::$config = new Config();
        Application::$storage = new Storage();
        Application::$auth = new Auth();
    }

    public function run() : string {
        // session_start();
        $routeArray = explode('/', $_SERVER['REQUEST_URI']);// Разделяем URL на части
        if (isset($routeArray[1]) && $routeArray[1] != '') {// Определяем контроллер
            $controllerName = $routeArray[1];
        } else {
            $controllerName = "page";
        }
        // Определяем имя контроллера с полным пространством имен
        $this->controllerName = Application::APP_NAMESPACE . ucfirst($controllerName) . "Controller";
        // Обработка POST запроса для /send-message
        if ($routeArray[1] == 'send-message' && $_SERVER['REQUEST_METHOD'] == 'POST') {
            // Устанавливаем контроллер для отправки сообщения
            $this->controllerName = Application::APP_NAMESPACE . 'SendMessageController';
            $this->methodName = 'actionSendMessage';  // Метод для отправки сообщения
        } else {
            // Для других маршрутов проверяем, существует ли класс контроллера
            if (class_exists($this->controllerName)) {
                // Определяем метод
                if (isset($routeArray[2]) && $routeArray[2] != '') {
                    $methodName = $routeArray[2];
                } else {
                    $methodName = "index";
                }
                $this->methodName = "action" . ucfirst($methodName);// Формируем имя метода
            } else {
                return "Класс $this->controllerName не существует";
            }
        }
        // Проверяем, существует ли метод
        if (method_exists($this->controllerName, $this->methodName)) {
            $controllerInstance = new $this->controllerName();
            if ($controllerInstance instanceof AbstractController) {// Проверяем права доступа
                if ($this->checkAccessToMethod($controllerInstance, $this->methodName)) {
                    return call_user_func_array(
                        [$controllerInstance, $this->methodName],
                        []);
                } else { return "Нет доступа к методу"; }
            } else {
                return call_user_func_array(
                    [$controllerInstance, $this->methodName],
                    []);
            }
        } else { return "Метод $this->methodName не существует"; }
    }

    // Метод для проверки прав доступа
    private function checkAccessToMethod(AbstractController $controllerInstance, string $methodName): bool {
        $userRoles = $controllerInstance->getUserRoles();
        $rules = $controllerInstance->getActionsPermissions($methodName);
        $isAllowed = false;
        if(!empty($rules)){
            foreach($rules as $rolePermission){
                if(in_array($rolePermission, $userRoles)){
                    $isAllowed = true;
                    break; }
            }
        }
        else{ $isAllowed = true; }
        return $isAllowed;
    }
}
