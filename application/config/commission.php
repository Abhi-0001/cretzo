<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Commission & settlement configuration
|--------------------------------------------------------------------------
|
| Commission RATES themselves live on the subscription plans (the
| commission_first50 / commission_51_100 / commission_after100 columns), because
| they are a per-plan commercial decision the admin edits. This file holds the
| platform-wide settlement parameters that are not per-plan.
|
*/

/*
| Fallback commission percentage.
|
| Used when a seller's plan has no rate for their slab. Previously a NULL rate was
| cast to 0.0 and the sale settled at ZERO commission with nothing reported - the
| shipped "Launch Offer" plan is in exactly that state. Falling back to an explicit
| platform rate means a missing plan value can never silently mean "free".
|
| Set this to 0 only if you genuinely intend un-priced plans to be commission-free.
*/
$config['default_commission_percent'] = 8;

/*
|--------------------------------------------------------------------------
| Statutory deductions - ALL DISABLED BY DEFAULT
|--------------------------------------------------------------------------
|
| The settlement engine computes, stores and displays each of these as its own line
| on the seller's statement, but they are set to 0 so that nothing is withheld until
| you have confirmed with your accountant that they apply to you. Withholding the
| wrong amount is considerably worse than not withholding at all, and applicability
| depends on your GST registration and turnover - which is not something that can be
| decided from the code.
|
| When your accountant confirms, set the values below. Typical Indian marketplace
| figures are given for reference only:
|
|   commission_gst_percent : GST on the platform's own commission (typically 18).
|                            The platform must also issue the seller a tax invoice
|                            for the commission charged.
|   tcs_percent            : TCS collected under GST on the net taxable value of
|                            supplies (typically 1), deposited against the seller's
|                            GSTIN.
|   tds_percent            : TDS deducted under Income Tax s.194-O on the gross
|                            sale value (typically 1), deposited against the
|                            seller's PAN.
|
| Each is charged on the base named in the comment, matching the settlement ladder
| in Seller_model::calculate_settlement_breakdown().
*/
$config['commission_gst_percent'] = 0; // charged on the commission amount
$config['tcs_percent']            = 0; // charged on the taxable (ex-GST) value
$config['tds_percent']            = 0; // charged on the gross (incl-GST) value
