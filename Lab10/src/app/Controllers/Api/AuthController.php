<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Models\EmployeeRepository;

final class AuthController
{
    private EmployeeRepository $employees;

    public function __construct()
    {
        $this->employees = new EmployeeRepository();
    }

    public function login(): void
    {
        $data = json_input();
        $errors = [];
        if (!is_string($data['email'] ?? null) || trim($data['email']) === '') {
            $errors['email'] = 'Введите email';
        }
        if (!is_string($data['password'] ?? null) || $data['password'] === '') {
            $errors['password'] = 'Введите пароль';
        }
        if ($errors) {
            json_error('Ошибка проверки данных', 422, $errors);
        }

        $user = $this->employees->findByEmail(trim($data['email']));
        if ($user === null || !password_verify($data['password'], $user['password_hash'])) {
            json_error('Неверный email или пароль', 401);
        }

        $session = Auth::issueToken($user['id']);
        json_response([
            'token'      => $session['token'],
            'expires_at' => $session['expires_at'],
            'user'       => public_user($user),
        ]);
    }

    public function me(): void
    {
        json_response(public_user(require_auth()));
    }

    public function logout(): void
    {
        $token = bearer_token();
        if ($token !== null) {
            Auth::revoke($token);
        }
        no_content();
    }
}