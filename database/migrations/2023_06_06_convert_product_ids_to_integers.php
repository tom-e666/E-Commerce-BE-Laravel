<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ConvertProductIdsToIntegers extends Migration
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up()
    {
        // Get MongoDB collection
        $collection = DB::connection('mongodb')->collection('product_details');
        
        // Find all documents with string product_id
        $documents = $collection->get();
        
        foreach ($documents as $doc) {
            if (isset($doc['product_id']) && is_string($doc['product_id'])) {
                // Update to integer type
                $collection->where('_id', $doc['_id'])->update([
                    'product_id' => (int) $doc['product_id']
                ]);
            }
        }
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        // No need to reverse as it would lose fidelity
    }
}
