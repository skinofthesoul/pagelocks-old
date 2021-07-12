<?php

namespace Grav\Plugin\PageLocks;

use Exception;
use Grav\Common\Grav;
use Grav\Common\Yaml;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Storage
{
    const LOCKFILE = 'user-data://pagelocks/locks.yaml';

    protected File $file;

    public function __construct()
    {
        $pathToLocks = $this->getPathToLocks();
        $this->file = File::instance($pathToLocks);       
    }

    /**
     * Read all locks for read access.
     * @return Locks containing all locks
     */
    public function readLocks(): Locks
    {
        $locks = new Locks(Yaml::parse($this->file->content()));
        $this->file->free();

        return $locks;
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

    /**
     * Get the path to the lock file (/path/to/user/data/pagelocks/locks.yaml)
     * 
     * @return string Path to locks.yaml file
     * @throws Exception When path to lock file cannot be found.
     */
    public function getPathToLocks(): string {
        /** @var UniformResourceLocator */
        $locator = Grav::instance()['locator'];
        $path = $locator->findResource(self::LOCKFILE, true, true);

        if ($path === false) {
            throw new Exception('Path for lock file cannot be found');
        }

        return $path;
    }
}
