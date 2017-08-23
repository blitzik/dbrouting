<?php declare(strict_types = 1);

namespace blitzik\Routing\Queries;

use Kdyby\Persistence\Queryable;
use Kdyby\Doctrine\QueryObject;
use blitzik\Routing\Url;
use Kdyby;

class UrlQuery extends QueryObject
{
    /** @var array */
    private $select = [];

    /** @var array  */
    private $filter = [];


    public function withRedirectionUrl(): self
    {
        $this->select[] = function (Kdyby\Doctrine\QueryBuilder $qb) {
            $qb->addSelect('rt')
               ->leftJoin('u.urlToRedirect', 'rt');
        };

        return $this;
    }


    public function byPath(string $path): self
    {
        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($path) {
            $qb->andWhere('u.urlPath = :path')->setParameter('path', $path);
        };

        return $this;
    }


    public function byPresenter(string $presenter): self
    {
        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($presenter) {
            $qb->andWhere('u.presenter = :presenter')->setParameter('presenter', $presenter);
        };

        return $this;
    }


    public function byAction(string $action): self
    {
        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($action) {
            $qb->andWhere('u.action = :action')->setParameter('action', $action);
        };

        return $this;
    }


    public function byInternalId(string $internalId): self
    {
        $this->filter[] = function (Kdyby\Doctrine\QueryBuilder $qb) use ($internalId) {
            $qb->andWhere('u.internalId = :internalId')->setParameter('internalId', $internalId);
        };

        return $this;
    }


    protected function doCreateCountQuery(Queryable $repository)
    {
        $qb = $this->createBasicQuery($repository);
        $qb->select('COUNT(u.id)');

        return $qb;
    }


    protected function doCreateQuery(Kdyby\Persistence\Queryable $repository)
    {
        $qb = $this->createBasicQuery($repository);
        $qb->select('u');

        foreach ($this->select as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }


    private function createBasicQuery(Kdyby\Persistence\Queryable $repository)
    {
        /** @var Kdyby\Doctrine\QueryBuilder $qb */
        $qb = $repository->getEntityManager()->createQueryBuilder();
        $qb->from(Url::class, 'u');

        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }

}