<?php
namespace SingleQuote\LaravelApiResource\Commands;

use Illuminate\Console\Command;

class Make extends Command
{

    /**
     * @var  string
     */
    protected $signature = 'seed:make {--path=} {--output=auto} {--with-events} {--only=}';

    /**
     * @var  string
     */
    protected $description = 'Create seeders from your models using the database';

    /**
     * 
     */
    public function handle()
    {
        dd('yo');
    }

}
