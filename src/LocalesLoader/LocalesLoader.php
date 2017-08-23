<?php declare(strict_types=1);

namespace blitzik\Routing\LocalesLoader;

use blitzik\Router\LocalesLoader\ILocalesLoader;

// todo
class LocalesLoader implements ILocalesLoader
{
    public function loadLocales(): array
    {
        return [];
    }


    public function getDefaultLocale(): string
    {
        return '';
    }

}