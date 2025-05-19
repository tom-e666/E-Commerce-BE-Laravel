<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestSeeder extends Command
{
    protected $signature = 'db:test-seed {seeder : The class name of the seeder}';
    protected $description = 'Run a seeder inside a transaction and roll it back afterwards';

    public function handle()
    {
        $seederClass = $this->argument('seeder');
        
        // Check if seeder class exists
        if (!class_exists($seederClass)) {
            $seederClass = "Database\\Seeders\\" . $seederClass;
            if (!class_exists($seederClass)) {
                $this->error("Seeder class {$seederClass} not found.");
                return 1;
            }
        }
        
        $this->info("Testing seeder: {$seederClass}");
        
        DB::beginTransaction();
        try {
            $startCount = $this->getTableCounts();
            $this->info("Tables before seeding: " . json_encode($startCount));
            
            // Run the seeder
            $seeder = new $seederClass();
            $seeder->run();
            
            $endCount = $this->getTableCounts();
            $this->info("Tables after seeding: " . json_encode($endCount));
            
            // Show changes
            $diff = [];
            foreach ($endCount as $table => $count) {
                $diffCount = $count - ($startCount[$table] ?? 0);
                if ($diffCount > 0) {
                    $diff[$table] = "+{$diffCount}";
                }
            }
            
            $this->info("Changes: " . json_encode($diff));
            
            // Ask for confirmation
            if ($this->confirm('Do you want to keep these changes?', false)) {
                DB::commit();
                $this->info("Changes committed to database.");
            } else {
                DB::rollBack();
                $this->info("Changes rolled back.");
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error running seeder: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function getTableCounts()
    {
        $tables = DB::select("SHOW TABLES");
        $counts = [];
        
        foreach ($tables as $table) {
            $tableName = reset($table);
            $count = DB::table($tableName)->count();
            $counts[$tableName] = $count;
        }
        
        return $counts;
    }
}