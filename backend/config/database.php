<?php
/**
 * Kết nối database (PDO) dùng chung cho toàn bộ backend.
 *
 * Dùng:   $pdo = getDBConnection();
 *
 * Mặc định khớp XAMPP/Laragon: 127.0.0.1:3306, database `fitness_center`, user `root`,
 * mật khẩu rỗng. Máy khác thì đặt biến môi trường DB_HOST, DB_PORT, DB_NAME,
 * DB_USER, DB_PASS thay vì sửa file này.
 */

if (!function_exists('getDBConnection')) {

    function getDBConnection(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $env = static function (string $key, string $default): string {
            $value = getenv('DB_' . $key);
            return ($value !== false) ? $value : $default;
        };

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $env('HOST', '127.0.0.1'),
            $env('PORT', '3306'),
            $env('NAME', 'fitness_center')
        );

        $user = $env('USER', 'root');
        $passEnv = getenv('DB_PASS');
        $passwords = ($passEnv !== false) ? [$passEnv] : ['', '123456'];

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        if (defined('Pdo\Mysql::ATTR_INIT_COMMAND')) {
            $options[\Pdo\Mysql::ATTR_INIT_COMMAND] = "SET time_zone = '+00:00'";
        } elseif (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET time_zone = '+00:00'";
        }

        $lastException = null;
        foreach ($passwords as $pass) {
            try {
                $pdo = new PDO($dsn, $user, $pass, $options);
                return $pdo;
            } catch (PDOException $e) {
                $lastException = $e;
            }
        }

        // Ghi log, không trả thông tin kết nối ra ngoài.
        error_log('DB connection failed: ' . ($lastException ? $lastException->getMessage() : 'Unknown error'));
        throw new RuntimeException('Khong the ket noi co so du lieu');

        return $pdo;
    }
}
