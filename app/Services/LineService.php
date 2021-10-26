<?php

namespace Services;

use App\Repositories\UserRepository;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;

class LineService
{
  /**
   * @var LINEBot
   */
  private $bot;
  /**
   * @var UserRepository
   */
  private $userRepository;

  public function __construct(UserRepository $userRepository)
  {
    $this->userRepository = $userRepository;
  }

  public function setBot($bot)
  {
    $this->bot = $bot;
  }

  public function getProfile($userId)
  {
    $res = $this->bot->getProfile($userId);
    $profile = $res->getJSONDecodedBody();
    if ($res->isSucceeded()) {
      $this->userRepository->updateUser($profile['userId'], null, $profile['displayName'], $profile['pictureUrl'] ?? null);
      return $profile;
    }
  }

  public function getWelcomeMessage($message)
  {
    $introduction = "I'm a bot that will help you remember your To-Do List so you'll never forget that.";
    $stickerMessageBuilder = new StickerMessageBuilder(11538, 51626494);

    // prepare help button
    $helpButton[] = new MessageTemplateActionBuilder('How To Use', '.help');

    // prepare button template
    $buttonTemplate = new ButtonTemplateBuilder(null, $introduction, null, $helpButton);

    // build message
    $haloMessage = new TextMessageBuilder($message);
    $introductionMessage = new TemplateMessageBuilder($message, $buttonTemplate);

    // merge all messages
    $multiMessageBuilder = new MultiMessageBuilder();
    $multiMessageBuilder->add($haloMessage)->add($stickerMessageBuilder)->add($introductionMessage);
    return $multiMessageBuilder;
  }

  public function getSourceId($source)
  {
    $sourceType = $source['type'];
    switch ($sourceType) {
      case 'user':
        return $source['userId'];
      case 'room':
      case 'group':
        return $source[$sourceType . 'Id'];
      default:
        return;
    }
  }
}
