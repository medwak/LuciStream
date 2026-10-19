<?php
declare(strict_types=1);

/**
 * Inserts fictional demo series so the site has something to show on first run.
 * Video sources point to the open-licence "Big Buck Bunny" film by the Blender Foundation.
 */
function seed_demo(PDO $pdo): void
{
    $genres = ['Action', 'Adventure', 'Comedy', 'Cultivation', 'Drama', 'Fantasy', 'Historical',
        'Martial Arts', 'Mystery', 'Reincarnation', 'Romance', 'Sci-Fi', 'Supernatural'];
    $genreIds = [];
    $g = $pdo->prepare('INSERT INTO genres (name, slug) VALUES (?, ?)');
    foreach ($genres as $name) {
        $g->execute([$name, slugify($name)]);
        $genreIds[$name] = (int) $pdo->lastInsertId();
    }

    $series = [
        ['Sword of the Nine Heavens', 'Jiu Tian Jian', 'Ongoing', 'ONA', 2025, 'Azure Peak Studio', 8.7, 'Monday', 1, 24,
            ['Action', 'Cultivation', 'Martial Arts', 'Fantasy'],
            'A disgraced disciple discovers a fragment of an ancient sword spirit sealed inside his broken meridians. To restore his sect\'s honour he must climb the nine heavens, where every realm hides a new trial and an old enemy.'],
        ['Celestial Flame Emperor', 'Tian Yan Di Zun', 'Ongoing', 'ONA', 2024, 'Crimson Lotus Animation', 8.9, 'Tuesday', 1, 36,
            ['Action', 'Cultivation', 'Fantasy', 'Reincarnation'],
            'Reborn ten thousand years in the past, the once-supreme Flame Emperor sets out to correct the mistakes that led to his downfall, armed only with memories and a heart of unquenchable fire.'],
        ['Legend of the Jade Phoenix', 'Yu Feng Chuan Shuo', 'Completed', 'TV', 2023, 'Silver Willow Works', 8.2, '', 1, 16,
            ['Romance', 'Historical', 'Drama', 'Fantasy'],
            'A palace physician with a hidden phoenix bloodline is drawn into a deadly struggle for the throne, where love and loyalty are the most dangerous medicines of all.'],
        ['Martial Peak Ascendant', 'Wu Feng Deng Tian', 'Ongoing', 'ONA', 2022, 'Azure Peak Studio', 8.4, 'Wednesday', 0, 40,
            ['Action', 'Martial Arts', 'Adventure'],
            'Sweeping the steps of a small sect for years, a humble servant finds a black book that turns every hardship into strength. His climb to the peak of martial arts begins at the very bottom.'],
        ['The Immortal Who Returned', 'Xian Zun Gui Lai', 'Ongoing', 'ONA', 2025, 'Moonstone Pictures', 8.1, 'Thursday', 1, 18,
            ['Cultivation', 'Comedy', 'Reincarnation'],
            'After five hundred years of cultivation in another world, an immortal returns to modern-day Earth only to find his family in debt and his old classmates all grown up.'],
        ['Soul Forge Chronicles', 'Hun Zhu Ji', 'Ongoing', 'ONA', 2024, 'Ironleaf Studio', 7.9, 'Friday', 0, 22,
            ['Action', 'Sci-Fi', 'Supernatural'],
            'In a city powered by bound spirits, a young blacksmith learns to forge weapons from souls and uncovers the conspiracy behind the city\'s eternal night.'],
        ['Moonlit Blade Sect', 'Yue Ying Dao Zong', 'Ongoing', 'ONA', 2025, 'Crimson Lotus Animation', 8.0, 'Saturday', 0, 12,
            ['Martial Arts', 'Mystery', 'Historical'],
            'A string of moonlit assassinations points to a sect that vanished decades ago. A wandering swordswoman and a blind archivist follow the trail of blood.'],
        ['Dragon Vein Heir', 'Long Mai Chuan Ren', 'Ongoing', 'ONA', 2023, 'Moonstone Pictures', 8.3, 'Sunday', 0, 30,
            ['Action', 'Adventure', 'Fantasy'],
            'The last descendant of the dragon clans awakens the vein of his ancestors and must decide whether to restore their empire or end the cycle of war forever.'],
        ['Thousand Star Pavilion', 'Qian Xing Ge', 'Completed', 'ONA', 2021, 'Silver Willow Works', 8.6, '', 0, 26,
            ['Fantasy', 'Romance', 'Adventure'],
            'Every star in the sky is a sealed world, and the keeper of the Thousand Star Pavilion has just lost the key to all of them.'],
        ['Eternal Lotus Realm', 'Yong Heng Lian Jie', 'Completed', 'TV', 2020, 'Ironleaf Studio', 7.8, '', 0, 20,
            ['Cultivation', 'Drama', 'Supernatural'],
            'A blooming lotus can grant eternity, but each petal costs a memory. A young cultivator must choose what she is willing to forget.'],
        ['Spirit Beast Tamer', 'Ling Shou Shi', 'Ongoing', 'ONA', 2026, 'Azure Peak Studio', 7.6, 'Monday', 0, 8,
            ['Adventure', 'Comedy', 'Fantasy'],
            'Paired with the laziest spirit beast in the academy, a determined girl aims to become the greatest tamer of her generation.'],
        ['Throne of the Void Monarch', 'Xu Kong Wang Zuo', 'Completed', 'Movie', 2024, 'Crimson Lotus Animation', 8.5, '', 0, 1,
            ['Action', 'Fantasy', 'Supernatural'],
            'The feature-length tale of the monarch who sealed the void and the price his kingdom paid for peace.'],
    ];

    $insSeries = $pdo->prepare('INSERT INTO series (title, slug, alt_title, synopsis, type, status, release_year, studio,
        rating, release_day, featured, views, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insSg = $pdo->prepare('INSERT INTO series_genres (series_id, genre_id) VALUES (?, ?)');
    $insEp = $pdo->prepare('INSERT INTO episodes (series_id, number, title, views, created_at) VALUES (?, ?, ?, ?, ?)');
    $insSrv = $pdo->prepare('INSERT INTO servers (episode_id, name, kind, url, sort) VALUES (?, ?, ?, ?, ?)');

    $pdo->beginTransaction();
    foreach ($series as $i => [$title, $alt, $status, $type, $year, $studio, $rating, $day, $featured, $epCount, $gs, $synopsis]) {
        // Stagger timestamps so "latest" ordering looks natural.
        $latest = time() - ($i * 5400) - random_int(0, 3000);
        $created = date('Y-m-d H:i:s', $latest - $epCount * 7 * 86400);
        $insSeries->execute([$title, slugify($title), $alt, $synopsis, $type, $status, $year, $studio, $rating, $day,
            $featured, random_int(500, 90000), $created, date('Y-m-d H:i:s', $latest)]);
        $sid = (int) $pdo->lastInsertId();
        foreach ($gs as $name) {
            $insSg->execute([$sid, $genreIds[$name]]);
        }
        for ($n = 1; $n <= $epCount; $n++) {
            $ts = date('Y-m-d H:i:s', $latest - ($epCount - $n) * 7 * 86400);
            $epTitle = $type === 'Movie' ? 'Full Movie' : '';
            $insEp->execute([$sid, $n, $epTitle, random_int(50, 9000), $ts]);
            $eid = (int) $pdo->lastInsertId();
            $insSrv->execute([$eid, 'Server 1', 'video',
                'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', 0]);
            $insSrv->execute([$eid, 'Server 2', 'embed', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ', 1]);
        }
    }
    $pdo->commit();
}
