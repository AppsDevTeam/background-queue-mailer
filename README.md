# Background Queue Mailer

Delegates sending emails to [adt/background-queue](https://github.com/AppsDevTeam/background-queue).

## Installation
```
composer require adt/background-queue-mailer
```

## Usage

To use BackgroundQueueMailer as buffer between your application and SmtpMailer, register
it in your `config.neon`:

```neon
services:
	smtpMailer:
		class: \Nette\Mail\SmtpMailer
		autowired: no # this is important

	nette.mailer: \ADT\Mail\BackgroundQueueMailer\Mailer(@smtpMailer, 'backgroundMail')

backgroundQueue:
	callbacks:
		backgroundMail: @nette.mailer::process
``` 

where `@smtpMailer` is outgoing mailer, and `backgroundMail` is unique callback name.

Callback name has to be same in both mailer definition and BackgroundQueue callback list. If they
are not, warning is logged using Tracy. This should get resolved [here](https://github.com/AppsDevTeam/BackgroundQueue/issues/8).

The `autowired: no` option is important because Nette DI container would not know
which `\Nette\Mail\IMailer` to inject in your application. By setting `autowired: no` on
SMTP mailer only one instance of `IMailer` interface remains.

You cannot set `autowired: no` on `nette.mailer` because your application
would not be able to inject it.

It is also important that you autowire `\Nette\Mail\IMailer` throughout your application.
