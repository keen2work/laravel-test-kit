<?php

namespace EMedia\TestKit\Traits;

/*
|--------------------------------------------------------------------------
| MailTracking Tester
|--------------------------------------------------------------------------
|
| Adapted from https://gist.github.com/JeffreyWay/b501c53d958b07b8a332
|
*/

use EMedia\TestKit\Mail\TestingMailEventListener;

trait MailTracking
{

	/**
	 *
	 * Returns sent emails using Symfony transport
	 *
	 * @return mixed
	 */
	protected function getSentEmails()
	{
		return $this->app->make('mailer')->getSymfonyTransport()->messages();
	}

	/**
	 * Assert that no emails were sent.
	 */
	protected function dontSeeEmailWasSent(): self
	{
		$this->assertEmpty(
			$this->getSentEmails(),
			'Did not expect any emails to have been sent.'
		);

		return $this;
	}

	/**
	 * Assert that the given number of emails were sent.
	 *
	 * @param integer $count
	 *
	 * @return MailTracking
	 */
	protected function seeEmailCount(int $count): self
	{
		$emailsSent = $this->getSentEmails();
		$sentCount = count($emailsSent);

		$this->assertCount(
			$count,
			$emailsSent,
			"Expected $count emails to have been sent, but $sentCount were."
		);

		return $this;
	}

	/**
	 * Assert that at least one email was sent.
	 */
	protected function seeEmailWasSent(): self
	{
		$this->assertNotEmpty(
			$this->getSentEmails(),
			'No emails have been sent.'
		);

		return $this;
	}

	/**
	 * Retrieve the mostly recently sent swift message.
	 */
	protected function lastEmail()
	{
		$emailsSent = $this->getSentEmails();

		return $emailsSent->last();
	}

	/**
	 * Assert that the last email's body contains the given text.
	 *
	 */
	protected function seeLastEmailSentTo(string|array $recipientEmails): self
	{
		// if $recipientEmails is a string, convert it to an array
		if (is_string($recipientEmails)) {
			$recipientEmails = [$recipientEmails];
		}

		$lastEmail = $this->lastEmail();
		$this->assertNotNull($lastEmail, 'No emails have been sent.');

		$to = $lastEmail->getEnvelope()->getRecipients();

		$recipients = [];
		foreach ($to as $address) {
			$recipients[$address->getAddress()] = $address->getName();
		}

		foreach ($recipientEmails as $recipientEmail) {
			$this->assertArrayHasKey(
				$recipientEmail,
				$recipients,
				"No email was sent to $recipientEmail."
			);
		}

		return $this;
	}

	/**
	 * @param string|array $recipientEmails
	 *
	 * @return MailTracking
	 */
	protected function dontSeeLastEmailSentTo(string|array $recipientEmails): self
	{
		// if $recipientEmails is a string, convert it to an array
		if (is_string($recipientEmails)) {
			$recipientEmails = [$recipientEmails];
		}

		$lastEmail = $this->lastEmail();
		$this->assertNotNull($lastEmail, 'No emails have been sent.');

		$to = $lastEmail->getEnvelope()->getRecipients();

		$recipients = [];
		foreach ($to as $address) {
			$recipients[$address->getAddress()] = $address->getName();
		}

		foreach ($recipientEmails as $recipientEmail) {
			$this->assertArrayNotHasKey(
				$recipientEmail,
				$recipients,
				"Email was sent to $recipientEmail. But it should not have been sent."
			);
		}

		return $this;
	}

	/**
	 * Assert that the last email's subject matches the given string.
	 *
	 * @param string $subject
	 *
	 * @return MailTracking
	 */
	protected function seeLastEmailSubject(string $subject): self
	{
		$this->assertEquals(
			$subject,
			$this->lastEmail()->getOriginalMessage()->getSubject(),
			"No email with a subject of $subject was found."
		);
		return $this;
	}

	/**
	 * Assert that the last email's body contains the given text.
	 *
	 * @param string $excerpt
	 *
	 * @return MailTracking
	 */
	protected function seeLastEmailContains(string $excerpt): self
	{

		$this->assertStringContainsString(
			$excerpt,
			$this->lastEmail()->getOriginalMessage()->getBody()->toString(),
			"The text `$excerpt` was not found in the last email."
		);

		return $this;
	}

	/**
	 * Assert that the last email's body does not contain the given text.
	 *
	 * @param string $excerpt
	 *
	 * @return MailTracking
	 */
	protected function dontSeeLastEmailContains(string $excerpt): self
	{

		$this->assertStringNotContainsString(
			$excerpt,
			$this->lastEmail()->getOriginalMessage()->getBody()->toString(),
			"The text `$excerpt` was found in the last email."
		);

		return $this;
	}
}
