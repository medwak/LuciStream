<?php partial('header', ['section' => 'mylist', 'pageTitle' => 'My List']); ?>
<div class="container">
    <div class="page-head">
        <h1>My List</h1>
        <p class="muted">Bookmarks and watch history are saved in this browser only.</p>
    </div>
    <section class="section">
        <div class="section-head"><h2>Bookmarks</h2></div>
        <div class="grid grid-wide" data-bookmark-list></div>
        <p class="empty" data-bookmark-empty hidden>You haven't bookmarked anything yet. Use “+ Add to List” on a series page.</p>
    </section>
    <section class="section">
        <div class="section-head"><h2>Watch History</h2><button class="link-btn" data-clear-history>Clear</button></div>
        <div class="grid grid-wide" data-history-list></div>
        <p class="empty" data-history-empty hidden>No watch history yet.</p>
    </section>
</div>
<?php partial('footer'); ?>
