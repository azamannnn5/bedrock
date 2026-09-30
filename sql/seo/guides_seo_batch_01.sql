-- ============================================================================
-- SEO buying guides (batch 1). Run once in phpMyAdmin after the guide_posts table
-- exists. Safe to re-run: each guide is upserted by slug.
-- Product/category links point at real catalog ids and category types.
-- ============================================================================
SET NAMES utf8mb4;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('slab-saw-vs-trim-saw', 'Slab Saw vs Trim Saw: Which Do You Need?', 'Buying Guide', 'Slab saws cut big rough into slabs; trim saws cut small pieces and shape preforms. Here is how to tell which one fits the way you work.', 'Slab saw vs trim saw: what each one cuts, blade sizes, cut depth, and which lapidary saw makes sense for your first shop or your next upgrade.', '<h2 id="difference">The short answer</h2>
<p>A slab saw is built to cut large, heavy rough into flat slabs. A trim saw is a smaller saw for cutting slabs down to size, trimming away waste and shaping a stone before it goes to the grinder. Many workshops eventually own both, but most people start with one.</p>
<h2 id="slab">What a slab saw does</h2>
<p><a href="/category/saws/slab-saws">Slab saws</a> use large blades, commonly 10 inches and up, and hold the rock in a vise that feeds it into the blade. As a rule of thumb, the usable depth of cut is roughly a third to two-fifths of the blade diameter once the flanges and vise are accounted for, so a bigger blade lets you cut through a bigger rock in one pass. If you cut geodes, nodules or large chunks of agate and jasper, a slab saw is the tool.</p>
<p>Examples in our range include the <a href="/product/hi-tech-diamond-10-inch-slab-saw">Hi-Tech Diamond 10 Inch Slab Saw</a> and the <a href="/product/covington-24-inch-slab-saw">Covington 24 Inch Slab Saw</a>. Larger machines cost more, use bigger blades and need more bench space.</p>
<h2 id="trim">What a trim saw does</h2>
<p><a href="/category/saws/trim-saws">Trim saws</a> use smaller blades, often around 6 inches, and hold the work by hand or in a small vise. They are ideal for cutting slabs into cabochon blanks, trimming out flaws, and cutting small pieces. A small blade makes a thin, tight cut, so you waste less material. The <a href="/product/covington-6-inch-trim-saw">Covington 6 Inch Trim Saw</a> is a typical example.</p>
<h2 id="combo">Combination saws</h2>
<p>If you want both jobs in one footprint, a combination unit such as the <a href="/product/covington-14-inch-combination-trim-slab-saw">Covington 14 Inch Combination Trim & Slab Saw</a> adds a trim saw to a slab saw.</p>
<div class="callout">
<strong>Which should I buy first?</strong>
If you mostly buy slabs or already-cut material, start with a trim saw. If you collect your own rough, or want to cut rocks bigger than your fist, start with a slab saw.
</div>
<h2 id="next">Next steps</h2>
<p>Once you have chosen a saw, pick the right blade: see <a href="/guides/diamond-saw-blade-guide">our diamond saw blade guide</a>, and read <a href="/guides/lapidary-saw-safety-and-coolant">how to cut safely and choose a coolant</a>.</p>', 'saws', 1, 20)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('diamond-saw-blade-guide', 'Diamond Saw Blade Guide: Size, Type and What to Buy', 'Buying Guide', 'Blade diameter, arbor size and bond type decide how well your saw cuts. Here is how to choose the right diamond blade.', 'How to choose a diamond saw blade for a lapidary saw: diameter, arbor size, sintered vs electroplated, rim thickness and matching blade to material.', '<h2 id="size">Match diameter and arbor to your saw</h2>
<p>Your saw sets the blade diameter and arbor (center hole) size, so start with the machine''s specifications and buy a blade that matches both. Running an undersized blade limits depth of cut, and forcing an oversize blade onto a saw is unsafe. Check the maximum blade size before you order.</p>
<h2 id="bond">Sintered vs electroplated blades</h2>
<p><strong>Sintered blades</strong> have diamond distributed through a metal rim, so fresh diamond keeps being exposed as the rim wears. They typically cost more and last a long time, which makes them a good choice for hard materials and frequent use. Examples: <a href="/product/cabking-sintered-diamond-saw-blade">CabKing Sintered Diamond Saw Blade</a> and the <a href="/product/covington-platinum-303-diamond-saw-blades">Covington Platinum 303 Diamond Saw Blades</a>.</p>
<p><strong>Electroplated blades</strong> have a thin layer of diamond plated onto the blade. They tend to cut fast and clean, but the diamond layer wears away and cannot be renewed.</p>
<h2 id="rim">Rim thickness</h2>
<p>A thinner rim removes less material and makes a narrower kerf, which saves expensive rough and produces smoother slabs. Thicker rims are stiffer and stand up better to heavy cutting. For delicate or valuable material, choose a thinner rim; for heavy general cutting, choose a sturdier blade.</p>
<h2 id="feed">Feed gently</h2>
<p>Whatever blade you buy, let it do the cutting. Forcing the rock into the blade overheats it, glazes the rim and can bend the blade. Use a steady, light feed and keep the blade cooled with the recommended coolant.</p>
<div class="callout">
<strong>Browse blades</strong>
See all <a href="/category/saws/saw-blades">diamond saw blades</a> for slab, trim, ring and band saws.
</div>
<p>Not sure which saw you need? Read <a href="/guides/slab-saw-vs-trim-saw">Slab Saw vs Trim Saw</a>.</p>', 'saws', 1, 21)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('lapidary-saw-safety-and-coolant', 'Lapidary Saw Safety and Choosing a Coolant', 'Buying Guide', 'Cut safely and keep your blades alive: eye protection, guarding, coolant choices and the habits that prevent most saw accidents.', 'Lapidary saw safety tips and how to choose a coolant: eye and hearing protection, blade guarding, wet cutting, dust and blade care.', '<h2 id="ppe">Protect yourself</h2>
<p>Wear safety glasses or a face shield every time the saw runs. Tie back long hair, remove loose sleeves and jewelry, and keep your fingers well clear of the blade. Some saws are loud enough that hearing protection is worthwhile for long sessions.</p>
<h2 id="guard">Keep guards and vises in place</h2>
<p>Use the saw''s cover, hood and vise as designed. A vise that holds the rock firmly prevents it from grabbing and kicking. Never hold a small stone by hand against a large blade; use a <a href="/category/saws/trim-saws">trim saw</a> with the right vise for small pieces.</p>
<h2 id="coolant">Coolant and why it matters</h2>
<p>Blades are meant to run wet. Coolant carries away heat, flushes out cuttings and extends blade life. Many lapidary saws use a cutting oil or a lapidary coolant, while others run on a water-based coolant. Follow your saw manufacturer''s recommendation. The Covington <a href="/product/covington-koolerant-3-kool-lube-reformulated">Koolerant #3 Kool Lube</a> is one example of a lapidary coolant, and a <a href="/product/covington-submersible-water-pump">submersible water pump</a> circulates coolant on machines that use one.</p>
<h2 id="dust">Dust</h2>
<p>Cutting wet keeps rock dust down. Dry-cutting stone generates fine dust that is unhealthy to breathe, so avoid it unless your equipment is designed for it.</p>
<div class="callout">
<strong>Before every session</strong>
Check the blade for wobble or damage, confirm the coolant level, secure the rock in the vise, close the cover, then start the saw and let it reach full speed before you feed.
</div>
<p>Related reading: <a href="/guides/diamond-saw-blade-guide">Diamond Saw Blade Guide</a> and <a href="/guides/slab-saw-vs-trim-saw">Slab Saw vs Trim Saw</a>.</p>', 'saws', 1, 22)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('rock-tumbler-grit-guide', 'Rock Tumbler Grit Guide: Stages, Sizes and Polish', 'Buying Guide', 'What each grit stage does, how much to use, and how to avoid the mistakes that leave you with dull, scratched stones.', 'A rock tumbler grit guide: what each grit stage does, the standard grit progression, how much to use, and how to get a glossy polish.', '<h2 id="why">Why stages matter</h2>
<p>Tumbling works by progressively removing the scratches left by the previous stage. Each finer grit erases the marks from the coarser one, until the final polish leaves a glossy surface. Skipping a stage leaves scratches that the next stage cannot remove.</p>
<h2 id="stages">The standard progression</h2>
<ul>
<li><strong>Coarse (about 60/90):</strong> shapes rough rock and removes sharp edges. See <a href="/product/step-1-60-90-silicon-carbide-grit">Step 1 - 60/90 Silicon Carbide Grit</a>.</li>
<li><strong>Medium (about 120/220):</strong> smooths the surface left by the coarse stage.</li>
<li><strong>Fine (about 500 to 600):</strong> pre-polish, removing the fine scratches.</li>
<li><strong>Polish:</strong> cerium oxide or aluminum oxide finishes the surface, and it is best paired with a soft filler such as pellets or <a href="/category/tumblers/tumbling-grit-polish">tumbler grit and polish</a> media.</li>
</ul>
<p>If you want everything in one order, a <a href="/product/rock-tumbler-starter-grit-and-polish-kit">starter grit and polish kit</a> or the <a href="/product/tumbler-grit-polish-kit">Tumbler Grit & Polish Kit</a> covers the progression.</p>
<h2 id="clean">Clean between every stage</h2>
<p>Wash the stones and barrel thoroughly before you add the next grit. A single trace of coarse grit carried into the polish stage will scratch the whole batch.</p>
<h2 id="shiny">Why are my rocks not shiny?</h2>
<ul>
<li>Coarse grit carried over into a later stage.</li>
<li>A previous stage was cut short, so scratches remain.</li>
<li>Stones of mixed hardness in the same barrel, so the softer ones polish unevenly.</li>
<li>An overfull or underfull barrel. Fill it about half to two-thirds.</li>
<li>Too little polish, or a worn-out polish charge.</li>
</ul>
<p>Learn how long each stage takes in <a href="/guides/how-long-does-rock-tumbling-take">How Long Does Rock Tumbling Take?</a> and see <a href="/guides/choosing-your-first-rotary-tumbler">Choosing Your First Rotary Tumbler</a>.</p>', 'tumblers', 1, 23)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('how-long-does-rock-tumbling-take', 'How Long Does Rock Tumbling Take?', 'Buying Guide', 'A typical batch takes about a month. Here is how long each stage runs and what changes the timeline.', 'How long does rock tumbling take? A typical four-stage rotary tumbler run, how long each stage lasts, and what speeds it up or slows it down.', '<h2 id="answer">The typical timeline</h2>
<p>A standard four-stage rotary tumbling run usually takes around four to six weeks from rough rock to polish. Each stage typically runs about a week, though the polish stage is often a little shorter and the coarse stage can run longer for very rough rock.</p>
<h2 id="stages">Stage by stage</h2>
<ul>
<li><strong>Coarse grind:</strong> about 7 days, sometimes repeated for very rough or angular rock.</li>
<li><strong>Medium grind:</strong> about 7 days.</li>
<li><strong>Pre-polish:</strong> about 7 days.</li>
<li><strong>Polish:</strong> about 4 to 7 days.</li>
</ul>
<h2 id="factors">What changes the timeline</h2>
<ul>
<li><strong>Material hardness.</strong> Harder stones like agate and jasper take longer to shape, but polish beautifully.</li>
<li><strong>Starting shape.</strong> Rough with sharp corners and pits needs more coarse time.</li>
<li><strong>Tumbler type.</strong> Barrel size, speed and load all affect how quickly the stones move.</li>
<li><strong>Grit quality and quantity.</strong> Follow the recommended amounts for your barrel size.</li>
</ul>
<div class="callout">
<strong>Do not rush the early stages</strong>
The final gloss depends on how well the coarse and medium stages did their work. Extra time at the start saves disappointment at the end.
</div>
<p>Choose a machine that fits your volume: browse <a href="/category/tumblers/rotary-tumblers">rotary tumblers</a> such as the <a href="/product/lortone-qt6-rock-tumbler">Lortone QT6</a> or the <a href="/product/lortone-qt12-rock-tumbler">Lortone QT12</a>, and read <a href="/guides/rock-tumbler-grit-guide">the Rock Tumbler Grit Guide</a>.</p>', 'tumblers', 1, 24)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('how-to-choose-a-flat-lap', 'How to Choose a Flat Lap Machine', 'Buying Guide', 'Flat laps flatten and polish stone, glass and crystal. Compare rotary and vibrating laps and the disc sizes that suit your work.', 'How to choose a flat lap machine: rotary vs vibrating flat laps, disc size, discs and polishes, and what to buy first for lapidary work.', '<h2 id="what">What a flat lap does</h2>
<p>A flat lap machine spins a disc that you charge with abrasive, so you can flatten the face of a stone, smooth a slab, or polish a flat surface to a high shine. It is used on stone, glass and crystal.</p>
<h2 id="types">Rotary and vibrating laps</h2>
<p><strong>Rotary flat laps</strong> spin a disc while you hold the work against it. The Covington <a href="/category/lap-machines/flat-lap-machines">Maxi Lap and Rociprolap machines</a> are examples, in sizes from 8 inches up to 36 inches.</p>
<p><strong>Vibrating laps</strong> shake the work across the surface automatically, which is useful for flattening or polishing without holding the piece the whole time. See our <a href="/category/lap-machines/vibrating-lap-machines">Vibra Lap vibrating machines</a>, for example the <a href="/product/covington-12-inch-vibra-lap-automatic-vibrating-flat-lap-mac">Covington 12 Inch Vibra Lap</a>.</p>
<h2 id="size">Choose the disc size for your work</h2>
<p>Disc diameter sets the largest piece you can comfortably flatten. Small discs suit cabochon-sized work; larger discs are better for slabs and big flat surfaces.</p>
<h2 id="discs">Discs, grit and polish</h2>
<p>Flat laps need abrasive discs or loose grits and a final polish. Budget for the <a href="/category/lap-machines/lap-disks">lap disks and pads</a> and the <a href="/category/supplies/diamond-compounds">diamond compounds</a> that go with your machine. If you are cutting cabochons rather than flat surfaces, a <a href="/category/lap-machines/slant-cabbers">slant cabber</a> such as the <a href="/product/hi-tech-diamond-slant-cabber-rock-mineral-model">Hi-Tech Diamond Slant Cabber</a> may fit better.</p>
<div class="callout">
<strong>Before you buy</strong>
Confirm disc size, motor and whether the machine needs a water system, and check that replacement discs are available.
</div>', 'lap-machines', 1, 25)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('diamond-vs-silicon-carbide-grinding-wheels', 'Diamond vs Silicon Carbide Grinding Wheels', 'Buying Guide', 'Diamond wheels cut faster and last longer; silicon carbide wheels cost less up front. Here is how to decide.', 'Diamond vs silicon carbide grinding wheels for lapidary work: how each cuts, how long they last, cost, and which one to choose for your grinder.', '<h2 id="diamond">Diamond wheels</h2>
<p>Diamond is the hardest abrasive, so <a href="/category/grinding-polishing/grinding-wheels">diamond grinding wheels</a> cut quickly, hold their shape and last for a long time. They can shape very hard stones such as quartz, agate and jasper efficiently. The trade-off is a higher purchase price. Examples include the <a href="/product/covington-ultimate-sintered-diamond-wheels">Covington Ultimate Sintered Diamond Wheels</a>.</p>
<h2 id="sic">Silicon carbide wheels</h2>
<p>Silicon carbide wheels cost less and cut well on many materials, but they wear faster and lose their shape, so they need dressing to stay flat and true. A dressing stick or tool helps. See, for instance, the <a href="/product/silicon-carbide-grinding-wheel-8-inch-5-8-inch-220">8 Inch Silicon Carbide Grinding Wheel</a>.</p>
<h2 id="choose">Which to choose</h2>
<ul>
<li><strong>Choose diamond</strong> if you grind often, work hard materials, or want consistent results with less maintenance.</li>
<li><strong>Choose silicon carbide</strong> if you are on a tight budget or grinding occasionally.</li>
</ul>
<h2 id="grit">Grit sizes</h2>
<p>Coarse wheels remove material quickly; finer wheels leave a smoother surface ready for sanding and polishing. Most cabochon work moves through several grits, from coarse shaping to fine smoothing, before the final polish.</p>
<p>Matching your wheels to your machine matters, so check size and arbor. Browse <a href="/category/grinding-polishing/cabbing-machines">grinder polishers</a> or read <a href="/guides/how-to-polish-cabochons">How to Polish Cabochons</a>.</p>', 'grinding-polishing', 1, 26)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('how-to-polish-cabochons', 'How to Polish Cabochons: Wheels, Compounds and Technique', 'Buying Guide', 'The final polish depends on clean sanding, the right compound and the right wheel. Here is how to get a glossy cabochon.', 'How to polish cabochons: the sanding-to-polish workflow, choosing cerium oxide, tin oxide or diamond compound, and polishing wheels and pads.', '<h2 id="prep">Polish starts with sanding</h2>
<p>A polish can only be as good as the surface underneath it. Work through the grits on your grinder, removing the scratches from each grit before moving to the next. Rinse the stone between steps so coarse grit does not carry over.</p>
<h2 id="compounds">Choosing a polishing compound</h2>
<ul>
<li><strong>Cerium oxide</strong> is a popular all-round polish for quartz-family stones and glass. See <a href="/product/cerium-oxide-1-standard">Cerium Oxide #1 - Standard</a>.</li>
<li><strong>Tin oxide</strong> is often used on softer or harder-to-polish stones. See <a href="/product/tin-oxide-polish">Tin Oxide Polish</a>.</li>
<li><strong>Diamond compound</strong> polishes very hard materials, and fine grades leave a brilliant finish. See <a href="/product/covington-diamond-compound">Covington Diamond Compound</a>.</li>
</ul>
<p>Different materials respond to different polishes, so it is worth keeping more than one on hand.</p>
<h2 id="wheels">Wheels and pads</h2>
<p>Use a clean wheel for each compound. Leather, felt, canvas and cork are all common, so browse our <a href="/category/grinding-polishing/polishing-wheels">polishing wheels and pads</a>, such as the <a href="/product/covington-cork-polishing-wheels">Covington Cork Polishing Wheels</a>.</p>
<h2 id="technique">Technique</h2>
<ul>
<li>Keep the stone moving so you do not create flat spots.</li>
<li>Use light pressure to avoid heat and undercutting.</li>
<li>Keep the polish damp, not soaking wet.</li>
<li>If the polish will not develop, go back a grit and remove any remaining scratches.</li>
</ul>
<p>Read more about wheels in <a href="/guides/diamond-vs-silicon-carbide-grinding-wheels">Diamond vs Silicon Carbide Grinding Wheels</a>, or see our <a href="/guides/how-to-cut-a-cabochon">step-by-step cabochon guide</a>.</p>', 'grinding-polishing', 1, 27)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('how-to-cut-a-cabochon', 'How to Cut a Cabochon: A Beginner Step-by-Step', 'Buying Guide', 'From slab to finished stone: the basic steps and equipment for cutting your first cabochon.', 'How to cut a cabochon step by step for beginners: slabbing, marking, trimming, grinding, sanding and polishing, plus the equipment you need.', '<h2 id="equipment">What you need</h2>
<p>A basic cabochon setup includes a <a href="/category/saws/trim-saws">trim saw</a> for cutting blanks, a <a href="/category/grinding-polishing/cabbing-machines">grinder polisher</a> with diamond wheels and polishing pads, dop wax and dop sticks, and a polishing compound. A book such as <a href="/product/cabochon-cutting-by-jack-r-cox">Cabochon Cutting by Jack R. Cox</a> is a useful reference.</p>
<h2 id="steps">The steps</h2>
<ol>
<li><strong>Choose a slab.</strong> Look for stone without cracks, pits or fractures in the area you will use.</li>
<li><strong>Mark the shape.</strong> Trace a template onto the slab with a pen or scribe.</li>
<li><strong>Trim the blank.</strong> Cut just outside your line on the trim saw.</li>
<li><strong>Dop the stone.</strong> Attach it to a dop stick with wax so you can hold it while you shape it. See <a href="/product/covington-green-dop-wax-1-pound">Covington Green Dop Wax</a>.</li>
<li><strong>Grind the shape.</strong> Use a coarse wheel to reach the outline, then shape the dome.</li>
<li><strong>Sand.</strong> Move through finer grits, removing scratches at each step.</li>
<li><strong>Polish.</strong> Finish on a clean polishing wheel with your chosen compound.</li>
<li><strong>Release the stone.</strong> Remove the dop and clean the finished cabochon.</li>
</ol>
<div class="callout">
<strong>Take your time</strong>
Skipping a grit is the most common beginner mistake. Each step exists to remove the scratches left by the last one.
</div>
<p>Continue with <a href="/guides/how-to-polish-cabochons">How to Polish Cabochons</a>.</p>', 'grinding-polishing', 1, 28)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('best-lapidary-equipment-for-beginners', 'Best Lapidary Equipment for Beginners', 'Buying Guide', 'Start with the essentials: a saw, a grinder polisher, a tumbler and a few supplies. Here is how to build a first setup.', 'Best lapidary equipment for beginners: the core machines and supplies to set up a first lapidary workshop for cabochons, tumbling and polishing.', '<h2 id="start">Decide what you want to make</h2>
<p>Your first purchases depend on your goal. Tumbling stones for polished pocket rocks needs very different equipment from cutting cabochons for jewelry. Start with the process you want to try first and add machines as your skills grow.</p>
<h2 id="tumbling">If you want polished stones</h2>
<p>A <a href="/category/tumblers/rotary-tumblers">rotary tumbler</a> is the easiest place to start. Small units such as the <a href="/product/lortone-3a-rock-tumbler">Lortone 3A</a> suit a first batch, and a <a href="/product/rock-tumbler-starter-grit-and-polish-kit">starter grit and polish kit</a> supplies the abrasives. Read <a href="/guides/choosing-your-first-rotary-tumbler">Choosing Your First Rotary Tumbler</a>.</p>
<h2 id="cabs">If you want to cut cabochons</h2>
<ul>
<li>A <a href="/category/saws/trim-saws">trim saw</a> to cut blanks.</li>
<li>A <a href="/category/grinding-polishing/cabbing-machines">grinder polisher</a> to shape and polish, such as the <a href="/product/cabking-6-inch-grinder-polisher">CabKing 6 Inch Grinder Polisher</a>.</li>
<li><a href="/category/supplies/dop-wax">Dop wax and sticks</a> to hold the stones.</li>
<li>A polish such as <a href="/product/cerium-oxide-1-standard">cerium oxide</a>.</li>
</ul>
<p>Learn the basics in <a href="/guides/how-to-cut-a-cabochon">How to Cut a Cabochon</a>.</p>
<h2 id="grow">Growing your shop</h2>
<p>Add a <a href="/category/saws/slab-saws">slab saw</a> for larger rough, a <a href="/category/lap-machines/flat-lap-machines">flat lap</a> for flat surfaces, or a <a href="/category/grinding-polishing/sanders">wet belt sander</a> to speed up shaping.</p>
<div class="callout">
<strong>Budget tip</strong>
Buy the best saw and grinder you can afford. Consumables like blades, wheels and grit are cheap to replace, but the machine itself sets the quality of everything you make.
</div>
<p>Not sure where to start? <a href="/category/saws/saw-blades">Browse blades</a> or <a href="/guides/slab-saw-vs-trim-saw">compare saws</a>.</p>', 'saws', 1, 29)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('how-to-choose-a-lapidary-saw', 'How to Choose a Lapidary Saw', 'Buying Guide', 'Saw type, blade diameter, depth of cut, feed system and coolant: the decisions that determine whether a lapidary saw fits the way you work.', 'How to choose a lapidary saw: slab, trim, ring and band saws compared, plus blade diameter, depth of cut, power feed, coolant and beginner advice.', '<h2 id="start">Start with what you cut</h2>
<p>The right lapidary saw depends on the size of the material and what you want to make. Large nodules and rough call for a big saw; slabs and cabochon blanks call for a small one. Decide what you cut most often before you compare models.</p>
<h2 id="types">The four saw types</h2>
<ul>
<li><a href="/category/saws/slab-saws">Slab saws</a> cut large rough into slabs. Example: the <a href="/product/hi-tech-diamond-10-inch-slab-saw">Hi-Tech Diamond 10 Inch Slab Saw</a>.</li>
<li><a href="/category/saws/trim-saws">Trim saws</a> cut slabs into blanks and trim away waste. Example: the <a href="/product/covington-6-inch-trim-saw">Covington 6 Inch Trim Saw</a>.</li>
<li><a href="/category/saws/ring-saws">Ring saws</a> use a ring-shaped blade for curves and intricate shapes with little waste. Example: the <a href="/product/gemini-apollo-ring-saw">Gemini Apollo Ring Saw</a>.</li>
<li><a href="/category/saws/band-saws">Band saws</a> follow curved outlines and suit carving preforms.</li>
</ul>
<p>If you want both slabbing and trimming in one machine, a combination saw such as the <a href="/product/covington-14-inch-combination-trim-slab-saw">Covington 14 Inch Combination Trim & Slab Saw</a> does both. See <a href="/guides/slab-saw-vs-trim-saw">Slab Saw vs Trim Saw</a> for the detail.</p>
<h2 id="blade">Blade diameter and depth of cut</h2>
<p>Blade diameter sets the depth of cut. Once the flanges and vise are accounted for, the usable depth is roughly a third to two-fifths of the blade diameter, so a 10 inch blade suits fist-sized rock and larger blades handle bigger pieces. Buying a bigger saw than you need costs more and takes more room, while a saw that is too small limits what you can cut.</p>
<h2 id="feed">Feed system</h2>
<p>A power feed advances the rock into the blade at a steady rate, which produces straighter, more consistent cuts and reduces blade wear. Manual feed saws are cheaper, but they depend on your hand to keep the feed even.</p>
<h2 id="coolant">Coolant</h2>
<p>Saws run wet. Some use cutting oil and others use a water-based coolant, and the right choice depends on the machine, so follow the manufacturer''s recommendation. See <a href="/guides/lapidary-saw-safety-and-coolant">Lapidary Saw Safety and Choosing a Coolant</a>.</p>
<h2 id="beginner">Beginner considerations</h2>
<ul>
<li>Check the bench space, electrical supply and water or oil setup you need.</li>
<li>Budget for the blade: many saws are sold without one. See our <a href="/category/saws/saw-blades">diamond saw blades</a> and the <a href="/guides/diamond-saw-blade-guide">Diamond Saw Blade Guide</a>.</li>
<li>Consider a vise that holds the material securely.</li>
</ul>
<h2 id="maintenance">Maintenance</h2>
<p>Keep coolant clean, clear slurry from the tank, check the blade for damage and keep the vise slides clean. A well-maintained saw keeps cutting accurately for years.</p>
<div class="callout">
<strong>Not sure yet?</strong>
<a href="/guides/best-lapidary-equipment-for-beginners">Our beginner equipment guide</a> shows how a first workshop fits together.
</div>', 'saws', 1, 30)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('tile-saw-vs-lapidary-saw', 'Tile Saw vs Lapidary Saw: Can You Cut Rocks With a Tile Saw?', 'Buying Guide', 'Tile saws and lapidary saws both use diamond blades and water, but they are built for very different work.', 'Tile saw vs lapidary saw: how they differ in blade, vise, coolant and depth of cut, and why rock cutting calls for a saw built for lapidary work.', '<h2 id="short">The short answer</h2>
<p>Tile saws and lapidary saws both cut with diamond blades and water, but they are designed for different material. A tile saw is built to cut flat tile and thin stone panels. A <a href="/category/saws">lapidary saw</a> is built to hold and cut irregular rocks, nodules and slabs.</p>
<h2 id="differences">Where they differ</h2>
<ul>
<li><strong>Holding the work.</strong> A lapidary saw has a vise that clamps irregular rock securely while it feeds into the blade. A tile saw has a flat table and a fence for flat material.</li>
<li><strong>Depth of cut.</strong> Lapidary slab saws use larger blades so they can cut through thick rock. Tile saw blades and tables are sized for tile thickness.</li>
<li><strong>Coolant.</strong> Lapidary saws use a coolant system designed to keep the blade and rock cool during long cuts. Tile saws use a water spray or tray sized for tile.</li>
<li><strong>Blade.</strong> Lapidary blades come in rim thicknesses and diamond bonds intended for stone and gemstone material, so a thin rim wastes less rough.</li>
<li><strong>Safety.</strong> Feeding an irregular, unclamped rock into a blade on a tile saw is risky because the rock can twist or bind.</li>
</ul>
<h2 id="when">When a tile saw makes sense</h2>
<p>If you are cutting thin, flat stone or tile, a tile saw is the right machine. For rocks, geodes and slabs, a saw made for lapidary work is the safer and more accurate choice.</p>
<h2 id="choose">Choosing a lapidary saw</h2>
<p>For small pieces and cabochon blanks, start with a <a href="/category/saws/trim-saws">trim saw</a>. For larger rough, a <a href="/category/saws/slab-saws">slab saw</a> is the tool. Read <a href="/guides/how-to-choose-a-lapidary-saw">How to Choose a Lapidary Saw</a> for the full breakdown.</p>', 'saws', 1, 31)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('how-to-use-a-rock-tumbler', 'How to Use a Rock Tumbler: Step-by-Step Guide', 'Buying Guide', 'Loading, grit stages, water level, run times and cleanup: everything you need to know to run your first batch.', 'How to use a rock tumbler step by step: choosing rocks, loading the barrel, adding grit and water, running each stage, and cleaning up between stages.', '<h2 id="pick">1. Choose your rocks</h2>
<p>Tumble stones of similar hardness together. Harder stones such as agate, jasper and quartz take a good polish, while soft stones tend to wear away or scratch. Mixing sizes helps the stones cushion each other, so use a range of small and medium pieces.</p>
<h2 id="load">2. Load the barrel</h2>
<p>Fill the barrel about half to two-thirds full of rock. Too empty and the stones fall instead of grinding against each other; too full and they cannot move freely. Add the amount of grit recommended for your barrel size.</p>
<h2 id="water">3. Add water</h2>
<p>Add water until it just reaches the top of the rocks. Too little water leaves a thick paste, while too much dilutes the grit and slows the work. Wipe the barrel rim clean so the lid seals.</p>
<h2 id="run">4. Run each stage</h2>
<p>Run the tumbler continuously through each stage, using a finer grit each time. A typical run goes through coarse grind, medium grind, pre-polish and polish. Our <a href="/guides/how-long-does-rock-tumbling-take">tumbling timeline</a> shows how long each stage usually takes, and the <a href="/guides/rock-tumbler-grit-guide">grit guide</a> explains what each one does.</p>
<h2 id="clean">5. Clean between stages</h2>
<p>Wash the stones and the barrel thoroughly before adding the next grit. Even a little leftover coarse grit will scratch the polish stage. Do not pour used grit slurry down a drain, because it can settle and clog pipes; let it settle and dispose of it in the trash.</p>
<h2 id="check">6. Check the barrel</h2>
<p>Look at the barrel after the first day or two. Some rough material can build up gas pressure in a sealed barrel, so open lids carefully and follow your tumbler manual''s advice.</p>
<h2 id="polish">7. Polish and finish</h2>
<p>In the final stage, add polish and consider plastic pellets or other <a href="/category/tumblers/tumbling-media">tumbling media</a> to cushion the stones. When they are finished, rinse well and dry.</p>
<h2 id="equip">What you need</h2>
<p>A <a href="/category/tumblers/rotary-tumblers">rotary tumbler</a> such as the <a href="/product/lortone-3a-rock-tumbler">Lortone 3A</a> or the <a href="/product/covington-single-gallon-rolling-rock-tumbler">Covington Single Gallon</a>, plus grit and polish. A <a href="/product/rock-tumbler-starter-grit-and-polish-kit">starter grit and polish kit</a> covers the stages, and you can find more in our <a href="/category/tumblers/tumbling-grit-polish">rock tumbler grit and polish</a> collection. New to tumbling? Read <a href="/guides/choosing-your-first-rotary-tumbler">Choosing Your First Rotary Tumbler</a>.</p>', 'tumblers', 1, 32)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('rotary-vs-vibratory-tumbler', 'Rotary vs Vibratory Rock Tumblers: Which Is Better?', 'Buying Guide', 'Rotary tumblers round and smooth stones over weeks; vibratory tumblers work faster and keep more of the original shape. Here is how they compare.', 'Rotary vs vibratory rock tumblers: how each works, speed, shape and finish, noise and capacity, and which type suits beginners and serious hobbyists.', '<h2 id="how">How they work</h2>
<p><strong>Rotary tumblers</strong> rotate a barrel so the stones slide and roll against each other and the grit. <strong>Vibratory tumblers</strong> shake a bowl or tub so the stones vibrate against each other and the grit.</p>
<h2 id="compare">How they compare</h2>
<ul>
<li><strong>Speed.</strong> Vibratory tumblers generally work faster, often in days rather than the weeks a rotary run takes. A full rotary run usually takes several weeks.</li>
<li><strong>Shape.</strong> Rotary tumbling rounds the stones and gives the classic smooth, rounded look. Vibratory tumbling tends to preserve more of the original shape and edges.</li>
<li><strong>Finish.</strong> Both can produce a good polish when the stages are done properly.</li>
<li><strong>Capacity.</strong> Rotary machines come in a wide range of barrel sizes, from small hobby barrels to production units.</li>
<li><strong>Noise and vibration.</strong> Both make noise; think about where you will run the machine.</li>
</ul>
<h2 id="choose">Which should you choose?</h2>
<p>If you want traditional, rounded tumbled stones and are happy to wait, a rotary tumbler is the classic choice and the easiest way to learn the process. If you want faster results or want to keep the original shape of the stones, a vibratory machine may suit you better.</p>
<div class="callout">
<strong>What Bedrock carries</strong>
Our tumbler range is <a href="/category/tumblers/rotary-tumblers">rotary tumblers</a> from Lortone, Covington and Tumble-Bee, with <a href="/category/tumblers/tumbler-parts">replacement barrels and parts</a> and <a href="/category/tumblers/tumbling-grit-polish">grit and polish</a> for every stage.
</div>
<p>For a first machine, read <a href="/guides/choosing-your-first-rotary-tumbler">Choosing Your First Rotary Tumbler</a> and <a href="/guides/how-to-use-a-rock-tumbler">How to Use a Rock Tumbler</a>.</p>', 'tumblers', 1, 33)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('flat-lap-vs-cabbing-machine', 'Flat Lap vs Cabbing Machine: What Is the Difference?', 'Buying Guide', 'Flat laps flatten and polish flat surfaces; cabbing machines shape and polish domed cabochons. Here is which one you need.', 'Flat lap vs cabbing machine: what each one does, when to use them, and whether you need one or both for cabochons, slabs, glass and crystal.', '<h2 id="short">The short answer</h2>
<p>A flat lap machine works on flat surfaces. A cabbing machine shapes curved surfaces, so it makes domed cabochons. Many lapidaries eventually own both.</p>
<h2 id="flat">What a flat lap does</h2>
<p>A <a href="/category/lap-machines/flat-lap-machines">flat lap machine</a> spins a flat disc charged with abrasive. You hold the work against it to flatten a surface, smooth a slab or polish a flat face. It is the tool for flat cabochon backs, faceting preforms, slabs and flat polished pieces of stone, glass or crystal. See <a href="/guides/how-to-choose-a-flat-lap">How to Choose a Flat Lap Machine</a>.</p>
<h2 id="cab">What a cabbing machine does</h2>
<p>A <a href="/category/grinding-polishing/cabbing-machines">cabbing machine</a> carries a stack of grinding and polishing wheels. Because the wheels are round, you can shape the curved dome and edges of a cabochon and polish them, all on one machine. Examples include the <a href="/product/cabking-6-inch-grinder-polisher">CabKing 6 Inch Grinder Polisher</a> and the <a href="/product/covington-8-inch-grinder-polisher-diamond">Covington 8 Inch Grinder Polisher</a>. Read <a href="/guides/what-is-a-cabbing-machine">What Is a Cabbing Machine?</a>.</p>
<h2 id="which">Which do you need?</h2>
<ul>
<li><strong>Making cabochons:</strong> start with a cabbing machine.</li>
<li><strong>Flattening slabs, flat backs or flat polished pieces:</strong> use a flat lap.</li>
<li><strong>Glass and crystal:</strong> models are made for both rock and glass, so check the version.</li>
</ul>
<p>A <a href="/category/lap-machines/slant-cabbers">slant cabber</a> sits between the two: it presents the stone at an angle to a diamond disc to shape a dome.</p>', 'lap-machines', 1, 34)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('what-is-a-cabbing-machine', 'What Is a Cabbing Machine? How It Works and What to Buy', 'Buying Guide', 'A cabbing machine shapes and polishes cabochons on a stack of diamond wheels and polishing pads. Here is how it works and what to look for.', 'What is a cabbing machine? How a cabochon machine works, what wheels and pads it uses, what to look for when buying, and how it differs from a flat lap.', '<h2 id="what">What it is</h2>
<p>A cabbing machine, also called a cabochon machine or grinder polisher, is a motorized shaft that holds a set of grinding and polishing wheels. It is used to shape stone into domed cabochons and to polish them.</p>
<h2 id="how">How it works</h2>
<p>The wheels are arranged from coarse to fine. You start on a coarse diamond wheel to shape the outline and dome, move to finer wheels to remove scratches, and finish on a polishing pad with a polish such as cerium oxide. Most machines run wet: a water feed keeps the stone and wheels cool and washes away slurry.</p>
<h2 id="buy">What to look for</h2>
<ul>
<li><strong>Wheel diameter.</strong> 6 and 8 inch are common. Larger wheels give a bigger working surface.</li>
<li><strong>Wheel set.</strong> Check how many diamond wheels and polishing pads are included and what grits they cover.</li>
<li><strong>Motor.</strong> A stronger motor keeps the wheels turning at speed under load.</li>
<li><strong>Water system.</strong> A pump, drip feed and splash guard make wet grinding cleaner.</li>
<li><strong>Extras.</strong> Some models accept accessories, such as a trim saw attachment.</li>
</ul>
<h2 id="examples">Machines to compare</h2>
<p>See our full range of <a href="/category/grinding-polishing/cabbing-machines">cabbing machines and grinder polishers</a>, including the <a href="/product/cabking-6-inch-grinder-polisher">CabKing 6 Inch</a> and <a href="/product/cabking-8-inch-grinder-polisher">CabKing 8 Inch</a> models, and the <a href="/product/covington-8-inch-grinder-polisher-diamond">Covington 8 Inch</a>. Keep spares of the <a href="/category/grinding-polishing/grinding-wheels">grinding wheels</a> and <a href="/category/grinding-polishing/polishing-compounds">polishing compounds</a> you use most.</p>
<h2 id="next">Learn the process</h2>
<p>New to cabbing? Follow <a href="/guides/how-to-cut-a-cabochon">How to Cut a Cabochon</a>, then <a href="/guides/how-to-polish-cabochons">How to Polish Cabochons</a>. To understand how a cabbing machine differs from a flat lap, read <a href="/guides/flat-lap-vs-cabbing-machine">Flat Lap vs Cabbing Machine</a>.</p>', 'grinding-polishing', 1, 35)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;

INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES ('how-to-polish-rocks', 'How to Polish Rocks: 3 Methods Compared', 'Buying Guide', 'Tumbling, cabbing and flat lapping can all polish rocks. Here is how each method works and how to choose one.', 'How to polish rocks: three methods compared, tumbling, cabbing on a grinder polisher and flat lapping, plus which stones polish best and what you need.', '<h2 id="which-stones">Which rocks polish well</h2>
<p>Hard, fine-grained stones polish best, including agate, jasper and quartz varieties. Soft stones scratch easily and are hard to bring to a high shine, so match your method to the material.</p>
<h2 id="tumble">Method 1: tumbling</h2>
<p>A <a href="/category/tumblers/rotary-tumblers">rock tumbler</a> polishes many stones at once, without you holding them. It takes several weeks and gives rounded, smooth stones. See <a href="/guides/how-to-use-a-rock-tumbler">How to Use a Rock Tumbler</a>. You need a tumbler, <a href="/category/tumblers/tumbling-grit-polish">grit and polish</a> and time.</p>
<h2 id="cab">Method 2: cabbing</h2>
<p>A <a href="/category/grinding-polishing/cabbing-machines">cabbing machine</a> lets you shape and polish one stone at a time into a domed cabochon, often for jewelry. It gives you control over the shape, and you finish on a polishing pad with a compound such as <a href="/product/cerium-oxide-1-standard">cerium oxide</a> or <a href="/product/tin-oxide-polish">tin oxide</a>. See <a href="/guides/how-to-polish-cabochons">How to Polish Cabochons</a>.</p>
<h2 id="lap">Method 3: flat lapping</h2>
<p>A <a href="/category/lap-machines/flat-lap-machines">flat lap</a> polishes flat surfaces, such as slabs, flat faces and flat backs. Read <a href="/guides/how-to-choose-a-flat-lap">How to Choose a Flat Lap</a>.</p>
<h2 id="choose">Choosing a method</h2>
<ul>
<li><strong>Lots of small stones, minimal effort:</strong> tumbling.</li>
<li><strong>Jewelry-quality shaped stones:</strong> cabbing.</li>
<li><strong>Flat slabs and faces:</strong> flat lapping.</li>
</ul>
<p>Whatever the method, polishing is about removing scratches in steps. Never skip a grit, and clean the stone between steps so coarse grit does not carry over.</p>', 'tumblers', 1, 36)
ON DUPLICATE KEY UPDATE title = VALUES(title), excerpt = VALUES(excerpt), meta_description = VALUES(meta_description), body = VALUES(body), category_link = VALUES(category_link), published = 1;
