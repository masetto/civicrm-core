<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Incoming mail class.
 *
 * @internal - this is not supported for use from outside of code.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Mail_IncomingMail {

  /**
   * @var \ezcMail
   */
  private $mail;

  /**
   * @var string
   */
  private $action;

  /**
   * @var int
   */
  private $queueID;

  /**
   * @var int
   */
  private $jobID;

  /**
   * @var string
   */
  private $hash;

  public function getAction() : ?string {
    return $this->action;
  }

  /**
   * @return int|null
   */
  public function getQueueID(): ?int {
    return $this->queueID;
  }

  /**
   * @return int|null
   */
  public function getJobID(): ?int {
    return $this->jobID;
  }

  /**
   * @return string|null
   */
  public function getHash(): ?string {
    return $this->hash;
  }

  /**
   * Is this a verp email.
   *
   * If the regex didn't find a match then no.
   *
   * @return bool
   */
  public function isVerp(): bool {
    return (bool) $this->action;
  }

  /**
   * @param \ezcMail $mail
   * @param string $emailDomain
   * @param string $emailLocalPart
   *
   * @throws \ezcBasePropertyNotFoundException
   * @throws \CRM_Core_Exception
   */
  public function __construct(ezcMail $mail, string $emailDomain, string $emailLocalPart) {
    $this->mail = $mail;

    $verpSeparator = preg_quote(\Civi::settings()->get('verpSeparator') ?: '');
    $emailDomain = preg_quote($emailDomain);
    $emailLocalPart = preg_quote($emailLocalPart);
    $twoDigitStringMin = $verpSeparator . '(\d+)' . $verpSeparator . '(\d+)';
    $twoDigitString = $twoDigitStringMin . $verpSeparator;

    // a common-for-all-actions regex to handle CiviCRM 2.2 address patterns
    $regex = '/^' . $emailLocalPart . '(b|c|e|o|r|u)' . $twoDigitString . '([0-9a-f]{16})@' . $emailDomain . '$/';

    // a tighter regex for finding bounce info in soft bounces’ mail bodies
    $rpRegex = '/Return-Path:\s*' . $emailLocalPart . '(b)' . $twoDigitString . '([0-9a-f]{16})@' . $emailDomain . '/';

    // a regex for finding bound info X-Header
    $rpXHeaderRegex = '/X-CiviMail-Bounce: ' . $emailLocalPart . '(b)' . $twoDigitString . '([0-9a-f]{16})@' . $emailDomain . '/i';
    // CiviMail in regex and Civimail in header !!!
    $matches = NULL;
    foreach ($this->mail->to as $address) {
      if (preg_match($regex, ($address->email ?? ''), $matches)) {
        [, $this->action, $this->jobID, $this->queueID, $this->hash] = $matches;
        break;
      }
    }

    // CRM-5471: if $matches is empty, it still might be a soft bounce sent
    // to another address, so scan the body for ‘Return-Path: …bounce-pattern…’
    if (!$matches && preg_match($rpRegex, ($mail->generateBody() ?? ''), $matches)) {
      [, $this->action, $this->jobID, $this->queueID, $this->hash] = $matches;
    }

    // if $matches is still empty, look for the X-CiviMail-Bounce header
    // CRM-9855
    if (!$matches && preg_match($rpXHeaderRegex, ($mail->generateBody() ?? ''), $matches)) {
      [, $this->action, $this->jobID, $this->queueID, $this->hash] = $matches;
    }
    // With Mandrill, the X-CiviMail-Bounce header is produced by generateBody
    // is base64 encoded
    // Check all parts
    if (!$matches) {
      $all_parts = $mail->fetchParts();
      foreach ($all_parts as $v_part) {
        if ($v_part instanceof ezcMailFile) {
          $p_file = $v_part->__get('fileName');
          $c_file = file_get_contents($p_file);
          if (preg_match($rpXHeaderRegex, ($c_file ?? ''), $matches)) {
            [, $this->action, $this->jobID, $this->queueID, $this->hash] = $matches;
          }
        }
      }
    }

    // if all else fails, check Delivered-To for possible pattern
    if (!$matches && preg_match($regex, ($mail->getHeader('Delivered-To') ?? ''), $matches)) {
      [, $this->action, $this->jobID, $this->queueID, $this->hash] = $matches;
    }
  }

}