<?php

namespace App\Console\Commands;

use Blaaiz\LaravelSdk\Blaaiz;
use Illuminate\Console\Command;

class CheckRatesCommand extends Command
{
    protected $signature = 'blaaiz:rates {searchTerm?}';

    protected $description = 'Fetch Blaaiz exchange rates';

    public function handle(Blaaiz $blaaiz): int
    {
        $rates = $blaaiz->rates()->list($this->argument('searchTerm'));

        $this->line(json_encode($rates, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
