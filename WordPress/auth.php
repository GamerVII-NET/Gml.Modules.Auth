<?php
/*
Plugin Name: Custom Auth API
*/

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/auth', [
        'methods' => 'POST',
        'callback' => 'custom_auth_handler',
        'args' => [
            'Login' => ['required' => true],
            'Password' => ['required' => true],
        ],
        'permission_callback' => '__return_true'
    ]);
});

add_filter('rest_pre_serve_request', function ($served, $result, $request, $server) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    if ($result instanceof WP_REST_Response) {
        $result = $result->get_data();
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    return true;
}, 10, 4);


function custom_auth_handler($request) {
    $login = sanitize_text_field($request->get_param('Login'));
    $password = $request->get_param('Password');

    $user = get_user_by('login', $login);
    if (!$user) {
        return new WP_REST_Response(['Message' => 'Пользователь не найден'], 404);
    }

    $blocked_reason = get_user_meta($user->ID, 'blocked_reason', true);
    if (!empty($blocked_reason)) {
        return new WP_REST_Response(['Message' => "Пользователь заблокирован. Причина: $blocked_reason"], 403);
    }

    if (!wp_check_password($password, $user->user_pass, $user->ID)) {
        return new WP_REST_Response(['Message' => 'Неверный логин или пароль'], 401);
    }

    return new WP_REST_Response([
        'Login' => $user->user_login,
        'Message' => 'Успешная авторизация'
    ], 200);
}
