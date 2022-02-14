<?php

/**
 * @see       https://github.com/laminas/laminas-skeleton-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-skeleton-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-skeleton-installer/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\SkeletonInstaller;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event as ScriptEvent;

use function version_compare;

/**
 * Plugin that uninstalls itself following a create-project operation.
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    /** @var Composer */
    private $composer;

    /** @var IOInterface */
    private $io;

    /**
     * Provide composer event listeners.
     *
     * This particular combination will ensure that the plugin works under each
     * of the following scenarios:
     *
     * - create-project
     * - install, with or without a composer.lock
     * - update, with or without a composer.lock
     *
     * After any of the above have run at least once, the plugin will uninstall
     * itself.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        $subscribers = [
            ['installOptionalDependencies', 1000],
        ];

        if (version_compare(PluginInterface::PLUGIN_API_VERSION, '2.0', 'lt')) {
            $subscribers[] = ['uninstallPlugin'];
        }

        return [
            'post-install-cmd' => $subscribers,
            'post-update-cmd'  => $subscribers,
        ];
    }

    /**
     * Activate the plugin
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io       = $io;
    }

    /**
     * Install optional dependencies, if any.
     */
    public function installOptionalDependencies(ScriptEvent $event)
    {
        $installer = new OptionalPackagesInstaller($this->composer, $this->io);
        $installer();
    }

    /**
     * Remove the installer after project installation.
     */
    public function uninstallPlugin(ScriptEvent $event)
    {
        $this->uninstall($event->getComposer(), $event->getIO());
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        $uninstall = new Uninstaller($composer, $io);
        $uninstall();
    }
}
