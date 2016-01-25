<?php
namespace Kijtra\Scraper;

class Logger
{
    private $db;
    private $table = 'urls';
    private $max = 1000;
    private static $buffer = array();

    public function __construct($file)
    {
        $dir = dirname($file);
        if (!($dir = realpath($dir)) || !is_writable($dir)) {
            throw new Exception('Directory "'.$dir.'" is not writable.');
        }

        $filepath = $dir.DIRECTORY_SEPARATOR.basename($file);
        try {
            $this->db = new \PDO('sqlite:'.$filepath, '', '', array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ));
        } catch(\PDOException $e) {
            throw $e;
        }

        if (!$this->isTable()) {
            $this->createTable();
        }

        $total = $this->getTotal();
        if ($total < $this->max) {
            self::$buffer = $this->getAll();
        }
    }

    private function isTable()
    {
        $sql = "
        SELECT `name`
        FROM `sqlite_master`
        WHERE `type`='table'
        AND `name`='{$this->table}'
        LIMIT 1;
        ";

        try {
            $query = $this->db->query($sql);
            if ($query) {
                $res = $query->fetchColumn();
                if (!empty($res)) {
                    return true;
                }
            }
        } catch(\PDOException $e) {}

        return false;
    }

    private function createTable()
    {
        try {
            $sql = "
            CREATE TABLE `{$this->table}`
            (
                url text NOT NULL,
                created text NULL,
                length integer NULL,
                date text NULL,
                PRIMARY KEY (url)
            );
            ";
            $this->db->query($sql);
        } catch(\PDOException $e) {
            throw $e;
        }
    }

    private function getTotal()
    {
        try {
            $sql = "
            SELECT COUNT(*)
            FROM `{$this->table}`;
            ";
            $total = $this->db->query($sql)->fetchColumn();
            return (!empty($total) ? (int)$total : 0);
        } catch(\PDOException $e) {
            throw $e;
        }
    }

    private function getAll()
    {
        try {
            $sql = "
            SELECT
            *
            FROM `{$this->table}`;
            ";

            $items = array();
            $query = $this->db->query($sql);
            while($val = $query->fetch(\PDO::FETCH_ASSOC)) {
                $items[$val['url']] = array(
                    'length' => (int)$val['length'],
                    'date' => $val['date']
                );
            }
            return (!empty($items) ? $items : null);
        } catch(\PDOException $e) {
            throw $e;
        }
    }

    public function isNotModified($urlKey, $length, $date)
    {
        if (is_array(self::$buffer)) {
            if (
                !empty(self::$buffer[$urlKey]) &&
                self::$buffer[$urlKey]['length'] == $length &&
                self::$buffer[$urlKey]['date'] == $date
            ) {
                return true;
            }
        } else {
            try {
                $sql = "
                SELECT 1
                FROM `{$this->table}`
                WHERE `url`=?
                AND `length`=?
                AND `date`=?
                LIMIT 1;
                ";

                $stmt = $this->db->prepare($sql);
                if ($stmt->execute(array($urlKey, $length, $date))) {
                    if (1 == $stmt->fetchColumn()) {
                        return true;
                    }
                }
            } catch(\PDOException $e) {}
        }

        $this->write($urlKey, $length, $date);
    }

    public function write($urlKey, $length, $date)
    {
        try {
            $sql = "
            REPLACE INTO `{$this->table}`
            VALUES (?, DATETIME(), ?, ?);
            ";
            $stmt = $this->db->prepare($sql);
            if ($stmt->execute(array($urlKey, $length, $date))) {
                self::$buffer[$urlKey] = array(
                    'length' => $length,
                    'date' => $date
                );
            }
        } catch(\PDOException $e) {
            throw $e;
        }
    }
}
