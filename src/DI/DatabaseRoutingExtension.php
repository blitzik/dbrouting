<?php declare(strict_types=1);

namespace blitzik\Routing\DI;

use blitzik\Router\LocalesLoader\NeonLocalesLoader;
use blitzik\Router\RoutesLoader\NeonRoutesLoader;
use Kdyby\Doctrine\DI\IEntityProvider;
use Nette\DI\CompilerExtension;
use Nette\DI\Compiler;

class DatabaseRoutingExtension extends CompilerExtension implements IEntityProvider
{
    /**
     * Processes configuration data. Intended to be overridden by descendant.
     * @return void
     */
    public function loadConfiguration(): void
    {
        $cb = $this->getContainerBuilder();

        Compiler::loadDefinitions($cb, $this->loadFromFile(__DIR__ . '/services.neon'), $this->name);
    }


    /**
     * Adjusts DI container before is compiled to PHP class. Intended to be overridden by descendant.
     * @return void
     */
    public function beforeCompile(): void
    {
        $cb = $this->getContainerBuilder();

        $cb->removeDefinition('routing.router');

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
    public function getEntityMappings(): array
    {
        return ['blitzik\Routing' => __DIR__ . '/..'];
    }

}