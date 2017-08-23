<?php declare(strict_types=1);

namespace blitzik\Routing\Facades;

use blitzik\Routing\Exceptions\UrlAlreadyExistsException;
use blitzik\Routing\Queries\UrlQuery;
use blitzik\Routing\Services\UrlPersister;
use blitzik\Routing\Services\UrlLinker;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\ResultSet;
use blitzik\Routing\Url;
use Nette\SmartObject;

class UrlFacade
{
    use SmartObject;
    
    
    /** @var EntityRepository */
    private $urlRepository;

    /** @var UrlPersister */
    private $urlPersister;

    /** @var UrlLinker */
    private $urlLinker;

    /** @var EntityManager */
    private $em;


    public function __construct(
        EntityManager $entityManager,
        UrlPersister $urlPersister,
        UrlLinker $urlLinker
    ) {
        $this->em = $entityManager;
        $this->urlPersister = $urlPersister;
        $this->urlLinker = $urlLinker;

        $this->urlRepository = $this->em->getRepository(Url::class);
    }


    /**
     * @param Url $url
     * @return Url
     * @throws UrlAlreadyExistsException
     * @throws \Exception
     */
    public function saveUrl(Url $url): Url
    {
        return $this->urlPersister->save($url);
    }


    public function getUrl(UrlQuery $query): ?Url
    {
        return $this->urlRepository->fetchOne($query);
    }


    public function findUrls(UrlQuery $query): ResultSet
    {
        return $this->urlRepository->fetch($query);
    }


    public function linkUrls(Url $old, Url $new): void
    {
        $this->urlLinker->linkUrls($old, $new);
    }


    public function getByPath(string $urlPath): ?Url
    {
        return $this->getUrl((new UrlQuery())->byPath($urlPath));
    }


    public function getById(int $urlId): ?Url
    {
        return $this->urlRepository->find($urlId);
    }


    public function getByDestination(string $presenter, string $action, string $internalId = null): ResultSet
    {
        $q = (new UrlQuery())
            ->byPresenter($presenter)
            ->byAction($action);

        if ($internalId !== null) {
            $q->byInternalId($internalId);
        }

        return $this->findUrls($q);
    }


    public function removeUrlById(int $id): void
    {
        $this->em->createQuery(
            'DELETE FROM ' . Url::class . ' u
             WHERE u.id = :id'
        )->setParameter('id', $id)
         ->execute();
    }

}