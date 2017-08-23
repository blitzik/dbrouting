<?php declare(strict_types=1);

namespace blitzik\Routing;

use blitzik\Routing\Exceptions\InvalidArgumentException;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use blitzik\Routing\Utils\ParametersCollector;
use blitzik\Routing\Utils\FilterCollector;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Nette\Utils\Validators;
use Nette\Utils\Strings;
use Nette\Utils\Json;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="url",
 *      indexes={
 *          @Index(name="presenter_action_internal_id", columns={"presenter", "action", "internal_id"})
 *      }
 * )
 */
class Url
{
    use Identifier;

    const CACHE_NAMESPACE = 'route/';

    const URL_PATH_LENGTH = 1000;


    /**
     * @ORM\Column(name="url_path", type="string", length=1000, nullable=false, unique=true)
     * @var string
     */
    private $urlPath;

    /**
     * @ORM\Column(name="presenter", type="string", length=255, nullable=true, unique=false)
     * @var string
     */
    private $presenter;

    /**
     * @ORM\Column(name="action", type="string", length=255, nullable=true, unique=false)
     * @var string
     */
    private $action;

    /**
     * @ORM\Column(name="internal_id", type="string", nullable=true, unique=false, options={"unsigned": false})
     * @var string
     */
    private $internalId;

    /**
     * @ORM\ManyToOne(targetEntity="Url", cascade={"persist"})
     * @ORM\JoinColumn(name="actual_url", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @var Url|null
     */
    private $urlToRedirect;

    /**
     * @ORM\Column(name="filters", type="text", length= 65535, nullable=true, unique=false)
     * @var string
     */
    private $filters;

    /**
     * @ORM\Column(name="internal_parameters", type="text", length= 65535, nullable=true, unique=false)
     * @var string
     */
    private $internalParameters;


    // -----


    /** @var array filters */
    private $f;

    /** @var array internal parameters */
    private $p;

    
    /*
     * --------------------
     * ----- SETTERS ------
     * --------------------
     */


    public function setUrlPath(string $path, bool $lower = false): void
    {
        Validators::assert($path, 'null|unicode:0..' . self::URL_PATH_LENGTH);
        $this->urlPath = $path === null ? null : Strings::webalize($path, '/.', $lower);
    }


    public function setInternalId(?string $internalId): void
    {
        Validators::assert($internalId, 'unicode|null');
        $this->internalId = $internalId;
    }


    public function setDestination(string $presenter, string $action = null): void
    {
        if ($action === null) {
            $destination = $presenter;
        } else {
            $destination = $presenter .':'. $action;
        }

        $matches = $this->resolveDestination($destination);

        $this->presenter = $matches['modulePresenter'];
        $this->action = $matches['action'];
    }


    private function resolveDestination(string $destination): array
    {
        // ((Module:)*(Presenter)):(action)
        if (!preg_match('~^(?P<modulePresenter>(?:(?:[A-Z][a-zA-Z]*):)*(?:[A-Z][a-zA-Z]*)):(?P<action>[a-z][a-zA-Z]*)$~', $destination, $matches)) {
            throw new InvalidArgumentException(
                'Wrong format of argument $presenter or $action.
                 Argument $presenter must have first letter upper-case and must not end with upper-case character in each part.');
        }

        return $matches;
    }


    public function setRedirectTo(Url $actualUrlToRedirect): void
    {
        $this->urlToRedirect = $actualUrlToRedirect;
    }


    public function setFilters(FilterCollector $collector): void
    {
        $this->filters = Json::encode($collector->getFilters());
        $this->f = $collector->getFilters();
    }


    public function setInternalParameters(ParametersCollector $collector): void
    {
        $this->internalParameters = Json::encode($collector->getParameters());
        $this->p = $collector->getParameters();
    }


    /*
     * --------------------
     * ----- GETTERS ------
     * --------------------
     */


    public function getUrlPath():string
    {
        return $this->urlPath;
    }


    public function getInternalId(): ?string
    {
        return $this->internalId;
    }


    public function getUrlToRedirect(): ?Url
    {
        return $this->urlToRedirect;
    }


    public function getCurrentUrlId(): ?int
    {
        if (!isset($this->actualUrlToRedirect)) {
            return $this->getId();
        }

        return $this->actualUrlToRedirect->getId();
    }


    public function getCurrentUrlPath(): string
    {
        if (!isset($this->urlToRedirect)) {
            return $this->urlPath;
        }

        return $this->urlToRedirect->getUrlPath();
    }


    public function getPresenter():string
    {
        return $this->presenter;
    }


    public function getAction(): string
    {
        return $this->action;
    }


    public function getDestination(): string
    {
        return $this->presenter. ':' .$this->action;
    }


    public function getAbsoluteDestination(): ?string
    {
        if (!isset($this->presenter, $this->action)) {
            return null;
        }

        return ':' .$this->presenter. ':' .$this->action;
    }


    public function getCacheKey(): string
    {
        return self::class . '/' . $this->getId();
    }


    public function getInternalParameters(): array
    {
        if ($this->p === null) {
            if ($this->internalParameters === null) {
                $this->p = [];
            } else {
                $this->p = Json::decode($this->internalParameters, Json::FORCE_ARRAY);
            }
        }

        return $this->p;
    }


    public function getFilters(): array
    {
        if ($this->f === null) {
            if ($this->filters === null) {
                $this->f = [];
            } else {
                $this->f = Json::decode($this->filters, Json::FORCE_ARRAY);
            }
        }

        return $this->f;
    }


    public function convertToRouterUrl(): \blitzik\Router\Url
    {
        $url = new \blitzik\Router\Url();

        $url->setUrlPath($this->urlPath);
        if ($this->presenter !== null) {
            $url->setDestination($this->presenter, $this->action);
        }
        $url->setInternalId($this->internalId);

        if ($this->urlToRedirect !== null) {
            $url->setRedirectTo($this->urlToRedirect->convertToRouterUrl());
        }

        foreach ($this->getInternalParameters() as $name => $value) {
            $url->addInternalParameter($name, $value);
        }

        foreach ($this->getFilters() as $parameterName => $filterName) {
            $url->addFilter($filterName, [$parameterName]);
        }

        return $url;
    }

}