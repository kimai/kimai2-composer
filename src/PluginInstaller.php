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
        $postfix = substr($package->getPrettyName(), -6);

        if ($postfix !== 'Bundle') {
            throw new \InvalidArgumentException(
                'Unable to install plugin, names for Kimai 2 plugins always need to end with: "Bundle"'
            );
        }

        /*
        if (file_exists('......')) {
            throw new \InvalidArgumentException(
                'Unable to install plugin, already existing...'
            );
        }
        */

        return 'var/plugins/' . substr($package->getPrettyName(), 23);
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
