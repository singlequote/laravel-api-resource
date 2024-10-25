<?php

namespace SingleQuote\LaravelApiResource\Generator;

use Illuminate\Support\Facades\File;

/**
 * Description of Stubs
 *
 * @author wim_p
 */
class StubGenerator
{
    /**
     * @param string $path
     * @return string
     */
    public function getFilePath(string $path): string
    {
        if (File::exists(base_path("stubs/ApiResource/$path.stub"))) {
            return base_path("stubs/ApiResource/$path.stub");
        }

        return __DIR__ . "/../Template/$path.stub";
    }

    /**
     * @param string $path
     * @return string
     */
    public function getFolderPath(string $path): string
    {
        if (File::isDirectory(base_path("stubs/ApiResource/$path"))) {
            return base_path("stubs/ApiResource/$path");
        }

        return __DIR__ . "/../Template/$path";
    }

    /**
     * @param string $path
     * @return string
     */
    public function copyDirectory(string $path, $newPath): void
    {
        if (! File::isDirectory($newPath)) {
            File::makeDirectory(path: $newPath, recursive: true);
        }

        $files = File::allFiles(__DIR__ . "/../Template/$path");

        foreach ($files as $file) {

            $templatePath = str($file->getPathname())->after('../Template/')->before('.stub')->value();

            $stubPath = $this->getFilePath($templatePath);

            File::put("$newPath/{$file->getFilename()}", File::get($stubPath));
        }
    }
}
