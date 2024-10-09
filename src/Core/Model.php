<?php

namespace Core;

use Core\Env;

class Model
{
    private $db_type;
    private $db_host;
    private $db_name;
    private $db_user;
    private $db_pass;
    private $pdo;
    private $db_port;

    public function __construct()
    {
        $this->db_type = $_ENV['MYSQL_DB_TYPE'];
        $this->db_host = $_ENV['MYSQL_HOST'];
        $this->db_name = $_ENV['MYSQL_DATABASE'];
        $this->db_user = $_ENV['MYSQL_USER'];
        $this->db_pass = $_ENV['MYSQL_PASSWORD'];
        $this->db_port = $_ENV['MYSQL_DB_PORT'];
        $this->connect();
    }

    public function connect()
    {
        try {
            if ($this->db_type == 'mysql') {
                $dsn = "mysql:host={$this->db_host};dbname={$this->db_name}";
                $this->pdo = new \PDO($dsn, $this->db_user, $this->db_pass);
            } elseif ($this->db_type == 'pgsql') {
                $dsn = "pgsql:host={$this->db_host};port={$this->db_port};dbname={$this->db_name}";
                $this->pdo = new \PDO($dsn, $this->db_user, $this->db_pass);
            } elseif ($this->db_type == 'sqlite') {
                $db_file = __DIR__ . '/../' . $this->db_name;
                $dsn = "sqlite:$db_file";
                $this->pdo = new \PDO($dsn);
            } else {
                throw new \Exception("Tipo de banco não suportado: {$this->db_type}");
            }
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            die("Erro ao conectar ao banco: " . $e->getMessage());
        }
    }

    public function insert(string $table, array $data)
    {
        try {
            if (empty($data)) {
                throw new \Exception("Os dados estão vazios.");
            }
            $columns = implode(", ", array_keys($data));
            $placeholders = ":" . implode(", :", array_keys($data));
            $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            if ($stmt->rowCount() > 0) {
                $ultimoId = $this->pdo->lastInsertId();
                $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE id = :id");
                $stmt->bindParam(':id', $ultimoId, \PDO::PARAM_INT);
                $stmt->execute();
            } else {
                return [];
            }
        } catch (\Exception $e) {
            throw new \Exception("Erro ao inserir dados: " . $e->getMessage());
        }
        return [];
    }

    public function paginate(string $table,  $page = 1, $itemsPerPage = 100)
    {
        $offset = ($page - 1) * $itemsPerPage;
        $sql = "SELECT * FROM {$table} ORDER BY id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':limit', $itemsPerPage, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function select($table, $where = '', $params = array())
    {
        try {
            $sql = "SELECT * FROM $table";
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new \Exception("Erro ao executar consulta: " . $e->getMessage());
        }
    }

    public function find($table, $where = '', $params = array())
    {
        try {
            $sql = "SELECT * FROM $table";
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC)[0];
        } catch (\Exception $e) {
            throw new \Exception("Erro ao executar consulta: " . $e->getMessage());
        }
    }    
    
    public function findLast($table, $where = '', $params = array())
    {
        try {
            $sql = "SELECT * FROM $table";
            if (!empty($where)) {
                $sql .= " WHERE $where ";
            }
            $sql .= " ORDER BY id DESC LIMIT 1 ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC)[0];
        } catch (\Exception $e) {
            throw new \Exception("Erro ao executar consulta: " . $e->getMessage());
        }
    }

    public function update($table, $data, $where = '', $params = array())
    {
        try {
            if (empty($data)) {
                throw new \Exception("Os dados estão vazios.");
            }
            $set = '';
            foreach ($data as $key => $value) {
                $set .= "$key = :$key, ";
            }
            $set = rtrim($set, ', ');
            $sql = "UPDATE $table SET $set";
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            $params = array_merge($data, $params);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            if ($stmt->rowCount() > 0) {
                $ultimoId = $this->pdo->lastInsertId();
                $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE id = :id");
                $stmt->bindParam(':id', $ultimoId, \PDO::PARAM_INT);
                $stmt->execute();
            } else {
                return [];
            }
        } catch (\Exception $e) {
            throw new \Exception("Erro ao atualizar dados: " . $e->getMessage());
        }
        return [];
    }

    public function query($sql, $params = array())
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function disconnect() {}

    public function  delete($table, $where = '', $params = array())
    {
        try {
            $sql = "DELETE FROM $table";
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new \Exception("Erro ao executar consulta: " . $e->getMessage());
        }
    }
}
