<?php declare(strict_types=1);

namespace blitzik\Routing\Services;

use Doctrine\Common\Persistence\ObjectManager;
use blitzik\Routing\Url;
use Nette\SmartObject;

class UrlGenerator
{
    use SmartObject;
    
    
    /** @var ObjectManager */
    private $em;

    /** @var string */
    private $presenter;

    /** @var bool */
    private $lowerCasePath = false;


    public function __construct($presenter, ObjectManager $manager)
    {
        $this->presenter = $presenter;
        $this->em = $manager;
    }


    public function useLowerCasePath(bool $b): void
    {
        $this->lowerCasePath = $b;
    }


    /**
     * @param string $presenter
     * @return UrlGenerator
     */
    public function addPresenter($presenter): UrlGenerator
    {
        $this->presenter = $presenter;

        return $this;
    }


    /**
     * @param string $url
     * @param string|null $action
     * @param string|null $internal_id
     * @return UrlGenerator
     */
    public function addUrl(string $url, string $action, string $internal_id = null): UrlGenerator
    {
        $url = self::create($url, $this->lowerCasePath, $this->presenter, $action, $internal_id);
        $this->em->persist($url);

        return $this;
    }


    /**
     * @param string $urlPath
     * @param bool $lowerCasePath
     * @param string $presenter
     * @param string|null $action
     * @param string|null $internal_id
     * @return Url
     */
    public static function create(string $urlPath, bool $lowerCasePath = true, string $presenter, string $action = null, string $internal_id = null): Url
    {
        $url = new Url();
        $url->setUrlPath($urlPath, $lowerCasePath);
        $url->setDestination($presenter, $action);
        $url->setInternalId($internal_id);

        return $url;
    }



}