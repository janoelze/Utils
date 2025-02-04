<?php

namespace JanOelze\Utils;

use PDO;

class SQ
{
  private $pdo;
  private $schemaCache = [];

  // Modified constructor to accept a config array or string
  public function __construct($config)
  {
    if (is_array($config)) {
      $path = $config['db'] ?? null;
      if (!$path) {
        throw new \InvalidArgumentException("Database path required in config with key 'db'.");
      }
    } else {
      $path = $config;
    }
    $dsn = "sqlite:" . $path;
    $this->pdo = new PDO($dsn);
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }

  public function dispense(string $type): SQRecord
  {
    return new SQRecord($this, $type);
  }

  public function find(string $type, array $criteria = []): array
  {
    // return empty array if table does not exist
    $stmt = $this->pdo->prepare(
      "SELECT name FROM sqlite_master WHERE type='table' AND name = ? LIMIT 1"
    );
    $stmt->execute([$type]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
      return [];
    }

    $where = [];
    $values = [];
    foreach ($criteria as $col => $val) {
      $where[]  = "`$col` = ?";
      $values[] = $val;
    }
    $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    $sql      = "SELECT * FROM `$type` $whereSql";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($values);

    $rows  = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $beans = [];
    foreach ($rows as $row) {
      $bean = new SQRecord($this, $type);
      foreach ($row as $k => $v) {
        $bean->$k = $v;
      }
      $bean->setIsNew(false);
      $beans[] = $bean;
    }
    return $beans;
  }

  public function findOne(string $type, array $criteria = []): ?SQRecord
  {
    $result = $this->find($type, $criteria);
    return !empty($result) ? $result[0] : null;
  }

  public function execute(string $sql, array $params = []): array
  {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function query(string $table): SQQueryBuilder
  {
    return new SQQueryBuilder($this, $table);
  }

  public function beginTransaction()
  {
    $this->pdo->beginTransaction();
  }

  public function commit()
  {
    $this->pdo->commit();
  }

  public function rollBack()
  {
    $this->pdo->rollBack();
  }

  public function getPDO(): PDO
  {
    return $this->pdo;
  }

  public function ensureTableExists(string $table, SQRecord $bean)
  {
    if (isset($this->schemaCache[$table])) {
      return;
    }

    $stmt = $this->pdo->prepare(
      "SELECT name FROM sqlite_master WHERE type='table' AND name = ? LIMIT 1"
    );
    $stmt->execute([$table]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
      $createSql = "CREATE TABLE `$table` (`id` INTEGER PRIMARY KEY AUTOINCREMENT)";
      $this->pdo->exec($createSql);
    }

    $columns = $this->loadTableColumns($table);
    $this->schemaCache[$table] = $columns;
  }

  private function loadTableColumns(string $table): array
  {
    $stmt = $this->pdo->prepare("PRAGMA table_info(`$table`)");
    $stmt->execute();
    $info = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $columns = [];
    foreach ($info as $col) {
      $columns[$col['name']] = $col['type'];
    }
    return $columns;
  }

  public function addColumn(string $table, string $column, string $type)
  {
    $this->pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $type");
    $this->schemaCache[$table][$column] = $type;
  }

  public function getTableSchema(string $table): array
  {
    return $this->schemaCache[$table] ?? [];
  }
}

class SQRecord
{
  private $orm;
  private $table;
  private $isNew = true;
  private $data = [];

  public function __construct(SQ $orm, string $table)
  {
    $this->orm   = $orm;
    $this->table = $table;
  }

  public function __set($name, $value)
  {
    $this->data[$name] = $value;
  }

  public function __get($name)
  {
    return $this->data[$name] ?? null;
  }

  public function setIsNew(bool $flag)
  {
    $this->isNew = $flag;
  }

  public function save()
  {
    $currentTimestamp = date('Y-m-d H:i:s');
    if ($this->isNew) {
      $this->uuid = $this->generateUUID();
      $this->created_at = $currentTimestamp;
    }
    $this->updated_at = $currentTimestamp;

    $this->orm->ensureTableExists($this->table, $this);

    $schema = $this->orm->getTableSchema($this->table);

    foreach ($this->data as $col => $val) {
      if ($col === 'id') {
        continue;
      }
      if (!array_key_exists($col, $schema)) {
        $type = $this->inferColumnType($val);
        $this->orm->addColumn($this->table, $col, $type);
      }
    }

    if ($this->isNew) {
      $this->insertRecord();
    } else {
      $this->updateRecord();
    }
  }

  public function delete()
  {
    if (empty($this->data['id'])) {
      return;
    }
    $sql = "DELETE FROM `{$this->table}` WHERE `id` = ?";
    $stmt = $this->orm->getPDO()->prepare($sql);
    $stmt->execute([$this->data['id']]);
  }

  private function insertRecord()
  {
    $columns      = [];
    $placeholders = [];
    $values       = [];

    foreach ($this->data as $col => $val) {
      if ($col === 'id') {
        continue;
      }
      $columns[]      = "`$col`";
      $placeholders[] = "?";
      $values[]       = $val;
    }

    $colList = implode(", ", $columns);
    $phList  = implode(", ", $placeholders);

    $sql  = "INSERT INTO `{$this->table}` ($colList) VALUES ($phList)";
    $stmt = $this->orm->getPDO()->prepare($sql);
    $stmt->execute($values);

    $this->data['id'] = $this->orm->getPDO()->lastInsertId();
    $this->isNew = false;
  }

  private function updateRecord()
  {
    if (empty($this->data['id'])) {
      $this->insertRecord();
      return;
    }

    $setClauses = [];
    $values     = [];
    foreach ($this->data as $col => $val) {
      if ($col === 'id') {
        continue;
      }
      $setClauses[] = "`$col` = ?";
      $values[]     = $val;
    }
    $values[] = $this->data['id'];

    $setSql = implode(", ", $setClauses);
    $sql    = "UPDATE `{$this->table}` SET $setSql WHERE `id` = ?";
    $stmt   = $this->orm->getPDO()->prepare($sql);
    $stmt->execute($values);
  }

  private function inferColumnType($val): string
  {
    if (is_int($val)) {
      return 'INTEGER';
    } elseif (is_float($val)) {
      return 'REAL';
    }
    return 'TEXT';
  }

  private function generateUUID(): string
  {
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff)
    );
  }
}

class SQQueryBuilder
{
  private $orm;
  private $table;
  private $wheres = [];
  private $order = [];
  private $limitCount = null;
  private $offsetCount = null;

  public function __construct(SQ $orm, string $table)
  {
    $this->orm   = $orm;
    $this->table = $table;
  }

  public function where(string $column, string $operator, $value): self
  {
    $this->wheres[] = ["`$column` $operator ?", $value];
    return $this;
  }

  public function orderBy(string $column, string $direction = 'ASC'): self
  {
    $this->order[] = "`$column` " . strtoupper($direction);
    return $this;
  }

  public function limit(int $count, int $offset = 0): self
  {
    $this->limitCount  = $count;
    $this->offsetCount = $offset;
    return $this;
  }

  public function get(): array
  {
    // Check if the table exists
    $stmt = $this->orm->getPDO()->prepare(
      "SELECT name FROM sqlite_master WHERE type='table' AND name = ? LIMIT 1"
    );
    $stmt->execute([$this->table]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exists) {
      return [];
    }

    $whereClauses = [];
    $params       = [];
    foreach ($this->wheres as $w) {
      $whereClauses[] = $w[0];
      $params[]       = $w[1];
    }
    $whereSql = "";
    if (!empty($whereClauses)) {
      $whereSql = "WHERE " . implode(" AND ", $whereClauses);
    }

    $orderSql = "";
    if (!empty($this->order)) {
      $orderSql = "ORDER BY " . implode(", ", $this->order);
    }

    $limitSql = "";
    if ($this->limitCount !== null) {
      $limitSql = "LIMIT " . (int) $this->limitCount;
      if ($this->offsetCount) {
        $limitSql .= " OFFSET " . (int) $this->offsetCount;
      }
    }

    $sql  = "SELECT * FROM `{$this->table}` $whereSql $orderSql $limitSql";
    $stmt = $this->orm->getPDO()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $beans = [];
    foreach ($rows as $row) {
      $bean = new SQRecord($this->orm, $this->table);
      foreach ($row as $k => $v) {
        $bean->$k = $v;
      }
      $bean->setIsNew(false);
      $beans[] = $bean;
    }
    return $beans;
  }
}

if (basename(__FILE__) === basename($_SERVER["SCRIPT_FILENAME"])) {
  $sq = new SQ(['db' => 'demo.sqlite']);

  $user = $sq->dispense('user');
  $user->name  = 'Alice';
  $user->email = 'alice@example.com';
  $user->save();

  $post = $sq->dispense('post');
  $post->title   = 'My First Post';
  $post->content = 'Hello from my first post!';
  $post->user_id = $user->id;
  $post->save();

  $aliceUsers = $sq->find('user', ['name' => 'Alice']);
  foreach ($aliceUsers as $alice) {
    echo "Found user: {$alice->name}, {$alice->email}\n";
  }

  $posts = $sq->query('post')
    ->where('user_id', '=', $user->id)
    ->orderBy('id', 'DESC')
    ->limit(10)
    ->get();
  foreach ($posts as $p) {
    echo "Post: {$p->title} => {$p->content}\n";
  }
}
