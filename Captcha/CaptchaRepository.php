<?php
declare(strict_types=1);

namespace AstrX\Captcha;

use AstrX\Captcha\Diagnostic\CaptchaDbDiagnostic;
use AstrX\Result\Diagnostics;
use AstrX\Result\Result;
use PDO;
use PDOException;
use AstrX\Result\DiagnosticLevel;

/**
 * Pure data-access layer for the `captcha` table.
 *
 * Schema (packages/cms/resources/sql/schema/0000_core_base.sql):
 *   captcha(id CHAR(32) PK, text VARCHAR(32), expires_at TIMESTAMP)
 */
final class CaptchaRepository
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * Store a new captcha token.
     *
     * @return Result<bool>
     */
    public function store(string $id, string $text, int $expiresAt): Result
    {
        // Store the answer as plaintext, lower-cased so verification is a simple
        // case-insensitive compare. Hashing would protect nothing (the same answer
        // is drawn in the image we send), and the reloadable captcha needs the text
        // to redraw the image. Rows expire after 10 minutes.
        $plain = strtolower($text);
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `captcha` (`id`, `text`, `expires_at`)
                 VALUES (:id, :text, FROM_UNIXTIME(:expires_at))'
            );
            $stmt->execute([':id' => $id, ':text' => $plain, ':expires_at' => $expiresAt]);

            return Result::ok(true);
        } catch (PDOException $e) {
            return Result::err(null, $this->diagnostic($e));
        }
    }

    /**
     * Find a captcha by ID.
     *
     * @return Result<array{text:string, expires_at:int}|null>
     */
    public function find(string $id): Result
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT `text`, UNIX_TIMESTAMP(`expires_at`) AS expires_at
                   FROM `captcha`
                  WHERE `id` = :id'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row === false) {
                return Result::ok(null);
            }
            /** @var array<string,mixed> $row */
            return Result::ok([
                                  'text'       => is_scalar($row['text']) ? (string)$row['text'] : '',
                                  'expires_at' => is_int($row['expires_at']) ? $row['expires_at'] : 0,
                              ]);
        } catch (PDOException $e) {
            return Result::err(null, $this->diagnostic($e));
        }
    }

    /**
     * Delete a captcha by ID (called after successful verification).
     *
     * @return Result<bool>
     */
    public function delete(string $id): Result
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM `captcha` WHERE `id` = :id');
            $stmt->execute([':id' => $id]);

            return Result::ok(true);
        } catch (PDOException $e) {
            return Result::err(null, $this->diagnostic($e));
        }
    }

    /**
     * Atomically CONSUME (single-use) a captcha by id. Returns true only for the
     * caller whose DELETE actually removed the row. Concurrent verifications of
     * the same solved captcha all reach here, but only one deletes it (rowCount
     * 1) and the rest see rowCount 0 — closing the find()-then-delete() TOCTOU
     * that would otherwise let one solved captcha authorise a burst of requests.
     *
     * @return Result<bool>
     */
    public function consume(string $id): Result
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM `captcha` WHERE `id` = :id');
            $stmt->execute([':id' => $id]);

            return Result::ok($stmt->rowCount() === 1);
        } catch (PDOException $e) {
            return Result::err(null, $this->diagnostic($e));
        }
    }

    /**
     * Replace the answer text of an existing captcha, atomically.
     *
     * The id stays the same, so the parent form's hidden input stays valid; only
     * the text to type changes.
     *
     * The rate limit is part of the UPDATE: it only matches when regen_count <
     * :max and last_regen_at is null or older than :cooldown seconds. Otherwise
     * it matches no row, and the caller keeps the current image. No lock needed,
     * even with concurrent refreshes.
     *
     * @return Result<bool>  true if the row was regenerated, false if rate-limited.
     */
    public function regenerate(
        string $id,
        string $newText,
        int    $maxRegens     = 5,
        int    $cooldownSecs  = 2,
    ): Result {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE `captcha`
                    SET `text`          = :text,
                        `regen_count`   = `regen_count` + 1,
                        `last_regen_at` = NOW()
                  WHERE `id`            = :id
                    AND `regen_count`   < :max
                    AND (`last_regen_at` IS NULL
                         OR `last_regen_at` < (NOW() - INTERVAL :cool SECOND))'
            );
            $stmt->execute([
                ':text' => strtolower($newText),
                ':id'   => $id,
                ':max'  => $maxRegens,
                ':cool' => $cooldownSecs,
            ]);
            return Result::ok($stmt->rowCount() > 0);
        } catch (\PDOException $e) {
            return Result::err(false, Diagnostics::of(
                new CaptchaDbDiagnostic(
                    'astrx.captcha/regenerate_failed',
                    DiagnosticLevel::ERROR,
                    $e->getMessage(),
                ),
            ));
        }
    }

    /**
     * Delete expired captchas. generate() calls it now and then to keep the
     * table small.
     *
     * @return Result<int> Number of rows deleted.
     */
    public function deleteExpired(): Result
    {
        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM `captcha` WHERE `expires_at` < NOW()'
            );
            $stmt->execute();

            return Result::ok($stmt->rowCount());
        } catch (PDOException $e) {
            return Result::err(0, $this->diagnostic($e));
        }
    }

    private function diagnostic(PDOException $e): Diagnostics
    {
        return Diagnostics::of(new CaptchaDbDiagnostic(
                                   'astrx.captcha/db_error', DiagnosticLevel::ERROR,
                                   $e->getMessage(),
                               ));
    }
}
