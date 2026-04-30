<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncItemData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cch:sync-item-data';

    protected $description = 'Sync item_data from sqlsrv connection to local database';

    public function handle()
    {
        $this->info('Starting sync for item_data from sqlsrv...');

        try {
            \App\Models\ItemData::truncate();

            \Illuminate\Support\Facades\DB::connection('erp')
                ->table('soi107.dbo.item_data')
                ->select([
                    'item',
                    'description',
                    'item_group',
                    'group_desc',
                    'desc-2',
                    'old_partno',
                    'unit',
                    'div_code',
                    'divisi',
                    'customer',
                    'customer_desc',
                    'model',
                    'unique code',
                    'classification',
                    'destination'
                ])
                ->orderBy('item') // chunking requires an order by
                ->chunk(1000, function ($items) {
                    $insertData = [];
                    foreach ($items as $item) {
                        $insertData[] = [
                            'item' => $item->item,
                            'description' => $item->description,
                            'item_group' => $item->item_group,
                            'group_desc' => $item->group_desc,
                            'desc_2' => $item->{'desc-2'},
                            'old_partno' => $item->old_partno,
                            'unit' => $item->unit,
                            'div_code' => $item->div_code,
                            'divisi' => $item->divisi,
                            'customer' => $item->customer,
                            'customer_desc' => $item->customer_desc,
                            'model' => $item->model,
                            'unique_code' => $item->{'unique code'},
                            'classification' => $item->classification,
                            'destination' => $item->destination,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    \App\Models\ItemData::insert($insertData);
                });

            $this->info('Sync completed successfully.');

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
        }
    }
}
