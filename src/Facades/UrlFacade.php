<?php declare(strict_types=1);

namespace blitzik\Routing\Facades;

use blitzik\Routing\Exceptions\UrlAlreadyExistsException;
use blitzik\Routing\Services\UrlPersister;
use blitzik\Routing\Services\UrlLinker;
use Kdyby\Doctrine\EntityRepository;
use Kdyby\Doctrine\EntityManager;
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


    /**
     * @param Url $old
     * @param Url $new
     * @return void
     */
    public function linkUrls(Url $old, Url $new)
    {
        $this->urlLinker->linkUrls($old, $new);
    }


    /**
     * @param string $urlPath
     * @return Url|null
     */
    public function getByPath($urlPath)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')
           ->from(Url::class, 'u')
           ->where('u.urlPath = :urlPath')
           ->setParameter('urlPath', $urlPath);

        return $qb->getQuery()->getOneOrNullResult();
    }


    /**
     * @param int $urlId
     * @return Url|null
     */
    public function getById(int $urlId)
    {
        return $this->urlRepository->find($urlId);
    }


    /**
     * @param string $presenter
     * @param string $action
     * @param int $internal_id
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUrl(string $presenter, string $action, int $internal_id)
    {
        return $this->em->createQuery(
            'SELECT u FROM ' . Url::class . ' u
             WHERE u.presenter = :presenter AND u.action = :action AND u.internalId = :internalID'
        )->setParameters([
            'presenter' => $presenter,
            'action' => $action,
            'internalID' => $internal_id
        ])->getOneOrNullResult();
    }


    public function removeUrlById(int $id)
    {
        $this->em->createQuery(
            'DELETE FROM ' . Url::class . ' u
             WHERE u.id = :id'
        )->setParameter('id', $id)
         ->execute();
    }

}