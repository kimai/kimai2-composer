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
     * @var PackageInterface[]
     */
    protected $packages= [];

    /**
     * {@inheritdoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        $packageName = $package->getName();
        $postfix = substr($packageName, -7);

        if ($postfix !== '-bundle') {
            throw new \InvalidArgumentException(
                sprintf('Unable to install Kimai plugin, package name "%s" must end with "-bundle"', $packageName)
            );
        }

        $this->packages[] = $package;

        return parent::getInstallPath($package);
    }

    /**
     * @return PackageInterface[]
     */
    public function getInstalledPackages()
    {
        return $this->packages;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($packageType)
    {
        $allowedTypes = [
            'kimai2-plugin',
            'kimai-plugin',
            'kimai-bundle',
        ];

        return in_array($packageType, $allowedTypes);
    }
}
