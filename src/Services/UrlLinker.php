<?php declare(strict_types=1);

namespace blitzik\Routing\Services;

use blitzik\Routing\Exceptions\UrlNotPersistedException;
use Kdyby\Doctrine\EntityManager;
use Nette\Caching\IStorage;
use blitzik\Router\Router;
use Nette\Caching\Cache;
use blitzik\Routing\Url;
use Nette\SmartObject;

class UrlLinker
{
    use SmartObject;
    
    
    /** @var Cache */
    private $cache;

    /** @var EntityManager */
    private $em;


    public function __construct(
        EntityManager $entityManager,
        IStorage $storage
    ) {
        $this->em = $entityManager;
        $this->cache = new Cache($storage, Router::ROUTING_NAMESPACE);
    }


    /**
     * @param Url $oldUrl
     * @param Url $newUrl
     * @return void
     * @throws \Exception
     */
    public function linkUrls(Url $oldUrl, Url $newUrl)
    {
        if ($oldUrl->getId() === null or $newUrl->getId() === null) {
            throw new UrlNotPersistedException;
        }

        try {
            $this->em->beginTransaction();

            $alreadyRedirectedUrls = $this->findByActualUrl($oldUrl->getId());

            /** @var Url $url */
            foreach ($alreadyRedirectedUrls as $url) {
                $url->setRedirectTo($newUrl);
                $this->cache->clean([Cache::TAGS => [$url->getCacheKey()]]);
            }

            $oldUrl->setRedirectTo($newUrl);
            $this->cache->clean([Cache::TAGS => [$oldUrl->getCacheKey()]]);

            $this->em->flush();
            $this->em->commit();

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->em->close();

            throw $e;
        }
    }


    /**
     * @param int $actualUrlID
     * @return array
     */
    private function findByActualUrl($actualUrlID): array
    {
        return $this->em->createQuery(
                   'SELECT u FROM ' .Url::class. ' u
                    WHERE u.actualUrlToRedirect = :urlToRedirect'
               )->setParameter('urlToRedirect', $actualUrlID)
                ->getResult();
    }




}