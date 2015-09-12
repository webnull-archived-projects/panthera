<?php
namespace Panthera\deployment;
use Panthera\FileNotFoundException;

/**
 * Update PF2 composer packages
 *
 * @package Panthera\deployment\build\framework
 * @author Damian Kęska <damian@pantheraframework.org>
 */
class updateComposerPackagesTask extends task
{
    /**
     * Install composer into /bin directory of application
     *
     * @throws FileNotFoundException
     * @author Damian Kęska <damian@pantheraframework.org>
     * @return bool
     */
    public function execute()
    {
        // don't double the composer installation on travis-ci.org build
        if (in_array('--travisci', $_SERVER['argv']))
        {
            $this->output('=> Skipping composer update on travis-ci.org');
            return true;
        }

        // install a new copy of composer if not installed yet
        if (!is_file($this->app->appPath. "/.content/bin/composer"))
        {
           throw new FileNotFoundException('Composer is not installed', 'NO_COMPOSER_INSTALLED');
        }
        else
        {
            system("cd " .PANTHERA_FRAMEWORK_PATH. " && " . $this->app->appPath . "/.content/bin/composer update --no-interaction");
        }

        return true;
    }
}