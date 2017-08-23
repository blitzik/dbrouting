<?php declare(strict_types=1);

namespace blitzik\Routing\Services;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use blitzik\Routing\Exceptions\UrlAlreadyExistsException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Monolog\Logger;
use blitzik\Routing\Url;
use Nette\SmartObject;

class UrlPersister
{
    use SmartObject;


    /** @var Logger */
    private $logger;

    /** @var EntityManager */
    private $em;


    public function __construct(EntityManager $em, Logger $logger)
    {
        $this->em = $em;
        $this->logger = $logger->channel('dbRouting');
    }


    /**
     * @param Url $url
     * @return Url
     * @throws UrlAlreadyExistsException
     * @throws \Exception
     */
    public function save(Url $url): Url
    {
        try {
            $this->em->beginTransaction();

            if ($url->getId() !== null) {
                $url = $this->update($url);
            } else {
                $url = $this->create($url);
            }

            $this->em->commit();

        } catch (UrlAlreadyExistsException $uae) {
            $this->closeEntityManager();

            $this->logger->addCritical(sprintf('Url path already exists: %s', $uae->getMessage()));

            throw $uae;

        } catch (\Exception $e) {
            $this->closeEntityManager();

            $this->logger->addCritical(sprintf('Url Entity saving failure: %s', $e->getMessage()));

            throw $e;
        }

        return $url;
    }


    /**
     * @param Url $url
     * @return Url
     * @throws UrlAlreadyExistsException
     */
    private function create(Url $url): Url
    {
        /** @var Url $url */
        $url = $this->em->safePersist($url);
        if ($url === false) {
            throw new UrlAlreadyExistsException;
        }

        return $url;
    }


    /**
     * @param Url $url
     * @return Url
     * @throws UniqueConstraintViolationException
     */
    private function update(Url $url): Url
    {
        $this->em->flush();

        return $url;
    }


    private function closeEntityManager(): void
    {
        $this->em->rollback();
        $this->em->close();
    }
}