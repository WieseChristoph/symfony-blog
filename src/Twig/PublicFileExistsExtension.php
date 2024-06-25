<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PublicFileExistsExtension extends AbstractExtension
{
    private string $projectDirectory;

    public function __construct(string $projectDirectory)
    {
        $this->projectDirectory = $projectDirectory;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('public_file_exists', [$this, 'publicFileExists'])
        ];
    }

    public function publicFileExists(string $path): bool
    {
        return file_exists($this->projectDirectory . "/public/" . $path);
    }
}