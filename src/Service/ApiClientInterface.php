<?php
namespace App\Service;

interface ApiClientInterface
{
    public function fetchProperties(): array;
    public function getName(): string;
}