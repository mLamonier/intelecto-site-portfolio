<?php
class ConfigController {
  private $db;
  private array $autoDefaults = [
    'DESCONTO_PARCELADO_PADRAO' => '5',
    'DESCONTO_AVISTA_PADRAO' => '10',
  ];

  public function __construct($db) {
    $this->db = $db;
  }

  public function index() {
    $this->ensureAutomaticDefaults();

    $sql = "SELECT chave, valor FROM configuracao";
    $stmt = $this->db->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  public function update() {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !isset($data['chave']) || !array_key_exists('valor', $data)) {
      http_response_code(400);
      echo json_encode(["erro" => "chave e valor s\u00e3o obrigat\u00f3rios"]);
      return;
    }

    $chave = (string)$data['chave'];
    $valor = $this->normalizeValue($chave, $data['valor']);

    $sqlCheck = "SELECT COUNT(*) FROM configuracao WHERE chave = :chave";
    $stmtCheck = $this->db->prepare($sqlCheck);
    $stmtCheck->bindParam(':chave', $chave);
    $stmtCheck->execute();
    $exists = $stmtCheck->fetchColumn() > 0;

    if ($exists) {
      $sql = "UPDATE configuracao SET valor = :valor WHERE chave = :chave";
      $stmt = $this->db->prepare($sql);
      $stmt->bindParam(':chave', $chave);
      $stmt->bindParam(':valor', $valor);

      if ($stmt->execute()) {
        echo json_encode(["mensagem" => "Configura\u00e7\u00e3o atualizada com sucesso"]);
      } else {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao atualizar configura\u00e7\u00e3o"]);
      }
    } else {
      $sql = "INSERT INTO configuracao (chave, valor) VALUES (:chave, :valor)";
      $stmt = $this->db->prepare($sql);
      $stmt->bindParam(':chave', $chave);
      $stmt->bindParam(':valor', $valor);

      if ($stmt->execute()) {
        echo json_encode(["mensagem" => "Configura\u00e7\u00e3o criada com sucesso"]);
      } else {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao criar configura\u00e7\u00e3o"]);
      }
    }
  }

  public function valorHora() {
    $sql = "SELECT chave, valor
            FROM configuracao
            WHERE chave IN (
              'VALOR_HORA_PERSONALIZADA_PRESENCIAL',
              'VALOR_HORA_PERSONALIZADA_EAD'
            )";
    $stmt = $this->db->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  private function normalizeValue(string $key, $value): string
  {
    $value = trim((string)($value ?? ''));
    if ($value === '' && isset($this->autoDefaults[$key])) {
      return $this->autoDefaults[$key];
    }
    return $value;
  }

  private function ensureAutomaticDefaults(): void
  {
    foreach ($this->autoDefaults as $key => $defaultValue) {
      $stmt = $this->db->prepare("SELECT valor FROM configuracao WHERE chave = :chave LIMIT 1");
      $stmt->bindValue(':chave', $key, PDO::PARAM_STR);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$row) {
        $insert = $this->db->prepare("INSERT INTO configuracao (chave, valor) VALUES (:chave, :valor)");
        $insert->bindValue(':chave', $key, PDO::PARAM_STR);
        $insert->bindValue(':valor', $defaultValue, PDO::PARAM_STR);
        $insert->execute();
        continue;
      }

      $current = trim((string)($row['valor'] ?? ''));
      if ($current === '') {
        $update = $this->db->prepare("UPDATE configuracao SET valor = :valor WHERE chave = :chave");
        $update->bindValue(':chave', $key, PDO::PARAM_STR);
        $update->bindValue(':valor', $defaultValue, PDO::PARAM_STR);
        $update->execute();
      }
    }
  }
}
?>
