<?php
/**
 * Shared SEO helpers for product.php, category.php, guide-post.php and
 * sitemap.php: server-side rendering of the same markup catalog.js builds
 * in the browser, plus category / subcategory copy.
 */

function bl_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Matches money() in catalog.js so server and client output are identical.
function bl_money($n) { return '$' . number_format((float)$n, 2, '.', ''); }

function bl_clean_text($text, $max = 0) {
    $t = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$text)));
    if ($max > 0 && mb_strlen($t) > $max) {
        $cut = mb_substr($t, 0, $max - 1);
        $sp = mb_strrpos($cut, ' ');
        if ($sp !== false && $sp > $max * 0.6) { $cut = mb_substr($cut, 0, $sp); }
        $t = rtrim($cut, " ,;:.-") . '…';
    }
    return $t;
}

function bl_404() {
    http_response_code(404);
    header('X-Robots-Tag: noindex, follow');
}

/** Tiny inline handler: webp -> jpg -> hidden. catalog.js redefines it with a placeholder image. */
function bl_img_fallback_script() {
    return "<script>window.blImgFallback=function(i,j){if(!i.dataset.f){i.dataset.f=1;i.removeAttribute('srcset');i.src=j;}else{i.onerror=null;i.style.visibility='hidden';}};</script>\n";
}

/** Same placeholder as placeholderDataURI() in catalog.js. */
function bl_placeholder_uri() {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><rect width="200" height="200" fill="#EDF6EF"/><g fill="none" stroke="#5C625E" stroke-width="2"><circle cx="100" cy="90" r="34"/><path d="M60 150h80M70 150v-14M130 150v-14"/></g><text x="100" y="180" text-anchor="middle" font-family="sans-serif" font-size="11" fill="#5C625E">photo pending</text></svg>';
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

/** Responsive product image: 400w/800w webp with original jpg as fallback. */
function bl_img($id, $name, $class = '', $eager = false, $sizes = '(max-width: 600px) 46vw, 300px') {
    $enc = rawurlencode($id);
    $base = 'assets/img/products/';
    $jpg  = '/' . $base . $enc . '.jpg';
    if (!file_exists(__DIR__ . '/../' . $base . $id . '.jpg')) {
        // No photo uploaded yet: never point crawlers at a URL that 404s.
        return '<img class="' . bl_e(trim($class . ' img-placeholder')) . '" src="' . bl_e(bl_placeholder_uri()) . '" width="200" height="200" alt="' . bl_e($name) . '" loading="lazy" decoding="async">';
    }
    $w400 = '/' . $base . 'w400/' . $enc . '.webp';
    $w800 = '/' . $base . 'w800/' . $enc . '.webp';
    return '<img class="' . bl_e($class) . '" src="' . $w400 . '" srcset="' . $w400 . ' 400w, ' . $w800 . ' 800w" sizes="' . bl_e($sizes) . '"'
        . ' width="400" height="400" alt="' . bl_e($name) . '" '
        . ($eager ? 'fetchpriority="high"' : 'loading="lazy"') . ' decoding="async"'
        . ' onerror="blImgFallback(this,\'' . $jpg . '\')">';
}

/** Same markup as productCardHTML() in catalog.js. */
function bl_card($p, $eager = false) {
    $sale = ($p['sale_price'] !== null && $p['sale_price'] !== '');
    $tag = !empty($p['tag']) ? '<span class="' . ($p['tag'] === 'Sale' ? 'tag tag-sale' : 'tag') . '">' . bl_e($p['tag']) . '</span>' : '';
    $best = !empty($p['best_seller']) ? '<span class="tag tag-best-seller">Best Seller</span>' : '';
    $price = $sale
        ? '<span class="price">' . bl_money($p['sale_price']) . '</span><span class="price-was">' . bl_money($p['price']) . '</span>'
        : '<span class="price">' . bl_money($p['price']) . '</span>';
    return '<a href="/product/' . bl_e($p['id']) . '" class="product-card">'
        . '<div class="thumb"><div class="tag-stack">' . $best . $tag . '</div>' . bl_img($p['id'], $p['name'], '', $eager) . '</div>'
        . '<div class="body"><div class="vendor">' . bl_e($p['vendor']) . '</div><h3>' . bl_e($p['name']) . '</h3>'
        . '<div class="price-row">' . $price . '</div></div></a>';
}

function bl_json_list($raw) {
    $v = json_decode((string)$raw, true);
    return is_array($v) ? $v : [];
}

/** Meta description for a product: unique (leads with the product name), padded if short, max 155 chars. */
function bl_product_meta_desc($p, $catLabel) {
    $d = bl_clean_text($p['blurb']);
    if (stripos($d, $p['name']) === false) { $d = $p['name'] . ': ' . $d; }
    if (mb_strlen($d) < 110) {
        $d = rtrim($d, ' .') . '. Shop ' . $p['vendor'] . ' ' . strtolower($catLabel) . ' at Bedrock Lapidary.';
    }
    return bl_clean_text($d, 155);
}

// ---------------------------------------------------------------------------
// Category copy (H1 / title / description / intro / buying tips)
// ---------------------------------------------------------------------------
function bl_category_seo($slug, $label) {
    static $c = [
        'saws' => ['Lapidary Saws', 'Lapidary Saws: Slab, Trim & Band Saws',
            'Shop lapidary saws and diamond saw blades: slab saws, trim saws, ring saws and band saws for cutting agate, jasper, geodes and more.',
            'Every lapidary workflow starts with a saw. Slab saws cut large rough into slabs, trim saws cut smaller pieces and shape preforms, and ring and band saws handle intricate cuts. Choose blade size, feed style and coolant setup to match the material you cut most.'],
        'grinding-polishing' => ['Lapidary Grinding & Polishing Equipment', 'Lapidary Grinding & Polishing Equipment',
            'Lapidary grinders, diamond grinding wheels, polishing pads, wet belt sanders and polishing compounds for shaping and finishing cabochons and stone.',
            'Shaping and polishing turns a sawn preform into a finished stone. Browse grinder-polisher units, diamond and silicon carbide wheels, buffs, felt and cork polishing wheels, wet belt sanders, and the polishes that bring out the final shine.'],
        'lap-machines' => ['Flat Lap Machines & Lapidary Laps', 'Flat Lap Machines & Lapidary Laps',
            'Flat lap machines, vibrating laps, slant cabbers and lap discs for flattening, smoothing and polishing gemstones, glass and crystal.',
            'Flat laps are used to flatten, smooth and polish stone, glass and crystal to a fine finish. Compare rotary flat laps, automatic vibrating laps and slant cabbers, plus the lap discs, plates and pads that go with them.'],
        'tumblers' => ['Rock Tumblers & Tumbling Supplies', 'Rock Tumblers, Barrels & Tumbling Media',
            'Rock tumblers from Lortone, Covington and Tumble-Bee, plus replacement barrels, tumbling media and grit and polish kits.',
            'Rotary rock tumblers turn rough stone into polished stones over several grit stages. Pick a barrel size for the volume you tumble, then stock up on grit, polish and finishing media.'],
        'glass' => ['Glass Working Equipment', 'Glass Grinders, Lathes & Cutters',
            'Glass grinders, cutters, lathes, bevelers and polishers for stained glass, cold working and glass fabrication.',
            'Cold-working glass takes the right tools. Browse glass grinders, scoring cutters, lathes and bevelers, along with the polishers and accessories that finish edges cleanly.'],
        'tools' => ['Lapidary Tools & Diamond Burs', 'Lapidary Tools & Diamond Burs',
            'Lapidary hand tools, diamond burs, core drill bits, calipers, rock picks and hammers for cutting, carving, drilling and collecting.',
            'Hand tools and drill bits for carving, drilling and measuring stone, plus rock picks and hammers for the field.'],
        'accessories' => ['Lapidary Machine Accessories & Replacement Parts', 'Lapidary Machine Parts & Accessories',
            'Replacement motors, pumps, flanges, pulleys, drive belts and accessories for Covington, Lortone, Gemini and Hi-Tech lapidary machines.',
            'Keep your machines running with replacement motors, pumps, plumbing, flanges, spindles, pulleys and belts.'],
        'supplies' => ['Lapidary Supplies: Grit, Compounds, Discs & Belts', 'Lapidary Supplies: Grit, Compound & Belts',
            'Silicon carbide grit, diamond compounds, sanding discs and belts, polishing discs, dop wax, epoxy and other lapidary consumables.',
            'Consumables you will reorder often: silicon carbide grit and tumbling kits, diamond compounds, sanding belts and discs, polishing discs, dop wax and adhesives.'],
        'books' => ['Lapidary, Rockhounding & Gem Trail Books', 'Lapidary & Rockhounding Books',
            'Books on cabochon cutting, tumbling, faceting and diamond abrasives, plus gem trail and rockhounding guides for the western United States.',
            'Learn the craft and find the material: cabochon cutting instruction, tumbling and abrasive guides, and regional gem trail and rockhounding guides.'],
    ];
    if (isset($c[$slug])) {
        return ['h1' => $c[$slug][0], 'title' => $c[$slug][1], 'desc' => $c[$slug][2], 'intro' => $c[$slug][3]];
    }
    return ['h1' => $label, 'title' => $label,
        'desc' => 'Shop ' . $label . ' at Bedrock Lapidary: equipment and supplies for cutting, grinding, and polishing stone and glass.',
        'intro' => ''];
}

// ---------------------------------------------------------------------------
// Subcategory ("type") pages, driven by product-name rules so no DB change is
// needed. Positional fields:
//  0 label  1 H1  2 include regex  3 exclude regex  4 meta description
//  5 intro  6 categories searched  7 buying notes (paragraphs split by blank line)
//  8 related guide slugs  9 short <title> (optional; falls back to the H1)
// ---------------------------------------------------------------------------
function bl_types($cat) {
    static $t = null;
    if ($t === null) {
        $t = [
        'saws' => [
            'slab-saws' => ['Slab Saws', 'Lapidary Slab Saws', '/slab saw\b/i', '/stand|blade|kit|fence|pulley/i',
                'Lapidary slab saws and combination trim and slab saws from 10 to 36 inches for cutting rough rock, agate and geodes into slabs.',
                'A slab saw cuts large rough into slabs. Larger blades cut deeper, and a power feed keeps cuts straight and consistent.',
                ['saws'],
                "Blade diameter sets how large a rock you can cut. As a rule of thumb, the usable depth of cut is roughly a third to two-fifths of the blade diameter, so a 10 inch blade suits fist-sized rough while 18 inch and larger saws handle big nodules and slabs.\n\nLook at the feed system (a power feed keeps cuts straight and repeatable), how the vise holds the rock, and whether the saw uses oil or water-based coolant. Blades are sold separately on most models, so budget for a diamond blade that matches the saw's arbor size.",
                ['slab-saw-vs-trim-saw', 'how-to-choose-a-lapidary-saw', 'diamond-saw-blade-guide'], 'Lapidary Slab Saws'],
            'trim-saws' => ['Trim Saws', 'Lapidary Trim Saws', '/trim saw\b/i', '/stand|vise|attachment|blade/i',
                'Trim saws for cutting small stones, trimming slabs and shaping preforms, including glass trim saws and combination units.',
                'Trim saws handle smaller pieces and precise cuts, making them the usual first saw for cabochon work.',
                ['saws'],
                "A trim saw uses a smaller blade, typically around 6 inches, and cuts slabs down into cabochon blanks, trims away flaws and cuts small pieces. The thin blade wastes little material, which matters when working with valuable rough.\n\nCheck the vise (it holds small stones while you feed them into the blade), whether the saw is built for rock or for glass, and the blade size. A trim saw with a power feed gives more consistent cuts.",
                ['slab-saw-vs-trim-saw', 'how-to-choose-a-lapidary-saw', 'lapidary-saw-safety-and-coolant'], ''],
            'band-saws' => ['Band Saws', 'Diamond Band Saws & Blades', '/band ?saw/i', '',
                'Diamond band saws and replacement band saw blades for intricate lapidary cuts.',
                'Band saws make curved and intricate cuts that circular blades cannot.',
                ['saws'],
                "A diamond band saw cuts curves and intricate outlines that a circular blade cannot follow, so it suits carving preforms and irregular shapes. Match the blade to the saw and to the material: diamond blades are for stone and glass, while metal and wood blades are for other work.",
                ['how-to-choose-a-lapidary-saw', 'diamond-saw-blade-guide'], ''],
            'ring-saws' => ['Ring Saws', 'Ring Saws & Ring Saw Blades', '/ring saw|gemini/i', '/kit|tool|belt|tensioner|grommet|keel|tray/i',
                'Gemini ring saws, blades and cartridges for cutting cabochon blanks, carving and intricate shapes in stone and glass.',
                'Ring saws use a diamond-coated ring blade to cut curves and complex shapes with very little waste.',
                ['saws'],
                "A ring saw uses a diamond-coated ring blade rather than a solid disc, which lets it cut tight curves, hollows and inside shapes with very little waste. It is popular for cabochon blanks, carving preforms and freeform cutting. Blades and cartridges wear with use, so check which blade type your model takes.",
                ['how-to-choose-a-lapidary-saw', 'diamond-saw-blade-guide'], ''],
            'saw-blades' => ['Saw Blades', 'Lapidary Diamond Saw Blades', '/blade/i', '',
                'Sintered and electroplated lapidary diamond saw blades for slab saws, trim saws, ring saws and band saws.',
                'Match blade diameter, arbor size and bond type to your saw and the hardness of the material.',
                ['saws'],
                "Start with your saw's blade diameter and arbor (center hole) size, then choose the bond. Sintered blades carry diamond throughout the rim and last a long time, while electroplated blades cut fast but the diamond layer wears away. Thinner rims waste less material; sturdier rims suit heavy cutting.\n\nBlades are meant to run wet, so keep the coolant topped up and feed the rock gently to avoid glazing the rim.",
                ['diamond-saw-blade-guide', 'lapidary-saw-safety-and-coolant', 'how-to-choose-a-lapidary-saw'], 'Lapidary Diamond Saw Blades'],
        ],
        'grinding-polishing' => [
            'cabbing-machines' => ['Cabbing Machines', 'Cabbing Machines & Grinder Polishers', '/grinder polisher|grinder-polisher|slant cabber|combination unit|combo unit/i', '/attachment|stand|pad|wheel|belt/i',
                'Cabbing machines: grinder polishers, combination units and slant cabbers from CabKing, Covington and Hi-Tech Diamond for shaping and polishing cabochons.',
                'A cabbing machine shapes and polishes cabochons on a stack of diamond wheels and polishing pads, all in one machine.',
                ['grinding-polishing', 'lap-machines'],
                "A cabbing machine stacks several grinding and polishing wheels on one shaft, so you move from coarse shaping to fine polish without changing tools. Most run wet, using a water feed to keep the stone and wheels cool.\n\nCompare wheel diameter (6 and 8 inch are common), motor power, how many wheels are included and whether the machine has a splash guard and water system. If you want to grind at an angle to a flat diamond disc instead, look at a slant cabber.",
                ['what-is-a-cabbing-machine', 'how-to-cut-a-cabochon', 'flat-lap-vs-cabbing-machine'], 'Cabbing Machines & Grinder Polishers'],
            'arbors' => ['Arbors & Drum Units', 'Lapidary Arbors & Drum Units', '/arbor|drum/i', '',
                'Open arbors and expandable drum units for grinding and polishing wheels.',
                'Arbors and drum units hold a stack of wheels so you can move from coarse shaping to fine polishing.',
                ['grinding-polishing'],
                "An arbor is a motorized shaft that holds grinding and polishing wheels. Expandable drums hold sanding belts and polishing wheels that grip as the drum expands. Check the shaft size and how many stations you need before ordering wheels.",
                ['what-is-a-cabbing-machine', 'how-to-polish-cabochons'], ''],
            'grinding-wheels' => ['Grinding Wheels', 'Diamond & Silicon Carbide Grinding Wheels', '/(grinding|diamond|sintered|radius|point|engraving|carving) wheel|REZ diamond wheels/i', '',
                'Sintered and resin diamond grinding wheels and silicon carbide wheels in 4, 6, 8 and 10 inch sizes.',
                'Diamond wheels cut faster and last longer than silicon carbide. Grit selection controls how quickly you shape stone and how fine the surface is.',
                ['grinding-polishing'],
                "Diamond wheels cut faster, stay true longer and suit hard materials, while silicon carbide wheels cost less but wear and need dressing. Match wheel diameter and arbor size to your machine, then pick grits from coarse (fast shaping) to fine (smoothing before polish).",
                ['diamond-vs-silicon-carbide-grinding-wheels', 'how-to-cut-a-cabochon'], 'Diamond & Silicon Carbide Grinding Wheels'],
            'polishing-wheels' => ['Polishing Wheels', 'Polishing Wheels, Pads & Buffs', '/(polishing|buff|felt|cork).*(wheel|pad|cone|disc)|buff wheel|spiral buff/i', '',
                'Felt, cork, canvas and cotton buff polishing wheels and pads for final polishing.',
                'The final polish depends on pairing the right wheel with the right compound, such as cerium oxide or tin oxide.',
                ['grinding-polishing'],
                "Felt, cork, leather, canvas and cotton buffs each pair with different compounds. Use a separate wheel for each compound so they do not mix, and match the wheel diameter and arbor to your machine.",
                ['how-to-polish-cabochons', 'how-to-polish-rocks'], ''],
            'polishing-compounds' => ['Polishing Compounds', 'Polishing Compounds: Cerium Oxide, Tin Oxide & More', '/polish|rouge|tripoli|pumice|cerium|alumina|chrome oxide|burnishing|tin oxide|oxide/i', '/wheel|pad|disc|cone|buff|felt|cork|belt|flexwheel|drum|attachment|sander|carving|grinding|grinder/i',
                'Cerium oxide, tin oxide, alumina, tripoli, rouge, pumice and chrome oxide polishing compounds for stone, glass and metal.',
                'The final shine depends on the compound. Different materials respond to different polishes.',
                ['grinding-polishing'],
                "Cerium oxide is a popular polish for quartz-family stones and glass, tin oxide is often used on harder-to-polish material, and alumina and chrome oxide are other finishing options. Pumice and tripoli are coarser pre-polishes. Keep each compound with its own wheel or pad, and rinse the stone between steps.",
                ['how-to-polish-cabochons', 'how-to-polish-rocks'], ''],
            'sanders' => ['Sanders', 'Wet Belt Sanders for Lapidary', '/sander/i', '',
                'Covington wet belt sanders, stands and back plates for shaping and smoothing stone.',
                'Wet belt sanders shape and smooth stone quickly while water keeps the work cool and controls dust.',
                ['grinding-polishing'],
                "A wet belt sander removes material quickly and smooths stone with sanding belts, while water keeps the work cool and the dust down. Consider belt width, whether the sander is a tabletop or stand-mounted model, and belt grits available for your machine.",
                ['how-to-cut-a-cabochon', 'best-lapidary-equipment-for-beginners'], ''],
        ],
        'lap-machines' => [
            'flat-lap-machines' => ['Flat Lap Machines', 'Flat Lap Machines', '/flat lap|maxi lap|rociprolap/i', '',
                'Covington, Hi-Tech Diamond and Ameritool flat lap machines for flattening and polishing stone, glass and crystal.',
                'Flat lap machines flatten and polish stone on a spinning disc. Disc diameter sets how large a piece you can work.',
                ['lap-machines'],
                "Disc diameter sets how large a piece you can flatten, and larger machines suit slabs and big flat surfaces. Rotary flat laps spin a disc while you hold the work; vibrating laps move the work for you. Budget for discs, grits and polishes as well as the machine.",
                ['how-to-choose-a-flat-lap', 'flat-lap-vs-cabbing-machine'], 'Flat Lap Machines'],
            'vibrating-lap-machines' => ['Vibrating Lap Machines', 'Vibrating Lap Machines', '/vibra lap|vibrating/i', '',
                'Automatic Vibra Lap vibrating flat lap machines in 8 to 16 inch sizes, plus replacement parts.',
                'Vibrating laps move the work automatically, so you can leave a batch to run while you do something else.',
                ['lap-machines'],
                "Automatic vibrating laps move the work across the surface for you, which frees your hands during long flattening or polishing runs. Choose the pan size for the pieces you lap most, and keep spare pans and discs on hand.",
                ['how-to-choose-a-flat-lap'], ''],
            'lap-disks' => ['Lap Disks', 'Lap Disks, Plates & Pads', '/backing plate|polishing pads|replacement plate|disc/i', '',
                'Replacement plates, backing plates and polishing pads for flat lap machines.',
                'Lap disks and pads wear out with use, so keep spares on hand for your machine.',
                ['lap-machines'],
                "Check the disc diameter and mounting style of your machine before ordering, and match the abrasive to the job: coarser for flattening, finer for smoothing and polishing.",
                ['how-to-choose-a-flat-lap'], ''],
            'slant-cabbers' => ['Slant Cabbers', 'Slant Cabbers', '/slant cabber/i', '',
                'Hi-Tech Diamond slant cabbers for shaping cabochons of rock, mineral, glass and crystal.',
                'A slant cabber presents the stone at an angle to a diamond disc to shape a domed cabochon.',
                ['lap-machines'],
                "A slant cabber holds the stone at an angle against a diamond disc to shape a domed cabochon. Models are available for rock and mineral or for glass and crystal, so choose the version that matches your material.",
                ['what-is-a-cabbing-machine', 'flat-lap-vs-cabbing-machine'], ''],
        ],
        'tumblers' => [
            'rotary-tumblers' => ['Rotary Tumblers', 'Rotary Rock Tumblers', '/tumbler/i', '/barrel|pulley|liner|gasket|kit|shot|pellet|shell|media/i',
                'Lortone, Covington and Tumble-Bee rotary rock tumblers from one-quart hobby sizes to 40 pound production tumblers.',
                'Rotary tumblers roll stone with grit inside a barrel. Multi-barrel models let you run several batches at once.',
                ['tumblers'],
                "Barrel size determines how much rock you can tumble per run, from one-quart hobby barrels to gallon barrels and production machines. Multi-barrel models let you run separate grit stages at once. Rotary tumblers run continuously for days, so consider noise and where you will place the machine.\n\nRemember that grit, polish and media are needed as well as the tumbler itself.",
                ['choosing-your-first-rotary-tumbler', 'how-to-use-a-rock-tumbler', 'rotary-vs-vibratory-tumbler'], 'Rotary Rock Tumblers'],
            'tumbling-grit-polish' => ['Grit & Polish Kits', 'Rock Tumbler Grit & Polish', '/tumbler.*(grit|polish|kit)|^step [12] |grit (&|and) polish kit/i', '',
                'Rock tumbler grit and polish: starter and deluxe grit kits and silicon carbide grit for each stage of tumbling.',
                'Tumbling works in stages, each with a finer grit than the last, followed by polish.',
                ['tumblers', 'supplies'],
                "A standard tumbling run uses a coarse grit, a medium grit, a fine or pre-polish grit and then a polish. A kit covers the stages in the right order, and buying grit by stage lets you restock only what you use. Clean the barrel and stones thoroughly between stages so coarse grit does not scratch the polish.",
                ['rock-tumbler-grit-guide', 'how-long-does-rock-tumbling-take', 'how-to-use-a-rock-tumbler'], 'Rock Tumbler Grit & Polish'],
            'tumbling-media' => ['Tumbling Media', 'Tumbling Media: Shot, Pellets, Walnut Shell & Ceramic', '/walnut shell|shot|pellet|ceramic finishing media/i', '',
                'Stainless and carbon steel shot, plastic pellets, walnut shell and ceramic tumbling media for cushioning, burnishing and polishing.',
                'Media fills space in the barrel, cushions the stones and helps carry polish.',
                ['tumblers'],
                "Plastic pellets cushion stones and carry polish during the final stage, walnut shell is used for drying and polishing, and steel shot is commonly used to burnish metal and jewelry. Ceramic media is used for deburring and finishing. Choose media for the job, not just the price.",
                ['rock-tumbler-grit-guide', 'how-to-use-a-rock-tumbler'], ''],
            'tumbler-parts' => ['Tumbler Parts', 'Rock Tumbler Parts & Replacement Barrels', '/barrel|tumbler.*(pulley|liner|gasket|motor|belt|repair)|liner and gasket|lortone.*(motor|belt)|replacement motor for lortone|v-belts|drive belts/i', '/saw|lap machine|glass/i',
                'Replacement barrels, motors, pulleys, belts, liners and gaskets for Lortone, Covington and Tumble-Bee rock tumblers.',
                'Keep your tumbler running with the right replacement barrel, motor, pulley, belt or liner.',
                ['tumblers', 'accessories', 'supplies', 'tools'],
                "Match the part to the tumbler model and size before you order. Barrels and liners wear with use, belts stretch and motors eventually fail, so having a spare belt or barrel on hand avoids downtime. Extra barrels also let you run separate grit stages without cross-contamination.",
                ['how-to-use-a-rock-tumbler', 'choosing-your-first-rotary-tumbler'], 'Rock Tumbler Parts & Replacement Barrels'],
        ],
        'accessories' => [
            'tumbler-motors' => ['Tumbler & Machine Motors', 'Replacement Motors for Tumblers & Lapidary Machines', '/motor/i', '',
                'Replacement motors for Lortone and Covington tumblers, vibrating laps and general lapidary machines.',
                'Match the replacement motor to your machine model and check the pulley size when you order.',
                ['accessories'],
                "Check the motor's horsepower, speed, voltage and shaft size against your machine's specifications, and confirm the pulley you already own fits the new motor.",
                [], ''],
            'pumps-plumbing' => ['Pumps & Plumbing', 'Water Pumps & Plumbing for Lapidary Machines', '/pump|manifold|plumbing|valve|drain|tank/i', '',
                'Submersible pumps, water tanks, manifolds, valves and plumbing kits for cooling lapidary saws and machines.',
                'Steady water flow keeps blades and wheels cool and clears slurry.',
                ['accessories'],
                "Pumps must suit the liquid they move: some are for water only, others handle water and oil. Check flow and voltage, and choose plumbing that fits the machine's water feed.",
                ['lapidary-saw-safety-and-coolant'], ''],
            'ring-saw-parts' => ['Ring Saw Parts', 'Gemini Ring Saw Replacement Parts', '/gemini|revolution xt|taurus|apollo/i', '',
                'Replacement drive belts, belt tensioners, grommets, wear-part kits and accessories for Gemini Revolution XT, Apollo and Taurus ring saws.',
                'Parts and accessories for Gemini Revolution XT, Apollo and Taurus ring saws.',
                ['accessories'],
                "Parts are specific to the ring saw model, so confirm whether yours is a Revolution XT, Apollo or Taurus before ordering. Drive belts, tensioners and cone grommets are wear items worth keeping as spares.",
                ['how-to-choose-a-lapidary-saw'], ''],
            'machine-hardware' => ['Flanges, Pulleys & Spindles', 'Flanges, Pulleys, Spindles & Shaft Adapters', '/flange|pulley|spindle|shaft adapter|spacer|bearing/i', '',
                'Aluminum and zinc flanges, pulleys, tapered spindles, shaft adapters, spacers and bearings for lapidary saws and grinders.',
                'Hardware for mounting blades and wheels and driving lapidary machines.',
                ['accessories'],
                "Flanges and spindles must match the arbor and blade or wheel size. Measure your shaft and bore before ordering, and check whether a pulley is a step or single-groove type.",
                [], ''],
        ],
        'supplies' => [
            'silicon-carbide-grit' => ['Silicon Carbide Grit', 'Silicon Carbide Grit for Lapping & Grinding', '/\bgrit\b/i', '/tumbler|^step [12] /i',
                'Loose silicon carbide grit in graded sizes from coarse to fine, plus lapping grit kits for flat laps, grinding and general lapidary work.',
                'Graded silicon carbide grit is the abrasive behind lapping and general lapidary shaping.',
                ['supplies'],
                "Grit is sold by mesh size: the higher the number, the finer the grit. Work from coarse to fine, and rinse thoroughly between grits. Lapping grit kits provide a matched set for flat laps, while individual grades let you restock the ones you use most.",
                ['rock-tumbler-grit-guide', 'how-to-choose-a-flat-lap'], ''],
            'diamond-compounds' => ['Diamond Compounds', 'Diamond Compound, Paste & Powder', '/diamond (compound|powder|extender)|compound/i', '',
                'Diamond compound, paste, powder and extender fluid for polishing stone and glass.',
                'Diamond compounds polish very hard materials that other polishes cannot touch.',
                ['supplies'],
                "Diamond compound comes in different micron grades: coarser grades cut, finer grades polish. Use a clean pad or wheel for each grade, and add extender fluid to thin the compound when working.",
                ['how-to-polish-cabochons'], ''],
            'dop-wax' => ['Dop Wax & Adhesives', 'Dop Wax & Lapidary Adhesives', '/dop|wax|epoxy/i', '',
                'Dop wax and epoxy for holding gemstones while you shape and polish them.',
                'Dopping holds small stones securely while you shape them.',
                ['supplies'],
                "Dop wax holds a stone to a dop stick while you shape it and releases with gentle heat. Different waxes have different melting points and hold, and epoxy is an option for stones that need a stronger bond.",
                ['how-to-cut-a-cabochon'], ''],
            'sanding-belts-discs' => ['Sanding Belts & Discs', 'Sanding Belts & Discs for Lapidary', '/sanding|belt|sander belts/i', '/drive|v-belt/i',
                'Silicon carbide and diamond sanding belts, discs, sheets and cloth for shaping and smoothing stone.',
                'Sanding belts and discs come in a range of grits and backings for wet sanders and lap machines.',
                ['supplies'],
                "Confirm the belt size or disc diameter for your machine, and choose a grit progression from coarse to fine. Diamond belts last longer than silicon carbide, which costs less per belt.",
                ['how-to-cut-a-cabochon', 'how-to-polish-cabochons'], ''],
        ],
        'glass' => [
            'glass-grinders' => ['Glass Grinders', 'Glass Grinders', '/grinder/i', '',
                'Inland glass grinders for stained glass work, from entry-level to advanced.',
                'A glass grinder smooths and shapes glass edges quickly and accurately.',
                ['glass'],
                "Compare the grinding surface size, the motor and the accessories included (grinding heads, face shields and work surfaces). Always wear eye protection when grinding glass.",
                [], ''],
            'glass-lathes' => ['Glass Lathes', 'Glass Lathes', '/lathe/i', '',
                'Covington glass lathes for cutting, shaping and polishing round glass pieces.',
                'A glass lathe spins the work so you can cut and polish circular forms.',
                ['glass'],
                "Glass lathes spin a piece while you cut, shape and polish it, which makes them useful for round forms such as bowls, lenses and cylinders. Compare the models by size and included accessories.",
                [], ''],
            'glass-cutters' => ['Glass Cutters', 'Glass Cutters', '/cutter/i', '',
                'Inland ScoreOne glass cutters and replacement heads.',
                'A good cutter scores glass cleanly so it breaks along the line.',
                ['glass'],
                "Replace the cutter head when scoring gets uneven. A clean, consistent score is what makes glass break along the line.",
                [], ''],
            'glass-polishers' => ['Glass Polishers', 'Glass Polishers', '/polisher/i', '',
                'Covington cork and felt glass polishers in 10 and 16 inch sizes.',
                'Cork and felt polishing brings polish to glass edges and surfaces.',
                ['glass'],
                "Cork and felt wheels are used with polishing compounds such as cerium oxide to finish glass edges and surfaces.",
                [], ''],
        ],
        'tools' => [
            'diamond-burs' => ['Diamond Burs', 'Diamond Burs & Carving Bur Sets', '/\bbur/i', '',
                'Sintered diamond burs and carving bur sets in flame, round, cone, cylinder and other shapes for carving and detailing stone and glass.',
                'Diamond burs shape, carve and detail stone and glass with a rotary tool.',
                ['tools'],
                "Choose the bur shape for the detail: round and pearl burs for hollows, cones and cylinders for edges, flame and Christmas tree shapes for carving. Sintered burs last longer than plated burs, and using them wet keeps them cooler.",
                [], ''],
            'core-drill-bits' => ['Core Drill Bits & Drills', 'Diamond Core Drill Bits & Gemstone Drills', '/drill/i', '',
                'Diamond core drill bits, sintered and bonded bits, and gemstone drill heads for drilling holes in stone, glass and beads.',
                'Core drill bits cut clean holes in stone, glass and beads.',
                ['tools'],
                "Match the bit diameter and shank to your drill, and drill wet so the bit stays cool. Sintered bits last longer than bonded bits, and ultrasonic drills handle delicate stones.",
                [], ''],
        ],
        ];
    }
    return isset($t[$cat]) ? $t[$cat] : [];
}

/** Flat list of every type as [category, key, def]. */
function bl_all_types() {
    $out = [];
    foreach (['saws','grinding-polishing','lap-machines','tumblers','accessories','supplies','glass','tools'] as $c) {
        foreach (bl_types($c) as $k => $d) { $out[] = [$c, $k, $d]; }
    }
    return $out;
}

/** Old type keys that were renamed; maps to the new key (same category). */
function bl_type_legacy($cat, $type) {
    static $m = [
        'grinding-polishing' => ['grinders-polishers' => 'cabbing-machines', 'combination-units' => 'cabbing-machines'],
        'tumblers' => ['replacement-barrels' => 'tumbler-parts'],
        'supplies' => ['grit-tumbling-media' => 'silicon-carbide-grit'],
    ];
    return isset($m[$cat][$type]) ? $m[$cat][$type] : null;
}

function bl_type_matches($def, $name) {
    if (!preg_match($def[2], $name)) { return false; }
    if ($def[3] !== '' && preg_match($def[3], $name)) { return false; }
    return true;
}

/** Products belonging to a type, ordered by name. */
function bl_type_products($db, $cat, $typeKey) {
    $types = bl_types($cat);
    if (!isset($types[$typeKey])) { return []; }
    $def = $types[$typeKey];
    $cats = !empty($def[6]) ? $def[6] : [$cat];
    $ph = implode(',', array_fill(0, count($cats), '?'));
    $stmt = $db->prepare("SELECT * FROM products WHERE category IN ($ph) ORDER BY name");
    $stmt->execute($cats);
    $out = [];
    foreach ($stmt->fetchAll() as $p) {
        if (bl_type_matches($def, $p['name'])) { $out[] = $p; }
    }
    return $out;
}

/** Types a single product belongs to (used for "Browse more" links on product pages). */
function bl_product_types($p) {
    $out = [];
    foreach (bl_all_types() as $t) {
        list($c, $k, $d) = $t;
        $cats = !empty($d[6]) ? $d[6] : [$c];
        if (in_array($p['category'], $cats, true) && bl_type_matches($d, $p['name'])) { $out[] = $t; }
    }
    return $out;
}

/** A type page is only indexable when it has enough products to be more than a thin page. */
function bl_type_indexable($count) { return $count >= 3; }

/** Data-driven, factual summary of a product list (brands, price range). */
function bl_collection_summary($items) {
    if (!$items) { return ''; }
    $brands = [];
    $min = null; $max = null;
    foreach ($items as $p) {
        $brands[$p['vendor']] = isset($brands[$p['vendor']]) ? $brands[$p['vendor']] + 1 : 1;
        $price = ($p['sale_price'] !== null && $p['sale_price'] !== '') ? (float)$p['sale_price'] : (float)$p['price'];
        if ($min === null || $price < $min) { $min = $price; }
        if ($max === null || $price > $max) { $max = $price; }
    }
    arsort($brands);
    $names = array_slice(array_keys($brands), 0, 4);
    $n = count($items);
    $s = $n . ' product' . ($n === 1 ? '' : 's');
    if ($names) {
        $last = array_pop($names);
        $s .= ' from ' . ($names ? implode(', ', $names) . ' and ' . $last : $last);
    }
    if ($min !== null) {
        $s .= $min == $max ? ' at ' . bl_money($min) . '.' : ', priced from ' . bl_money($min) . ' to ' . bl_money($max) . '.';
    }
    return $s;
}

/** <title> text: keeps the brand suffix when it fits in ~60 characters, otherwise drops it. */
function bl_title($core) {
    $core = trim(preg_replace('/\s+/u', ' ', $core));
    $suffix = ' | Bedrock Lapidary';
    return mb_strlen($core . $suffix) <= 60 ? $core . $suffix : $core;
}

function bl_shipping_details_schema($p) {
    if (empty($p['free_shipping'])) { return null; }
    $states = ['AL','AZ','AR','CA','CO','CT','DE','DC','FL','GA','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY'];
    $dest = [];
    foreach ($states as $s) { $dest[] = ['@type' => 'DefinedRegion', 'addressCountry' => 'US', 'addressRegion' => $s]; }
    return [
        '@type' => 'OfferShippingDetails',
        'shippingRate' => ['@type' => 'MonetaryAmount', 'value' => '0', 'currency' => 'USD'],
        'shippingDestination' => $dest,
        'deliveryTime' => ['@type' => 'ShippingDeliveryTime',
            'handlingTime' => ['@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 5, 'unitCode' => 'DAY']],
    ];
}

function bl_return_policy_schema($siteUrl) {
    return [
        '@type' => 'MerchantReturnPolicy',
        'applicableCountry' => 'US',
        'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
        'merchantReturnDays' => 30,
        'returnMethod' => 'https://schema.org/ReturnByMail',
        'returnFees' => 'https://schema.org/ReturnShippingFees',
        'merchantReturnLink' => $siteUrl . '/returns',
    ];
}

function bl_org_schema_json($siteUrl) {
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        '@id' => $siteUrl . '/#organization',
        'name' => 'Bedrock Lapidary',
        'url' => $siteUrl . '/',
        'logo' => $siteUrl . '/assets/img/icons/android-chrome-512x512.png',
        'email' => 'contact@bedrocklapidary.com',
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Bellaire', 'addressRegion' => 'MI', 'addressCountry' => 'US'],
    ], JSON_UNESCAPED_SLASHES);
}
