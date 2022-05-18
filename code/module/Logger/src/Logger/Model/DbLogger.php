<?php
namespace Logger\Model;

use Psr\Log;
use Logger\Model\LoggerTrait;

class DbLogger implements Log\LoggerInterface
{
    use LoggerTrait;

    private $pdo;
    public function setDb(\PDO $pdo)
    {
        $this->pdo = $pdo;
        return $this;
    }
    public function getDb()
    {
        return $this->pdo;
    }

    public function log($level, $message, array $context = array())
    {
        $pdo = $this->getDb();
        if($pdo) {
            $pdo->beginTransaction();
            $prepared = $pdo->prepare("INSERT INTO logs SET level=:level, message=:message");
            $prepared->execute([$level, $this->interpolate($message, $context)]);
        }
    }
}
