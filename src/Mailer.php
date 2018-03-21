<?php

namespace ADT\Mail\BackgroundQueueMailer;

use ADT\BackgroundQueue;
use Nette\Mail;
use Nette\Utils\Json;
use Tracy\Debugger;


class Mailer extends \Nette\Object implements Mail\IMailer {

	/** @var Mail\IMailer */
	protected $next;

	/** @var string */
	protected $callbackName;

	/** @var BackgroundQueue\Service */
	protected $backgroundQueueService;

	public function __construct(
		Mail\IMailer $next,
		$callbackName,
		BackgroundQueue\Service $backgroundQueueService
	) {
		$this->next = $next;
		$this->callbackName = $callbackName;
		$this->backgroundQueueService = $backgroundQueueService;
	}

	public function send(Mail\Message $mail) {
		$entity = new BackgroundQueue\Entity\QueueEntity;
		$entity->setCallbackName($this->callbackName);
		$entity->setParameters([
			// Parameters are stored as LONGTEXT UTF-8, so they cannot contain binary data.
			// This should be fine if we encode mail as base64.
			'mail' => base64_encode(serialize($mail)),
		]);

		$this->backgroundQueueService
			->publish($entity);

		return $entity;
	}

	public function process(BackgroundQueue\Entity\QueueEntity $entity) {
		if ($entity->getCallbackName() !== $this->callbackName) {
			Debugger::log("Callback names do not match, expected: '{$this->callbackName}' but got: '{$entity->getCallbackName()}'; skipping'", Debugger::WARNING);
			return FALSE; // repeatable error
		}

		$parameters = $entity->getParameters();
		$mailData = $parameters['mail'];

		$mailDataDecoded = base64_decode($mailData);

		// DEPRECATED: This block of code will be deleted in future.
		// $mailData can also have JSON format (old deprecated format)
		if ($mailDataDecoded === FALSE) {
			// JSON (old deprecated format)
			$mailDataDecoded = Json::decode($mailData);
		}

		$mail = unserialize($mailDataDecoded);

		try {
			$this->next->send($mail);
			return TRUE; // done
		} catch (Mail\SendException $e) {
			return FALSE; // repeatable error
		}

		// everything else is unrepeatable error (logged in BackgroundQueue)
	}

}
