<?php declare(strict_types=1);

namespace blitzik\Routing;

use blitzik\Routing\Exceptions\InvalidArgumentException;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Nette\Utils\Validators;
use Nette\Utils\Strings;

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

    const URL_PATH_LENGTH = 255;


    /**
     * @ORM\Column(name="url_path", type="string", length=255, nullable=true, unique=true)
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


    public function getParameters(): array
    {
        return [];
    }

    
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


    public function convertToRouterUrl(): \blitzik\Router\Url
    {
        $url = new \blitzik\Router\Url();

        $url->setUrlPath($this->urlPath);
        $url->setDestination($this->presenter, $this->action);
        $url->setInternalId($this->internalId);

        if ($this->urlToRedirect !== null) {
            $url->setRedirectTo($this->urlToRedirect->convertToRouterUrl());
        }

        foreach ($this->getParameters() as $name => $value) {
            $url->addParameter($name, $value);
        }

        return $url;
    }

}