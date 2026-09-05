#!/bin/bash
# ============================================================================
# Referral programme - EDGE CASES
# ============================================================================
#
#   bash tools/referral-edge-cases.sh      (run from the project root)
#
# tools/referral-e2e.sh proves the happy paths work. This proves the awkward
# ones do: boundaries (an order at exactly the minimum), refusals (self-referral,
# a shared mobile), windows (a programme that has not started), and the money
# cases nobody wants to discover in production (reversing into an empty wallet,
# running the release twice, a promo code redeemed by the wrong person).
#
# LOCAL DATABASE ONLY. It writes, moves real wallet money, and does not clean up
# after itself - see the header of referral-e2e.sh for what to delete.
# ============================================================================
PHP=/c/xampp/php/php.exe
MYX="/c/xampp/mysql/bin/mysql.exe -uroot -h127.0.0.1 -P3307 cretzo_db -N -B -e"
BASE="http://localhost/cretzo"
J=/tmp/edge_c.txt; A=/tmp/edge_a.txt
rm -f $J $A

sql() { $MYX "$1" | tr -d '\r'; }
bust() { rm -f application/cache/app/cz1_settings.referral_settings.cache application/cache/app/cz1_settings.system_settings.cache; }
pass() { printf "  \033[32mPASS\033[0m %s\n" "$1"; }
fail() { printf "  \033[31mFAIL\033[0m %s -- got: %s\n" "$1" "$2"; FAILED=$((FAILED+1)); }
expect() { if echo "$1" | grep -qE "$2"; then pass "$3"; else fail "$3" "$(echo $1 | head -c 150)"; fi }
FAILED=0

ctok() { curl -s -b $J -c $J "$BASE/" -o /tmp/edge_pg.html; grep -oE "csrfHash *= *['\"][^'\"]*['\"]" /tmp/edge_pg.html | head -1 | cut -d"'" -f2 | cut -d'"' -f2; }

signup() { # $1 name, $2 code -> echoes the new user id
  local email="edge$(date +%s%N | tail -c 7)@example.com"
  local t=$(ctok)
  curl -s -b $J -c $J -X POST "$BASE/auth/register_user" -d "name=$1" -d "email=$email" \
       -d "password=secret123" -d "type=email" -d "friends_code=$2" -d "ekart_security_token=$t" -o /dev/null
  sql "SELECT id FROM users WHERE email='$email'"
}

order_for() { # $1 user, $2 total, [$3 seller_id, $4 seller_total], [$5 wallet_used]
  sql "INSERT INTO orders (user_id, mobile, total, delivery_charge, wallet_balance, discount, total_payable, final_total, payment_method, address, date_added)
       VALUES ($1,'9000000000',$2,0,${5:-0},0,$2,$2,'cod','test',NOW())"
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
sql "DELETE FROM referral_rewards; DELETE FROM referrals; DELETE FROM promo_code_users;"
sql "DELETE FROM promo_codes WHERE promo_code LIKE 'REF%'"
sql "DELETE FROM login_attempts"
sql "UPDATE referral_programs SET status=1, spent_to_date=0, starts_at=NULL, ends_at=NULL"
sql "UPDATE referral_milestones SET status=1, min_order_amount=499 WHERE code='first_delivered_order'"
sql "UPDATE referral_milestones SET referrer_amount=100 WHERE code IN ('first_delivered_order','kyc_shop_live')"
sql "UPDATE settings SET value='{\"withdrawable\":\"0\",\"monthly_budget_cap\":\"10000\",\"per_referrer_monthly_cap\":\"2000\",\"hold_days_after_return_window\":\"1\",\"wallet_orders_qualify\":\"1\",\"allow_negative_on_reversal\":\"0\",\"min_order_amount\":\"499\",\"promo_discount\":\"100\",\"promo_min_cart\":\"499\",\"promo_validity_days\":\"30\",\"flag_review_hold_hours\":\"24\",\"credit_expiry_months\":\"12\",\"expiry_notice_days\":\"30\",\"ambassador_cumulative\":\"1\",\"tier_counts_credited_only\":\"1\"}' WHERE variable='referral_settings'"
sql "UPDATE users SET balance=0, referral_credit=0, ambassador_tier=0 WHERE id IN (2,6,7,8)"
bust
CODE=$(sql "SELECT referral_code FROM users WHERE id=2")
SELLER_CODE=$(sql "SELECT referral_code FROM users WHERE id=7")
echo "  referrer code $CODE (user 2), seller code $SELLER_CODE (user 7)"

echo
echo "============================================================"
echo " A  ATTRIBUTION REFUSALS"
echo "============================================================"
U=$(signup "Edge One" "$CODE")

# Referring yourself. Unreachable through the signup form - a new account cannot
# know its own code - so it is driven straight at the binding function.
OWN=$(sql "SELECT referral_code FROM users WHERE id=$U")
sql "DELETE FROM referrals WHERE referee_id=$U"
expect "$($PHP index.php referral_harness bind $U $OWN)" '"reason":"self_referral"' "A1 a user cannot refer themselves"

# Two accounts, one email address. Not a mobile: users.mobile carries a UNIQUE
# index, so the database already makes that impossible - but `email` is indexed
# without being unique, so this is the shared-contact case that can actually
# happen, and the one the self-dealing check has to catch.
U2=$(signup "Edge Two" "$CODE")
sql "DELETE FROM referrals WHERE referee_id=$U2"
SHARED="shared$(date +%s)@example.com"
sql "UPDATE users SET email='$SHARED' WHERE id IN ($U,$U2)"
expect "$($PHP index.php referral_harness bind $U2 $OWN)" '"reason":"self_referral"' "A2 two accounts sharing an email cannot refer each other"
sql "UPDATE users SET email=CONCAT('edge-restored-', id, '@example.com') WHERE id IN ($U,$U2)"

# Already bound.
sql "DELETE FROM referrals WHERE referee_id=$U2"
$PHP index.php referral_harness bind $U2 $CODE >/dev/null
expect "$($PHP index.php referral_harness bind $U2 $SELLER_CODE)" '"reason":"already_referred"' "A3 a second binding on the same account is refused"

expect "$($PHP index.php referral_harness bind $U2 NOSUCH99)" '"reason":"already_referred|unknown_code"' "A4 an unknown code binds nothing"
expect "$($PHP index.php referral_harness bind 999999 $CODE)" '"reason":"unknown_referee"' "A5 a referee that does not exist is refused"
# Punctuation only: referral_normalize_code() strips everything that is not
# A-Z0-9, so this reaches the same branch as an empty field - and is a value a
# real user could actually paste.
expect "$($PHP index.php referral_harness bind $U2 ---)" '"reason":"no_code"' "A6 a code that normalises to nothing binds nothing"

echo
echo "============================================================"
echo " B  QUALIFYING-ORDER BOUNDARIES"
echo "============================================================"
B1=$(signup "Edge Min" "$CODE")
O=$(order_for $B1 499)
expect "$($PHP index.php referral_harness delivered $B1 $O)" '"reason":"reward_pending"' "B1 an order at exactly the minimum qualifies"

B2=$(signup "Edge Under" "$CODE")
O=$(order_for $B2 498.99)
expect "$($PHP index.php referral_harness delivered $B2 $O)" '"reason":"order_not_qualifying"' "B2 one paisa under the minimum does not"

# An order id that belongs to somebody else must not pay this referral.
B3=$(signup "Edge Wrong" "$CODE")
OTHER=$(order_for 8 900)
expect "$($PHP index.php referral_harness delivered $B3 $OTHER)" '"reason":"order_not_qualifying"' "B3 an order belonging to another user pays nothing"

# Wallet-paid orders: currently ON, so this checks the switch actually switches.
sql "UPDATE settings SET value=REPLACE(value,'\"wallet_orders_qualify\":\"1\"','\"wallet_orders_qualify\":\"0\"') WHERE variable='referral_settings'"; bust
B4=$(signup "Edge Wallet" "$CODE")
O=$(order_for $B4 900 "" "" 900)
expect "$($PHP index.php referral_harness delivered $B4 $O)" '"reason":"order_not_qualifying"' "B4 with the switch off, a wallet-paid order earns nothing"
sql "UPDATE settings SET value=REPLACE(value,'\"wallet_orders_qualify\":\"0\"','\"wallet_orders_qualify\":\"1\"') WHERE variable='referral_settings'"; bust
expect "$($PHP index.php referral_harness delivered $B4 $O)" '"reason":"reward_pending"' "B5 with it on, the same order does earn"

echo
echo "============================================================"
echo " C  PROGRAMME WINDOWS AND SWITCHES"
echo "============================================================"
C1=$(signup "Edge Future" "$CODE")
sql "UPDATE referral_programs SET starts_at=DATE_ADD(NOW(), INTERVAL 5 DAY) WHERE code='customer_customer'"
O=$(order_for $C1 900)
expect "$($PHP index.php referral_harness delivered $C1 $O)" '"reason":"not_referred"' "C1 a programme that has not started yet pays nothing"

sql "UPDATE referral_programs SET starts_at=NULL, ends_at=DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE code='customer_customer'"
expect "$($PHP index.php referral_harness delivered $C1 $O)" '"reason":"not_referred"' "C2 a programme that has ended pays nothing"
sql "UPDATE referral_programs SET ends_at=NULL WHERE code='customer_customer'"

sql "UPDATE referral_milestones SET status=0 WHERE code='first_delivered_order'"
expect "$($PHP index.php referral_harness delivered $C1 $O)" '"reason":"no_active_milestone"' "C3 a switched-off milestone pays nothing"
sql "UPDATE referral_milestones SET status=1 WHERE code='first_delivered_order'"

expect "$($PHP index.php referral_harness delivered $C1 $O)" '"reason":"reward_pending"' "C4 and with everything on, the same order pays"

echo
echo "============================================================"
echo " D  RELEASE AND REVERSAL"
echo "============================================================"
sql "UPDATE referral_rewards SET qualified_at=DATE_SUB(NOW(),INTERVAL 1 HOUR), flagged=0 WHERE status='pending'"
FIRST=$($PHP index.php referral_harness release)
SECOND=$($PHP index.php referral_harness release)
expect "$SECOND" '"credited":0' "D1 running the release twice credits nothing the second time"

BAL_BEFORE=$(sql "SELECT balance FROM users WHERE id=2")
$PHP index.php referral_harness release >/dev/null
expect "$(sql "SELECT balance FROM users WHERE id=2")" "^$BAL_BEFORE$" "D2 a third run does not move the balance either"

# Reversing into a wallet that has already been emptied.
sql "UPDATE users SET balance=0 WHERE id=2"
REV_ORDER=$(sql "SELECT source_order_id FROM referral_rewards WHERE status='credited' AND benefit_type='wallet' AND source_order_id IS NOT NULL ORDER BY id DESC LIMIT 1")
REV_USER=$(sql "SELECT user_id FROM orders WHERE id=$REV_ORDER")
expect "$($PHP index.php referral_harness returned $REV_USER $REV_ORDER)" '"recovered":0' "D3 reversing against an empty wallet recovers nothing"
expect "$(sql "SELECT balance FROM users WHERE id=2")" "^0.00$" "D4 and does not push the wallet negative"
expect "$(sql "SELECT reversed_shortfall FROM referral_rewards WHERE source_order_id=$REV_ORDER AND role='referrer'")" "^100.00$" "D5 the whole amount is recorded as a shortfall"

# Reversing a reward that was never paid.
P1=$(signup "Edge Pending" "$CODE")
O=$(order_for $P1 900)
$PHP index.php referral_harness delivered $P1 $O >/dev/null
expect "$($PHP index.php referral_harness returned $P1 $O)" '"reason":"reversed"' "D6 a pending reward can be reversed before it is paid"
expect "$(sql "SELECT status FROM referral_rewards WHERE source_order_id=$O AND role='referrer'")" "^reversed$" "D7 and is marked reversed, not credited"

# Reversing twice.
expect "$($PHP index.php referral_harness returned $P1 $O)" '"reason":"nothing_to_reverse"' "D8 reversing the same order twice does nothing"

echo
echo "============================================================"
echo " E  THE REFEREE'S DISCOUNT CODE"
echo "============================================================"
# Mints a fresh one rather than picking the newest coupon in the table: section D
# reverses rewards, and a reversed reward switches its coupon off - so the newest
# coupon is quite likely to be a casualty of D rather than a live code.
EU=$(signup "Edge Promo" "$CODE")
EO=$(order_for $EU 900)
$PHP index.php referral_harness delivered $EU $EO >/dev/null
sql "UPDATE referral_rewards SET qualified_at=DATE_SUB(NOW(),INTERVAL 1 HOUR), flagged=0 WHERE status='pending'"
$PHP index.php referral_harness release >/dev/null

PROMO=$(sql "SELECT p.promo_code FROM promo_codes p JOIN promo_code_users u ON u.promo_code_id=p.id WHERE u.user_id=$EU ORDER BY p.id DESC LIMIT 1")
if [ -z "$PROMO" ]; then fail "E0 a discount code was issued to the referred customer" "none found"; else
  pass "E0 a discount code was issued to the referred customer ($PROMO)"
  expect "$($PHP index.php referral_harness promo $PROMO $EU 600)" '"error":false' "E1 the owner can redeem it"
  expect "$($PHP index.php referral_harness promo $PROMO 2 600)" 'issued to another account' "E2 nobody else can"
  expect "$($PHP index.php referral_harness promo $PROMO $EU 499)" '"error":false' "E3 a cart at exactly the minimum is accepted"
  expect "$($PHP index.php referral_harness promo $PROMO $EU 498.99)" '"error":true' "E4 one paisa under is refused"
  expect "$(sql "SELECT list_promocode FROM promo_codes WHERE promo_code='$PROMO'")" "^0$" "E5 it stays out of the public coupon list"

  sql "UPDATE promo_codes SET end_date=DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE promo_code='$PROMO'"
  expect "$($PHP index.php referral_harness promo $PROMO $EU 600)" '"error":true' "E6 an expired code is refused"
  sql "UPDATE promo_codes SET end_date=DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE promo_code='$PROMO'"
  expect "$($PHP index.php referral_harness promo $PROMO $EU 600)" '"error":false' "E7 and works again once the window is restored"

  # Withdrawn with its reward: reversing the referral takes the coupon with it.
  $PHP index.php referral_harness returned $EU $EO >/dev/null
  expect "$(sql "SELECT status FROM promo_codes WHERE promo_code='$PROMO'")" "^0$" "E8 reversing the reward switches the coupon off"
  expect "$($PHP index.php referral_harness promo $PROMO $EU 600)" '"error":true' "E9 and it can no longer be redeemed"
fi

echo "============================================================"
echo " F  SELLER PROGRAMME EDGES"
echo "============================================================"
S1=$(signup "Edge Seller" "$SELLER_CODE")
sql "INSERT INTO users_groups (user_id, group_id) VALUES ($S1, 4)"
sql "INSERT INTO seller_data (user_id, store_name, slug, status) VALUES ($S1,'Edge Shop','edge-shop-$S1',2)"
sql "UPDATE referrals SET program_id=(SELECT id FROM referral_programs WHERE code='seller_seller') WHERE referee_id=$S1"

$PHP index.php referral_harness approved $S1 >/dev/null
expect "$($PHP index.php referral_harness approved $S1)" '"reason":"already_rewarded"' "F1 approving a shop twice pays once"

# A multi-seller order where this seller's own lines are under the minimum.
BUYER=$(signup "Edge Buyer" "$CODE")
O=$(order_for $BUYER 900 $S1 60)
$PHP index.php referral_harness delivered $BUYER $O >/dev/null
SALE=$(sql "SELECT COUNT(*) FROM referral_rewards rw JOIN referral_milestones m ON m.id=rw.milestone_id JOIN referral_programs p ON p.id=m.program_id WHERE p.code='seller_seller' AND m.code='first_delivered_order' AND rw.beneficiary_id=7")
expect "$SALE" "^0$" "F2 a seller's Rs 60 line inside a Rs 900 order does not pay the sale milestone"

O=$(order_for $BUYER 900 $S1 900)
$PHP index.php referral_harness delivered $BUYER $O >/dev/null
SALE=$(sql "SELECT COUNT(*) FROM referral_rewards rw JOIN referral_milestones m ON m.id=rw.milestone_id JOIN referral_programs p ON p.id=m.program_id WHERE p.code='seller_seller' AND m.code='first_delivered_order' AND rw.beneficiary_id=7")
expect "$SALE" "^1$" "F3 a qualifying line does pay it"

# A customer-referred account must not earn the seller sale milestone.
C2U=$(signup "Edge Cust Seller" "$CODE")
sql "INSERT INTO users_groups (user_id, group_id) VALUES ($C2U, 4)"
sql "INSERT INTO seller_data (user_id, store_name, slug, status) VALUES ($C2U,'Cust Shop','cust-shop-$C2U',1)"
O=$(order_for $BUYER 900 $C2U 900)
$PHP index.php referral_harness delivered $BUYER $O >/dev/null
WRONG=$(sql "SELECT COUNT(*) FROM referral_rewards rw JOIN referral_milestones m ON m.id=rw.milestone_id JOIN referral_programs p ON p.id=m.program_id WHERE p.code='seller_seller' AND rw.beneficiary_id=2")
expect "$WRONG" "^0$" "F4 someone referred as a customer does not trigger the seller programme"

echo
echo "============================================================"
echo " G  SPEND-ONLY BOUNDARIES"
echo "============================================================"
sql "UPDATE users SET balance=500, referral_credit=200 WHERE id=7"
expect "$($PHP index.php referral_harness withdrawable 7)" "^300" "G1 withdrawable is balance minus referral credit"
expect "$($PHP index.php referral_harness withdraw 7 300)" '"error":false' "G2 withdrawing exactly the withdrawable amount is allowed"
sql "DELETE FROM payment_requests WHERE user_id=7"
sql "UPDATE users SET balance=500, referral_credit=200 WHERE id=7"
expect "$($PHP index.php referral_harness withdraw 7 300.01)" 'referral credit' "G3 one paisa more is refused, and says why"
sql "DELETE FROM payment_requests WHERE user_id=7"

# Spending drains the restricted part first, and stops at zero.
sql "UPDATE users SET balance=250, referral_credit=200 WHERE id=7"
$PHP index.php referral_harness spend 7 220 >/dev/null
expect "$(sql "SELECT CONCAT(balance,'|',referral_credit) FROM users WHERE id=7")" "^30.00\|0.00$" "G4 a debit larger than the restricted part floors it at zero"

echo
echo "============================================================"
echo " H  ACCESS CONTROL"
echo "============================================================"
expect "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/referral_harness/release")" "^404$" "H1 the CLI harness is not reachable over HTTP"

# Signed out: the admin screens must not answer with data.
for path in "admin/referral/programs" "admin/referral/ledger_list" "admin/referral/rewards_list"; do
  BODY=$(curl -s "$BASE/$path")
  if echo "$BODY" | grep -q '"rows"\|czr-card'; then fail "H2 $path leaks to a signed-out visitor" "returned content"; else pass "H2 $path gives a signed-out visitor nothing"; fi
done

# A referral code is user input that reaches a LIKE search on the admin ledger.
T=$(curl -s -b $A -c $A "$BASE/admin/login" -o /tmp/edge_l.html; grep -oE "name='ekart_security_token' value='[^']*'" /tmp/edge_l.html | head -1 | sed "s/.*value='\(.*\)'/\1/")
curl -s -b $A -c $A -X POST "$BASE/auth/login" -d "identity=9910919480" -d "password=TestPass123!" -d "ekart_security_token=$T" -o /dev/null
INJ=$(curl -s -b $A "$BASE/admin/referral/ledger_list?limit=10&offset=0&search=%27%20OR%20%271%27%3D%271")
expect "$INJ" '"total":0|"rows":\[\]' "H3 a quote-injection search returns nothing rather than everything"
expect "$(curl -s -b $A "$BASE/admin/referral/ledger_list?limit=10&offset=0&sort=id%3B%20DROP%20TABLE%20users" | head -c 40)" '"total"' "H4 an injected sort column is ignored, not executed"
expect "$(sql "SHOW TABLES LIKE 'users'")" "users" "H5 and the users table is still there"

echo
echo "============================================================"
if [ "$FAILED" = "0" ]; then printf " \033[32mALL EDGE CASES PASSED\033[0m\n"; else printf " \033[31m%s EDGE CASE(S) FAILED\033[0m\n" "$FAILED"; fi
echo "============================================================"
