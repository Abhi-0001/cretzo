#!/bin/bash
# ============================================================================
# Referral programme - end-to-end regression, phases 1 to 5 plus the QR feature.
# ============================================================================
#
#   bash tools/referral-e2e.sh          (run from the project root)
#
# WHAT IT DOES TO THE DATABASE
# ----------------------------
# It writes. It enables all four programmes, creates throwaway customers and a
# throwaway seller, places orders, credits and reverses real wallet money, and
# issues promo codes. LOCAL DATABASE ONLY - never point it at production.
#
# It also OVERWRITES the programme's amounts and its policy blob with the values
# it asserts on (min order 499, per-referrer cap 2000, monthly budget 10000), so
# a run cannot fail because somebody tuned those on the admin screens.
#
# It does NOT clean up after itself: the rows are left behind on purpose so a
# failure can be inspected. Clean up by deleting users whose email matches
# 'e2e%@example.com', their orders/order_items/referrals/rewards, promo codes
# matching 'REF%', then setting referral_programs.status back to 0.
#
# It needs: an admin login (user 6 / mobile 9910919480 / TestPass123! on the
# local DB), a customer referrer with a code (user 2), a seller referrer
# (user 7), and XAMPP MySQL on 127.0.0.1:3307.
#
# It drives the engine through application/controllers/Referral_harness.php - a
# CLI-only pass-through that ships with this script. Deleting one breaks the
# other.
#
# The one thing it cannot exercise is the browser: the signup modal's ?ref=
# capture and the admin screens' JavaScript are verified by eye, not here.
# ============================================================================
PHP=/c/xampp/php/php.exe
MYX="/c/xampp/mysql/bin/mysql.exe -uroot -h127.0.0.1 -P3307 cretzo_db -N -B -e"
BASE="http://localhost/cretzo"
J=/tmp/e2e_customer.txt
A=/tmp/e2e_admin.txt
rm -f $J $A

sql() { $MYX "$1" | tr -d '\r'; }
bust() { rm -f application/cache/app/cz1_settings.referral_settings.cache application/cache/app/cz1_settings.system_settings.cache; }
pass() { printf "  \033[32mPASS\033[0m %s\n" "$1"; }
fail() { printf "  \033[31mFAIL\033[0m %s -- got: %s\n" "$1" "$2"; FAILED=$((FAILED+1)); }
expect() { # $1 haystack  $2 needle  $3 label
  if echo "$1" | grep -q "$2"; then pass "$3"; else fail "$3" "$(echo $1 | head -c 150)"; fi
}
FAILED=0

ctok() { curl -s -b $J -c $J "$BASE/" -o /tmp/e2e_pg.html; grep -oE "csrfHash *= *['\"][^'\"]*['\"]" /tmp/e2e_pg.html | head -1 | sed -E "s/.*['\"]([^'\"]*)['\"]/\1/"; }
atok() { curl -s -b $A -c $A "$BASE/admin/referral/programs" -o /tmp/e2e_ap.html; grep -oE "csrfHash *= *\"[^\"]*\"" /tmp/e2e_ap.html | head -1 | sed -E 's/.*"([^"]*)".*/\1/'; }

signup() { # $1 name, $2 code -> echoes new user id
  local email="e2e$(date +%s%N | tail -c 7)@example.com"
  local t=$(ctok)
  curl -s -b $J -c $J -X POST "$BASE/auth/register_user" -d "name=$1" -d "email=$email" \
       -d "password=secret123" -d "type=email" -d "friends_code=$2" -d "ekart_security_token=$t" -o /tmp/e2e_reg.json
  sql "SELECT id FROM users WHERE email='$email'"
}

order_for() { # $1 user, $2 total, [$3 seller_id, $4 seller_total]
  sql "INSERT INTO orders (user_id, mobile, total, delivery_charge, wallet_balance, discount, total_payable, final_total, payment_method, address, date_added)
       VALUES ($1,'9000000000',$2,0,0,0,$2,$2,'cod','test',NOW())"
  local oid=$(sql "SELECT MAX(id) FROM orders")
  if [ -n "$3" ]; then
    sql "INSERT INTO order_items (user_id, order_id, seller_id, product_name, quantity, price, sub_total, active_status, date_added)
         VALUES ($1,$oid,$3,'test item',1,$4,$4,'delivered',NOW())"
  fi
  echo $oid
}

echo "============================================================"
echo " SETUP"
echo "============================================================"
sql "DELETE FROM referral_rewards; DELETE FROM referrals;"
sql "DELETE FROM promo_code_users; DELETE FROM promo_codes WHERE promo_code LIKE 'REF%';"
sql "UPDATE referral_programs SET status=1, spent_to_date=0"
sql "DELETE FROM login_attempts"
sql "UPDATE users SET balance=0, referral_credit=0, listing_bonus=0 WHERE id IN (2,6,7)"

# The suite asserts on specific amounts, so it SETS them rather than hoping
# nobody has touched the admin screens. Every value here is admin-editable, and
# all of them were found changed mid-development (budget 5000, per-referrer cap
# 499, minimum order 0) - which failed three checks for a reason that had nothing
# to do with the code under test.
sql "UPDATE referral_milestones SET min_order_amount=499 WHERE code='first_delivered_order'"
sql "UPDATE referral_milestones SET referrer_amount=100 WHERE code IN ('first_delivered_order','kyc_shop_live')"
sql "UPDATE referral_milestones SET referee_benefit_type='promo_code', referee_benefit_value=100 WHERE code='first_delivered_order' AND program_id IN (SELECT id FROM referral_programs WHERE code IN ('customer_customer','seller_customer'))"
sql "UPDATE referral_milestones SET referrer_amount=500 WHERE code='tier_5'"
sql "UPDATE referral_milestones SET referrer_amount=1000 WHERE code='tier_10'"
sql "UPDATE settings SET value='{\"withdrawable\":\"0\",\"monthly_budget_cap\":\"10000\",\"per_referrer_monthly_cap\":\"2000\",\"hold_days_after_return_window\":\"1\",\"wallet_orders_qualify\":\"1\",\"allow_negative_on_reversal\":\"0\",\"min_order_amount\":\"499\",\"promo_discount\":\"100\",\"promo_min_cart\":\"499\",\"promo_validity_days\":\"30\",\"flag_review_hold_hours\":\"24\",\"credit_expiry_months\":\"12\",\"expiry_notice_days\":\"30\",\"ambassador_cumulative\":\"1\",\"tier_counts_credited_only\":\"1\"}' WHERE variable='referral_settings'"

# Settings are cached across requests, so an SQL edit is invisible to the engine
# until the cached copy is dropped.
bust
echo "  all four programmes enabled; amounts and policy set to the suite's values"

# admin session for the panel checks
T=$(curl -s -b $A -c $A "$BASE/admin/login" -o /tmp/e2e_l.html; grep -oE "name='ekart_security_token' value='[^']*'" /tmp/e2e_l.html | head -1 | sed "s/.*value='\(.*\)'/\1/")
curl -s -b $A -c $A -X POST "$BASE/auth/login" -d "identity=9910919480" -d "password=TestPass123!" -d "ekart_security_token=$T" -o /dev/null

CUST_CODE=$(sql "SELECT referral_code FROM users WHERE id=2")
SELLER_CODE=$(sql "SELECT referral_code FROM users WHERE id=7")
echo "  customer referrer code: $CUST_CODE (user 2)"
echo "  seller referrer code:   $SELLER_CODE (user 7)"

echo
echo "============================================================"
echo " PHASE 1  attribution"
echo "============================================================"
R1=$(signup "E2E Customer" "$CUST_CODE")
ROW=$(sql "SELECT CONCAT(referrer_id,'|',referee_id,'|',program_id,'|',status) FROM referrals WHERE referee_id=$R1")
expect "$ROW" "^2|$R1|1|attributed" "1.1 signup with a code binds referrer -> referee on the right programme"

CODE1=$(sql "SELECT referral_code FROM users WHERE id=$R1")
[ -n "$CODE1" ] && pass "1.2 the new account got its own code ($CODE1)" || fail "1.2 new account code" "empty"

t=$(ctok)
V=$(curl -s -b $J -c $J -X POST "$BASE/auth/validate_referral" -d "code=$(echo $CUST_CODE | tr 'A-Z' 'a-z')" -d "ekart_security_token=$t")
expect "$V" '"error":false' "1.3 lower-case code validates (normalisation)"

t=$(ctok)
V=$(curl -s -b $J -c $J -X POST "$BASE/auth/register_user" -d "name=Bad" -d "email=bad$(date +%s)@example.com" -d "password=secret123" -d "type=email" -d "friends_code=NOTREAL9" -d "ekart_security_token=$t")
expect "$V" "does not exist" "1.4 signup with an unknown code is refused"

DUP=$(sql "INSERT INTO referrals (referrer_id, referee_id, program_id, code_used) VALUES (6,$R1,1,'X') " 2>&1)
expect "$DUP" "Duplicate entry" "1.5 a user cannot be referred twice (unique key)"

echo
echo "============================================================"
echo " PHASE 2  reward engine: earn, hold, credit, reverse"
echo "============================================================"
O1=$(order_for $R1 750)
D=$($PHP index.php referral_harness delivered $R1 $O1)
expect "$D" "reward_pending" "2.1 delivered order creates a pending reward"

D2=$($PHP index.php referral_harness delivered $R1 $O1)
expect "$D2" "already_rewarded" "2.2 the same delivery called again is a no-op"

REL=$($PHP index.php referral_harness release)
expect "$REL" '"credited":0' "2.3 release before the hold credits nothing"

sql "UPDATE referral_rewards SET qualified_at=DATE_SUB(NOW(),INTERVAL 1 HOUR) WHERE status='pending'"
REL=$($PHP index.php referral_harness release)
# Two rewards settle on one milestone: the referrer's wallet credit and the
# referee's discount code. Only the first is money leaving a wallet.
expect "$REL" '"credited":2' "2.4 release settles both sides of the milestone"
expect "$REL" '"amount":100' "2.4b only Rs 100 of it is wallet money"
expect "$REL" '"benefits":1' "2.4c the other is a benefit, not cash"

BAL=$(sql "SELECT CONCAT(balance,'|',referral_credit) FROM users WHERE id=2")
expect "$BAL" "^100.00|100.00" "2.5 wallet and restricted credit both at 100"

TXN=$(sql "SELECT COUNT(*) FROM referral_rewards rw JOIN transactions t ON t.id=rw.transaction_id WHERE rw.status='credited'")
expect "$TXN" "^[1-9]" "2.6 the reward points at its ledger row"

W=$($PHP index.php referral_harness withdrawable 2)
expect "$W" "^0" "2.7 referral credit is not withdrawable"

$PHP index.php referral_harness spend 2 40 >/dev/null
BAL=$(sql "SELECT CONCAT(balance,'|',referral_credit) FROM users WHERE id=2")
expect "$BAL" "^60.00|60.00" "2.8 spending consumes the restricted part first"

REV=$($PHP index.php referral_harness returned $R1 $O1)
expect "$REV" '"recovered":60' "2.9 return recovers only what is there"
BAL=$(sql "SELECT CONCAT(balance,'|',referral_credit) FROM users WHERE id=2")
expect "$BAL" "^0.00|0.00" "2.10 the wallet is never pushed below zero"
SF=$(sql "SELECT reversed_shortfall FROM referral_rewards WHERE beneficiary_id=2 AND status='reversed' LIMIT 1")
expect "$SF" "^40.00" "2.11 the unrecovered 40 is recorded as a shortfall"

R2=$(signup "E2E Second" "$CUST_CODE")
O2=$(order_for $R2 900)
$PHP index.php referral_harness delivered $R2 $O2 >/dev/null
sql "UPDATE referral_rewards SET qualified_at=DATE_SUB(NOW(),INTERVAL 1 HOUR) WHERE status='pending'"
$PHP index.php referral_harness release >/dev/null
BAL=$(sql "SELECT balance FROM users WHERE id=2")
expect "$BAL" "^60.00" "2.12 the shortfall comes out of the next reward (100-40)"

R3=$(signup "E2E Small" "$CUST_CODE")
O3=$(order_for $R3 300)
D=$($PHP index.php referral_harness delivered $R3 $O3)
expect "$D" "order_not_qualifying" "2.13 an order under Rs 499 earns nothing"

echo
echo "============================================================"
echo " PHASE 4  referee benefits"
echo "============================================================"
PROMO=$(sql "SELECT promo_code FROM promo_codes WHERE promo_code LIKE 'REF%' ORDER BY id DESC LIMIT 1")
[ -n "$PROMO" ] && pass "4.1 the referred customer was issued a discount code ($PROMO)" || fail "4.1 promo issued" "none"

OWNER=$(sql "SELECT u.user_id FROM promo_code_users u JOIN promo_codes p ON p.id=u.promo_code_id WHERE p.promo_code='$PROMO'")
expect "$OWNER" "^$R2$" "4.2 the code is bound to the referred customer, not the referrer"

P=$($PHP index.php referral_harness promo "$PROMO" $R2 600)
expect "$P" '"error":false' "4.3 the owner can redeem it"

P=$($PHP index.php referral_harness promo "$PROMO" 2 600)
expect "$P" "issued to another account" "4.4 nobody else can redeem it"

P=$($PHP index.php referral_harness promo "$PROMO" $R2 200)
expect "$P" '"error":true' "4.5 it respects its minimum cart"

LIST=$(sql "SELECT list_promocode FROM promo_codes WHERE promo_code='$PROMO'")
expect "$LIST" "^0$" "4.6 it stays out of the public coupon list"

echo
echo "============================================================"
echo " PHASE 4  seller programmes"
echo "============================================================"
S1=$(signup "E2E Seller" "$SELLER_CODE")
sql "INSERT INTO users_groups (user_id, group_id) VALUES ($S1, 4)"
sql "INSERT INTO seller_data (user_id, store_name, slug, status) VALUES ($S1,'E2E Shop','e2e-shop',2)"
sql "UPDATE referrals SET program_id=(SELECT id FROM referral_programs WHERE code='seller_seller') WHERE referee_id=$S1"

APP=$($PHP index.php referral_harness approved $S1)
expect "$APP" "reward_pending" "4.7 approving a referred seller's shop creates the KYC reward"

REFEREE_REWARD=$(sql "SELECT CONCAT(role,'|',benefit_type,'|',amount) FROM referral_rewards WHERE beneficiary_id=$S1")
expect "$REFEREE_REWARD" "^referee|wallet|50.00" "4.8 the referred seller is owed Rs 50 wallet credit (not listings)"

sql "UPDATE referral_rewards SET qualified_at=DATE_SUB(NOW(),INTERVAL 1 HOUR) WHERE status='pending'"
$PHP index.php referral_harness release >/dev/null
SB=$(sql "SELECT CONCAT(balance,'|',referral_credit) FROM users WHERE id=$S1")
expect "$SB" "^50.00|50.00" "4.9 the seller's Rs 50 is credited as restricted money"
RB=$(sql "SELECT balance FROM users WHERE id=7")
expect "$RB" "^100.00" "4.10 the referring seller got Rs 100 for the shop going live"

O4=$(order_for 2 900 $S1 900)
$PHP index.php referral_harness delivered 2 $O4 >/dev/null
SALE=$(sql "SELECT COUNT(*) FROM referral_rewards rw JOIN referral_milestones m ON m.id=rw.milestone_id WHERE rw.beneficiary_id=7 AND m.code='first_delivered_order'")
expect "$SALE" "^1$" "4.11 the referred seller's first SALE pays the referrer again"

echo
echo "============================================================"
echo " PHASE 4  spend-only at the payout door"
echo "============================================================"
sql "UPDATE users SET balance=500, referral_credit=200 WHERE id=$S1"
WD=$($PHP index.php referral_harness withdraw $S1 400)
expect "$WD" "referral credit" "4.12 withdrawing into referral credit is refused, and says why"
WD=$($PHP index.php referral_harness withdraw $S1 250)
expect "$WD" '"error":false' "4.13 withdrawing the unrestricted part is allowed"

echo
echo "============================================================"
echo " PHASE 3  admin panel"
echo "============================================================"
for p in "admin/referral/programs" "admin/referral/ledger" "admin/referral/queue?status=all" "admin/referral/report"; do
  c=$(curl -s -b $A -o /tmp/e2e_o.html -w '%{http_code}' "$BASE/$p")
  e=$(grep -c "A PHP Error\|Fatal error" /tmp/e2e_o.html)
  if [ "$c" = "200" ] && [ "$e" = "0" ]; then pass "3.x $p renders clean"; else fail "3.x $p" "http $c, php errors $e"; fi
done

L=$(curl -s -b $A "$BASE/admin/referral/ledger_list?limit=50&offset=0")
expect "$L" '"total":[1-9]' "3.5 the ledger feed returns the referrals"
Q=$(curl -s -b $A "$BASE/admin/referral/rewards_list?limit=50&offset=0&status=credited")
expect "$Q" '"total":[1-9]' "3.6 the rewards feed returns credited rewards"

t=$(atok)
PEND=$(sql "SELECT id FROM referral_rewards WHERE status='pending' ORDER BY id DESC LIMIT 1")
if [ -n "$PEND" ]; then
  RV=$(curl -s -b $A -c $A -X POST "$BASE/admin/referral/review" -d "id=$PEND" -d "action=reject" -d "note=e2e test" -d "ekart_security_token=$t")
  expect "$RV" "rejected" "3.7 an admin can reject a pending reward"
fi
t=$(atok)
CRED=$(sql "SELECT id FROM referral_rewards WHERE status='credited' AND benefit_type='wallet' ORDER BY id DESC LIMIT 1")
RV=$(curl -s -b $A -c $A -X POST "$BASE/admin/referral/review" -d "id=$CRED" -d "action=reject" -d "ekart_security_token=$t")
expect "$RV" "use Reverse" "3.8 rejecting a PAID reward is refused with the right advice"

echo
echo "============================================================"
echo " PHASE 5  ambassador tiers"
echo "============================================================"
# Real thresholds are 5/10/25. Reaching them would need 25 signups, so they are
# temporarily lowered to 2 and 3 and restored at the end of this section - the
# amounts, the cumulative rule and the guard against double-payment are what is
# under test, not the specific numbers.
sql "UPDATE referral_milestones SET code='tier_2' WHERE code='tier_5'"
sql "UPDATE referral_milestones SET code='tier_3' WHERE code='tier_10'"

for i in 1 2 3; do
  U=$(signup "Tier $i" "$CUST_CODE")
  O=$(order_for $U 900)
  $PHP index.php referral_harness delivered $U $O >/dev/null
done
# The third signup shares this machine's IP with the earlier ones, so phase 1
# flags it and the release run holds it for review - correct behaviour. Clearing
# the flag is what an admin approving it does.
sql "UPDATE referral_rewards SET qualified_at=DATE_SUB(NOW(),INTERVAL 1 HOUR), flagged=0 WHERE status='pending'"
$PHP index.php referral_harness release >/dev/null

Q=$($PHP index.php referral_harness tiers 2)
expect "$Q" '"qualified":[3-9]' "5.1 credited referrals are what counts toward a tier"

T1=$(sql "SELECT COUNT(*) FROM referral_rewards rw JOIN referral_milestones m ON m.id=rw.milestone_id WHERE m.code='tier_2' AND rw.beneficiary_id=2")
expect "$T1" "^1$" "5.2 the first tier was awarded"
T2=$(sql "SELECT COUNT(*) FROM referral_rewards rw JOIN referral_milestones m ON m.id=rw.milestone_id WHERE m.code='tier_3' AND rw.beneficiary_id=2")
expect "$T2" "^1$" "5.3 the second tier too - cumulative, not replacement"
T3=$(sql "SELECT COUNT(*) FROM referral_rewards rw JOIN referral_milestones m ON m.id=rw.milestone_id WHERE m.code='tier_25' AND rw.beneficiary_id=2")
expect "$T3" "^0$" "5.4 an unreached tier is not awarded"

$PHP index.php referral_harness tiers 2 >/dev/null
DUP=$(sql "SELECT COUNT(*) FROM referral_rewards rw JOIN referral_milestones m ON m.id=rw.milestone_id WHERE m.code='tier_2' AND rw.beneficiary_id=2")
expect "$DUP" "^1$" "5.5 re-running does not pay a tier twice"

BADGE=$(sql "SELECT ambassador_tier FROM users WHERE id=2")
expect "$BADGE" "^3$" "5.6 the badge records the highest tier reached"

sql "UPDATE referral_rewards SET qualified_at=DATE_SUB(NOW(),INTERVAL 1 HOUR) WHERE status='pending'"
$PHP index.php referral_harness release >/dev/null
TIERPAID=$(sql "SELECT COALESCE(SUM(rw.amount),0) FROM referral_rewards rw JOIN referral_milestones m ON m.id=rw.milestone_id JOIN referral_programs p ON p.id=m.program_id WHERE p.code='ambassador' AND rw.status='credited' AND rw.beneficiary_id=2")
expect "$TIERPAID" "^1500.00$" "5.7 both tier bonuses paid (500 + 1000)"

sql "UPDATE referral_milestones SET code='tier_5' WHERE code='tier_2'"
sql "UPDATE referral_milestones SET code='tier_10' WHERE code='tier_3'"

echo
echo "============================================================"
echo " PHASE 5  notifications"
echo "============================================================"
N=$(sql "SELECT COUNT(*) FROM notifications WHERE type='referral_earned'")
expect "$N" "^[1-9]" "5.8 in-app notice when a reward is earned"
N=$(sql "SELECT COUNT(*) FROM notifications WHERE type='referral_credited'")
expect "$N" "^[1-9]" "5.9 in-app notice when it is credited"
N=$(sql "SELECT COUNT(*) FROM notifications WHERE type='referral_tier'")
expect "$N" "^[1-9]" "5.10 in-app notice when a tier is reached"
MSG=$(sql "SELECT message FROM notifications WHERE type='referral_credited' LIMIT 1")
expect "$MSG" "cannot be withdrawn" "5.11 the credited notice says the money is spend-only"

echo
echo "============================================================"
echo " PHASE 5  customer page"
echo "============================================================"
# Users 1 and 2 have no users_groups row, so home/login answers "Invalid user"
# for them. Customer 8 is a real member account.
CUST8_CODE=$(sql "SELECT referral_code FROM users WHERE id=8")
HASH=$($PHP -r 'echo password_hash("TestPass123!", PASSWORD_BCRYPT, ["cost" => 10]);')
sql "UPDATE users SET password='$HASH' WHERE id=8"
signup "Ref of 8" "$CUST8_CODE" >/dev/null

# A FRESH jar: the signups above rotate the CSRF hash, so a token read before
# them is already stale.
CJ=/tmp/e2e_cust_page.txt; rm -f $CJ
CT=$(curl -s -c $CJ "$BASE/login" -o /tmp/e2e_login.html; grep -oE 'csrf-token-hash" content="[^"]*"' /tmp/e2e_login.html | head -1 | cut -d'"' -f3)
curl -s -b $CJ -c $CJ -X POST "$BASE/home/login" -d "identity=9910919035" -d "password=TestPass123!" -d "type=phone" -d "ekart_security_token=$CT" -o /tmp/e2e_loginres.html
# home/login answers with an EMPTY body on success, so the assertion is on what
# it did NOT say.
expect "$(grep -c 'not allowed' /tmp/e2e_loginres.html)" "^0$" "5.12 the customer signs in"

C=$(curl -s -b $CJ -o /tmp/e2e_ref.html -w '%{http_code}' "$BASE/my-account/refer-and-earn")
E=$(grep -c "A PHP Error\|Fatal error" /tmp/e2e_ref.html)
if [ "$C" = "200" ] && [ "$E" = "0" ]; then pass "5.13 /my-account/refer-and-earn renders clean"; else fail "5.13 customer page" "http $C errors $E"; fi
grep -q "$CUST8_CODE" /tmp/e2e_ref.html && pass "5.14 it shows the customer's own code" || fail "5.14 code on page" "missing"
grep -q "wa.me" /tmp/e2e_ref.html && pass "5.15 it offers a WhatsApp share" || fail "5.15 share link" "missing"
grep -q "Waiting for their first delivered order" /tmp/e2e_ref.html && pass "5.16 it explains WHY a reward is pending" || fail "5.16 pending explanation" "missing"

echo
echo "============================================================"
echo " PHASE 5  seller page and wallet-paid subscription"
echo "============================================================"
SHASH=$($PHP -r 'echo password_hash("TestPass123!", PASSWORD_BCRYPT, ["cost" => 10]);')
sql "UPDATE users SET password='$SHASH' WHERE id=7"
SJ=/tmp/e2e_seller.txt; rm -f $SJ
SMOB=$(sql "SELECT mobile FROM users WHERE id=7")
SCT=$(curl -s -c $SJ "$BASE/seller/login" -o /tmp/e2e_sl.html; grep -oE 'csrf-token-hash" content="[^"]*"' /tmp/e2e_sl.html | head -1 | cut -d'"' -f3)
curl -s -b $SJ -c $SJ -X POST "$BASE/seller/auth/login" -d "identity=$SMOB" -d "password=TestPass123!" -d "ekart_security_token=$SCT" -o /dev/null

C=$(curl -s -b $SJ -o /tmp/e2e_sref.html -w '%{http_code}' "$BASE/seller/refer")
E=$(grep -c "A PHP Error\|Fatal error" /tmp/e2e_sref.html)
if [ "$C" = "200" ] && [ "$E" = "0" ]; then pass "5.17 /seller/refer renders clean"; else fail "5.17 seller page" "http $C errors $E"; fi
grep -q "Invite a seller" /tmp/e2e_sref.html && pass "5.18 it explains both programmes on one code" || fail "5.18 seller page copy" "missing"

# The plan the seller is NOT on, so this is a renewal or an upgrade rather than a
# refused downgrade.
PLAN=$(sql "SELECT id FROM subscriptions WHERE price > 0 ORDER BY price DESC LIMIT 1")
PRICE=$(sql "SELECT price FROM subscriptions WHERE id=$PLAN")
sql "UPDATE users SET balance=10, referral_credit=10 WHERE id=7"
SCT=$(curl -s -b $SJ -c $SJ "$BASE/seller/subscription/details/$PLAN" -o /tmp/e2e_sdet.html; grep -oE 'csrf-token-hash" content="[^"]*"' /tmp/e2e_sdet.html | head -1 | cut -d'"' -f3)
grep -q "Pay from wallet" /tmp/e2e_sdet.html && pass "5.19 checkout offers the wallet" || fail "5.19 wallet option" "missing"

W=$(curl -s -b $SJ -c $SJ -X POST "$BASE/seller/subscription/purchase" -d "subscription_id=$PLAN" -d "payment_method=wallet" -d "ekart_security_token=$SCT")
expect "$W" "paid in full from the wallet" "5.20 an underfunded wallet is refused, and says why"

sql "UPDATE users SET balance=$PRICE + 100, referral_credit=200 WHERE id=7"
SCT=$(curl -s -b $SJ -c $SJ "$BASE/seller/subscription/details/$PLAN" -o /tmp/e2e_sdet.html; grep -oE 'csrf-token-hash" content="[^"]*"' /tmp/e2e_sdet.html | head -1 | cut -d'"' -f3)
W=$(curl -s -b $SJ -c $SJ -X POST "$BASE/seller/subscription/purchase" -d "subscription_id=$PLAN" -d "payment_method=wallet" -d "ekart_security_token=$SCT")
expect "$W" "was paid from your wallet" "5.21 a funded wallet pays for the plan"
CREDIT_LEFT=$(sql "SELECT referral_credit FROM users WHERE id=7")
expect "$CREDIT_LEFT" "^0.00$" "5.22 the restricted referral credit was spent FIRST"
LEDGER=$(sql "SELECT COUNT(*) FROM transactions WHERE user_id=7 AND type='debit' AND message LIKE 'Subscription:%'")
expect "$LEDGER" "^[1-9]" "5.23 the subscription debit is in the ledger"

echo
echo "============================================================"
echo " ADMIN  ambassador roster"
echo "============================================================"
C=$(curl -s -b $A -o /tmp/e2e_amb.html -w '%{http_code}' "$BASE/admin/referral/ambassadors")
E=$(grep -c "A PHP Error\|Fatal error" /tmp/e2e_amb.html)
if [ "$C" = "200" ] && [ "$E" = "0" ]; then pass "5.24 the ambassador roster renders clean"; else fail "5.24 roster" "http $C errors $E"; fi
# Asserts on the tier NAME, which is content, not on a CSS class - the admin
# views have been restyled once already, and a test that breaks on a class rename
# is a test that gets deleted rather than fixed.
# "Tier 3" is expected here rather than "Champion": this section restores the
# real thresholds (5/10/25) before the roster is fetched, so the test user's
# lowered tier number no longer matches a milestone and the view falls back to
# its generic label - which is the behaviour worth having.
grep -qE "Champion|Starter|Elite|Tier [0-9]" /tmp/e2e_amb.html && pass "5.25 it shows the referrer's tier" || fail "5.25 tier badge" "missing"

echo
echo "============================================================"
echo " QR  scan-to-refer"
echo "============================================================"
for a in "assets/vendor/qrcode.min.js" "assets/referral-qr.js" "assets/referral-qr.css"; do
  code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$a")
  if [ "$code" = "200" ]; then pass "7.x $a is served"; else fail "7.x $a" "http $code"; fi
done

QR=$(grep -o 'data-referral-qr="[^"]*"' /tmp/e2e_ref.html | head -1)
expect "$QR" "ref=$CUST8_CODE" "7.1 the buyer page encodes the buyer's own code"
expect "$QR" "src=qr" "7.2 the encoded link is marked as a scan"

QRS=$(grep -o 'data-referral-qr="[^"]*"' /tmp/e2e_sref.html | head -1)
expect "$QRS" "src=qr" "7.3 the seller page carries one too"

C=$(curl -s -b $SJ -o /tmp/e2e_card.html -w '%{http_code}' "$BASE/seller/refer/card")
E=$(grep -c "A PHP Error\|Fatal error" /tmp/e2e_card.html)
if [ "$C" = "200" ] && [ "$E" = "0" ]; then pass "7.4 the printable card renders"; else fail "7.4 printable card" "http $C errors $E"; fi
grep -q 'class="card-a5"' /tmp/e2e_card.html && grep -q 'class="card-sm"' /tmp/e2e_card.html \
  && pass "7.5 it offers both a poster and a business card" || fail "7.5 card sizes" "missing"
grep -q "do not use" /tmp/e2e_card.html && pass "7.6 it warns against fit-to-page printing" || fail "7.6 print hint" "missing"

# Attribution: the same signup, arriving three different ways.
QJ=/tmp/e2e_qr.txt; rm -f $QJ
scan_signup() { # $1 = source posted, echoes the stored signup_source
  local email="qr$(date +%s%N | tail -c 7)@example.com"
  local t=$(curl -s -b $QJ -c $QJ "$BASE/?ref=$CUST_CODE&src=qr" -o /tmp/e2e_land.html; grep -oE "csrfHash *= *['\"][^'\"]*['\"]" /tmp/e2e_land.html | head -1 | cut -d"'" -f2 | cut -d'"' -f2)
  curl -s -b $QJ -c $QJ -X POST "$BASE/auth/register_user" -d "name=Scan Test" -d "email=$email" \
       -d "password=secret123" -d "type=email" -d "friends_code=$CUST_CODE" -d "referral_source=$1" \
       -d "ekart_security_token=$t" -o /dev/null
  sql "SELECT signup_source FROM referrals WHERE referee_id=(SELECT id FROM users WHERE email='$email')"
}

expect "$(scan_signup qr)" "^qr$" "7.7 a scanned signup is recorded as a QR scan"
expect "$(scan_signup typed)" "^typed$" "7.8 a typed code is recorded as typed"
# The source is written by a public form, so an unrecognised value must not be
# stored raw - it becomes the generic channel instead.
expect "$(scan_signup '<script>x</script>')" "^link$" "7.9 a junk source is normalised, not stored raw"

L=$(curl -s -b $A "$BASE/admin/referral/ledger_list?limit=50&offset=0")
expect "$L" "QR scan" "7.10 the admin ledger shows which referrals came from a scan"

echo
echo "============================================================"
echo " CRON"
echo "============================================================"
C=$(curl -s "$BASE/admin/cron_job/release_referral_rewards")
expect "$C" "not configured" "6.1 no secret configured: refuses loudly"
C=$(SUBSCRIPTION_CRON_SECRET="e2e-secret" $PHP index.php admin cron_job release_referral_rewards wrong-token 2>&1)
expect "$C" "Unauthorized" "6.2 wrong token: 401, no session"
C=$(SUBSCRIPTION_CRON_SECRET="e2e-secret" $PHP index.php admin cron_job release_referral_rewards e2e-secret 2>&1)
expect "$C" '"error":false' "6.3 correct token, no session: runs"

echo
echo "============================================================"
if [ "$FAILED" = "0" ]; then printf " \033[32mALL CHECKS PASSED\033[0m\n"; else printf " \033[31m%s CHECK(S) FAILED\033[0m\n" "$FAILED"; fi
echo "============================================================"
