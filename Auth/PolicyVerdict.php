<?php
declare(strict_types=1);

namespace AstrX\Auth;

/**
 * A resource-level policy's ruling on one permission check.
 *
 * Three named cases instead of a nullable bool: abstaining on purpose is
 * Abstain, while "this policy has no rule for this permission" (see
 * PolicyInterface::governs()) is something Gate can tell apart and deny.
 */
enum PolicyVerdict
{
    /** Permit, regardless of what the role grant would have decided next. */
    case Allow;

    /** Refuse, even though the role grant allows it. */
    case Deny;

    /**
     * Deliberately no opinion: the policy has a rule for this permission but it
     * did not fire, so the role-level decision stands. NOT the same as "this
     * policy does not know about this permission" — that is expressed by
     * leaving the permission out of governs(), which Gate treats as a denial.
     */
    case Abstain;
}
