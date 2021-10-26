<?php

namespace App\Repositories;

use App\Models\Note;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class NoteRepository
{
    /**
     * @var ConnectionInterface
     */
    private $db;

    public function __construct()
    {
        $this->db = app('db');
    }

    /**
     * Reverse the migration
     * 
     * @return void
     */
    public function truncate(string $source)
    {
        Note::where('source', $source)->delete();
    }

    public function count(string $source)
    {
        return Note::where('source', $source)->count();
    }

    public function save($note, $profileId, $sourceId)
    {
        Note::create([
            'content' => $note,
            'owner' => $profileId,
            'source' => $sourceId
        ]);
    }

    function getAll(string $source)
    {
        return Note::where('source', $source)->get();
    }

    function delete(int $num, string $source)
    {
        $this->getRowNumber($num, $source);
    }

    private function getRowNumber($num, string $source)
    {
        $user = DB::select("WITH temp AS
        (
        SELECT *, ROW_NUMBER() OVER(ORDER BY id) AS number
        FROM notes
        WHERE source = ?
        ), temp2 AS (
        SELECT *
        FROM temp
        WHERE number = ?
        )
        DELETE FROM notes
        WHERE id IN (SELECT id FROM temp2) AND source = ?;
        ", [$source, $num, $source]);
        return $user;
    }
}
