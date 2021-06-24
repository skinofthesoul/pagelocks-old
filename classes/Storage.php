<?php

namespace Grav\Plugin\PageLocks;

use Grav\Common\Yaml;
use RocketTheme\Toolbox\File\File;

class Storage
{
    const LOCKFILE = USER_PATH . '/data/pagelocks/locks.yaml';

    protected File $file;

    public function __construct()
    {
        $this->file = File::instance(self::LOCKFILE);
    }

    /**
     * Read all locks for read access.
     * @return Locks containing all locks
     */
    public function readLocks(): Locks
    {
        return new Locks(Yaml::parse($this->file->content()));
    }

    /**
     * Read all locks for write access.
     * @return Locks containing all locks
     */
    public function readLocksForUpdate(): Locks
    {
        $this->file->lock();

        return $this->readLocks();
    }

    /**
     * Write locks to file.
     */
    public function saveLocks(Locks $locks): void
    {
        $this->file->save(Yaml::dump($locks->toArray(), JSON_PRETTY_PRINT));
        $this->fileReleaseLock();
    }

    /**
     * Unlock file.
     */
    public function fileReleaseLock(): void
    {
        $this->file->unlock();
    }
}
