<?php

/*
 * This file is part of the Kimai time-tracking app (composer-plugin-installer).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kimai2\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\Version\VersionParser;
use Composer\Plugin\PluginInterface;
use Composer\Installer\InstallerEvent;
use Composer\Semver\Constraint\Constraint;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    const LOCK_FILE = 'kimai-plugins.lock';

    /**
     * @var PluginInstaller
     */
    protected $installer;
    /**
     * @var string
     */
    protected $rootDir;
    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->rootDir = dirname($composer->getConfig()->get('vendor-dir'));

        $this->installer = new PluginInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($this->installer);

        $this->io = $io;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'pre-dependencies-solving'  => 'addPluginDependencies',
            'post-dependencies-solving' => 'writePluginLock',
        );
    }

    /**
     * @return bool
     */
    protected function isKimaiInstallation()
    {
        if (!file_exists($this->rootDir . '/src/Constants.php')) {
            return false;
        }

        return true;
    }

    public function addPluginDependencies(InstallerEvent $event)
    {
        if (!$this->isKimaiInstallation()) {
            $this->io->writeError('This is not a Kimai installation, skipping plugin installation');
            return;
        }

        $plugins = $this->getInstalledPlugins();
        foreach($plugins as $name => $version) {
            $this->io->write(
                sprintf('Checking Kimai plugin %s (Version: %s)', $name, $version)
            );
            $parser = new VersionParser();
            //$packages = $event->getPool()->whatProvides($name, new Constraint('=', $parser->normalize($version)));
            $event->getRequest()->install($name, new Constraint('=', $parser->normalize($version)));
        }
    }

    /**
     * @return string
     */
    protected function getLockDir()
    {
        return $this->rootDir . DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     */
    protected function getLockFilename()
    {
        return $this->getLockDir() . self::LOCK_FILE;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getInstalledPlugins()
    {
        $contents = $this->getLockContent();

        if (!isset($contents['plugins'])) {
            throw new \Exception(
                sprintf('Invalid Kimai lock file found, no plugins section: %s', self::LOCK_FILE)
            );
        }

        return $contents['plugins'];
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getLockContent()
    {
        $lockFile = $this->getLockFilename();

        if (!file_exists($lockFile)) {
            return [];
        }

        if (!is_readable($lockFile)) {
            throw new \Exception(
                sprintf('Kimai lock file is not readable, fix permission: %s', $lockFile)
            );
        }

        $existing = file_get_contents($lockFile);
        $contents = json_decode($existing, true);

        return $contents;
    }

    /**
     * @param InstallerEvent $event
     * @throws \Exception
     */
    public function writePluginLock(InstallerEvent $event)
    {
        if (!$this->isKimaiInstallation()) {
            return;
        }

        $installedPackages = [];
        foreach($this->installer->getInstalledPackages() as $package)
        {
            $packageName = $package->getName();
            $packageVersion = $package->getPrettyVersion();
            $installedPackages[$packageName] = $packageVersion;
        }

        $plugins = $this->getInstalledPlugins();
        $plugins = array_merge($plugins, $installedPackages);

        $this->writeLock($plugins);
    }

    /**
     * @param array $plugins
     * @throws \Exception
     */
    protected function writeLock(array $plugins)
    {
        $lockFile = $this->getLockFilename();

        if (file_exists($lockFile) && !is_writable($lockFile)) {
            throw new \Exception(
                sprintf('Kimai lock file cannot be written, fix permission: %s', $lockFile)
            );
        }

        if (!file_exists($lockFile) && !is_writable($this->getLockDir())) {
            throw new \Exception(
                sprintf('Kimai lock file cannot be written, fix permission: %s', $lockFile)
            );
        }

        $installed = $this->getInstalledPlugins();
        $oldHash = md5(json_encode($installed));
        $contentHash = md5(json_encode($plugins));

        if ($oldHash === $contentHash) {
            $this->io->write('Kimai plugins did not change');
            return;
        }

        $content = [
            'readme' => [
                'This file locks all installed Kimai plugins.',
                'Read more about it at https://www.kimai.org/documentation/plugins.html',
                'This file is generated automatically, do not edit it manually!'
            ],
            'time' => date(DATE_ATOM),
            'content-hash' => $contentHash,
            'plugins' => $plugins,
        ];

        $lock = new JsonFile($lockFile, null, $this->io);
        try {
            $lock->write($content);
        } catch (\Exception $ex) {
            $this->io->writeError(
                sprintf('Failed writing Kimai lock file: %s', $ex->getMessage())
            );
        }

        $this->io->write(
            sprintf('Writing Kimai lock file: %s', self::LOCK_FILE)
        );
    }
}
