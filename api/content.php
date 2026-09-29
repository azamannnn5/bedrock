<?php
/**
 * GET /api/content.php
 * Returns admin-editable content blocks: the About page body text, and
 * per-category intro blurbs (keyed by category slug).
 */

require_once __DIR__ . '/config.php';
api_headers();

$db = get_db();
$aboutContent = get_setting('about_page_content', '');
$catRows = $db->query("SELECT category_slug, description FROM category_content")->fetchAll();
$categoryContent = [];
foreach ($catRows as $row) {
    if (trim($row['description']) !== '') {
        $categoryContent[$row['category_slug']] = $row['description'];
    }
}

json_response([
    'aboutPageContent' => $aboutContent,
    'categoryContent'  => $categoryContent,
]);
