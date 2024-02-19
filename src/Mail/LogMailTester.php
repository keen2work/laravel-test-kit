<?php

namespace EMedia\TestKit\Mail;

use Symfony\Component\Mime\Header\Headers;

class LogMailTester
{

	protected $mailData = [];

	public static function getLogContents()
	{
		// Read the Laravel log file
		$logFile = storage_path('logs/laravel.log');

		return file_get_contents($logFile);
	}

	public static function getLogContentLines()
	{
		// split content into lines
		return explode(PHP_EOL, self::getLogContents());
	}

	public static function getEmailsFromLog()
	{
		// Regex pattern for email log entry start
		$logEntryPattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): From:(.*)/';

		$inHeaderLoop = false;
		$headerLines = [];

		$inBodyLoop = false;
		$inBodyHeader = false;
		$inBodyContent = false;
		$bodyLines = [];

		$contentType = null;
		$bodyType = 'UnknownType';

		$boundary = null;

		$email = [];
		$emails = [];

		// parse all lines
		$lines = self::getLogContentLines();

		foreach ($lines as $line) {
			if (preg_match($logEntryPattern, $line, $matches)) {
				// if there's unprocessed email data, then process and clear it
				if (!empty($headerLines) || !empty($bodyLines)) {
					$email['headers'] = $headerLines;
					$email['body'][$bodyType]['content'] = $bodyLines;
					$emails[] = $email;

					$contentType = null;
					$bodyType = 'UnknownType';
					$headerLines = [];
					$bodyLines = [];
				}

				// get date, env, type
				$email['date'] = \Carbon\Carbon::parse($matches[1]);
				$email['env'] = $matches[2];
				$email['type'] = $matches[3];

				$inHeaderLoop = true;
			}

			// process headers of the email
			if ($inHeaderLoop) {
				// if this is the first line, then it's the `From` header
				// so we need to strip everything before `From:`
				if (str_contains($line, 'From:')) {
					$line = preg_replace('/^.*From:/', 'From:', $line);
				}

				$headerLines[] = $line;

				if (preg_match('/Content-Type: (.*);/', $line, $matches)) {
					switch ($matches[1]) {
						case 'text/plain':
							$contentType = $matches[1];
							// if the content type is `text/plain`, there won't be a boundary
							break;
						case 'text/html':
						case 'multipart/alternative':
						default:
						// check if `boundary` param is present, and if so, extract it
							if (preg_match('/Content-Type: (.*); boundary=(.*)/', $line, $matches)) {
								$boundary = $matches[2];
							}
					}
				}

				// detect end of headers.
				// if we get an empty line, then we are done with the headers
				if (trim($line) === '') {
					$inHeaderLoop = false;
					$inBodyLoop = true;
				}
			}

			// process boundaries
			if ($boundary) {
				// check if this is the start of the email body
				if (preg_match('/^--' . $boundary . '$/', $line)) {
					$inBodyHeader = true;
					// peek the next line to see if it's a `Content-Type` header
					// $nextLine = $lines[key($lines) + 1];
				}
			}

			// process content of the email
			if ($inBodyLoop) {
				$bodyLines[] = $line;

				if ($boundary) {
					// check if this is the start of the email body
					if (preg_match('/^--' . $boundary . '$/', $line)) {
						$inBodyHeader = true;
					}

					if ($inBodyHeader) {
						// parse body header types
						if (str_contains($line, ':')) {
							$headerParts = explode(':', $line, 2);
							$headerName = trim($headerParts[0]);
							$headerValue = trim($headerParts[1]);

							// if we don't have a content type yet, then check if this is a `Content-Type` header
							if (empty($contentType)) {
								switch ($headerName) {
									case 'Content-Type':
										$bodyType = $headerValue;
										break;
								}
							} else {
								$bodyType = $contentType;
							}

							$email['body'][$bodyType]['headers'][] = $line;
						}

						// check if this is the end of the email body header
						if (trim($line) === '') {
							$inBodyHeader = false;
							$inBodyContent = true;
						}
					}
				} else {
					// if there's no boundary, then we can assume that the content type is `text/plain`
					$bodyType = $contentType;
					$inBodyHeader = false;
					$inBodyContent = true;
				}

				if ($inBodyContent) {
					if (empty($bodyType) && !empty($contentType)) {
						$bodyType = $contentType;
					}
					$email['body'][$bodyType]['content'][] = $line;
				}

				// check if we are at the end of the email
				if (preg_match('/--' . $boundary . '--/', $line)) {
					$inBodyLoop = false;

					// this is the end of the email, so we can process it
					$email['headers'] = $headerLines;
					$email['body'][$bodyType]['content'] = $bodyLines;
					$emails[] = $email;

					$email = [];
					$headerLines = [];
					$bodyLines = [];
				}
			}
		}

		// if there's any leftover email data, then process it
		// this can happen if the last line of the log file is not a new email
		if (!empty($headerLines) || !empty($bodyLines)) {
			$email['headers'] = $headerLines;
			$email['body'][$bodyType]['content'] = $bodyLines;
			$emails[] = $email;
		}

		// dd($emails);

		// process all emails
		$emailMessages = collect();
		foreach ($emails as $email) {
			$headers = new Headers();
			foreach ($email['headers'] as $headerLine) {
				if (str_contains($headerLine, ':')) {
					$headerParts = explode(':', $headerLine, 2);
					$headerName = trim($headerParts[0]);
					$headerValue = trim($headerParts[1]);

					switch ($headerName) {
						case 'Date':
							// dd($headerValue);
							$header = new \Symfony\Component\Mime\Header\DateHeader(
								$headerName,
								\Carbon\Carbon::parse($headerValue)
							);
							$headers->add($header);
							break;
						case 'From':
						case 'To':
							$addresses = explode(',', $headerValue);
							$header = new \Symfony\Component\Mime\Header\MailboxListHeader($headerName, []);
							foreach ($addresses as $address) {
								$address = \Symfony\Component\Mime\Address::create(trim($address));
								$header->addAddress($address);
							}
							$headers->add($header);
							break;
						case 'Message-ID':
							// Don't reuse the same ID, as this may cause an RFC exception with test domains.
							// $header = new \Symfony\Component\Mime\Header\IdentificationHeader($headerName,
							//      $headerValue);
							// $headers->add($header);
							break;
						default:
							$headers->addTextHeader($headerName, $headerValue);
							break;
					}
				}
			}

			// Create the email message
			$emailMessage = (new \Symfony\Component\Mime\Email())
				->setHeaders($headers);

			foreach ($email['body'] as $bodyType => $bodyContent) {
				if (str_contains($bodyType, 'text/plain')) {
					$emailMessage->text(implode(PHP_EOL, $bodyContent['content']));
				} elseif (str_contains($bodyType, 'text/html')) {
					$emailMessage->html(implode(PHP_EOL, $bodyContent['content']));
				}
			}

			$emailMessages->push($emailMessage);
		}

		return $emailMessages;
	}
}
