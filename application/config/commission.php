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
| Statutory deductions - TCS (GST s.52) and TDS (Income Tax s.194-O)
|--------------------------------------------------------------------------
|
| These are the LAST-RESORT defaults. The live values come from the admin settings
| screen (Settings > Statutory deductions), and the per-sale rate is then decided by
| Tax_compliance_model from facts about the seller - so what is written here is only
| read when a setting is missing entirely.
|
| They used to ship at 0 with an instruction to leave them alone until an accountant
| confirmed, which in practice meant a live marketplace collected nothing at all.
| Both collections are compulsory for an e-commerce operator, so the statutory rates
| are now the defaults and the master switch (statutory_deductions_enabled) is what
| turns them off.
|
| How each is applied - see Seller_model::calculate_settlement_breakdown():
|
|   commission_gst_percent : GST on the platform's own commission (18). The platform
|                            issues the seller a tax invoice for the commission.
|   tcs_percent            : GST TCS u/s 52 (0.5), collected from GSTIN-registered
|                            sellers only, on the ex-GST taxable value, and split into
|                            IGST (inter-state) or CGST + SGST (intra-state). A seller
|                            on an Enrollment ID is unregistered: nothing is collected
|                            and they may only supply within their own state.
|   tds_percent            : TDS u/s 194-O (0.1) for a seller with a valid PAN, on the
|                            ex-GST taxable value. An individual / HUF (PAN 4th letter
|                            P or H) pays none until cumulative sales for the financial
|                            year exceed tds_threshold_amount; a firm / LLP / company
|                            (F / L / C) has no threshold at all.
|   tds_percent_no_pan     : TDS u/s 206AA (5) where no valid PAN is on file.
|   tds_threshold_amount   : the s.194-O annual exemption (500000), individual / HUF only.
|
| GST charged on the goods is excluded from both the TDS and the TCS base: the seller
| invoices and remits that themselves, so the marketplace never deducts against it.
*/
$config['commission_gst_percent'] = 18;     // charged on the commission amount
$config['tcs_percent']            = 0.5;    // GST s.52, on the ex-GST taxable value
$config['tds_percent']            = 0.1;    // s.194-O with a valid PAN, ex-GST base
$config['tds_percent_no_pan']     = 5;      // s.206AA, no valid PAN on file
$config['tds_threshold_amount']   = 500000; // per financial year, individual / HUF only
