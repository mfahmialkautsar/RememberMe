<?php

namespace App\Repositories;

use App\Models\EventLog;
use Illuminate\Database\ConnectionInterface;

class EventLogRepository
{
    /**
     * @var ConnectionInterface
     */
    private $db;

    public function __construct()
    {
        $this->db = app('db');
    }

    public function saveLog(string $signature, string $body)
    {
        EventLog::create([
            'signature' => $signature,
            'events' => $body
        ]);
    }
}
