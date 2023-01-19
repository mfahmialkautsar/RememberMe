<?php

namespace Services;

use App\Repositories\NoteRepository;

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
            array_push($list, $i + 1 . ". {$notes[$i]['content']}");
        }
        return (array) $list;
    }

    public function deleteByOrderNumbers($numbers, string $source)
    {
        $deletes = array();
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
            array_push($deletes, $final);
        }

        for ($i = 0; $i < count($deletes); $i++) {
            $this->noteRepository->delete($deletes[$i], $source);
        }
    }
}
