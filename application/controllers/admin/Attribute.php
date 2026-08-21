<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Intentionally empty.
 *
 * This file used to declare `class Attribute extends CI_Controller`. PHP 8.0 added a built-in
 * class called `Attribute` (the one behind #[Attr] syntax), so as soon as this project moved to
 * PHP 8 the mere act of routing to admin/attribute produced an unrecoverable
 *
 *     Fatal error: Cannot declare class Attribute, because the name is already in use
 *
 * before any of the controller's own code ran - not a 404, a hard 500 with a PHP stack path
 * printed to the browser. Reproduced live on this install, for the super admin as well as for
 * restricted roles, so the permission check inside it never got a chance to run either.
 *
 * Nothing pointed at it: it was a stale, shorter copy of admin/Attributes.php (117 lines vs
 * 200), and the sidebar, the views and the admin JS all link to `admin/attributes/...`, which
 * is the maintained one. Emptying the file rather than editing the class name keeps
 * admin/attribute a plain 404 instead of resurrecting a second, out-of-date copy of the
 * attribute screens that would then drift from Attributes.php.
 *
 * The real controller is application/controllers/admin/Attributes.php.
 */
