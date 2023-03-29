<?php

namespace Services;

use App\Repositories\NoteRepository;
use Illuminate\Support\Facades\Log;

class NoteService
{
    /**
     * @var NoteRepository
     */
    private $noteRepository;

    public function __construct(
        NoteRepository $noteRepository
    ) {
        $this->noteRepository = $noteRepository;
    }

    public function getNotes($source)
    {
        $notes = $this->noteRepository->getAll($source);
        $list = array();
        for ($i = 0; $i < count($notes); $i++) {
            $checked = $notes[$i]['is_checked'];
            $checkedString = $checked ? "✅ " : "";
            array_push($list, $i + 1 . ". " . $checkedString . "{$notes[$i]['content']}");
        }
        return (array) $list;
    }

    public function deleteByOrderNumbers($numbers, string $source)
    {
        $list = array();
        $temp = 0;
        $smallest = $numbers[0];
        foreach ($numbers as $value) {
            $final = $value;
            if ($value > $smallest) {
                $final = $value - $temp;
            } elseif ($value < $smallest) {
                $smallest = $value;
            }
            $temp++;
            array_push($list, $final);
        }

        Log::debug("list: ", $numbers);

        for ($i = 0; $i < count($list); $i++) {
            $this->noteRepository->delete($list[$i], $source);
        }
    }

    public function check($numbers, string $source)
    {
        foreach ($numbers as $value) {
            $this->noteRepository->check($value, $source);
        }
    }
}
