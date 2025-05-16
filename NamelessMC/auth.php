<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
$login = isset($data['Login']) ? trim($data['Login']) : null;
$password = $data['Password'] ?? null;

if (!$login || !$password) {
    http_response_code(400);
    echo json_encode(['Message' => 'Login и Password обязательны'], JSON_UNESCAPED_UNICODE);
    exit;
}

$config_path = realpath(__DIR__ . '/core/config.php');
if (!$config_path || !file_exists($config_path)) {
    http_response_code(500);
    echo json_encode(['Message' => 'Конфиг NamelessMC не найден'], JSON_UNESCAPED_UNICODE);
    exit;
}

$conf = require_once $config_path;
$db_conf = $conf['mysql'];

$host = $db_conf['host'];
$port = $db_conf['port'] ?? 3306;
$dbname = $db_conf['db'];
$user = $db_conf['username'];
$pass = $db_conf['password'];
$charset = $db_conf['charset'] ?? 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'Message' => 'Ошибка подключения к БД',
        'Error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("SELECT id, username, password, isbanned, active FROM nl2_users WHERE username = ?");
$stmt->execute([$login]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['Message' => 'Пользователь не найден'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((int)$user['isbanned'] === 1 || (int)$user['active'] === 0) {
    http_response_code(403);
    echo json_encode(['Message' => 'Пользователь заблокирован или не активирован'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['Message' => 'Неверный логин или пароль'], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(200);
echo json_encode([
    'Login' => $user['username'],
    'Message' => 'Успешная авторизация'
], JSON_UNESCAPED_UNICODE);
