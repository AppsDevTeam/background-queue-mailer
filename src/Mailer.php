<?php

namespace ADT\BackgroundQueueMailer;

use ADT\BackgroundQueue\BackgroundQueue;
use Nette\Mail\IMailer;
use Nette\Mail\Message;
use Nette\Mail\SendException;
use Nette\Utils\Json;
use Tracy\Debugger;

class BackgroundQueueMailer implements IMailer
{
	protected IMailer $next;

	protected string $callbackName;

	protected BackgroundQueue $backgroundQueue;

	public function __construct(
		IMailer $next,
		string $callbackName,
		BackgroundQueue $backgroundQueue
	) {
		$this->next = $next;
		$this->callbackName = $callbackName;
		$this->backgroundQueue = $backgroundQueueService;
	}

	public function send(Message $mail): void
	{
		$this->backgroundQueue->publish(
			$this->callbackName,
			// Parameters are stored as LONGTEXT UTF-8, so they cannot contain binary data.
			// This should be fine if we encode mail as base64.
			['message' => base64_encode(serialize($mail))],
		);
	}

	public function process(array $parameters) 
	{
		/** @var Message $mail */
		$mail = unserialize(base64_decode($parameters['message']));
		
		$this->next->send($mail);
	}
}
