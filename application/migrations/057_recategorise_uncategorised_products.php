<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Sorts the products that migration 056 parked in "Uncategorised" into the store's real categories.
 *
 * 056 rescued 177 products whose category_id pointed at a category that does not exist - without it
 * they were invisible in the shop - but it deliberately did not guess where they belonged. This one
 * does the sorting, from the product names, which on this catalogue are unusually descriptive
 * ("Beaded Butterfly Stud Earrings", "Underwater Ocean Ceramic Mugs Set", "Eucalyptus Mint Natural
 * Soap Bar"). Dry-run over all 90 distinct names before writing this: 88 matched, and every match
 * was checked by hand.
 *
 * Two design points worth knowing if you change the rules:
 *
 *   1. FIRST MATCH WINS and the list runs most-specific first. That ordering is doing real work.
 *      "Ocean Wave Resin Hoop Earrings" must land in Earrings rather than Homedecor, and
 *      "Underwater Ocean Ceramic Mugs Set" in Kitchen and Dining rather than Pottery - both names
 *      also match broader rules further down the list.
 *
 *   2. Anything no rule matches is LEFT in Uncategorised on purpose. Two products end up there
 *      ("Crochet Reindeer Christmas Gift Set", "Crochet Woodland Animal Friends Set") because they
 *      genuinely could be a toy, a decoration or a gift, and an obviously-unsorted product is
 *      better than one filed somewhere wrong. Uncategorised stays as the place to find them.
 *
 * Targets subcategories rather than top-level ones, since a subcategory is what actually makes a
 * product browsable and puts it in the mega menu.
 *
 * Only ever moves products that are still sitting in Uncategorised, so re-running is a no-op and
 * anything recategorised by hand afterwards is never touched again.
 */
class Migration_recategorise_uncategorised_products extends CI_Migration
{
    const UNCATEGORISED_SLUG = 'uncategorised';

    /**
     * [ name pattern, category slug, human label ] - matched in order, first match wins.
     *
     * Categories are resolved by SLUG, not by hard-coded id: this has to run against production,
     * where the ids differ from the machine the rules were written on.
     */
    private function rules()
    {
        return [
            // jewellery - specific item words before any material or theme rule
            ['/\bearrings?\b/i',                        'earrings'],
            ['/necklace|\bchoker\b/i',                  'necklaces-1'],
            ['/\bbracelet\b/i',                         'bracelets-1'],
            ['/\bring collection\b|\bhoop\b/i',         'rings'],

            // a "making supplies" kit is a craft supply, not the finished piece
            ['/\bmaking supplies\b|\bsupplies kit\b/i',  'beads-and-jewellery-making'],

            // footwear
            ['/\bmen casual ankle boots?\b|\bmen ankle boots?\b/i', 'mens-footwear-1'],
            ['/\bsandals?\b|\bmary jane\b/i',            'womens-footwear-1'],
            ['/\bboots?\b|\bshoes?\b/i',                 'casual-footwear-1'],

            // bags
            ['/\btote bag\b|\bsling bag\b|\bboho bag\b|\bhandbag\b/i', 'handbags'],

            // tableware, before the generic ceramic rule below
            ['/\bmugs?\b|\bcoaster|\bplates?\b|\bbowl\b|\bserving\b/i', 'kitchen-and-dining'],

            // candles
            ['/\bcandle holder\b|\bsimmer pot\b/i',      'candles-and-fragnances-1'],

            // furniture and lighting
            ['/\bstool\b|\bchest of drawers\b|\bpendant light\b|\blight shade\b/i', 'furniture-and-lighting-1'],

            // beauty
            ['/\blip balm\b/i',                          'makeup'],
            ['/\bface mask\b|\boil roller/i',            'skin-care'],
            ['/\bsoap\b|\bbath oil\b|\bsugar scrub\b/i', 'bath-and-body'],

            // children's gifts
            ['/\bbaby (gift|overalls)\b|\brattle\b|\bdolls?\b/i', 'gifts-for-kids'],

            // clothing
            ['/\blehenga\b|\bdress\b|\bcardigan\b|\bshrug\b|\bsweater\b/i', 'womens-wear'],

            // home decor - broad catch-all for wall and decorative pieces
            ['/\bwall hangings?\b|\bwall art\b|\bdecor\b|\bdream catcher\b|\bbottles?\b|\bmagnet\b|\bbookends\b|\bclock\b/i', 'homedecor-1'],

            // art and collectibles - material-led, only after everything specific
            ['/\bceramic\b|\bpottery\b|\bclay\b/i',      'pottery-and-cremics'],
            ['/\bresin art\b|\bfelt art\b|\bart set\b|\bart plate\b/i', 'others'],

            // jewellery sets last, so a specific item word always wins first
            ['/\bjewelry (set|collection)\b/i',          'jewellery-and-accessories-1'],
        ];
    }

    public function up()
    {
        if (!$this->db->table_exists('products') || !$this->db->table_exists('categories')) {
            return;
        }

        $uncategorised = $this->db->select('id')
            ->where('slug', self::UNCATEGORISED_SLUG)
            ->get('categories')->row_array();
        if (empty($uncategorised['id'])) {
            return; // 056 never ran, or the category was removed - nothing to sort
        }
        $uncategorised_id = (int) $uncategorised['id'];

        // Resolve every rule's slug to an id once. A slug that does not exist on this install is
        // skipped rather than fataling the migration - category slugs are editable in the admin.
        $resolved = [];
        foreach ($this->rules() as $rule) {
            list($pattern, $slug) = $rule;
            if (!isset($resolved[$slug])) {
                $row = $this->db->select('id')->where('slug', $slug)->get('categories')->row_array();
                $resolved[$slug] = !empty($row['id']) ? (int) $row['id'] : null;
                if ($resolved[$slug] === null) {
                    log_message('error', 'Migration 057: category slug "' . $slug . '" not found; its rule is skipped.');
                }
            }
        }

        $products = $this->db->select('id, name')
            ->where('category_id', $uncategorised_id)
            ->get('products')->result_array();

        $moved = 0;
        $left = 0;
        foreach ($products as $product) {
            $target = null;
            foreach ($this->rules() as $rule) {
                list($pattern, $slug) = $rule;
                if (empty($resolved[$slug])) {
                    continue;
                }
                if (preg_match($pattern, (string) $product['name'])) {
                    $target = $resolved[$slug];
                    break; // first match wins
                }
            }

            if ($target === null || $target === $uncategorised_id) {
                $left++;
                continue;
            }

            $this->db->where('id', $product['id'])->update('products', ['category_id' => $target]);
            $moved++;
        }

        log_message('error', 'Migration 057: recategorised ' . $moved . ' product(s); ' . $left
            . ' left in Uncategorised for a human to place.');
    }

    public function down()
    {
        // Sending them back to Uncategorised would undo real categorisation without restoring any
        // information - the ids they held before 056 pointed at categories that never existed.
    }
}
