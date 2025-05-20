<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ProductSeeder;

class GenerateMockProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:mock {count=50 : Number of products to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate mock product data with MongoDB details';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $count = $this->argument('count');
        
        $this->info("Generating $count mock products...");
        
        // Run the seeder
        $seeder = new ProductSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Mock products generated successfully!');
        
        return 0;
    }
}
