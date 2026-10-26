<?php
declare(strict_types=1);

namespace AstrX\Auth;

use Attribute;

/**
 * Declares the permission needed to open a controller's page at all.
 *
 * ContentManager reads it off the controller class before calling handle(),
 * so the page's entry gate lives in the code that serves it, not only in the
 * `page_closure` table. A migration can add or drop a closure row; it can't
 * remove this attribute.
 *
 * It doesn't replace a controller's own finer checks (AdminConfigSystemController
 * still answers 403 to a moderator who has ADMIN_ACCESS but not
 * ADMIN_CONFIG_SYSTEM). It only states the coarse "may this visitor open this
 * page" gate.
 *
 * It lives in Auth, next to the permissions it carries.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class RequiresPermission
{
    public function __construct(public readonly Permission $permission) {}
}
