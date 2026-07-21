# KOVCHEG Blog v1

## Product goal

KOVCHEG Blog is a fast self-hosted publishing and portfolio platform built on KOVCHEG CMS 3.0 Yadro.

It must support:

- a personal development blog;
- portfolio sites for musicians, builders, artists and other professionals;
- custom pages and navigation menus;
- registered readers with profiles, avatars, comments and reactions;
- roles: owner, administrator, moderator, user and guest;
- selectable public themes;
- a stable redesigned administration panel;
- installable modules without changing core files.

## Core content model

All public materials use one content entity with a `type` field:

- `post` — chronological blog article;
- `page` — static page;
- `portfolio` — portfolio item or completed project.

Every entry has a slug, title, excerpt, block content, publication status, featured image, SEO fields, author and publication date.

## Page editor

The editor stores structured blocks in JSON and produces sanitized HTML for public rendering. Initial blocks:

- heading;
- paragraph;
- image;
- gallery;
- video/embed;
- quote;
- list;
- button;
- columns;
- spacer;
- code;
- portfolio facts.

Raw arbitrary PHP and JavaScript are never accepted from the editor.

## Public information architecture

- `/` — configurable home page;
- `/blog` — article archive;
- `/blog/{slug}` — article;
- `/page/{slug}` — static page;
- `/portfolio` — portfolio archive;
- `/portfolio/{slug}` — portfolio item;
- `/author/{username}` — public author profile;
- `/account` — reader account;
- `/login` and `/register` — authentication.

## Administration

Primary navigation:

1. Dashboard
2. Posts
3. Pages
4. Portfolio
5. Media
6. Comments
7. Menus
8. Appearance
9. Modules
10. Users and roles
11. Settings
12. System

The administration theme is independent from public themes so a broken public theme cannot make the control panel inaccessible.

## Themes

Themes live in `themes/<slug>/` and contain:

- `theme.json` manifest;
- public layout and view templates;
- CSS and JavaScript assets;
- optional block templates;
- screenshot and metadata.

The first themes will be:

- `kovcheg-editorial` — modern blog and product journal;
- `kovcheg-portfolio` — visual professional portfolio;
- `kovcheg-minimal` — lightweight text-focused site.

## Modules

Modules live in `modules/<slug>/`, register routes and hooks through one bootstrap file, and may ship namespaced source files, views, assets and migrations declared in a checksummed manifest.

Core files are never overwritten by ordinary modules.

## Delivery stages

### Stage 1 — Foundation

- content database tables;
- public blog routing;
- first clean public theme;
- role `moderator`;
- migration runner;
- compatibility with the existing installed database.

### Stage 2 — Content administration

- post/page/portfolio lists;
- create/edit/publish workflow;
- categories and tags;
- media picker;
- comments moderation.

### Stage 3 — Visual page builder

- block editor;
- live preview;
- reusable sections;
- page templates.

### Stage 4 — Themes and modules

- theme manager;
- theme package validation;
- improved module packages;
- starter modules.

### Stage 5 — Portfolio presets

- musician;
- builder/craftsman;
- artist/photographer;
- developer/company.

## Ownership

Author and copyright: Ланцет Семён Борисович.

License: proprietary / all rights reserved.
