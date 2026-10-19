# LuciStream

A donghua (Chinese animation) streaming website written in plain PHP: no framework, no Composer and no build step.
It uses SQLite, so there is no database server to set up.

## Features

**Public site**
- Homepage with a featured slider, the latest episodes, ongoing and completed rows, and a "Most Popular" sidebar
- Series pages with poster, banner, synopsis, genres, studio, year, airing day, an episode grid with a filter, and related series
- Watch page with several servers per episode (iframe embeds or direct MP4/WebM), Prev/Next, and an episode sidebar
- Browse with filters (genre, status, type, year, sort) and pagination
- Search with live suggestions as you type
- Weekly release schedule, A-Z list, and genre pages
- "My List" bookmarks and "Continue Watching" history, saved in the visitor's browser
- Responsive dark theme, SEO meta and Open Graph tags, and clean URLs (`/series/slug`, `/watch/slug-episode-12`)
- Placeholder posters and banners are generated automatically until you upload real artwork

**Admin panel** (`/admin`)
- Dashboard with stats and recently added episodes
- Create, edit and delete series: upload poster and banner images or paste an image URL, set genres, and mark as featured
- Add, edit and delete episodes with any number of video servers (paste a URL or a whole `<iframe>` embed code)
- Manage genres, and change the admin username and password
- CSRF protection, hashed passwords, login throttling, and checks on uploaded images

## Requirements

- PHP 8.1 or newer with `pdo_sqlite` (enabled by default on almost every host)
- Apache with `mod_rewrite` (most shared hosting), Nginx, or PHP's built-in server for local testing

## Run locally

```bash
php -S localhost:8000 router.php
```

Open http://localhost:8000. The database is created in `data/` on the first visit and filled with demo series.

Admin: http://localhost:8000/admin, username **admin**, password **admin123**. Change the password right after you log in (Admin → Account).

## Deploy to shared hosting (cPanel and similar)

1. Upload every file, including `.htaccess`, to `public_html/` (or to a subfolder).
2. Make sure `data/` and `uploads/` are writable by PHP (usually 755 or 775).
3. Edit `config.php`: site name, timezone, and set `seed_demo` to `false` if you don't want the demo series.
4. Visit the site once to create the database, then log in to `/admin` and change the password.

If you get 404s on every page except the homepage, the host has no `mod_rewrite`. Set `'pretty_urls' => false` in `config.php`.

### Nginx

```nginx
location ~ ^/(data|includes|pages)/ { deny all; }
location = /config.php { deny all; }
location ~ ^/uploads/.*\.php$ { deny all; }
location / { try_files $uri $uri/ /index.php?$query_string; }
```

## Adding content

1. **Admin → Series → New series.** Fill in the details and pick genres. Tick *Feature on the homepage slider* for the hero slider.
2. **Episodes → Add episode.** Enter the number and add one or more servers:
   - **Embed (iframe):** an embed URL from a video host, or paste the full `<iframe …>` code.
   - **Direct video:** a link straight to an `.mp4` or `.webm` file.
3. Adding an episode moves the series to the top of "Latest Episodes".

## Project layout

```
index.php            front controller / router
router.php           router for PHP's built-in server
config.php           settings
includes/            bootstrap, database + schema, helpers, demo seed, partials (header, footer, cards…)
pages/               public pages (home, series, watch, browse, search, schedule, az, mylist, api, img)
admin/               admin panel
assets/              CSS, JS, favicon
data/                SQLite database (blocked from web access)
uploads/             uploaded posters and banners
```

## Content notice

The demo data uses made-up titles. Its video sources point to *Big Buck Bunny*, an openly licensed film by the Blender Foundation.
Only publish videos you own or have permission to stream.
