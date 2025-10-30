<?php

namespace SingleQuote\LaravelApiResource\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use SingleQuote\LaravelApiResource\Generator\StubGenerator;
use SingleQuote\LaravelApiResource\Infra\ApiModel;
use SingleQuote\LaravelApiResource\Traits\HasApi;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

use function base_path;
use function class_uses_recursive;
use function collect;
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
    protected array $relationTypes = [
        'HasMany',
        'BelongsToMany',
        'MorphToMany',
        'MorphTo',
    ];

    /**
     * @var object
     */
    private object $config;

    public function __construct(
        protected StubGenerator $stubGenerator
    ) {
        parent::__construct();
    }

    /**
     * @return int
     */
    public function handle()
    {
        cache()->clear();

        if (!$this->determineModelExists()) {
            return 0;
        }

        $this->copyController();
        $this->copyActions();
        $this->copyRequests();
        $this->copyResource();

        $this->info('Api resource created...');
    }

    /**
     * @return bool
     */
    private function determineModelExists(): bool
    {
        $name = $this->argument('model');

        if (str($name)->contains('/')) {
            $modelPath = str($name)->replace('/', '\\')->value();
        } else {
            $modelPath = $this->getConfig('namespaces.models') . "\\$name";
        }

        if (!class_exists($modelPath)) {
            $this->error("The provided model could not be found! $modelPath::class");

            return false;
        }

        if (!in_array(HasApi::class, class_uses_recursive($modelPath))) {
            $this->error('The provided model does not include the HasApi trait!');

            return false;
        }

        $this->info("Model found as $modelPath::class");

        $this->fillConfig($modelPath);

        return true;
    }

    /**
     * @param string $modelPath
     * @return void
     */
    private function fillConfig(string $modelPath): void
    {
        $this->config = (object) [
                'model' => new $modelPath(),
                'modelPath' => (new $modelPath())::class,
                'modelName' => str((new $modelPath())::class)->afterLast('\\'),
                'modelPlural' => str((new $modelPath())::class)->afterLast('\\')->plural(),
                'modelLowerPlural' => str((new $modelPath())::class)->afterLast('\\')->lower()->plural(),
        ];
    }

    /**
     * @param string $configName
     * @param mixed $default
     * @param bool $replaceKeys
     * @return string
     */
    private function getConfig(string $configName, mixed $default = null, bool $replaceKeys = true): string
    {
        $config = config("laravel-api-resource.$configName", $default);

        if (str($config)->contains('module') && !$replaceKeys) {
            return str($config)->replace('{module}', '');
        }

        if (str($config)->contains('module') && $this->hasOption('module')) {
            return str($config)->replace('{module}', $this->option('module'));
        }

        return $config;
    }

    /**
     * @return void
     */
    private function copyController(): void
    {
        $newPath = base_path($this->getConfig('namespaces.controllers'));

        if (File::exists("$newPath/Api{$this->config->modelName}Controller.php") && !$this->confirm("Api{$this->config->modelName}Controller.php already exists, overwrite it?", true)) {
            return;
        }

        if (!File::isDirectory($newPath)) {
            File::makeDirectory($newPath);
        }

        File::copy($this->stubGenerator->getFilePath("Http/Controllers/ApiTemplateController"), "$newPath/Api{$this->config->modelName}Controller.php");

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

    /**
     * @return void
     */
    private function copyActions(): void
    {
        $newPath = base_path($this->getConfig('namespaces.actions') . "/{$this->config->modelPlural}");

        $this->deleteDirectory($newPath);

        $this->stubGenerator->copyDirectory('Actions/Templates', $newPath);

        foreach (File::files($newPath) as $file) {
            $content = str($file->getContents())
                ->replace('<syncRelationMethods>', $this->getMethodRelationsForActions($file))
                ->replace('<callableRelation>', $this->getCallableRelationForActions());

            $newContent = str($content)
                ->replace('<namespace>', $this->getConfig('namespaces.actions') . "\\{$this->config->modelPlural}")
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

    /**
     * @return string
     */
    private function getMethodRelationsForActions(SplFileInfo $file): string
    {
        $relations = ApiModel::relations($this->config->model, false);
        $dir = str($file->getFilename())->startsWith(['Update']) ? "Update" : "Store";
        $content = str('');

        foreach ($relations as $relation) {
            if (in_array($relation, config('laravel-api-resource.exclude.resources', [])) || !str($this->getClassName($relation))->startsWith($this->relationTypes)) {
                continue;
            }

            $fileType = str($this->getClassName($relation))->startsWith('HasMany') ? "Relation" : "PivotRelation";

            $content = $content->append(File::get($this->stubGenerator->getFilePath("Actions/$dir/$fileType")))
                ->replace('<ucRelation>', ucFirst($relation))
                ->replace('<relation>', $relation);
        }


        return $content;
    }

    /**
     * @return string
     */
    private function getCallableRelationForActions(): string
    {
        $relations = ApiModel::relations($this->config->model, false);

        $content = str('');

        foreach ($relations as $relation) {

            if (in_array($relation, config('laravel-api-resource.exclude.resources', [])) || !str($this->getClassName($relation))->startsWith($this->relationTypes)) {
                continue;
            }

            $lower = str($this->config->modelName)->lcFirst();
            $content = $content->append("
        " . str($relation)->ucfirst()->prepend('$this->sync')->append("(\$request, \${$lower});"));
        }

        return $content;
    }

    /**
     * @return void
     */
    private function copyResource(): void
    {
        $newPath = base_path($this->getConfig('namespaces.resources'));

        if (File::exists("$newPath/{$this->config->modelName}Resource.php") && !$this->confirm("{$this->config->modelName}Resource.php already exists, overwrite it?", true)) {
            return;
        }

        if (!File::isDirectory($newPath)) {
            File::makeDirectory($newPath);
        }

        File::copy($this->stubGenerator->getFilePath("Http/Resources/TemplateResource"), "$newPath/{$this->config->modelName}Resource.php");

        $content = File::get("$newPath/{$this->config->modelName}Resource.php");

        $newContent = str($content)
            ->replace('<namespace>', $this->getConfig('namespaces.resources'))
            ->replace('<fillables>', $this->getFillablesForResource())
            ->replace('<relations>', $this->getRelationsForResource())
            ->replace('<modelName>', $this->config->modelName);

        $this->updateFile("$newPath/{$this->config->modelName}Resource.php", $newContent);
    }

    /**
     * @return string
     */
    private function getFillablesForResource(): string
    {
        $fillables = ApiModel::fillable($this->config->model);

        $content = str('');

        foreach ($fillables as $fillable) {
            if (in_array($fillable, config('laravel-api-resource.exclude.resources', []))) {
                continue;
            }

            $content = $content->append("
            '$fillable' => \$this->whenHas('$fillable'),\r");
        }

        return $content;
    }

    /**
     * @return string
     */
    private function getRelationsForResource(): string
    {
        $relations = ApiModel::relations($this->config->model, false);

        $content = str('');

        foreach ($relations as $relation) {

            if (in_array($relation, config('laravel-api-resource.exclude.resources', []))) {
                continue;
            }

            try {
                $object = $this->config->model->$relation();
            } catch (\Throwable $ex) {
                continue;
            }

            $namespace = $this->getRelatedResource($object, $relation, $this->hasOption('module'));

            if (!$namespace) {
                continue;
            }

            if (str($this->getClassName($relation))->startsWith(['HasOne', 'MorphOne', 'MorphTo', 'BelongsTo']) && $this->getClassName($relation) !== 'BelongsToMany') {
                $type = "new \\$namespace";
            } else {
                $type = "\\$namespace::collection";
            }
            $content = $content->append("
            '$relation' => $type(\$this->whenLoaded('$relation')),\r");
        }

        return $content;
    }

    /**
     * @param Relation $object
     * @param string $relation
     * @param bool $tryWithinModule
     * @return string|null
     */
    private function getRelatedResource(Relation $object, string $relation, bool $tryWithinModule = true): ?string
    {
        $files = $this->tryLocateFiles($tryWithinModule);

        $model = get_class($object->getModel());

        $related = collect($files)->filter(function ($file) use ($model) {
            $namespace = $this->extractNamespace($file);

            return $namespace && str($file->getFilename())->contains(str($model)->afterLast('\\')->append('Resource'));
        });

        if ($tryWithinModule && $related->isEmpty()) {
            return $this->getRelatedResource($object, $relation, false);
        }

        if ($related->isEmpty()) {
            return null;
        }

        if ($related->count() > 1) {
            $relatedFile = $this->choice("Multiple resources found, please select correct resource for $relation", $related->values()->toArray());

            return str($relatedFile)->before('.php')->value();
        } else {
            return $this->extractNamespace($related->first());
        }

        return null;
    }

    /**
     * @param bool $onlyModule
     * @return Collection
     */
    private function tryLocateFiles(bool $onlyModule = false): Collection
    {
        if ($onlyModule && $this->hasOption('module')) {
            $files = collect(File::allFiles($this->getConfig('namespaces.resources', null, true)));

            if ($files->isNotEmpty()) {
                return $files;
            }
        }

        return collect(File::allFiles($this->getConfig('namespaces.resources', null, false)));
    }

    /**
     * @return void
     */
    private function copyRequests(): void
    {
        $newPath = base_path($this->getConfig('namespaces.requests') . "/{$this->config->modelPlural}");

        $this->deleteDirectory($newPath);

        $this->stubGenerator->copyDirectory('Http/Requests/Templates', $newPath);

        foreach (File::files($newPath) as $file) {
            $content = $file->getContents();

            $newContent = str($content)
                ->replace('<namespace>', $this->getConfig('namespaces.requests') . "\\{$this->config->modelPlural}")
                ->replace('<modelPath>', $this->config->modelPath)
                ->replace('<modelName>', $this->config->modelName);

            $newName = str($file->getFilename())
                ->replace('Templates', $this->config->modelPlural)
                ->replace('Template', $this->config->modelName)
                ->replace('.stub', '.php');

            if (str($file->getFilename())->startsWith(['Update', 'Store'])) {
                $newContent = $newContent->replace('<fillables>', $this->getFillablesForRequest(str($file->getFilename())->startsWith('Update') ? 'sometimes' : 'required'))
                    ->replace('<relations>', $this->getRelationsForRequest(str($file->getFilename())->startsWith('Update') ? 'sometimes' : 'required'))
                    ->replace('<relationAttributes>', $this->getRelationAttributesForRequest())
                    ->replace('<attributes>', $this->getAttributesForRequest());
            }

            $this->storeFile("$newPath/$newName", $newContent);

            File::delete($file->getPathname());
        }
    }

    /**
     * @param Model|null $model
     * @param string|null $keyPrefix
     * @return string
     */
    private function getAttributesForRequest(?Model $model = null, ?string $keyPrefix = null): string
    {
        $useModel = $model ?? $this->config->model;
        $fillables = ApiModel::fillable($useModel);

        $content = str('');

        foreach ($fillables as $fillable) {
            if (in_array($fillable, config('laravel-api-resource.exclude.requests', [])) || $useModel->getKeyName() === $fillable) {
                continue;
            }

            $keyName = $keyPrefix ? "$keyPrefix.$fillable" : $fillable;

            $prefix = str($this->getConfig('namespaces.translations'))->lower();

            $slugged = str($useModel::class)->afterLast('\\')->snake()->lower()->plural()->replace('_', '-');
            $translateKeyName = str($fillable)->ucfirst()->replace(['-', '_'], ' ');
            $key = str("$prefix$slugged.$translateKeyName")->ltrim('.');

            $content = $content->append("
            '$keyName' => __('$key'),\r");
        }

        return $content;
    }

    /**
     * @return string
     */
    private function getRelationAttributesForRequest(): string
    {
        $relations = ApiModel::relations($this->config->model, false);

        $content = str('');

        foreach ($relations as $relation) {

            if (in_array($relation, config('laravel-api-resource.exclude.resources', [])) || !str($this->getClassName($relation))->startsWith(['HasMany'])) {
                continue;
            }

            $object = $this->config->model->$relation();

            $content = $content->append($this->getAttributesForRequest($object->getModel(), "$relation.*"));
        }

        return $content;
    }

    /**
     * @param string $requiredLabel
     * @return string
     */
    private function getRelationsForRequest(string $requiredLabel = 'required'): string
    {
        $relations = ApiModel::relations($this->config->model, false);

        $content = str('');

        foreach ($relations as $relation) {

            if (in_array($relation, config('laravel-api-resource.exclude.resources', [])) || !str($this->getClassName($relation))->startsWith($this->relationTypes)) {
                continue;
            }

            $object = $this->config->model->$relation();

            if (str($this->getClassName($relation))->startsWith(['BelongsToMany', 'MorphToMany'])) {
                $content = $content->append("
            '$relation' => ['nullable', 'array'],\r{$this->getFillablesForRequestUsingPivot($requiredLabel, $object, "$relation.*")}
                ");
            } else {
                /** @var \Illuminate\Database\Eloquent\Relations\HasMany $object */
                $content = $content->append("
            '$relation' => ['nullable', 'array'],{$this->getFillablesForRequestRelation($requiredLabel, $object->getModel(), "$relation.*", [$object->getForeignKeyName()])}
                ");
            }
        }

        return $content;
    }

    /**
     * @param string $requiredLabel
     * @param Model|null $model
     * @param string|null $keyPrefix
     * @return string
     */
    private function getFillablesForRequest(string $requiredLabel = 'required', ?Model $model = null, ?string $keyPrefix = null, array $ignore = []): string
    {
        $useModel = $model ?? $this->config->model;
        $fillables = ApiModel::fillable($useModel);
        $pdoColumns = $this->getPDOColumns($useModel);
        $keyName = $keyPrefix ? "$keyPrefix." : "";

        $content = str("");

        foreach ($fillables as $fillable) {
            $keyName = $keyPrefix ? "$keyPrefix.$fillable" : $fillable;

            if (in_array($fillable, [... config('laravel-api-resource.exclude.requests', []), ... $ignore]) || $useModel->getKeyName() === $fillable) {
                continue;
            }

            $pdoColumn = $pdoColumns->firstWhere('name', $fillable);

            if ($pdoColumn === null) {
                $this->error("Column $fillable does not exists within your database scheme.");
                continue;
            }

            $content = $content->append(
                "
            '$keyName' => [{$this->columnRequired($pdoColumn, $requiredLabel)}, {$this->getColumnAttributes($fillable, $pdoColumn)}],\r"
            );
        }

        return $content;
    }

    /**
     * @param string $requiredLabel
     * @param Model|null $model
     * @param string|null $keyPrefix
     * @return string
     */
    private function getFillablesForRequestRelation(string $requiredLabel = 'required', ?Model $model = null, ?string $keyPrefix = null, array $ignore = []): string
    {
        $useModel = $model ?? $this->config->model;
        $fillables = ApiModel::fillable($useModel);

        $pdoColumns = $this->getPDOColumns($useModel);
        $relatedPdoColumn = $this->getPDOColumns($useModel->getModel())->firstWhere('name', 'id');

        $keyName = $keyPrefix ? "$keyPrefix." : "";

        $content = str("
            '{$keyName}id' => [{$this->columnRequired($relatedPdoColumn, $requiredLabel)}, {$this->getColumnAttributes('id', $relatedPdoColumn)}],\r");

        foreach ($fillables as $fillable) {
            $keyName = $keyPrefix ? "$keyPrefix.$fillable" : $fillable;

            if (in_array($fillable, [... config('laravel-api-resource.exclude.requests', []), ... $ignore]) || $useModel->getKeyName() === $fillable) {
                continue;
            }

            $pdoColumn = $pdoColumns->firstWhere('name', $fillable);

            if ($pdoColumn === null) {
                $this->error("Column $fillable does not exists within your database scheme.");
                continue;
            }

            $content = $content->append(
                "
            '$keyName' => [{$this->columnRequired($pdoColumn, $requiredLabel)}, {$this->getColumnAttributes($fillable, $pdoColumn)}],\r"
            );
        }

        return $content;
    }

    /**
     * @param string $requiredLabel
     * @param Model|null $model
     * @param string|null $keyPrefix
     * @return string
     */
    private function getFillablesForRequestUsingPivot(string $requiredLabel = 'required', mixed $model = null, ?string $keyPrefix = null): string
    {
        $fillables = $model->getPivotColumns();

        $pdoColumns = $this->getPDOColumns($model);
        $relatedPdoColumn = $this->getPDOColumns($model->getModel())->firstWhere('name', 'id');

        $keyName = $keyPrefix ? "$keyPrefix." : "";

        $content = str("
            '{$keyName}id' => [{$this->columnRequired($relatedPdoColumn, $requiredLabel)}, {$this->getColumnAttributes('id', $relatedPdoColumn)}],\r");

        foreach ($fillables as $fillable) {
            $pdoColumn = $pdoColumns->firstWhere('name', $fillable);

            if ($pdoColumn === null) {
                $this->error("Column $fillable does not exists within your database scheme.");
                continue;
            }

            $content = $content->append("
            '$keyName$fillable' => [{$this->columnRequired($pdoColumn, $requiredLabel)}, {$this->getColumnAttributes($fillable, $pdoColumn)}],\r");
        }

        return $content;
    }

    /**
     * @param Relation|Model $model
     * @return Collection
     */
    private function getPDOColumns(Relation|Model $model): Collection
    {
        try {
            $connection = $model instanceof Relation ? $model->getModel()->getConnectionName() : $model->getConnectionName();

            return collect(DB::connection($connection)
                    ->getSchemaBuilder()
                    ->getColumns(str($model->getTable())->afterLast('.')));
        } catch (Throwable $ex) {
            return collect([]);
        }
    }

    /**
     * @param array|null $pdoColumn
     * @param string $requiredLabel
     * @return string
     */
    private function columnRequired(?array $pdoColumn = null, string $requiredLabel = 'required'): string
    {
        if (isset($pdoColumn['nullable']) && $pdoColumn['nullable']) {
            return "'nullable'";
        }

        return "'$requiredLabel'";
    }

    /**
     * @param string $column
     * @param array|null $pdoColumn
     * @return string
     */
    private function getColumnAttributes(string $column, ?array $pdoColumn = null): string
    {
        $relations = ApiModel::relations($this->config->model, false);

        foreach ($relations as $relation) {
            try {
                $object = $this->config->model->$relation();
            } catch (\Throwable $ex) {
                continue;
            }


            if ($this->getClassName($relation) === 'BelongsTo' && $object->getForeignKeyName() === $column) {
                $model = get_class($object->getModel());

                return "\$this->ruleExists(new \\$model())";
            }
        }

        return $this->getColumnType($pdoColumn);
    }

    /**
     * @param array $pdoColumn
     * @return string
     */
    private function getColumnType(array $pdoColumn)
    {
        switch ($pdoColumn['type_name'] ?? '') {
            case "char":
                return "'uuid'";
            case "varchar":
                $max = str($pdoColumn['type'])->between('(', ')');
                return "'string', 'max:$max', 'min:1'";
            case "datetime":
                return "'date_format:Y-m-d H:i:s'";
            case "timestamp":
                return "'date_format:Y-m-d H:i:s'";
            case "int":
                return "'int'";
            case "tinyint":
                return "'boolean'";
            case "json":
                return "'array'";
            case "double":
                return "'numeric'";
            case "enum":
                $items = str($pdoColumn['type'])->between('(', ')')->replace("'", '');
                return "'in:$items'";
        }

        return "'string'";
    }

    /**
     * @param string $relation
     * @return string|null
     */
    private function getClassName(string $relation): ?string
    {
        try {
            $type = get_class($this->config->model->{$relation}());
            $class = explode('\\', $type);
        } catch (\Throwable $ex) {
            return '';
        }

        return end($class);
    }

    /**
     * @param string $path
     * @param string $content
     * @return void
     */
    private function storeFile(string $path, string $content): void
    {
        $name = str($path)->afterLast('/');

        if (File::exists($path) && $this->confirm("$name already exists, overwrite it?", true)) {
            $this->info("Created: $name");
            File::put($path, $content);
        }

        if (!File::exists($path)) {
            File::put($path, $content);
            $this->info("Created: $name");
        }
    }

    /**
     * @param string $path
     * @param string $content
     * @return void
     */
    private function updateFile(string $path, string $content): void
    {
        $name = str($path)->afterLast('/');

        if (File::exists($path)) {
            File::put($path, $content);
            $this->info("Created: $name");
        }

        if (!File::exists($path)) {
            $this->error("File $name does not exists");
        }
    }

    /**
     * @param string $path
     * @return void
     */
    private function deleteDirectory(string $path): void
    {
        $name = str($path)->afterLast('');

        if (File::isDirectory($path) && $this->confirm("$name already directory exists, delete the directory?", true)) {
            File::deleteDirectory($path);
        }
    }

    /**
     * @param SplFileInfo $file
     * @return string|null
     */
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
