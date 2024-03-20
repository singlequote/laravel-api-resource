<?php

namespace SingleQuote\LaravelApiResource\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SingleQuote\LaravelApiResource\Service\ApiRequestService;
use SingleQuote\LaravelApiResource\Traits\HasApi;
use Symfony\Component\Finder\SplFileInfo;

use function base_path;
use function class_uses_recursive;
use function config;
use function str;

class MakeApiResource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api-resource {model} {--module=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new Api resource';

    private object $config;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->determineModelExists()) {
            return 0;
        }

        $this->copyController();
        $this->copyActions();
        $this->copyRequests();
        $this->copyResource();

        $this->info('Api resource created...');
    }

    private function determineModelExists(): bool
    {
        $name = $this->argument('model');

        if (str($name)->contains('/')) {
            $modelPath = str($name)->replace('/', '\\')->value();
        } else {
            $modelPath = $this->getConfig('namespaces.models')."\\$name";
        }

        if (! class_exists($modelPath)) {
            $this->error("The provided model could not be found! $modelPath::class");

            return false;
        }

        if (! in_array(HasApi::class, class_uses_recursive($modelPath))) {
            $this->error('The provided model does not include the HasApi trait!');

            return false;
        }

        $this->info("Model found as $modelPath::class");

        $this->fillConfig($modelPath);

        return true;
    }

    private function fillConfig(string $modelPath): void
    {
        $this->config = (object) [
            'model' => new $modelPath,
            'modelPath' => (new $modelPath)::class,
            'modelName' => str((new $modelPath)::class)->afterLast('\\'),
            'modelPlural' => str((new $modelPath)::class)->afterLast('\\')->plural(),
            'modelLowerPlural' => str((new $modelPath)::class)->afterLast('\\')->lower()->plural(),
        ];
    }

    private function getConfig(string $configName, mixed $default = null, bool $replaceKeys = true): string
    {
        $config = config("laravel-api-resource.$configName", $default);

        if (str($config)->contains('module') && ! $replaceKeys) {
            return str($config)->replace('{module}', '');
        }

        if (str($config)->contains('module') && $this->hasOption('module')) {
            return str($config)->replace('{module}', $this->option('module'));
        }

        return $config;
    }

    private function copyController(): void
    {
        $newPath = base_path($this->getConfig('namespaces.controllers'));

        if (File::exists("$newPath/Api{$this->config->modelName}Controller.php") && ! $this->confirm("Api{$this->config->modelName}Controller.php already exists, overwrite it?", true)) {
            return;
        }

        if (! File::isDirectory($newPath)) {
            File::makeDirectory($newPath);
        }

        File::copy(__DIR__.'/../Template/Http/Controllers/ApiTemplateController.stub', "$newPath/Api{$this->config->modelName}Controller.php");

        $content = File::get("$newPath/Api{$this->config->modelName}Controller.php");

        $newContent = str($content)
            ->replace('<namespace>', $this->getConfig('namespaces.controllers'))
            ->replace('<modelLower>', str($this->config->modelName)->lcFirst())
            ->replace('<modelPath>', $this->config->modelPath)
            ->replace('<modelPlural>', $this->config->modelPlural)
            ->replace('<modelName>', $this->config->modelName)
            ->replace('<requestNamespace>', $this->getConfig('namespaces.requests'))
            ->replace('<actionNamespace>', $this->getConfig('namespaces.actions'));

        $this->updateFile("$newPath/Api{$this->config->modelName}Controller.php", $newContent);
    }

    private function copyActions(): void
    {
        $newPath = base_path($this->getConfig('namespaces.actions')."/{$this->config->modelPlural}");

        $this->deleteDirectory($newPath);

        File::copyDirectory(__DIR__.'/../Template/Actions/Templates', $newPath);

        foreach (File::files($newPath) as $file) {

            $content = $file->getContents();

            $newContent = str($content)
                ->replace('<namespace>', $this->getConfig('namespaces.actions')."\\{$this->config->modelPlural}")
                ->replace('<modelLower>', str($this->config->modelName)->lcFirst())
                ->replace('<modelPath>', $this->config->modelPath)
                ->replace('<modelPlural>', $this->config->modelPlural)
                ->replace('<modelName>', $this->config->modelName)
                ->replace('<modelLowerPlural>', $this->config->modelLowerPlural)
                ->replace('<requestNamespace>', $this->getConfig('namespaces.requests'))
                ->replace('<resourceNamespace>', $this->getConfig('namespaces.resources'));

            $newName = str($file->getFilename())
                ->replace('Templates', $this->config->modelPlural)
                ->replace('Template', $this->config->modelName)
                ->replace('.stub', '.php');

            $this->storeFile("$newPath/$newName", $newContent);

            File::delete($file->getPathname());
        }
    }

    private function copyRequests(): void
    {
        $newPath = base_path($this->getConfig('namespaces.requests')."/{$this->config->modelPlural}");

        $this->deleteDirectory($newPath);

        File::copyDirectory(__DIR__.'/../Template/Http/Requests/Templates', $newPath);

        foreach (File::files($newPath) as $file) {
            $content = $file->getContents();

            $newContent = str($content)
                ->replace('<namespace>', $this->getConfig('namespaces.requests')."\\{$this->config->modelPlural}")
                ->replace('<modelPath>', $this->config->modelPath)
                ->replace('<modelName>', $this->config->modelName);

            $newName = str($file->getFilename())
                ->replace('Templates', $this->config->modelPlural)
                ->replace('Template', $this->config->modelName)
                ->replace('.stub', '.php');

            if (str($file->getFilename())->startsWith(['Update', 'Store'])) {
                $newContent = $newContent->replace('<fillables>', $this->getFillablesForRequest(str($file->getFilename())->startsWith('Update') ? 'sometimes' : 'required'))
                    ->replace('<attributes>', $this->getAttributesForRequest());
            }

            $this->storeFile("$newPath/$newName", $newContent);

            File::delete($file->getPathname());
        }
    }

    private function copyResource(): void
    {
        $newPath = base_path($this->getConfig('namespaces.resources'));

        if (File::exists("$newPath/{$this->config->modelName}Resource.php") && ! $this->confirm("{$this->config->modelName}Resource.php already exists, overwrite it?", true)) {
            return;
        }

        if (! File::isDirectory($newPath)) {
            File::makeDirectory($newPath);
        }

        File::copy(__DIR__.'/../Template/Http/Resources/TemplateResource.stub', "$newPath/{$this->config->modelName}Resource.php");

        $content = File::get("$newPath/{$this->config->modelName}Resource.php");

        $newContent = str($content)
            ->replace('<namespace>', $this->getConfig('namespaces.resources'))
            ->replace('<fillables>', $this->getFillablesForResource())
            ->replace('<relations>', $this->getRelationsForResource())
            ->replace('<modelName>', $this->config->modelName);

        $this->updateFile("$newPath/{$this->config->modelName}Resource.php", $newContent);
    }

    private function getFillablesForResource(): string
    {
        $fillables = ApiRequestService::getFillable($this->config->modelPath);

        $content = str('');

        foreach (explode(',', $fillables) as $fillable) {
            if (in_array($fillable, config('laravel-api-resource.exclude.resources', []))) {
                continue;
            }

            $content = $content->append("
            '$fillable' => \$this->whenHas('$fillable'),\r");
        }

        return $content;
    }

    private function getRelationsForResource(): string
    {
        $relations = ApiRequestService::getRelations($this->config->modelPath);

        $content = str('');

        foreach (explode(',', $relations) as $relation) {
            if (in_array($relation, config('laravel-api-resource.exclude.resources', []))) {
                continue;
            }

            $object = $this->config->model->$relation();
            $model = get_class($object->getModel());

            $files = File::allFiles($this->getConfig('namespaces.resources', null, false));

            foreach ($files as $file) {

                $namespace = $this->extractNamespace($file);

                if (str($this->getClassName($relation))->startsWith(['HasOne', 'morphOne']) || $this->getClassName($relation) === 'BelongsTo') {
                    $type = "new \\$namespace";
                } else {
                    $type = "\\$namespace::collection";
                }

                if ($namespace && str($file->getFilename())->contains(str($model)->afterLast('\\')->append('Resource'))) {
                    $content = $content->append("
            '$relation' => $type(\$this->whenLoaded('$relation')),\r");
                }
            }
        }

        return $content;
    }

    private function getFillablesForRequest($preFixed = 'required'): string
    {
        $fillables = ApiRequestService::getFillable($this->config->modelPath);

        $content = str('');

        foreach (explode(',', $fillables) as $fillable) {
            if (in_array($fillable, config('laravel-api-resource.exclude.requests', [])) || $this->config->model->getKeyName() === $fillable) {
                continue;
            }

            $content = $content->append("
            '$fillable' => ['$preFixed', {$this->getRelationByColumn($fillable)}],\r");
        }

        return $content;
    }

    private function getRelationByColumn(string $column): string
    {
        $relations = ApiRequestService::getRelations($this->config->modelPath);

        foreach (explode(',', $relations) as $relation) {
            $object = $this->config->model->$relation();

            if ($this->getClassName($relation) === 'BelongsTo' && $object->getForeignKeyName() === $column) {
                $model = get_class($object->getModel());

                return "\$this->ruleExists(new \\$model())";
            }
        }

        return "'string'";
    }

    /**
     * Return className of relation
     */
    private function getClassName(string $relation): string
    {
        $type = get_class($this->config->model->{$relation}());
        $class = explode('\\', $type);

        return end($class);
    }

    private function getAttributesForRequest(): string
    {
        $fillables = ApiRequestService::getFillable($this->config->modelPath);

        $content = str('');

        foreach (explode(',', $fillables) as $fillable) {
            if (in_array($fillable, config('laravel-api-resource.exclude.requests', [])) || $this->config->model->getKeyName() === $fillable) {
                continue;
            }

            $prefix = $this->getConfig('namespaces.translations');

            $slugged = str($this->config->modelName);

            $key = str("$prefix$slugged.$fillable")->lower()->ltrim('.');

            $content = $content->append("
            '$fillable' => __('$key'),\r");
        }

        return $content;
    }

    private function storeFile(string $path, string $content): void
    {
        $name = str($path)->afterLast('/');

        if (File::exists($path) && $this->confirm("$name already exists, overwrite it?", true)) {
            $this->info("Created: $name");
            File::put($path, $content);
        }

        if (! File::exists($path)) {
            File::put($path, $content);
            $this->info("Created: $name");
        }
    }

    private function updateFile(string $path, string $content): void
    {
        $name = str($path)->afterLast('/');

        if (File::exists($path)) {
            File::put($path, $content);
            $this->info("Created: $name");
        }

        if (! File::exists($path)) {
            $this->error("File $name does not exists");
        }
    }

    private function deleteDirectory(string $path): void
    {
        $name = str($path)->afterLast('');

        if (File::isDirectory($path) && $this->confirm("$name already directory exists, delete the directory?", true)) {
            File::deleteDirectory($path);
        }
    }

    public function extractNamespace(SplFileInfo $file): ?string
    {
        $content = $file->getContents();

        if (str($content)->contains('<namespace>')) {
            return null;
        }

        return str($content)
            ->betweenFirst('namespace ', ';')
            ->append('\\')
            ->append($file->getFilename())
            ->replace('.php', '');
    }
}
