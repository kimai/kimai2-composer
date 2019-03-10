<?php

/*
 * This file is part of the Kimai time-tracking app (composer-plugin-installer).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kimai2\Composer;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;

class PluginInstaller extends LibraryInstaller
{
    /**
     * {@inheritdoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        $packageName = $package->getName();

        $dirname = substr($package->getPrettyName(), strrpos($package->getPrettyName(), '/') + 1);

        $extra = $package->getExtra();
        if (isset($extra['kimai']) && isset($extra['kimai']['name'])) {
            $dirname = $extra['kimai']['name'];
        }

        $postfix = substr($dirname, -6);
        if ($postfix !== 'Bundle') {
            $this->io->writeError(
                sprintf('Unable to install Kimai plugin, package name "%s" must either end with "Bundle" or you provide a name via composer "extra.kimai.name" config', $packageName)
            );

            return parent::getInstallPath($package);
        }

        $rootDir = dirname($this->composer->getConfig()->get('vendor-dir'));

        return $rootDir . '/var/plugins/' . $dirname . '/';
    }

    /**
     * {@inheritdoc}
     */
    public function supports($packageType)
    {
        $allowedTypes = [
            'kimai-plugin',
        ];

        return in_array($packageType, $allowedTypes);
    }
}
