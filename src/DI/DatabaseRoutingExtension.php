<?php declare(strict_types=1);

namespace blitzik\Routing\DI;

use blitzik\Router\LocalesRouter\NeonLocalesLoader;
use blitzik\Router\RoutesLoader\NeonRoutesLoader;
use Kdyby\Doctrine\DI\IEntityProvider;
use Nette\DI\CompilerExtension;
use blitzik\Router\Router;
use Nette\DI\Compiler;

class DatabaseRoutingExtension extends CompilerExtension implements IEntityProvider
{
    /**
     * Processes configuration data. Intended to be overridden by descendant.
     * @return void
     */
    public function loadConfiguration()
    {
        $cb = $this->getContainerBuilder();

        Compiler::loadDefinitions($cb, $this->loadFromFile(__DIR__ . '/services.neon'), $this->name);
    }


    /**
     * Adjusts DI container before is compiled to PHP class. Intended to be overridden by descendant.
     * @return void
     */
    public function beforeCompile()
    {
        $cb = $this->getContainerBuilder();

        $cb->removeDefinition('routing.router');

        $router = $cb->addDefinition($this->prefix('router'));
        $router->setClass(Router::class);

        $neonRoutesLoader = $cb->getByType(NeonRoutesLoader::class);
        if ($neonRoutesLoader !== null) {
            $cb->removeDefinition($neonRoutesLoader);
        }

        $localesLoader = $cb->getByType(NeonLocalesLoader::class);
        if ($localesLoader !== null) {
            $cb->removeDefinition($localesLoader);
        }
    }


    /**
     * Returns associative array of Namespace => mapping definition
     *
     * @return array
     */
    public function getEntityMappings()
    {
        return ['blitzik\Routing' => __DIR__ . '/..'];
    }

}