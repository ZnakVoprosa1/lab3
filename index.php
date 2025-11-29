<?php
declare(strict_types=1);

namespace App {
    class User {
        private string $file = __DIR__ . '/users.json';
        private array $users;

        public function __construct() {
            if (!file_exists($this->file)) {
                file_put_contents($this->file, json_encode([]));
            }
            $this->users = json_decode(file_get_contents($this->file), true);
        }

        public function register(string $username, string $password, array $settings): bool {
            if (isset($this->users[$username])) {
                return false;
            }
            $this->users[$username] = [
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'settings' => $settings
            ];
            $this->save();
            return true;
        }

        public function login(string $username, string $password): bool {
            if (isset($this->users[$username]) &&
                password_verify($password, $this->users[$username]['password'])
            ) {
                $_SESSION['user'] = $username;
                return true;
            }
            return false;
        }

        public function logout(): void {
            unset($_SESSION['user']);
        }

        public function getCurrentUser(): ?array {
            $username = $_SESSION['user'] ?? null;
            if ($username && isset($this->users[$username])) {
                return [
                    'username' => $username,
                    'settings' => $this->users[$username]['settings'],
                ];
            }
            return null;
        }

        private function save(): void {
            file_put_contents(
                $this->file,
                json_encode($this->users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }
    }
}

namespace {
    session_start();
    use App\User;

    $user = new User();
    $currentUser = $user->getCurrentUser();
    $message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['action'] === 'register') {
            $ok = $user->register(
                $_POST['username'] ?? '',
                $_POST['password'] ?? '',
                [
                    'bg_color'   => $_POST['bg_color'] ?? '#ffffff',
                    'font_color' => $_POST['font_color'] ?? '#000000',
                ]
            );
            $message = $ok ? 'Регистрация успешна!' : 'Пользователь уже существует!';
        }

        if ($_POST['action'] === 'login') {
            $ok = $user->login(
                $_POST['username'] ?? '',
                $_POST['password'] ?? ''
            );
            if ($ok) {
                $message = 'Вход успешен!';
                $currentUser = $user->getCurrentUser();
            } else {
                $message = 'Неверный логин или пароль!';
            }
        }

        if ($_POST['action'] === 'logout') {
            $user->logout();
            $message = 'Вы вышли';
            $currentUser = null;
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Профиль пользователя</title>
        <?php if ($currentUser): ?>
        <style>
            body {
                background-color: <?= $currentUser['settings']['bg_color'] ?>;
                color:            <?= $currentUser['settings']['font_color'] ?>;
            }
        </style>
        <?php endif; ?>
    </head>
    <body>
        <?php if ($message): ?>
            <p><?= $message ?></p>
        <?php endif; ?>

        <?php if (!$currentUser): ?>
            <h2>Регистрация</h2>
            <form method="post">
                <input type="hidden" name="action" value="register">
                Логин: <input type="text" name="username"><br>
                Пароль: <input type="password" name="password"><br>
                Цвет фона: <input type="color" name="bg_color" value="#ffffff"><br>
                Цвет шрифта: <input type="color" name="font_color" value="#000000"><br>
                <button type="submit">Зарегистрироваться</button>
            </form>

            <h2>Вход</h2>
            <form method="post">
                <input type="hidden" name="action" value="login">
                Логин: <input type="text" name="username"><br>
                Пароль: <input type="password" name="password"><br>
                <button type="submit">Войти</button>
            </form>
        <?php else: ?>
            <h2>Добро пожаловать, <?= $currentUser['username'] ?>!</h2>
            <form method="post">
                <input type="hidden" name="action" value="logout">
                <button type="submit">Выйти</button>
            </form>
        <?php endif; ?>
    </body>
    </html>
    <?php
} // конец глобального namespace
?>