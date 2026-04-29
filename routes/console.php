<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:fix-worker-categories', function () {
    $this->info('Starting worker category fix...');
    
    // 1. Resolve correct category
    $category = \App\Models\ExpenseCategory::where('name', 'like', '%TRABAJADOR%')->first();
    if (!$category) {
        $this->error('Worker category not found by name "TRABAJADOR".');
        return;
    }
    
    $correctId = $category->id;
    $this->info("Correct category ID found: {$correctId} ({$category->name})");

    // 2. Identify potential miscategorized expenses
    // Those containing worker names but with different category_id
    $workers = ['BREINER', 'ANDRES', 'JAIR'];
    $count = 0;

    foreach ($workers as $worker) {
        $misplaced = \App\Models\Expense::where('description', 'like', "%{$worker}%")
            ->where('category_id', '!=', $correctId)
            ->get();

        foreach ($misplaced as $expense) {
            $oldId = $expense->category_id;
            $expense->category_id = $correctId;
            $expense->save();
            $this->line("Fixed expense #{$expense->id}: [{$expense->description}] Moved from ID {$oldId} to {$correctId}");
            $count++;
        }
    }

    $this->info("Task completed. Total expenses fixed: {$count}");
})->purpose('Fix miscategorized worker expenses based on description');
