<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Log;
use App\Services\ProductImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('products:seed')]
#[Description('Import smartphone products from DummyJSON API')]
class SeedProductsCommand extends Command
{
    public function handle(ProductImportService $importService): int
    {
        $this->info('Importing smartphone products from DummyJSON API...');

        try {
            $importService->importProductsFromApi();
            $this->info('Products imported successfully!');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error($e);
            $this->error('Error importing products: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
