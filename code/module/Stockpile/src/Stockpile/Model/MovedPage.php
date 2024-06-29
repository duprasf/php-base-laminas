<?php

namespace Stockpile\Model;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerAwareInterface;

class MovedPage extends \ArrayObject implements EventManagerAwareInterface
{
    protected $events;
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers([
            __CLASS__,
            get_called_class(),
        ]);
        $this->events = $events;
        return $this;
    }
    public function getEventManager()
    {
        if (null === $this->events) {
            $this->setEventManager(new EventManager());
        }
        return $this->events;
    }

    private $pdo;
    public function setPdo(\PDO $pdo)
    {
        $this->pdo = $pdo;
        return $this;
    }
    protected function getPdo()
    {
        return $this->pdo;
    }

    public function getMovedPages()
    {
        $db = $this->getPdo();
        if($db && !count($this)) {
            $stmt = $db->query("SELECT movedPageId, movedPageId, originalLocation, newLocation FROM movedPage");
            if($stmt) {
                $this->exchangeArray(array_map('reset', $stmt->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_ASSOC)));
            }
        }
        return $this;
    }

    public function remove($movedPageId)
    {
        $db = $this->getPdo();
        if($db) {
            $prepared = $db->prepare("DELETE FROM movedPage WHERE movedPageId = ?");
            $prepared->execute([$movedPageId]);

            return $db->exec($query);
        }

        return null;
    }

    public function add($originalLocation, $newLocation)
    {
        $db = $this->getPdo();

        if($db) {
            $prepared = $db->prepare("INSERT INTO movedPage (originalLocation, newLocation) VALUES (?,?)");
            $prepared->execute([$originalLocation, $db->quote($newLocation)]);
            $count = $prepared->rowCount();
            if($count) {
                $return = array(
                    "originalLocation" => $originalLocation,
                    "newLocation" => $newLocation,
                    "movedPageId" => $db->lastInsertId()
                );
            } else {
                $return = array('error' => 'Could not insert this record. '.var_export($db->errorInfo(), true));
            }
            return $return;
        }

        return array('error' => 'No DB specified');
    }

    public function match($path)
    {
        $db = $this->getPdo();

        $data = null;
        if($db) {
            $query = "SELECT originalLocation, newLocation
                FROM movedPage
                WHERE originalLocation LIKE ?
            ";
            $prepared = $db->prepare($query);
            $prepared->execute([$path]);
            $data = $prepared->fetch(\PDO::FETCH_ASSOC);

            if($data == false) {
                $query = "SELECT originalLocation, newLocation
                    FROM movedPage
                    WHERE ? REGEXP originalLocation
                ";
                $prepared = $db->prepare($query);
                $prepared->execute(['/'.$path]);
                $data = $stmt->fetch(\PDO::FETCH_ASSOC);
                if($data) {
                    $data['originalLocation'] = $path;
                }
            }
        }
        return $data;
    }

    public function setup()
    {
        $db = $this->getPdo();
        if($db) {
            $sql = "CREATE TABLE IF NOT EXISTS `movedPage` (
              `movedPageId` integer PRIMARY KEY AUTOINCREMENT,
              `originalLocation` varchar(200),
              `newLocation` varchar(200)
            );
            ";
            $db->exec($sql);
        } else {
            throw new \Exception('No DB specified');
        }
        return $this;
    }

    /**
    * Load all redirect from a apache conf file
    *
    * @param string $file the location of the conf file
    * @param \PDO $db the PDO where to store the redirects
    */
    public function loadRedirectFromConf($file)
    {
        $db = $this->getPdo();
        if(!$db) {
            return null;
        }

        if(!file_exists($file)) {
            throw new \Exception('File not found');
        }
        $content = file($file);
        $prepared = $db->prepare("INSERT INTO movedPage VALUES (null, :original, :new);");
        $count = 0;
        foreach($content as $line) {
            if(preg_match('(^Redirect(Match)? 30\d ([^\s]*)\s+([^\s]*))i', $line, $out)) {
                $count++;
                $prepared->execute(array(":original" => substr($out[2], $out[1] ? 0 : 1), ":new" => $out[3]));
            }
        }
        return $count;
    }
}
