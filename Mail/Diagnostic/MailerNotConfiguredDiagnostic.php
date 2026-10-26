<?php
declare(strict_types=1);

namespace AstrX\Mail\Diagnostic;

use AstrX\Result\AbstractDiagnostic;

/**
 * Emitted when the framework would send an email but no mailer is set up,
 * e.g. a verification token that should have been mailed. Shown to admins in
 * the diagnostics panel, so pending verifications aren't lost silently.
 */
final class MailerNotConfiguredDiagnostic extends AbstractDiagnostic
{
}
