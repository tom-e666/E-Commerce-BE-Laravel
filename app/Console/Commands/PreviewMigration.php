<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PreviewMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:preview {--path= : The path to the migrations files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Preview migration SQL statements without executing them';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Previewing migration SQL:');
        $this->newLine();
        
        $options = ['--pretend' => true];
        
        if ($path = $this->option('path')) {
            $options['--path'] = $path;
        }
        
        $this->info('--- SQL that would be executed ---');
        $this->newLine();
        
        // Capture output from migrate command
        $output = '';
        Artisan::call('migrate', $options, $output);
        
        // Display the SQL statements
        $this->line($output);
        
        $this->newLine();
        $this->info('--- End of preview ---');
        $this->newLine();
        $this->info('No database changes have been made.');
        
        return Command::SUCCESS;
    }
}
