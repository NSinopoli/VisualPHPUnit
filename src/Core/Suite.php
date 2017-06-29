<?php
/**
 * VisualPHPUnit
 *
 * VisualPHPUnit is a visual front-end for PHPUnit.
 *
 * PHP Version 5.6<
 *
 * @author    Johannes Skov Frandsen <localgod@heaven.dk>
 * @copyright 2011-2016 VisualPHPUnit
 * @license   http://opensource.org/licenses/BSD-3-Clause The BSD License
 * @link      https://github.com/VisualPHPUnit/VisualPHPUnit VisualPHPUnit
 */
namespace Visualphpunit\Core;

use \Doctrine\DBAL\Connection;
use \DateTime;

/**
 * Visualphpunit test suite result
 *
 * @author Johannes Skov Frandsen <localgod@heaven.dk>
 */
class Suite
{

    /**
     * Create the table if it dos not exists
     *
     * @param \Doctrine\DBAL\Connection $connection
     *
     * @return boolean
     */
    public static function createTable(Connection $connection)
    {
        $sql = "CREATE TABLE IF NOT EXISTS suites(id INTEGER PRIMARY KEY AUTOINCREMENT, suite TEXT, executed NUMERIC);";
        $stmt = $connection->prepare($sql);
        return $stmt->execute();
    }

    /**
     * Drop the table if it exists
     *
     * @param \Doctrine\DBAL\Connection $connection
     *
     * @return boolean
     */
    public static function dropTable(Connection $connection)
    {
        $sql = "DROP suites;";
        $stmt = $connection->prepare($sql);
        return $stmt->execute();
    }

    /**
     * Truncate the table
     *
     * @param \Doctrine\DBAL\Connection $connection
     *
     * @return boolean
     */
    public static function truncateTable(Connection $connection)
    {
        $sql = "DELETE FROM suites;";
        $stmt = $connection->prepare($sql);
        return $stmt->execute();
    }

    /**
     * Store a test suite result
     *
     * @param \Doctrine\DBAL\Connection $connection
     * @param mixed[] $result
     *
     * @return boolean
     */
    public static function store(Connection $connection, $result)
    {
        $sql = "INSERT INTO suites (suite, executed) VALUES (?, ?);";
        $date = new DateTime();
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(1, json_encode($result));
        $stmt->bindValue(2, $date->format('Y-m-d H:i:s'));
        return $stmt->execute();
    }

    /**
     * Get all test suite results
     *
     * @param \Doctrine\DBAL\Connection $connection
     *
     * @return mixed[]
     */
    public static function getSnapshots(Connection $connection)
    {
        $sql = 'SELECT id, executed FROM suites ORDER BY datetime(executed) DESC;';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get test suite results
     *
     * @param \Doctrine\DBAL\Connection $connection
     * @param integer $id
     *
     * @return mixed[]
     */
    public static function getSuite(Connection $connection, $id)
    {
        $sql = 'SELECT suite FROM suites WHERE id=?;';
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(1, $id);
        $stmt->execute();
        return json_decode($stmt->fetch()['suite']);
    }
}
