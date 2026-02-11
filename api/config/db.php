<?php
require_once __DIR__ . '/../../includes/site.php';

class Database {
  private $host = "localhost";
  private $port = 3307;
  private $db_name = "intelecto_db";
  private $username = "root";
  private $password = "";
  public $conn;

  private function loadEnvValue(string $key): ?string {
    $content = function_exists('site_env_content') ? site_env_content() : null;
    if (is_string($content)) {
      $pattern = '/^' . preg_quote($key, '/') . '[ \t]*=[ \t]*([^\n]*)$/m';
      if (preg_match($pattern, $content, $match)) {
        return trim($match[1], " \t\n\r\0\x0B\"'");
      }
    }

    $value = getenv($key);
    if ($value !== false && $value !== '') {
      return $value;
    }

    return null;
  }

  private function applyEnvConfig(): void {
    $host = $this->loadEnvValue('DB_HOST');
    if ($host !== null) {
      $this->host = $host;
    }

    $port = $this->loadEnvValue('DB_PORT');
    if ($port !== null && is_numeric($port)) {
      $this->port = (int)$port;
    }

    $dbName = $this->loadEnvValue('DB_NAME');
    if ($dbName !== null) {
      $this->db_name = $dbName;
    }

    $username = $this->loadEnvValue('DB_USER');
    if ($username !== null) {
      $this->username = $username;
    }

    $password = $this->loadEnvValue('DB_PASS');
    if ($password !== null) {
      $this->password = $password === '' ? null : $password;
    }
  }

  public function getConnection() {
    $this->applyEnvConfig();
    $this->conn = null;
    try {
      $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
      $this->conn = new PDO($dsn, $this->username, $this->password);
      $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(["error" => $e->getMessage()]);
      exit;
    }
    return $this->conn;
  }
}
