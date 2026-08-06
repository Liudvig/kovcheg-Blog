<?php

declare(strict_types=1);

use Kovcheg\Blog\Blog;

/*
 * Saved links from older KOVCHEG Blog versions remain HTTP 200.
 * They are not added to menus and do not create mandatory site sections.
 */
$router->get('/blog', function (): void {
    header('X-Robots-Tag: noindex, follow');
    Blog::render('home', [
        'title'=>(string)setting('site_name', 'KOVCHEG CMS'),
        'posts'=>Blog::entries('post', max(6, min(36, (int)setting('blog_posts_per_page', '18')))),
    ]);
});

$router->get('/portfolio', function (): void {
    header('X-Robots-Tag: noindex, follow');
    Blog::render('home', [
        'title'=>(string)setting('site_name', 'KOVCHEG CMS'),
        'posts'=>Blog::entries('post', max(6, min(36, (int)setting('blog_posts_per_page', '18')))),
    ]);
});
