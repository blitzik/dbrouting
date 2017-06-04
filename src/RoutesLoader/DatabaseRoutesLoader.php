<?php declare(strict_types=1);

namespace blitzik\Routing\RoutesLoader;

use blitzik\Router\RoutesLoader\IRoutesLoader;
use Kdyby\Doctrine\EntityManager;
use Nette\Caching\IStorage;
use Kdyby\Monolog\Logger;
use Nette\Caching\Cache;
use blitzik\Routing\Url;
use Nette\SmartObject;

final class DatabaseRoutesLoader implements IRoutesLoader
{
    use SmartObject;


    const ROUTING_NAMESPACE = 'appDatabaseRouting';


    /** @var Logger */
    private $logger;

    /** @var Cache */
    private $cache;

    /** @var EntityManager */
    private $em;


    public function __construct(
        EntityManager $entityManager,
        IStorage $storage,
        Logger $logger
    ) {
        $this->em = $entityManager;
        $this->logger = $logger->channel(self::ROUTING_NAMESPACE);
        $this->cache = new Cache($storage, self::ROUTING_NAMESPACE);
    }


    public function loadUrlByPath(string $urlPath): ?\blitzik\Router\Url
    {
        /** @var Url $urlEntity */
        $urlEntity = $this->cache->load($urlPath, function (& $dependencies) use ($urlPath) {
            /** @var Url $urlEntity */
            $urlEntity = $this->em->createQuery(
                'SELECT u, rt FROM ' .Url::class. ' u
                 LEFT JOIN u.urlToRedirect rt
                 WHERE u.urlPath = :urlPath'
            )->setParameter('urlPath', $urlPath)
             ->getOneOrNullResult();

            if ($urlEntity === null) {
                $this->logger->addError(sprintf('Page not found. URL_PATH: %s', $urlPath));
                return null;
            }

            $dependencies = [Cache::TAGS => $urlEntity->getCacheKey()];
            return $urlEntity;
        });

        if ($urlEntity === null) {
            return null;
        }

        return $urlEntity->convertToRouterUrl();
    }


    public function loadUrlByDestination(string $presenter, string $action, string $internalId = null): ?\blitzik\Router\Url
    {
        $urlPathCacheKey = sprintf('%s:%s:%s', $presenter, $action, $internalId);

        /** @var Url $urlEntity */
        $urlEntity = $this->cache->load($urlPathCacheKey, function (& $dependencies) use ($presenter, $action, $internalId) {
            $urlEntity = $this->getUrlEntity($presenter, $action, $internalId);
            if ($urlEntity === null) {
                $this->logger
                     ->addWarning(
                         sprintf(
                            'No route found | presenter: %s | action: %s | id %s',
                            $presenter,
                            $action,
                            $internalId
                         )
                     );
                return null;
            }

            $dependencies = [Cache::TAGS => $urlEntity->getCacheKey()];
            return $urlEntity;
        });

        if ($urlEntity === null) {
            return null;
        }

        return $urlEntity->convertToRouterUrl();
    }


    /**
     * @param string $presenter
     * @param string $action
     * @param string|null $internalId
     * @return Url|null
     */
    private function getUrlEntity(string $presenter, string $action, string $internalId = null): ?Url
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u, rt')
           ->from(Url::class, 'u')
           ->leftJoin('u.urlToRedirect', 'rt')
           ->where('u.presenter = :p AND u.action = :a')
           ->setParameters(['p' => $presenter, 'a' => $action]);

        if ($internalId !== null) {
            $qb->andWhere('u.internalId = :i')
               ->setParameter('i', $internalId);
        }

        /** @var Url[] $urls */
        $urls = $qb->getQuery()->setMaxResults(1)->getResult();

        if (empty($urls)) {
            return null;
        }

        return $urls[0];
    }

}