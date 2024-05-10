<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
include 'engine/api/api.class.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array(
        'success' => false,
        'message' => 'Метод запроса должен быть POST'
    ));

    http_response_code(405);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$login = isset($data['Login']) ? $data['Login'] : '';
$password = isset($data['Password']) ? $data['Password'] : '';

if (!empty($login) && !empty($password)) {

    function check_authorization($login, $password)
    {
        global $dle_api;

        if ($dle_api->external_auth($login, $password)) {

            $result = array(
                'success' => true,
                'message' => 'Авторизация успешна'
            );

            http_response_code(200);

        } else {
            $result = array(
                'success' => false,
                'message' => 'Неверный логин или пароль'
            );

            http_response_code(401);
        }

        return $result;
    }

    $authResult = check_authorization($login, $password);

    echo json_encode($authResult);
} else {
    http_response_code(400);
    echo json_encode(array(
        'success' => false,
        'message' => 'Отсутствует логин или пароль'
    ));

}
