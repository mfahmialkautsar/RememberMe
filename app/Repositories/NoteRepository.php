<?php

namespace App\Repositories;

use App\Models\Note;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        return Note::where('source', $source)->orderBy('id')->get();
    }

    function delete(int $num, string $source)
    {
        $action = "DELETE FROM notes";
        $this->doSomethingToNote($num, $source, $action);
    }

    function check(int $num, string $source)
    {
        $action = "UPDATE notes
        SET is_checked = NOT is_checked";
        $this->doSomethingToNote($num, $source, $action);
    }

    private function doSomethingToNote(int $num, string $source, string $action)
    {
        DB::select("WITH temp AS
        (
        SELECT *, ROW_NUMBER() OVER(ORDER BY id) AS number
        FROM notes
        WHERE source = ?
        ), temp2 AS (
        SELECT *
        FROM temp
        WHERE number = ?
        )
        $action
        WHERE id IN (SELECT id FROM temp2) AND source = ?;
        ", [$source, $num, $source]);
    }
}
