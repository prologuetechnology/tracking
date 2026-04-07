<?php

namespace App\Logging;

use App\Models\Log;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class DatabaseLogger extends AbstractProcessingHandler
{
    protected function write(array|LogRecord $record): void
    {
        Log::create([
            'level' => $record['level_name'],
            'message' => $record['message'],
            'context' => json_encode($record['context']),
        ]);
    }
}
