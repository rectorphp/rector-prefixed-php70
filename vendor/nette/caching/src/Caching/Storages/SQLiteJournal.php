<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace RectorPrefix20210503\Nette\Caching\Storages;

use RectorPrefix20210503\Nette;
use RectorPrefix20210503\Nette\Caching\Cache;
/**
 * SQLite based journal.
 */
class SQLiteJournal implements \RectorPrefix20210503\Nette\Caching\Storages\Journal
{
    use Nette\SmartObject;
    /** @string */
    private $path;
    /** @var \PDO */
    private $pdo;
    public function __construct(string $path)
    {
        if (!\extension_loaded('pdo_sqlite')) {
            throw new \RectorPrefix20210503\Nette\NotSupportedException('SQLiteJournal requires PHP extension pdo_sqlite which is not loaded.');
        }
        $this->path = $path;
    }
    /**
     * @return void
     */
    private function open()
    {
        if ($this->path !== ':memory:' && !\is_file($this->path)) {
            \touch($this->path);
            // ensures ordinary file permissions
        }
        $this->pdo = new \PDO('sqlite:' . $this->path);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('
			PRAGMA foreign_keys = OFF;
			PRAGMA journal_mode = WAL;
			CREATE TABLE IF NOT EXISTS tags (
				key BLOB NOT NULL,
				tag BLOB NOT NULL
			);
			CREATE TABLE IF NOT EXISTS priorities (
				key BLOB NOT NULL,
				priority INT NOT NULL
			);
			CREATE INDEX IF NOT EXISTS idx_tags_tag ON tags(tag);
			CREATE UNIQUE INDEX IF NOT EXISTS idx_tags_key_tag ON tags(key, tag);
			CREATE UNIQUE INDEX IF NOT EXISTS idx_priorities_key ON priorities(key);
			CREATE INDEX IF NOT EXISTS idx_priorities_priority ON priorities(priority);
		');
    }
    /**
     * @return void
     */
    public function write(string $key, array $dependencies)
    {
        if (!$this->pdo) {
            $this->open();
        }
        $this->pdo->exec('BEGIN');
        if (!empty($dependencies[\RectorPrefix20210503\Nette\Caching\Cache::TAGS])) {
            $this->pdo->prepare('DELETE FROM tags WHERE key = ?')->execute([$key]);
            foreach ($dependencies[\RectorPrefix20210503\Nette\Caching\Cache::TAGS] as $tag) {
                $arr[] = $key;
                $arr[] = $tag;
            }
            $this->pdo->prepare('INSERT INTO tags (key, tag) SELECT ?, ?' . \str_repeat('UNION SELECT ?, ?', \count($arr) / 2 - 1))->execute($arr);
        }
        if (!empty($dependencies[\RectorPrefix20210503\Nette\Caching\Cache::PRIORITY])) {
            $this->pdo->prepare('REPLACE INTO priorities (key, priority) VALUES (?, ?)')->execute([$key, (int) $dependencies[\RectorPrefix20210503\Nette\Caching\Cache::PRIORITY]]);
        }
        $this->pdo->exec('COMMIT');
    }
    /**
     * @return mixed[]|null
     */
    public function clean(array $conditions)
    {
        if (!$this->pdo) {
            $this->open();
        }
        if (!empty($conditions[\RectorPrefix20210503\Nette\Caching\Cache::ALL])) {
            $this->pdo->exec('
				BEGIN;
				DELETE FROM tags;
				DELETE FROM priorities;
				COMMIT;
			');
            return null;
        }
        $unions = $args = [];
        if (!empty($conditions[\RectorPrefix20210503\Nette\Caching\Cache::TAGS])) {
            $tags = (array) $conditions[\RectorPrefix20210503\Nette\Caching\Cache::TAGS];
            $unions[] = 'SELECT DISTINCT key FROM tags WHERE tag IN (?' . \str_repeat(', ?', \count($tags) - 1) . ')';
            $args = $tags;
        }
        if (!empty($conditions[\RectorPrefix20210503\Nette\Caching\Cache::PRIORITY])) {
            $unions[] = 'SELECT DISTINCT key FROM priorities WHERE priority <= ?';
            $args[] = (int) $conditions[\RectorPrefix20210503\Nette\Caching\Cache::PRIORITY];
        }
        if (empty($unions)) {
            return [];
        }
        $unionSql = \implode(' UNION ', $unions);
        $this->pdo->exec('BEGIN IMMEDIATE');
        $stmt = $this->pdo->prepare($unionSql);
        $stmt->execute($args);
        $keys = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        if (empty($keys)) {
            $this->pdo->exec('COMMIT');
            return [];
        }
        $this->pdo->prepare("DELETE FROM tags WHERE key IN ({$unionSql})")->execute($args);
        $this->pdo->prepare("DELETE FROM priorities WHERE key IN ({$unionSql})")->execute($args);
        $this->pdo->exec('COMMIT');
        return $keys;
    }
}