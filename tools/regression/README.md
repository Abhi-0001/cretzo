# Regression harness (not deployed, not routable)

`Perfcheck.php.harness` is the verification controller used for the performance work.
It is kept OUT of `application/controllers/` deliberately: anything in that directory is
routable by CodeIgniter, and a debug harness has no business shipping to production.

## To re-run the regression suite

```bash
cp tools/regression/Perfcheck.php.harness application/controllers/Perfcheck.php

php index.php perfcheck snapshot   > /tmp/a.txt
diff <(grep -v QUERIES docs/performance/baseline-fingerprints.txt) <(grep -v QUERIES /tmp/a.txt)

php index.php perfcheck orders     > /tmp/b.txt
diff <(grep -v QUERIES docs/performance/baseline-orders.txt) <(grep -v QUERIES /tmp/b.txt)

php index.php perfcheck categories   # expects "mismatches: 0 => IDENTICAL"
php index.php perfcheck brands       # compare against docs/performance/baseline-brands.txt

rm application/controllers/Perfcheck.php     # <- always remove it again
```

A clean diff on the first two, `mismatches: 0` on the third, and identical brand
fingerprints means every optimised read path still returns byte-identical data.

The harness is CLI-only (`show_404()` on any web request) and carries the standard
`defined('BASEPATH') or exit` guard, so even the copy in this directory cannot be
executed by fetching it over HTTP.
