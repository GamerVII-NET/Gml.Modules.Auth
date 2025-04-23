<?php

if(!defined("MCR")){ exit("Hacking Attempt!"); }

class submodule{
    private $core, $db, $user, $cfg, $lng;

    public function __construct($core){
        $this->core = $core;
        $this->db = $core->db;
        $this->user = $core->user;
        $this->cfg = $core->cfg;
        $this->lng = $core->lng_m;
    }

    private function sendResponse($code, $data) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function content(){
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->sendResponse(200, null);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendResponse(405, ['Message' => 'Method not allowed']);
        }

        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            if (!empty($_POST)) {
                $data = $_POST;
            } else {
                $this->sendResponse(400, ['Message' => 'No input data']);
            }
        } else {
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->sendResponse(400, ['Message' => 'Invalid JSON format: ' . json_last_error_msg()]);
            }
        }

        if (!isset($data['Login']) || !isset($data['Password'])) {
            $this->sendResponse(400, ['Message' => 'Login and Password are required']);
        }

        $login = $this->db->safesql($data['Login']);
        
        $ctables = $this->cfg->db['tables'];
        $ug_f = $ctables['ugroups']['fields'];
        $us_f = $ctables['users']['fields'];

        $query_text = "SELECT `u`.`id`, `u`.`login`, `u`.`password`, 
                             `u`.`salt`, `u`.`gid`, `u`.`ban_server`,
                             `u`.`uuid`, `g`.`permissions`
                      FROM `mcr_users` AS `u`
                      INNER JOIN `mcr_groups` AS `g`
                          ON `g`.`id`=`u`.`gid`
                      WHERE `u`.`login`='$login'
                      LIMIT 1";

        $query = $this->db->query($query_text);

        if (!$query || $this->db->num_rows($query) <= 0) {
            $this->sendResponse(404, ['Message' => 'Пользователь не найден']);
        }

        $ar = $this->db->fetch_assoc($query);
        
        $methods = [
            'Method 1 (sha1)' => sha1($data['Password']),
            'Method 2 (sha256)' => hash('sha256', $data['Password']),
            'Method 3 (sha512)' => hash('sha512', $data['Password']),
            'Method 4 (md5(md5))' => md5(md5($data['Password'])),
            'Method 5 (Joomla)' => md5($data['Password'].$ar['salt']),
            'Method 6 (osCommerce)' => md5($ar['salt'].$data['Password']),
            'Method 7 (vBulletin)' => md5(md5($ar['salt']).$data['Password']),
            'Method 8' => md5(md5($data['Password']).$ar['salt']),
            'Method 9' => md5($data['Password'].md5($ar['salt'])),
            'Method 10' => md5($ar['salt'].md5($data['Password'])),
            'Method 11' => sha1($data['Password'].$ar['salt']),
            'Method 12' => sha1($ar['salt'].$data['Password']),
            'Method 13 (IPB)' => md5(md5($ar['salt']).md5($data['Password'])),
            'Method 14' => hash('sha256', $data['Password'].$ar['salt']),
            'Method 15' => hash('sha512', $data['Password'].$ar['salt']),
            'Method 0 (default)' => md5($data['Password'])
        ];
        
        $password_valid = false;
        foreach ($methods as $hash) {
            if ($hash === $ar['password']) {
                $password_valid = true;
                break;
            }
        }

        if (!$password_valid) {
            $this->sendResponse(401, ['Message' => 'Неверный логин или пароль']);
        }

        if (!empty($ar['ban_server'])) {
            $this->sendResponse(403, ['Message' => "Пользователь заблокирован"]);
        }

        $permissions = json_decode($ar['permissions'], true);
        if (!@$permissions['sys_auth']) {
            $this->sendResponse(403, ['Message' => 'Пользователь не имеет доступа']);
        }

        $uuid = (!empty($ar['uuid'])) ? $ar['uuid'] : $this->generateUUID($ar['id']);

        $this->sendResponse(200, [
            'Login' => $ar['login'],
            'UserUuid' => $uuid,
            'Message' => 'Успешная авторизация'
        ]);
    }

    private function generateUUID($userId) {
        $hash = md5($userId . $this->cfg->main['mcr_secury']);
        return sprintf('%08s-%04s-%04x-%04x-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x4000,
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            substr($hash, 20, 12)
        );
    }
} 