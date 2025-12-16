<?php

declare(strict_types=1);

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
use function cache;
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
    protected $signature = 'make:api-resource 
                            {model : The model class name} 
                            {--module= : The module name (optional)}
                            {--force : Overwrite existing files without confirmation}
                            {--only= : Generate only specific parts (controller, actions, requests, resource)}
                            {--except= : Exclude specific parts from generation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API resource with Controller, Actions, Requests and Resource classes.';

    /**
     * List of relation types that return a collection.
     *
     * @var array<string>
     */
    protected array $relationTypes = [
        'HasMany',
        'HasOne',
        'BelongsToMany',
        'MorphToMany',
        'MorphTo',
        'MorphOne',
        'MorphMany',
        'BelongsTo',
        'HasOneThrough',
        'HasManyThrough',
        'MorphedByMany',
    ];

    /**
     * List of relation types that return a single model.
     *
     * @var array<string>
     */
    protected array $singleRelations = [
        'HasOne',
        'MorphOne',
        'BelongsTo',
        'MorphTo',
        'HasOneThrough',
    ];

    /**
     * @var object
     */
    private object $config;

    /**
     * @param StubGenerator $stubGenerator
     */
    public function __construct(
        protected StubGenerator $stubGenerator
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        cache()->clear();

        if (!$this->determineModelExists()) {
            return self::FAILURE;
        }

        $tasks = $this->getGenerationTasks();

        $this->output->info("Generating API Resource for {$this->config->modelName}...");
        $this->output->newLine();

        foreach ($tasks as $taskName => $task) {
            if ($this->shouldGenerate($taskName)) {
                $this->line("Processing <comment>$taskName</comment>...", 'v');
                $task();
            }
        }

        $this->output->newLine();
        $this->info("Api resource for {$this->config->modelName} created successfully.");
        $this->displayRouteSuggestion();

        return self::SUCCESS;
    }

    /**
     * Define the available generation tasks.
     *
     * @return array<string, callable>
     */
    private function getGenerationTasks(): array
    {
        return [
            'controller' => fn () => $this->copyController(),
            'actions'    => fn () => $this->copyActions(),
            'requests'   => fn () => $this->copyRequests(),
            'resource'   => fn () => $this->copyResource(),
        ];
    }

    /**
     * Determine if a specific part should be generated based on --only and --except flags.
     *
     * @param string $part
     * @return bool
     */
    private function shouldGenerate(string $part): bool
    {
        $only = $this->option('only') ? explode(',', (string) $this->option('only')) : [];
        $except = $this->option('except') ? explode(',', (string) $this->option('except')) : [];

        if (!empty($only) && !in_array($part, $only)) {
            return false;
        }

        if (!empty($except) && in_array($part, $except)) {
            return false;
        }

        return true;
    }

    /**
     * Validate if the model exists and has the required traits.
     *
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

        $this->line("Model found: <comment>$modelPath</comment>");

        $this->fillConfig($modelPath);

        return true;
    }

    /**
     * @param string $modelPath
     * @return void
     */
    private function fillConfig(string $modelPath): void
    {
        $modelInstance = new $modelPath();
        $className = $modelInstance::class;

        $this->config = (object) [
            'model'            => $modelInstance,
            'modelPath'        => $className,
            'modelName'        => str($className)->afterLast('\\')->toString(),
            'modelPlural'      => str($className)->afterLast('\\')->plural()->toString(),
            'modelLowerPlural' => str($className)->afterLast('\\')->lower()->plural()->toString(),
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
            return str($config)->replace('{module}', '')->toString();
        }

        if (str($config)->contains('module') && $this->hasOption('module')) {
            return str($config)->replace('{module}', $this->option('module'))->toString();
        }

        return (string) $config;
    }

    /**
     * @return void
     */
    private function copyController(): void
    {
        $newPath = base_path($this->getConfig('namespaces.controllers'));
        $fileName = "Api{$this->config->modelName}Controller.php";
        $targetPath = "$newPath/$fileName";

        // For single files, we use the standard overwrite check
        if (!$this->shouldOverwrite($targetPath)) {
            return;
        }

        $this->ensureDirectoryExists($newPath);

        File::copy($this->stubGenerator->getFilePath("Http/Controllers/ApiTemplateController"), $targetPath);

        $content = File::get($targetPath);

        $newContent = str($content)
            ->replace('<namespace>', $this->getConfig('namespaces.controllers'))
            ->replace('<modelLower>', str($this->config->modelName)->lcFirst()->toString())
            ->replace('<modelPath>', $this->config->modelPath)
            ->replace('<modelPlural>', $this->config->modelPlural)
            ->replace('<modelName>', $this->config->modelName)
            ->replace('<requestNamespace>', $this->getConfig('namespaces.requests'))
            ->replace('<actionNamespace>', $this->getConfig('namespaces.actions'));

        $this->updateFile($targetPath, $newContent->toString());
    }

    /**
     * @return void
     */
    private function copyActions(): void
    {
        $baseNamespace = $this->getConfig('namespaces.actions');
        $newPath = base_path("$baseNamespace/{$this->config->modelPlural}");
        $overwriteAll = false;

        // Check if directory exists and determine overwrite strategy
        if (File::isDirectory($newPath)) {
            if ($this->option('force')) {
                $overwriteAll = true;
            } elseif (!collect(File::files($newPath))->isEmpty()) {
                // Ask once for the whole folder
                $overwriteAll = $this->confirm("Actions directory for '{$this->config->modelPlural}' already exists. Do you want to overwrite all actions?", true);
            }
        }

        $this->ensureDirectoryExists($newPath);
        $this->stubGenerator->copyDirectory('Actions/Templates', $newPath);

        // Filter only .stub files to avoid re-processing existing PHP files (prevents double prompts)
        $files = collect(File::files($newPath))->filter(fn ($file) => str($file->getFilename())->endsWith('.stub'));

        foreach ($files as $file) {
            $content = str($file->getContents())
                ->replace('<syncRelationMethods>', $this->getMethodRelationsForActions($file))
                ->replace('<callableRelation>', $this->getCallableRelationForActions());

            $newContent = str($content)
                ->replace('<namespace>', "$baseNamespace\\{$this->config->modelPlural}")
                ->replace('<modelLower>', str($this->config->modelName)->lcFirst()->toString())
                ->replace('<modelPath>', $this->config->modelPath)
                ->replace('<modelPlural>', $this->config->modelPlural)
                ->replace('<modelName>', $this->config->modelName)
                ->replace('<modelLowerPlural>', $this->config->modelLowerPlural)
                ->replace('<requestNamespace>', $this->getConfig('namespaces.requests'))
                ->replace('<resourceNamespace>', $this->getConfig('namespaces.resources'));

            $newName = str($file->getFilename())
                ->replace('Templates', $this->config->modelPlural)
                ->replace('Template', $this->config->modelName)
                ->replace('.stub', '.php')
                ->toString();

            // Pass the $overwriteAll flag to avoid individual prompts if accepted
            $this->storeFile("$newPath/$newName", $newContent->toString(), $overwriteAll);

            File::delete($file->getPathname());
        }
    }

    /**
     * @param SplFileInfo $file
     * @return string
     */
    private function getMethodRelationsForActions(SplFileInfo $file): string
    {
        $relations = ApiModel::relations($this->config->model, false);
        $dir = str($file->getFilename())->startsWith(['Update']) ? "Update" : "Store";
        $content = str('');

        foreach ($relations as $relation) {
            $className = $this->getClassName($relation);

            if (
                in_array($relation, config('laravel-api-resource.exclude.resources', []))
                || !in_array($className, $this->relationTypes)
                || in_array($className, $this->singleRelations)
            ) {
                continue;
            }

            $pivotRelations = ['BelongsToMany', 'MorphToMany', 'MorphedByMany'];
            $fileType = in_array($className, $pivotRelations) ? "PivotRelation" : "Relation";

            $content = $content->append(File::get($this->stubGenerator->getFilePath("Actions/$dir/$fileType")))
                ->replace('<ucRelation>', ucfirst($relation))
                ->replace('<relation>', $relation);
        }

        return $content->toString();
    }

    /**
     * @return string
     */
    private function getCallableRelationForActions(): string
    {
        $relations = ApiModel::relations($this->config->model, false);
        $content = str('');
        $lower = str($this->config->modelName)->lcFirst()->toString();

        foreach ($relations as $relation) {
            $className = $this->getClassName($relation);

            if (
                in_array($relation, config('laravel-api-resource.exclude.resources', []))
                || !in_array($className, $this->relationTypes)
                || in_array($className, $this->singleRelations)
            ) {
                continue;
            }

            $content = $content->append("\n        " . str($relation)->ucfirst()->prepend('$this->sync')->append("(\$request, \${$lower});"));
        }

        return $content->toString();
    }

    /**
     * @return void
     */
    private function copyResource(): void
    {
        $newPath = base_path($this->getConfig('namespaces.resources'));
        $targetPath = "$newPath/{$this->config->modelName}Resource.php";

        // Single file, standard check
        if (!$this->shouldOverwrite($targetPath)) {
            return;
        }

        $this->ensureDirectoryExists($newPath);

        File::copy($this->stubGenerator->getFilePath("Http/Resources/TemplateResource"), $targetPath);

        $content = File::get($targetPath);

        $newContent = str($content)
            ->replace('<namespace>', $this->getConfig('namespaces.resources'))
            ->replace('<fillables>', $this->getFillablesForResource())
            ->replace('<relations>', $this->getRelationsForResource())
            ->replace('<modelName>', $this->config->modelName);

        $this->updateFile($targetPath, $newContent->toString());
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

            $content = $content->append("\n            '$fillable' => \$this->whenHas('$fillable'),");
        }

        return $content->toString();
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

            $className = $this->getClassName($relation);

            if (in_array($className, $this->singleRelations)) {
                $type = "new \\$namespace";
            } else {
                $type = "\\$namespace::collection";
            }
            $content = $content->append("\n            '$relation' => $type(\$this->whenLoaded('$relation')),");
        }

        return $content->toString();
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
            $options = $related->map(fn ($file) => $file->getPathname())->values()->toArray();
            $choice = $this->choice("Multiple resources found for relation '$relation', please select the correct one:", $options);

            $chosenFile = $related->first(fn ($file) => $file->getPathname() === $choice);

            return $this->extractNamespace($chosenFile);
        }

        return $this->extractNamespace($related->first());
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
        $baseNamespace = $this->getConfig('namespaces.requests');
        $newPath = base_path("$baseNamespace/{$this->config->modelPlural}");
        $overwriteAll = false;

        // Check if directory exists and determine overwrite strategy
        if (File::isDirectory($newPath)) {
            if ($this->option('force')) {
                $overwriteAll = true;
            } elseif (!collect(File::files($newPath))->isEmpty()) {
                $overwriteAll = $this->confirm("Requests directory for '{$this->config->modelPlural}' already exists. Do you want to overwrite all requests?", true);
            }
        }

        $this->ensureDirectoryExists($newPath);
        $this->stubGenerator->copyDirectory('Http/Requests/Templates', $newPath);

        // Filter only .stub files
        $files = collect(File::files($newPath))->filter(fn ($file) => str($file->getFilename())->endsWith('.stub'));

        foreach ($files as $file) {
            $content = $file->getContents();

            $newContent = str($content)
                ->replace('<namespace>', "$baseNamespace\\{$this->config->modelPlural}")
                ->replace('<modelPath>', $this->config->modelPath)
                ->replace('<modelName>', $this->config->modelName);

            $newName = str($file->getFilename())
                ->replace('Templates', $this->config->modelPlural)
                ->replace('Template', $this->config->modelName)
                ->replace('.stub', '.php')
                ->toString();

            if (str($file->getFilename())->startsWith(['Update', 'Store'])) {
                $isUpdate = str($file->getFilename())->startsWith('Update');
                $requiredLabel = $isUpdate ? 'sometimes' : 'required';

                $newContent = $newContent->replace('<fillables>', $this->getFillablesForRequest($requiredLabel))
                    ->replace('<relations>', $this->getRelationsForRequest($requiredLabel))
                    ->replace('<relationAttributes>', $this->getRelationAttributesForRequest())
                    ->replace('<attributes>', $this->getAttributesForRequest());
            }

            // Pass the $overwriteAll flag
            $this->storeFile("$newPath/$newName", $newContent->toString(), $overwriteAll);

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

            $content = $content->append("\n            '$keyName' => __('$key'),");
        }

        return $content->toString();
    }

    /**
     * @return string
     */
    private function getRelationAttributesForRequest(): string
    {
        $relations = ApiModel::relations($this->config->model, false);
        $content = str('');

        foreach ($relations as $relation) {
            if (in_array($relation, config('laravel-api-resource.exclude.resources', []))) {
                continue;
            }

            $object = $this->config->model->$relation();
            $className = $this->getClassName($relation);
            $isSingle = in_array($className, $this->singleRelations);
            $prefix = $isSingle ? $relation : "$relation.*";

            $content = $content->append($this->getAttributesForRequest($object->getModel(), $prefix));
        }

        return $content->toString();
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
            $className = $this->getClassName($relation);

            if (in_array($relation, config('laravel-api-resource.exclude.resources', [])) || !in_array($className, $this->relationTypes)) {
                continue;
            }

            $object = $this->config->model->$relation();

            if (in_array($className, ['BelongsToMany', 'MorphToMany', 'MorphedByMany'])) {
                $pivotFillables = $this->getFillablesForRequestUsingPivot($requiredLabel, $object, "$relation.*");
                $content = $content->append("\n            '$relation' => ['nullable', 'array'],$pivotFillables");
            } else {
                $isSingle = in_array($className, $this->singleRelations);
                $prefix = $isSingle ? $relation : "$relation.*";
                $rule = "['nullable', 'array']";

                $relationFillables = $this->getFillablesForRequestRelation($requiredLabel, $object->getModel(), $prefix, [$object->getForeignKeyName()]);
                $content = $content->append("\n            '$relation' => $rule,$relationFillables");
            }
        }

        return $content->toString();
    }

    /**
     * @param string $requiredLabel
     * @param Model|null $model
     * @param string|null $keyPrefix
     * @param array $ignore
     * @return string
     */
    private function getFillablesForRequest(string $requiredLabel = 'required', ?Model $model = null, ?string $keyPrefix = null, array $ignore = []): string
    {
        $useModel = $model ?? $this->config->model;
        $fillables = ApiModel::fillable($useModel);
        $pdoColumns = $this->getPDOColumns($useModel);
        $content = str("");

        foreach ($fillables as $fillable) {
            $keyName = $keyPrefix ? "$keyPrefix.$fillable" : $fillable;

            if (in_array($fillable, [...config('laravel-api-resource.exclude.requests', []), ...$ignore]) || $useModel->getKeyName() === $fillable) {
                continue;
            }

            $pdoColumn = $pdoColumns->firstWhere('name', $fillable);

            if ($pdoColumn === null) {
                $this->warn("Column $fillable does not exist within your database scheme.");
                continue;
            }

            $rules = "{$this->columnRequired($pdoColumn, $requiredLabel)}, {$this->getColumnAttributes($fillable, $pdoColumn)}";
            $content = $content->append("\n            '$keyName' => [$rules],");
        }

        return $content->toString();
    }

    /**
     * @param string $requiredLabel
     * @param Model|null $model
     * @param string|null $keyPrefix
     * @param array $ignore
     * @return string
     */
    private function getFillablesForRequestRelation(string $requiredLabel = 'required', ?Model $model = null, ?string $keyPrefix = null, array $ignore = []): string
    {
        $useModel = $model ?? $this->config->model;
        $fillables = ApiModel::fillable($useModel);
        $pdoColumns = $this->getPDOColumns($useModel);
        $relatedPdoColumn = $this->getPDOColumns($useModel->getModel())->firstWhere('name', 'id');
        $keyName = $keyPrefix ? "$keyPrefix." : "";

        $idRules = "{$this->columnRequired($relatedPdoColumn, $requiredLabel)}, {$this->getColumnAttributes('id', $relatedPdoColumn)}";
        $content = str("\n            '{$keyName}id' => [$idRules],");

        foreach ($fillables as $fillable) {
            $keyName = $keyPrefix ? "$keyPrefix.$fillable" : $fillable;

            if (in_array($fillable, [...config('laravel-api-resource.exclude.requests', []), ...$ignore]) || $useModel->getKeyName() === $fillable) {
                continue;
            }

            $pdoColumn = $pdoColumns->firstWhere('name', $fillable);

            if ($pdoColumn === null) {
                continue;
            }

            $rules = "{$this->columnRequired($pdoColumn, $requiredLabel)}, {$this->getColumnAttributes($fillable, $pdoColumn)}";
            $content = $content->append("\n            '$keyName' => [$rules],");
        }

        return $content->toString();
    }

    /**
     * @param string $requiredLabel
     * @param mixed $model
     * @param string|null $keyPrefix
     * @return string
     */
    private function getFillablesForRequestUsingPivot(string $requiredLabel = 'required', mixed $model = null, ?string $keyPrefix = null): string
    {
        $fillables = $model->getPivotColumns();
        $pdoColumns = $this->getPDOColumns($model);
        $relatedPdoColumn = $this->getPDOColumns($model->getModel())->firstWhere('name', 'id');
        $keyName = $keyPrefix ? "$keyPrefix." : "";

        $idRules = "{$this->columnRequired($relatedPdoColumn, $requiredLabel)}, {$this->getColumnAttributes('id', $relatedPdoColumn)}";
        $content = str("\n            '{$keyName}id' => [$idRules],");

        foreach ($fillables as $fillable) {
            $pdoColumn = $pdoColumns->firstWhere('name', $fillable);

            if ($pdoColumn === null) {
                continue;
            }

            $rules = "{$this->columnRequired($pdoColumn, $requiredLabel)}, {$this->getColumnAttributes($fillable, $pdoColumn)}";
            $content = $content->append("\n            '$keyName$fillable' => [$rules],");
        }

        return $content->toString();
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
                    ->getColumns(str($model->getTable())->afterLast('.')->toString()));
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
     * @param array|null $pdoColumn
     * @return string
     */
    private function getColumnType(?array $pdoColumn)
    {
        if (!$pdoColumn) {
            return "'string'";
        }

        switch ($pdoColumn['type_name'] ?? '') {
            case "char":
                return "'uuid'";
            case "varchar":
                $max = str($pdoColumn['type'])->between('(', ')');
                return "'string', 'max:$max', 'min:1'";
            case "datetime":
            case "timestamp":
                return "'date_format:Y-m-d H:i:s'";
            case "int":
            case "bigint":
                return "'int'";
            case "tinyint":
                return "'boolean'";
            case "json":
                return "'array'";
            case "double":
            case "decimal":
            case "float":
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
     * @param bool $forceOverwrite
     * @return void
     */
    private function storeFile(string $path, string $content, bool $forceOverwrite = false): void
    {
        $name = str($path)->afterLast('/');

        if ($forceOverwrite || $this->shouldOverwrite($path)) {
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
            $this->info("Updated: $name");
        } else {
            $this->error("File $name does not exist");
        }
    }

    /**
     * Helper to determine if a file should be written based on existence and force flag.
     *
     * @param string $path
     * @return bool
     */
    private function shouldOverwrite(string $path): bool
    {
        $name = str($path)->afterLast('/');

        if (File::exists($path)) {
            if ($this->option('force')) {
                return true;
            }
            return $this->confirm("$name already exists, overwrite it?", true);
        }

        return true;
    }

    /**
     * @param string $path
     * @return void
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
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
                ->replace('.php', '')
                ->toString();
    }

    /**
     * Output helpful route suggestion.
     *
     * @return void
     */
    private function displayRouteSuggestion(): void
    {
        $controllerName = "Api{$this->config->modelName}Controller";
        $controllerNamespace = $this->getConfig('namespaces.controllers');
        $resourceName = str($this->config->modelPlural)->snake('-');

        $this->comment("Add the following line to your routes/api.php:");
        $this->output->block("Route::apiResource('$resourceName', \\$controllerNamespace\\$controllerName::class);", null, 'fg=yellow', '  ');
    }
}
