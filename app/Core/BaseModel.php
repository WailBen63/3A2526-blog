<?php
namespace App\Core;

use PDO;

abstract class BaseModel {
    protected PDO $db;
    protected Logger $logger;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
    }
}
