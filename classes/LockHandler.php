<?php

namespace Grav\Plugin\PageLocks;

use DateTime;
use Exception;
use Grav\Common\Data\Data;
use Grav\Common\Grav;
use Grav\Common\User\DataUser\User;

class LockHandler
{
    protected Grav $grav;
    protected Data $config;
    protected Storage $storage;
    protected bool $debug;

    public function __construct()
    {
        $this->grav = Grav::instance();
        $this->config = $this->grav['config'];
        $this->storage = new Storage();
        $this->debug = $this->config->get('plugins.pagelocks.debug', true);
    }

    /**
     * Handle async requests send by javascript injected by PageLocks into Admin panel.
     * 
     * @return ?array The response specific to each type of request, or null if no PageLocks request.
     */
    public function handleRequest(): ?array
    {
        if ($this->isNotSendByPageLocks()) {
            return null;
        }

        if ($this->isReadLocks()) {
            $locks = $this->readLocks();
            return $locks;
        }

        /** @var User */
        $user = $this->grav['user'];

        $locks = $this->storage->readLocksForUpdate();

        if ($this->isAcquireLock()) {
            $response = $this->acquireLock($locks, $user);
        } elseif ($this->isKeepAlive()) {
            $response = $this->keepAlive($locks);
        } elseif ($this->isForceRemoveLock()) {
            $response = $this->forceRemoveLock($locks, $user);
        } else {
            throw new Exception('Should not reach this.');
        }

        $this->clearExpiredLocks($locks);
        $this->storage->saveLocks($locks);

        return $response;
    }

    /**
     * Check if request originated from front-end javascript from PageLocks.
     * 
     * @return bool True if request is not from PageLocks
     */
    protected function isNotSendByPageLocks(): bool
    {
        return !(
            $this->isKeepAlive() ||
            $this->isAcquireLock() ||
            $this->isReadLocks() ||
            $this->isForceRemoveLock()
        );
    }

    /**
     * Check if request is a 'readLocks' request.
     * 
     * @return bool True if request is a 'readLocks' request, else false.
     */
    protected function isReadLocks(): bool
    {
        return isset($_POST['readLocks']);
    }

    /**
     * Check if request is a 'acquireLock' request.
     * 
     * @return bool True if request is a 'acquireLock' request, else false.
     */
    protected function isAcquireLock(): bool
    {
        return isset($_POST['acquireLock']);
    }

    /**
     * Check if request is a 'keepAlive' request.
     * 
     * @return bool True if request is a 'keepAlive' request, else false.
     */
    protected function isKeepAlive(): bool
    {
        return isset($_POST['keepAlive']);
    }

    /**
     * Check if request is a 'forceRemoveLocks' request.
     * 
     * @return bool True if request is a 'forceRemoveLocks' request, else false.
     */
    protected function isForceRemoveLock(): bool
    {
        return isset($_POST['forceRemoveLock']);
    }

    /**
     * Get list of locks for PageLocks admin page.
     * 
     * @return array Response for 'readLocks' request.
     */
    protected function readLocks(): array
    {
        if ($this->debug) $this->log("/admin/locks : Read all locks\n");

        $locks = $this->storage->readLocks();
        
        return [
            'locks' => $locks->toArray(),
            'alert' => $this->translate('PLUGIN_PAGELOCKS.ALERT.CONFIRM_DELETE'),
            'countAlert' => $this->translate(['PLUGIN_PAGELOCKS.ALERT.LOCK_COUNT', count($locks)]),
        ];
    }

    /**
     * Set lock if page is not already locked by other user.
     * 
     * @return array Response for 'acquireLock' request.
     */
    protected function acquireLock(Locks $locks, User $user): array
    {
        $route = $this->filteredPOST('acquireLock');

        // Remove existing lock for user
        $this->removeLockOfUser($locks, $user);

        $keepAliveInterval = $this->config->get('plugins.pagelocks.keepAliveInterval', 60) * 1000;

        if (!$this->userIsOnPage($route)) {
            return [
                'isOnPage' => false,
                'isLockGranted' => false,
                'byUser' => $user->fullname,
                'keepAliveInterval' => $keepAliveInterval,
                'alert' => $this->translate('PLUGIN_PAGELOCKS.ALERT.LOCK_REMOVED')
            ];
        }

        // Acquire new lock for new page

        $userHoldingLock = $this->isPageLocked($locks, $route, $user);

        if ($userHoldingLock) {
            // Page has already been locked by other user
            if ($this->debug) $this->log("$route : Lock request by $user->email, already locked by $userHoldingLock\n");

            return [
                'isOnPage' => true,
                'isLockGranted' => false,
                'byUser' => $userHoldingLock,
                'keepAliveInterval' => $keepAliveInterval,
                'alert' => $this->translate([
                    'PLUGIN_PAGELOCKS.ALERT.ALREADY_LOCKED',
                    $userHoldingLock, $userHoldingLock
                ])
            ];
        } else {
            // Lock has been granted
            if ($this->debug) $this->log("$route : Lock acquired: By $user->email\n");

            $this->setLock($locks, $route, $user);
            return [
                'isOnPage' => true,
                'isLockGranted' => true,
                'byUser' => $user->fullname,
                'keepAliveInterval' => $keepAliveInterval,
                'alert' => $this->translate('PLUGIN_PAGELOCKS.ALERT.LOCK_GRANTED'),
            ];
        }
    }

    /**
     * Check if user is on a Page in Admin.
     * 
     * @return bool True if user is on /admin/pages/<path-to-page>
     */
    public function userIsOnPage(string $route): bool
    {
        return preg_match('/\/pages(\/[a-z\-]+)+$/', $route) === 1;
    }

    /**
     * Update timestamp of lock to extend the lifetime of the lock.
     * 
     * @return array Response for 'keepAlive' request.
     */
    protected function keepAlive(Locks $locks): array
    {
        $route = $this->filteredPOST('keepAlive');

        if (isset($locks[$route])) {
            if ($this->debug) $this->log("$route : Lock extended: Held by {$locks[$route]->email}\n");

            $now = (new DateTime())->getTimestamp();
            $locks[$route]->timestamp = $now;

            return [
                'isExtended' => true,
                'alert' => $this->translate('PLUGIN_PAGELOCKS.ALERT.LOCK_EXTENDED'),
            ];
        } else {
            return [
                'isExtended' => false,
                'alert' => $this->translate('PLUGIN_PAGELOCKS.ALERT.LOCK_EXPIRED'),
            ];
        }
    }

    /**
     * Remove lock held on page.
     * 
     * @return array Response for 'forceRemoveLocks' request.
     */
    protected function forceRemoveLock(Locks $locks, User $user): array
    {
        $route = $this->filteredPOST('forceRemoveLock');

        if (isset($locks[$route])) {
            if ($this->debug) $this->log("$route : Lock removed by force by $user->email\n");

            unset($locks[$route]);

            return [
                'isLockRemoved' => true,
                'alert' => $this->translate('PLUGIN_PAGELOCKS.ALERT.LOCK_REMOVED')
            ];
        } else {
            return [
                'isLockRemoved' => false,
                'alert' => $this->translate('PLUGIN_PAGELOCKS.ALERT.LOCK_NOT_REMOVED')
            ];
        }
    }

    /**
     * Remove lock held by user.
     */
    protected function removeLockOfUser(Locks $locks, User $user): void
    {
        foreach ($locks as $route => $lock) {
            if ($lock->email === $user->email) {
                if ($this->debug) $this->log("$route : Lock removed: Was held by {$locks[$route]->email}\n");

                unset($locks[$route]);
            }
        }
    }

    /**
     * Set lock on page for user.
     */
    protected function setLock(Locks $locks, string $route, User $user): void
    {
        $now = (new DateTime())->getTimestamp();

        $locks[$route] = new Lock([
            'email' => $user->email,
            'fullname' => $user->fullname,
            'timestamp' => $now,
        ]);
    }

    /**
     * Check if page is locked by other user
     * 
     * @return string Email address of user locking the page, or '' if no lock.
     */
    protected function isPageLocked(Locks $locks, string $route, User $user): string
    {
        if (isset($locks[$route])) {
            $lock = $locks[$route];

            if ($lock->email != $user->email) {
                return $lock->email;
            }
        }

        return '';
    }

    /**
     * Loops through all locks and removes expired locks
     */
    private function clearExpiredLocks(Locks $locks): void
    {
        $expiresAfter = $this->config->get('plugins.pagelocks.expiresAfter', 3600);
        $now = (new DateTime())->getTimestamp();

        foreach ($locks as $route => $lock) {
            $lastKeepalive = $lock->timestamp;
            $interval = $now - $lastKeepalive;

            if ($interval > $expiresAfter) {
                if ($this->debug) $this->log("$route : Clear expired lock\n");

                unset($locks[$route]);
            }
        }
    }

    /**
     * Sanitize data received via $_POST.
     * 
     * @param string $key The value to be sanitized.
     * @return string Sanitized data.
     */
    protected function filteredPOST(string $key): string
    {
        switch ($key) {
            case 'keepAlive':
            case 'acquireLock':
            case 'forceRemoveLock':
                return filter_var($_POST[$key], FILTER_SANITIZE_URL);
            default:
                throw new Exception("Case '$key' is not being handled.");
        }

        return '';
    }

    /**
     * Log data in log file for debugging purposes.
     */
    protected function log(string $message): void
    {
        file_put_contents(
            USER_PATH . '/data/pagelocks/debug.log',
            $message,
            FILE_APPEND
        );
    }

    /**
     * Translate alerts
     * 
     * @param string|array $args Can be a string, or an array containing a string and its parameters.
     */
    protected function translate($args): string
    {
        return $this->grav['language']->translate($args);
    }
}
