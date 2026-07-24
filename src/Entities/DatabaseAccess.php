<?php

declare(strict_types=1);

namespace Danilocgsilva\EntityClone\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'database_credentials')]
class DatabaseAccess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'host', type: 'string', length: 255)]
    private string $host;

    #[ORM\Column(name: 'user', type: 'string', length: 255)]
    private string $user;

    #[ORM\Column(name: 'password', type: 'text')]
    private string $password;

    #[ORM\Column(name: 'database_name', type: 'string', length: 255, nullable: true)]
    private ?string $databaseName;

    #[ORM\Column(name: 'port', type: 'integer')]
    private int $port;

    public function getId(): int
    {
        return $this->id;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getDatabaseName(): ?string
    {
        return $this->databaseName;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setHost(string $host): self
    {
        $this->host = $host;
        return $this;
    }

    public function setUser(string $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setDatabaseName(?string $databaseName): self
    {
        $this->databaseName = $databaseName;
        return $this;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;
        return $this;
    }
}