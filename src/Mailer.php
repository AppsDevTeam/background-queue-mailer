<?php

namespace ADT\Mail\BackgroundQueueMailer;

use ADT\BackgroundQueue;
use Nette\Mail;
use Tracy\Debugger;


class Mailer extends \Nette\Object implements Mail\IMailer {

	/** @var Mail\IMailer */
	protected $next;

	/** @var BackgroundQueue\Service */
	protected $backgroundQueueService;

	/** @var string */
	protected $callbackName;

	public function __construct(
		Mail\IMailer $next,
		BackgroundQueue\Service $backgroundQueueService,
		$callbackName
	) {
		$this->next = $next;
		$this->backgroundQueueService = $backgroundQueueService;
		$this->callbackName = $callbackName;
	}

	public function send(Mail\Message $mail) {
		$entity = new BackgroundQueue\Entity\QueueEntity;
		$entity->setCallbackName($this->callbackName);
		$entity->setParameters([
			// Parameters are stored as LONGTEXT, so they cannot contain binary data.
			// This should be fine if we encode mail as JSON.
			'mail' => json_encode(serialize($mail)),
		]);

		$this->backgroundQueueService
			->publish($entity);
	}

	public function process(BackgroundQueue\Entity\QueueEntity $entity) {
		if ($entity->getCallbackName() !== $this->callbackName) {
			Debugger::log("Callback names do not match, expected: '{$this->callbackName}' but got: '{$entity->getCallbackName()}'; skipping'", Debugger::WARNING);
			return FALSE; // repeatable error
		}

		$mail = unserialize(json_decode($entity->getParameters()['mail']));

		try {
			$this->next->send($mail);
			return TRUE; // done
		} catch (Mail\SendException $e) {
			return FALSE; // repeatable error
		}

		// everything else is unrepeatable error (logged in BackgroundQueue)
	}

}