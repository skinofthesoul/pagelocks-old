<?php

use Grav\Common\Grav;
use Grav\Common\User\DataUser\User;
use Grav\Plugin\PageLocks\LockHandler;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class LockHandlerTest extends \Codeception\Test\Unit
{
    const LOCKFILE = 'user-data://pagelocks/locks.yaml';

    protected YamlFile $lockFile;

    public function _before()
    {
        /** @var Grav */
        $grav = Grav::instance();

        /** @var User */
        $user = new User();
        $user->email = 'user1@domain.com';
        $user->fullname = 'User 1';
        $user->save();

        $grav['user'] = $user;

        /** @var UniformResourceLocator */
        $locator = $grav['locator'];
        $lockPath = $locator->findResource(self::LOCKFILE, true, true);

        if ($lockPath === false) {
            $this->fail('Path to Lockfile could not be found.');
        }

        $this->lockFile = YamlFile::instance($lockPath);
    }

    protected function _after(): void
    {
    }

    public function testReadEmptyLocks(): void
    {
        $this->set_POST([
            'readLocks' => '',
        ]);
        $this->set_LOCKFILE([]);

        $lockHandler = new LockHandler();
        $response = $lockHandler->handleRequest();

        if ($response === null) {
            $this->fail('Variable $response is null');
        }

        $this->assertEquals(0, count($response['locks']));
        $this->assertEquals('Have you confirmed the page is no longer being edited by %s?', $response['alert']);
        $this->assertEquals('Found 0 lock(s)', $response['countAlert']);
    }

    public function testReadLocks(): void
    {
        $this->set_POST([
            'readLocks' => '',
        ]);

        $this->set_LOCKFILE([
            '/admin/pages/page1' => [
                'email' => 'user1@domain.com',
                'fullname' => 'User 1',
                'timestamp' => 1234556789,
            ]
        ]);

        $lockHandler = new LockHandler();
        $response = $lockHandler->handleRequest();

        if ($response === null) {
            $this->fail('Variable $response is null');
        }

        $this->assertEquals(1, count($response['locks']));
        $this->assertEquals(true, isset($response['locks']['/admin/pages/page1']));
        $this->assertEquals('user1@domain.com', $response['locks']['/admin/pages/page1']['email']);
        $this->assertEquals('Have you confirmed the page is no longer being edited by %s?', $response['alert']);
        $this->assertEquals('Found 1 lock(s)', $response['countAlert']);
    }

    public function testAcquireLockAlreadyLocked(): void
    {
        $this->set_POST([
            'acquireLock' => '/admin/pages/page1',
        ]);

        $this->set_LOCKFILE([
            '/admin/pages/page1' => [
                'email' => 'user2@domain.com',
                'fullname' => 'User 2',
                'timestamp' => 1234556789,
            ]
        ]);

        $lockHandler = new LockHandler();
        $response = $lockHandler->handleRequest();

        if ($response === null) {
            $this->fail('Variable $response is null');
        }

        $this->assertEquals(true, $response['isOnPage']);
        $this->assertEquals(false, $response['isLockGranted']);
        $this->assertEquals('User 2', $response['byUser']);
        $this->assertEquals(60000, $response['keepAliveInterval']);
        $this->assertEquals("User 2 is currently editing this page. \nPlease try again later, or contact User 2 to coordinate editing of page.\n", $response['alert']);
    }

    public function testAcquireLockGranted(): void
    {
        $this->set_POST([
            'acquireLock' => '/admin/pages/page2',
        ]);

        $this->set_LOCKFILE([
            '/admin/pages/page1' => [
                'email' => 'user2@domain.com',
                'fullname' => 'User 2',
                'timestamp' => 1234556789,
            ]
        ]);

        $lockHandler = new LockHandler();
        $response = $lockHandler->handleRequest();

        if ($response === null) {
            $this->fail('Variable $response is null');
        }

        $this->assertEquals(true, $response['isOnPage']);
        $this->assertEquals(true, $response['isLockGranted']);
        $this->assertEquals('User 1', $response['byUser']);
        $this->assertEquals(60000, $response['keepAliveInterval']);
        $this->assertEquals('Locks has been acquired successfully', $response['alert']);
    }

    public function testClearExpiredLocks(): void
    {
        $this->set_POST([
            'acquireLock' => '/admin/pages/page2',
        ]);

        $this->set_LOCKFILE([
            '/admin/pages/page1' => [
                'email' => 'user2@domain.com',
                'fullname' => 'User 2',
                'timestamp' => 0,
            ]
        ]);

        $lockHandler = new LockHandler();
        $response = $lockHandler->handleRequest();

        $this->set_POST([
            'readLocks' => '',
        ]);

        $response = $lockHandler->handleRequest();

        if ($response === null) {
            $this->fail('Variable $response is null');
        }

        $this->assertEquals(1, count($response['locks']));
        $this->assertEquals(true, isset($response['locks']['/admin/pages/page2']));
    }

    public function testForceRemoveLock(): void
    {
        $this->set_POST([
            'forceRemoveLock' => '/admin/pages/page2',
        ]);

        $this->set_LOCKFILE([
            '/admin/pages/page1' => [
                'email' => 'user1@domain.com',
                'fullname' => 'User 1',
                'timestamp' => 0,
            ],
            '/admin/pages/page2' => [
                'email' => 'user2@domain.com',
                'fullname' => 'User 2',
                'timestamp' => 0,
            ]
        ]);

        $lockHandler = new LockHandler();
        $response = $lockHandler->handleRequest();

        if ($response === null) {
            $this->fail('Variable $response is null');
        }

        $this->assertEquals(true, $response['isLockRemoved']);
        $this->assertEquals('Lock has been removed successfully.', $response['alert']);
    }

    private function set_LOCKFILE(array $locks): void
    {
        $this->lockFile->save($locks);
        $this->lockFile->free();
    }

    private function set_POST(array $post): void
    {
        global $_POST;
        $_POST = $post;
    }
}
