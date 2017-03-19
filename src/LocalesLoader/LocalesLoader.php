<?php declare(strict_types=1);

namespace blitzik\Routing\LocalesLoader;

use blitzik\Router\LocalesRouter\ILocalesLoader;

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