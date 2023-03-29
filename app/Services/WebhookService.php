<?php

namespace Services;

use App\Repositories\NoteRepository;
use App\Repositories\UserRepository;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class WebhookService
{
    /**
     * @var LINEBot
     */
    private $bot;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var NoteRepository
     */
    private $noteRepository;
    /**
     * @var UtilityService
     */
    private $utilityService;
    /**
     * @var NoteService
     */
    private $noteService;
    /**
     * @var LineService
     */
    private $lineService;

    public function __construct(
        UserRepository $userRepository,
        NoteRepository $noteRepository,
        UtilityService $utilityService,
        NoteService $noteService,
        LineService $lineService
    ) {
        $this->userRepository = $userRepository;
        $this->noteRepository = $noteRepository;
        $this->utilityService = $utilityService;
        $this->noteService = $noteService;
        $this->lineService = $lineService;
    }

    public function setBot($bot)
    {
        $this->bot = $bot;
        $this->lineService->setBot($bot);
    }

    public function handleEvents($data)
    {
        if (is_array($data['events'])) {
            foreach ($data['events'] as $event) {
                $source = $event['source'];
                $sourceType = $source['type'];
                switch ($sourceType) {
                    case 'room':
                    case 'group':
                        $this->userRepository->saveUser(
                            $source[$sourceType . 'Id'],
                            $sourceType
                        );
                        break;
                    case 'user':
                        $this->userRepository->saveUser(
                            $source['userId'],
                            $sourceType
                        );
                        break;
                    default:
                        break;
                }

                // respond event
                if (method_exists($this, $event['type'] . 'Event')) {
                    $this->{$event['type'] . 'Event'}($event);
                }
            }
        }
    }

    private function followEvent($event)
    {
        $profile = $this->lineService->getProfile($event['source']['userId']);
        if ($profile) {

            // save user data
            $this->userRepository->updateUser(
                $profile['userId'],
                true
            );

            // create welcome message
            $message = "Hello, {$profile['displayName']}!";
            $messageBuilder = $this->lineService->getWelcomeMessage($message);

            // send reply message
            return $this->bot->replyMessage($event['replyToken'], $messageBuilder);
        }

        return $this->bot->replyMessage($event['replyToken'], $this->lineService->getWelcomeMessage('Hello!'));
    }

    private function unfollowEvent($event)
    {
        $this->lineService->getProfile($event['source']['userId']);
        $this->userRepository->updateUser($event['source']['userId'], false);
    }

    private function joinEvent($event)
    {
        $sourceId = $this->lineService->getSourceId($event['source']);
        $this->userRepository->updateUser(
            $sourceId,
            true
        );

        // create welcome message
        $message = 'Hello, Everyone!';

        // send reply message
        $this->bot->replyMessage($event['replyToken'], $this->lineService->getWelcomeMessage($message));
    }

    private function leaveEvent($event)
    {
        $this->userRepository->updateUser($event['source'][$event['source']['type'] . 'Id'], false);
    }

    private function messageEvent($event)
    {
        // set default (fallback) message
        $message = "Oops, there's something wrong.";

        $textHowToUse = 'How To Use:';
        $textAdd = '‣ .add [your note]: Save note';
        $textDelete = '‣ .del [note number]: Delete note';
        $helpDeleteInfo = '^You can delete multiple notes, e.g. ".del 2 1 3" will delete notes no. 2, 1, and 3.';
        $helpShow = '‣ .show: Show notes list';
        $helpShowHelp = '‣ .help: Show Help';
        $helpClear = '‣ #~clear: Clear all Notes';
        $helpPS = "*The Notes saved in this To-Do List will be different for each private chat, multichat, and group chat. So, you can create your personal To-Do List and To-Do List for team. {$this->utilityService->getEmoji('10008A')}";
        $helpMessage = "{$textHowToUse}\n{$textAdd}\n{$textDelete}\n{$helpDeleteInfo}\n{$helpShow}\n{$helpShowHelp}\n\n{$helpClear}\n\n{$helpPS}";
        $additionalMessage = null;

        $text = $event['message']['text'];
        $args = explode(' ', trim($text));
        $command = $args[0];
        $strArgs = null;

        // create the right words
        if (isset($args[1])) {
            array_splice($args, 0, 1);
            $strArgs = implode(' ', $args);
        }

        $sourceType = $event['source']['type'];
        $profile = $this->lineService->getProfile($event['source']['userId']);

        if ($profile) {
            $sourceId = $this->lineService->getSourceId($event['source']);

            // if bot is asked to leave
            if (strtolower($text) == 'bot leave') {
                if ($sourceType == 'group' || $sourceType == 'room') {
                    $message = "bye guys {$this->utilityService->getEmoji('10007C')}";
                    $textMessageBuilder = new TextMessageBuilder($message);
                    $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);
                    return $this->bot->{'leave' . ucfirst($sourceType)}($sourceId);
                }
            }

            switch (strtolower($command)) {
                case '.add':
                case '.a':
                    if ($strArgs) {
                        $this->noteRepository->save($strArgs, $profile['userId'], $sourceId);
                        $message = "Note Saved {$this->utilityService->getEmoji('100041')}";
                    } else {
                        $message = "What note do wanna add?\nType \".add [your note]\"";
                    }
                    break;
                case '.del':
                case '.d';
                    if ($strArgs) {
                        $deleteCount = count($args);
                        $newArgs = array();
                        $isPassed = false;

                        // check input
                        for ($i = 0; $i < $deleteCount; $i++) {
                            $number = $args[$i];

                            // make sure the input is integer only
                            if (!is_numeric($number)) {
                                $isPassed = false;
                                break;
                            }

                            $number = (int) $number;

                            if ($number > $this->noteRepository->count($sourceId) || $number <= 0) {
                                $isPassed = false;
                                $message = "Oops, there's no note number {$number}";
                                break;
                            }

                            // check if there's same input
                            if (in_array($number, $newArgs)) {
                                $message = "Oops, you can't delete the same numbers";
                                $isPassed = false;
                                break;
                            }

                            array_push($newArgs, $number);
                            $isPassed = true;
                        }

                        // delete note
                        if ($isPassed) {

                            // delete item based on array
                            $this->noteService->deleteByOrderNumbers($args, $sourceId);

                            // set reply
                            $message = "Note Deleted {$this->utilityService->getEmoji('10008F')}";
                        }
                    } else {
                        $message = "What note do you wanna delete?\nType \".del [note number]\"";
                    }
                    break;
                case '.check':
                case '.c':
                    if ($strArgs) {
                        $checkCount = count($args);
                        $newArgs = array();
                        $isPassed = false;

                        // check input
                        for ($i = 0; $i < $checkCount; $i++) {
                            $number = $args[$i];

                            // make sure the input is integer only
                            if (!is_numeric($number)) {
                                $isPassed = false;
                                break;
                            }

                            $number = (int) $number;

                            if ($number > $this->noteRepository->count($sourceId) || $number <= 0) {
                                $isPassed = false;
                                $message = "Oops, there's no note number {$number}";
                                break;
                            }

                            // check if there's same input
                            if (in_array($number, $newArgs)) {
                                $message = "Oops, you can't check the same numbers";
                                $isPassed = false;
                                break;
                            }

                            array_push($newArgs, $number);
                            $isPassed = true;
                        }

                        // check note
                        if ($isPassed) {

                            // check item based on array
                            $this->noteService->check($args, $sourceId);

                            // set reply
                            $message = "Success {$this->utilityService->getEmoji('100033')}";
                        }
                    } else {
                        $message = "What note do you wanna check?\nType \".check [note number]\"";
                    }
                    break;
                case '.show':
                case '.s':
                    $notes = $this->noteService->getNotes($sourceId);
                    if (count($notes) > 0) {
                        array_unshift($notes, "{$this->utilityService->getEmoji('10006C')} To-Do List:");
                        $message = implode("\n", $notes);
                    } else {
                        $message = 'Yeay. Nothing you have to do.';
                    }
                    if ($strArgs) {
                        $additionalMessage = new TextMessageBuilder("Just type \".show\" to see your To-Do List.");
                    }
                    break;
                case '.help':
                case '.h':
                    $message = $helpMessage;
                    if ($strArgs) {
                        $additionalMessage = new TextMessageBuilder("Just type \".help\" for help.");
                    }
                    break;

                case '#~clearall':
                    if (!$strArgs) {
                        $this->noteRepository->truncate($sourceId);
                        $message = "All notes cleared {$this->utilityService->getEmoji('1000AE')}";
                    }
                    break;
                default:
                    if ($sourceType == 'user') {
                        $message = "Sorry, mate. I don't understand. {$this->utilityService->getEmoji('100084')} \nType \".help\" for help.";
                        break;
                    } else {
                        return;
                    }
            }
        } else {
            $mustFollowMessage = "Hi, add me as friend first. So I can help you remember your To-Do List {$this->utilityService->getEmoji('10007A')}";
            switch (strtolower($command)) {
                case '.add':
                case '.del':
                case '.show':
                case '.help':
                    $message = $mustFollowMessage;
                    break;
                default:
                    return;
            }
        }

        // send response message
        $multiMessageBuilder = new MultiMessageBuilder();
        if ($additionalMessage) $multiMessageBuilder->add($additionalMessage);
        $textMessageBuilder = new TextMessageBuilder($message);
        $multiMessageBuilder->add($textMessageBuilder);
        return $this->bot->replyMessage($event['replyToken'], $multiMessageBuilder);
    }
}
